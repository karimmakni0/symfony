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
        
        // Use QR code API directly - works reliably in PDFs as the image is loaded from the external URL
        // This will render directly in the PDF without any additional processing
        $qrDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($activityUrl);
        
        // Note: We're using the direct URL to the QR code service
        // This will ensure that the QR code appears in the PDF as the URL is accessible
        // during the PDF generation process

        
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
        $options->set('fontDir', $this->getParameter('kernel.project_dir') . '/public/fonts');
        $options->set('fontCache', $this->getParameter('kernel.project_dir') . '/var/cache/fonts');
        $options->setIsRemoteEnabled(true); // Allow loading images from remote URLs (for QR code)
        $options->set('isPhpEnabled', true); // Enable PHP processing
        $options->set('isHtml5ParserEnabled', true); // Enable HTML5 parsing

        // Create PDF with specific page size to ensure content fits
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
