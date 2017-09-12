<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 2017/9/12
 * Time: 下午8:55
 */

require dirname(__DIR__) .DIRECTORY_SEPARATOR. "App/Libraries/ImageMagic.php";

$imageMagic = new \App\Libraries\ImageMagic();


$imageMagic->readFromBlob("hello");
$imageMagic->output();