<?php

namespace App\Repository;

use App\Entity\Rfc;

interface RfcRepository
{
    public function findOneByUrl(string $url) : ?Rfc;
    public function persist(Rfc $rfc);
    public function flush();
}