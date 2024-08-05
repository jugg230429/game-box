<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class WechatController extends AdminBaseController
{
    //判断权限
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('admin/main/index'));
            };
        }
    }

    //首页公众号信息
    public function index(Request $request)
    {
        //获取基本信息
        $base = new BaseController();
        $base->BaseConfig('wechat');
        $this->assign("wechat_url", $request->domain() . "/callback/Wechat/index");
        return $this->fetch();
    }

    /**
     * [保存公众号信息]
     * @param string $value
     * @author 郭家屯[gjt]
     */
    public function save_tool()
    {
        $data = $this->request->param();
        $name = $data['name'];
        $base = new BaseController();
        $flag = $base->saveConfig($name, $data);
        if ($flag !== false) {
            write_action_log("公众号设置");
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }

    }


}
