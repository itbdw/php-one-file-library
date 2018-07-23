<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 2018/7/23
 * Time: 下午12:34
 */

require dirname(__DIR__) .DIRECTORY_SEPARATOR. "App/Libraries/PSMath.php";

use \App\Libraries\PSMath;

/**
 * 对照函数，向下取整
 * @param $money
 */
function cmpFormatMoney($money) {
    return (string)(floor($money * 100) / 100);
}

var_dump("supposed 0.58");

var_dump(cmpFormatMoney(0.50+0.08));
var_dump(PSMath::calculate(0.50, "+", 0.08, 2));

var_dump(cmpFormatMoney('0.50'+'0.08'));
var_dump(PSMath::calculate('0.50', "+", '0.08', 2));

var_dump(cmpFormatMoney(0.58));
var_dump(PSMath::formatCNYYuan(0.58));

//exception
PSMath::calculate("2", ">", "3");
