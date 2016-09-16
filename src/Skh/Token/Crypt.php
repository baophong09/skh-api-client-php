<?php

namespace Skh\Token;

use \Firebase\JWT\JWT as JWT;

class Crypt
{
    private $privateKey;

    public function __construct($privateKey = '')
    {
        $this->privateKey = $privateKey;
    }

    public function encrypt($data)
    {
        return JWT::encode($data, $this->privateKey);
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