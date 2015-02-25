<?php

namespace AppBundle\CouchDocument;

use Doctrine\CouchDB\ODM\Mapping\Annotations as CouchDB;

/**
 * @CouchDB\Document
 */
class RfcVote
{
    /**
     * @CouchDB\Id
     */
    private $id;

    /**
     * @CouchDB\Type("string")
     * @var string
     */
    private $title;
}
