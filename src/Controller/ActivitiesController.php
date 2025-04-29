<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Billet;
use App\Entity\Resources;
use App\Entity\Reservation;
use App\Entity\Users;
use App\Form\ActivityFormType;
use App\Repository\ActivitiesRepository;
use App\Repository\BilletRepository;
use App\Repository\DestinationsRepository;
use App\Repository\ResourcesRepository;
use App\Repository\ReservationRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ActivitiesRepository $activitiesRepository,
        DestinationsRepository $destinationsRepository,
        ResourcesRepository $resourcesRepository,
        BilletRepository $billetRepository,
        ReservationRepository $reservationRepository,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->activitiesRepository = $activitiesRepository;
        $this->destinationsRepository = $destinationsRepository;
        $this->resourcesRepository = $resourcesRepository;
        $this->billetRepository = $billetRepository;
        $this->reservationRepository = $reservationRepository;
        $this->emailService = $emailService;
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
        
        // Fetch weather data for the activity location from OpenWeatherMap
        $weatherData = null;
        if ($activity->getActivityDestination()) {
            $weatherData = $this->getWeatherForLocation($activity->getActivityDestination());
        }
        
        return $this->render('client/Activities/Details.html.twig', [
            'activity' => $activity,
            'totalBookedTickets' => $totalBookedTickets,
            'weatherData' => $weatherData
        ]);
    }
    
    #[Route('/publicator/activities', name: 'app_publicator_activities')]
    public function publicatorActivities(Request $request): Response
    {
        // Check if user has the appropriate role
        $user = $this->getUser();
        if (!$user || $user->getRole() !== 'Publicitaire') {
            return $this->redirectToRoute('app_login');
        }
        
        // Set up pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Number of activities per page
        
        // Get total activities count for pagination
        $totalActivities = $this->activitiesRepository->count(['user' => $user]);
        $maxPages = ceil($totalActivities / $limit);
        
        // Get all activities for statistics
        $allActivities = $this->activitiesRepository->findBy(['user' => $user]);
        
        // Get unique destinations for statistics
        $uniqueDestinations = [];
        $upcomingActivitiesCount = 0;
        $today = new \DateTime();
        
        foreach ($allActivities as $activity) {
            // Count unique destinations
            if (!empty($activity->getActivityDestination()) && !in_array($activity->getActivityDestination(), $uniqueDestinations)) {
                $uniqueDestinations[] = $activity->getActivityDestination();
            }
            
            // Count upcoming activities
            if ($activity->getActivityDate() && $activity->getActivityDate() > $today) {
                $upcomingActivitiesCount++;
            }
        }
        
        // Get paginated activities created by this publicator
        $pagedActivities = $this->activitiesRepository->findBy(
            ['user' => $user], 
            ['created_at' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );
        
        return $this->render('publicator/activities/index.html.twig', [
            'activities' => $pagedActivities,
            'current_page' => $page,
            'max_pages' => $maxPages,
            'total_items' => $totalActivities,
            'items_per_page' => $limit,
            'all_activities_count' => count($allActivities),
            'unique_destinations_count' => count($uniqueDestinations),
            'unique_destinations' => $uniqueDestinations,
            'upcoming_activities_count' => $upcomingActivitiesCount
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
            
            // Send email notification to all users about the new activity
            try {
                // Add a flag to check if this code block is executed
                file_put_contents(__DIR__ . '/../../var/log/email_notification_attempt.log', date('Y-m-d H:i:s') . " - Attempting to send notification for activity ID: " . $activity->getId() . "\n", FILE_APPEND);
                
                $this->emailService->sendNewActivityNotification($activity);
                
                // Log success
                file_put_contents(__DIR__ . '/../../var/log/email_notification_success.log', date('Y-m-d H:i:s') . " - Successfully sent notification for activity ID: " . $activity->getId() . "\n", FILE_APPEND);
            } catch (\Exception $e) {
                // Log the detailed error information
                $errorMessage = 'Failed to send email notification: ' . $e->getMessage() . "\n";
                $errorMessage .= 'Stack trace: ' . $e->getTraceAsString() . "\n";
                file_put_contents(__DIR__ . '/../../var/log/email_notification_error.log', date('Y-m-d H:i:s') . " - " . $errorMessage, FILE_APPEND);
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
            
            // Handle new image uploads
            $imageFiles = $form->get('activity_images')->getData();
            if ($imageFiles && !empty($imageFiles[0])) {
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/activities';
                
                // Create directory if it doesn't exist
                if (!file_exists($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                
                // Get the first image (we only allow one image per activity now)
                $imageFile = $imageFiles[0];
                
                // Generate unique filename
                $newFilename = 'activity-' . uniqid() . '-' . time() . '.' . $imageFile->guessExtension();
                
                try {
                    // Move the file to the uploads directory
                    $imageFile->move($uploadsDirectory, $newFilename);
                    
                    // Remove all existing images for this activity that aren't in the deletedImages array
                    // because we're replacing the image, not adding to it
                    $existingResources = $activity->getResources();
                    $deletedImageIds = $request->request->all('deleted_images') ?? [];
                    
                    foreach ($existingResources as $existingResource) {
                        if (!in_array($existingResource->getId(), $deletedImageIds)) {
                            $this->entityManager->remove($existingResource);
                        }
                    }
                    
                    // Create a new resource for this image
                    $resource = new Resources();
                    $resource->setPath('/uploads/activities/' . $newFilename);
                    $resource->setActivity($activity);
                    
                    $this->entityManager->persist($resource);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Failed to upload image: ' . $e->getMessage());
                }
            }
            
            // Save all changes
            $this->entityManager->flush();
            
            // Use URL parameter instead of flash message for consistency
            return $this->redirectToRoute('app_publicator_activities', [
                'success' => 'update'
            ]);
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
        // Calculate initial total price
        $initialTotalPrice = $participants * $unitPrice;
        
        // Get coupon from session if exists
        $session = $request->getSession();
        $appliedCoupon = $session->get('applied_coupon', null);
        $appliedDiscount = 0;
        
        // Apply coupon discount if available
        if ($appliedCoupon) {
            $couponRepository = $this->entityManager->getRepository('App\Entity\Coupon');
            $coupon = $couponRepository->find($appliedCoupon['id']);
            
            if ($coupon && $coupon->isValid()) {
                $appliedDiscount = $coupon->calculateDiscount($initialTotalPrice);
                $totalPrice = $initialTotalPrice - $appliedDiscount;
            } else {
                // Invalid or expired coupon, remove from session
                $session->remove('applied_coupon');
                $appliedCoupon = null;
            }
        } else {
            $totalPrice = $initialTotalPrice;
        }
        
        // Validate participants count
        $totalBookedTickets = $this->billetRepository->getTotalBookedTicketsForActivity($id);
        $remainingTickets = $activity->getMaxNumber() - $totalBookedTickets;
        
        if ($participants <= 0 || $participants > $remainingTickets) {
            $this->addFlash('error', 'Invalid number of participants');
            return $this->redirectToRoute('app_client_activity_detail', ['id' => $id]);
        }
        
        // Handle coupon application
        if ($request->isMethod('POST') && $request->request->has('coupon_code')) {
            $couponCode = $request->request->get('coupon_code');
            $couponRepository = $this->entityManager->getRepository('App\Entity\Coupon');
            $coupon = $couponRepository->findValidCoupon($couponCode);
            
            if ($coupon) {
                $discount = $coupon->calculateDiscount($initialTotalPrice);
                $discountedPrice = $initialTotalPrice - $discount;
                
                // Store coupon in session
                $session->set('applied_coupon', [
                    'id' => $coupon->getId(),
                    'code' => $coupon->getCode(),
                    'discount' => $discount,
                    'discount_text' => $coupon->isPercentage() ? $coupon->getDiscount() . '%' : $coupon->getDiscount() . ' TND',
                ]);
                
                $this->addFlash('success', 'Coupon applied successfully: ' . $coupon->getCode());
                return $this->redirectToRoute('app_payment_reservation', ['id' => $id]);
            } else {
                $this->addFlash('error', 'Invalid or expired coupon code');
            }
        }
        
        // Remove coupon if requested
        if ($request->query->has('remove_coupon')) {
            $session->remove('applied_coupon');
            $this->addFlash('success', 'Coupon removed successfully');
            return $this->redirectToRoute('app_payment_reservation', ['id' => $id]);
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
                            'initialTotalPrice' => $initialTotalPrice,
                            'appliedCoupon' => $appliedCoupon,
                            'user' => $user
                        ]);
                    }
                    try {
                        // Begin transaction
                        $this->entityManager->beginTransaction();
                        
                        // Update coupon usage if one was applied
                        if ($appliedCoupon) {
                            $couponRepository = $this->entityManager->getRepository('App\Entity\Coupon');
                            $coupon = $couponRepository->find($appliedCoupon['id']);
                            if ($coupon) {
                                $coupon->incrementUsageCount();
                            }
                        }
                        
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
                        
                        // Store coupon information, without using a comment field
                        if ($appliedCoupon) {
                            // We would need to add a couponCode field to the Reservation entity to properly store this
                            // For now we'll just log it for debugging purposes
                            error_log('Coupon applied to reservation: ' . $appliedCoupon['code'] . ' (' . $appliedCoupon['discount_text'] . ')');
                            
                            // If you want to store this information, you would need to add a field to your Reservation entity
                            // Example: $reservation->setCouponCode($appliedCoupon['code']);
                        }
                        
                        $this->entityManager->persist($reservation);
                        $this->entityManager->flush(); // ensures reservation gets a unique auto-incremented id
                        
                        // Clear applied coupon from session
                        $session->remove('applied_coupon');
                        
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
                            'initialTotalPrice' => $initialTotalPrice,
                            'appliedCoupon' => $appliedCoupon,
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
            'initialTotalPrice' => $initialTotalPrice,
            'appliedCoupon' => $appliedCoupon,
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

    /**
     * Get weather data for a location using OpenWeatherMap API
     */
    private function getWeatherForLocation(string $location): ?array
    {
        // Get the OpenWeatherMap API key from environment variables
        $apiKey = $this->getParameter('OPENWEATHERMAP_API_KEY');
        
        // If no API key is set, return null to avoid errors
        if (!$apiKey || $apiKey === 'your_api_key_here') {
            error_log('No OpenWeatherMap API key is set in .env file');
            return null;
        }
        
        try {
            // URL encode the location for the API request
            $encodedLocation = urlencode($location);
            
            // Make API request to OpenWeatherMap
            $url = "https://api.openweathermap.org/data/2.5/weather?q={$encodedLocation}&units=metric&appid={$apiKey}";
            $response = file_get_contents($url);
            
            if ($response) {
                $weatherData = json_decode($response, true);
                
                // Format the data for easy use in template
                if (isset($weatherData['main']) && isset($weatherData['weather'][0])) {
                    return [
                        'temp' => round($weatherData['main']['temp']),
                        'feels_like' => round($weatherData['main']['feels_like']),
                        'humidity' => $weatherData['main']['humidity'],
                        'description' => ucfirst($weatherData['weather'][0]['description']),
                        'icon' => $weatherData['weather'][0]['icon'],
                        'wind_speed' => $weatherData['wind']['speed'] ?? null,
                        'location' => $weatherData['name'],
                        'country' => $weatherData['sys']['country'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break page rendering
            error_log('Error fetching weather: ' . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * API endpoint to get weather forecast for a date
     */
    #[Route('/api/weather-forecast', name: 'api_weather_forecast', methods: ['GET'])]
    public function getWeatherForecast(Request $request): JsonResponse
    {
        try {
            // Get location and date from query parameters
            $location = $request->query->get('location');
            $date = $request->query->get('date');
            
            if (!$location || !$date) {
                return $this->json(['error' => 'Location and date are required', 'weather' => $this->getFallbackWeather()], 400);
            }
            
            // Get the OpenWeatherMap API key
            $apiKey = $this->getParameter('OPENWEATHERMAP_API_KEY');
            
            if (!$apiKey || $apiKey === 'your_api_key_here') {
                return $this->json([
                    'error' => 'API key not configured', 
                    'weather' => $this->getFallbackWeather()
                ], 200); // Return 200 to avoid breaking the UI
            }
            
            // Calculate the number of days from today to the requested date
            $today = new \DateTime();
            $targetDate = new \DateTime($date);
            $interval = $today->diff($targetDate);
            $days = $interval->days;
            
            // If date is more than 5 days in the future, use estimated weather
            if ($days > 5) {
                return $this->json([
                    'warning' => 'Weather forecast is only available for up to 5 days in advance',
                    'estimatedForecast' => true,
                    'weather' => $this->getEstimatedWeather($location)
                ]);
            }
            
            // For dates within 5 days, use the OpenWeatherMap 5-day forecast API
            $encodedLocation = urlencode($location);
            $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$encodedLocation}&units=metric&appid={$apiKey}";
            
            $context = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ];
            
            $response = @file_get_contents($forecastUrl, false, stream_context_create($context));
            
            if (!$response) {
                return $this->json([
                    'error' => 'Failed to fetch weather forecast', 
                    'weather' => $this->getEstimatedWeather($location)
                ]);
            }
            
            $forecastData = json_decode($response, true);
            
            // Check if the API returned an error
            if (isset($forecastData['cod']) && $forecastData['cod'] != '200') {
                return $this->json([
                    'error' => $forecastData['message'] ?? 'Error fetching forecast', 
                    'weather' => $this->getEstimatedWeather($location)
                ]);
            }
            
            if (!isset($forecastData['list']) || empty($forecastData['list'])) {
                return $this->json([
                    'error' => 'Invalid forecast data received', 
                    'weather' => $this->getEstimatedWeather($location)
                ]);
            }
            
            // Find the forecast closest to our target date
            $targetDateFormatted = $targetDate->format('Y-m-d');
            $matchingForecasts = array_filter($forecastData['list'], function($item) use ($targetDateFormatted) {
                $forecastDate = substr($item['dt_txt'], 0, 10); // Extract date part (YYYY-MM-DD)
                return $forecastDate === $targetDateFormatted;
            });
            
            if (empty($matchingForecasts)) {
                // If no exact match, use the current weather as a fallback
                return $this->json([
                    'warning' => 'No specific forecast available for the selected date',
                    'estimatedForecast' => true,
                    'weather' => $this->getEstimatedWeather($location)
                ]);
            }
            
            // Take midday forecast if available (around 12:00-15:00), or the first forecast of the day
            $middayForecasts = array_filter($matchingForecasts, function($item) {
                $time = substr($item['dt_txt'], 11, 5); // Extract time part (HH:MM)
                return $time === '12:00' || $time === '15:00';
            });
            
            $forecast = !empty($middayForecasts) ? reset($middayForecasts) : reset($matchingForecasts);
            
            // Format the forecast data
            $weather = [
                'temp' => round($forecast['main']['temp']),
                'feels_like' => round($forecast['main']['feels_like']),
                'humidity' => $forecast['main']['humidity'],
                'description' => ucfirst($forecast['weather'][0]['description']),
                'icon' => $forecast['weather'][0]['icon'],
                'wind_speed' => $forecast['wind']['speed'] ?? null,
                'location' => $forecastData['city']['name'],
                'country' => $forecastData['city']['country'] ?? null,
                'forecast_time' => $forecast['dt_txt'],
            ];
            
            return $this->json(['weather' => $weather]);
            
        } catch (\Exception $e) {
            // Log the error and return a fallback response that won't break the UI
            error_log('Error fetching weather forecast: ' . $e->getMessage());
            
            return $this->json([
                'error' => 'Error fetching weather forecast: ' . $e->getMessage(), 
                'estimatedForecast' => true,
                'weather' => $this->getFallbackWeather()
            ], 200); // Use 200 status code to prevent fetch() errors
        }
    }
    
    /**
     * Get estimated weather when forecast is not available
     */
    private function getEstimatedWeather(string $location): array
    {
        try {
            // Try to get current weather as a fallback
            $currentWeather = $this->getWeatherForLocation($location);
            
            if ($currentWeather) {
                return array_merge($currentWeather, [
                    'estimated' => true,
                    'notice' => 'This is an estimate based on current weather',
                ]);
            }
        } catch (\Exception $e) {
            // If getWeatherForLocation fails, continue to default values
            error_log('Error in getEstimatedWeather: ' . $e->getMessage());
        }
        
        // If even current weather fails, return default values
        return $this->getFallbackWeather($location);
    }
    
    /**
     * Get default weather values when all else fails
     */
    private function getFallbackWeather(string $location = 'Unknown'): array
    {
        return [
            'temp' => 25,
            'feels_like' => 26,
            'humidity' => 60,
            'description' => 'Weather data unavailable',
            'icon' => '01d', // default sunny icon
            'estimated' => true,
            'notice' => 'Weather information is currently unavailable',
            'location' => $location,
            'wind_speed' => 5,
        ];
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
