<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Vote
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Rfc::class, inversedBy: 'votes')]
    public ?Rfc $rfc;

    #[ORM\Column(type: 'boolean')]
    public bool $primaryVote = true;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $voteId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    public ?string $question = null;

    #[ORM\Column(type: 'json')]
    /** @var array<string,int> */
    public array $currentVotes = [];

    #[ORM\Column(type: 'integer')]
    public int $passThreshold = 66;

    #[ORM\Column(type: 'boolean', nullable: true)]
    public bool $hide = false;
}
