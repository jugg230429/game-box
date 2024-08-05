<?php

namespace app\promote\controller;

use app\common\controller\BaseController;
use app\common\logic\SupportLogic;
use app\common\model\SupportModel;
use cmf\controller\AdminBaseController;
use think\Db;

class SupportController extends AdminBaseController
{
    private $lSupport;

    public function __construct()
    {
        parent ::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this -> error('请购买渠道权限');
            } else {
                $this -> error('请购买渠道权限', url('admin/main/index'));
            };
        }
        $this -> lSupport = new SupportLogic();
    }


    /**
     * @扶持发放列表
     *
     * @author: zsl
     * @since: 2020/9/15 20:08
     */
    public function lists()
    {

        $base = new BaseController();
        $model = new SupportModel();
        //添加搜索条件
        $data = $this -> request -> param();
        $map = [];
        if($data['promote_id'] > 0){
            $map['promote_id'] = $data['promote_id'];
        }
        if($data['game_id'] > 0){
            $map['game_id'] = $data['game_id'];
        }
        if($data['support_type'] != ''){
            $map['support_type'] = $data['support_type'];
        }
        if($data['status'] != ''){
            $map['status'] = $data['status'];
        }
        //区服筛选
        if($data['server_id'] != ''){
            $map['server_id'] = $data['server_id'];
        }
        $exend['order'] = 'create_time desc';
        $exend['field'] = '*';
        $data = $base -> data_list($model, $map, $exend);
        // 获取分页显示
        $page = $data -> render();
//        dump($data);


        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach ($data as $k5 => $v5) {
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }


        $this -> assign("data_lists", $data);
        $this -> assign("page", $page);
        //扶持申请自动审核
        $autostatus = Db ::table('tab_promote_config') -> where(array('name' => 'promote_auto_audit_support')) -> value('status');
        $this -> assign('autostatus', $autostatus);
        return $this -> fetch();
    }


    /**
     * @审核扶持
     *
     * @author: zsl
     * @since: 2020/9/16 9:13
     */
    public function audit()
    {
        $param = $this -> request -> param();
        $data['status'] = '1';
        $data['audit_time'] = time();
        $res = $this -> lSupport -> reStatus(['id' => $param['id']], $data);
        if ($res) {
            $this -> success('审核成功');
        } else {
            $this -> error('审核失败');
        }
    }

    /**
     * @发放扶持
     *
     * @author: zsl
     * @since: 2020/9/16 9:21
     */
    public function send()
    {
        $param = $this -> request -> param();
        $data['status'] = 3;
        $data['send_num'] = $param['send_num'];
        $res = $this -> lSupport -> reStatus(['id' => $param['id']], $data);
        if ($res) {
            $this -> success('发放成功');
        } else {
            $this -> error('发放失败');
        }
    }


    /**
     * @拒绝扶持
     *
     * @author: zsl
     * @since: 2020/9/16 9:36
     */
    public function deny()
    {
        $param = $this -> request -> param();
        $data['status'] = 2;
        $data['audit_time'] = time();
        $data['audit_idea'] = $param['audit_idea'];
        $res = $this -> lSupport -> reStatus(['id' => $param['id']], $data);
        if ($res) {
            $this -> success('拒绝成功');
        } else {
            $this -> error('拒绝失败');
        }
    }

    //

    /**
     * 批量操作审核通过或驳回
     * by:byh-20210428
     */
    public function changeStatus()
    {
        $ids = $this->request->param('ids/a');
        if (empty($ids)) $this->error('请选择要操作的数据');
        $status = $this->request->param('status', 0, 'intval');
        $arr = $this->request->param();
        if($status<1){
            $this->error('状态丢失');
        }
        $save['status'] = $status;
        $save['audit_time'] = time();
        if(isset($arr['audit_idea'])){
            $save['audit_idea'] = $arr['audit_idea'];
        }
        $model = new SupportModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $map['id'] = $value;
            $result = $model->where($map)->update($save);
            if ($result === false) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }


    /**
     * @自动审核状态
     *
     * @author: zsl
     * @since: 2020/9/16 9:36
     */
    public function changeAutoAudit()
    {
        $param = $this -> request -> param();
        if ($param['status'] == '0') {
            Db ::table('tab_promote_config') -> where(['name' => 'promote_auto_audit_support']) -> update(['status' => 1]);
        } else {
            Db ::table('tab_promote_config') -> where(['name' => 'promote_auto_audit_support']) -> update(['status' => 0]);
        }
        $this -> success('修改成功');
    }


}
