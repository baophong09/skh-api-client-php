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

    public function setUrl($url)
    {
        $this->url = filter_var(trim($url,'&'), FILTER_SANITIZE_URL);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function request($type, $url, $params = [])
    {
        $type = strtolower($type);

        return $this->$type($url, $params);
    }

    public function get($url, $params = [])
    {
        if($params) {
            $url .= '?';
            foreach($params as $key => $param)
            {
                $url .= $key.'='.$param.'&';
            }
        }

        $this->setUrl($url);
        return $this->exec($this->curl);
    }

    public function post($url, $params)
    {
        $this->setUrl($url);
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