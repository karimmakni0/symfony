<?php

namespace App\Controller;

use App\Entity\Resources;
use App\Repository\ResourcesRepository;
use App\Repository\ActivitiesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

/**
 * @Route("/api/resources", name="api_resources_")
 */
class ResourcesController extends AbstractController
{
    private $entityManager;
    private $resourcesRepository;
    private $activitiesRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        ResourcesRepository $resourcesRepository,
        ActivitiesRepository $activitiesRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->resourcesRepository = $resourcesRepository;
        $this->activitiesRepository = $activitiesRepository;
        $this->security = $security;
    }

    /**
     * @Route("", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        // Will implement later
        return new Response('Resources index');
    }

    /**
     * @Route("/activity/{id}", name="get_activity_resources", methods={"GET"})
     */
    public function getActivityResources(int $id): Response
    {
        $activity = $this->activitiesRepository->find($id);
        
        if (!$activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }
        
        $resources = $this->resourcesRepository->findBy(['activity' => $activity]);
        
        $resourcesData = [];
        foreach ($resources as $resource) {
            $resourcesData[] = [
                'id' => $resource->getId(),
                'path' => $resource->getPath(),
                'url' => $this->getParameter('app.base_url') . '/' . $resource->getPath()
            ];
        }
        
        return $this->json($resourcesData);
    }

    /**
     * @Route("/delete/{id}", name="delete_resource", methods={"DELETE"})
     */
    public function deleteResource(int $id): Response
    {
        $resource = $this->resourcesRepository->find($id);
        
        if (!$resource) {
            return $this->json(['error' => 'Resource not found'], 404);
        }
        
        // Check if user has permission to delete this resource
        $activity = $resource->getActivity();
        $user = $this->getUser();
        
        if (!$user || ($activity && $activity->getUser() !== $user)) {
            return $this->json(['error' => 'Permission denied'], 403);
        }
        
        // Delete the file from storage
        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $resource->getPath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Remove from database
        $this->entityManager->remove($resource);
        $this->entityManager->flush();
        
        return $this->json(['success' => true]);
    }

    #[Route('/api/activities/{activityId}/resources', name: 'app_api_get_activity_resources')]
    public function getApiActivityResources(int $activityId): Response
    {
        $activity = $this->entityManager->getRepository(Activities::class)->find($activityId);
        
        if (!$activity) {
            return $this->json(['error' => 'Activity not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if the logged-in user is the owner of this activity
        $user = $this->security->getUser();
        if (!$user || $activity->getPublicator() !== $user) {
            return $this->json(['error' => 'You do not have permission to view these resources'], Response::HTTP_FORBIDDEN);
        }
        
        $resources = $this->entityManager->getRepository(Resources::class)->findBy(['activity' => $activity]);
        
        $formattedResources = [];
        foreach ($resources as $resource) {
            $formattedResources[] = [
                'id' => $resource->getId(),
                'path' => $resource->getPath(),
                'type' => $resource->getType(),
                'createdAt' => $resource->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }
        
        return $this->json($formattedResources);
    }

    #[Route('/publicator/activity/{activityId}/upload-images', name: 'app_publicator_upload_activity_images')]
    public function uploadActivityImages(Request $request, int $activityId): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $activity = $this->activitiesRepository->find($activityId);
        
        // Check if activity exists and belongs to the current user
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Activity not found or you don\'t have permission to upload images.');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        if ($request->isMethod('POST')) {
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
                        $newFilename = 'activity-'.uniqid().'-'.time().'.'.$imageFile->guessExtension();
                        
                        try {
                            $imageFile->move(
                                $uploadsDirectory,
                                $newFilename
                            );
                            
                            // Create a new resource for this image
                            $resource = new Resources();
                            $resource->setPath('uploads/activities/'.$newFilename);
                            $resource->setActivity($activity);
                            
                            $this->entityManager->persist($resource);
                        } catch (\Exception $e) {
                            $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                        }
                    }
                }
                
                // Save all resources
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Images uploaded successfully!');
            } else {
                $this->addFlash('error', 'No images selected for upload.');
            }
        }
        
        return $this->redirectToRoute('app_publicator_edit_activity', ['id' => $activityId]);
    }
    
    #[Route('/publicator/resource/delete/{id}', name: 'app_publicator_delete_resource')]
    public function deleteActivityResource(int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $resource = $this->resourcesRepository->find($id);
        
        if (!$resource) {
            $this->addFlash('error', 'Resource not found');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        $activity = $resource->getActivity();
        
        // Check if the resource belongs to the current user's activity
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Permission denied');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        $activityId = $activity->getId();
        
        // Delete the file from storage
        $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $resource->getPath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Remove from database
        $this->entityManager->remove($resource);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Image deleted successfully!');
        return $this->redirectToRoute('app_publicator_edit_activity', ['id' => $activityId]);
    }
}
