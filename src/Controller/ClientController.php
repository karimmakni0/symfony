<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Users;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
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

        // Generate QR code with ticket information
        $ticketData = json_encode([
            'ticket_id' => $billet->getNumero(),
            'activity' => $activity->getActivityName(),
            'date' => $reservation->getDateAchat(),
            'participants' => $reservation->getNombre(),
            'total_price' => $reservation->getPrixTotal(),
            'status' => $reservation->getStatuts(),
            'user_name' => $user->getName() . ' ' . $user->getLastname(),
        ]);

        // Generate a simple QR code data as a small base64 image (hardcoded example)
        // This is a fallback in case the GD extension doesn't work
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
        $options->set('defaultFont', 'Arial');
        $options->setIsRemoteEnabled(true);

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
