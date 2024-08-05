<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtPropModel;
use app\btwelfare\model\BtWelfareModel;
use app\btwelfare\model\BtWelfareRegisterModel;
use app\game\model\ServerModel;
use app\promote\model\PromoteModel;

/**
 * bt福利注册
 * Class RegisterLogic
 *
 * @package app\btwelfare\logic
 */
class RegisterLogic extends BaseLogic
{

    protected $mRegister;
    protected $mBtWelfare;
    protected $mBtProp;

    public function __construct()
    {
        parent ::__construct();
        $this -> mRegister = new BtWelfareRegisterModel();
        $this -> mBtWelfare = new BtWelfareModel();
        $this -> mBtProp = new BtPropModel();
    }


    /**
     * @注册福利管理后台列表
     *
     * @author: zsl
     * @since: 2021/1/14 17:22
     */
    public function adminLists($param)
    {
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['promote_id'])) {
            $where['promote_id'] = $param['promote_id'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['user_account'])) {
            $where['user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['game_player_name'])) {
            $where['game_player_name'] = ['like', '%' . $param['game_player_name'] . '%'];
        }
        if ($param['status'] === '0' ||$param['status']==='1') {
            $where['status'] = $param['status'];
        }
        if (!empty($param['server_id'])) {
            $mServer = new ServerModel();
            $server_num = $mServer->where(['id'=>$param['server_id']])->value('server_num');
            if(!empty($server_num)){
                $where['server_id'] = $server_num;
            }
        }

        if ($param['login_start_time'] && $param['login_end_time']) {
            $where['login_time'] = ['between', [strtotime($param['login_start_time']), strtotime($param['login_end_time']) + 86399]];
        } elseif ($param['login_end_time']) {
            $where['login_time'] = ['lt', strtotime($param['login_end_time']) + 86400];
        } elseif ($param['login_start_time']) {
            $where['login_time'] = ['egt', strtotime($param['login_start_time'])];
        }
        // 渠道独占-------- START
        $promote_id = PID;
        $map_forbid = [];
        if(!empty($promote_id)){
            $onlyForPromote = game_only_for_promote($promote_id);
            $forbidGameids = $onlyForPromote['forbid_game_ids'];
            if(!empty($forbidGameids)){
                if(!empty($where['game_id'])){
                    $where['game_id'] = [['=',$where['game_id']],['not in',$forbidGameids]];
                }else{
                    $where['game_id'] = ['not in',$forbidGameids];
                }
            }
        }
        // 渠道独占-------- END

        $lists = $this -> mRegister
                -> field('id,user_id,user_account,game_id,game_name,server_id,server_name,game_player_name,promote_id,promote_name,login_time,status')
                -> where($where)
                ->where($map_forbid) // 渠道独占
                -> order('create_time desc,id desc')
                -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


    /**
     * @生成注册福利待发放数据
     *
     * @author: zsl
     * @since: 2021/1/14 15:29
     */
    public function buildData($param = [])
    {

        //获取渠道id
        $userInfo = get_user_entity($param['user_id'], false, 'puid,promote_id');
        if ($userInfo['puid'] == 0) {
            $param['promote_id'] = $userInfo['promote_id'];
        } else {
            $param['promote_id'] = get_user_entity($userInfo['puid'], false, 'promote_id')['promote_id'];
        }
        //检查是否需要发放
        if (!$welfareInfo = $this -> checkExist($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '无需发放';
            return $this -> result;
        }
        //生成注册福利数据
        $this -> mRegister -> user_id = $param['user_id'];
        $this -> mRegister -> user_account = get_user_entity($param['user_id'], false, 'account')['account'];
        $this -> mRegister -> game_id = $param['game_id'];
        $this -> mRegister -> game_name = get_game_name($param['game_id']);
        $this -> mRegister -> server_id = $param['server_id'];
        $this -> mRegister -> server_name = $param['server_name'];
        $this -> mRegister -> game_player_id = $param['game_player_id'];
        $this -> mRegister -> game_player_name = $param['game_player_name'];
        $this -> mRegister -> promote_id = $param['promote_id'];
        $this -> mRegister -> promote_name = get_promote_name($param['promote_id']);
        $this -> mRegister -> login_time = $param['register_time'];
        //查询道具内容
        $this -> mRegister -> send_prop = $this -> getPropContent($welfareInfo -> register_prop_ids);
        $this -> mRegister -> status = 0;
        $result = $this -> mRegister -> allowField(true) -> isUpdate(false) -> save();
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,生成失败';
        }
        //获取记录id
        $recordId = $this -> mRegister -> getLastInsID();
        $this -> result['msg'] = '生成成功';
        //自动发放
        $mPromote = new PromoteModel();
        $bt_welfare_register_auto = $mPromote -> where(['id' => $param['promote_id']]) -> value('bt_welfare_register_auto');
        if ($bt_welfare_register_auto == '1') {
            try {
                $this -> send($recordId);
            } catch (\Exception $e) {
                //自动发放失败
            }
        }
        return $this -> result;
    }


    /**
     * @检查是否需要生成
     *
     * @author: zsl
     * @since: 2021/1/14 15:47
     */
    public function checkExist($param = [])
    {
        //查询该条件下是否配置游戏福利
        $where = [];
        $where['w.game_id'] = $param['game_id'];
        $where['w.status'] = 1;
        $where['w.start_time'] = ['lt', time()];
        $where['w.end_time'] = [['gt', time()], ['eq', 0], 'or'];
        $where['wp.promote_id'] = $param['promote_id'];
        $welfareInfo = $this -> mBtWelfare -> alias('w')
                -> field('w.id,w.register_prop_ids')
                -> join(['tab_bt_welfare_promote' => 'wp'], 'w.id = wp.bt_welfare_id', 'left')
                -> where($where)
                -> find();
        if (empty($welfareInfo) || empty($welfareInfo['register_prop_ids'])) {
            return false;
        }
        //查询福利是否已存在
        $where = [];
        $where['user_id'] = $param['user_id'];
        $where['game_id'] = $param['game_id'];
        $where['game_player_id'] = $param['game_player_id'];
        $registerRecord = $this -> mRegister -> where($where) -> find();
        if (!empty($registerRecord)) {
            return false;
        }
        return $welfareInfo;
    }


    /**
     * @发放福利
     *
     * @author: zsl
     * @since: 2021/1/18 21:34
     */
    public function send($id)
    {

        $info = $this -> mRegister -> find($id);
        if ($info['status'] == '1') {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '不可重复发放';
            return $this -> result;
        }
        //验证sign
        $apiPath = "\app\btwelfare\api\Game" . $info['game_id'] . "Api";
        $apiObject = new $apiPath;
        //获取待发放道具
        $propList = json_decode($info['send_prop'], true);
        if (empty($propList)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '暂无道具';
            return $this -> result;
        }
        $this -> mRegister -> startTrans();
        try {
            //发放道具
            foreach ($propList as $v) {
                $data = $info -> toArray();
                $data['prop_id'] = $v['prop_tag'];
                $data['prop_num'] = $v['number'];
                $sendRes = $apiObject -> send($data);
                if (false === $sendRes) {
                    $this -> mRegister -> rollback();
                    $this -> result['code'] = 0;
                    $this -> result['msg'] = '发放失败';
                    return $this -> result;
                }
            }
            //修改发放状态
            $info -> status = 1;
            $info -> isUpdate(true) -> save();
            $this -> mRegister -> commit();
            return $this -> result;
        } catch (\Exception $e) {
            $this -> mRegister -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误,发放失败'.$e->getMessage();
        }
        return $this -> result;
    }

}