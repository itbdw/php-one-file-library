<?php
/**
 * Created by PhpStorm.
 * User: zhaobinyan
 * Date: 2018/7/23
 * Time: 上午10:56
 */

namespace App\Libraries;

/**
 * 自动将计算结果转为字符串，避免一系列float精度问题
 *
 * Class PSMath
 * @package App\Libraries
 */
class PSMath
{
    private static $scale = 2;//保留小数点后两位

    /**
     * 设置精度
     *
     * @param $scale
     */
    public static function setScale($scale) {
        self::$scale = $scale;
    }

    /**
     * 舍去法计算
     *
     * @param $a
     * @param $symbol
     * @param $b
     * @param null $scale
     * @return null|string
     * @throws \Exception
     */
    public static function calculate($a, $symbol, $b, $scale=null) {
        $res = null;

        if ($scale === null) {
            $scale = self::$scale;
        }

        switch ($symbol) {
            case "+":
                $res = bcadd($a, $b, $scale);
                break;
            case "-":
                $res = bcsub($a, $b, $scale);
                break;
            case "*":
                $res = bcmul($a, $b, $scale);

                //乘法疑似有bug，不一定够位数，需要补零
                $padcount = $scale + (strpos($res, '.') + 1);
                $res = str_pad($res, $padcount, '0', STR_PAD_RIGHT);

                break;
            case "/":
                $res = bcdiv($a, $b, $scale);
                break;
            case "%":
                $res = bcmod($a, $b);
                break;
            default:
                throw new \Exception("Unsupported calculator " . $symbol);

        }

        return $res;
    }

    /**
     * 格式化为分
     *
     * @param $money_yuan
     * @return null|string
     */
    public static function formatCNYFen($money_yuan) {
        return self::calculate($money_yuan, "*", 100, 0);
    }

    /**
     * 格式化为元
     *
     * @param $money_yuan
     * @return null|string
     */
    public static function formatCNYYuan($money_yuan) {
        return self::calculate($money_yuan, "-", 0, 2);
    }

    /**
     * 四舍五入
     *
     * @param $money_yuan
     * @param null $scale
     * @return null|string
     */
    public static function round($money_yuan, $scale=null) {
        if ($scale === null) {
            $scale = self::$scale;
        }
        $money_yuan = round($money_yuan, $scale);
        return self::calculate($money_yuan, "-", 0, $scale);
    }

    /**
     * 舍去
     *
     * @param $money_yuan
     * @param null $scale
     * @return null|string
     */
    public static function floor($money_yuan, $scale=null) {
        if ($scale === null) {
            $scale = self::$scale;
        }
        return self::calculate($money_yuan, "-", 0, $scale);
    }
}