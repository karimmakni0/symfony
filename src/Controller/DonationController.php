<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class DonationController extends AbstractController
{
    #[Route('/donate', name: 'app_donate')]
    public function index(): Response
    {
        // Get Stripe publishable key from environment
        $stripePublicKey = $_ENV['STRIPE_PUBLIC_KEY'] ?? 'pk_test_yourStripePublicKey';
        
        return $this->render('donation/index.html.twig', [
            'controller_name' => 'DonationController',
            'stripe_public_key' => $stripePublicKey,
        ]);
    }

    #[Route('/donate/checkout', name: 'app_donate_checkout')]
    public function checkout(Request $request): Response
    {
        try {
            // Set Stripe API key
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
            
            // Check if this is a JSON request (from the JavaScript client)
            if ($request->getContentType() === 'json' || $request->headers->get('Content-Type') === 'application/json') {
                try {
                    $data = json_decode($request->getContent(), true);
                    if (!$data) {
                        return new JsonResponse(['error' => 'Invalid JSON format'], 400);
                    }
                    
                    // Get amount from JSON data
                    $amount = isset($data['amount']) ? (int)$data['amount'] : 10;
                    if ($amount < 1) {
                        return new JsonResponse(['error' => 'Invalid amount'], 400);
                    }
                    
                    // Convert to cents for Stripe
                    $amount = $amount * 100;
                    
                    // Get name and email for metadata
                    $name = $data['name'] ?? 'Anonymous';
                    $email = $data['email'] ?? '';
                    $message = $data['message'] ?? '';
                    
                    // Create Stripe session
                    $session = Session::create([
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => 'usd',
                                'product_data' => [
                                    'name' => 'Support Palestine Donation',
                                ],
                                'unit_amount' => $amount,
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => $this->generateUrl('app_donate_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'cancel_url' => $this->generateUrl('app_donate', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'customer_email' => $email ?: null,
                        'metadata' => [
                            'name' => $name,
                            'message' => $message,
                        ],
                    ]);
                    
                    return new JsonResponse(['url' => $session->url]);
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => $e->getMessage()], 400);
                }
            } else {
                // Traditional form submission
                // Get amount from form
                $amount = $request->request->get('amount', 10);
                
                // Handle custom amount
                if ($amount === 'custom') {
                    $amount = $request->request->get('custom_amount', 10);
                }
                
                // Convert to cents for Stripe
                $amount = (int)$amount * 100;
                
                // Get name and email for metadata
                $name = $request->request->get('name', 'Anonymous');
                $email = $request->request->get('email', '');
                $message = $request->request->get('message', '');
                
                $session = Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => [[
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Support Palestine Donation',
                            ],
                            'unit_amount' => $amount,
                        ],
                        'quantity' => 1,
                    ]],
                    'mode' => 'payment',
                    'success_url' => $this->generateUrl('app_donate_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'cancel_url' => $this->generateUrl('app_donate', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    'customer_email' => $email ?: null,
                    'metadata' => [
                        'name' => $name,
                        'message' => $message,
                    ],
                ]);
                
                return $this->redirect($session->url, 303);
            }
        } catch (\Exception $e) {
            if ($request->getContentType() === 'json' || $request->headers->get('Content-Type') === 'application/json') {
                return new JsonResponse(['error' => $e->getMessage()], 500);
            }
            
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_donate');
        }
    }

    #[Route('/donate/success', name: 'app_donate_success')]
    public function success(): Response
    {
        return $this->render('donation/success.html.twig');
    }
}
