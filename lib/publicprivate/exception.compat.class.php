<?php

/**
 * An public/private base exception for PHP versions before PHP 5.3.
 *
 * If PHP 5.3+, should use exception.base.class.php instead.
 *
 * @uses Exception
 */

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
