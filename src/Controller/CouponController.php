<?php

namespace App\Controller;

use App\Entity\Coupon;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class CouponController extends AbstractController
{
    private $entityManager;
    private $couponRepository;
    private $security;

    public function __construct(
        EntityManagerInterface $entityManager,
        CouponRepository $couponRepository,
        Security $security
    ) {
        $this->entityManager = $entityManager;
        $this->couponRepository = $couponRepository;
        $this->security = $security;
    }

    #[Route('/admin/coupons', name: 'app_admin_coupons')]
    public function index(): Response
    {
        $user = $this->security->getUser();
        if (!$user || ($user->getRole() !== 'Admin' && $user->getRole() !== 'admin')) {
            return $this->redirectToRoute('app_home');
        }

        // Get all coupons sorted by created date descending
        $coupons = $this->couponRepository->findBy([], ['created_at' => 'DESC']);
        
        return $this->render('admin/coupons/index.html.twig', [
            'coupons' => $coupons
        ]);
    }

    #[Route('/admin/coupons/new', name: 'app_admin_coupons_new')]
    public function new(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user || ($user->getRole() !== 'Admin' && $user->getRole() !== 'admin')) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            $code = strtoupper($request->request->get('code'));
            $discount = $request->request->get('discount');
            $isPercentage = $request->request->get('discount_type') === 'percentage';
            $usageLimit = $request->request->get('usage_limit');
            $expiresAt = $request->request->get('expires_at');
            
            // Validate inputs
            if (empty($code) || empty($discount) || empty($usageLimit) || empty($expiresAt)) {
                $this->addFlash('error', 'All fields are required');
                return $this->redirectToRoute('app_admin_coupons_new');
            }
            
            // Check if coupon with this code already exists
            $existingCoupon = $this->couponRepository->findOneBy(['code' => $code]);
            if ($existingCoupon) {
                $this->addFlash('error', 'A coupon with this code already exists');
                return $this->redirectToRoute('app_admin_coupons_new');
            }
            
            // Create new coupon
            $coupon = new Coupon();
            $coupon->setCode($code);
            $coupon->setDiscount(floatval($discount));
            $coupon->setIsPercentage($isPercentage);
            $coupon->setUsageLimit(intval($usageLimit));
            $coupon->setUsageCount(0);
            $coupon->setExpiresAt(new \DateTimeImmutable($expiresAt));
            $coupon->setIsActive(true);
            $coupon->setCreatedAt(new \DateTimeImmutable());
            
            $this->entityManager->persist($coupon);
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Coupon created successfully');
            return $this->redirectToRoute('app_admin_coupons');
        }
        
        return $this->render('admin/coupons/new.html.twig');
    }

    #[Route('/admin/coupons/edit/{id}', name: 'app_admin_coupons_edit')]
    public function edit(Request $request, int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user || ($user->getRole() !== 'Admin' && $user->getRole() !== 'admin')) {
            return $this->redirectToRoute('app_home');
        }
        
        $coupon = $this->couponRepository->find($id);
        if (!$coupon) {
            $this->addFlash('error', 'Coupon not found');
            return $this->redirectToRoute('app_admin_coupons');
        }
        
        if ($request->isMethod('POST')) {
            $discount = $request->request->get('discount');
            $isPercentage = $request->request->get('discount_type') === 'percentage';
            $usageLimit = $request->request->get('usage_limit');
            $expiresAt = $request->request->get('expires_at');
            $isActive = $request->request->has('is_active');
            
            // Validate inputs
            if (empty($discount) || empty($usageLimit) || empty($expiresAt)) {
                $this->addFlash('error', 'All fields are required');
                return $this->redirectToRoute('app_admin_coupons_edit', ['id' => $id]);
            }
            
            // Update coupon
            $coupon->setDiscount(floatval($discount));
            $coupon->setIsPercentage($isPercentage);
            $coupon->setUsageLimit(intval($usageLimit));
            $coupon->setExpiresAt(new \DateTimeImmutable($expiresAt));
            $coupon->setIsActive($isActive);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', 'Coupon updated successfully');
            return $this->redirectToRoute('app_admin_coupons');
        }
        
        return $this->render('admin/coupons/edit.html.twig', [
            'coupon' => $coupon
        ]);
    }

    #[Route('/admin/coupons/delete/{id}', name: 'app_admin_coupons_delete')]
    public function delete(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user || ($user->getRole() !== 'Admin' && $user->getRole() !== 'admin')) {
            return $this->redirectToRoute('app_home');
        }
        
        $coupon = $this->couponRepository->find($id);
        if (!$coupon) {
            $this->addFlash('error', 'Coupon not found');
            return $this->redirectToRoute('app_admin_coupons');
        }
        
        $this->entityManager->remove($coupon);
        $this->entityManager->flush();
        
        $this->addFlash('success', 'Coupon deleted successfully');
        return $this->redirectToRoute('app_admin_coupons');
    }

    #[Route('/api/validate-coupon', name: 'app_api_validate_coupon', methods: ['POST'])]
    public function validateCoupon(Request $request): JsonResponse
    {
        $code = strtoupper($request->request->get('code'));
        $price = floatval($request->request->get('price'));
        
        if (empty($code)) {
            return $this->json(['success' => false, 'message' => 'Coupon code is required']);
        }
        
        $coupon = $this->couponRepository->findValidCoupon($code);
        
        if (!$coupon) {
            return $this->json(['success' => false, 'message' => 'Invalid or expired coupon code']);
        }
        
        $discount = $coupon->calculateDiscount($price);
        $newPrice = $price - $discount;
        
        $discountText = $coupon->isPercentage() 
            ? $coupon->getDiscount() . '%' 
            : $coupon->getDiscount() . ' TND';
        
        return $this->json([
            'success' => true,
            'message' => 'Coupon applied successfully',
            'discount' => $discount,
            'discountText' => $discountText,
            'newPrice' => $newPrice,
            'couponId' => $coupon->getId()
        ]);
    }
}
