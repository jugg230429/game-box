<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\recharge\controller;

use app\recharge\model\SpendModel;
use app\common\model\DateListModel;
use app\common\controller\BaseController;
use app\recharge\model\SpendPromoteParamModel;
use app\recharge\model\SpendWxparamModel;
use app\recharge\validate\PromoteParamValidate;
use app\recharge\validate\WxparamValidate;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

class PaytypeController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_PAY != 1) {
            $this->error('请购买充值权限', url('admin/main/index'));
        }
        if (AUTH_USER != 1) {
            $this->error('请购买用户权限', url('admin/main/index'));
        }

    }

    public function lists()
    {
        $data = Db::table('tab_spend_payconfig')->field('id,name,config,status')->select()->toarray();
        foreach ($data as $key => $value) {
            $value['config'] = json_decode($value['config'], true);
            $this->assign($value['name'], $value);
        }

        //渠道配置页面
        $base = new BaseController();
        $param = $this->request->param();
        if ($param['game_id']) {
            $map['game_id'] = $param['game_id'];
            $this->assign('is_promote',1);
        }
        $model = new SpendPromoteParamModel();
        $data = $base->data_list($model, $map)->each(function ($item, $key) {
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('data_lists', $data);
        $this->assign('promote_list',$this->promoteList());
        $this->assign('type_list',$this->typeList());
        $this->assign("page", $page);
        return $this->fetch();
    }

    public function set_pay()
    {
        $post = $this->request->param();
        $data['zfb'] = $post['zfb'];
        $data['wxscan'] = $post['wx'];
        $data['wxapp'] = $post['wxapp'];
        $data['yl'] = $post['yl'];
        $data['ysf'] = $post['ysf'];
        $data['szrmb'] = $post['szrmb'];
        $data['rgcz'] = $post['rgcz'];
        $data['goldpig'] = $post['jz'];
        $data['ptb_pay'] = $post['ptb_pay'];
        $data['bind_pay'] = $post['bind_pay'];
        $data['zfb_tx'] = $post['zfb_tx'];
        foreach ($data as $key => $value) {
            $map = [];
            $save = [];
            $map['name'] = $key;
            $save['status'] = $value['status'] ? 1 : 0;
            unset($value['status']);
            $save['config'] = json_encode($value);
            Db::table('tab_spend_payconfig')->where($map)->update($save);
        }
        write_action_log("支付配置设置");
        $this->success('保存成功');
    }

    /**
     * @函数或方法说明
     * @微端微信支付
     * @author: 郭家屯
     * @since: 2020/7/10 10:20
     */
    public function weiduan()
    {
        $base = new BaseController();
        $param = $this->request->param();
        if ($param['game_id']) {
            $map['game_id'] = $param['game_id'];
        }
        $model = new SpendWxparamModel();
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
     * @函数或方法说明
     * @添加微端设置
     * @author: 郭家屯
     * @since: 2020/7/10 11:23
     */
    public function add()
    {
        $param = $this->request->param();

        if($this->request->isPost()){
            $validata = new PromoteParamValidate();
            if(!$validata->check($param)){
                $this->error($validata->getError());
            }
            $model = new SpendPromoteParamModel();
            //校验最小大金额
            if($param['max_amount'] < $param['min_amount']){
                $this->error('最大金额设置错误');
            }
            if($param['game_id'] != 0){
                $param['game_name'] = get_game_entity($param['game_id'],'game_name')['game_name'];
            }else{
                $param['game_name'] = '默认';
            }
      
            $param['create_time'] = time();
            $result = $model->insert($param);
            if($result){
                $this->success('新增成功',url('promote'));
            }else{
                $this->error('新增失败');
            }
        }else{
            //获取当前支付渠道
            $this->assign('promote_list',$this->promoteList());
            $this->assign('type_config_list',$this->typeConfigList());
            return $this->fetch();
        }
    }

    private function promoteList(){
        $list = [
           1 => [ 'promote_id' => 1, 'promote_name' => '鼎盛支付'],
           2 => [ 'promote_id' => 2, 'promote_name' => '蚂蚁支付'],
           3 => [ 'promote_id' => 3, 'promote_name' => '彩虹易支付'],
           4 => [ 'promote_id' => 4, 'promote_name' => 'hipay支付'],
           5 => [ 'promote_id' => 5, 'promote_name' => '大头支付'],
        ];
        return $list;
    }

    private function typeList(){
        $list = [
            1 => '支付宝',
            2 => '微信',
            3 => '银联',
            4 => '云闪付',
            5 => '数字人民币',
        ];
        return $list;
    }

    private function typeConfigList(){
        $list = [
           1 => [ 'type_id' => 1, 'type_name' => '支付宝'],
           2 => [ 'type_id' => 2, 'type_name' => '微信'],
           3 => [ 'type_id' => 3, 'type_name' => '银联'],
           4 => [ 'type_id' => 4, 'type_name' => '云闪付'],
           5 => [ 'type_id' => 5, 'type_name' => '数字人民币'],
        ];
        return $list;
    }

    /**
     * @函数或方法说明
     * @编辑
     * @author: 郭家屯
     * @since: 2020/7/10 13:42
     */

    public function edit()
    {
        $id = $this->request->param('id');
        $model = new SpendPromoteParamModel();
        if($this->request->isPost()){
            $param = $this->request->param();
            $validata = new PromoteParamValidate();
            if(!$validata->scene('edit')->check($param)){
                $this->error($validata->getError());
            }
            //校验最小大金额
            if($param['max_amount'] < $param['min_amount']){
                $this->error('最大金额设置错误');
            }
            $result = $model->where('id',$id)->update($param);
            if($result){
                $this->success('编辑成功',url('promote'));
            }else{
                $this->error('编辑失败');
            }
        }else{
            $data = $model->find($id);
            $this->assign('data',$data);
            //获取当前支付渠道
            $this->assign('promote_list',$this->promoteList());
            $this->assign('type_config_list',$this->typeConfigList());
            return$this->fetch();
        }
    }

    /**
     * @函数或方法说明
     * @微端微信支付
     * @author: 郭家屯
     * @since: 2020/7/10 10:20
     */
    public function promote()
    {
         //渠道配置页面
         $base = new BaseController();
         $param = $this->request->param();
         if ($param['game_id']) {
             $map['game_id'] = $param['game_id'];
             $this->assign('is_promote',1);
         }
         $model = new SpendPromoteParamModel();
         $data = $base->data_list($model, $map)->each(function ($item, $key) {
             $item['status_name'] = get_info_status($item['status'], 4);
         });
         // 获取分页显示
         $page = $data->render();
         $this->assign('data_lists', $data);
         $this->assign('promote_list',$this->promoteList());
         $this->assign('type_list',$this->typeList());
         $this->assign("page", $page);
         return $this->fetch();
    }

    public function del()
    {
        $ids = $this->request->param('ids/a');
        if(!$ids)$this->error('请选择要操作的数据');
        $model = new SpendPromoteParamModel();
        $result = $model->where('id','in',$ids)->delete();
        if($result){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}
