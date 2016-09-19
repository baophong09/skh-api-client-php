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

    public function get($data)
    {
        return $this->crypt->encrypt($data);
    }
}