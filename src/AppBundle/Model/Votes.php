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
        $added = array_diff_assoc($this->votes, $other->votes);
        $removed = array_diff_assoc($other->votes, $this->votes);

        $newVotes = Votes::emptyVote();
        foreach ($added as $username => $option) {
            $newVotes = $newVotes->addVote($username, $option);
        }

        $removedVotes = Votes::emptyVote();
        foreach ($removed as $username => $option) {
            $removedVotes = $removedVotes->addVote($username, $option);
        }

        return new VotesDiff($newVotes, $removedVotes);
    }

    public function equals(Votes $other)
    {
        return $this->votes === $other->votes;
    }
}
