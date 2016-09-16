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

    const API_SERVER = 'http://api.sukienhay.com/';

    const VERSION = 'v1/';   

    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = md5($secretKey);
        $this->serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $this->request = new Request();
        $this->token = new Token($this->secretKey);
    }

    private function getVerifyApplication($publicKey, $secretKey)
    {
        $data = [
            'iat'           =>  time(),
            'iss'           =>  $this->serverName,
            'public_key'    =>  $publicKey,
            'exp'           =>  time() + 60,
            'data'          =>  [
                'public_key'    =>  $publicKey,
                'secret_key'    =>  $this->secretKey
            ]
        ];

        return $this->token->get($data);
    }

    public function getAccessToken()
    {
        $res = $this->post('token/generate', [
            'verify_token'  =>  $this->getVerifyApplication($this->publicKey, $this->secretKey),
            'public_key'    =>  $this->publicKey
        ]);

        dd($res);
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