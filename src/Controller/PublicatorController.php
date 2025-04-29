<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicatorController extends AbstractController
{
    #[Route('/publicator', name: 'app_publicator')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get activities created by this publicator
        $activitiesCount = $entityManager->getRepository(Activities::class)
            ->count(['user' => $user]);
            
        // Get bookings for this publicator's activities
        $activities = $entityManager->getRepository(Activities::class)
            ->findBy(['user' => $user]);
            
        $activityIds = array_map(function($activity) {
            return $activity->getId();
        }, $activities);
        
        $billetsCount = 0;
        $revenue = 0;
        $recentBookings = [];
        $myActivities = [];
        
        if (!empty($activityIds)) {
            // Use custom query to find billets by activiteId
            $billetsRepo = $entityManager->getRepository(Billet::class);
            $qb = $billetsRepo->createQueryBuilder('b');
            $qb->where('b.activiteId IN (:activityIds)')
               ->setParameter('activityIds', $activityIds);
            
            $billets = $qb->getQuery()->getResult();
            $billetsCount = count($billets);
                
            // Calculate revenue (assuming billets have a price field)
            foreach ($billets as $billet) {
                $revenue += $billet->getPrix() ?? 0;
            }
            
            // Get recent bookings - using the query builder as well
            $qbRecent = $billetsRepo->createQueryBuilder('b');
            $qbRecent->where('b.activiteId IN (:activityIds)')
                   ->setParameter('activityIds', $activityIds)
                   ->setMaxResults(5);
            
            $recentBookings = $qbRecent->getQuery()->getResult();
                
            // Get recent activities
            $myActivities = $entityManager->getRepository(Activities::class)
                ->findBy(
                    ['user' => $user],
                    [],
                    10
                );
        }
        
        return $this->render('publicator/index.html.twig', [
            'controller_name' => 'PublicatorController',
            'activitiesCount' => $activitiesCount,
            'billetsCount' => $billetsCount,
            'revenue' => $revenue,
            'recentBookings' => $recentBookings,
            'myActivities' => $myActivities,
        ]);
    }
}
