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
}
