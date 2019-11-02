<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 */
class Rfc
{
    const OPEN = 'open';
    const CLOSE = 'close';
    const ABORTED = 'aborted';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $url;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    public $title;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    public $status = self::OPEN;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $closeDate;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    public $targetPhpVersion = '';

    /**
     * @ORM\Column(type="json_array")
     * @var array
     */
    public $discussions = [];

    /**
     * @ORM\OneToMany(targetEntity="Vote", mappedBy="rfc", indexBy="voteId", cascade={"PERSIST"})
     */
    public $votes;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    public $firstVote;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    public $rejected = false;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    public $created;

    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->firstVote = new \DateTime('now');
        $this->votes = new ArrayCollection();
    }

    public function getVoteById(string $voteId) : Vote
    {
        if (!isset($this->votes[$voteId])) {
            $this->votes[$voteId] = new Vote();
            $this->votes[$voteId]->rfc = $this;
            $this->votes[$voteId]->voteId = $voteId;
        }

        return $this->votes[$voteId];
    }
}
