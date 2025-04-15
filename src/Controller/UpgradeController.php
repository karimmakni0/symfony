<?php

namespace App\Controller;

use App\Entity\UpgradeRequests;
use App\Repository\UpgradeRequestsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client')]
class UpgradeController extends AbstractController
{
    #[Route('/upgrade', name: 'app_client_upgrade')]
    public function index(UpgradeRequestsRepository $requestsRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Check if the user already has an upgrade request
        $existingRequest = $requestsRepository->findByUser($user);
        
        return $this->render('client/upgrade.html.twig', [
            'hasExistingRequest' => $existingRequest !== null,
            'requestStatus' => $existingRequest ? ucfirst(strtolower($existingRequest->getStatus())) : null,
            'request' => $existingRequest,
        ]);
    }
    
    #[Route('/upgrade/request', name: 'app_client_upgrade_request', methods: ['POST'])]
    public function submitRequest(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Check if user already has a request
        $existingRequest = $entityManager->getRepository(UpgradeRequests::class)->findByUser($user);
        if ($existingRequest) {
            $this->addFlash('error', 'You already have a pending upgrade request');
            return $this->redirectToRoute('app_client_upgrade');
        }
        
        // Create new upgrade request
        $upgradeRequest = new UpgradeRequests();
        $upgradeRequest->setUser($user);
        $upgradeRequest->setMessage($request->request->get('message'));
        $upgradeRequest->setStatus('Pending');
        
        $entityManager->persist($upgradeRequest);
        $entityManager->flush();
        
        $this->addFlash('success', 'Your request to become a Publicator has been submitted successfully.');
        
        return $this->redirectToRoute('app_client_upgrade');
    }
}
