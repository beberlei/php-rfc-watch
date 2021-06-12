<?php

namespace App\Tests\App\Entity;

use App\Entity\Rfc;
use App\Entity\Vote;
use PHPUnit\Framework\TestCase;

class RfcTest extends TestCase
{
    public function testTallyQuestionResults(): void
    {
        $vote1 = new Vote();
        $vote1->question = 'Foo?';
        $vote1->passThreshold = 66;
        $vote1->currentVotes['Yes'] = 10;
        $vote1->currentVotes['No'] = 0;

        $vote2 = new Vote();
        $vote2->question = 'Bar?';
        $vote2->passThreshold = 66;
        $vote2->currentVotes['Bar!'] = 5;
        $vote2->currentVotes['Barrr?'] = 5;

        $rfc = new Rfc();
        $rfc->votes->add($vote1);
        $rfc->votes->add($vote2);

        $talliedResults = $rfc->tallyQuestionResults();

        $this->assertEquals([
            [
                'question' => 'Foo?',
                'hasYes' => true,
                'passing' => true,
                'results' => [
                    ['votes' => 10, 'share' => 1, 'option' => 'Yes'],
                    ['votes' => 0, 'share' => 0, 'option' => 'No'],
                ],
            ],
            [
                'question' => 'Bar?',
                'hasYes' => false,
                'passing' => false,
                'results' => [
                    ['votes' => 5, 'share' => 0.5, 'option' => 'Bar!'],
                    ['votes' => 5, 'share' => 0.5, 'option' => 'Barrr?'],
                ],
            ],
        ], $talliedResults);
    }

    public function testTallyVoteResultsForNonPassingVote(): void
    {
        $vote1 = new Vote();
        $vote1->question = 'Foo?';
        $vote1->passThreshold = 66;
        $vote1->currentVotes['Yes'] = 0;
        $vote1->currentVotes['No'] = 10;

        $rfc = new Rfc();
        $rfc->votes->add($vote1);

        $talliedResults = $rfc->tallyQuestionResults();

        $this->assertEquals([
            [
                'question' => 'Foo?',
                'hasYes' => true,
                'passing' => false,
                'results' => [
                    ['votes' => 0, 'share' => 0, 'option' => 'Yes'],
                    ['votes' => 10, 'share' => 1, 'option' => 'No'],
                ],
            ],
        ], $talliedResults);
    }

    public function testGetVoteById(): void
    {
        $rfc = new Rfc();
        $vote = $rfc->getVoteById('xyz');

        $this->assertEquals('xyz', $vote->voteId);
        $this->assertSame($vote, $rfc->getVoteById($vote->voteId));
    }
}
