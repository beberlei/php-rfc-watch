<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Rfc
{
    public const OPEN = 'open';
    public const CLOSE = 'close';
    public const ABORTED = 'aborted';

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id;

    /** @ORM\Column(type="string") */
    public string $url;

    /** @ORM\Column(type="string") */
    public string $title;

    /** @ORM\Column(type="string") */
    public string $status = self::OPEN;

    /** @ORM\Column(type="datetime", nullable=true) */
    public ?\DateTime $closeDate;

    /** @ORM\Column(type="string") */
    public string $targetPhpVersion = '';

    /**
     * @ORM\Column(type="json_array")
     *
     * @var array<string>
     */
    public array $discussions = [];

    /**
     * @ORM\OneToMany(targetEntity="Vote", mappedBy="rfc", indexBy="voteId", cascade={"PERSIST"})
     *
     * @var Collection<string, Vote>
     */
    public Collection $votes;

    /** @ORM\Column(type="datetime") */
    public \DateTime $firstVote;

    /** @ORM\Column(type="boolean") */
    public bool $rejected = false;

    /** @ORM\Column(type="datetime") */
    public \DateTime $created;

    public function __construct()
    {
        $this->created = new \DateTime('now');
        $this->firstVote = new \DateTime('now');
        $this->votes = new ArrayCollection();
    }

    public function getVoteById(string $voteId): Vote
    {
        if (! isset($this->votes[$voteId])) {
            $this->votes[$voteId] = new Vote();
            $this->votes[$voteId]->rfc = $this;
            $this->votes[$voteId]->voteId = $voteId;
        }

        return $this->votes[$voteId];
    }

    /**
     * @return array<Vote>
     */
    public function getVoteList(): array
    {
        return array_values($this->votes->toArray());
    }
}
