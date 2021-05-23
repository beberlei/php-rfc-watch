<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Rfc;
use Doctrine\ORM\EntityManagerInterface;

class RfcOrmRepository implements RfcRepository
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function findOneByUrl(string $url): ?Rfc
    {
        return $this->entityManager->getRepository(Rfc::class)->findOneBy(['url' => $url]);
    }

    /** @return list<Rfc> */
    public function findActiveRfcs(): array
    {
        return $this->entityManager->getRepository(Rfc::class)->findBy(['status' => Rfc::OPEN]);
    }

    public function persist(Rfc $rfc): void
    {
        $this->entityManager->persist($rfc);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }
}
