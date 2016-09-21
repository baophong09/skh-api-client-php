<?php

namespace Skh\Request;

class Request
{
    public $curl;

    public $url;

    public function __construct()
    {
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Accept: application/json'));
    }

    public function setUrl($url, $token)
    {
        $this->url = filter_var(trim($url,'&'), FILTER_SANITIZE_URL);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$token
        ]);
    }

    public function request($type, $url, $params = [], $token = null)
    {
        $type = strtolower($type);

        return $this->$type($url, $params, $token);
    }

    public function get($url, $params = [], $token = null)
    {
        if($params) {
            $url .= '?';
            foreach($params as $key => $param)
            {
                $url .= $key.'='.$param.'&';
            }
        }

        $this->setUrl($url, $token);
        return $this->exec($this->curl);
    }

    public function post($url, $params, $token = null)
    {
        $this->setUrl($url, $token);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        return $this->exec($this->curl);
    }

    public function exec($ch)
    {
        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }
}