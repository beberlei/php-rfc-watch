<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;

/**
 * @ORM\Entity
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $eventType;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $option;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\RequestForComment")
     */
    private $rfc;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    public function __construct(RequestForComment $rfc, $type, $option, DateTime $date)
    {
        $this->rfc = $rfc;
        $this->eventType = $type;
        $this->option = $option;
        $this->date = $date;
    }

    public function getRfc()
    {
        return $this->rfc;
    }

    public function getType()
    {
        return $this->eventType;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getDate()
    {
        return $this->date;
    }
}
