<?php
/**
 *
 * RSS 输出工具
 *
 * 需要安装 PHP XMLWriter 扩展
 *
 * @link https://github.com/itbdw/php-one-file-library
 */
namespace App\Libraries;

/**
 * Class RssMaker
 *
 * @usage see showUsage() method.
 *
 * @package App\Libraries
 */
class RssMaker
{

    private final function __construct()
    {
    }

    private final function __clone()
    {
    }

    /**
     * @var \XMLWriter
     */
    private $instance = null;

    /**
     * @return RssMaker
     */
    public static function getNewInstance() {
        $obj = new self;
        $obj->instance = new \XMLWriter();

        $obj->instance->openMemory();
        $obj->instance->setIndent(true);
        $obj->instance->setIndentString('    ');
        $obj->instance->startDocument('1.0', 'UTF-8');
        $obj->instance->startElement('rss');//rss
            $obj->instance->writeAttribute("version", "2.0");
        $obj->instance->startElement('channel');//channel
        return $obj;
    }

    /**
     * @param $config
     * @param array $use_cdata_keys
     */
    public function setBasicInformation($config, $use_cdata_keys=[]) {
        foreach ($config as $k=>$v) {
            $this->instance->startElement($k);
                if (in_array($k, $use_cdata_keys)) {
                    $this->instance->writeCData($v);
                } else {
                    $this->instance->text($v);
                }
            $this->instance->endElement();
        }
    }

    /**
     * @param $config
     * @param array $use_cdata_keys
     */
    public function addElement($config, $use_cdata_keys=[]) {
        $this->instance->startElement('item');
        foreach ($config as $k=>$v) {
            $this->instance->startElement($k);
            if (in_array($k, $use_cdata_keys)) {
                $this->instance->writeCData($v);
            } else {
                $this->instance->text($v);
            }
            $this->instance->endElement();
        }
        $this->instance->endElement();
    }

    /**
     * @return string
     */
    public function getDocument() {
        $this->instance->endElement();//channel
        $this->instance->endElement();//rss
        $this->instance->endDocument();
        return $this->instance->outputMemory();
    }

    /**
     *
     * Just a usage.
     *
     * @return string
     */
    public function showUsage() {
        $doc = <<<'DOC'
        $rss = RssMaker::getNewInstance();

        $rss->setBasicInformation([
            'title'=>'hello world',
            'link'=>'https://example.com',
            'description'=>'good morning',
            'language'=>'zh-cn',

            'pubDate'=> gmdate('D, d M Y H:i:s T'),
            'lastBuildDate'=> gmdate('D, d M Y H:i:s T'),

            'ttl'=> '30',

            'docs'=> '',
            'generator'=> 'GGGGG',

            'managingEditor'=> 'webmaster@example.com',
            'webMaster'=> 'webmaster@example.com',
        ]);

        $rss->addElement([
            'title'=>'测试数据',
            'link'=>'https://example.com/item/1',
            'description'=>'这是示例样本啦',

            'author'=>'我啊',
            'pubDate'=>gmdate('D, d M Y H:i:s T'),
            'guid' => 'https://example.com/item/1',
            'comments' => 'https://example.com/item/1/comments',
        ], [
            'title', 'description', 'author'
            ]
        );

        $d = $rss->getDocument();
DOC;
        return $doc;
    }

}
