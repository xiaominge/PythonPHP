<?php
class image
{
    public $info, $img, $w_quality;

    public $fitler = array(
        '<?php'=>'3C3F706870',
        '<?PHP'=>'3C3F504850',
        '<?PHp'=>'3C3F504870',
        '<?Php'=>'3C3F506870',
        '<?pHP'=>'3C3F704850',
        '<?phP'=>'3C3F706850',
        '<SCRIPT'=>'3C534352495054',
        '/SCRIPT>'=>'2F5343524950543E',
        '<script'=>'3C736372697074',
        '/script>'=>'2F7363726970743E',
    );

    // 十六进制检测图片是否挂马
    function checkhex($file)
    {
        $resource = fopen($file, 'rb');
        $filesize = filesize($file);
        fseek($resource, 0);
        // 取头和尾 或 取全部
        if($filesize > 512)
        {
            $hexcode = bin2hex(fread($resource, 512));
            fseek($resource, $filesize - 512);
            $hexcode .= bin2hex(fread($resource, 512));
        }
        else
        {
            $hexcode = bin2hex(fread($resource, $filesize));
        }
        fclose($resource);
        // 匹配脚本
        $fitler = '('.implode(')|(', $this->fitler).')';
        $pattern = "/$fitler/is";
        if(preg_match($pattern, $hexcode))
        {
            $status = false;
        } else {
            $status = true;
        }
        return $status;
    }

    function resave($image, $quality = 90)
    {
        if(!$this->check($image)) return false;
        $this->load_file($image);
        if($this->info === false) return false;

        $type = strtolower($this->get_type());
        if($quality) $this->w_quality = $quality;
        $width = $this->get_width();
        $height = $this->get_height(); 
        // 创建一个透明背景的图像
        $newimg = $this->create_alpha_image($width, $height);
        // 将原始重新采样复制到透明背景上
        imagecopyresampled($newimg, $this->img, 0, 0, 0, 0, $width, $height, $width, $height);
        imagedestroy($this->img);
        $this->img = $newimg;
        $this->info['width'] = $width;
        $this->info['height'] = $height;

        $this->save($image);
        return $image;
    }

    function load_file($file)
    {
        if(!file_exists($file)) return false;
        $string = file_get_contents($file);

        $imageinfo = getimagesize($file);
        if($imageinfo === false) return false;
        $imagetype = strtolower(substr(image_type_to_extension($imageinfo[2]), 1));
        $imagesize = filesize($file);
        $this->info = array('file' => $file,
            'width' => $imageinfo[0],
            'height' => $imageinfo[1],
            'type' => $imagetype,
            'size' => $imagesize,
            'mime' => $imageinfo['mime']
            );
        $this->img = imagecreatefromstring($string);
        return $this;
    }

    function check($image)
    {
        return extension_loaded('gd') && preg_match("/\.(jpg|jpeg|gif|png)/i", $image, $m) && file_exists($image) && function_exists('imagecreatefrom'.($m[1]=='jpg' ? 'jpeg' : $m[1]));
    }

    function get_type()
    {
        if(isset($this->info['type']))
        {
            return $this->info['type'];
        } 
        return false;
    }
    
    function get_width()
    {
        if(isset($this->info['width']))
        {
            return $this->info['width'];
        } 
        return false;
    }

    function get_height()
    {
        if(isset($this->info['height']))
        {
            return $this->info['height'];
        } 
        return false;
    }

    /**
    * 创建一个透明图片,用于图像复制
    * 
    * @param int $width 宽度
    * @param int $height 高度
    */
    function create_alpha_image($width, $height)
    {
        $newimg = imagecreatetruecolor($width, $height);
        if($this->get_type() == 'gif')
        {
            $color_count = imagecolorstotal($this->img);
            imagetruecolortopalette($newimg, true, $color_count);
            imagepalettecopy($newimg, $this->img);
            $transparentcolor = imagecolortransparent($this->img);
            imagefill($newimg, 0, 0, $transparentcolor);
            imagecolortransparent($newimg, $transparentcolor);
        }
        elseif($this->get_type() == 'png')
        {
            imagealphablending($newimg, false);
            $col = imagecolorallocatealpha($newimg, 255, 255, 255, 127);
            imagefilledrectangle($newimg, 0, 0, $width, $height, $col);
            imagealphablending($newimg, true);
        } 
        return $newimg;
    }

    /**
    * 保存到文件
    * 
    * @param string $path 文件的绝对路径
    */
    function save($path)
    {
        return $this->_output($path);
    }

    function _output($path, $type = null)
    {
        $tofile = false; 
        // 输出到文件
        if($path != 'stream')
        {
            if(!is_dir(dirname($path))) return false;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $tofile = true;
        } 
        // png的alpha校正
        $this->_pngalpha($type);

        if($type == "jpg") $type = "jpeg";
        $func = "image".$type;
        if(!function_exists($func))
        {
            $type = 'gif';
            $func = 'imagegif';
        } 
        if($tofile)
        {
            $type=='png' ? call_user_func($func, $this->img, $path) : call_user_func($func, $this->img, $path, $this->w_quality);
        }
        else
        {
            if(!headers_sent()) header("Content-type:image/".$type);
            call_user_func($func, $this->img);
        }
        return $this;
    }

    // png的alpha校正
    function _pngalpha($format)
    { 
        // PNG图像要保持alpha通道
        if($format == 'png')
        {
            imagealphablending($this->img, false);
            imagesavealpha($this->img, true);
        }
    }
}

$i = 0;
$file = 'tangweitrojans.jpeg';
$image = new image();
while(!$image->checkhex($file))
{
    $image->resave($file);
    $i++;
}
echo $i;