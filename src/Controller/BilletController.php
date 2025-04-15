<?php

namespace App\Controller;

use App\Entity\Billet;
use App\Repository\BilletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/billets", name="api_billets_")
 */
class BilletController extends AbstractController
{
    private $entityManager;
    private $billetRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        BilletRepository $billetRepository
    ) {
        $this->entityManager = $entityManager;
        $this->billetRepository = $billetRepository;
    }

    /**
     * @Route("", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        // Will implement later
        return new Response('Billets index');
    }
}
