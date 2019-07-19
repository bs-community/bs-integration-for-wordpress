<?php

require_once(__DIR__.'/BaseCipher.php');

class SHA256 extends BaseCipher
{
    /**
     * Once SHA256 hash.
     */
    public function hash($value, $salt = '')
    {
        return hash('sha256', $value);
    }
}
