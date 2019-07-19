<?php

require_once(__DIR__.'/BaseCipher.php');

class MD5 extends BaseCipher
{
    /**
     * Once MD5 hash.
     */
    public function hash($value, $salt = '')
    {
        return md5($value);
    }
}
