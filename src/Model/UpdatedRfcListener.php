<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Rfc;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UpdatedRfcListener
{
    private MercurePublisher $publisher;

    public function __construct(MercurePublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function postUpdate(Rfc $rfc, LifecycleEventArgs $event): void
    {
        $this->publisher->publish('rfcs', ['rfc' => $rfc->id]);
    }
}
