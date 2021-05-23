<?php

namespace App\Repository;

use App\Entity\Rfc;
use Doctrine\ORM\EntityManagerInterface;

class RfcOrmRepository implements RfcRepository
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findOneByUrl(string $url): ?Rfc
    {
        return $this->entityManager->getRepository(Rfc::class)->findOneBy(['url' => $url]);
    }

    public function findActiveRfcs(): array
    {
        return $this->entityManager->getRepository(Rfc::class)->findBy(['status' => Rfc::OPEN]);
    }

    public function persist(Rfc $rfc)
    {
        $this->entityManager->persist($rfc);
    }

    public function flush()
    {
        $this->entityManager->flush();
    }
}
