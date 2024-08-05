<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;

use app\member\model\WarningModel;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;
use think\Request;

class WarningController extends AdminBaseController
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

    /**
     * @函数或方法说明
     * @会员购买记录
     * @author: 郭家屯
     * @since: 2020/2/15 13:54
     */
    public function lists_old()
    {
        $base = new BaseController();
        $model = new WarningModel();
        $map['status'] = ['in',[0,1]];
        $account = $this->request->param('user_account/s');
        if ($account) {
            $map['user_account'] = ['like', '%' . $account . '%'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $type = $this->request->param('type');
        if($type){
            $map['type'] = $type;
        }
        $target = $this->request->param('target');
        if($target){
            $map['target'] = $target;
        }
        $status = $this->request->param('status');
        if($status != ''){
            $map['status'] = $status;
        }
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @会员购买记录
     * @author: byh-20210615
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new WarningModel();
        $map['tab_warning.status'] = ['in',[0,1]];
        $account = $this->request->param('user_account/s');
        if ($account) {
            $map['tab_warning.user_account'] = ['like', '%' . $account . '%'];
        }
        $start_time = $this->request->param('start_time', '');
        $end_time = $this->request->param('end_time', '');
        if ($start_time && $end_time) {
            $map['tab_warning.create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['tab_warning.create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['tab_warning.create_time'] = ['egt', strtotime($start_time)];
        }
        $type = $this->request->param('type');
        if($type){
            $map['tab_warning.type'] = $type;
        }
        $target = $this->request->param('target');
        if($target){
            $map['tab_warning.target'] = $target;
        }
        $status = $this->request->param('status');
        if($status != ''){
            $map['tab_warning.status'] = $status;
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $view_game_ids = Db::name('user')->where('id',session('ADMIN_ID'))->value('view_game_ids');
        if(!empty($view_game_ids)){
            $map['u.fgame_id'] = ['IN',$view_game_ids];
        }
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end
        $extend['field'] = 'tab_warning.*';

        $extend['order'] = 'tab_warning.id desc';
        $extend['join1'] = [['tab_user' => 'u'], 'u.id=tab_warning.user_id', 'left'];
        $data = $base->data_list_join($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @忽略
     * @author: 郭家屯
     * @since: 2020/8/12 14:54
     */
    public function ignore()
    {
        $id = $this -> request -> param('id');
        $ids = $this -> request -> param('ids/a');
        if (empty($id) && empty($ids)) {
            $this -> error('请选择要操作的数据');
        }
        $model = new WarningModel();
        $result = false;
        if (!empty($id)) {
            $result = $model -> where('id', $id) -> setField('status', 2);
        }
        if (!empty($ids)) {
            $result = $model -> where(['id' => ['in', $ids]]) -> setField('status', 2);
        }
        if ($result) {
            $this -> success('操作成功');
        } else {
            $this -> error('操作失败');
        }
    }


}
