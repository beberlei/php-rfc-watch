<?php

namespace App\Model;

class Vote {
    private $option;
    private $time;

    public function __construct($option, $time = null)
    {
        $this->option = $option;
        $this->time = $time ?: new \DateTime;
    }

    public function __toString()
    {
        return $this->getOption();
    }

    public function getOption()
    {
        return $this->option;
    }

    public function getTime()
    {
        return $this->time;
    }
}
