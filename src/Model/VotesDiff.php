<?php

namespace App\Model;

use Countable;

class VotesDiff implements Countable
{
    /**
     * @var Votes
     */
    private $newVotes;

    /**
     * @var Votes
     */
    private $removedVotes;

    public function __construct(Votes $newVotes, Votes $removedVotes)
    {
        $this->newVotes = $newVotes;
        $this->removedVotes = $removedVotes;
    }

    public function getNewVotes()
    {
        return $this->newVotes;
    }

    public function getRemovedVotes()
    {
        return $this->removedVotes;
    }

    public function count()
    {
        return count($this->newVotes) + count($this->removedVotes);
    }
}
