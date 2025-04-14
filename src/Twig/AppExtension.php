<?php

namespace App\Twig;

use App\Entity\Activities;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getActivityById', [$this, 'getActivityById']),
        ];
    }

    public function getActivityById(int $id): ?Activities
    {
        return $this->entityManager->getRepository(Activities::class)->find($id);
    }
}
