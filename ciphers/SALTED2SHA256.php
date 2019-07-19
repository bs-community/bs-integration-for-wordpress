<?php

require_once(__DIR__.'/BaseCipher.php');

class SALTED2SHA256 extends BaseCipher
{
    /**
     * SHA256 hash with salt.
     */
    public function hash($value, $salt = '')
    {
        return hash('sha256', hash('sha256', $value).$salt);
    }
}
