<?php

require_once(__DIR__.'/BaseCipher.php');

class SHA512 extends BaseCipher
{
    /**
     * Once SHA512 hash.
     */
    public function hash($value, $salt = '')
    {
        return hash('sha512', $value);
    }
}
