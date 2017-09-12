<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 16/7/5
 * Time: 上午11:22
 */

namespace App\Libraries;

/**
 * Class Curl
 * @package App\Libraries
 */
class Curl
{
    public static $ua = null;

    public static $http_code = null;
    public static $error_code = null;
    public static $error_msg = null;

    /**
     * @param $url
     * @param $curl_params
     * @return mixed
     */
    public static function get($url, $curl_params = [])
    {
        $ch = curl_init($url);

        $result_params = self::setDefaults();

        $result_params = [

            ] + $result_params;

        $proxy_params = self::getProxyParam($url);
        if ($proxy_params) {
            $result_params = $result_params + $proxy_params;
        }
        $result_params = $curl_params + $result_params;

        curl_setopt_array($ch, $result_params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

    public static function setDefaults()
    {
        return [
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => self::uniqueRandomUA(),
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.8',
                'Cache-Control: no-cache',
            ],
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_ENCODING => 'gzip,deflate',
        ];

    }

    /**
     * @return mixed|null
     */
    public static function uniqueRandomUA()
    {
        if (self::$ua === null) {
            self::$ua = self::randomUA();
        }
        return self::$ua;
    }

    /**
     * @return mixed
     */
    public static function randomUA()
    {
        $uas = [];
        $rand_num = 10;
        for ($i = 0; $i <= $rand_num; $i++) {

            $version = mt_rand(58, 60);
            $version .= '.' . mt_rand(0, 10);
            $version .= '.' . mt_rand(10, 60);
            $version .= '.' . mt_rand(10, 60);

            $uas[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . $version . ' Safari/537.36';
        }

        return $uas[mt_rand(0, count($uas) - 1)];
    }

    /**
     * @param $url
     * @return array
     */
    public static function getProxyParam($url)
    {
        $param = [];
        if (self::shouldUseProxy($url)) {
            $param[CURLOPT_PROXY] = "socks5://127.0.0.1:1080";
        }

        return $param;
    }

    /**
     * @param $url
     * @return bool
     */
    public static function shouldUseProxy($url)
    {

        $flag = false;

        if (strpos($url, 'steamstatic.com') !== false) {
            $flag = true;
        }

        if (strpos($url, 'akamaihd.net') !== false) {
            $flag = true;
        }

        if (strpos($url, 'steampowered.com') !== false) {
            $flag = true;
        }

        if (strpos($url, 'steamcommunity.com') !== false) {
            $flag = true;
        }

        if (strpos($url, 'google.com') !== false) {
            $flag = true;
        }

        return $flag;
    }

    /**
     * @param $url
     * @param $ch
     */
    public static function logResponse($url, $ch)
    {
        self::$error_code = curl_errno($ch);
        self::$error_msg = curl_error($ch);
        self::$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (self::$error_code) {
            self::error($url, self::$error_code . ' ' . self::$error_msg);
        }

        //strpos 只对字符串有效
        if (strpos((string)self::$http_code, '20') !== 0) {
            self::error($url, 'http code is not 20x, got ' . self::$http_code . ' instead');
        }
    }

    /**
     * @param $url
     * @param $em
     * @param array $ext
     */
    public static function error($url, $em, $ext = array())
    {
        error_log("Curl Library Error Found! $em $url " . json_encode($ext));
    }

    /**
     * @param $url
     * @param $data
     * @param $curl_params
     * @return mixed
     */
    public static function post($url, $data, $curl_params = [])
    {
        $ch = curl_init($url);

        $result_params = self::setDefaults();

        $result_params = [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data
            ] + $result_params;

        $proxy_params = self::getProxyParam($url);
        if ($proxy_params) {
            $result_params = $result_params + $proxy_params;
        }
        $result_params = $curl_params + $result_params;

        curl_setopt_array($ch, $result_params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

    /**
     * @param $url
     * @param array $curl_params
     * @return array
     */
    public static function getHeadersBeautyLowerCase($url, $curl_params = [])
    {
        $headers = Curl::getHeadersBeauty($url, $curl_params);
        foreach ($headers as $k => $v) {
            unset($headers[$k]);
            $headers[strtolower($k)] = $v;
        }

        return $headers;
    }

    /**
     * @param $url
     * @param array $curl_params
     * @return array
     *
     * {
     * Http-Header: "HTTP/1.1 200 OK",
     * Date: "Tue, 05 Jul 2016 04",
     * Server: "nginx",
     * Content-Type: "image/gif",
     * Content-Length: "14437898",
     * Accept-Ranges: "bytes",
     * Access-Control-Allow-Origin: "*",
     * Access-Control-Expose-Headers: "X-Log, X-Reqid",
     * Access-Control-Max-Age: "2592000",
     * Cache-Control: "public, max-age=7200",
     * Content-Disposition: "inline; filename="ff59817bf2a5bbb678d71a57486e90da.gif"",
     * Content-Transfer-Encoding: "binary",
     * ETag: ""loZkShTothYHyLM8VmVVy_Bxari5"",
     * Last-Modified: "Mon, 04 Jul 2016 10",
     * X-Log: "mc.g;IO",
     * X-Reqid: "exMAAM5VC8bSEV4U",
     * X-Qiniu-Zone: "0",
     * X-Via: "1.1 zhouwangtong155",
     * Connection: "keep-alive"
     * }
     */
    public static function getHeadersBeauty($url, $curl_params = [])
    {
        $return = Curl::getHeaders($url, $curl_params);
        $return = explode("\r\n", $return);

        $scheme = array_shift($return);
        array_unshift($return, 'Http-Header:' . $scheme);
        $return = array_filter($return);

        $headers = [];
        foreach ($return as $r) {
            list($k, $v) = explode(":", $r);
            $headers[trim($k)] = trim($v);
        }

        if (!isset($headers['Http-Header']) || !$headers['Http-Header']) {
            $headers['Http-Header'] = 'CUSTOEM-ERROR TIME OUT';
        }

        return $headers;
    }

    /**
     * @param $url
     * @param $curl_params
     * @return mixed
     *
     * HTTP/1.1 200 OK
     * Date: Tue, 05 Jul 2016 04:04:48 GMT
     * Server: nginx
     * Content-Type: image/gif
     * Content-Length: 14437898
     * Accept-Ranges: bytes
     * Access-Control-Allow-Origin: *
     * Access-Control-Expose-Headers: X-Log, X-Reqid
     * Access-Control-Max-Age: 2592000
     * Cache-Control: public, max-age=7200
     * Content-Disposition: inline; filename="ff59817bf2a5bbb678d71a57486e90da.gif"
     * Content-Transfer-Encoding: binary
     * ETag: "loZkShTothYHyLM8VmVVy_Bxari5"
     * Last-Modified: Mon, 04 Jul 2016 10:34:16 GMT
     * X-Log: mc.g;IO:1
     * X-Reqid: Z1EAALPQ5y5bEV4U
     * X-Qiniu-Zone: 0
     * X-Via: 1.1 chengwtong85:8105 (Cdn Cache Server V2.0), 1.1 lianwangtong61:4 (Cdn Cache Server V2.0)
     * Connection: keep-alive
     * {此处有一个 \r\n}
     * {此处有一个 \r\n}
     */
    public static function getHeaders($url, $curl_params = [])
    {
        $ch = curl_init($url);

        $result_params = self::setDefaults();

        $result_params = [
                CURLOPT_HEADER => true,
                CURLOPT_NOBODY => true
            ] + $result_params;

        $proxy_params = self::getProxyParam($url);
        if ($proxy_params) {
            $result_params = $result_params + $proxy_params;
        }
        $result_params = $curl_params + $result_params;

        curl_setopt_array($ch, $result_params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

}
