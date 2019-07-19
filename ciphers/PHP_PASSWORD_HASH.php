<?php

require_once(__DIR__.'/BaseCipher.php');

class PHP_PASSWORD_HASH extends BaseCipher
{
    public function hash($value, $salt = '')
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    public function verify($password, $hash, $salt = '')
    {
        return password_verify($password, $hash);
    }
}
