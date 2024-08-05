<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/25
 * Time: 14:35
 */

namespace app\member\controller;
use app\common\controller\BaseController;
use app\member\model\UserAwardModel;
use app\member\model\UserAwardRecordModel;
use cmf\controller\AdminBaseController;
use think\Db;
use think\Request;

class AwardController extends AdminBaseController
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
    public function lists()
    {
        $model = new UserAwardModel();
        $base = new BaseController();
        $extend['order'] = 'id asc';
        $data = $base->data_list_select($model, [], $extend);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @编辑抽奖产品
     * @author: 郭家屯
     * @since: 2020/8/5 14:29
     */
    public function edit()
    {
        $id = $this->request->param('id');
        $model = new UserAwardModel();
        if($this->request->isPost()){
            $request = $this->request->param();
            $data = $this->create_data($request);
            $award = $model->where('id',$request['id'])->find()->toArray();
            Db::startTrans();
            try{
                if($award['type'] == 3){
                    Db::table('tab_coupon')->where('id',$award['award'])->setField('coupon_type',0);
                }
                if($request['type'] == 3){
                    Db::table('tab_coupon')->where('id',$request['award'])->setField('coupon_type',5);
                }
                $model->where('id',$id)->update($data);
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error('修改失败');
            }
            $this->success('修改成功',url('lists'));
        }
        $data = $model->where('id',$id)->find();
        if(!$data){
            $this->error('参数错误');
        }
        $data = $data->toArray();
        $this->assign('data',$data);
        return $this->fetch();
    }




    /**
     * @函数或方法说明
     * @抽奖记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 15:05
     */
    public function record()
    {
        $user_account = $this->request->param('user_account', '');
        if ($user_account != '') {
            $map['user_account'] = ['like', '%' . $user_account . '%'];
        }
        $name = $this->request->param('name', '');
        if ($name != '') {
            $map['name'] = ['like', '%' . $name . '%'];
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
        $model = new UserAwardRecordModel();
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
     * @抽奖设置
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/29 17:41
     */
    public function award_set()
    {
        $data = cmf_get_option('award_set');
        $data['vip_level'] = $data['vip_level'] ? explode(',',$data['vip_level']):[];
        $data['count'] = $data['count'] ? explode(',',$data['count']):[];
        $this->assign('data', $data);
        $this->assign("name", 'award_set');
        return $this->fetch();
    }


    /**
     * [站点设置保存]
     * @author 郭家屯[gjt]
     */
    public function sitePost()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['user_point'] = $data['user_point'] < 1 ? 1 : $data['user_point'];
            if(empty($data['free_draw'])){
                $this->error('每日免费抽奖不能为空');
            }
            if(empty($data['draw_limit'])){
                $this->error('每日积分抽奖上限不能为空');
            }
            if($data['vip_level']){
                sort($data['vip_level']);
                sort($data['count']);
                $data['vip_level'] = implode(',',$data['vip_level']);
                $data['count'] = implode(',',$data['count']);
            }
            $set_type = $data['set_type'];
            cmf_set_option($set_type, $data);
            write_action_log("抽奖设置");
            $this->success("保存成功！");
        }
    }

    /**
     * @函数或方法说明
     * @生成更新数据
     * @param $request
     *
     * @author: 郭家屯
     * @since: 2020/8/6 14:42
     */
    protected function create_data($request=[])
    {
        $data['probability'] = (int)$request['probability'];
        $data['cover'] = $request['cover']?:'';
        $data['type'] = $request['type'];
        $data['stock'] = $request['stock'];
        switch ($request['type']){
            case 1:
                $data['award'] = (int)$request['award_point'];
                if($data['award'] < 1){
                    $this->error('积分为正整数');
                }
                $data['name'] = $data['award'].'积分';
                break;
            case 2:
                $data['award'] = (int)$request['award_balance'];
                if($data['award'] < 1){
                    $this->error('平台币为正整数');
                }
                $data['name'] = $data['award'].'平台币';
                break;
            case 3:
                $data['award'] = (int)$request['coupon_id'];
                if($data['award'] < 1){
                    $this->error('请选择代金券');
                }
                $coupon = get_coupon_info($request['coupon_id'],'id,coupon_name');
                $data['name'] = $coupon['coupon_name'];
                break;
            case 4:
                $data['award'] = '';
                $data['name'] = $request['name'];
                if(empty($data['name'])){
                    $this->error('奖品名称不能为空');
                }
                break;
            case 5:
                $data['award'] = 0;
                $data['stock'] = 1;
                $data['name'] = '谢谢惠顾';
                break;
            default:
                $this->error('奖品分类不存在');
        }
        return $data;
    }

}
