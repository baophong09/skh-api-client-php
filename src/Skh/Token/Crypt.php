<?php

namespace Skh\Token;

use \Baophong09\JWT\JWT as JWT;

class Crypt
{
    /**
     * Store private key to decryot
     * 
     * @var String $privateKey
     */
    private $privateKey;

    public function __construct($privateKey = '')
    {
        $this->privateKey = $privateKey;
    }

    public function encrypt($data)
    {
        return JWT::encode($data, $this->privateKey);
    }

    public function decrypt($data)
    {
        return JWT::decode($data, $this->privateKey, array('HS256'));
    }

    /**
     * Generate a random string
     * @param  int $length
     */
    public function generateRandomString($length = 32)
    {
        $salt = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $len = strlen($salt);
        $makepass = '';

        $stat = '';
        try{
            $stat = stat(__FILE__);
        }catch(\Exception $e){}

        if(!is_array($stat)){
            $stat = array(php_uname());
        }

        mt_srand($this->crc32(microtime() . implode('|', $stat)));

        for($i = 0; $i < $length; $i ++){
            $makepass .= $salt[mt_rand(0, $len - 1)];
        }

        return $makepass;
    }

    /**
     * fix crc32
     * @param  string $str
     */
    public function crc32($str){
        return sprintf('%u', crc32($str));
    }
}