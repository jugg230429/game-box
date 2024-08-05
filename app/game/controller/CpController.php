<?php
/**
 *
 * CP 管理
 *
 */

namespace app\game\controller;

use app\common\controller\BaseController;
use app\game\model\CpModel;
use app\game\validate\CpValidate;
use app\game\validate\CpSettlementValidate as CpSettleValidate;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

class CpController extends AdminBaseController{
    // 继承父类构造
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * [CP列表]
     * @return mixed
     * @author wjd
     */
    public function lists(){
        // return $this->fetch();
        $cp_name = trim($this->request->param('cp_name'));

        $map = [];
        $extend = [];
        if($cp_name != ''){
            $map['cp_name'] = ['like', '%' . $cp_name . '%'];
        }
        $game_id = $this->request->param('game_id');
        if ($game_id != '') {
            $game_info = Db::table('tab_game')->where("id=$game_id")->find();
            $cp_id = $game_info['cp_id'];
            $map['tab_game_cp.id'] = $cp_id;
        }
        $cp_model = new CpModel();
        $base = new BaseController();
        // if ($game_id != '') {
        //     // 此时一定只有一个CP商,或者没有
        //     // $map['game_id'] = $game_id;
        //     $game_info = Db::table('tab_game')->where("id=$game_id")->find();
        //     $cp_id = $game_info['cp_id'];
        //     $game_ids = Db::table('tab_game')->where("cp_id=$cp_id")->column('id');

        //     $map['id'] = $cp_id;
        //     $cp_data = $base->data_list($cp_model, $map, $exend);
        //     // $cp_data = $cp_model->where("id=$cp_id")->find();
        //     foreach($cp_data as $key => $val){
        //         $val->count_game = count($game_ids);
        //         $val->total_register = 0;
        //         $val->total_recharge = 0.00;
        //         if(!empty($game_ids)){
        //             $string_game_ids = implode(',',$game_ids);
        //             // 总注册数
        //             $total_register = Db::table('tab_user')->where("fgame_id in ($string_game_ids)")->count();
        //             $val->total_register = $total_register;
        //             // 总充值数
        //             $total_recharge = Db::table('tab_spend')->where("game_id in ($string_game_ids)")->sum('pay_amount');
        //             $val->total_recharge = $total_recharge;
        //         }
        //     }

        //     $page = $cp_data->render();
        //     $this->assign('page', $page);
        //     $this->assign('data_lists', $cp_data);
        //     return $this->fetch();

        // }
        $extend['field'] = 'tab_game_cp.id,cp_name,cp_attribute,cp_contact_name,cp_mobile,cp_email,cp_qq,count(g.id) as count_game,tab_game_cp.create_time,tab_game_cp.remark,group_concat(g.id) as game_ids';
        $extend['join1'][] = ['tab_game' => 'g'];
        $extend['join1'][] = 'tab_game_cp.id = g.cp_id';
        $extend['join1'][] ='left';
        $extend['group'] ='tab_game_cp.id';
        $extend['order'] = 'tab_game_cp.id desc';
        $cp_data = $base->data_list_join($cp_model,$map,$extend);
        // 总注册,总充值
        $d_page_register = 0;
        $d_page_recharge = 0;
        foreach($cp_data as $key => $val){
            $val->total_register = 0;
            $val->total_recharge = 0.00;
            if(!empty($val->game_ids)){
                // 总注册数
                $game_ids = $val->game_ids;
                // 不统计小号
                $total_register = Db::table('tab_user')->where("fgame_id in ($game_ids)")->where("puid=0")->count();
                $val->total_register = $total_register;
                // 总充值数
                $total_recharge = Db::table('tab_spend')->where("game_id in ($game_ids) AND pay_status=1")->sum('pay_amount');
                $val->total_recharge = $total_recharge;

                $d_page_register += $total_register;
                $d_page_recharge += $total_recharge;
            }
        }

        $page = $cp_data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $cp_data);
        $this->assign('d_page_register', $d_page_register);
        $this->assign('d_page_recharge', $d_page_recharge);
        return $this->fetch();
    }

    // 添加cp商
    public function add(){
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $validate = new CpValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }

            $cp_model = new CpModel();

            $save_res = $cp_model->isUpdate(false)->save($data);
            if ($save_res) {
                write_action_log("新增CP【" . $data['cp_name'] . "】商");
                $this->success('CP商添加成功', url('lists'));
            } else {
                $this->error('CP商添加失败');
            }
        }
        return $this->fetch();
    }

    // 管理/编辑 cp商信息
    public function edit(){
        $cp_model = new CpModel();
        if ($this->request->post()){
            $data = $this->request->param();
            $option_type = $data['option_type'];
            if($option_type == 1){
                $data_1 = [
                    'id'=>$data['id'],
                    'cp_name'=>$data['cp_name'],
                    'cp_mobile'=>$data['cp_mobile'],
                    'cp_contact_name'=>$data['cp_contact_name'],
                    'cp_email'=>$data['cp_email'],
                    'cp_qq'=>$data['cp_qq'],
                    'cp_attribute'=>$data['cp_attribute'],
                    'remark'=>$data['remark'],
                ];
                $validate = new CpValidate();
                if (!$validate->check($data_1)) {
                    $this->error($validate->getError());
                }
                $update_res = $cp_model->edit($data_1, 1);

                if ($update_res) {
                    // 更新成功
                    $this->success('编辑成功', url('Cp/lists'));
                } else {
                    // 更新失败
                    $this->error('编辑失败,请稍后重试!');
                }
            }
            if($option_type == 2){
                if($data['settlement_type'] == 1){  // 更新银行卡信息
                    $data_2 = [
                        'cp_bank_mobile'=>$data['cp_bank_mobile'],
                        'cp_bank_num'=>$data['cp_bank_num'],
                        'bank_name'=>$data['bank_name'],
                        'cp_bank_username'=>$data['cp_bank_username'],
                        'cp_bank_open_area'=>$data['cp_bank_open_area'],
                        'province'=>$data['province'] ?? '',
                        'city'=>$data['city'] ?? '',
                        'county'=>$data['county'] ?? '',
                    ];
                    $validate = new CpSettleValidate();
                    if (!$validate->check($data_2)) {
                        $this->error($validate->getError());
                    }
                    // 保存
                    $update_res = $cp_model->edit(['id'=>$data['id'],'settlement_type'=>1, 'bank_info'=>json_encode($data_2, JSON_UNESCAPED_UNICODE)], 2);
                    if ($update_res) {
                        // 更新成功
                        $this->success('修改成功',url('edit',array_merge(input(''),['is_jiesuan'=>1])));
                    } else {
                        // 更新失败
                        $this->error('编辑失败,请稍后重试!');
                    }
                }
                if($data['settlement_type'] == 2){  // 更新支付宝信息
                    $data_3 = [
                        'alipay_account'=>$data['alipay_account'],
                        'alipay_name'=>$data['alipay_name'],
                    ];
                    // 保存
                    $update_res = $cp_model->edit(['id'=>$data['id'],'settlement_type'=>2, 'alipay_info'=>json_encode($data_3, JSON_UNESCAPED_UNICODE)], 3);

                    if ($update_res) {
                        // 更新成功
                        $this->success('修改成功',url('edit',array_merge(input(''),['is_jiesuan'=>1])));
                    } else {
                        // 更新失败
                        $this->error('编辑失败,请稍后重试!');
                    }

                }

            }

        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');

        $find_res = $cp_model->where("id=$id")->find()->toArray();
        $bank_info = json_decode($find_res['bank_info'], true);
        $alipay_info = json_decode($find_res['alipay_info'], true);
        // $final_res = array_merge($find_res, $bank_info, $alipay_info);
        $find_res['bank_info'] = $bank_info;
        $find_res['alipay_info'] = $alipay_info;

        $this->assign('cp_info', $find_res);
        return $this->fetch();

    }
    // 检查CP商名称
    public function checkCpName()
    {
        $cp_name = trim($this->request->param('cp_name'));
        // 如果是编辑这条cp信息 另做处理
        $id = $this->request->param('id', 0, 'intval');
        if(!empty($id)){
            $model = new CpModel();
            $result = $model->checkGameName($cp_name,$id);
            return json_encode($result);
        }else{
            $model = new CpModel();
            $result = $model->checkGameName($cp_name);
            return json_encode($result);
        }

    }



     /**
     * [编辑游戏]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit222()
    {
        if(PERMI == 2){
            $this->error('权限不足');
        }
        $model = new GameModel();
        if ($this->request->post()) {
            $data = $this->request->param();
            if (empty($data['id'])) $this->error('游戏不存在');
            $logic = new SyLogic();
            $result = $logic->edit($data);
            if ($result['status']) {
                $this->edit_warning($data['discount'],$data['id']);
                $this->success('编辑成功', url('Game/lists'));
            } else {
                $this->error($result['msg']);
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $game = $model
            ->alias('g')
            ->field('g.*,login_notify_url,pay_notify_url,game_key,access_key,agent_id,game_pay_appid,apk_pck_name,apk_pck_sign')
            ->Join(['tab_game_set' => 's'], 'g.id=s.game_id', 'left')
            ->where('g.id', $id)->find();
        if (!$game) $this->error('游戏不存在或是已删除');
        $game = $game->toArray();
        $tag_name = explode(',', $game['tag_name']);
        $game['tag_name_one'] = $tag_name[0];
        $game['tag_name_two'] = $tag_name[1];
        $game['tag_name_three'] = $tag_name[2];

        $this->assign('data', $game);
        return $this->fetch();
    }
}
