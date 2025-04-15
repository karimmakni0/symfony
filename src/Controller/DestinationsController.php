<?php

namespace App\Controller;

use App\Entity\Destinations;
use App\Repository\DestinationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

class DestinationsController extends AbstractController
{
    private $entityManager;
    private $destinationsRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        DestinationsRepository $destinationsRepository
    ) {
        $this->entityManager = $entityManager;
        $this->destinationsRepository = $destinationsRepository;
    }


    #[Route('/destinations', name: 'app_destinations')]
    public function clientDestinations(): Response
    {
        // Get all destinations to display to clients
        $destinations = $this->destinationsRepository->findAll();
        
        return $this->render('client/Destinations/index.html.twig', [
            'destinations' => $destinations
        ]);
    }

    #[Route('/publicator/destinations', name: 'app_publicator_destinations')]
    public function publicatorDestinations(): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get destinations created by this publicator
        $destinations = $this->destinationsRepository
            ->findBy(['user' => $user], ['created_at' => 'DESC']);
        
        return $this->render('publicator/destinations/index.html.twig', [
            'destinations' => $destinations,
        ]);
    }
    
    #[Route('/publicator/destinations/add', name: 'app_publicator_add_destination')]
    public function addDestination(Request $request): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $destination = new Destinations();
        $destination->setUser($user);
        
        if ($request->isMethod('POST')) {
            // Handle form submission
            $name = $request->request->get('name');
            $location = $request->request->get('location');
            $description = $request->request->get('description');
            
            // Validate form data
            if ($name && $location) {
                $destination->setName($name);
                $destination->setLocation($location);
                $destination->setDescription($description);
                
                // Handle image upload
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/destinations';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0777, true);
                    }
                    
                    $newFilename = 'destination-'.uniqid().'-'.time().'.'.$imageFile->guessExtension();
                    
                    try {
                        $imageFile->move(
                            $uploadsDirectory,
                            $newFilename
                        );
                        $destination->setImagePath('uploads/destinations/'.$newFilename);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                    }
                }
                
                // Save the destination
                $this->entityManager->persist($destination);
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Destination created successfully!');
                return $this->redirectToRoute('app_publicator_destinations');
            } else {
                $this->addFlash('error', 'Please fill out all required fields.');
            }
        }
        
        return $this->render('publicator/destinations/add.html.twig', [
            'destination' => $destination,
        ]);
    }
    
    #[Route('/publicator/destinations/edit/{id}', name: 'app_publicator_edit_destination')]
    public function editDestination(Request $request, int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $destination = $this->destinationsRepository->find($id);
        
        // Check if destination exists and belongs to the current user
        if (!$destination || $destination->getUser() !== $user) {
            $this->addFlash('error', 'Destination not found or you don\'t have permission to edit it.');
            return $this->redirectToRoute('app_publicator_destinations');
        }
        
        if ($request->isMethod('POST')) {
            // Handle form submission
            $name = $request->request->get('name');
            $location = $request->request->get('location');
            $description = $request->request->get('description');
            
            // Validate form data
            if ($name && $location) {
                $destination->setName($name);
                $destination->setLocation($location);
                $destination->setDescription($description);
                
                // Handle image upload
                $imageFile = $request->files->get('image');
                if ($imageFile) {
                    $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/destinations';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($uploadsDirectory)) {
                        mkdir($uploadsDirectory, 0777, true);
                    }
                    
                    // Remove old image if exists
                    $oldImagePath = $destination->getImagePath();
                    if ($oldImagePath) {
                        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldImagePath;
                        if (file_exists($fullPath)) {
                            unlink($fullPath);
                        }
                    }
                    
                    $newFilename = 'destination-'.uniqid().'-'.time().'.'.$imageFile->guessExtension();
                    
                    try {
                        $imageFile->move(
                            $uploadsDirectory,
                            $newFilename
                        );
                        $destination->setImagePath('uploads/destinations/'.$newFilename);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                    }
                }
                
                // Save the destination
                $this->entityManager->flush();
                
                $this->addFlash('success', 'Destination updated successfully!');
                return $this->redirectToRoute('app_publicator_destinations');
            } else {
                $this->addFlash('error', 'Please fill out all required fields.');
            }
        }
        
        return $this->render('publicator/destinations/edit.html.twig', [
            'destination' => $destination,
        ]);
    }
    
    #[Route('/publicator/destinations/delete/{id}', name: 'app_publicator_delete_destination')]
    public function deleteDestination(int $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        $destination = $this->destinationsRepository->find($id);
        
        // Check if destination exists and belongs to the current user
        if (!$destination || $destination->getUser() !== $user) {
            $this->addFlash('error', 'Destination not found or you don\'t have permission to delete it.');
            return $this->redirectToRoute('app_publicator_destinations');
        }
        
        // Remove image file if exists
        $imagePath = $destination->getImagePath();
        if ($imagePath) {
            $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $imagePath;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Remove the destination
        $this->entityManager->remove($destination);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Destination deleted successfully!');
        return $this->redirectToRoute('app_publicator_destinations');
    }
}
