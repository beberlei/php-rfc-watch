<?php

namespace App\Model;

use PHPUnit\Framework\TestCase;

class VotesTest extends TestCase
{
    /**
     * @test
     */
    public function it_diffs_votes()
    {
        $empty = Votes::emptyVote();
        $votes = Votes::emptyVote()
            ->addVote('beberlei', 'Yes')
            ->addVote('derick', 'No');

        $diff = $votes->diff($empty);

        $this->assertTrue($votes->equals($diff->getNewVotes()));
        $this->assertTrue($empty->equals($diff->getRemovedVotes()));

        $diff = $empty->diff($votes);

        $this->assertTrue($votes->equals($diff->getRemovedVotes()));
        $this->assertTrue($empty->equals($diff->getNewVotes()));

        $moreVotes = $votes
            ->addVote('zeev', 'Yes')
            ->addVote('andi', 'No');

        $diff = $moreVotes->diff($votes);

        $this->assertEquals(4, count($moreVotes));
        $this->assertTrue($diff->getNewVotes()->equals(new Votes(['zeev' => 'Yes', 'andi' => 'No'])));

        $removeVotes = $moreVotes->removeVote('derick');

        $diff = $removeVotes->diff($moreVotes);

        $this->assertTrue($diff->getRemovedVotes()->equals(new Votes(['derick' => 'No'])));
    }
}
