<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Vote
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\ManyToOne(targetEntity="Rfc", inversedBy="votes") */
    public $rfc;

    /** @ORM\Column(type="boolean") */
    public $primaryVote = true;

    /** @ORM\Column(type="string", nullable=true) */
    public $voteId;

    /** @ORM\Column(type="string", nullable=true) */
    public string $question;

    /**
     * @ORM\Column(type="json_array")
     *
     * @var array<string,int>
     */
    public array $currentVotes = [];

    /** @ORM\Column(type="integer") */
    public int $passThreshold = 66;

    /** @ORM\Column(type="boolean", nullable=true) */
    public bool $hide = false;
}
