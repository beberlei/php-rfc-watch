<?php

namespace App\CouchDocument;

use Doctrine\ODM\CouchDB\Mapping\Annotations as CouchDB;
use DateTime;

/**
 * @CouchDB\Document
 * @CouchDB\Index
 */
class Event
{
    /**
     * @CouchDB\Id
     */
    private $id;

    /**
     * @CouchDB\Field(type="string")
     */
    private $eventType;

    /**
     * @CouchDB\Field(type="string")
     */
    private $option;

    /**
     * @CouchDB\Field(type="string")
     */
    private $user;

    /**
     * @CouchDB\ReferenceOne(targetDocument="App\CouchDocument\RequestForComment")
     */
    private $rfc;

    /**
     * @CouchDB\Field(type="datetime")
     */
    private $date;

    public function __construct(RequestForComment $rfc, $type, $user, $option, DateTime $date)
    {
        $this->rfc = $rfc;
        $this->eventType = $type;
        $this->user = $user;
        $this->option = $option;
        $this->date = $date;
    }

    public function getRfc()
    {
        return $this->rfc;
    }

    public function getType()
    {
        return $this->eventType;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getDate()
    {
        return $this->date;
    }
}
