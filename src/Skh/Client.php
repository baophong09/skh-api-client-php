<?php

namespace Skh;

use \Skh\Request\Request as Request;
use \Skh\Token\Token as Token;

class Client
{
    private $publicKey;

    private $secretKey;

    private $client;

    private $serverName;

    private $request;

    private $cookie;

    const API_SERVER = 'http://api.sukienhay.com/';

    const VERSION = 'v1/';   

    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = md5($secretKey);
        $this->serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $this->request = new Request();
        $this->token = new Token($this->secretKey);
        $this->cookie = isset($_COOKIE["SKH_API_COOKIE"]) ? $_COOKIE["SKH_API_COOKIE"] : "";
    }

    private function getVerifyApplication($publicKey, $secretKey, $cookie)
    {
        $data = [
            'iat'           =>  time(),
            'iss'           =>  $this->serverName,
            'public_key'    =>  $publicKey,
            'exp'           =>  time() + 60,
            'data'          =>  [
                'public_key'    =>  $publicKey,
                'secret_key'    =>  $secretKey,
                'cookie'        =>  $cookie
            ]
        ];

        return $this->token->get($data);
    }

    public function getAccessToken()
    {
        $res = $this->post('token/generate', [
            'verify_token'  =>  $this->getVerifyApplication($this->publicKey, $this->secretKey, $this->cookie),
            'public_key'    =>  $this->publicKey
        ]);

        dd($res);

        $decoded = json_decode($res);

        if($decoded && $decoded->success === true && $decoded->access_token) {
            setcookie("SKH_API_COOKIE", $this->token->get($decoded->access_token), $decoded->ei);
        }

        return $res;
    }

    public function get($url, $params)
    {
        $res = $this->request->request('GET', self::API_SERVER . self::VERSION . $url, $params);

        return $res;
    }

    public function post($url, $params)
    {
        $res = $this->request->request('POST', self::API_SERVER . self::VERSION . $url, $params);

        return $res;
    }

    public function put($url, $params)
    {

    }

    public function delete($url, $params)
    {

    }
}