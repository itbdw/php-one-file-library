<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 2017/9/12
 * Time: 下午9:05
 */

require dirname(__DIR__) .DIRECTORY_SEPARATOR. "App/Libraries/RssMaker.php";

$rss = \App\Libraries\RssMaker::getInstance();


$rss->addElement(["a"=>"b"]);

echo $rss->getDocument();