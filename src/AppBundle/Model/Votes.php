<?php

namespace AppBundle\Model;

use IteratorAggregate;
use Countable;
use ArrayIterator;

class Votes implements IteratorAggregate, Countable
{
    private $votes = array();

    public function emptyVote()
    {
        return new self([]);
    }

    public function __construct(array $votes = [])
    {
        $this->votes = $votes;
    }

    public function hasVoted($username)
    {
        return isset($this->votes[$username]);
    }

    public function castedVoted($username)
    {
        return $this->votes[$username];
    }

    public function addVote($username, $option)
    {
        $votes = $this->votes;
        $votes[$username] = $option;

        return new Votes($votes);
    }

    public function removeVote($username)
    {
        $votes = $this->votes;
        unset($votes[$username]);

        return new Votes($votes);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->votes);
    }

    public function count()
    {
        return count($this->votes);
    }

    public function diff(Votes $other)
    {
        $ourVotes = $this->votes;

        $added = array_diff_assoc($ourVotes, $other->votes);
        $removed = array_diff_assoc($other->votes, $ourVotes);

        $newVotes = Votes::emptyVote();
        foreach ($added as $username => $vote) {
            $newVotes = $newVotes->addVote($username, $vote);
        }

        $removedVotes = Votes::emptyVote();
        foreach ($removed as $username => $vote) {
            $removedVotes = $removedVotes->addVote($username, $vote);
        }

        return new VotesDiff($newVotes, $removedVotes);
    }

    public function equals(Votes $other)
    {
        return $this->votes === $other->votes;
    }
}
