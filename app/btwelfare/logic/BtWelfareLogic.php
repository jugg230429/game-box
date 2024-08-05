<?php

namespace app\btwelfare\logic;

use app\btwelfare\model\BtWelfareModel;
use app\btwelfare\model\BtWelfarePromoteModel;
use app\btwelfare\model\BtWelfareRegisterModel;
use app\btwelfare\validate\BtWelfareValidate;
use think\exception\PDOException;

class BtWelfareLogic extends BaseLogic
{

    public function __construct()
    {
        parent ::__construct();
    }


    /**
     * @福利后台列表
     *
     * @author: zsl
     * @since: 2021/1/12 20:02
     */
    public function adminLists($param)
    {

        $mBtWelfare = new BtWelfareModel();
        $row = (int) $param['row'] ? $param['row'] : config('paginate.list_rows');//每页数量
        $where = [];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if ($param['status'] === '1' || $param['status'] === '0') {
            $where['status'] = $param['status'];
        }

        if ($param['start_time'] && $param['end_time']) {
            $where['start_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time']) + 86399]];
        } elseif ($param['end_time']) {
            $where['start_time'] = ['lt', strtotime($param['end_time']) + 86400];
        } elseif ($param['start_time']) {
            $where['start_time'] = ['egt', strtotime($param['start_time'])];
        }
        $lists = $mBtWelfare -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param]);
        return $lists;
    }


    /**
     * @新增福利
     *
     * @author: zsl
     * @since: 2021/1/12 19:16
     */
    public function add($param)
    {

        //验证请求参数
        $lBtWelfare = new BtWelfareValidate();
        if (!$lBtWelfare -> scene('add') -> check($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = $lBtWelfare -> getError();
            return $this -> result;
        }
        //验证所选游戏下所选渠道 是否已配置
        $selected = $lBtWelfare -> checkPromote($param);
        if (!$selected -> isEmpty()) {
            $promote_id = $selected[0] -> promote_id;
            $promote_name = get_promote_name($promote_id);
            $this -> result['code'] = 0;
            $this -> result['msg'] = "推广员{$promote_name}已存在配置";
            return $this -> result;
        }
        //写入数据
        $mBtWelfare = new BtWelfareModel();
        $mBtWelfarePromote = new BtWelfarePromoteModel();
        $mBtWelfare -> startTrans();
        try {
            //新增游戏福利设置
            $mBtWelfare -> add($param);
            $btWelfareId = $mBtWelfare -> getLastInsID();
            //更新渠道关联表
            $mBtWelfarePromote -> updateBtWelfare($param, $btWelfareId);
            $mBtWelfare -> commit();
        } catch (\Exception $e) {
            $mBtWelfare -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误: ' . $e -> getMessage();
            return $this -> result;
        }
        $this -> result['msg'] = '新增成功';
        return $this -> result;
    }


    /**
     * @编辑BT福利
     *
     * @author: zsl
     * @since: 2021/1/13 9:44
     */
    public function edit($param)
    {

        //验证请求参数
        $lBtWelfare = new BtWelfareValidate();
        if (!$lBtWelfare -> scene('edit') -> check($param)) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = $lBtWelfare -> getError();
            return $this -> result;
        }
        //验证所选游戏下所选渠道 是否已配置
        $selected = $lBtWelfare -> checkPromote($param);
        if (!$selected -> isEmpty()) {
            $promote_id = $selected[0] -> promote_id;
            $promote_name = get_promote_name($promote_id);
            $this -> result['code'] = 0;
            $this -> result['msg'] = "推广员{$promote_name}已存在配置";
            return $this -> result;
        }
        //写入数据
        $mBtWelfare = new BtWelfareModel();
        $mBtWelfarePromote = new BtWelfarePromoteModel();
        $mBtWelfare -> startTrans();
        try {
            //新增游戏福利设置
            $mBtWelfare -> edit($param);
            //更新渠道关联表
            $mBtWelfarePromote -> updateBtWelfare($param, $param['id']);
            $mBtWelfare -> commit();
        } catch (\Exception $e) {
            $mBtWelfare -> rollback();
            $this -> result['code'] = 0;
            $this -> result['msg'] = '发生错误: ' . $e -> getMessage();
            return $this -> result;
        }
        $this -> result['msg'] = '保存成功';
        return $this -> result;
    }


    /**
     * @删除Bt福利
     *
     * @author: zsl
     * @since: 2021/1/13 10:26
     */
    public function del($id)
    {
        $mBtWelfare = new BtWelfareModel();
        $mBtWelfarePromote = new BtWelfarePromoteModel();
        $mBtWelfare -> startTrans();
        try {
            //删除福利配置
            $mBtWelfare -> where(['id' => $id]) -> delete();
            //删除关联推广员
            $mBtWelfarePromote -> where(['bt_welfare_id' => $id]) -> delete();
            $mBtWelfare -> commit();
        } catch (\Exception $e) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = "删除失败: " . $e -> getMessage();
            $mBtWelfare -> rollback();
            return $this -> result;
        }
        $this -> result['msg'] = '删除成功';
        return $this -> result;
    }


    /**
     * @修改状态
     *
     * @author: zsl
     * @since: 2021/1/13 10:38
     */
    public function changeStatus($param)
    {
        if ($param['status'] != '0' && $param['status'] != '1') {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '修改失败';
        }
        $mBtWelfare = new BtWelfareModel();
        $result = $mBtWelfare -> where(['id' => $param['id']]) -> setField('status', $param['status']);
        if (false === $result) {
            $this -> result['code'] = 0;
            $this -> result['msg'] = '修改失败';
        }
        $this -> result['msg'] = '修改成功';
        return $this -> result;
    }


    /**
     * @发放福利道具
     *
     * @author: zsl
     * @since: 2021/1/18 21:18
     */
    public function send($type, $id)
    {

        switch ($type) {
            case 1:
                //注册福利
                $logic = new RegisterLogic();
                $result = $logic -> send($id);
                break;
            case 2:
                //充值奖励
                $logic = new RechargeLogic();
                $result = $logic -> send($id);
                break;
            case 3:
                //累充奖励,
                $logic = new TotalRechargeLogic();
                $result = $logic -> send($id);
                break;
            case 4:
                //月卡奖励
                $logic = new MonthCardLogic();
                $result = $logic -> send($id);
                break;
            case 5:
                //周卡奖励
                $logic = new WeekCardLogic();
                $result = $logic -> send($id);
                break;
            default:
                $this -> result['code'] = 0;
                $this -> result['msg'] = '发放类型错误';
                return $this -> result;
                break;
        }




        return $result;
    }


}