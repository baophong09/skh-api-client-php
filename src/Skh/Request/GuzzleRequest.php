<?php

namespace Skh\Request;

use \GuzzleHttp\Client as Guzzle;

class GuzzleRequest
{
    public function __construct()
    {
        $this->guzzle = new Guzzle();
    }

    public function get($url, $params, $token = null)
    {
        $this->guzzle->request('GET', $url, [
            "headers"   =>  [
                "Accept"    =>  'application/json',
                "Authorization: Bearer ".$token
            ]
        ]);
    }

    public function post($url, $params = [], $token = null)
    {
        $data = array();
        foreach($params as $k => $param) {
            $tmp = array();
            $tmp["name"] = $k;
            $tmp["contents"] = $param;

            array_push($data, $tmp);
        }

        $this->guzzle->request('POST', $url, [
            "headers"   =>  [
                "Accept"        => "application/json",
                "Authorization" => "Bearer ".$token
            ],

            "multipart" =>  $data
        ]);
    }

    public function request($type, $url, $params = [], $token = null)
    {
        $type = strtolower($type);

        return $this->{$type}($url, $params, $token);
    }
}