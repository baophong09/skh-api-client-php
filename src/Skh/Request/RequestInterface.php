<?php

namespace Skh\Request;

interface RequestInterface
{
    public static function get($url, $param, $token);

    public static function post($url, $param, $token);

    public static function put($url, $param, $token);

    public static function delete($url, $param, $token);

    public function request($url, $param, $token);
}