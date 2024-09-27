<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;
use app\recharge\logic\RebateLogic;
use app\recharge\model\CouponModel;
use app\recharge\model\CouponRecordModel;
use app\recharge\validate\CouponValidate;
use cmf\controller\AdminBaseController;
use app\common\controller\BaseController;
use think\Db;

//该控制器必须以下3个权限
class CouponController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_GAME != 1) {
            $this->error('请购买游戏权限', url('admin/main/index'));
        }
        if (AUTH_PROMOTE != 1) {
            $this->error('请购买渠道权限', url('admin/main/index'));
        }
    }

    /**
     * @函数或方法说明
     * @代金券设置列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/1/10 17:27
     */
    public function lists()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $data = $logic->getCouponLists($request);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @新增代金券设置
     * @author: 郭家屯
     * @since: 2019/12/30 11:06
     */
    public function add()
    {
        if($this->request->isPost()){
            $data = $this->request->param();
            $validate = new CouponValidate();

            if(!$validate->check($data)){
                $this->error($validate->getError());
            }
            $data['create_time'] = time();
            if(empty($data['start_time'])){
                $data['start_time'] = time();
            }else{
                $data['start_time'] = strtotime($data['start_time']);
            }
            $data['end_time'] = $data['end_time'] ? strtotime($data['end_time']) : 0;
            if(empty($data['receive_start_time'])){
                $data['receive_start_time'] = time();
            }else{
                $data['receive_start_time'] = strtotime($data['receive_start_time']);
            }
            $data['receive_end_time'] = $data['receive_end_time'] ? strtotime($data['receive_end_time']) : 0;
            if($data['receive_end_time'] > 0 && $data['end_time'] >0 && $data['receive_end_time'] > $data['end_time']){
                $this->error('可领取时间不能大于过期时间');
            }
            if($data['receive_end_time'] == 0 && $data['end_time'] != 0){
                $this->error('可领取时间不能大于过期时间');
            }
            $logic = new RebateLogic();
            $result = $logic->addCoupon($data);
            if($result){
                $this->success('添加成功',url('lists'));
            }else{
                $this->error('添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑代金券列表
     * @author: 郭家屯
     * @since: 2020/1/10 9:39
     */
    public function edit()
    {
        $id = $this->request->param('id');
        if(empty($id))$this->error('参数错误');
        $logic = new RebateLogic();
        $detail = $logic->get_coupon_detail($id);
        if($this->request->isPost()){
            $data = $this->request->param();
            $logic = new RebateLogic();
            $result = $logic->editCoupon($data);
            if($result){
                $this->success('修改成功',url('lists'));
            }else{
                $this->error('修改失败');
            }
        }
        if($detail['type'] == 4){
            //获取推广员页面
            $detail['promote_lists'] = Db::table('tab_coupon_promote')->field('promote_id')->where('coupon_id',$detail['id'])->select()->toArray();
            $lists = array_flip(array_column($detail['promote_lists'],'promote_id'));
            $this->assign('lists',$lists);
            $map['status'] = 1;
            $map['parent_id'] = 0;
            $promote_list = get_promote_list($map,"id,account");
            $this->assign('promote_list',$promote_list);
            $promote_choose = $this->fetch("promote_choose");
            $this->assign('promote_choose',$promote_choose);
        }
        $this->assign('data',$detail);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取推广员
     * @return \think\response\Json
     *
     * @author: 郭家屯
     * @since: 2020/1/9 16:40
     */
    public function ajaxGetPromote(){
        if($this->request->isAjax()){
            $map['status'] = 1;
            //$map['parent_id'] = 0;
            $promote_list = get_promote_list($map,"id,account,promote_level");
            $this->assign('promote_list',$promote_list);
            $choose = $this->fetch("promote_choose");
            $this->success($choose);
        }
    }


    /**
     * [删除代金券]
     *
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $id = $this -> request -> param('id/d');
        $ids = $this -> request -> param('ids/a');
        if (empty($id) && empty($ids)) $this -> error('请选择要操作的数据');
        $model = new CouponModel();
        if (!empty($id)) {
            $result = $model -> where('id', $id) -> delete();
        }
        if (!empty($ids)) {
            $result = $model -> where(['id' => ['in', $ids]]) -> delete();
        }
        if ($result) {
            $this -> success('删除成功');
        } else {
            $this -> error('删除失败');
        }
    }

    /**
     * [回收代金券]
     * @author 郭家屯[gjt]
     */
    public function recorddel()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new CouponRecordModel();
        $result = $model->where('id', 'in', $ids)->setField('is_delete',1);
        if ($result) {
            if (count($ids) > 1) {
                write_action_log("批量回收代金券记录");
            } else {
                write_action_log("回收代金券记录");
            }
            $this->success('回收成功');
        } else {
            $this->error('回收失败');
        }
    }

    /**
     * @函数或方法说明
     * @代金券记录
     * @author: 郭家屯
     * @since: 2020/1/10 17:34
     */
    public function record()
    {
        $logic = new RebateLogic();
        $request = $this->request->param();
        $map['pid'] = 0;
        $data = $logic->getCouponRecord($request,$map);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }
        $this->assign("page", $page);
        $total = $logic->get_coupon_total($request,$map);
        $this->assign("total", $total[0]);//累计统计
        return $this->fetch();
    }


    /**
     * [修改折扣状态]
     * @author 郭家屯[gjt]
     */
    public function changeStatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('请选择要操作的数据');
        $data = $this->request->param();
        $logic = new RebateLogic();
        $result = $logic->changeCouponStatus($data);
        if ($result) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @发放代金券
     * @author: 郭家屯
     * @since: 2020/2/4 11:41
     */
    public function grant()
    {
        $logic = new RebateLogic();
        if($this->request->isPost()){
            $data = $this->request->param();
            if(!$data['account']){
                $this->error('请选择用户');
            }
            $user_id = Db::table('tab_user')->where('account',$data['account'])->value('id');
            if(!$user_id){
                $this->error('账号对应用户不存在');
            }
            $data['user_id'] = $user_id;
            if(!$data['coupon_id']){
                $this->error('请选择优惠券');
            }

            $result = $logic->grant($data);
            if ($result) {
                $this->success('发放成功',url('record'));
            } else {
                $this->error('发放失败');
            }
        }
        //获取
        $coupon = $logic->getCoupon(1);
        $this->assign('coupon',$coupon);
        $this->assign('jscoupon',$coupon);
        return $this->fetch();
    }


}
