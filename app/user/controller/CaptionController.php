<?php
/**
 * 短信类
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-26 13:45
 */

namespace app\user\controller;

use think\Controller;
use think\View;
use app\common\logic\CaptionLogic;

class CaptionController extends Controller
{
    protected $lCaption;

    public function __construct()
    {
        parent ::__construct();
        $this -> lCaption = new CaptionLogic();
    }
    public function verify_pop()
    {
        $v = new View();
        $v->assign('key', base64_encode(sha1(md5('fdsfasdfsadfsadfsad'))));
        $v->assign('id',request()->param('id'));
        return json($v->fetch('user@caption/verify_pop'));
    }
    /**
     * 初始获取 验证码
     */
    public function init()
    {
        return json($this -> lCaption -> init());
    }
    /**
     * 获取验证码的html
     *
     * @echo Html | String
     */
    public function captchar()
    {
        echo $this -> lCaption -> captchar();
    }

    /**
     * 验证码，校验
     *
     * @echo String : {"Err":"","out":""}
     */
    public function check()
    {
        return json($this -> lCaption -> check());
    }
}
