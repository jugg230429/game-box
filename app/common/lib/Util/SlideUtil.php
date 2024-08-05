<?php
//+++++++++++++++++++++++++++++++++++++++++++++++++++++
// [中|英]云天河——滑动验证码 Link: http://hlzblog.top/
//+++++++++++++++++++++++++++++++++++++++++++++++++++++
/** 示例
 * \Mine\Slide::instance(1); // String 获取验证码的html
 * \Mine\Slide::instance(2); // JsonString  验证失败|成功   => {["code"=>"状态码","info"=>"对应的错误信息"]}
 * \Mine\Slide::instance(3); // Array  表单提交时验证  => ["status"=> true|false,,"code"=>"状态码","info"=>"对应的错误信息"]
 */

namespace app\common\lib\Util;

// 解决nginx下没有函数名getallheaders的情况
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

class SlideUtil
{
    /**
     *  验证码配置
     */
    private static $session_name = '__Slide_Verify_x';  // 存，待验证的横坐标值的session
    private static $session_data = '__Slide_Verify__';  // 存，各种状态信息的session
    private static $post_x_name = 'h';  // POST变量：滑动的横坐标
    private static $times_max = 3; // 单个验证图片，最多可验证次数
    private static $timer = 60; // 验证码的时效，单位s
    /**
     * 语言包，中、英
     */
    private static $inner_info = [
        'zh' => [
            'slide_info' => ' 向右滑动填充拼图 ',
            'code' => [
                "200" => "验证码通过",
                "-201" => "图形验证码错误",
                "-202" => "验证参数 或者 容差值 不为整数",
                "-203" => "验证码超时了，请重新验证",
                "-204" => "您操作失误太多次了，请重新验证",
                "-205" => "验证码生成失败，服务器未安装GD库",
                "-206" => "没有找到生成验证码的初始图片",
                "-207" => "传入的参数有误",
                "-208" => "图形验证码失效"
            ]
        ],
        'en' => [
            'slide_info' => ' Slide the fill puzzle to the right',
            'code' => [
                "200" => "Verify code is right",
                "-201" => "Verify code is error!",
                "-202" => "Post data or fault-tolerant is not an int value",
                "-203" => "Verify timeout",
                "-204" => "You have made too many mistakes, please verify again",
                "-205" => "Cannot Initialize new GD image stream",
                "-206" => "Failed to find the initial image of the generated verification code ",
                "-207" => "Incoming parameters are incorrect ",
                "-208" => "Verify code Invalid "
            ]
        ],
    ]; // 原始图片库有多少张png[要求规格260x116]，从编号1开始
    private $source_pic_count = 5;   // 每次删除几个目录或内容，
    /**
     * 自动删除临时资源
     */
    private $del_count = 5;   // 开关：(Boolean)false关闭，true打开
    private $auto_del_tmp = true;
    /**
     * 系统配置
     */
    private $pic_info;
    private $dir;        // 截图宽
    private $tailoring_w;        // 截图高
    private $tailoring_h;        // 资源图片路径
    private $ResourcesPath;        // 小图片名称
    private $smallPicName;        // 大图图片名称
    private $bigPicName;            // 参照图片
    private $srcPic;        // 随机X位置
    private $location_x;        // 随机Y位置
    private $location_y;      // 随机图片的相关信息
    private $get_png_info;        // 生成的图片，后缀
    private $pic_suffix;   // 拼图保存路径
    private $tailoring_big_save_path; // 小截图保存路径
    private $tailoring_small_save_path;

    /**
     * 构造函数
     *
     * @return void
     */
    private function __construct()
    {
        $this -> init_core();
        $this -> init_variable();
        // 获取原图的 宽高
        $this -> pic_info = $this -> get_pic_wide_height($this -> srcPic);
        // 剪裁操作
        $this -> set_rand_loc($this -> pic_info);
        $this -> tailoring($this -> srcPic, $this -> smallPicName, $this -> location_x, $this -> location_y, $this -> tailoring_w, $this -> tailoring_h);
        $this -> merge_pic($this -> srcPic, $this -> smallPicName, $this -> bigPicName, $this -> location_x, $this -> location_y);
        $this -> restruct_cut();
    }

    /**
     * 配置的路径  这里相对于入口文件
     *
     * @return Void
     */
    private function init_core()
    {
        //  背景图片存放目录 （Resources）
        $this -> ResourcesPath = './static/verify/img/';
        //  被处理图片 存放目录，相对于入口文件（裁剪小图，错位背景图）
        $this -> dir = $this -> ResourcesPath . 'tmp/' . time();
    }

    /**
     * 初始化相关数据
     *
     * @return void
     */
    protected function init_variable()
    {
        $this -> tailoring_w = mt_rand(40, 40);
        $this -> tailoring_h = mt_rand(40, 40);
        $this -> tailoring_big_save_path = $this -> dir . '/big/';
        $this -> tailoring_small_save_path = $this -> dir . '/small/';
        $this -> pic_suffix = '.png';
        $this -> get_rand_png();
        $this -> smallPicName = $this -> tailoring_small_save_path . md5(uniqid()) . $this -> pic_suffix;
        $this -> bigPicName = $this -> tailoring_big_save_path . md5(uniqid() . time()) . $this -> pic_suffix;
        // 检查目录是否存在
        if (!file_exists($this -> tailoring_big_save_path)) {
            mkdir($this -> tailoring_big_save_path, 0777, true);
        }
        if (!file_exists($this -> tailoring_small_save_path)) {
            mkdir($this -> tailoring_small_save_path, 0777, true);
        }
        // 自动删除图片资源，默认关闭
        if ($this -> auto_del_tmp) {
            $get_tmp_dir = dirname($this -> dir);
            $this -> rec_del_dir($get_tmp_dir);
        }
    }

    /**
     * 随机获取背景图片
     *
     * @return String 返回图片资源地址
     */
    private function get_rand_png()
    {

        $this -> srcPic = $this -> ResourcesPath . $this -> bigPicName . mt_rand(1, $this -> source_pic_count) . '.png';
        if (!file_exists($this -> srcPic)) {
            exit('{"code":206}');
        } else {
            return $this -> srcPic;
        }

    }

    /**
     * 递归删除目录
     *
     * @return Void
     */
    protected function rec_del_dir($dir)
    {
        $count = $this -> del_count;
        $expire = self ::$timer;
        // 目录句柄
        $handler = dir($dir);
        // 执行
        for ($i = 0; $i < $count + 2; $i ++) {
            // 指针方式，该目录下所有的名称
            $name = $handler -> read();
            $path = $handler -> path . '/' . $name; //当前目录真实路径
            switch ($name) {
                // 结束当前层，递归
                case false:
                    return false;
                // 退出当前层，循环
                case '.'  :
                case '..' :
                    break;
                default   :
                    if (is_dir($path) && @rmdir($path) == false) {
                        if (!is_numeric($name)) {
                            $this -> rec_del_dir($path);
                        }// 计算时差，并查看该目录，是否过期
                        else if (time() - $name > $expire) {
                            $this -> rec_del_dir($path);
                        }
                    } else {
                        @unlink($path);
                    }
            }
        }
    }

    /**
     * 获取图片宽、高
     *
     * @param String : pic_path
     *
     * @return Array  : 结果集
     */
    protected function get_pic_wide_height($pic_path)
    {
        $lim_size = getimagesize($pic_path);
        return array('w' => $lim_size[0], 'h' => $lim_size[1]);
    }

    /**
     * 随机 X与Y位置 用于验证滑动值
     *
     * @param Array : pic_info
     *
     * @return Float
     */
    protected function set_rand_loc($pic_info)
    {
        $this -> location_x = mt_rand(30, $pic_info['w'] - $this -> tailoring_w);
        $this -> location_y = mt_rand(5, $pic_info['h'] - $this -> tailoring_h);
    }

    /**
     * 裁剪小图
     *
     * @param String : srcFile
     * @param String : picName
     * @param Int    : tailoring_x
     * @param Int    : tailoring_y
     * @param Int    : PicW
     * @param Int    : PicH
     *
     * @return Void
     */
    protected function tailoring($srcFile, $picName, $tailoring_x, $tailoring_y, $PicW, $PicH)
    {
        if ($this -> pic_suffix == '.webp') {
            $imgStream = file_get_contents($srcFile);
            $srcIm = imagecreatefromstring($imgStream);
        } else {
            $srcIm = @imagecreatefrompng($srcFile);
        }
        $dstIm = @imagecreatetruecolor($PicW, $PicH) or die('{"code":205}');
        $dstImBg = @imagecolorallocate($dstIm, 255, 255, 255);
        imagefill($dstIm, 0, 0, $dstImBg); //创建背景为白色的图片
        imagecopy($dstIm, $srcIm, 0, 0, $tailoring_x, $tailoring_y, $PicW, $PicH);
        //imagewebp($dstIm,$picName,100);
        imagepng($dstIm, $picName);
        imagedestroy($dstIm);
        imagedestroy($srcIm);
    }

    /**
     * 去色合并块状,指定位置
     *
     * @param String: srcFile
     * @param String: smallPicName
     * @param String: picName
     * @param Int   : tailoring_x
     * @param Int   : tailoring_y
     *
     * @return Void
     */
    protected function merge_pic($srcFile, $smallPicName, $picName, $tailoring_x, $tailoring_y)
    {
        $src_lim = imagecreatefrompng($srcFile);
        $lim_size = getimagesize($smallPicName); //取得水印图像尺寸，信息
        $border = imagecolorat($src_lim, 5, 5);
        $red = imagecolorallocate($src_lim, 0, 0, 0);
        imagefilltoborder($src_lim, 0, 0, $border, $red);
        $im_size = getimagesize($srcFile);
        $src_w = $im_size[0];
        $src_h = $im_size[1];
        $src_im = imagecreatefrompng($srcFile);
        $dst_im = imagecreatetruecolor($src_w, $src_h);
        //根据原图尺寸创建一个相同大小的真彩色位图
        $white = imagecolorallocate($dst_im, 255, 255, 255);//白
        //给新图填充背景色
        $black = imagecolorallocate($dst_im, 0, 0, 0);//黑
        $red = imagecolorallocate($dst_im, 255, 0, 0);//红
        $orange = imagecolorallocate($dst_im, 255, 85, 0);//橙
        imagefill($dst_im, 0, 0, $black);
        imagecopymerge($dst_im, $src_im, 0, 0, 0, 0, $src_w, $src_h, 100);//原图图像写入新建真彩位图中
        $src_lw = $tailoring_x; //水印位于背景图正中央width定位
        $src_lh = $tailoring_y; //height定位
        imagecopymerge($dst_im, $src_lim, $src_lw, $src_lh, 0, 0, $lim_size[0], $lim_size[1], 66);// 合并两个图像，设置水印透明度$waterclearly
        imagecopymerge($dst_im, $src_lim, $src_lw + 2, $src_lh + 2, 0, 0, $lim_size[0] - 4, $lim_size[1] - 4, 33);
        imagepng($dst_im, $picName); //生成图片 定义命名规则
        imagedestroy($src_lim);
        imagedestroy($src_im);
        imagedestroy($dst_im);
    }

    /**
     * 图片切割、打乱、重组 ，并把结果信息赋给内部变量
     *
     * @return Array: 结果集
     */
    protected function restruct_cut()
    {
        // 配置信息
        $srcFile = $this -> bigPicName;
        $bigPicName = $this -> bigPicName;
        $thumbnailWide = $this -> pic_info['w'];
        $thumbnailHeight = $this -> pic_info['h'];
        // 开始剪裁工作
        $num_w = 20;
        $num_h = 2;
        //每张小图宽度，高度
        $number_wide = $thumbnailWide / $num_w;
        $number_height = $thumbnailHeight / $num_h;
        $p_x = 0;
        $p_y = 0;
        for ($y = 0; $y < $num_h; $y ++) {
            for ($x = 0; $x < $num_w; $x ++) {
                if ($p_x >= $thumbnailWide) {
                    $p_x = 0;
                }
                $data_source[] = array('x' => $p_x, 'y' => $p_y);
                $p_x += $number_wide;
            }
            $p_y += $number_height;
        }
        if (empty($data_source)) {
            return false;
        }
        shuffle($data_source);
        $target_imgA = imagecreatetruecolor($thumbnailWide, $thumbnailHeight);
        $dstImBg = @imagecolorallocate($target_imgA, 255, 255, 255);
        imagefill($target_imgA, 0, 0, $dstImBg); //创建背景为白色的图片
        $srcIm = @imagecreatefrompng($srcFile); //截取指定区域
        $p_x = 0;
        $p_y = 0;
        $dataV = array();
        foreach ($data_source as $key => $val) {
            imagecopy($target_imgA, $srcIm, $p_x, $p_y, $val['x'], $val['y'], $number_wide, $number_height);
            $dataV[$val['x'] . '_' . $val['y']] = array('X' => $p_x . '_' . $p_y, 'Y' => $val['x'] . '_' . $val['y']);
            $p_x += $number_wide;
            if ($p_x >= $thumbnailWide) {
                $p_x = 0;
                $p_y = $number_height;
            }
        }
        imagepng($target_imgA, $bigPicName);
        imagedestroy($target_imgA);
        imagedestroy($srcIm);
        $_temp_xy_data = array();
        foreach ($dataV as $key => $val) {
            if ($val['X'] != $val['Y']) {
                $vv = explode('_', $dataV[$val['X']]['X']);
                $_temp_xy_data[] = $vv;
            } else {
                $vv = explode('_', $val['X']);
                $_temp_xy_data[] = $vv;
            }
        }
        session(self ::$session_name,$this -> location_x);
        $this -> get_png_info = array(
            'data' => $_temp_xy_data,
            'bg_pic' => ltrim($bigPicName, '.'),
            'ico_pic' => array('url' => ltrim($this -> smallPicName, '.'), 'w' => $this -> tailoring_w, 'h' => $this -> tailoring_h),
            'x_point' => $this -> location_x,
            'y_point' => $this -> location_y
        );
    }

    /**
     * @获取互动验证码
     *
     * @param bool $flag
     *
     * @return String
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\4 0004 10:02
     */
    public static function instance($flag = false)
    {
        $init = self ::init();
        session(self ::$session_data,json_encode($init));
        $obj = new self;
        return $obj -> render();

    }

    private static function init()
    {
        return $init = [
            "need_new" => false,     // 重置验证码的标识, false=>不需要
            "count" => 0,         // 验证最大次数，默认6次
            "timer" => time(),     // 验证码的时效，默认一分钟
            "success" => false,      // 是否已通过验证，默认否
            "status" => false       // 是否需要重新请求验证码，默认否
        ];
    }

    /**
     * 输出经过渲染的 DIV与CSS
     *
     * @return String  输出经过html渲染后的div+css，js需要自己写
     */
    private function render($width = 260, $height = 116)
    {
        $data = $this -> get_png_info;
        // 分割为左边图片与右边图片
        $pic_temp = array_chunk($data['data'], 20);
        $pg_bg = $data['bg_pic'];
        $ico_pic = $data['ico_pic'];
        $y_point = $data['y_point'];
        // 渲染 上
        $out_div = '<style>.drag{ position: relative; background-color: #f7f9fa; border: 1px solid #e4e7eb;width: 260px; height: 34px; line-height: 34px;margin-top:10px; text-align: center;-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;}'
            . '.drag .handler{ position: absolute; top: 0px; left: 0px; width: 40px; height: 32px; box-shadow: 0 0 3px rgba(0, 0, 0, 0.3); cursor: pointer;}'
            . '.handler_bg{ background: #fff url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA3hpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0ZDhlNWY5My05NmI0LTRlNWQtOGFjYi03ZTY4OGYyMTU2ZTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NTEyNTVEMURGMkVFMTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NTEyNTVEMUNGMkVFMTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo2MTc5NzNmZS02OTQxLTQyOTYtYTIwNi02NDI2YTNkOWU5YmUiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NGQ4ZTVmOTMtOTZiNC00ZTVkLThhY2ItN2U2ODhmMjE1NmU2Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+YiRG4AAAALFJREFUeNpi/P//PwMlgImBQkA9A+bOnfsIiBOxKcInh+yCaCDuByoswaIOpxwjciACFegBqZ1AvBSIS5OTk/8TkmNEjwWgQiUgtQuIjwAxUF3yX3xyGIEIFLwHpKyAWB+I1xGSwxULIGf9A7mQkBwTlhBXAFLHgPgqEAcTkmNCU6AL9d8WII4HOvk3ITkWJAXWUMlOoGQHmsE45ViQ2KuBuASoYC4Wf+OUYxz6mQkgwAAN9mIrUReCXgAAAABJRU5ErkJggg==") no-repeat center;}'
            . '.handler_ok_bg{ background: #fff url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA3hpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo0ZDhlNWY5My05NmI0LTRlNWQtOGFjYi03ZTY4OGYyMTU2ZTYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6NDlBRDI3NjVGMkQ2MTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6NDlBRDI3NjRGMkQ2MTFFNEI5NDBCMjQ2M0ExMDQ1OUYiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDphNWEzMWNhMC1hYmViLTQxNWEtYTEwZS04Y2U5NzRlN2Q4YTEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NGQ4ZTVmOTMtOTZiNC00ZTVkLThhY2ItN2U2ODhmMjE1NmU2Ii8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+k+sHwwAAASZJREFUeNpi/P//PwMyKD8uZw+kUoDYEYgloMIvgHg/EM/ptHx0EFk9I8wAoEZ+IDUPiIMY8IN1QJwENOgj3ACo5gNAbMBAHLgAxA4gQ5igAnNJ0MwAVTsX7IKyY7L2UNuJAf+AmAmJ78AEDTBiwGYg5gbifCSxFCZoaBMCy4A4GOjnH0D6DpK4IxNSVIHAfSDOAeLraJrjgJp/AwPbHMhejiQnwYRmUzNQ4VQgDQqXK0ia/0I17wJiPmQNTNBEAgMlQIWiQA2vgWw7QppBekGxsAjIiEUSBNnsBDWEAY9mEFgMMgBk00E0iZtA7AHEctDQ58MRuA6wlLgGFMoMpIG1QFeGwAIxGZo8GUhIysmwQGSAZgwHaEZhICIzOaBkJkqyM0CAAQDGx279Jf50AAAAAABJRU5ErkJggg==") no-repeat center;}'
            . '.drag .drag_bg{ background-color: #ffc36e; height: 32px; width: 0px;}'
            . '.drag .drag_text{color:#45494c; font-family: 微软雅黑;font-weight: normal;letter-spacing: 1px;position: absolute;top: 0px;width: 260px;font-size: 14px;-moz-user-select: none;-webkit-user-select: none;user-select: none;-o-user-select: none;-ms-user-select: none;}'
            . '.drag.drag_success {border-color: rgba(118,198,29,1);}'
            . '.drag.drag_success .drag_bg {background-color: rgba(118,198,29,.7);}'
            . '.drag.drag_success .drag_text {color: #fff;}'
            . '.drag.drag_success .handler{box-shadow:none;}'
            . '.drag.drag_error{border-color: rgba(211,0,0,.7);}'
            . '.drag.drag_error .drag_bg{background-color: rgba(211,0,0,.7);}'
            . '.drag.drag_error .drag_text {color: #fff;}'
            . '.drag.drag_error .handler{box-shadow:none;}'
            . '.drag.drag_error .handler_bg {background:#fff url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAYAAABzenr0AAACfklEQVRYR8WX32oTQRTGv7PJblQQelHwIt1NKDTNNl4UFAoKBQUFQUHcsPFFfAavfQsb2iIoCAoKgoKg4EXrJqlUs1sFwQtBULNJ9sjZJiW1af7U1NnbnTnn930zc+YMQfFHivNjD6Bi6s9BlAVwJ++Hq8cBVrGMIoB7YK7lg+YlybEHULWMDQYKBLTBKM4H4YNJQlRN4yYIqwwkAHzJ+2F6H4DQEXC/MyBkUNH2Gw8nAVGzUtcj8BoAQwQycLvr8r49IJRMWJGBAH4RkTNfbzz+F4hqJnWNmWVJTwEIiVHqdffAJvSs1A0CC8RJAD+YqWgHjSdHgahZqSsd5acB/NRApZzfeNQbq+8p6FALhEz8DtKcfP33s3EgKpkTl8GR2D4lQoio1M/NQ4+hZ6auEsVOTAH0TeO2kwtaL0aBeG8mlzVKrAE8LQI0kJvzG0/7zR1YB3ZVCARPM/grgZ2833o5CMLLJC+AaZ1AZwQcRKVB7g0tRDUzudwmKu8GxGctYie303zdD2JzRl9KaCS2pwWYmd2FIa4NBZBEFSt5EdBkOdLM8GMnguabXgjP0s/FygmWgDJFrl1vvRq2ZCMBSJDajL4UO0GwCNgmjZ3cp+Y7+beZ1RcTUax8VgAjZrdwiEt/A40MEDth6ueJaIWBWQBbScatUMopYZ2AOQDbDHZtv/l2mPLu/7EAYiey+mIUURnAHAEe70ayGdiKNHYLHVeODUACfzCNs21CmQG7k8iLGO5CEG6MmvjIDigHULoESjeh0mOotBApLcVKLyOl17HShkRpS6a0KVXellcs4yOArLqHyX96mjFwl5h3DjzNxr1GJzV+7IZkUom7cf4A6Re+MPwLI0QAAAAASUVORK5CYII=") no-repeat center;background-size: 40%;}'
            . '.gt_cut_fullbg_slice { float: left; width: 13px; height: 58px; margin: 0 !important; border: 0px; padding: 0 !important; background-image: url(' . $pg_bg . '); } '
            . '.caption_box{display:inline-block;}'
            . '.verification_container{width:' . $width . 'px;height:' . $height . 'px;}'
            . '.sliding_block{background-image: url(' . $ico_pic['url'] . ');z-index: 999;width:' . $ico_pic['w'] . 'px;height:' . $ico_pic['h'] . 'px;position: relative; top: ' . $y_point . 'px;left: 0px;display: none;border: 1px solid rgba(255,255,255,.7);box-shadow: 0 0 0px 0px rgba(255,255,255,.7);-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box;}'
            . '</style><div class="caption_box"><div class="verification_container" >';
        // 渲染 中
        foreach ($pic_temp as $temp) {
            foreach ($temp as $vo) {
                $left_symbol = $vo[0] == 0 ? '' : '-';
                $right_symbol = $vo[1] == 0 ? '' : '-';
                $out_div .= '<div class="gt_cut_fullbg_slice" style="background-position:' . $left_symbol . $vo[0] . 'px ' . $right_symbol . $vo[1] . 'px;"></div>';
            }
        }
        // 渲染 下
        $out_div .= '<div class="sliding_block"></div></div>'
            . '<div class="drag">'
            . '<div class="drag_bg"></div>'
            . '<div class="drag_text" onselectstart="return false;" unselectable="on">' . self ::get_lan_pack()['slide_info'] . '</div>'
            . '<div class="handler handler_bg"></div></div></div>';
        return $out_div;
    }

    /**
     * 用户语言判断
     *
     * @param Array : session session的配置
     * @param Int   : x_value 横坐标的值
     *
     * @return String 中文或者英文包
     */
    private static function get_lan_pack()
    {
        $language = getallheaders()['Accept-Language'];
        if (preg_match('/zh/i', $language)) {
            return self ::$inner_info['zh'];
        } else {
            return self ::$inner_info['en'];
        }
    }

    /**
     * @验证滑动验证码
     *
     * @param int $more
     *
     * @return array|Array
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\4 0004 9:37
     */
    public static function verify($more = 8)
    {
        $back = ['code' => - 207, 'info' => self ::get_lan_pack()['code']['-207']];
        $init = self ::init();
        if (null !==session(self ::$session_data)) {
            $this_session = json_decode(session(self ::$session_data), true);
            $config = array_merge($init, $this_session);
        } else {
            $config = $init;
        }
        if (!isset($_POST[self ::$post_x_name]) ||
            !isset($_POST['b']) ||
            !isset($_POST['j']) ||
            !isset($_POST['d']) ||
            !isset($_POST['e'])
        ) {
            $msg = $back;
        } else {
            if (self ::prefixCheck($_POST)) {
                $x = str_replace(base64_decode($_POST['d']), '', base64_decode($_POST[self ::$post_x_name])) - 1000;
                $msg = self ::validate($config, $x, $more);
                $msg['tag'] = self ::random_string(32);
                $msg['token'] = self ::getToken($msg['tag']);

            } else {
                $msg = $back;
            }
        }
        return $msg;
    }

    /**
     * @函数或方法说明
     *
     * @param $post
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\2 0002 13:43
     */
    private static function prefixCheck($post)
    {
        $data = explode('&', base64_decode(self ::fromCode($post['b'])));
        $str = self ::$post_x_name . '=' . $post[self ::$post_x_name] . '&' . $data[1] . '&t=' . $post['j'];
        if ($_POST['b'] !== self ::toCode(base64_encode($str))) {
            return false;
        }
        if ($post['e'] !== sha1(self ::fromCode($post['b']) . base64_decode($post['d']))) {
            return false;
        }
        $track = json_decode(explode('=', $data[1])[1], true);
        if (!self ::track($track)) {
            return false;
        }
        return true;

    }

    /**
     * @解密
     *
     * @param $str
     *
     * @return float|string
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\2 0002 18:03
     */
    private static function fromCode($str)
    {
        $key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $l = strlen($key);
        $d = 0;
        $s = [];
        $b = floor(strlen($str) / 3);
        for ($i = 0; $i < $b; $i ++) {
            $b1 = stripos($key, $str[$d ++]);
            $b2 = stripos($key, $str[$d ++]);
            $b3 = stripos($key, $str[$d ++]);
            $s[$i] = $b1 * $l * $l + $b2 * $l + $b3;
        }
        $b = self ::fromCharCode($s);
        return $b;
    }


    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    //              分割线，若只是使用，则下面的不用管
    //+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    /**
     * @函数或方法说明
     *
     * @param $codes
     *
     * @return string
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\2 0002 18:03
     */
    private static function fromCharCode($codes)
    {
        if (is_scalar($codes)) {
            $codes = func_get_args();
        }
        $str = '';
        foreach ($codes as $code) {
            $str .= chr($code);
        }
        return $str;
    }

    /**
     * @加密
     *
     * @param $str
     *
     * @return string
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\2 0002 18:03
     */
    private static function toCode($str)
    {
        $key = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $l = strlen($key);
        $s = '';
        for ($i = 0; $i < strlen($str); $i ++) {
            $b = self ::charCodeAt($str[$i]);
            $b1 = $b % $l;
            $b = ($b - $b1) / $l;
            $b2 = $b % $l;
            $b = ($b - $b2) / $l;
            $b3 = $b % $l;
            $s .= $key[$b3] . $key[$b2] . $key[$b1];
        }
        return $s;
    }

    /**
     * @函数或方法说明
     *
     * @param $str
     * @param int $index
     *
     * @return |null
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\2 0002 18:03
     */
    private static function charCodeAt($str, $index = 0)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, "UCS-4BE");
            $ret = unpack("N", $ret);
            return $ret[1];
        } else {
            return null;
        }
    }

    /**
     * @鼠标轨迹判断
     *
     * @param $data
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\12\31 0031 11:53
     */
    private static function track($data)
    {

        if (!self ::checkTimeExtent($data)) {
            return false;
        }
        if (!self ::checkExtent($data)) {
            //return false;
        }
        return true;

    }

    /**
     * @测试时间是否符合
     *
     * @param $data
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\12\30 0030 19:54
     */
    private static function checkTimeExtent($data)
    {
        $len = count($data);
        $starTime = $data[0][2];
        $endTime = $data[$len - 1][2];
        if ($endTime - $starTime > 4000) {
            return false;
        } elseif ($endTime - $starTime < 600) {
            return false;
        }
        return true;

    }

    /**
     * @测试Y轴方向抖动情况
     *
     * @param $data
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\12\30 0030 20:10
     */
    private static function checkExtent($data)
    {

        $y = array_column($data, 1);
        $yMin = $y[array_search(min($y), $y)];
        $yMax = $y[array_search(max($y), $y)];
        if ($yMax - $yMin > 40) {
            return false;
        } elseif ($yMax - $yMin < 5) {
            return false;
        }
        return true;
    }

    /**
     * 验证，验证码
     *
     * @param Array : session session的配置
     * @param Int   : x_value 横坐标的值
     * @param Int   : fault_tolerant 容错值
     *
     * @return Array : 结果集
     */
    private static function validate($session, $x_value, $fault_tolerant)
    {
        // 计数器
        $session['count'] ++;
        // 是否在有效期内
        $dis_time = time() - $session['timer'];
        if ($dis_time > self ::$timer) {
            $msg['status'] = true;
            $msg['code'] = - 203;
            $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            return $msg;
        }
        // 是否已需重新验证
        if (true == $session['need_new']) {
            $msg['status'] = true;
            $msg['code'] = - 204;
            $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            return $msg;
        }
        // 数值正确性，验证
        $result = self ::check($x_value, $fault_tolerant);
        if ($result['code'] == 200) {
            // 校验成功标志
            $session['success'] = true;
            // 还原配置
            $session['need_new'] = false;
            $session['count'] = 0;
            session(self ::$session_data,json_encode($session));
            $msg['status'] = false;
            $msg['code'] = 200;
            $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            return $msg;
        } else {
            $msg['status'] = false;
            $msg['code'] = - 201;
            // 如果以达到最大次数
            if ($session['count'] >= self ::$times_max) {
                $session['need_new'] = true;
                $msg['status'] = true;
                $msg['code'] = - 204;
            }
            $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            session(self ::$session_data,json_encode($session));
            return $msg;
        }
    }

    /**
     * 校验数据合法性
     *
     * @param Int    : val
     * @param Int    : deviation 验证码,允许误差值
     *
     * @return Array
     */
    private static function check($val, $deviation)
    {
        if (!is_numeric($val) || !is_numeric($deviation)) {
            $msg['code'] = - 202;
            $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
        } else {
            $max = session(self ::$session_name) + $deviation;
            $min = session(self ::$session_name) - $deviation;
            // 是否在误差允许范围内
            if ($val <= $max && $val >= $min) {
                $msg['code'] = 200;
                $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            } else {
                $msg['code'] = - 201;
                $msg['info'] = self ::get_lan_pack()['code'][$msg['code']];
            }
        }
        return $msg;
    }

    /**
     * @随机字符串
     *
     * @param int $length
     *
     * @return string
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\3 0003 9:27
     */
    private static function random_string($length = 64)
    {
        // 密码字符集，可任意添加你需要的字符
//        $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
//                'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
//                't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
//                'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
//                'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
//                '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '!',
//                '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_',
//                '[', ']', '{', '}', '<', '>', '~', '`', '+', '=', ',',
//                '.', ';', ':', '/', '?', '|');
        $chars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D',
            'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        // 在 $chars 中随机取 $length 个数组元素键名
        $keys = array_rand($chars, $length);
        $password = '';
        for ($i = 0; $i < $length; $i ++) {
            // 将 $length 个数组元素连接成字符串
            $password .= $chars[$keys[$i]];
        }
        return $password;
    }

    /**
     * @生成唯一标识
     *
     * @return bool|string
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\3 0003 9:27
     */
    private static function getToken($tag)
    {
        $str = self ::random_string();
        $token = sha1(base64_encode(md5($str . date('Y-m-d H'))));
        $save_token_check_count = $token.'check_count ';
        session($save_token_check_count,0);
        session($tag . '_token', json_encode(['token' => $token, 'str' => $str]));
        return $token;
    }

    /**
     * 清除token
     */
    public function clearToken($tag)
    {
        $save_token = json_decode(session($tag . '_token'), true);
        $save_token_check_count = $save_token['token'].'check_count ';
    }
    /**
     * @验证唯一标识
     *
     * @param $token
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2020\1\3 0003 9:28
     */
    public static function checkToken($token, $tag,$remove=1)
    {
        $back = ['code' => - 201, 'info' => self ::get_lan_pack()['code']['-201']];
        if (empty($token) || empty($tag)) {
            return $back;
        }
        if (empty(session($tag . '_token'))) {
            $back = ['code' => - 208, 'info' => self ::get_lan_pack()['code']['-208']];
            return $back;
        }
        $save_token = json_decode(session($tag . '_token'), true);
        $save_token_check_count = $save_token['token'].'check_count ';
        if(empty(session($save_token_check_count))){
            session($save_token_check_count,0);
        }
        session($save_token_check_count,session($save_token_check_count)+1);
        if(session($save_token_check_count)>self::$times_max){
            $back = ['code' => - 204, 'info' => self ::get_lan_pack()['code']['-204']];
            session($tag . '_token', null);
            session($save_token_check_count,0);
            return $back;
        }
        if($remove){
            session($tag . '_token', null);
        }
        if ($token === sha1(base64_encode(md5($save_token['str'] . date('Y-m-d H'))))) {
            $back = ['code' => 200, 'info' => self ::get_lan_pack()['code'][200]];
            return $back;
        }
        return $back;

    }

    /**
     * @速度判断（todo）
     *
     * @param $data
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\12\31 0031 11:53
     */
    private static function checkSpeed($data)
    {
        $speed = [];
        foreach ($data as $key => $value) {
            if ($key > 0) {
                $speed[] = ($value[0] - $data[$key - 1][0]) / ($value[2] - $data[$key - 1][2]);
            }
        }

    }

    /**
     * @方差判断（todo）
     *
     * @param $data
     *
     * @return bool
     *
     * @author: 鹿文学[lwx]<fyj301415926@126.com>
     * @since: 2019\12\31 0031 11:54
     */
    private static function variance($data)
    {
        $y = array_column($data, 1);
        $yLen = count($y);
        if ($yLen == 0) {
            return false;
        }
        $yAverage = array_sum($y) / $yLen;
        $count1 = $count2 = 0;
        $yTemp = array_chunk($y, 2);
        foreach ($yTemp[0] as $v) {
            $count1 += pow($yAverage - $v, 2);
        }
        foreach ($yTemp[1] as $v) {
            $count2 += pow($yAverage - $v, 2);
        }
        $variance1 = $count1 / count($yTemp[0]);
        $variance2 = $count2 / count($yTemp[1]);
        if ($variance2 > $variance1 + 2) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 判断是否验证成功
     *
     * @param Array : session 当前session的信息
     *
     * @return Array : 结果集
     */
    private static function if_success($session)
    {
        // 超时？
        if (time() - $session['timer'] > self ::$timer) {
            $session['success'] = false;
            session(self ::$session_data,json_encode($session));
        }
        // 已通过？
        if ($session['success']) {
            // 重置验证码
            $session['success'] = false; // 置为之前的状态
            session(self ::$session_name, mt_rand(1, 100)); // 设置临时验证码，防止验证码重复提交
            session(self ::$session_data,json_encode($session));
            return [
                "status" => false,// 是否需要换验证图片
                "code" => 200, // 编码值，值为2000表示验证成功
                "info" => self ::get_lan_pack()['code']['200'] // 输出错误信息
            ];
        } else {
            // 获取信息的资源
            return [
                "status" => true,// 是否需要换验证图片
                "code" => 203,
                "info" => self ::get_lan_pack()['code']['-203'] // 输出错误信息
            ];
        }
    }

}
