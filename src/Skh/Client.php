<?php

namespace Skh;

class Client
{
    private $publicKey;

    private $secretKey;

    private $apiServer = 'http://api.sukienhay.com/';

    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }
}