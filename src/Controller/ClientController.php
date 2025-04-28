<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Reservation;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\BuilderInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Color\Color;

#[Route('/client')]
class ClientController extends AbstractController
{
    #[Route('/', name: 'app_client')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'user') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get recent bookings for this client
        $bookings = $entityManager->getRepository(Billet::class)
            ->findBy(
                ['client' => $user],
                ['createdAt' => 'DESC'],
                5
            );
            
        // Count bookings
        $bookingsCount = $entityManager->getRepository(Billet::class)
            ->count(['client' => $user]);
            
        // Get recently viewed activities (this would need to be implemented with a session or cookie system)
        $recentActivities = $entityManager->getRepository(Activities::class)
            ->findBy(
                [], // No specific criteria, just get the latest ones 
                ['createdAt' => 'DESC'], 
                6
            );
        
        return $this->render('client/dashboard.html.twig', [
            'controller_name' => 'ClientController',
            'bookings' => $bookings,
            'bookingsCount' => $bookingsCount,
            'recentActivities' => $recentActivities,
        ]);
    }
    
    /**
     * Profile Management Section
     */
    
    #[Route('/profile', name: 'app_client_profile')]
    public function profile(): Response
    {
        // Check if user is logged in and has appropriate role
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('client/profile/index.html.twig', [
            'user' => $user,
        ]);
    }
    
    #[Route('/profile/update-personal', name: 'app_client_profile_update_personal', methods: ['POST'])]
    public function updatePersonal(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Process form submission for personal information
        if ($request->isMethod('POST')) {
            /** @var Users $user */
            $user->setName($request->request->get('name'));
            $user->setLastname($request->request->get('lastname'));
            $user->setEmail($request->request->get('email'));
            $user->setPhone($request->request->get('phone'));
            
            // Process birthday if provided (format: YYYY-MM-DD)
            $birthday = $request->request->get('birthday');
            if ($birthday) {
                $user->setBirthday(new \DateTime($birthday));
            }
            
            // Process profile picture upload
            /** @var UploadedFile $profilePicture */
            $profilePicture = $request->files->get('avatar');
            if ($profilePicture) {
                $originalFilename = pathinfo($profilePicture->getClientOriginalName(), PATHINFO_FILENAME);
                // Sanitize filename and add a unique ID
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$profilePicture->guessExtension();
                
                // Move the file to the directory where profile pictures are stored
                try {
                    $profilePicture->move(
                        'assets/img/UserPictures',
                        $newFilename
                    );
                    
                    // Delete old profile picture if exists
                    $oldImagePath = $user->getImage();
                    if ($oldImagePath && file_exists('assets/img/UserPictures/'.$oldImagePath)) {
                        unlink('assets/img/UserPictures/'.$oldImagePath);
                    }
                    
                    // Update user entity with new image name
                    $user->setImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'There was a problem uploading your profile picture.');
                }
            }
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your personal information has been updated!');
        }
        
        return $this->redirectToRoute('app_client_profile');
    }
    
    #[Route('/profile/change-password', name: 'app_client_profile_change_password', methods: ['POST'])]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        /** @var Users $user */
        
        // Process form submission for password change
        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $newPasswordConfirm = $request->request->get('new_password_confirmation');
            
            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_client_profile');
            }
            
            // Verify password confirmation
            if ($newPassword !== $newPasswordConfirm) {
                $this->addFlash('error', 'The new password fields must match.');
                return $this->redirectToRoute('app_client_profile');
            }
            
            // Password length validation
            if (strlen($newPassword) < 6) {
                $this->addFlash('error', 'Password must be at least 6 characters long.');
                return $this->redirectToRoute('app_client_profile');
            }
            
            // Set new password
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your password has been changed successfully!');
        }
        
        return $this->redirectToRoute('app_client_profile');
    }

    /**
     * Generate PDF ticket with QR code for a booking
     */
    #[Route('/reservations/ticket/{id}', name: 'app_client_reservation_ticket')]
    public function generateTicket(int $id, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Find the billet
        $billet = $entityManager->getRepository(Billet::class)->find($id);
        if (!$billet) {
            $this->addFlash('error', 'Ticket not found.');
            return $this->redirectToRoute('app_client');
        }
        
        // Find the reservation associated with this billet
        $reservation = $entityManager->getRepository(Reservation::class)->findOneBy(['billet' => $billet]);
        if (!$reservation) {
            $this->addFlash('error', 'Reservation not found for this ticket.');
            return $this->redirectToRoute('app_client');
        }
        
        // Check if the reservation belongs to this user
        if ($reservation->getUser()->getId() !== $user->getId()) {
            $this->addFlash('error', 'You do not have permission to access this ticket.');
            return $this->redirectToRoute('app_client');
        }

        // Get the activity associated with this reservation
        $activity = $entityManager->getRepository(Activities::class)->find($billet->getActiviteId());
        if (!$activity) {
            $this->addFlash('error', 'Activity not found for this reservation.');
            return $this->redirectToRoute('app_client');
        }

        // Create a QR code that links to the activity page
        $activityUrl = $this->generateUrl('app_client_activity_detail', ['id' => $activity->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // For now, use a placeholder QR code image to ensure the PDF works
        // This is a blue QR code for demo purposes
        $qrDataUri = 'data:image/png;base64,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';

        // Get Activity image if available
        $activityImage = null;
        if ($activity->getResources() && count($activity->getResources()) > 0) {
            $activityImage = $activity->getResources()[0]->getPath();
        }
        
        // Render the ticket template
        $html = $this->renderView('client/reservations/ticket_pdf.html.twig', [
            'billet' => $billet,
            'reservation' => $reservation,
            'activity' => $activity,
            'user' => $user,
            'qrCode' => $qrDataUri,
            'activityImage' => $activityImage,
            'issueDate' => new \DateTime(),
        ]);

        // Configure PDF options
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->setIsRemoteEnabled(true);
        $options->set('isHtml5ParserEnabled', true);

        // Create PDF
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Stream the PDF as a downloadable file
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="ticket-' . $billet->getNumero() . '.pdf"'
            ]
        );
    }
}
