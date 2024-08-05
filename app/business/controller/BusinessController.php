<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\business\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use app\promote\logic\PromoteLogic;
use app\promote\model\PromoteModel;
use app\business\model\PromoteBusinessModel;
use app\business\validate\BusinessValidate;
use think\Request;
use think\Db;

class BusinessController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PROMOTE != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买渠道权限');
            } else {
                $this->error('请购买渠道权限', url('admin/main/index'));
            };
        }
    }

    /**
     * [渠道列表]
     * @return mixed
     * @author yyh
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new PromoteBusinessModel();
        //添加搜索条件
        $data = $this->request->param();
        $busier_id = $data['busier_id'];
        if ($busier_id) {
            $map['id'] = $busier_id;
        }
        $account = $data['account'];
        if ($account) {
            $map['account'] = $account;
        }

        $exend['order'] = 'create_time desc';
        $exend['field'] = 'id,account,real_name,mobile_phone,qq,create_time,last_login_time,status,promote_ids';
        $data = $base->data_list($model, $map, $exend)->each(function($item,$key){
            $promote_ids = array_column(get_busier_promote_list(['busier_id'=>$item['id']]),'id');
            $item['total_register'] = 0;
            $item['total_pay'] = '0.00';
            if(!empty($promote_ids)){
                $start_time = $this->request->param('start_time', '');
                $end_time = $this->request->param('end_time', '');
                if ($start_time && $end_time) {
                    $totalpay['pay_time'] = $totalreg['register_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $totalpay['pay_time'] = $totalreg['register_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $totalpay['pay_time'] = $totalreg['register_time'] = ['egt', strtotime($start_time)];
                }
                $logic = new PromoteLogic();
                $item['total_register'] = $logic->get_total_register($promote_ids,$totalreg);
                $item['total_pay'] = $logic->get_total_pay($promote_ids,$totalpay);
            }
            return $item;
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * [add 新增渠道]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new BusinessValidate();
            if (!$validate->scene('add')->check($data)) {
                $this->error($validate->getError());
            }
            $add['account'] = $data['account'];
            $add['status'] = $data['status'];
            $add['real_name'] = $data['real_name'];
            $add['mobile_phone'] = $data['mobile_phone'];
            $add['qq'] = $data['qq'];
            $add['password'] = cmf_password($_POST['password']);
            $add['create_time'] = time();
            $model = new PromoteBusinessModel();
            $result = $model->field(true)->insert($add);
            if ($result) {
                $this->success('添加成功', url('lists'));
            } else {
                $this->error('添加失败');
            }


        }
        return $this->fetch();
    }

    public function edit()
    {
        $model = new PromoteBusinessModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new BusinessValidate();
            $valresult = $validate->scene('edit')->check($data);
            if (!$valresult) {
                $this->error($validate->getError());
            }
            $save['status'] = $data['status'];
            $save['real_name'] = $data['real_name'];
            $save['mobile_phone'] = $data['mobile_phone'];
            $save['pattern'] = $data['pattern'];
            $save['qq'] = $data['qq'];
            $save['email'] = $data['email'];
            if ($data['password'] != '') {
                $save['password'] = cmf_password($data['password']);
            }
            if ($data['second_pwd'] != '') {
                $save['second_pwd'] = cmf_password($data['second_pwd']);
            }
            $save['mark1'] = $data['mark1'];
            $save['id'] = $data['id'];
            $result = $model->field(true)->update($save);
            if ($result !== false) {
                $this->success('编辑成功', url('lists'));
            } else {
                $this->error('编辑失败');
            }
        } else {
            $id = $this->request->param('id');
            if ($id > 0) {
                $data = $model->field('id,account,status,real_name,mobile_phone,qq,create_time,last_login_time,mark1')->where('id', $id)->find();
                if (empty($data)) {
                    $this->error('没有该数据', url('lists'));
                }
                $this->assign('data', $data);
                return $this->fetch();
            } else {
                $this->error('缺少id', url('lists'));
            }
        }
    }

    public function get_business_promote_ids($business_id='')
    {
        if(empty($business_id)){
            return $this->fetch();
        }
        $model = new PromoteBusinessModel();
        $promote_ids = $model->field('account,promote_ids')->find($business_id)->toArray();
        if(empty($promote_ids)){
            return $this->fetch();
        }
        $this->assign('user',$promote_ids['account']);
        $promote_ids = explode(',',$promote_ids['promote_ids']);
        $map['id'] = ['in',$promote_ids];
        $promote = new PromoteModel();
        $data = $promote->field('id,account')->where($map)->order('id desc')->select()->toArray();
        if(!empty($data)){
            $this->assign('data',$data);
        }
        return $this->fetch();
    }
    /**
     * [修改审核状态]
     * @author yyh
     */
    public function changeStatus()
    {
        $data = $this->request->param();
        $ids = $data['ids'];
        if (empty($ids)) $this->error('请选择要操作的数据');
        if (!is_array($ids)) {
            $id = $ids;
            $ids = [];
            $ids[] = $id;
        }
        $status = $this->request->param('status', 0, 'intval');
        $save['status'] = $status == 1 ? -1 : 1;
        $model = new PromoteBusinessModel();
        Db::startTrans();
        foreach ($ids as $key => $value) {
            $map['id'] = $value;
            $result = $model->where($map)->update($save);
            if (!$result) {
                Db::rollback();
                $this->error('操作失败');
            }
        }
        Db::commit();
        $this->success('操作成功');
    }

}