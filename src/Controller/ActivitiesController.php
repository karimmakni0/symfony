<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Resources;
use App\Entity\User;
use App\Entity\Reservation;
use App\Entity\Users;
use App\Form\ActivityFormType;
use App\Repository\ActivitiesRepository;
use App\Repository\BilletRepository;
use App\Repository\DestinationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe;

class ActivitiesController extends AbstractController
{
    private $entityManager;
    private $activitiesRepository;
    private $destinationsRepository;
    private $resourcesRepository;
    private $billetRepository;
    private $reservationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivitiesRepository $activitiesRepository,
        DestinationsRepository $destinationsRepository,
        ResourcesRepository $resourcesRepository,
        BilletRepository $billetRepository,
        ReservationRepository $reservationRepository
    ) {
        $this->entityManager = $entityManager;
        $this->activitiesRepository = $activitiesRepository;
        $this->destinationsRepository = $destinationsRepository;
        $this->resourcesRepository = $resourcesRepository;
        $this->billetRepository = $billetRepository;
        $this->reservationRepository = $reservationRepository;
    }

    #[Route("/api/activities", name: 'api_activities_index', methods: ['GET'])]
    public function index(): Response
    {
        // Will implement later
        return new Response('Activities index');
    }
    
    #[Route('/activities', name: 'app_activities')]
    public function clientActivities(Request $request): Response
    {
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 9; // Number of activities per page
        
        // Get total activities count
        $totalActivities = $this->activitiesRepository->count([]);
        $maxPages = ceil($totalActivities / $limit);
        
        // Get activities with resources for current page
        $activities = $this->activitiesRepository->findAllWithResourcesPaginated($page, $limit);
        
        // Get unique destinations for the filter dropdown
        $destinations = $this->activitiesRepository->findAllDestinations();
        
        // Get activity types with counts
        $activityTypes = $this->activitiesRepository->findActivityTypesWithCount();
        
        // Get durations with counts
        $durations = $this->activitiesRepository->findDurationsWithCount();
        
        return $this->render('client/Activities/index.html.twig', [
            'activities' => $activities,
            'destinations' => $destinations,
            'activityTypes' => $activityTypes,
            'durations' => $durations,
            'current_page' => $page,
            'max_pages' => $maxPages,
            'total_items' => $totalActivities,
            'items_per_page' => $limit
        ]);
    }
    
    #[Route('/activities/filter', name: 'app_activities_filter')]
    public function filterActivities(Request $request): Response
    {
        // Get filter parameters from request
        $destination = $request->query->get('destination');
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $minPrice = $request->query->get('minPrice') ? (float)$request->query->get('minPrice') : null;
        $maxPrice = $request->query->get('maxPrice') ? (float)$request->query->get('maxPrice') : null;
        $activityTypes = $request->query->all('activityType');
        $durations = $request->query->all('duration');
        
        // Get filtered activities
        $activities = $this->activitiesRepository->findByFilters(
            $destination,
            $startDate,
            $endDate,
            $minPrice,
            $maxPrice,
            $activityTypes,
            $durations
        );
        
        // Get filter options for the sidebar
        $destinations = $this->activitiesRepository->findAllDestinations();
        $activityTypesWithCount = $this->activitiesRepository->findActivityTypesWithCount();
        $durationsWithCount = $this->activitiesRepository->findDurationsWithCount();
        
        return $this->render('client/Activities/index.html.twig', [
            'activities' => $activities,
            'destinations' => $destinations,
            'activityTypes' => array_keys($activityTypesWithCount),
            'activityTypesWithCount' => $activityTypesWithCount,
            'durations' => array_keys($durationsWithCount),
            'durationsWithCount' => $durationsWithCount,
            'minPrice' => $minPrice,
            'maxPrice' => $maxPrice,
            'selectedDestination' => $destination,
            'startDate' => $startDate,
            'endDate' => $endDate
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
        
        // Create a choices array for the destinations dropdown
        $destinationsChoices = [];
        foreach ($destinations as $destination) {
            $destinationsChoices[$destination->getName() . ' (' . $destination->getLocation() . ')'] = $destination->getName();
        }
        
        // Create the form with our destinations choices
        $form = $this->createForm(ActivityFormType::class, $activity, [
            'destinations_choices' => $destinationsChoices,
            'images_required' => true
        ]);
        $form->handleRequest($request);
        
        // Check for direct POST data
        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            
            // Debug coordinates from form data
            $formName = 'activity_form'; 
            if (isset($formData[$formName]['latitude']) && isset($formData[$formName]['longitude'])) {
                $latitude = $formData[$formName]['latitude'];
                $longitude = $formData[$formName]['longitude'];
                
                // Log the coordinates for debugging
                dump("Form coordinates: $latitude, $longitude");
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Simple validation for coordinates
            $latitude = $form->get('latitude')->getData();
            $longitude = $form->get('longitude')->getData();
            
            // Ensure coordinates are valid
            if (empty($latitude) || empty($longitude)) {
                $this->addFlash('error', 'Coordinates are required. Please enter valid latitude and longitude values.');
                
                return $this->render('publicator/activities/add.html.twig', [
                    'form' => $form->createView(),
                    'destinations' => $destinations,
                    'coordinate_error' => true
                ]);
            }
            
            // Save the activity first to get its ID
            $this->entityManager->persist($activity);
            $this->entityManager->flush();
            
            // Handle image uploads
            $imageFiles = $form->get('activity_images')->getData();
            if ($imageFiles) {
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
            
            // Redirect with success parameter instead of flash message
            return $this->redirectToRoute('app_publicator_activities', ['success' => 'activity_created']);
        }
        
        return $this->render('publicator/activities/add.html.twig', [
            'form' => $form->createView(),
            'destinations' => $destinations
        ]);
    }
    
    #[Route('/publicator/activities/edit/{id}', name: 'app_publicator_edit_activity')]
    public function editActivity(Request $request, $id): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get the activity by ID
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists and belongs to this user
        if (!$activity || $activity->getUser() !== $user) {
            $this->addFlash('error', 'Activity not found or you are not authorized to edit it.');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        // Get all destinations created by this user for the dropdown
        $destinations = $this->destinationsRepository->findBy(['user' => $user]);
        
        // Create a choices array for the destinations dropdown
        $destinationsChoices = [];
        foreach ($destinations as $destination) {
            $destinationsChoices[$destination->getName() . ' (' . $destination->getLocation() . ')'] = $destination->getName();
        }
        
        // Create a new form for editing
        $form = $this->createForm(ActivityFormType::class, $activity, [
            'destinations_choices' => $destinationsChoices,
            'images_required' => false
        ]);
        
        // Process form submission
        $form->handleRequest($request);
        
        // Check for direct POST data
        if ($request->isMethod('POST')) {
            $formData = $request->request->all();
            
            // Debug the form data
            dump("Edit form data:", $formData);
            
            // Debugging latitude and longitude values
            $formName = 'activity_form'; // Change this if needed to match your form name
            if (isset($formData[$formName]['latitude']) && isset($formData[$formName]['longitude'])) {
                $latitude = $formData[$formName]['latitude'];
                $longitude = $formData[$formName]['longitude'];
                
                // Manually set coordinates if they exist in POST data
                if (!empty($latitude) && !empty($longitude)) {
                    $activity->setLatitude($latitude);
                    $activity->setLongitude($longitude);
                    
                    // Debug info
                    dump("Manually set coordinates in edit: $latitude, $longitude");
                }
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Simple validation for coordinates
            $latitude = $form->get('latitude')->getData();
            $longitude = $form->get('longitude')->getData();
            
            // Ensure coordinates are valid
            if (empty($latitude) || empty($longitude)) {
                $this->addFlash('error', 'Coordinates are required. Please enter valid latitude and longitude values.');
                
                // Get existing images for the activity
                $resources = $activity->getResources();
                
                // Render the form again with error
                return $this->render('publicator/activities/edit.html.twig', [
                    'form' => $form->createView(),
                    'activity' => $activity,
                    'destinations' => $destinations,
                    'resources' => $resources,
                    'coordinate_error' => true
                ]);
            }
            
            // Process deleted images
            $deletedImages = $request->request->all('deleted_images') ?? [];
            foreach ($deletedImages as $resourceId) {
                $resource = $this->resourcesRepository->find($resourceId);
                if ($resource && $resource->getActivity() === $activity) {
                    // Remove from database
                    $this->entityManager->remove($resource);
                }
            }
            
            // Save all changes
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Activity updated successfully!');
            return $this->redirectToRoute('app_publicator_activities');
        }
        
        // Get existing images for the activity
        $resources = $activity->getResources();
        
        return $this->render('publicator/activities/edit.html.twig', [
            'form' => $form->createView(),
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
            
            // Use query parameter instead of flash message
            return $this->redirectToRoute('app_publicator_activities', ['success' => 'activity_deleted']);
        } catch (\Exception $e) {
            // Log the detailed error for debugging
            error_log('Error deleting activity: ' . $e->getMessage() . ' | ' . $e->getTraceAsString());
            
            $this->addFlash('error', 'Error deleting activity. Please contact the administrator.');
            return $this->redirectToRoute('app_publicator_activities');
        }
    }
    
    // Removed redundant publicator profile route
    // This functionality is now provided by the client profile route at /client/profile
    
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
    
    #[Route('/payment-reservation/{id}', name: 'app_payment_reservation')]
    public function paymentReservation(Request $request, int $id): Response
    {
        // Check if user is logged in
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        // Find the activity
        $activity = $this->activitiesRepository->find($id);
        
        // Check if activity exists
        if (!$activity) {
            throw $this->createNotFoundException('Activity not found');
        }
        
        // Get participants and totalPrice from request (POST)
        $participants = $request->request->getInt('participants', 1);
        $totalPrice = $request->request->get('totalPrice');
        
        // Calculate unit price from activity (for safety)
        $unitPrice = $activity->getActivityPrice();
        // Optionally, recalculate totalPrice to prevent tampering
        $totalPrice = $participants * $unitPrice;
        
        // Validate participants count
        $totalBookedTickets = $this->billetRepository->getTotalBookedTicketsForActivity($id);
        $remainingTickets = $activity->getMaxNumber() - $totalBookedTickets;
        
        if ($participants <= 0 || $participants > $remainingTickets) {
            $this->addFlash('error', 'Invalid number of participants');
            return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
        }
        
        // Process form submission for credit card payment
        if ($request->isMethod('POST') && $request->request->has('payment_submit')) {
            // Validate basic credit card info (just checking if fields are filled in)
            $cardNumber = $request->request->get('card_number');
            $cardName = $request->request->get('card_name');
            $expiryDate = $request->request->get('expiry_date');
            $cvv = $request->request->get('cvv');
            
            if (empty($cardNumber) || empty($cardName) || empty($expiryDate) || empty($cvv)) {
                $this->addFlash('error', 'Please fill in all payment details');
            } else {
                try {
                    // Process payment with Stripe
                    $paymentProcessed = $this->processStripePayment($cardNumber, $cardName, $expiryDate, $cvv, $totalPrice);
                    
                    if (!$paymentProcessed) {
                        $this->addFlash('error', 'Payment processing failed. Please try again.');
                        return $this->render('client/Activities/passReservation.html.twig', [
                            'activity' => $activity,
                            'participants' => $participants,
                            'totalPrice' => $totalPrice,
                            'user' => $user
                        ]);
                    }
                    try {
                        // Begin transaction
                        $this->entityManager->beginTransaction();
                        // Always create a new Billet for each reservation
                        $billet = new Billet();
                        $billet->setActiviteId($activity->getId());
                        $billet->setPrix($unitPrice);
                        $billet->setNb($participants);
                        $billet->setNumero('TICKET-' . uniqid());
                        $this->entityManager->persist($billet);
                        $this->entityManager->flush(); // ensures billet gets a unique auto-incremented id
                        // Always create a new Reservation for each booking
                        $reservation = new Reservation();
                        $reservation->setUser($user); // must be the managed User entity
                        $reservation->setBillet($billet);
                        $reservation->setDateAchat(date('Y-m-d H:i:s'));
                        $reservation->setNombre($participants);
                        $reservation->setPrixTotal($totalPrice);
                        $reservation->setPrixUnite($unitPrice);
                        $reservation->setStatuts('confirmed');
                        $this->entityManager->persist($reservation);
                        $this->entityManager->flush(); // ensures reservation gets a unique auto-incremented id
                        $this->addFlash('success', 'Your reservation was successful! Ticket number: ' . $billet->getNumero());
                        $this->entityManager->commit();
                        return $this->redirectToRoute('app_user_reservation_history');
                    } catch (\Exception $e) {
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->rollback();
                        }
                        error_log('Reservation error: ' . $e->getMessage());
                        $this->addFlash('error', 'Error: ' . $e->getMessage());
                        return $this->render('client/Activities/passReservation.html.twig', [
                            'activity' => $activity,
                            'participants' => $participants,
                            'totalPrice' => $totalPrice,
                            'user' => $user
                        ]);
                    }
                } catch (\Exception $e) {
                    error_log('Payment error: ' . $e->getMessage());
                    $this->addFlash('error', 'Error: ' . $e->getMessage());
                }
            }
        }
        
        return $this->render('client/Activities/passReservation.html.twig', [
            'activity' => $activity,
            'participants' => $participants,
            'totalPrice' => $totalPrice,
            'user' => $user
        ]);
    }

    /**
     * Process payment with Stripe
     */
    private function processStripePayment($cardNumber, $cardName, $expiryDate, $cvv, $amount)
    {
        try {
            // Set your secret key directly 
            Stripe\Stripe::setApiKey('sk_test_51RFcLuFZnnbGj2Q6ReU6Xr9UjQ7ghUGjlJ3e7HmaEsmYhwVH38a3Bm8OGPxLlygueAg0NJUY1w430o9ewALBirUf00TKzamiv4');
            
            // For testing purposes, we'll use a test token
            // In production, you would generate tokens client-side using Stripe.js
            $testToken = 'tok_visa'; // This represents a successful Visa card payment
            
            // Create charge using the test token
            $charge = Stripe\Charge::create([
                'amount' => (int)($amount * 100), // Convert to cents and ensure it's an integer
                'currency' => 'usd', // Using USD for test tokens
                'source' => $testToken,
                'description' => 'Activity reservation',
            ]);
            
            return $charge->status === 'succeeded';
        } catch (\Stripe\Exception\CardException $e) {
            // Card declined
            error_log('Stripe CardException: ' . $e->getMessage());
            $this->addFlash('error', 'Payment failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            // Other error
            error_log('Stripe Error: ' . $e->getMessage());
            $this->addFlash('error', 'Payment processing error: ' . $e->getMessage());
            return false;
        }
    }

    #[Route('/activities/map', name: 'app_activities_map')]
    public function activitiesMap(): Response
    {
        // Get all activities with valid coordinates and resources
        $activities = $this->activitiesRepository->findAll();
        
        // Prepare data for the map including resources
        $activitiesData = [];
        foreach ($activities as $activity) {
            // Skip activities without coordinates
            if (empty($activity->getLatitude()) || empty($activity->getLongitude())) {
                continue;
            }
            
            // Get resources (images) for this activity
            $resources = $this->resourcesRepository->findBy(['activity' => $activity]);
            $resourcesData = [];
            
            foreach ($resources as $resource) {
                $resourcesData[] = [
                    'id' => $resource->getId(),
                    'path' => $resource->getPath()
                ];
            }
            
            $activitiesData[] = [
                'activity_id' => $activity->getId(),
                'activity_name' => $activity->getActivityName(),
                'activity_description' => $activity->getActivityDescription(),
                'activity_destination' => $activity->getActivityDestination(),
                'activity_duration' => $activity->getActivityDuration(),
                'activity_price' => $activity->getActivityPrice(),
                'activity_genre' => $activity->getActivityGenre(),
                'latitude' => $activity->getLatitude(),
                'longitude' => $activity->getLongitude(),
                'resources' => $resourcesData
            ];
        }
        
        // Get unique activity genres for the legend
        $activityTypes = $this->activitiesRepository->findUniqueGenres();
        
        // Define function to get color for activity type
        $getColorForType = function($type) {
            $colors = [
                'Adventure' => '#f44336', // Red
                'Cultural' => '#9c27b0', // Purple 
                'Relaxation' => '#03a9f4', // Light Blue
                'Family' => '#4caf50', // Green
                'Romantic' => '#e91e63', // Pink
                'Educational' => '#ff9800', // Orange
                'Sport' => '#2196f3', // Blue
                'Other' => '#607d8b' // Blue Grey
            ];
            
            return $colors[$type] ?? '#607d8b';
        };
        
        return $this->render('user/activity/map.html.twig', [
            'activities' => $activitiesData,
            'activityTypes' => $activityTypes
        ]);
    }
}
