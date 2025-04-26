<?php

namespace App\Controller;

use App\Entity\Activities;
use App\Entity\Destinations;
use App\Entity\Post;
use App\Entity\Users;
use App\Entity\Billet;
use App\Entity\Reservation;
use App\Entity\UpgradeRequests;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/', name: 'admin_dashboard')]
    public function index(): Response
    {
        // Get counts for dashboard widgets
        $userCount = $this->entityManager->getRepository(Users::class)->count([]);
        $activitiesCount = $this->entityManager->getRepository(Activities::class)->count([]);
        $destinationsCount = $this->entityManager->getRepository(Destinations::class)->count([]);
        $billetsCount = $this->entityManager->getRepository(Billet::class)->count([]);

        // Get recent bookings with their reservations
        $billets = $this->entityManager->getRepository(Billet::class)
            ->findRecentWithReservations(5);
            
        // Get recent blog posts - simplified query to avoid errors
        try {
            $posts = $this->entityManager->getRepository(Post::class)
                ->findBy([], ['date' => 'DESC'], 5);
                
            // Get user data for the posts
            $recentPosts = [];
            if (!empty($posts)) {
                $userIds = array_map(function($post) {
                    return $post->getUserId();
                }, $posts);
                
                $users = $this->entityManager->getRepository(Users::class)
                    ->findBy(['id' => array_unique($userIds)]);
                    
                $usersById = [];
                foreach ($users as $user) {
                    $usersById[$user->getId()] = $user;
                }
                
                foreach ($posts as $post) {
                    $post->user = isset($usersById[$post->getUserId()]) ? $usersById[$post->getUserId()] : null;
                    $recentPosts[] = $post;
                }
            }
        } catch (\Exception $e) {
            // If something goes wrong, provide an empty array
            $recentPosts = [];
        }
            
        // Get pending upgrade requests - simplified query to avoid errors
        try {
            $recentUpgradeRequests = $this->entityManager->getRepository(UpgradeRequests::class)
                ->createQueryBuilder('ur')
                ->leftJoin('ur.user', 'u')
                ->addSelect('u')
                ->where('ur.status = :status')
                ->setParameter('status', 'pending')
                ->orderBy('ur.request_date', 'DESC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
                
            // Count total pending upgrade requests for badge
            $pendingUpgrades = $this->entityManager->getRepository(UpgradeRequests::class)
                ->count(['status' => 'pending']);
        } catch (\Exception $e) {
            // If something goes wrong, provide empty data
            $recentUpgradeRequests = [];
            $pendingUpgrades = 0;
        }
            
        // Get reservations indexed by billet ID for easy lookup
        $reservations = [];
        $billetRepository = $this->entityManager->getRepository(Billet::class);
        $reservationRepository = $this->entityManager->getRepository(Reservation::class);
        
        foreach ($billets as $billet) {
            // Find the reservation associated with this billet
            $reservation = $reservationRepository->findOneBy(['billet' => $billet]);
            if ($reservation) {
                $reservations[$billet->getId()] = $reservation;
            }
        }
            
        // Get all activity IDs from recent bookings
        $activityIds = [];
        foreach ($billets as $billet) {
            if ($billet !== null && method_exists($billet, 'getActiviteId')) {
                $activityId = $billet->getActiviteId();
                if ($activityId !== null) {
                    $activityIds[] = $activityId;
                }
            }
        }
        
        // Get all activities in one database query
        $activities = [];
        if (!empty($activityIds)) {
            $activityEntities = $this->entityManager->getRepository(Activities::class)
                ->findBy(['id' => array_unique($activityIds)]);
                
            // Index activities by ID for easy lookup
            foreach ($activityEntities as $activity) {
                $activities[$activity->getId()] = $activity;
            }
        }

        return $this->render('admin/index.html.twig', [
            'userCount' => $userCount,
            'activitiesCount' => $activitiesCount,
            'destinationsCount' => $destinationsCount,
            'billetsCount' => $billetsCount,
            'recentBookings' => $billets,
            'reservations' => $reservations,
            'activities' => $activities,
            'recentPosts' => $recentPosts,
            'recentUpgradeRequests' => $recentUpgradeRequests,
            'pendingUpgrades' => $pendingUpgrades,
        ]);
    }

    #[Route('/bookings', name: 'admin_bookings')]
    public function bookings(Request $request): Response
    {
        // Get filter parameters from the request
        $filters = [
            'search' => $request->query->get('search', ''),
            'activity' => $request->query->get('activity', ''),
            'status' => $request->query->get('status', ''),
            'date_from' => $request->query->get('date_from', '')
        ];
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Get reservations with applied filters
        $reservationRepository = $this->entityManager->getRepository(Reservation::class);
        $reservationResults = [];
        
        // Apply filters if any are set
        if (!empty($filters['search']) || !empty($filters['activity']) || !empty($filters['status']) || 
            !empty($filters['date_from'])) {
            $reservationResults = $reservationRepository->filterBookings($filters);
        } else {
            $reservationResults = $reservationRepository->findAllWithDetails();
        }
        
        // Calculate pagination
        $totalItems = count($reservationResults);
        $totalPages = ceil($totalItems / $limit);
        
        // Apply pagination limits (on the already filtered results)
        $reservationResults = array_slice($reservationResults, ($page - 1) * $limit, $limit);
        
        // Get all billets and organize reservations by billet ID
        $billets = [];
        $reservations = [];
        
        foreach ($reservationResults as $reservation) {
            $billet = $reservation->getBillet();
            if ($billet) {
                $billets[] = $billet;
                $reservations[$billet->getId()] = $reservation;
            }
        }
            
        // Get all activity IDs from bookings
        $activityIds = [];
        foreach ($billets as $billet) {
            if ($billet !== null && method_exists($billet, 'getActiviteId')) {
                $activityId = $billet->getActiviteId();
                if ($activityId !== null) {
                    $activityIds[] = $activityId;
                }
            }
        }
        
        // Get list of all statuses for the filter dropdown
        $statuses = ['pending', 'confirmed', 'cancelled'];
        
        // Get all activities for the filter dropdown
        $activityRepository = $this->entityManager->getRepository(Activities::class);
        $allActivities = $activityRepository->findAll();
        $activitiesForFilter = [];
        foreach ($allActivities as $activity) {
            $activitiesForFilter[$activity->getId()] = $activity;
        }
        
        return $this->render('admin/bookings.html.twig', [
            'bookings' => $billets,
            'reservations' => $reservations,
            'activities' => $activitiesForFilter,
            'allActivities' => $activitiesForFilter,
            'statuses' => $statuses,
            'filters' => $filters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]);
    }

    #[Route('/destinations', name: 'admin_destinations')]
    public function destinations(Request $request): Response
    {
        // Get filter parameters
        $filters = [
            'search' => $request->query->get('search', ''),
        ];
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Get all destinations with their associated users
        $destinationsRepo = $this->entityManager->getRepository(Destinations::class);
        $qb = $destinationsRepo->createQueryBuilder('d');
        
        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $qb->andWhere('d.name LIKE :search OR d.description LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        // Get total count for pagination
        $countQb = clone $qb;
        $totalItems = count($countQb->getQuery()->getResult());
        $totalPages = ceil($totalItems / $limit);
        
        // Add order and pagination to the main query
        $qb->orderBy('d.id', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        // Execute query
        $destinations = $qb->getQuery()->getResult();
        
        // Get user IDs for easy lookup
        $userIds = [];
        foreach ($destinations as $destination) {
            if ($destination->getUser()) {
                $userIds[] = $destination->getUser()->getId();
            }
        }
        
        // Get all users in one database query
        $users = [];
        if (!empty($userIds)) {
            $userEntities = $this->entityManager->getRepository(Users::class)
                ->findBy(['id' => array_unique($userIds)]);
                
            // Index users by ID for easy lookup
            foreach ($userEntities as $user) {
                $users[$user->getId()] = $user;
            }
        }

        return $this->render('admin/destinations.html.twig', [
            'destinations' => $destinations,
            'users' => $users,
            'filters' => $filters,
            'count' => $totalItems,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(Request $request): Response
    {
        // Get filter parameters from the request
        $filters = [
            'search' => $request->query->get('search', ''),
            'role' => $request->query->get('role', ''),
            'status' => $request->query->get('status', '')
        ];
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Create query builder
        $repository = $this->entityManager->getRepository(Users::class);
        $queryBuilder = $repository->createQueryBuilder('u');
        
        // Apply filters if provided
        if (!empty($filters['search'])) {
            $queryBuilder->andWhere('u.name LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }
        
        if (!empty($filters['role'])) {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', $filters['role']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'banned') {
                $queryBuilder->andWhere('u.isBanned = :banned')
                    ->setParameter('banned', true);
            } else if ($filters['status'] === 'active') {
                $queryBuilder->andWhere('u.isBanned = :banned')
                    ->setParameter('banned', false);
            }
        }
        
        // Get total count for pagination
        $countQb = clone $queryBuilder;
        $totalItems = count($countQb->getQuery()->getResult());
        $totalPages = ceil($totalItems / $limit);
        
        // Add order and pagination to the main query
        $queryBuilder->orderBy('u.id', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        // Get paginated results
        $users = $queryBuilder->getQuery()->getResult();
        
        // Get all available roles for the dropdown
        $roles = ['Admin', 'Publicitaire', 'user'];

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles,
            // Pagination data
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]);
    }
    
    #[Route('/delete/user/{id}', name: 'admin_delete_user')]
    public function deleteUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(Users::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        // Check for bookings associated with this user
        $bookings = $this->entityManager->getRepository(Billet::class)
            ->findBy(['user' => $user]);
        
        if (count($bookings) > 0) {
            $this->addFlash('error', 'Cannot delete user because they have associated bookings.');
            return $this->redirectToRoute('admin_users');
        }
        
        try {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete user: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/delete/destination/{id}', name: 'admin_delete_destination')]
    public function deleteDestination(int $id): Response
    {
        $destination = $this->entityManager->getRepository(Destinations::class)->find($id);
        
        if (!$destination) {
            $this->addFlash('error', 'Destination not found.');
            return $this->redirectToRoute('admin_destinations');
        }
        
        // Check for activities associated with this destination
        $activities = $this->entityManager->getRepository(Activities::class)
            ->findBy(['destination' => $destination]);
        
        // Can't delete destination with activities
        if (count($activities) > 0) {
            $this->addFlash('error', 'Cannot delete destination because it has associated activities. Delete the activities first.');
            return $this->redirectToRoute('admin_destinations');
        }
        
        try {
            $this->entityManager->remove($destination);
            $this->entityManager->flush();
            $this->addFlash('success', 'Destination deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete destination: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_destinations');
    }

    #[Route('/delete/activity/{id}', name: 'admin_delete_activity')]
    public function deleteActivity(int $id): Response
    {
        $activity = $this->entityManager->getRepository(Activities::class)->find($id);
        
        if (!$activity) {
            $this->addFlash('error', 'Activity not found.');
            return $this->redirectToRoute('admin_activities');
        }
        
        // Check for bookings using activiteId (not the relationship)
        $bookings = $this->entityManager->getRepository(Billet::class)
            ->findBy(['activiteId' => $activity->getId()]);
        
        if (count($bookings) > 0) {
            $this->addFlash('error', 'Cannot delete activity because it has associated bookings. Delete the bookings first.');
            return $this->redirectToRoute('admin_activities');
        }
        
        try {
            // First, delete all resources associated with this activity
            $resources = $activity->getResources();
            foreach($resources as $resource) {
                $this->entityManager->remove($resource);
            }
            
            // Then delete the activity
            $this->entityManager->remove($activity);
            $this->entityManager->flush();
            $this->addFlash('success', 'Activity deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete activity: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_activities');
    }

    #[Route('/delete/booking/{id}', name: 'admin_delete_booking')]
    public function deleteBooking(int $id): Response
    {
        $booking = $this->entityManager->getRepository(Billet::class)->find($id);
        
        if (!$booking) {
            $this->addFlash('error', 'Booking not found.');
            return $this->redirectToRoute('admin_bookings');
        }
        
        try {
            // First find and delete any associated reservations
            $reservation = $this->entityManager->getRepository(Reservation::class)
                ->findOneBy(['billet' => $booking]);
                
            if ($reservation) {
                $this->entityManager->remove($reservation);
            }
            
            // Then delete the booking (billet)
            $this->entityManager->remove($booking);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Booking deleted successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete booking: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_bookings');
    }

    #[Route('/ban/user/{id}', name: 'admin_ban_user')]
    public function banUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(Users::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        try {
            $user->setIsBanned(true);
            $this->entityManager->flush();
            $this->addFlash('success', 'User has been banned successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to ban user: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_users');
    }
    
    #[Route('/unban/user/{id}', name: 'admin_unban_user')]
    public function unbanUser(int $id): Response
    {
        $user = $this->entityManager->getRepository(Users::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        try {
            $user->setIsBanned(false);
            $this->entityManager->flush();
            $this->addFlash('success', 'User has been unbanned successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to unban user: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/change-role/user/{id}/{role}', name: 'admin_change_role_user')]
    public function changeUserRole(int $id, string $role): Response
    {
        // Validate role
        $allowedRoles = ['Admin', 'Publicitaire', 'user'];
        if (!in_array($role, $allowedRoles)) {
            $this->addFlash('error', 'Invalid role specified.');
            return $this->redirectToRoute('admin_users');
        }
        
        $user = $this->entityManager->getRepository(Users::class)->find($id);
        
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('admin_users');
        }
        
        try {
            $user->setRole($role);
            $this->entityManager->flush();
            $this->addFlash('success', 'User role has been changed to ' . $role . ' successfully.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to change user role: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('admin_users');
    }
    
    #[Route('/activities', name: 'admin_activities')]
    public function activities(Request $request): Response
    {
        // Get filter parameters
        $search = $request->query->get('search', '');
        $genre = $request->query->get('genre', '');
        
        // Get pagination parameters
        $page = $request->query->getInt('page', 1);
        $limit = 5; // Show 5 items per page
        
        // Create query builder
        $repository = $this->entityManager->getRepository(Activities::class);
        $queryBuilder = $repository->createQueryBuilder('a');
        
        // Apply filters if provided
        if (!empty($search)) {
            $queryBuilder->andWhere('a.activityName LIKE :search OR a.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        if (!empty($genre)) {
            $queryBuilder->andWhere('a.genre = :genre')
                ->setParameter('genre', $genre);
        }
        
        // Get total count for pagination
        $countQb = clone $queryBuilder;
        $totalItems = count($countQb->getQuery()->getResult());
        $totalPages = ceil($totalItems / $limit);
        
        // Add order and pagination to the main query
        $queryBuilder->orderBy('a.id', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        
        // Get paginated results
        $activities = $queryBuilder->getQuery()->getResult();
            
        // Get user IDs for easy lookup
        $userIds = [];
        foreach ($activities as $activity) {
            if (method_exists($activity, 'getUser') && $activity->getUser()) {
                $userIds[] = $activity->getUser()->getId();
            }
        }
        
        // Get all users in one database query
        $users = [];
        if (!empty($userIds)) {
            $userEntities = $this->entityManager->getRepository(Users::class)
                ->findBy(['id' => array_unique($userIds)]);
                
            // Index users by ID for easy lookup
            foreach ($userEntities as $user) {
                $users[$user->getId()] = $user;
            }
        }

        return $this->render('admin/activities.html.twig', [
            'activities' => $activities,
            'users' => $users,
            'search' => $search,
            'genre' => $genre,
            // Pagination data
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'limit' => $limit
        ]);
    }
}
