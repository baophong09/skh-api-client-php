<?php

/*
 * +------------------------------------------------------------------------+
 * | SUKIENHAY.com API - API CLIENT                                         |
 * | @package       : skh-client-api                                        |
 * | @authors       : Dinh Phong                                            |
 * | @github        : baophong09                                            |
 * | @copyright     : Copyright (c) 2016, SUKIENHAY.COM                     |
 * | @since         : Version 2.0.0                                         |
 * | @website       : https://sukienhay.com                                 |
 * | @e-mail        : dinhphong.developer@gmail.com                         |
 * | @require       : PHP version >= 5.5.0                                  |
 * +------------------------------------------------------------------------+
 */


namespace Skh;

use \Skh\Request\CurlRequest as CurlRequest;
use \Skh\Token\Token as Token;

class Client
{
    /**
     * @var static $instance Store \Skh\Client (this) Object
     * Singleton
     */
    public static $instance;

    /**
     * @var static $config Config (Public Key, Secret Key)
     */
    public static $config;

    /**
     * @var $check | Check public && secret key
     */
    public $check;

    /**
     * @var $accessToken Current access Token
     */
    public $accessToken;

    /**
     * @var $publicKey Public Key of Application
     */
    private $publicKey;

    /**
     * @var $secretKey Secret Key of Application (after MD5)
     */
    private $secretKey;

    /**
     * @var $serverName Server Name of Application
     */
    private $serverName;

    /**
     * @var $request \Skh\Request\Request Object
     */
    private $request;

    /**
     * @var $cookie Current cookie (Container of accessToken, Expire of token etc...)
     */
    private $cookie;

    /**
     * API_SERVER: Domain of API Server
     */
    const API_SERVER = 'https://api.sukienhay.com/';

    /**
     * VERSION: Version of API
     */
    const VERSION = 'v2/';

    /**
     * @static Get current instance
     * @param null
     *
     * @return Object \Skh\Client (this)
     */
    public static function getInstance()
    {
        if(is_null(static::$instance)) {
            static::$instance = new self(static::$config["public_key"], static::$config["secret_key"], static::$config["type"]);
        }

        return static::$instance;
    }

    /**
     * @static Add config
     * @param Array $config
     *
     * @return static::$config
     */
    public static function config($config)
    {
        foreach($config as $k => $v) {
            static::$config[$k] = $v;
        }

        return static::$config;
    }

    /**
     * Construct function
     * @param String $publicKey
     * @param String $secretKey
     *
     * When new Client() store PublicKey, Encrypt SecretKey, store serverName
     * Init Request Object (use for send request), Token Object (use for encrypt client data)
     * If have Cookie:
     *     + Can't decrypt (changed private key): Get Access Token from API Server check
     *       public key and secret key
     *     + Can decrypt: store AccessToken
     */
    public function __construct($publicKey, $secretKey, $type)
    {
        $this->check = true;
        $this->publicKey = $publicKey;
        $this->secretKey = md5($secretKey.$publicKey);

        $this->serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

        $this->request = new CurlRequest();

        $this->token = new Token($this->secretKey);

        $this->cookie = isset($_COOKIE["SKH_API_COOKIE"]) ? $_COOKIE["SKH_API_COOKIE"] : "";

		if($this->cookie) {
        	$cookie = $this->token->decrypt($this->cookie);
		} else {
			$cookie = null;
		}

		// auto renew before expired day three days
        if($cookie == null || (($cookie->ei - 259200) <= time())) {
			// set new $this->cookie
            $this->extendToken();

			// Decrypt cookie
            $cookie = $this->token->decrypt($this->cookie);
        } else {
            // If not renew, check public key && secret key
            $this->check();
        }

        $this->accessToken = isset($cookie->token) ? $cookie->token : "";

    }

    public function decode($token)
    {
        $decrypted = $this->token->decrypt($token);
        return $decrypted;
    }

    public function getCheckKey()
    {
        return $this->check;
    }

    public function check()
    {
        try {
            $this->postCheckKey();
        } catch (\Exception $e) {
            $this->check = false;
            // echo "Caught exception: ". $e->getMessage() . "\n";
        }
    }

    public function postCheckKey()
    {
        $response = $this->post('token/check', [
            'verify_token'  =>  $this->getVerifyApplicationToken($this->publicKey, $this->secretKey, $this->cookie),
            'public_key'    =>  $this->publicKey
        ]);

        $res = json_decode($response);

        if(isset($res->success) && $res && $res->success === true) {
            return $res;
        } else {
            throw new \Exception("Check Public key && Secret key again");
        }

        return false;
    }

    /**
     * Get Access Token from API Server
     *
     * @param null
     *
     * @return Set Cookie
     */
    public function getAccessToken()
    {
        $response = $this->post('token/generate', [
            'verify_token'  =>  $this->getVerifyApplicationToken($this->publicKey, $this->secretKey, $this->cookie),
            'public_key'    =>  $this->publicKey
        ]);

        $res = json_decode($response);

        if(isset($res->success) && $res && $res->success === true && isset($res->access_token) && $res->access_token) {
            return $res;
        } else {
            throw new \Exception("Check Public key && Secret key again");
        }

        return false;
    }

	/**
	 * Renew token with new expired time
	 */
	public function extendToken()
	{
		try {
			$res = $this->getAccessToken();
		} catch (\Exception $e) {
            $this->check = false;
			// echo "Caught exception: ". $e->getMessage() . "\n";
		}

        if($res) {
    		// set $this->accessToken from new access token received from server
    		$this->accessToken = $res->access_token;

    		// set $this->cookie with token, ei, eid
            $this->setCookie([
                "token" => $this->accessToken,
                "ei"    => $res->ei,
                "eid"   => $res->expire_in
            ], $res->ei);

    		return $this->cookie;
        }

        return false;
	}

    /**
     * Have Cookie
     *
     * @return boolean
     */
    public function haveCookie()
    {
        if (isset($this->cookie) && $this->cookie) {
            $cookie = $this->token->decrypt($this->cookie);

            if(!$cookie || $cookie->ei <= time()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Set Cookie. Store new access token and expire time
     * @param Array $data
     * @param Integer $time
     *
     * @return void
     */
    public function setCookie($data, $time, $path = '/', $test = false)
    {

        $this->cookie = $this->token->encrypt($data);

        $domain = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "";

        if($domain) {
            if(static::$config["share_domain"] == true) {
                setcookie('SKH_API_COOKIE', null, -1, $path, "", static::$config['secure_cookie'], static::$config['http_only']);
                setcookie("SKH_API_COOKIE", $this->cookie, $time, $path, $this->getDomain($domain), static::$config['secure_cookie'], static::$config['http_only']);
            } else {
                setcookie("SKH_API_COOKIE", $this->cookie, $time, $path, "",static::$config['secure_cookie'], static::$config['http_only']);
            }
        } else {
            setcookie("SKH_API_COOKIE", $this->cookie, $time, $path, "", static::$config['secure_cookie'], static::$config['http_only']);
        }

        return true;
    }

    /**
     * Get current access token
     *
     * @return String $this->accessToken
     */
    public function token()
    {
        return ($this->accessToken) ? $this->accessToken : "";
    }

    /**
     * Send a HTTP GET Request
     *
     * @param String $url
     * @param Array $params
     *
     * @return JSON $res
     */
    public function get($url, $params = [])
    {
        $accessToken = $this->accessToken;

        $response = $this->request->request('GET', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        $this->checkAccessTokenAndSetCookie($response);

        // return json
        return $response;
    }

    /**
     * Send a HTTP POST Request
     *
     * @param String $url
     * @param Array $params
     *
     * @return JSON $res
     */
    public function post($url, $params = [])
    {
        $accessToken = $this->accessToken;

        $response = $this->request->request('POST', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        $this->checkAccessTokenAndSetCookie($response);      

        // return json
        return $response;
    }

    /**
     * Send a HTTP PUT Request
     *
     * @param String $url
     * @param Array $params
     *
     * @return JSON $res
     */
    public function put($url, $params = [])
    {
        $accessToken = $this->accessToken;
        $response = $this->request->request('PUT', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        $this->checkAccessTokenAndSetCookie();

        return $response;
    }

    /**
     * Send a HTTP DELETE Request
     *
     * @param String $url
     * @param Array $params
     *
     * @return JSON $res
     */
    public function delete($url, $params = [])
    {
        $accessToken = $this->accessToken;

        $response = $this->request->request('DELETE', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        $this->checkAccessTokenAndSetCookie();

        return $response;
    }

    /**
     * Check if access token return and set cookie 
     *
     * @param Json $response | Response from request
     *
     * @return void setCookie() or extendToken()
     */
    private function checkAccessTokenAndSetCookie($response)
    {
        $res = json_decode($response);

        if (isset($res->access_token) && $res->access_token) {

            $this->accessToken = $res->access_token;

            $data = [
                "token" => $this->accessToken,
                "ei"    => $res->ei,
                "eid"   => $res->expire_in
            ];

            $this->setCookie($data, $res->ei, '/');
        } else if(isset($res->errors->need_get_access_token) && $res->errors->need_get_access_token == 1) {
            $this->extendToken();
        }
    }

    /**
     * Generate a token to verify application
     *
     * @param String $publicKey
     * @param String $secretKey
     * @param String $cookie
     *
     * @return String Token $data
     */
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

    /**
     * Get primary domain from domain
     * Ex: test.sukienhay.com => .sukienhay.com
     *   : sukienhay.com      => .sukienhay.com
     *   : abc.xyz.io         => .xyz.io
     *
     * @param String $url
     * @param Array $params
     *
     * @return JSON $res
     */
    private function getDomain($url)
    {
        $urlArray = explode('.',$url);

        if(count($urlArray) >= 3) {
            return '.'.$urlArray[1].'.'.$urlArray[2];
        }

        return ".".$url;
    }
}