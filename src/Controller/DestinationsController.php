<?php

namespace App\Controller;

use App\Entity\Destinations;
use App\Form\DestinationFormType;
use App\Repository\DestinationsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

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
    public function clientDestinations(Request $request): Response
    {
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 9; // Number of destinations per page
        
        // Get total destinations count for pagination
        $totalDestinations = $this->destinationsRepository->count([]);
        $maxPages = ceil($totalDestinations / $limit);
        
        // Get destinations for current page
        $destinations = $this->destinationsRepository->findBy(
            [], // criteria
            ['created_at' => 'DESC'], // order by
            $limit, // limit
            ($page - 1) * $limit // offset
        );
        
        return $this->render('client/Destinations/index.html.twig', [
            'destinations' => $destinations,
            'current_page' => $page,
            'max_pages' => $maxPages,
            'total_items' => $totalDestinations,
            'items_per_page' => $limit
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
        
        // Create form
        $form = $this->createForm(DestinationFormType::class, $destination);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            
            // Validate that an image was uploaded
            if (!$imageFile) {
                $this->addFlash('error', 'Please upload a destination image.');
                return $this->render('publicator/destinations/add.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
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
                return $this->render('publicator/destinations/add.html.twig', [
                    'form' => $form->createView(),
                ]);
            }
            
            // Save the destination
            $this->entityManager->persist($destination);
            $this->entityManager->flush();
            
            // Set a session flag for SweetAlert instead of flash message
            $request->getSession()->set('destination_created', true);
            return $this->redirectToRoute('app_publicator_destinations');
        }
        
        return $this->render('publicator/destinations/add.html.twig', [
            'form' => $form->createView(),
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
        
        // Create form for editing - with image not required
        $form = $this->createForm(DestinationFormType::class, $destination, [
            'image_required' => false // This option will be passed to the form type
        ]);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
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
                    return $this->render('publicator/destinations/edit.html.twig', [
                        'form' => $form->createView(),
                        'destination' => $destination,
                    ]);
                }
            }
            
            // Save the destination
            $this->entityManager->flush();
            
            // If it's an AJAX request, return a JSON response
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Destination updated successfully!'
                ]);
            }
            
            // For regular requests, add flash message and redirect
            $this->addFlash('success', 'Destination updated successfully!');
            return $this->redirectToRoute('app_publicator_destinations');
        }
        
        return $this->render('publicator/destinations/edit.html.twig', [
            'form' => $form->createView(),
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
