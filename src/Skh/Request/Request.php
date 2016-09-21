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

    public function setUrl($url, $token = null)
    {
        $this->url = filter_var(trim($url,'&'), FILTER_SANITIZE_URL);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer ".$token,
            "Content-Type: multipart/form-data"
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
        return $this->exec();
    }

    public function post($url, $params, $token = null)
    {
        $this->setUrl($url, $token);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $params);
        return $this->exec();
    }

    public function exec()
    {
        $response = curl_exec($this->curl);

        curl_close($this->curl);

        $this->curl = null;

        return $response;
    }
}