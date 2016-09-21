<?php

namespace Skh;

use \Skh\Request\Request as Request;
use \Skh\Token\Token as Token;

class Client
{
    public static $instance;

    public static $config;

    public $accessToken;

    private $publicKey;

    private $secretKey;

    private $serverName;

    private $request;

    private $cookie;

    const API_SERVER = 'http://api.sukienhay.com/';

    const VERSION = 'v1/';

    public static function getInstance()
    {
        if(is_null(static::$instance)) {
            static::$instance = new self(static::$config["public_key"], static::$config["secret_key"]);
        }

        return static::$instance;
    }

    public static function config($config)
    {
        foreach($config as $k => $v) {
            static::$config[$k] = $v;
        }

        return static::$config;
    }

    public function __construct($publicKey, $secretKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = md5($secretKey.$publicKey);
        $this->serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $this->request = new Request();
        $this->token = new Token($this->secretKey);
        $this->cookie = isset($_COOKIE["SKH_API_COOKIE"]) ? $_COOKIE["SKH_API_COOKIE"] : "";

        if($this->cookie) {
            $cookie = $this->token->decrypt($this->cookie);

            $this->accessToken = isset($cookie->token) ? $cookie->token : "";
        }
    }

    public function getAccessToken()
    {
        $res = $this->post('token/generate', [
            'verify_token'  =>  $this->getVerifyApplicationToken($this->publicKey, $this->secretKey, $this->cookie),
            'public_key'    =>  $this->publicKey
        ]);

        $decoded = json_decode($res);

        if($decoded && $decoded->success === true && $decoded->access_token) {
            $this->accessToken = $decoded->access_token;

            $this->setCookie([
                "token" => $this->accessToken,
                "ei"    => $decoded->ei,
                "eid"   => $decoded->expire_in
            ], $decoded->ei);

        }

        return $res;
    }

    public function haveCookie()
    {
        return (isset($this->cookie) && $this->cookie) ? $this->cookie : false;
    }

    public function getCookie()
    {
        $cookie = $this->token->decrypt($this->cookie);

        if($cookie && $cookie->ei > time()) {
            return $cookie;
        }

        return false;
    }

    public function setCookie($data, $time)
    {
        $this->cookie = $this->token->encrypt($data);

        setcookie("SKH_API_COOKIE", $this->cookie, $time);

        return true;
    }

    public function token()
    {
        return ($this->accessToken) ? $this->accessToken : "";
    }


    public function get($url, $params = [])
    {
        $accessToken = $this->accessToken;

        $res = $this->request->request('GET', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        return $res;
    }

    public function post($url, $params = [])
    {
        $accessToken = $this->accessToken;

        $res = $this->request->request('POST', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        return $res;
    }

    public function put($url, $params = [])
    {
        $accessToken = $this->accessToken;

    }

    public function delete($url, $params = [])
    {
        $accessToken = $this->accessToken;

    }

    private function getVerifyApplicationToken($publicKey, $secretKey, $cookie)
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

        return $this->token->encrypt($data);
    }
}