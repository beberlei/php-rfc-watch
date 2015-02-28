<?php

namespace AppBundle\CouchDocument;

use Doctrine\ODM\CouchDB\Mapping\Annotations as CouchDB;
use AppBundle\Model\Votes;

/**
 * @CouchDB\Document
 * @CouchDB\Index
 */
class RequestForComment
{
    const OPEN = 'open';
    const CLOSE = 'close';

    /**
     * @CouchDB\Id
     */
    private $id;

    /**
     * @CouchDB\Field(type="string")
     */
    private $url;

    /**
     * @CouchDB\Field(type="string")
     * @var string
     */
    private $title;

    /**
     * @CouchDB\Field(type="string")
     * @var string
     */
    private $author;

    /**
     * @CouchDB\Field(type="string")
     * @var string
     */
    private $status = self::OPEN;

    /**
     * @CouchDB\Field(type="mixed")
     * @var array<string,string>
     */
    private $currentVotes = array();

    public function getId()
    {
        return $this->id;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isOpen()
    {
        return $this->status === self::OPEN;
    }

    public function closeVote()
    {
        $this->status = self::CLOSE;
    }

    public function setAuthor($author)
    {
        $this->author = $author;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setVotes(Votes $votes)
    {
        $this->currentVotes = array();
        foreach ($votes as $username => $vote) {
            $this->currentVotes[$username] = $vote->getOption();
        }
    }

    public function getVotes()
    {
        return new Votes($this->currentVotes);
    }

    public function getCurrentResults()
    {
        $results = array();

        foreach ($this->currentVotes as $_ => $vote) {
            if (!isset($results[$vote])) {
                $results[$vote] = ['votes' => 0, 'share' => 0, 'option' => $vote];
            }
            $results[$vote]['votes']++;
        }

        $total = count($this->currentVotes);
        foreach ($results as $vote => $data) {
            $results[$vote]['share'] = $data['votes'] / $total;
        }

        $result = array_values($results);

        // magic happening here
        usort($result, function ($a, $b) {
            $aYes = stripos($a['option'], 'yes') === 0;
            $bYes = stripos($b['option'], 'yes') === 0;

            if ($aYes && !$bYes) {
                return -1;
            } else if ($bYes && !$aYes) {
                return 1;
            }

            return strcmp($a['option'], $b['option']);
        });

        return $result;
    }
}
