<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 16/7/5
 * Time: 上午11:22
 */
namespace App\Libraries;

class Curl
{
    public static $ua = null;

    public static $http_code = null;
    public static $error_code = null;
    public static $error_msg = null;

    /**
     * @param $url
     * @param $params
     * @return mixed
     */
    public static function get($url, $params = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_USERAGENT, self::uniqueRandomUA());

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        curl_setopt_array($ch, $params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

    /**
     * @param $url
     * @param $data
     * @param $params
     * @return mixed
     */
    public static function post($url, $data, $params = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        curl_setopt($ch, CURLOPT_USERAGENT, self::uniqueRandomUA());

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

        curl_setopt_array($ch, $params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

    /**
     * @param $url
     * @param $params
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
    public static function getHeaders($url, $params = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_setopt($ch, CURLOPT_USERAGENT, self::uniqueRandomUA());
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept'=>'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Encoding'=>'gzip, deflate, sdch',
            'Accept-Language'=>'zh-CN,zh;q=0.8',
            'Cache-Control'=>'no-cache',
        ]);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        curl_setopt_array($ch, $params);

        $output = curl_exec($ch);

        self::logResponse($url, $ch);

        curl_close($ch);
        return $output;
    }

    /**
     * @param $url
     * @param array $params
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
    public static function getHeadersBeauty($url, $params = [])
    {
        $return = Curl::getHeaders($url, $params);
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
     * @param array $params
     * @return array
     */
    public static function getHeadersBeautyLowerCase($url, $params=[]) {
        $headers = Curl::getHeadersBeauty($url, $params);
        foreach ($headers as $k=>$v) {
            unset($headers[$k]);
            $headers[strtolower($k)] = $v;
        }

        return $headers;
    }

    //todo 可以在此改为你自己系统里的日志处理方式，也可以自己再写一个 Class 继承，再改写方法
    protected static function error($url, $em, $ext=array())
    {
        error_log("Curl Library Error Found! $em " . json_encode($ext));
    }

    //todo 可以在此直接新增或删除 UA，也可以自己再写一个 Class 继承，并改写该方法
    protected static function randomUA()
    {
        $uas = [
           'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
           'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36',
           'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36',
           'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36',
           'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2226.0 Safari/537.36',
           'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
           'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36'
        ];

        $rand_num = 10;
        for ($i=0; $i<= $rand_num; $i++) {

            $version = mt_rand(10, 60);
            $version .= '.'.mt_rand(0, 10);
            $version .= '.'.mt_rand(10, 60);
            $version .= '.'.mt_rand(10, 60);

            $uas[] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/'.$version.' Safari/537.36';
        }

        return $uas[mt_rand(0, count($uas) - 1)];
    }

    protected static function uniqueRandomUA() {
        if (self::$ua === null) {
            self::$ua = self::randomUA();
        }
        return self::$ua;
    }

    protected static function logResponse($url, $ch) {
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

}