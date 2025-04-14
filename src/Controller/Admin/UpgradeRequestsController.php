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
    public function index(UpgradeRequestsRepository $repository): Response
    {
        // Get all upgrade requests
        $requests = $repository->findBy([], ['request_date' => 'DESC']);
        
        return $this->render('admin/upgrade_requests/index.html.twig', [
            'requests' => $requests
        ]);
    }
    
    #[Route('/{id}/approve', name: 'admin_upgrade_requests_approve')]
    public function approve(UpgradeRequests $request, EntityManagerInterface $entityManager): Response
    {
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
    public function reject(UpgradeRequests $request, EntityManagerInterface $entityManager): Response
    {
        // Update request status
        $request->setStatus('Rejected');
        $request->setProcessedDate(new \DateTime());
        
        $entityManager->persist($request);
        $entityManager->flush();
        
        $this->addFlash('success', 'Request rejected successfully.');
        
        return $this->redirectToRoute('admin_upgrade_requests');
    }
}
