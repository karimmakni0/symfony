<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Resources;
use App\Entity\User;
use App\Entity\Reservation;
use App\Entity\Users;
use App\Form\ActivityFormType;
use App\Repository\ActivitiesRepository;
use App\Repository\BilletRepository;
use App\Repository\DestinationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe;

class ActivitiesController extends AbstractController
{
    private $entityManager;
    private $activitiesRepository;
    private $destinationsRepository;
    private $resourcesRepository;
    private $billetRepository;
    private $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivitiesRepository $activitiesRepository,
        DestinationsRepository $destinationsRepository,
        ResourcesRepository $resourcesRepository,
        BilletRepository $billetRepository,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->activitiesRepository = $activitiesRepository;
        $this->destinationsRepository = $destinationsRepository;
        $this->resourcesRepository = $resourcesRepository;
        $this->billetRepository = $billetRepository;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route("/api/activities", name: 'api_activities_index', methods: ['GET'])]
    public function index(): Response
    {
        // Will implement later
        return new Response('Activities index');
    }
    
    #[Route('/activities', name: 'app_activities')]
    public function clientActivities(): Response
    {
        // Get all activities to display to clients with their resources
        $activities = $this->activitiesRepository->findAllWithResources();
        
        return $this->render('client/Activities/index.html.twig', [
            'activities' => $activities
        ]);
    }
    
    #[Route('/client/activities/{id}', name: 'app_client_activity_detail')]
    public function clientActivityDetail(int $id): Response
    {
        // Find the activity
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists
        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }
        
        // Get total booked tickets for this activity
        $totalBookedTickets = $this->billetRepository->getTotalBookedTicketsForActivity($id);
        
        return $this->render('client/Activities/Details.html.twig', [
            'activity' => $activity,
            'totalBookedTickets' => $totalBookedTickets
        ]);
    }
    
    #[Route('/publicator/activities', name: 'app_publicator_activities')]
    public function publicatorActivities(): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get activities created by this publicator
        $activities = $this->activitiesRepository
            ->findBy(['user' => $user], ['created_at' => 'DESC']);
        
        return $this->render('publicator/activities/index.html.twig', [
            'activities' => $activities,
        ]);
    }
    
    #[Route('/publicator/activities/add', name: 'app_publicator_add_activity')]
    public function addActivity(Request $request): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Create a new activity
        $activity = new Activities();
        $activity->setUser($user);
        
        // Get all destinations created by this user for the dropdown
        $destinations = $this->destinationsRepository->findBy(['user' => $user]);
        
        // Create a choices array for the destinations dropdown
        $destinationsChoices = [];
        foreach ($destinations as $destination) {
            $destinationsChoices[$destination->getName() . ' (' . $destination->getLocation() . ')'] = $destination->getName();
        }
        
        // Create the form with our destinations choices
        $form = $this->createForm(ActivityFormType::class, $activity, [
            'destinations_choices' => $destinationsChoices,
            'images_required' => true
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Save the activity first to get its ID
            $this->entityManager->persist($activity);
            $this->entityManager->flush();
            
            // Handle image uploads
            $imageFiles = $form->get('activity_images')->getData();
            if ($imageFiles) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/activities';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile) {
                        // Simple filename generation without transliterator
                        $newFilename = 'activity-' . uniqid() . '-' . time() . '.' . $imageFile->guessExtension();
                        
                        try {
                            $imageFile->move(
                                $uploadsDirectory,
                                $newFilename
                            );
                            
                            // Create a new resource for this image
                            $resource = new Resources();
                            $resource->setPath('/uploads/activities/' . $newFilename);
                            $resource->setActivity($activity);
                            
                            $this->entityManager->persist($resource);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        }
                    }
                }
                
                // Save all resources
                $this->entityManager->flush();
            }
            
            $this->addFlash('success', 'Activity created successfully!');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        return $this->render('publicator/activities/add.html.twig', [
            'form' => $form->createView(),
            'destinations' => $destinations
        ]);
    }
    
    #[Route('/publicator/activities/edit/{id}', name: 'app_publicator_edit_activity')]
    public function editActivity(Request $request, int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists and belongs to the current user
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Activity not found or you don\'t have permission to edit it.');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        // Get all destinations created by this user for the dropdown
        $destinations = $this->destinationsRepository->findBy(['user' => $user]);
        
        // Create a choices array for the destinations dropdown
        $destinationsChoices = [];
        foreach ($destinations as $destination) {
            $destinationsChoices[$destination->getName() . ' (' . $destination->getLocation() . ')'] = $destination->getName();
        }
        
        // Get all resources (images) for this activity
        $resources = $this->resourcesRepository->findBy(['activity' => $activity]);
        
        // Create the form with our destinations choices but don't require images for edit
        $form = $this->createForm(ActivityFormType::class, $activity, [
            'destinations_choices' => $destinationsChoices,
            'images_required' => false
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image uploads
            $imageFiles = $form->get('activity_images')->getData();
            if ($imageFiles) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/activities';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                
                foreach ($imageFiles as $imageFile) {
                    if ($imageFile) {
                        // Simple filename generation without transliterator
                        $newFilename = 'activity-' . uniqid() . '-' . time() . '.' . $imageFile->guessExtension();
                        
                        try {
                            $imageFile->move(
                                $uploadsDirectory,
                                $newFilename
                            );
                            
                            // Create a new resource for this image
                            $resource = new Resources();
                            $resource->setPath('/uploads/activities/' . $newFilename);
                            $resource->setActivity($activity);
                            
                            $this->entityManager->persist($resource);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        }
                    }
                }
            }
            
            // Handle image deletions if any
            $imagesToDelete = $request->request->get('delete_images', []);
            foreach ($imagesToDelete as $resourceId) {
                $resource = $this->resourcesRepository->find($resourceId);
                if ($resource && $resource->getActivity() === $activity) {
                    // Remove the file from storage
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $resource->getPath();
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    
                    // Remove from database
                    $this->entityManager->remove($resource);
                }
            }
            
            // Save all changes
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Activity updated successfully!');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        return $this->render('publicator/activities/edit.html.twig', [
            'form' => $form->createView(),
            'activity' => $activity,
            'destinations' => $destinations,
            'resources' => $resources
        ]);
    }
    
    #[Route('/publicator/activities/detail/{id}', name: 'app_publicator_activity_detail')]
    public function activityDetail(int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists and belongs to the current user
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Activity not found or you don\'t have permission to view it.');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        return $this->render('publicator/Activities/detail.html.twig', [
            'activity' => $activity
        ]);
    }
    
    #[Route('/publicator/activities/delete/{id}', name: 'app_publicator_delete_activity')]
    public function deleteActivity(int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists and belongs to the current user
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Activity not found or you don\'t have permission to delete it.');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        try {
            // DIRECT APPROACH: Delete resources first
            $resources = $this->resourcesRepository->findBy(['activity' => $activity]);
            
            // Process each resource
            foreach ($resources as $resource) {
                // Try to delete the physical file
                try {
                    $filePath = $this->getParameter('kernel.project_dir') . '/public' . $resource->getPath();
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                } catch (\Exception $e) {
                    // Just log this error but continue with deletion
                    error_log('Failed to delete file: ' . $e->getMessage());
                }
                
                // Remove the resource from database
                $this->entityManager->remove($resource);
            }
            
            // Flush resource deletions
            $this->entityManager->flush();
            
            // Now delete the activity
            $this->entityManager->remove($activity);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Activity deleted successfully!');
        } catch (\Exception $e) {
            // Log the detailed error for debugging
            error_log('Error deleting activity: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            
            $this->addFlash('error', 'Error deleting activity. Please contact the administrator.');
        }
        
        return $this->redirectToRoute('app_publicator_activities');
    }
    
    #[Route('/publicator/profile', name: 'app_publicator_profile')]
    public function profile(): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('publicator/profile/index.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/api/activities/{id}/tickets', name: 'app_api_activity_tickets')]
    public function getActivityTickets(int $id): Response
    {
        $activity = $this->activitiesRepository->find($id);
        if (!$activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }
        
        // Use the new repository method to get all tickets for this activity
        $billets = $this->billetRepository->findByActivityId($activity->getId());
        
        // Format the tickets for the JSON response
        $ticketsData = [];
        foreach ($billets as $billet) {
            $ticketsData[] = [
                'id' => $billet->getId(),
                'price' => $billet->getPrix(),
                'number' => $billet->getNumero(),
                'participants' => $billet->getNb(),
            ];
        }
        
        return $this->json([
            'activity_id' => $activity->getId(),
            'activity_name' => $activity->getActivityName(),
            'tickets_count' => count($billets),
            'tickets' => $ticketsData
        ]);
    }
    
    #[Route('/payment-reservation/{id}', name: 'app_payment_reservation')]
    public function paymentReservation(Request $request, int $id): Response
    {
        // Check if user is logged in
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Find the activity
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists
        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }
        
        // Get participants count from form
        $participants = $request->request->getInt('participants', 1);
        
        // Validate participants count
        $totalBookedTickets = $this->billetRepository->getTotalBookedTicketsForActivity($id);
        $remainingTickets = $activity->getMaxNumber() - $totalBookedTickets;
        
        if ($participants <= 0 || $participants > $remainingTickets) {
            $this->addFlash('error', 'Invalid number of participants');
            return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
        }
        
        // Calculate total price
        $unitPrice = $activity->getActivityPrice();
        $totalPrice = $participants * $unitPrice;
        
        // Process form submission for credit card payment
        if ($request->isMethod('POST') && $request->request->has('payment_submit')) {
            // Validate basic credit card info (just checking if fields are filled in)
            $cardNumber = $request->request->get('card_number');
            $cardName = $request->request->get('card_name');
            $expiryDate = $request->request->get('expiry_date');
            $cvv = $request->request->get('cvv');
            
            if (empty($cardNumber) || empty($cardName) || empty($expiryDate) || empty($cvv)) {
                $this->addFlash('error', 'Please fill in all payment details');
            } else {
                try {
                    // Process payment with Stripe
                    $paymentProcessed = $this->processStripePayment($cardNumber, $cardName, $expiryDate, $cvv, $totalPrice);
                    
                    if (!$paymentProcessed) {
                        $this->addFlash('error', 'Payment processing failed. Please try again.');
                        return $this->render('client/Activities/passReservation.html.twig', [
                            'activity' => $activity,
                            'participants' => $participants,
                            'totalPrice' => $totalPrice,
                            'user' => $user
                        ]);
                    }
                    try {
                        // Begin transaction
                        $this->entityManager->beginTransaction();
                        // Always create a new Billet for each reservation
                        $billet = new Billet();
                        $billet->setActiviteId($activity->getId());
                        $billet->setPrix($unitPrice);
                        $billet->setNb($participants);
                        $billet->setNumero('TICKET-' . uniqid());
                        $this->entityManager->persist($billet);
                        $this->entityManager->flush(); // ensures billet gets a unique auto-incremented id
                        // Always create a new Reservation for each booking
                        $reservation = new Reservation();
                        $reservation->setUser($user); // must be the managed User entity
                        $reservation->setBillet($billet);
                        $reservation->setDateAchat(date('Y-m-d H:i:s'));
                        $reservation->setNombre($participants);
                        $reservation->setPrixTotal($totalPrice);
                        $reservation->setPrixUnite($unitPrice);
                        $reservation->setStatuts('confirmed');
                        $this->entityManager->persist($reservation);
                        $this->entityManager->flush(); // ensures reservation gets a unique auto-incremented id
                        $this->addFlash('success', 'Your reservation was successful! Ticket number: ' . $billet->getNumero());
                        $this->entityManager->commit();
                        return $this->redirectToRoute('app_user_reservation_history');
                    } catch (\Exception $e) {
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->rollback();
                        }
                        error_log('Reservation error: ' . $e->getMessage());
                        $this->addFlash('error', 'Error: ' . $e->getMessage());
                        return $this->render('client/Activities/passReservation.html.twig', [
                            'activity' => $activity,
                            'participants' => $participants,
                            'totalPrice' => $totalPrice,
                            'user' => $user
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log('Payment error: ' . $e->getMessage());
                    $this->addFlash('error', 'Error: ' . $e->getMessage());
                }
            }
        }
        
        return $this->render('client/Activities/passReservation.html.twig', [
            'activity' => $activity,
            'participants' => $participants,
            'totalPrice' => $totalPrice,
            'user' => $user
        ]);
    }

    /**
     * Process payment with Stripe
     */
    private function processStripePayment($cardNumber, $cardName, $expiryDate, $cvv, $amount)
    {
        try {
            // Set your secret key directly 
            Stripe\Stripe::setApiKey('sk_test_51QWkPJDv0oob45G0dizhQzmMeUY6LcdW8POzhvJ6jJ0Mv9Do9GS2WC7XAq3ZDufBCaJuGRbaYl7NrtoyJxpgdx5d00FIR9nfuJ');
            
            // For testing purposes, we'll use a test token
            // In production, you would generate tokens client-side using Stripe.js
            $testToken = 'tok_visa'; // This represents a successful Visa card payment
            
            // Create charge using the test token
            $charge = Stripe\Charge::create([
                'amount' => (int)($amount * 100), // Convert to cents and ensure it's an integer
                'currency' => 'usd', // Using USD for test tokens
                'source' => $testToken,
                'description' => 'Activity reservation',
            ]);
            
            return $charge->status === 'succeeded';
        } catch (\Stripe\Exception\CardException $e) {
            // Card declined
            error_log('Stripe CardException: ' . $e->getMessage());
            $this->addFlash('error', 'Payment failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            // Other error
            error_log('Stripe Error: ' . $e->getMessage());
            $this->addFlash('error', 'Payment processing error: ' . $e->getMessage());
            return false;
        }
    }
}
