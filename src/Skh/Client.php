<?php

namespace Skh;

use \Skh\Request\CurlRequest as CurlRequest;
use \Skh\Request\GuzzleRequest as GuzzleRequest;
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
    const VERSION = 'v1/';

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
        $this->publicKey = $publicKey;
        $this->secretKey = md5($secretKey.$publicKey);

        $this->serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

        if ($type == "stream") {
            $this->request = new GuzzleRequest();
        } elseif ($type == "curl") {
            $this->request = new CurlRequest();
        }

        $this->token = new Token($this->secretKey);

        $this->cookie = isset($_COOKIE["SKH_API_COOKIE"]) ? $_COOKIE["SKH_API_COOKIE"] : "";

        if($this->cookie) {
            $cookie = $this->token->decrypt($this->cookie);
            if(!$cookie || $cookie->ei <= time()) {

                // renew cookie
                try {
                    $this->getAccessToken();
                } catch (\Exception $e) {
                    echo 'Caught exception: '. $e->getMessage() . "\n";
                }

                $cookie = $this->token->decrypt($this->cookie);
            }

            $this->accessToken = isset($cookie->token) ? $cookie->token : "";
        }
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
            $this->accessToken = $res->access_token;

            $this->setCookie([
                "token" => $this->accessToken,
                "ei"    => $res->ei,
                "eid"   => $res->expire_in
            ], $res->ei);

        } else {
            throw new \Exception("Check Public key && Secret key again");
        }

        return $res;
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
    public function setCookie($data, $time, $path = '/')
    {
        $this->cookie = $this->token->encrypt($data);

        $domain = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "";
        
        if($domain) {
            if(static::$config["share_domain"] == true) {
                setcookie("SKH_API_COOKIE", $this->cookie, $time, $path, $this->getDomain($domain));
            } else {
                setcookie("SKH_API_COOKIE", $this->cookie, $time, $path, $domain);
            }
        } else {
            setcookie("SKH_API_COOKIE", $this->cookie, $time, $path);
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

        $res = json_decode($response);

        if (isset($res->access_token) && $res->access_token) {
            $this->accessToken = $res->access_token;

            $data = [
                "token" => $this->accessToken,
                "ei"    => $res->ei,
                "eid"   => $res->expire_in
            ];

            $this->setCookie($data, $res->ei);
        } else if(isset($res->errors->need_get_access_token) && $res->errors->need_get_access_token == 1) {
            $this->getAccessToken();
        }

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
        // $params = json_encode($params);

        $accessToken = $this->accessToken;

        $response = $this->request->request('POST', self::API_SERVER . self::VERSION . $url, $params, $accessToken);

        $res = json_decode($response);

        if (isset($res->access_token) && $res->access_token) {

            $this->accessToken = $res->access_token;

            $data = [
                "token" => $this->accessToken,
                "ei"    => $res->ei,
                "eid"   => $res->expire_in
            ];

            $this->setCookie($data, $res->ei);
        } else if(isset($res->errors->need_get_access_token) && $res->errors->need_get_access_token == 1) {
            $this->getAccessToken();
        }

        return $response;
    }

    public function put($url, $params = [])
    {
        $accessToken = $this->accessToken;

    }

    public function delete($url, $params = [])
    {
        $accessToken = $this->accessToken;

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

    private function getDomain($url)
    {
        $urlArray = explode('.',$url);

        if(count($urlArray) >= 3) {
            return '.'.$urlArray[1].'.'.$urlArray[2];
        }

        return ".".$url;
    }
}