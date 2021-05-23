<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Rfc;

interface RfcRepository
{
    public function findOneByUrl(string $url): ?Rfc;

    /** @return list<Rfc> */
    public function findActiveRfcs(): array;

    public function persist(Rfc $rfc): void;

    public function flush(): void;
}
