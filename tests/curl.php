<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 2017/9/12
 * Time: 下午9:03
 */

require dirname(__DIR__) .DIRECTORY_SEPARATOR. "App/Libraries/Curl.php";

\App\Libraries\Curl::get("http://www.baidu.com");

var_dump(\App\Libraries\Curl::$http_code, \App\Libraries\Curl::$error_code, \App\Libraries\Curl::$error_msg);