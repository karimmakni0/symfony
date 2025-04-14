<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Resources;
use App\Repository\ActivitiesRepository;
use App\Repository\BilletRepository;
use App\Repository\DestinationsRepository;
use App\Repository\ResourcesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivitiesController extends AbstractController
{
    private $entityManager;
    private $activitiesRepository;
    private $destinationsRepository;
    private $resourcesRepository;
    private $billetRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivitiesRepository $activitiesRepository,
        DestinationsRepository $destinationsRepository,
        ResourcesRepository $resourcesRepository,
        BilletRepository $billetRepository
    ) {
        $this->entityManager = $entityManager;
        $this->activitiesRepository = $activitiesRepository;
        $this->destinationsRepository = $destinationsRepository;
        $this->resourcesRepository = $resourcesRepository;
        $this->billetRepository = $billetRepository;
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
        
        if ($request->isMethod('POST')) {
            // Handle form submission
            $activityName = $request->request->get('activity_name');
            $activityDescription = $request->request->get('activity_description');
            $activityDestination = $request->request->get('activity_destination');
            $activityDuration = $request->request->get('activity_duration');
            $activityPrice = $request->request->get('activity_price');
            $activityGenre = $request->request->get('activity_genre');
            $activityDate = $request->request->get('activity_date');
            $activityMaxNumber = $request->request->get('activity_max_number');
            
            // Validate form data
            if ($activityName && $activityDestination) {
                $activity->setActivityName($activityName);
                $activity->setActivityDescription($activityDescription);
                $activity->setActivityDestination($activityDestination);
                $activity->setActivityDuration($activityDuration);
                $activity->setActivityPrice($activityPrice);
                $activity->setActivityGenre($activityGenre);
                $activity->setMaxNumber(intval($activityMaxNumber));
                
                if ($activityDate) {
                    $activity->setActivityDate(new \DateTime($activityDate));
                }
                
                // Save the activity first to get its ID
                $this->entityManager->persist($activity);
                $this->entityManager->flush();
                
                // Handle image uploads
                $imageFiles = $request->files->get('activity_images');
                if ($imageFiles && !empty($imageFiles)) {
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
            } else {
                $this->addFlash('error', 'Please fill out all required fields.');
            }
        }
        
        return $this->render('publicator/activities/add.html.twig', [
            'activity' => $activity,
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
        
        // Get all resources (images) for this activity
        $resources = $this->resourcesRepository->findBy(['activity' => $activity]);
        
        if ($request->isMethod('POST')) {
            // Handle form submission
            $activityName = $request->request->get('activity_name');
            $activityDescription = $request->request->get('activity_description');
            $activityDestination = $request->request->get('activity_destination');
            $activityDuration = $request->request->get('activity_duration');
            $activityPrice = $request->request->get('activity_price');
            $activityGenre = $request->request->get('activity_genre');
            $activityDate = $request->request->get('activity_date');
            $activityMaxNumber = $request->request->get('activity_max_number');
            
            // Validate form data
            if ($activityName && $activityDestination) {
                $activity->setActivityName($activityName);
                $activity->setActivityDescription($activityDescription);
                $activity->setActivityDestination($activityDestination);
                $activity->setActivityDuration($activityDuration);
                $activity->setActivityPrice($activityPrice);
                $activity->setActivityGenre($activityGenre);
                $activity->setMaxNumber(intval($activityMaxNumber));
                
                if ($activityDate) {
                    $activity->setActivityDate(new \DateTime($activityDate));
                } else {
                    $activity->setActivityDate(null);
                }
                
                // Handle image uploads
                $imageFiles = $request->files->get('activity_images');
                if ($imageFiles && !empty($imageFiles)) {
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
            } else {
                $this->addFlash('error', 'Please fill out all required fields.');
            }
        }
        
        return $this->render('publicator/activities/edit.html.twig', [
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
}
