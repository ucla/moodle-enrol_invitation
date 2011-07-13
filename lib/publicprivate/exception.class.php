<?php

class PublicPrivate_Exception extends Exception
{
    private $previous = false;

    public function __construct($message, $code = 0, Exception $previous = false)
    {
        parent::__construct($message, $code);
        $this->previous = $previous;
    }

    public function getPrevious()
    {
        return $this->previous;
    }
}

?>
