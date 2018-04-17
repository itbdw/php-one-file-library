<?php
namespace App\Libraries;
/**
 *
 * itbdw
 * @since 2017-09-11
 */

/**
 * 原作者找不到，修改自 http://www.jb51.net/article/49113.htm
 *
 * 图片处理服务类
 * 使用php扩展服务\Imagick实现
 * ImageMagick 官网地址 http:www.imagemagick.org/script/index.php
 *
 * @author weiguang3
 * @since 20140403
 */
class CowImageMagic
{
    /**
     * @var $image \Imagick
     */
    private $image = null;
    private $type = null;

    /**
     *
     */
    public function __destruct()
    {
        if ($this->image !== null) {
            $this->image->destroy();
        }
    }

    /**
     * @param $path
     * @return \Imagick
     */
    public function readFromFile($path)
    {
        $this->image = new \Imagick ($path);
        if ($this->image) {
            $this->type = strtolower($this->image->getImageFormat());
        }
        return $this->image;
    }

    /**
     * @param $binary
     * @return \Imagick
     */
    public function readFromBlob($binary)
    {
        $this->image = new \Imagick ();
        $this->image->readImageBlob($binary);
        if ($this->image) {
            $this->type = strtolower($this->image->getImageFormat());
        }
        return $this->image;
    }

    /**
     * 图片缩放（注意：是缩小和放大，尺寸可能会变大）
     *
     * 裁剪规则：
     *   1. 高度为零       按宽度缩放 高度自适应
     *   2. 宽度为零       按高度缩放 宽度自适应
     *   3. 宽度高度都为零  按宽高比例等比例缩放裁剪 默认从头部居中裁剪
     *
     * @param int $width
     * @param int $height
     */
    public function resize($width = 0, $height = 0)
    {
        if ($width == 0 && $height == 0) {
            return;
        }

        $color = '';// 'rgba(255,255,255,1)';
        $size = $this->image->getImagePage();
        //原始宽高
        $src_width = $size ['width'];
        $src_height = $size ['height'];

        //按宽度缩放 高度自适应
        if ($width != 0 && $height == 0) {
            if ($src_width > $width) {
                $height = intval($width * $src_height / $src_width);

                if ($this->type == 'gif') {
                    $this->resizeGif($width, $height);
                } else {
                    $this->image->thumbnailImage($width, $height, true);
                }
            }
            return;
        }
        //按高度缩放 宽度自适应
        if ($width == 0 && $height != 0) {
            if ($src_height > $height) {
                $width = intval($src_width * $height / $src_height);

                if ($this->type == 'gif') {
                    $this->resizeGif($width, $height);
                } else {
                    $this->image->thumbnailImage($width, $height,true);
                }
            }
            return;
        }

        //缩放的后的尺寸
        $crop_w = $width;
        $crop_h = $height;

        //缩放后裁剪的位置
        $crop_x = 0;
        $crop_y = 0;

        if (($src_width / $src_height) < ($width / $height)) {
            //宽高比例小于目标宽高比例  宽度等比例放大      按目标高度从头部截取
            $crop_h = intval($src_height * $width / $src_width);
            //从顶部裁剪  不用计算 $crop_y
        } else {
            //宽高比例大于目标宽高比例   高度等比例放大      按目标宽度居中裁剪
            $crop_w = intval($src_width * $height / $src_height);
            $crop_x = intval(($crop_w - $width) / 2);
        }

        if ($this->type == 'gif') {
            $this->resizeGif($crop_w, $crop_h, true, $width, $height, $crop_x, $crop_y);
        } else {
            $this->image->thumbnailImage($crop_w, $crop_h,true);
            $this->image->cropImage($width, $height, $crop_x, $crop_y);
        }
    }

    /**
     * 处理gif图片 需要对每一帧图片处理
     * @param int $t_w 缩放宽
     * @param int $t_h 缩放高
     * @param string $isCrop 是否裁剪
     * @param number $c_w 裁剪宽
     * @param number $c_h 裁剪高
     * @param number $c_x 裁剪坐标 x
     * @param number $c_y 裁剪坐标 y
     */
    private function resizeGif($t_w, $t_h, $isCrop = false, $c_w = 0, $c_h = 0, $c_x = 0, $c_y = 0)
    {
        $dest = new \Imagick();
        $color_transparent = new \ImagickPixel("transparent"); //透明色
        foreach ($this->image as $img) {
            $page = $img->getImagePage();
            $tmp = new \Imagick();
            $tmp->newImage($page['width'], $page['height'], $color_transparent, 'gif');
            $tmp->compositeImage($img, \Imagick::COMPOSITE_OVER, $page['x'], $page['y']);

            $tmp->thumbnailImage($t_w, $t_h,true);
            if ($isCrop) {
                $tmp->cropImage($c_w, $c_h, $c_x, $c_y);
            }

            $dest->addImage($tmp);
            $dest->setImagePage($tmp->getImageWidth(), $tmp->getImageHeight(), 0, 0);
            $dest->setImageDelay($img->getImageDelay());
            $dest->setImageDispose($img->getImageDispose());

        }
        $this->image->destroy();
        $this->image = $dest;
    }


    /**
     * 图片缩放（注意：是缩小和放大，尺寸可能会变大）
     *
     * 更改图像大小
     *
     *  $fit: 适应大小方式
     *
     *   'force':      把图片强制变形成 $width X $height 大小
     *   'scale':      按比例在安全框 $width X $height 内缩放图片, 输出缩放后图像大小 不完全等于 $width X $height
     *   'scale_fill': 按比例在安全框 $width X $height 内缩放图片，安全框内没有像素的地方填充色,
     *                      使用此参数时可设置背景填充色 $bg_color = array(255,255,255)(红,绿,蓝, 透明度)
     *                      透明度(0不透明-127完全透明)) 其它: 智能模能 缩放图像并载取图像的中间部分 $width X $height 像素大小
     *  $fit = 'force','scale','scale_fill' 时： 输出完整图像
     *
     *  $fit = 图像方位值 时, 输出指定位置部分图像 字母与图像的对应关系如下:
     *
     *   north_west north north_east
     *   west center east
     *   south_west south south_east
     *
     * @deprecated not recommended 使用前需要测试充分
     */
    public function resizeTo($width = 100, $height = 100, $fit = 'center', $fill_color = [255, 255, 255, 0])
    {
        switch ($fit) {
            case 'force' :
                if ($this->type == 'gif') {
                    $image = $this->image;
                    $canvas = new \Imagick ();

                    $images = $image->coalesceImages();
                    foreach ($images as $frame) {
                        $img = new \Imagick ();
                        $img->readImageBlob($frame);
                        $img->thumbnailImage($width, $height, false);

                        $canvas->addImage($img);
                        $canvas->setImageDelay($img->getImageDelay());
                    }
                    $image->destroy();
                    $this->image = $canvas;
                } else {
                    $this->image->thumbnailImage($width, $height, false);
                }
                break;
            case 'scale' :
                if ($this->type == 'gif') {
                    $image = $this->image;
                    $images = $image->coalesceImages();
                    $canvas = new \Imagick ();
                    foreach ($images as $frame) {
                        $img = new \Imagick ();
                        $img->readImageBlob($frame);
                        $img->thumbnailImage($width, $height,true);

                        $canvas->addImage($img);
                        $canvas->setImageDelay($img->getImageDelay());
                    }
                    $image->destroy();
                    $this->image = $canvas;
                } else {
                    $this->image->thumbnailImage($width, $height,true);
                }
                break;
            case 'scale_fill' :
                $size = $this->image->getImagePage();
                $src_width = $size ['width'];
                $src_height = $size ['height'];

                $x = 0;
                $y = 0;

                $dst_width = $width;
                $dst_height = $height;

                if ($src_width * $height > $src_height * $width) {
                    $dst_height = intval($width * $src_height / $src_width);
                    $y = intval(($height - $dst_height) / 2);
                } else {
                    $dst_width = intval($height * $src_width / $src_height);
                    $x = intval(($width - $dst_width) / 2);
                }

                $image = $this->image;
                $canvas = new \Imagick ();

                $color = 'rgba(' . $fill_color [0] . ',' . $fill_color [1] . ',' . $fill_color [2] . ',' . $fill_color [3] . ')';
                if ($this->type == 'gif') {
                    $images = $image->coalesceImages();
                    foreach ($images as $frame) {
                        $frame->thumbnailImage($width, $height,true);

                        $draw = new \ImagickDraw ();
                        $draw->composite($frame->getImageCompose(), $x, $y, $dst_width, $dst_height, $frame);

                        $img = new \Imagick ();
                        $img->newImage($width, $height, $color, 'gif');
                        $img->drawImage($draw);

                        $canvas->addImage($img);
                        $canvas->setImageDelay($img->getImageDelay());
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
                } else {
                    $image->thumbnailImage($width, $height,true);

                    $draw = new \ImagickDraw ();
                    $draw->composite($image->getImageCompose(), $x, $y, $dst_width, $dst_height, $image);

                    $canvas->newImage($width, $height, $color, $this->type);
                    $canvas->drawImage($draw);
                    $canvas->setImagePage($width, $height, 0, 0);
                }
                $image->destroy();
                $this->image = $canvas;
                break;
            default :
                $size = $this->image->getImagePage();
                $src_width = $size ['width'];
                $src_height = $size ['height'];

                $crop_x = 0;
                $crop_y = 0;

                $crop_w = $src_width;
                $crop_h = $src_height;

                if ($src_width * $height > $src_height * $width) {
                    $crop_w = intval($src_height * $width / $height);
                } else {
                    $crop_h = intval($src_width * $height / $width);
                }

                switch ($fit) {
                    case 'north_west' :
                        $crop_x = 0;
                        $crop_y = 0;
                        break;
                    case 'north' :
                        $crop_x = intval(($src_width - $crop_w) / 2);
                        $crop_y = 0;
                        break;
                    case 'north_east' :
                        $crop_x = $src_width - $crop_w;
                        $crop_y = 0;
                        break;
                    case 'west' :
                        $crop_x = 0;
                        $crop_y = intval(($src_height - $crop_h) / 2);
                        break;
                    case 'center' :
                        $crop_x = intval(($src_width - $crop_w) / 2);
                        $crop_y = intval(($src_height - $crop_h) / 2);
                        break;
                    case 'east' :
                        $crop_x = $src_width - $crop_w;
                        $crop_y = intval(($src_height - $crop_h) / 2);
                        break;
                    case 'south_west' :
                        $crop_x = 0;
                        $crop_y = $src_height - $crop_h;
                        break;
                    case 'south' :
                        $crop_x = intval(($src_width - $crop_w) / 2);
                        $crop_y = $src_height - $crop_h;
                        break;
                    case 'south_east' :
                        $crop_x = $src_width - $crop_w;
                        $crop_y = $src_height - $crop_h;
                        break;
                    default :
                        $crop_x = intval(($src_width - $crop_w) / 2);
                        $crop_y = intval(($src_height - $crop_h) / 2);
                }

                $image = $this->image;
                $canvas = new \Imagick ();

                if ($this->type == 'gif') {
                    $images = $image->coalesceImages();
                    foreach ($images as $frame) {
                        $img = new \Imagick ();
                        $img->readImageBlob($frame);
                        $img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                        $img->thumbnailImage($width, $height,true);

                        $canvas->addImage($img);
                        $canvas->setImageDelay($img->getImageDelay());
                        $canvas->setImagePage($width, $height, 0, 0);
                    }
                } else {
                    $image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                    $image->thumbnailImage($width, $height,true);
                    $canvas->addImage($image);
                    $canvas->setImagePage($width, $height, 0, 0);
                }
                $image->destroy();
                $this->image = $canvas;
        }
    }

    /**
     *
     * 使宽高限定在指定范围内，只有一边超过指定尺寸时才适应并居中剪裁。
     *
     * 类似 resizeTo() , $fit 为 center 的效果，但不会放大图片
     *
     * @param int $width
     * @param int $height
     */
    public function resizeAndCropIfLargeThen($width = 100, $height = 100)
    {
        $size = $this->image->getImagePage();
        $src_width = $size ['width'];
        $src_height = $size ['height'];

        if ($src_height > $height || $src_width > $width) {

        } else {
            return ;
        }

        $crop_w = $src_width;
        $crop_h = $src_height;

        if ($src_width * $height > $src_height * $width) {
            $crop_w = intval($src_height * $width / $height);
        } else {
            $crop_h = intval($src_width * $height / $width);
        }

        $crop_x = intval(($src_width - $crop_w) / 2);
        $crop_y = intval(($src_height - $crop_h) / 2);


        $image = $this->image;
        $canvas = new \Imagick ();

        if ($this->type == 'gif') {
            $img_count = $image->getNumberImages();
            //太耗费资源
            if ($img_count <= 15) {
                $images = $image->coalesceImages();
                foreach ($images as $frame) {
                    $img = new \Imagick ();
                    $img->readImageBlob($frame);
                    $img->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
                    $img->thumbnailImage($width, $height,true);

                    $canvas->addImage($img);
                    $canvas->setImageDelay($img->getImageDelay());
                    $canvas->setImagePage($width, $height, 0, 0);
                }

                $image->destroy();
                $this->image = $canvas;
            }
        } else {
            $image->cropImage($crop_w, $crop_h, $crop_x, $crop_y);
            $image->thumbnailImage($width, $height,true);
            $canvas->addImage($image);
            $canvas->setImagePage($width, $height, 0, 0);
            $image->destroy();

            $this->image = $canvas;
        }
    }

    /**
     * 使宽高限定在指定范围内，只有一边超过指定尺寸时才适应。
     *
     * 类似 resizeTo() , $fit 为 scale 的效果，但不会放大图片
     *
     * @param int $width
     * @param int $height
     */
    public function resizeNoCropIfLargeThen($width = 100, $height = 100)
    {
        $size = $this->image->getImagePage();
        $src_width = $size ['width'];
        $src_height = $size ['height'];

        if ($src_height > $height || $src_width > $width) {

        } else {
            return ;
        }

        if ($this->type == 'gif') {
            $image = $this->image;
            $images = $image->coalesceImages();
            $canvas = new \Imagick ();

            $img_count = $image->getNumberImages();
            //太耗费资源
            if ($img_count <= 15) {
                foreach ($images as $frame) {
                    $img = new \Imagick ();
                    $img->readImageBlob($frame);
                    $img->thumbnailImage($width, $height, true);

                    $canvas->addImage($img);
                    $canvas->setImageDelay($img->getImageDelay());
                }
                $image->destroy();
                $this->image = $canvas;
            }
        } else {
            $this->image->thumbnailImage($width, $height,true);
        }
    }

    /**
     * gif 图片不压缩
     * png 有透明则不压缩
     *
     * 否则转换为 jpg 并压缩
     *
     * @param float $q
     */
    public function setJpegCompressQualityAndMore($q) {

        $this->image->stripImage();

        if ($this->type == 'gif') {
            return;
        }

//        //png 开头。这样可以保持透明
//        if (strpos($this->type, 'png') === 0) {
//
//            $this->image->setImageCompression(\Imagick::COMPRESSION_UNDEFINED);
//
//            $colors = min(1600, $this->image->getImageColors());
//            $this->image->quantizeImage($colors, \Imagick::COLORSPACE_RGB, 0, false, false );
//            $this->image->setImageDepth(8 /* bits */);
//
//            return;
//        }


        //所有其它格式全部 转 jpg， png 的透明部分会变黑

        if (strpos($this->type, 'png') === 0) {

            $size = $this->image->getImagePage();
            $width = $size ['width'];
            $height = $size ['height'];

            $white=new \Imagick();
            $white->newImage($width, $height, "#2c2c41");
            $white->compositeImage($this->image, \Imagick::COMPOSITE_OVER, 0, 0);
            $white->setImageFormat('jpg');

            $this->image = $white;
        }

        $a = $this->image->getImageCompressionQuality();

        //原图无压缩，或者压缩较轻
        //原图压缩比当前大则不再处理，避免过分失真
        if ($a == 0 || $a > $q) {

            $a = $q;

            $this->type = 'jpg';

            $this->image->setImageFormat('JPEG');
            $this->image->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $this->image->setImageCompressionQuality($a);

            $this->image->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        }

    }

    /**
     * @return string
     */
    public function getBlob()
    {
        return $this->image->getImagesBlob();
    }

    /**
     *
     */
    public function getFirstBlob() {
        $image = $this->image;
        $images = $image->coalesceImages();
        foreach ($images as $frame) {
            $img = new \Imagick ();
            $img->readImageBlob($frame);
            $img->setImageFormat('JPEG');
            $img->setImageCompression(\Imagick::COMPRESSION_JPEG);

            $img->setInterlaceScheme(\Imagick::INTERLACE_PLANE);

            return $img->getImagesBlob();
        }
    }

    /**
     * @return null
     */
    public function getExtension() {
        return $this->type;
    }

}