<?php

namespace app\channelsite\controller;

use app\channelsite\model\PromoteModel;
use app\common\logic\SimulatorLogic;

class SimulatorController extends BaseController
{


    /**
     * @模拟器白名单
     *
     * @author: zsl
     * @since: 2021/5/25 11:04
     */
    public function white_list()
    {

        $lSimulator = new SimulatorLogic();
        $param = $this -> request -> param();
        $param['promote_id'] = PID;
        //获取可配置游戏列表
        $gameLists = $lSimulator -> getGameLists($param);
        $this -> assign('game_lists', $gameLists);
        //获取已添加到白名单用户
        $userLists = $lSimulator -> getUserLists($param);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($userLists as $k5=> $v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $userLists[$k5]['account'] = get_ys_string($v5['account'],$ys_show_promote['account_show_promote']);
            }
        }
        $this -> assign('user_lists', $userLists);
        return $this -> fetch();
    }


    /**
     * @新增白名单
     *
     * @author: zsl
     * @since: 2021/5/25 15:30
     */
    public function addUser()
    {
        $param = $this -> request -> param();
        //添加玩家到白名单
        $lSimulator = new SimulatorLogic();
        $param['promote_id'] = PID;
        $result = $lSimulator -> addUser($param);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);
    }


    /**
     * @移除白名单
     *
     * @author: zsl
     * @since: 2021/5/25 16:55
     */
    public function removeUser()
    {
        $param = $this -> request -> param();
        //添加玩家到白名单
        $lSimulator = new SimulatorLogic();
        $param['promote_id'] = PID;
        $result = $lSimulator -> removeUser($param);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);
    }


    /**
     * @移除全部白名单
     *
     * @author: zsl
     * @since: 2021/7/22 13:39
     */
    public function removeAllUser()
    {
        $param = $this -> request -> param();
        $lSimulator = new SimulatorLogic();
        $param['promote_id'] = PID;
        $result = $lSimulator -> removeAllUser($param);
        if ($result['code'] == '0') {
            $this -> error($result['msg']);
        }
        $this -> success($result['msg']);
    }


    /**
     * @设置渠道是否全部允许模拟器登录
     *
     * @author: zsl
     * @since: 2021/7/22 11:28
     */
    public function saveAllAllow()
    {
        $param = $this -> request -> param();
        $all_allow = empty($param['all_allow']) ? 0 : 1;
        $mPromote = new PromoteModel();
        $res = $mPromote -> where(['id' => PID]) -> setField('allow_simulator', $all_allow);
        if (false === $res) {
            $this -> error('保存失败');
        }
        $this -> success('保存成功');
    }


}
