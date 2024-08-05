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
use app\admin\model\AdminMenuModel;
use app\site\model\PortalPostModel;
use think\Request;

class IndexController extends AdminBaseController
{

    public function initialize()
    {
        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);//设置后台登录加密码
                cookie('__LOGIN_BY_CMF_ADMIN_PW__', 1, 86400);
            }
        }

        parent::initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        $content = hook_one('admin_index_index_view');
        if (!empty($content)) {
            return $content;
        }
        $adminMenuModel = new AdminMenuModel();
        $menus = cache('admin_menus_' . cmf_get_current_admin_id(), '', null, 'admin_menus');
        if (empty($menus)) {
            $menus = $adminMenuModel->menuTree();
            cache('admin_menus_' . cmf_get_current_admin_id(), $menus, null, 'admin_menus');
        }
        $this->assign("menus", $menus);
        $result = Db::name('AdminMenu')->order(["app" => "ASC", "controller" => "ASC", "action" => "ASC"])->select();
        $menusTmp = array();
        foreach ($result as $item) {
            //去掉/ _ 全部小写。作为索引。
            $indexTmp = $item['app'] . $item['controller'] . $item['action'];
            $indexTmp = preg_replace("/[\\/|_]/", "", $indexTmp);
            $indexTmp = strtolower($indexTmp);
            $menusTmp[$indexTmp] = $item;
        }
        $this->assign("menus_js_var", json_encode($menusTmp));
        //$admin = Db::name("user")->where('id', cmf_get_current_admin_id())->find();
        //$this->assign('admin', $admin);
        //获取更新信息
        include_once APP_PATH . 'upgrade/common.php';;
        $res = check_upgrade();
        $version_list = vesion_list($res[0]['from'], $res[1], 1);
        $upgrade_data = json_decode($version_list, true);
        $this -> assign('upgrade_data', $upgrade_data);
        // 当前管理员的 role
        $admin_id = session('ADMIN_ID');
        $role_id = Db::table('sys_role_user')->where(['user_id'=>$admin_id])->find()['role_id'];

        // 获取一篇公告内容 ---------------------------------- START
        $portalPostModel = new PortalPostModel();
        $gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                -> where(['cp.category_id' => 4, 'p.delete_time' => 0, 'p.website' => 17, 'p.role_ids' => ['like', '%"' . $role_id . '"%']])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                ->order('p.sort desc,p.id desc')
                ->limit(0,2)
                ->select()->toArray();

        $this->assign('gonggao_info', $gonggaoInfo);
        // $this->assign('post_data', $postData);
        //每日显示一次
        $today_time = date('Y_m_d',time());
        $today_shown = cache($today_time.'show_gonggao_times_'.$admin_id);
       
        $this->assign('admin_id', $admin_id);

        // 获取一篇公告内容 ---------------------------------- END

        return $this->fetch();
    }

    // 获取文章的上一篇下一篇 by wjd 2021-7-13 10:13:34
    public function getPreviousNextArticle(Request $request)
    {
        $param = $request->param();
        $tmp_type = $param['tmp_type']; // 1 获取上一篇  2获取下一篇
        $post_sort = $param['post_sort'];
        $post_id = $param['post_id'];
        $portalPostModel = new PortalPostModel();
        // 当前管理员的 role
        $admin_id = cmf_get_current_admin_id();
        $role_id = Db::table('sys_role_user')->where(['user_id'=>$admin_id])->find()['role_id'];

        $d_gonggaoInfo = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                ->where(['cp.category_id'=>4,'p.delete_time'=>0, 'p.id'=>$post_id])
                ->where(['p.post_status'=>1])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                ->order('p.sort desc')
                ->find();
        $all_gonggao = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail,cp.category_id,cp.post_id')
                -> where(['cp.category_id' => 4, 'p.delete_time' => 0, 'website' => 17, 'p.role_ids' => ['like', '%"' . $role_id . '"%']])
                ->where(['p.post_status'=>1])
                // ->where(['p.role_ids'=>['like','%'.$role_id.'%']])
                // ->join(['tab_game' => 'g'], 'g.relation_game_id=p.game_id','left')
                ->join(['sys_portal_category_post' => 'cp'], 'cp.post_id=p.id','right')
                ->order('p.sort desc p.id desc')
                ->select()->toArray();

        foreach($all_gonggao as $k=>$v){
            if ($d_gonggaoInfo['id'] == $v['id']) {
                //获取下一篇
                if (isset($all_gonggao[$k+1])) {
                    $next_id = $all_gonggao[$k+1]['id'];
                } else {
                    $next_id = '';
                }
                //获取上一篇
                if (isset($all_gonggao[$k-1])) {
                    $pre_id = $all_gonggao[$k-1]['id'];
                } else {
                    $pre_id = '';
                }
            }
        }

        if($next_id != ''){
            $next_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$next_id])
                ->find();
        }else{
            $next_info = '';
        }

        if($pre_id != ''){
            $pre_info = $portalPostModel
                ->alias('p')
                ->field('p.id,p.sort,p.post_title,p.update_time,p.create_time,p.post_content,p.game_id,p.thumbnail')
                ->where(['p.id'=>$pre_id])
                ->find();
        }else{
            $pre_info = '';
        }
        $mixData = [
            'd_gonggaoInfo'=>$d_gonggaoInfo,
            'next_gonggaoInfo'=>$pre_info,
            'previous_gonggaoInfo'=>$next_info,
        ];
        // var_dump($mixData);  exit;
        return json(['code'=>1, 'msg'=>'获取成功', 'data'=>$mixData]);

    }
}
