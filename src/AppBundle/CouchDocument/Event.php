<?php

namespace AppBundle\CouchDocument;

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
    private $type;

    /**
     * @CouchDB\Field(type="string")
     */
    private $option;

    /**
     * @CouchDB\Field(type="string")
     */
    private $user;

    /**
     * @CouchDB\ReferenceOne(targetDocument="AppBundle\CouchDocument\RequestForComment")
     */
    private $rfc;

    /**
     * @CouchDB\Field(type="datetime")
     */
    private $date;

    public function __construct(RequestForComment $rfc, $type, $user, $option, DateTime $date)
    {
        $this->rfc = $rfc;
        $this->type = $type;
        $this->user = $user;
        $this->option = $option;
        $this->date = $date;
    }
}
