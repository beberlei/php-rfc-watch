<?php

namespace App\Model;

use App\Entity\Rfc;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class UpdatedRfcListener
{
    private $publisher;

    public function __construct(MercurePublisher $publisher)
    {
        $this->publisher = $publisher;
    }

    public function postUpdate(Rfc $rfc, LifecycleEventArgs $event)
    {
        ($this->publisher)->publish('rfcs', ['rfc' => $rfc->id]);
    }
}