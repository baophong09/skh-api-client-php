<?php

namespace Skh\Token;

use \Skh\Token\Crypt as Crypt;

class Token
{
    private $crypt;

    public function __construct($privateKey = '')
    {
        $this->crypt = new Crypt($privateKey);
    }

    public function encrypt($data)
    {
        return $this->crypt->encrypt($data);
    }

    public function decrypt($data)
    {
        return $this->crypt->decrypt($data);
    }
}