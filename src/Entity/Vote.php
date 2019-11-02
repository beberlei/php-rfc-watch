<?php

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

    /**
     * @ORM\ManyToOne(targetEntity="Rfc", inversedBy="votes")
     */
    public $rfc;

    /**
     * @ORM\Column(type="boolean")
     */
    public $primaryVote = true;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public $voteId;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    public $question;

    /**
     * @ORM\Column(type="json_array")
     * @var array<string,int>
     */
    public $currentVotes = array();

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    public $passThreshold = 66;
}
