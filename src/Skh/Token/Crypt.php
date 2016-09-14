<?php

namespace Skh\Token;

class Crypt
{
    private $privateKey;

    private $iv;

    public function __construct($privateKey = '')
    {
        $this->privateKey = md5($privateKey);
        $this->iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND);
    }

    public function encrypt($data)
    {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->privateKey, $data, MCRYPT_MODE_ECB, $this->iv));
    }

    public function decrypt($data)
    {
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->privateKey, base64_decode($data), MCRYPT_MODE_ECB, $this->iv));
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