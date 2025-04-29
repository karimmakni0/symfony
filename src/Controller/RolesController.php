<?php

namespace App\Controller;

use App\Entity\Roles;
use App\Repository\RolesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/roles", name="api_roles_")
 */
class RolesController extends AbstractController
{
    private $entityManager;
    private $rolesRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RolesRepository $rolesRepository
    ) {
        $this->entityManager = $entityManager;
        $this->rolesRepository = $rolesRepository;
    }

    /**
     * @Route("", name="index", methods={"GET"})
     */
    public function index(): Response
    {
        // Will implement later
        return new Response('Roles index');
    }
}
