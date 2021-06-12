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
    public string $url = '';

    /** @ORM\Column(type="string") */
    public string $title = '';

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
     * @return array<mixed>
     */
    public function tallyQuestionResults(): array
    {
        return array_values(array_map(
            static function (Vote $vote) {
                $data = ['question' => $vote->question, 'results' => [], 'hasYes' => false, 'passing' => false, 'votes' => 0];

                $total = array_sum($vote->currentVotes);

                foreach ($vote->currentVotes as $option => $count) {
                    $data['votes'] += $count;
                    $data['results'][] = [
                        'votes' => $count,
                        'share' => $total > 0 ? $count / $total : 0,
                        'option' => $option,
                    ];

                    if ($option !== 'Yes') {
                        continue;
                    }

                    $data['hasYes'] = true;

                    if ($count / $total < $vote->passThreshold / 100) {
                        continue;
                    }

                    $data['passing'] = true;
                }

                return $data;
            },
            $this->votes->filter(static fn (Vote $vote) => ! $vote->hide)->toArray()
        ));
    }

    public function asFeedText(): string
    {
        $content = 'URL: ' . $this->url . "\n\n";

        if (count($this->discussions) > 0) {
            $content .= "## Discussions\n\n";

            foreach ($this->discussions as $discussion) {
                $content .= '- ' . $discussion . "\n";
            }

            $content .= "\n";
        }

        $content .= "## Votes\n\n";

        foreach ($this->votes as $vote) {
            assert($vote instanceof Vote);

            $content .= sprintf("### %s\n\n", $vote->question);

            foreach ($vote->currentVotes as $option => $count) {
                $content .= sprintf("- %s with %d votes\n", $option, $count);
            }

            $content .= "\n";
        }

        return strip_tags(trim($content));
    }
}
