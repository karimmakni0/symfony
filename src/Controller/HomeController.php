<?php

namespace App\Controller;

use App\Repository\ActivitiesRepository;
use App\Repository\DestinationsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_home")        
     */
    public function index(ActivitiesRepository $activitiesRepository, DestinationsRepository $destinationsRepository): Response
    {
        // Get latest activities
        $activities = $activitiesRepository->findBy(
            [], // No criteria
            ['created_at' => 'DESC'], // Order by creation date descending (newest first)
            4 // Limit to 4 activities
        );

        // Get latest destinations
        $destinations = $destinationsRepository->findBy(
            [], // No criteria               
            ['created_at' => 'DESC'], // Order by creation date descending (newest first)
            5 // Limit to 5 destinations
        );

        return $this->render('client/index.html.twig', [
            'activities' => $activities,
            'destinations' => $destinations,
        ]);
    }
}
