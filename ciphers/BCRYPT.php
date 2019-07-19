<?php

require_once(__DIR__.'/BaseCipher.php');

class BCRYPT extends BaseCipher
{
    public function hash($value, $salt = '')
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public function verify($password, $hash, $salt = '')
    {
        return password_verify($password, $hash);
    }
}
