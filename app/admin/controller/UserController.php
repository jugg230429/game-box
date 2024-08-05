<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\db\Query;

/**
 * Class UserController
 * @package app\admin\controller
 * @adminMenuRoot(
 *     'name'   => '管理组',
 *     'action' => 'default',
 *     'parent' => 'user/AdminIndex/default',
 *     'display'=> true,
 *     'order'  => 10000,
 *     'icon'   => '',
 *     'remark' => '管理组'
 * )
 */
class UserController extends AdminBaseController
{

    /**
     * 管理员列表
     * @adminMenu(
     *     'name'   => '管理员',
     *     'parent' => 'default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员管理',
     *     'param'  => ''
     * )
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $content = hook_one('admin_user_index_view');

        if (!empty($content)) {
            return $content;
        }

        /**搜索条件**/
        $userLogin = trim($this->request->param('user_login'));
        $user_status = $this->request->param('user_status');
        $row = (int)$this->request->param('row') ?: config('paginate.list_rows');
        $users = Db::name('user')
            ->where('user_type', 1)
            ->where(function (Query $query) use ($userLogin, $user_status) {
                if ($userLogin) {
                    $query->where('user_login', 'like', "%$userLogin%");
                }

                if (is_numeric($user_status)) {
                    $query->where('user_status', 'eq', "$user_status");
                }
            })
            ->order("id DESC")
            ->paginate($row, false, ['query' => $this->request->param()]);
        $users->appends(['user_login' => $userLogin, 'user_email' => $userEmail]);
        // 获取分页显示
        $page = $users->render();

        $rolesSrc = Db::name('role')->select();
        $roles = [];
        foreach ($rolesSrc as $r) {
            $roleId = $r['id'];
            $roles["$roleId"] = $r;
        }
        $this->assign("page", $page);
        $this->assign("roles", $roles);
        $this->assign("users", $users);
        return $this->fetch();
    }

    /**
     * 管理员添加
     * @adminMenu(
     *     'name'   => '管理员添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $content = hook_one('admin_user_add_view');

        if (!empty($content)) {
            return $content;
        }

        $roles = Db::name('role')->where('status', 1)->order("id DESC")->select()->toArray();
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /**
     * 管理员添加提交
     * @adminMenu(
     *     'name'   => '管理员添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        if ($this->request->isPost()) {
            //增加对可查看游戏的选择处理-20210624--byh-start
            if(!empty($_POST['view_game_ids'])){
                $_POST['view_game_ids'] = implode(',',$_POST['view_game_ids']);
            }
            //增加对可查看游戏的选择处理-20210624--byh-end
            if (!empty($_POST['role_id'])) {
                $role_ids[] = $_POST['role_id'];
                unset($_POST['role_id']);
                $post = $this->request->param();
                foreach ($post as $key => $value) {
                    if (!preg_match("/^[A-Za-z0-9]+$/", $value)) {
                        $post[$key] = '';
                    }
                }
                $result = $this->validate($post, 'User');
                if(!empty($_POST['user_email'])){
                    $tmp_admin_info = Db::table('sys_user')->where(['user_email'=>$_POST['user_email']])->find();
                    if(!empty($tmp_admin_info)){
                        $this->error("邮箱已经存在!");
                    }
                }
                if ($result !== true) {
                    $this->error($result);
                } else {

                    if(!empty($post['mobile']) && !cmf_check_mobile($post['mobile']) ){
                        $this->error('手机号不正确');
                    }

                    unset($_POST['user_pass_confirm']);
                    unset($_POST['user_pass_erji']);
                    $_POST['user_pass'] = cmf_password($_POST['user_pass']);
                    $_POST['second_pass'] = cmf_password($_POST['second_pass']);
                    //开启事务
                    Db::startTrans();
                    $result = DB::name('user')->insertGetId($_POST);
                    if ($result !== false) {
                        //$role_user_model=M("RoleUser");
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                // 回滚事务
                                Db::rollback();
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            Db::name('RoleUser')->insert(["role_id" => $role_id, "user_id" => $result]);
                        }
                        // 提交事务
                        Db::commit();
                        write_action_log("新增管理员【" . $post['user_login'] . "】");
                        $this->success("添加成功！", url("user/index"));
                    } else {
                        // 回滚事务
                        Db::rollback();
                        $this->error("添加失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员编辑
     * @adminMenu(
     *     'name'   => '管理员编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $content = hook_one('admin_user_edit_view');

        if (!empty($content)) {
            return $content;
        }

        $id = $this->request->param('id', 0, 'intval');
        $roles = DB::name('role')->where('status', 1)->order("id DESC")->select();
        $this->assign("roles", $roles);
        $role_ids = DB::name('RoleUser')->where("user_id", $id)->column("role_id");
        $this->assign("role_ids", $role_ids);

        if(in_array('1',$role_ids)){
            $menu_select_lists = $this->menu_all_lists();
        }else{
            $menu_select_lists = $this->get_role_menu_data($role_ids);
        }
        $this->assign("menu_select_lists", $menu_select_lists);

        $user = DB::name('user')->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理员编辑提交
     * @adminMenu(
     *     'name'   => '管理员编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        if ($this->request->isPost()) {
            if (!empty($_POST['role_id'])) {
                $post = $this->request->param();
                //增加对可查看游戏的选择处理-20210612--byh-start
                if(!empty($_POST['view_game_ids'])){
                    $_POST['view_game_ids'] = implode(',',$_POST['view_game_ids']);
                }else{
                    $_POST['view_game_ids'] = '';//没有值的时候可能是取消限制
                }
//                dump($_POST);die;
                //增加对可查看游戏的选择处理-20210612--byh-wnd
                if (empty($_POST['user_pass'])) {
                    unset($_POST['user_pass']);
                } else {
                    $_POST['user_pass'] = cmf_password($post['user_pass']);
                }
                if (empty($_POST['second_pass'])) {
                    unset($_POST['second_pass']);
                } else {
                    $_POST['second_pass'] = cmf_password($post['second_pass']);
                }
                $role_ids = $this->request->param('role_id/a');
                unset($_POST['role_id']);
                foreach ($post as $key => $value) {
                    if (!(preg_match("/^[A-Za-z0-9]+$/", $value))&&$value!='') {
                        $post[$key] = 1;//密码包含特殊字符不允许保存
                    }
                }
                if(!empty($_POST['user_email'])){
                    $tmp_admin_info = Db::table('sys_user')->where(['user_email'=>$_POST['user_email'], 'id'=>['<>', $_POST['id']]])->find();
                    if(!empty($tmp_admin_info)){
                        $this->error("邮箱已经存在!");
                    }
                }
                $result = $this->validate($post, 'User.edit');

                if ($result !== true) {
                    // 验证失败 输出错误信息
                    $this->error($result);
                } else {
                    if(!empty($post['mobile']) && !cmf_check_mobile($post['mobile']) ){
                        $this->error('手机号不正确');
                    }

                    $result = DB::name('user')->update($_POST);
                    if ($result !== false) {
                        $uid = $this->request->param('id', 0, 'intval');
                        DB::name("RoleUser")->where(["user_id" => $uid])->delete();
                        foreach ($role_ids as $role_id) {
                            if (cmf_get_current_admin_id() != 1 && $role_id == 1) {
                                $this->error("为了网站的安全，非网站创建者不可创建超级管理员！");
                            }
                            DB::name("RoleUser")->insert(["role_id" => $role_id, "user_id" => $uid]);
                        }
                        write_action_log("修改管理员【" . get_admin_name($post['id']) . "】信息");
                        $this->success("保存成功！", url("user/index"));
                    } else {
                        $this->error("保存失败！");
                    }
                }
            } else {
                $this->error("请为此用户指定角色！");
            }

        }
    }

    /**
     * 管理员个人信息修改
     * @adminMenu(
     *     'name'   => '个人信息',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改',
     *     'param'  => ''
     * )
     */
    public function userInfo()
    {
        $id = cmf_get_current_admin_id();
        $user = Db::name('user')->where("id", $id)->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 管理员个人信息修改提交
     * @adminMenu(
     *     'name'   => '管理员个人信息修改提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员个人信息修改提交',
     *     'param'  => ''
     * )
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data = $this->request->post();
            $data['birthday'] = strtotime($data['birthday']);
            $data['id'] = cmf_get_current_admin_id();
            $create_result = Db::name('user')->update($data);;
            if ($create_result !== false) {
                write_action_log("管理员个人信息修改");
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * 管理员删除
     * @adminMenu(
     *     'name'   => '管理员删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '管理员删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id == 1) {
            $this->error("最高管理员不能删除！");
        }
        $admin_name = \think\Db::table('sys_user')->where(['id' => $id])->value('user_login');
        if (Db::name('user')->delete($id) !== false) {
            Db::name("RoleUser")->where("user_id", $id)->delete();
            write_action_log("删除管理员【" . $admin_name . "】");
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用管理员
     * @adminMenu(
     *     'name'   => '停用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '停用管理员',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '0');
            if ($result !== false) {
                write_action_log("锁定管理员【" . get_admin_name($id) . "】");
                $this->success("管理员停用成功！", url("user/index"));
            } else {
                $this->error('管理员停用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 启用管理员
     * @adminMenu(
     *     'name'   => '启用管理员',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '启用管理员',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('user')->where(["id" => $id, "user_type" => 1])->setField('user_status', '1');
            if ($result !== false) {
                write_action_log("解锁管理员【" . get_admin_name($id) . "】");
                $this->success("管理员启用成功！", url("user/index"));
            } else {
                $this->error('管理员启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }


    /**
     * 获取菜单有界面可访问且显示的全部数据-20210624-byh
     */
    public function menu_all_lists()
    {
        $map['type'] = 1;
        $map['status'] = 1;
        $map['parent_id'] = ['NOTIN',[0,1,110,193,168,198]];
        $result = Db::name('AdminMenu')
            ->field('app,controller,action,name')
            ->where($map)
            ->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])
            ->select();
        if(empty($result)){
            return [];
        }
        $result = $result->toArray();
        foreach ($result as $k => $v){
            $result[$k]['page_url'] = $v['app'].'/'.$v['controller'].'/'.$v['action'];
        }
        return $result;
    }
    /**
     * 根据不同的角色权限-查询权限中菜单有界面可访问且显示的数据
     * by:byh-20210624
     */
    public function get_role_menu_list()
    {
        $result = [
            'code'=>0,
            'data'=>[]
        ];
        $role_id = $this->request->param('role_id', 0, 'intval');
        if(empty($role_id)){
            return json($result);
        }
        //根据role_id查询权限
        if($role_id==1){//1是超级管理员,直接显示全部
            $result = [
                'code'=>200,
                'data'=>$this->menu_all_lists()
            ];
            return $result;
        }
        $new_list = $this->get_role_menu_data($role_id);
        $result = [
            'code'=>200,
            'data'=>$new_list
        ];
        return json($result);
    }

    /**
     * 根据不同的角色id获取可以访问的菜单页面
     * by:byh-20210624
     * @param $role_ids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_role_menu_data($role_ids)
    {
        $new_list = [];
        $map['role_id'] = ['IN',$role_ids];
        $role_data = Db::name('auth_access')->field('rule_name')->where($map)->select();
        //循环查询处理菜单展示数据
        if(!empty($role_data)){
            foreach ($role_data as $k => $v){
                $arr = explode("/", $v['rule_name']);
                $where['app'] = $arr[0];
                $where['controller'] = $arr[1];
                $where['action'] = $arr[2];
                $where['type'] = 1;
                $where['status'] = 1;
                $where['parent_id'] = ['NOTIN',[0,1,110,193,168,198]];
                $name = Db::name('admin_menu')->where($where)->value('name');
                if(!empty($name)){
                    $new_list[$k]['name'] = $name;
                    $new_list[$k]['page_url'] = $v['rule_name'];
                }
            }
        }

        return $new_list;
    }

    public function check_login_code()
    {

    }

}
