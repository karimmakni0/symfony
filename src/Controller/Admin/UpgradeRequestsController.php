<?php

namespace App\Controller\Admin;

use App\Entity\UpgradeRequests;
use App\Entity\Users;
use App\Repository\UpgradeRequestsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/admin/upgrade-requests')]
class UpgradeRequestsController extends AbstractController
{
    #[Route('/', name: 'admin_upgrade_requests')]
    public function index(Request $request, UpgradeRequestsRepository $repository): Response
    {
        // Get filter parameters from the request
        $filters = [
            'search' => $request->query->get('search', ''),
            'status' => $request->query->get('status', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', '')
        ];
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Get all upgrade requests with filters applied
        $allRequests = $repository->filterRequests($filters);
        
        // Calculate pagination
        $totalItems = count($allRequests);
        $totalPages = ceil($totalItems / $limit);
        
        // Apply pagination (in memory since we already have all results)
        $requests = array_slice($allRequests, ($page - 1) * $limit, $limit);
        
        // Get all possible statuses for the dropdown
        $statuses = ['pending', 'Approved', 'Rejected'];
        
        return $this->render('admin/upgrade_requests/index.html.twig', [
            'requests' => $requests,
            'filters' => $filters,
            'statuses' => $statuses,
            // Pagination data
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]);
    }
    
    #[Route('/{id}/approve', name: 'admin_upgrade_requests_approve')]
    public function approve(int $id, UpgradeRequestsRepository $repository, EntityManagerInterface $entityManager): Response
    {
        // Get the upgrade request entity
        $request = $repository->find($id);
        
        if (!$request) {
            $this->addFlash('error', 'Upgrade request not found');
            return $this->redirectToRoute('admin_upgrade_requests');
        }
        
        // Update request status
        $request->setStatus('Approved');
        $request->setProcessedDate(new \DateTime());
        
        // Update user role
        $user = $request->getUser();
        $user->setRole('Publicitaire');
        
        $entityManager->persist($request);
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'Request approved successfully. User is now a Publicator.');
        
        return $this->redirectToRoute('admin_upgrade_requests');
    }
    
    #[Route('/{id}/reject', name: 'admin_upgrade_requests_reject')]
    public function reject(int $id, UpgradeRequestsRepository $repository, EntityManagerInterface $entityManager): Response
    {
        // Get the upgrade request entity
        $request = $repository->find($id);
        
        if (!$request) {
            $this->addFlash('error', 'Upgrade request not found');
            return $this->redirectToRoute('admin_upgrade_requests');
        }
        
        // Update request status
        $request->setStatus('Rejected');
        $request->setProcessedDate(new \DateTime());
        
        $entityManager->persist($request);
        $entityManager->flush();
        
        $this->addFlash('success', 'Request rejected successfully.');
        
        return $this->redirectToRoute('admin_upgrade_requests');
    }
    
    #[Route('/export-csv', name: 'admin_export_upgrade_requests_csv')]
    public function exportRequestsCsv(UpgradeRequestsRepository $repository, MailerInterface $mailer): Response
    {
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        // Get the current user (admin)
        $admin = $this->getUser();
        if (!$admin || $admin->getRole() !== 'admin') {
            return $this->json([
                'success' => false,
                'message' => 'You do not have permission to export upgrade requests data.'
            ]);
        }
        
        try {
            // Get all upgrade requests
            $requests = $repository->findAll();
            $requestCount = count($requests);
            
            // Log for debugging
            error_log("Found {$requestCount} upgrade requests");
        
            // Create CSV content - Use semicolons as delimiters to better handle commas in content
            $csvContent = "ID;User ID;User Name;User Email;Request Date;Status;Processed Date;Message\n";
            
            foreach ($requests as $request) {
                // Get user information
                $user = $request->getUser();
                $userId = $user ? $user->getId() : 'N/A';
                $userName = $user ? ($user->getName() . ' ' . $user->getLastname()) : 'N/A';
                $userEmail = $user ? $user->getEmail() : 'N/A';
                
                // Format dates
                $requestDate = $request->getRequestDate() ? $request->getRequestDate()->format('Y-m-d H:i:s') : 'N/A';
                $processedDate = $request->getProcessedDate() ? $request->getProcessedDate()->format('Y-m-d H:i:s') : 'N/A';
                
                // Get other data
                $id = $request->getId();
                $status = $request->getStatus();
                $message = str_replace(';', ',', $request->getMessage() ?: '');
                
                // Add row to CSV with semicolon delimiters
                $csvContent .= "{$id};{$userId};{$userName};{$userEmail};{$requestDate};{$status};{$processedDate};{$message}\n";
            }
            
            // Get current timestamp for unique filename
            $now = new \DateTime();
            $timestamp = $now->format('Y-m-d_H-i-s');
            $filename = "upgrade_requests_export_{$timestamp}.csv";
            
            // Save CSV file temporarily
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tempPath, $csvContent);
            // Get admin email
            $adminEmail = $admin->getEmail();
            
            // Format date for email template
            $exportDate = $now->format('F j, Y \\a\\t h:i A');
            
            // Create email with CSV attachment
            $email = (new Email())
                ->from(new Address('hala.omran@jameiconseil.org', 'TripMakers'))
                ->to($adminEmail)
                ->subject('Go Trip - Upgrade Requests Export')
                ->attachFromPath($tempPath, $filename, 'text/csv')
                ->html(
                    $this->renderView('emails/csv_export.html.twig', [
                        'userCount' => $requestCount,
                        'exportDate' => $exportDate
                    ])
                );
            
            // Send the email
            $mailer->send($email);
            
            // Log email sent
            error_log("Upgrade requests CSV export sent to admin email: {$adminEmail}");
            
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $this->json([
                'success' => true,
                'message' => "Upgrade requests data has been exported and sent to your email address: {$adminEmail}",
            ]);
            
        } catch (\Exception $e) {
            // Log the error
            error_log("Error sending upgrade requests CSV export: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // Clean up temp file on error
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $this->json([
                'success' => false,
                'message' => 'An error occurred while sending the export: ' . $e->getMessage(),
            ]);
        } catch (\Throwable $t) {
            // Catch any other type of error
            error_log("Throwable error in CSV export: " . $t->getMessage());
            error_log("Stack trace: " . $t->getTraceAsString());
            
            // Clean up temp file on error
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            return $this->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $t->getMessage(),
            ]);
        }
    }
}
