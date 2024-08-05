<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/26
 * Time: 11:24
 */

namespace app\member\controller;

use app\member\model\UserParamModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class ThirdloginController extends AdminBaseController
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
        $action = request()->action();
        $array = ['qq_param_lists', 'wx_param_lists'];
        if (in_array($action, $array)) {
            if (AUTH_GAME != 1) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                    $this->error('请购买游戏权限');
                } else {
                    $this->error('请购买游戏权限', url('admin/main/index'));
                };
            }
        }
    }

    //qq官网设置
    public function qq_thirdparty()
    {
        $base = new BaseController();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $name = $data['name'];
            $flag = $base->saveConfig($name, $data);
            if ($flag !== false) {
                write_action_log("第三方登录设置");
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
        $name = 'qq_login';
        $base->BaseConfig($name);
        return $this->fetch();
    }

    //qq官网设置
    public function app_qq_thirdparty()
    {
        $base = new BaseController();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $name = $data['name'];
            $flag = $base->saveConfig($name, $data);
            if ($flag !== false) {
                write_action_log("第三方登录设置");
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
        $name = 'app_qq_login';
        $base->BaseConfig($name);
        return $this->fetch();
    }

    //qq  SDK设置
    public function qq_param_lists()
    {
        $base = new BaseController();
        $param = $this->request->param();
        if ($param['game_id']) {
            $map['game_id'] = $param['game_id'];
        }
        $map['type'] = 1;
        $model = new UserParamModel();
        $data = $base->data_list($model, $map)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    //微信官方设置
    public function wx_thirdparty()
    {
        $base = new BaseController();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $name = $data['name'];
            $flag = $base->saveConfig($name, $data);
            if ($flag !== false) {
                write_action_log("第三方登录设置");
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
        $name = 'weixin_login';
        $base->BaseConfig($name);
        return $this->fetch();
    }

    //微信APP官方设置
    public function app_wx_thirdparty()
    {
        $base = new BaseController();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $name = $data['name'];
            $flag = $base->saveConfig($name, $data);
            if ($flag !== false) {
                write_action_log("第三方登录设置");
                $this->success('设置成功');
            } else {
                $this->error('设置失败');
            }
        }
        $name = 'app_weixin_login';
        $base->BaseConfig($name);
        return $this->fetch();
    }

    //微信SDK设置
    public function wx_param_lists()
    {
        $base = new BaseController();
        $param = $this->request->param();
        if ($param['game_id']) {
            $map['game_id'] = $param['game_id'];
        }
        $map['type'] = 2;
        $model = new UserParamModel();
        $data = $base->data_list($model, $map)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * 方法 fb_param_lists
     *
     * @descript 脸书
     *
     * @return mixed
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/22 0022 17:12
     */
    public function fb_param_lists()
    {
        $base = new BaseController();
        $param = $this->request->param();
        if ($param['game_id']) {
            $map['game_id'] = $param['game_id'];
        }
        $map['type'] = 3;
        $model = new UserParamModel();
        $data = $base->data_list($model, $map)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * [新增QQ参数]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add_qq()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['game_id'])) $this->error('请选择游戏');
            $map['game_id'] = $data['game_id'];
            $map['type'] = 1;
            $info = Db::table('tab_user_param')->where($map)->find();
            if ($info) $this->error('该游戏已添加过参数，无需重复添加');
            if (empty($data['openid'])) $this->error('请输入openid');
            if (empty($data['key'])) $this->error('请输入安全校验码');
            $data['type'] = 1;
            $data['create_time'] = time();
            $result = Db::table('tab_user_param')->insert($data);
            if ($result) {
                write_action_log("第三方登录设置");
                $this->success('新增成功', 'qq_param_lists');
            } else {
                $this->error('新增失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [新增微信参数]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function add_wx()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['game_id'])) $this->error('请选择游戏');
            $map['game_id'] = $data['game_id'];
            $map['type'] = 2;
            $info = Db::table('tab_user_param')->where($map)->find();
            if ($info) $this->error('该游戏已添加过参数，无需重复添加');
            if (empty($data['wx_appid'])) $this->error('请输入appid');
            if (empty($data['appsecret'])) $this->error('请输入APPSECRET');
            $data['type'] = 2;
            $data['create_time'] = time();
            $result = Db::table('tab_user_param')->insert($data);
            if ($result) {
                write_action_log("第三方登录设置");
                $this->success('新增成功', 'wx_param_lists');
            } else {
                $this->error('新增失败');
            }
        }
        return $this->fetch();
    }

    public function add_fb()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (empty($data['game_id'])) $this->error('请选择游戏');
            $map['game_id'] = $data['game_id'];
            $map['type'] = 3;
            $info = Db::table('tab_user_param')->where($map)->find();
            if ($info) $this->error('该游戏已添加过参数，无需重复添加');
            if (empty($data['wx_appid'])) $this->error('请输入appid');
            if (empty($data['appsecret'])) $this->error('请输入APPSECRET');
            $data['type'] = 3;
            $data['create_time'] = time();
            $result = Db::table('tab_user_param')->insert($data);
            if ($result) {
                write_action_log("第三方登录设置");
                $this->success('新增成功', 'fb_param_lists');
            } else {
                $this->error('新增失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑QQ]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit_qq()
    {
        $data = $this->request->param();
        $map['id'] = $data['id'];
        $map['type'] = 1;
        $info = Db::table('tab_user_param')->where($map)->find();
        if (empty($info)) $this->error('信息不存在或是已删除');
        if ($this->request->isPost()) {
            unset($data['game_id']);
            if (empty($data['openid'])) $this->error('请输入openid');
            if (empty($data['key'])) $this->error('请输入安全校验码');
            $result = Db::table('tab_user_param')->update($data);
            if ($result === false) {
                $this->error('编辑失败');
            } else {
                write_action_log("第三方登录设置");
                $this->success('编辑成功', 'qq_param_lists');
            }
        }
        $this->assign('data', $info);
        return $this->fetch();
    }

    /**
     * [编辑微信]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit_wx()
    {
        $data = $this->request->param();
        $map['id'] = $data['id'];
        $map['type'] = 2;
        $info = Db::table('tab_user_param')->where($map)->find();
        if (empty($info)) $this->error('信息不存在或是已删除');
        if ($this->request->isPost()) {
            unset($data['game_id']);
            if (empty($data['wx_appid'])) $this->error('请输入appid');
            if (empty($data['appsecret'])) $this->error('请输入APPSECRET');
            $result = Db::table('tab_user_param')->update($data);
            if ($result === false) {
                $this->error('编辑失败');
            } else {
                write_action_log("第三方登录设置");
                $this->success('编辑成功', 'wx_param_lists');
            }
        }
        $this->assign('data', $info);
        return $this->fetch();
    }

    public function edit_fb()
    {
        $data = $this->request->param();
        $map['id'] = $data['id'];
        $map['type'] = 3;
        $info = Db::table('tab_user_param')->where($map)->find();
        if (empty($info)) $this->error('信息不存在或是已删除');
        if ($this->request->isPost()) {
            unset($data['game_id']);
            if (empty($data['wx_appid'])) $this->error('请输入appid');
            if (empty($data['appsecret'])) $this->error('请输入APPSECRET');
            $result = Db::table('tab_user_param')->update($data);
            if ($result === false) {
                $this->error('编辑失败');
            } else {
                write_action_log("第三方登录设置");
                $this->success('编辑成功', 'fb_param_lists');
            }
        }
        $this->assign('data', $info);
        return $this->fetch();
    }

    /**
     * [删除]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (!$ids) $this->error('请选择你要操作的数据');
        $map['id'] = ['in', $ids];
        $result = Db::table('tab_user_param')->where($map)->delete();
        if ($result) {
            write_action_log("第三方登录设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    public function del_fb()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (!$ids) $this->error('请选择你要操作的数据');
        $map['id'] = ['in', $ids];
        $result = Db::table('tab_user_param')->where($map)->delete();
        if ($result) {
            write_action_log("第三方登录设置");
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }


}
