<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;
use app\common\controller\BaseController;
use app\member\logic\PointUseLogic;
use app\member\model\PointRecordModel;
use app\member\model\PointShopModel;
use app\member\model\PointTypeModel;
use app\member\model\PointUseModel;
use app\member\validate\PointShopValidate;
use app\member\validate\PointTypeValidate;
use app\member\model\PointShopRecordModel;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;

class PointController extends AdminBaseController
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
     * @积分任务列表
     * @author: 郭家屯
     * @since: 2020/2/15 13:54
     */
    public function task()
    {
        $model = new PointTypeModel();
        $base = new BaseController();
        $extend['field'] = 'tab_user_point_type.*,sum(r.point) as totalpoint';
        $extend['order'] = 'tab_user_point_type.id asc';
        $extend['join1'] = [['tab_user_point_record'=>'r'],'tab_user_point_type.id=r.type_id','left'];
        $extend['group'] = 'tab_user_point_type.id';
        $data = $base->data_list_join_select($model, $map, $extend);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * [修改类型状态]
     * @author 郭家屯[gjt]
     */
    public function changeTypeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 0 ? 1 : 0;
        $map['id'] = $id;
        $model = new PointTypeModel();
        $result = $model->where($map)->update($save);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @编辑类型
     * @author: 郭家屯
     * @since: 2020/4/24 16:21
     */
    public function edit_type()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $model = new PointTypeModel();
        if($this->request->isPost()){
            $request = $this->request->param();
            $validate = new PointTypeValidate();
            if(!$validate->check($request)){
                $this->error($validate->getError());
            }
            $result = $model->where('id',$id)->update($request);
            if($result !== false){
                $this->success('修改成功',url('point/task'));
            }else{
                $this->error('修改失败');
            }
        }
        $data = $model->where('id',$id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * @函数或方法说明
     * @积分商品列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 10:30
     */
    public function shop()
    {
        $base = new BaseController();
        $model = new PointShopModel();
        $request = $this->request->param();
        if($request['good_name'] != ''){
            $map['good_name'] = ['like','%'.$request['good_name'].'%'];
        }
        if($request['type']){
            $map['type'] = $request['type'];
        }
        if($request['status'] != ''){
            $map['status'] = $request['status'];
        }
        $extend['order'] = 'sort desc,id desc';
        $data = $base->data_list($model, $map, $extend)->each(function($item,$key){
            $item['exchange'] = Db::table('tab_user_point_shop_record')->where('good_id',$item['id'])->sum('number');
            $vip_msg = '';
            if($item['vip_level']){
                $vip_level = explode('/',$item['vip_level']);
                $vip_discount = explode('/',$item['vip_discount']);
                $vip = array_combine($vip_level,$vip_discount);
                foreach ($vip as $kk=>$v){
                    $vip_msg .= 'VIP'.$kk.'('.$v.'%)/';
                }
            }
            $item['vip'] = substr($vip_msg,0,strlen($vip_msg)-1);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @添加商品
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 11:35
     */
    public function add_shop()
    {
        if($this->request->isPost()){
            $request = $this->request->param();
            $validate = new PointShopValidate();
            if(!$validate->check($request)){
                $this->error($validate->getError());
            }
            if($request['vip_level']){
                if(count($request['vip_level']) != count($request['vip_discount'])){
                    $this->error('VIP折扣输入有误');
                }
                sort($request['vip_level']);
                rsort($request['vip_discount']);
                $request['vip_level'] = implode('/',$request['vip_level']);
                $request['vip_discount'] = implode('/',$request['vip_discount']);
            }
            $request['create_time'] = time();
            $model = new PointShopModel();
            $result = $model->insert($request);
            if($result){
                $this->success('添加成功',url('shop'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑积分产品
     * @author: 郭家屯
     * @since: 2020/4/29 14:29
     */
    public function edit_shop()
    {
        $id = $this->request->param('id');
        $model = new PointShopModel();
        if($this->request->isPost()){
            $request = $this->request->param();
            $validate = new PointShopValidate();
            if(!$validate->check($request)){
                $this->error($validate->getError());
            }
            $request['vip_level'] = array_filter($request['vip_level']);
            $request['vip_discount'] = array_filter($request['vip_discount']);
            if($request['vip_level']){
                if(count($request['vip_level']) != count($request['vip_discount'])){
                    $this->error('VI等级与折扣输入有误');
                }
                sort($request['vip_level']);
                rsort($request['vip_discount']);
                $request['vip_level'] = implode('/',$request['vip_level']);
                $request['vip_discount'] = implode('/',$request['vip_discount']);
            }
            $result = $model->where('id',$id)->update($request);
            if($result !== false){
                $this->success('修改成功',url('shop'));
            }else{
                $this->error('修改失败');
            }
        }
        $data = $model->where('id',$id)->find();
        if(!$data){
            $this->error('参数错误');
        }
        $data = $data->toArray();
        $data['vip_level'] = $data['vip_level'] ? explode('/',$data['vip_level']) : [];
        $data['vip_discount'] = $data['vip_discount'] ? explode('/',$data['vip_discount']) : [];
        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * [修改商品状态]
     * @author 郭家屯[gjt]
     */
    public function changeShopStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 0 ? 1 : 0;
        $map['id'] = $id;
        $model = new PointShopModel();
        $result = $model->where($map)->update($save);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * [设置优先级]
     * @author 郭家屯[gjt]
     */
    public function setsort()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $sort = $this->request->param('sort', 0, 'intval');
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '设置失败']);
            }
            $model = new PointShopModel();
            $result = $model->where('id', $id)->setField('sort', $sort);
            if ($result !== false) {
                return json(['code' => 1, 'msg' => '设置成功']);
            } else {
                return json(['code' => 0, 'msg' => '设置失败']);
            }
        }
    }

    /**
     * @函数或方法说明
     * @兑换记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 15:05
     */
    public function shop_record()
    {
        $user_account = $this->request->param('user_account', '');
        if ($user_account != '') {
            $map['user_account'] = ['like', '%' . $user_account . '%'];
        }
        $good_name = $this->request->param('good_name', '');
        if ($good_name != '') {
            $map['good_name'] = ['like', '%' . $good_name . '%'];
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
        $base = new BaseController();
        $model = new PointShopRecordModel();
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach ($data as $k5 => $v5) {
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
     * @发货备注
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/4/29 17:24
     */
    public function setremark()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $remark = $this->request->param('remark/s');
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '设置失败']);
            }
            $model = new PointShopRecordModel();
            $save['remark'] = $remark;
            $save['status'] = 1;
            $result = $model->where('id', $id)->update($save);
            if ($result !== false) {
                return json(['code' => 1, 'msg' => '发货成功']);
            } else {
                return json(['code' => 0, 'msg' => '发货失败']);
            }
        }
    }


    /**
     * @函数或方法说明
     * @积分获取记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 18:00
     */
    public function point_record()
    {
        $user_account = $this->request->param('user_account/s');
        if($user_account != ''){
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        if($this->request->param('type_id/d')!=''){
            $map['type_id'] = $this->request->param('type_id/d');
        }
        $base = new BaseController();
        $model = new PointRecordModel();
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        // 获取分页显示
        $page = $data->render();
        //汇总
        $total = $model->where($map)->sum('point');
        $this->assign('total',$total);
        $this->assign('data_lists', $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @积分使用记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 18:00
     */
    public function point_use()
    {
        $user_account = $this->request->param('user_account/s');
        if($user_account != ''){
            $map['user_account'] = ['like','%'.$user_account.'%'];
        }
        $base = new BaseController();
        $model = new PointUseModel();
        $extend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $extend);
        // 获取分页显示
        $page = $data->render();
        //汇总
        $total = $model->where($map)->sum('point');
        $this->assign('total',$total);

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
     * @删除积分产品
     * @author: 郭家屯
     * @since: 2020/10/12 10:30
     */
    public function del()
    {
        $id = $this->request->param('id');
        if(empty($id)){
            $this->error('请选择要删除的数据');
        }
        $model = new PointShopModel();
        $result = $model->where('id',$id)->delete();
        if($result){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

}
