<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Entity\Reservation;
use App\Entity\Users;
use App\Entity\Activities;
use App\Repository\BilletRepository;
use App\Repository\ReservationRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ReservationController extends AbstractController
{
    private $entityManager;
    private $reservationRepository;
    private $billetRepository;
    private $usersRepository;
    private $security;
    private $requestStack;

    public function __construct(
        EntityManagerInterface $entityManager,
        ReservationRepository $reservationRepository,
        BilletRepository $billetRepository,
        UsersRepository $usersRepository,
        Security $security,
        RequestStack $requestStack
    ) {
        $this->entityManager = $entityManager;
        $this->reservationRepository = $reservationRepository;
        $this->billetRepository = $billetRepository;
        $this->usersRepository = $usersRepository;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    #[Route('/user/reservations', name: 'app_user_reservations')]
    public function userReservations(): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservations = $this->reservationRepository->findByUser($user);

        return $this->render('user/reservations/index.html.twig', [
            'reservations' => $reservations
        ]);
    }

    #[Route('/publicator/reservations', name: 'app_publicator_reservations')]
    public function publicatorReservations(): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login'); 
        }

        // Get all activities created by this publicator
        $activities = $this->entityManager->getRepository('App\Entity\Activities')->findBy(['user' => $user]);
        
        // Get activity IDs
        $activityIds = array_map(function($activity) {
            return $activity->getId();
        }, $activities);
        
        // Find reservations for these activities
        $reservations = [];
        if (!empty($activityIds)) {
            $reservations = $this->reservationRepository->findByActivityIds($activityIds);
        }
        
        // Load the activity details for each reservation's billet
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }
        
        return $this->render('publicator/reservations/index.html.twig', [
            'reservations' => $reservations,
            'activities' => $activitiesById
        ]);
    }

    #[Route('/admin/reservations', name: 'app_admin_reservations')]
    public function adminReservations(): Response
    {
        $user = $this->security->getUser();
        if (!$user || $user->getRole() !== 'Admin') {
            return $this->redirectToRoute('app_home');
        }

        // Get all activities
        $activities = $this->entityManager->getRepository('App\Entity\Activities')->findAll();
        
        // Create a lookup array of activities by ID
        $activitiesById = [];
        foreach ($activities as $activity) {
            $activitiesById[$activity->getId()] = $activity;
        }
        
        // Get all reservations
        $reservations = $this->reservationRepository->findAll();
        
        return $this->render('admin/reservations/index.html.twig', [
            'reservations' => $reservations,
            'activities' => $activitiesById
        ]);
    }

    #[Route('/reservation/create/{id}', name: 'app_create_reservation')]
    public function createReservation(Request $request, int $id): Response
    {
        // Get the database connection for direct SQL operations
        $conn = $this->entityManager->getConnection();
        
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to make a reservation');
            return $this->redirectToRoute('app_login');
        }

        // Get activity
        $activity = $this->entityManager->getRepository(Activities::class)->find($id);
        if (!$activity) {
            $this->addFlash('error', 'Activity not found');
            return $this->redirectToRoute('app_client_activities');
        }

        // Get number of participants from form data
        $participants = (int) $request->request->get('participants', 1);
        if ($participants < 1) {
            $participants = 1;
        }
    
        // Manually query the database for booked tickets to avoid any ORM issues
        try {
            $ticketCountSql = "SELECT SUM(nb) as total_booked FROM billet WHERE activiteId = ?"; 
            $ticketStmt = $conn->prepare($ticketCountSql);
            $ticketStmt->bindValue(1, $id, \PDO::PARAM_INT);
            $ticketResult = $ticketStmt->executeQuery();
            $totalBookedTickets = (int)($ticketResult->fetchAssociative()['total_booked'] ?? 0);
            
            $availableTickets = $activity->getMaxNumber() - $totalBookedTickets;
            
            if ($participants > $availableTickets) {
                $this->addFlash('error', 'Not enough tickets available. Only ' . $availableTickets . ' tickets left.');
                return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
            }

            // Calculate total price
            $pricePerPerson = $activity->getActivityPrice();
            $totalPrice = $pricePerPerson * $participants;
            
            // Begin transaction to ensure both operations succeed or fail together
            $conn->beginTransaction();
            
            try {
                // Generate a unique ticket number
                $now = new \DateTime();
                $dateAchat = $now->format('Y-m-d H:i:s');
                $userId = $user->getId();
                $numero = 'TKT-' . uniqid();
                
                // Simple billet creation
                $conn->executeStatement(
                    'INSERT INTO billet (prix, numero, activiteId, nb) VALUES (?, ?, ?, ?)',
                    [$pricePerPerson, $numero, $id, $participants],
                    [\PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT]
                );
                
                // Get the last inserted ID
                $billetId = (int)$conn->lastInsertId();
                
                // Simple reservation creation
                $conn->executeStatement(
                    'INSERT INTO reservation (dateAchat, userId, billetId, nombre, prixTotal, prixUnite, statuts) VALUES (?, ?, ?, ?, ?, ?, ?)',
                    [$dateAchat, $userId, $billetId, $participants, $totalPrice, $pricePerPerson, 'pending'],
                    [\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_STR, \PDO::PARAM_STR, \PDO::PARAM_STR]
                );
                
                // Commit the transaction
                $conn->commit();
                
                // Redirect to reservation history
                $this->addFlash('success', 'Your reservation has been created successfully! You booked ' . $participants . ' place(s) for the activity.');
                return $this->redirectToRoute('app_user_reservation_history');
                
            } catch (\Exception $e) {
                // Roll back the transaction if anything fails
                $conn->rollBack();
                $this->addFlash('error', 'An error occurred while creating your reservation.');
                return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'An error occurred while checking ticket availability.');
            return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
        }
    }

    #[Route('/user/reservation-history', name: 'app_user_reservation_history')]
    public function reservationHistory(): Response
    {
        // Check if user is logged in
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Get all reservations for the user
        $reservations = $this->reservationRepository->findBy(['user' => $user], ['dateAchat' => 'DESC']);
        
        // Prepare data for the view
        $reservationData = [];
        foreach ($reservations as $reservation) {
            $billet = $reservation->getBillet();
            $activityId = $billet->getActiviteId();
            
            // Get activity details
            $activity = $this->entityManager->getRepository(\App\Entity\Activities::class)->find($activityId);
            
            $reservationData[] = [
                'reservation' => $reservation,
                'billet' => $billet,
                'activity' => $activity
            ];
        }

        return $this->render('client/reservations/history.html.twig', [
            'reservationData' => $reservationData
        ]);
    }

    #[Route('/reservation/cancel/{id}', name: 'app_reservation_cancel')]
    public function cancelReservation(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $reservation = $this->reservationRepository->find($id);
        if (!$reservation || ($reservation->getUser() !== $user && $user->getRole() !== 'Admin')) {
            $this->addFlash('error', 'Reservation not found or you do not have permission');
            return $this->redirectToRoute('app_user_reservations');
        }

        // Update status
        $reservation->setStatuts('Cancelled');

        // Return tickets to availability
        $billet = $reservation->getBillet();
        $billet->setNb($billet->getNb() + $reservation->getNombre());

        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation cancelled successfully');

        if ($user->getRole() === 'Admin') {
            return $this->redirectToRoute('app_admin_reservations');
        } else {
            return $this->redirectToRoute('app_user_reservations');
        }
    }

    #[Route('/reservation/update/{id}/{status}', name: 'app_reservation_update_status', methods: ['GET', 'POST'])]
    public function updateReservationStatus(int $id, string $status): Response
    {
        $user = $this->security->getUser();
        if (!$user || ($user->getRole() !== 'Admin' && $user->getRole() !== 'Publicitaire')) {
            $this->addFlash('error', 'Access denied: You must be an Admin or Publicator');
            return $this->redirectToRoute('app_home');
        }

        $reservation = $this->reservationRepository->find($id);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found');
            return $this->redirectToRoute('app_publicator_reservations');
        }

        // Debug logging
        error_log("Updating reservation #{$id} status to: {$status}");

        // Update status
        $reservation->setStatuts($status);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation status updated successfully');

        // Get the referer URL to redirect back to the same page
        $referer = $this->requestStack->getCurrentRequest()->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }
        
        // Fallback if referer is not available
        return $this->redirectToRoute('app_publicator_reservations');
    }
}
