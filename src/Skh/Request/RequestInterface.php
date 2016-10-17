<?php

namespace Skh\Request;

interface RequestInterface
{
    public function get($url, $param, $token);

    public function post($url, $param, $token);

    public function request($url, $param, $token);
}