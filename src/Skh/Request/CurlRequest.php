<?php

namespace Skh\Request;

class CurlRequest implements RequestInterface
{
    public function request($type, $url, $params = [], $token = null)
    {
        $type = strtolower($type);

        return self::$type($url, $params, $token);
    }

    public static function execute($method = '', $url = '', $params = [], $token = null)
    {
        $curl = curl_init();

        switch($method) {
            case 'GET':
                $url .= '?'.http_build_query($params);
                break;

            case 'POST':
                curl_setopt($curl, CURLOPT_POST, TRUE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
                break;

            case 'PUT':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
                break;

            case 'DELETE':
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$token
        )); 
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public static function get($url = '', $params = [], $token = '')
    {
        return self::execute('GET', $url, $params, $token);
    }

    public static function post($url = '', $params = [], $token = '')
    {
        return self::execute('POST', $url, $params, $token);
    }

    public static function put($url = '', $params = [], $token = '')
    {
        return self::execute('PUT', $url, $params, $token);
    }

    public static function delete($url = '', $params = [], $token = '')
    {
        return self::execute('DELETE', $url, $params, $token);
    }
}