<?php

namespace app\common\model;

use app\common\lib\constant\SupportConstant;
use think\Db;
use think\helper\Time;
use think\Model;

class SupportModel extends Model
{

    protected $table = 'tab_support';
    protected $autoWriteTimestamp = true;


    public function addValidate($param)
    {
        $result = ['code' => 1, 'msg' => '验证通过'];
        if (empty($param['game_id'])) {
            $result['code'] = 0;
            $result['msg'] = '请选择游戏';
            return $result;
        }
        if (empty($param['server_id'])) {
            $result['code'] = 0;
            $result['msg'] = '请选择区服';
            return $result;
        }
        //获取当前推广员所有下级渠道
        $pids = $this -> getAllPid();
        if (!empty($pids)) {
            $pids = array_column($pids, 'id');
            array_push($pids, PID);
        } else {
            $pids = [PID];
        }
        $accountArr = [];
        $where = [];
        $where['promote_id'] = ['in', $pids];
        $where['is_platform'] = 0;
        $where['puid'] = 0;
        foreach ($param['support'] as $item) {
            /*验证账号*/
            if (empty($item['user_account'])) {
                $result['code'] = 0;
                $result['msg'] = '请输入账号';
                return $result;
            }
            /*验证账号是否存在*/
            $where['account'] = $item['user_account'];
            $uRes = Db ::table('tab_user') -> where($where) -> count();
            if (empty($uRes)) {
                $result['code'] = 0;
                $result['msg'] = '账号' . $item['user_account'] . '不存在';
                return $result;
            }
            //验证账号是否重复
            if (!in_array($item['user_account'], $accountArr)) {
                $accountArr[] = $item['user_account'];
            } else {
                $result['code'] = 0;
                $result['msg'] = '账号' . $item['user_account'] . '已存在';
                return $result;
            }
            /*验证账号是否已新增过*/
            $map = [];
            $map['user_account'] = $item['user_account'];
            $map['game_id'] = $param['game_id'];
            $map['server_id'] = $param['server_id'];
            $map['support_type'] = 0;
            $map['status'] = ['in', [SupportConstant::UNCHECKED, SupportConstant::CHECKED, SupportConstant::SEND]];
            $sRes = $this -> where($map) -> count();
            if (!empty($sRes)) {
                $result['code'] = 0;
                $result['msg'] = '账号' . $item['user_account'] . '已存在';
                return $result;
            }
            /*验证角色名*/
            if (empty($item['role_name'])) {
                $result['code'] = 0;
                $result['msg'] = '请输入角色名';
                return $result;
            }
            /*验证扶持数量*/
            if (empty($item['apply_num'])) {
                $result['code'] = 0;
                $result['msg'] = '请输入扶持数量';
                return $result;
            }
            /*验证扶持申请额度*/
            if ($item['apply_num'] > $this -> gameFirstSupportNum($param['game_id'])) {
                $result['code'] = 0;
                $result['msg'] = '' . $item['user_account'] . ' 申请扶持数量超出新增扶持额度上限';
                return $result;
            }

        }
        return $result;
    }

    /**
     * @后续扶持验证
     *
     * @author: zsl
     * @since: 2020/9/15 17:36
     */
    public function followValidate($param)
    {
        $result = ['code' => 1, 'msg' => '验证通过'];
        if (empty($param['apply_num'])) {
            $result['code'] = 0;
            $result['msg'] = '请输入扶持数量';
            return $result;
        }
        if (!is_numeric($param['apply_num']) || $param['apply_num'] <= 0) {
            $result['code'] = 0;
            $result['msg'] = '扶持数量请输入正整数';
            return $result;
        }
        return $result;
    }


    /**
     * @获取游戏首次扶持额度
     *
     * @author: zsl
     * @since: 2020/9/14 16:13
     */
    public function gameFirstSupportNum($game_id = 0)
    {
        if (empty($game_id)) {
            return 0;
        }
        $first_support_num = Db ::table('tab_game') -> where(['id' => $game_id]) -> value('first_support_num');
        return $first_support_num ? $first_support_num : 0;
    }


    /**
     * @获取渠道用户单个游戏区服扶持次数
     *
     * @author: zsl
     * @since: 2020/3/17 16:31
     */
    public function getTotalSupportCount($suuportInfo = [])
    {
        $map = [];
        $map['promote_id'] = $suuportInfo['promote_id'];
        $map['user_id'] = $suuportInfo['user_id'];
        $map['server_id'] = $suuportInfo['server_id'];
        $map['status'] = ['in', [SupportConstant::CHECKED, SupportConstant::SEND]];
        $totalCount = $this -> where($map) -> count();
        return $totalCount;
    }

    /**
     * @获取渠道用户单个游戏区服扶持总量
     *
     * @author: zsl
     * @since: 2020/3/17 16:31
     */
    public function getTotalSupportNum($suuportInfo = [])
    {
        $map = [];
        $map['promote_id'] = $suuportInfo['promote_id'];
        $map['user_id'] = $suuportInfo['user_id'];
        $map['server_id'] = $suuportInfo['server_id'];
        $map['status'] = SupportConstant::SEND;
        $totalNum = $this -> where($map) -> sum('send_num');
        return $totalNum ? $totalNum : 0;
    }


    /**
     * @获取渠道下游戏后续扶持总额度
     *
     * @author: zsl
     * @since: 2020/3/16 19:42
     */
    public function gameFollowingSupportNum($param = [])
    {
        if (empty($param['game_id']) || empty($param['promote_id'])) {
            return 0;
        }
        //获取游戏续充比例
        $rate = $this -> gameFollowingSupportRate($param['game_id']);
        if (empty($rate)) {
            return 0;
        }
        //获取下级所有推广员
        $pIds = $this -> getAllPid();
        //查询游戏充值总额
        $where = [];
        $where['game_id'] = $param['game_id'];
        if(!empty($param['server_id'])){
            $where['server_id'] = $this -> getGameServerId($param['server_id']);
        }
        $where['promote_id'] = ['in', $pIds];
        $where['pay_status'] = 1;
        $payAmount = Db ::table('tab_spend') -> where($where) -> sum('pay_amount');
        if (empty($payAmount)) {
            return 0;
        }
        //计算总额度
        $supportNum = $this -> getGameCoin($payAmount, $rate);
//        return intval($supportNum);
        //查询区服下充值最多玩家总充值
        $total_amount_list = Db ::table('tab_spend') -> field('sum(pay_amount) as total_amount') -> where($where) -> group('user_id') -> select(false);
        $maxAmount = Db ::table('') -> table('(' . $total_amount_list . ') as t') -> max('total_amount');
        $maxSupportNum = $this -> getGameCoin($maxAmount, 100);
        //如果总额度大于最高充值额度 则使用最高充值额度
        $supportNum = $supportNum > $maxSupportNum ? $maxSupportNum : $supportNum;
        return intval($supportNum);
    }

    /**
     * @渠道下游戏后续区服已使用额度
     *
     * @param $only_send : true: 只返回已发放数量  false:返回已发放加未发放数量
     * @param $only_uncheck : true: 只返回待审核记录  false:返回已发放加未发放数量
     *
     * @author: zsl
     * @since: 2020/3/17 10:11
     */
    public function gameFollowingUsedNum($param = [])
    {
        if (empty($param['game_id']) || empty($param['promote_id'])) {
            return 0;
        }
        //获取游戏续充比例
        $rate = $this -> gameFollowingSupportRate($param['game_id']);
        if (empty($rate)) {
            return 0;
        }
        $where = [];
        $where['promote_id'] = PID;
        $where['game_id'] = $param['game_id'];
        if(!empty($param['server_id'])){
            $where['server_id'] = $param['server_id'];
        }
        //是否返回首次扶持记录
        if (!empty($param['is_first']) && true === $param['is_first']) {
            $where['support_type'] = SupportConstant::FIRST;
        } elseif (!empty($param['is_all']) && true === $param['is_all']) {
            $where['support_type'] = ['in', [SupportConstant::FIRST, SupportConstant::FOLLOWING]];
        } else {
            $where['support_type'] = SupportConstant::FOLLOWING;
        }
        //申请时间
        if (!empty($param['create_time'])) {
            $where['create_time'] = $param['create_time'];
        }
        //已发放
        $where['status'] = ['in', [SupportConstant::SEND]];
        $sendNum = $this -> where($where) -> sum('send_num');
        $where['status'] = ['in',[SupportConstant::CHECKED]];
        $sendNum +=  $this -> where($where) -> sum('apply_num');
        //只返回已发放记录
        if (!empty($param['only_send']) && true === $param['only_send']) {
            return intval($sendNum);
        }
        //待审核
//        $where['status'] = ['in', [SupportConstant::UNCHECKED, SupportConstant::CHECKED]];
        $where['status'] = ['in', [SupportConstant::UNCHECKED]];
        $applyNum = $this -> where($where) -> sum('apply_num');
        //只返回待审核记录
        if (!empty($param['only_uncheck']) && true === $param['only_uncheck']) {
            return intval($applyNum);
        }
        $UsedNum = intval($sendNum) + intval($applyNum);
        return $UsedNum;
    }


    /**
     * @获取游戏后续扶持比例
     *
     * @author: zsl
     * @since: 2020/3/17 9:40
     */
    public function gameFollowingSupportRate($game_id = 0)
    {
        $following_support_rate = Db ::table('tab_game') -> where(['id' => $game_id]) -> value('following_support_rate');
        return $following_support_rate ? $following_support_rate : 0;
    }

    /**
     * @获取下级所有推广员id(包含自身)
     *
     * @author: zsl
     * @since: 2020/3/17 9:49
     */
    public function getAllPid()
    {
        $pids = get_song_promote_lists(PID, 2);
        if (!empty($pids)) {
            $pids = array_column($pids, 'id');
            array_push($pids, PID);
        } else {
            $pids = [PID];
        }
        return $pids;
    }

    /**
     * @金额转游戏币
     *
     * @author: zsl
     * @since: 2020/3/17 10:01
     */
    private function getGameCoin($amount, $rate)
    {
        return intval($amount * SupportConstant::RATIO * $rate / 100);
    }

    /**
     * @获取游戏CP方区服id
     *
     * @author: zsl
     * @since: 2020/9/15 17:10
     */
    private function getGameServerId($id = 0)
    {
        $server_num = Db ::table('tab_game_server') -> where('id', '=', $id) -> value('server_num');
        return $server_num ? $server_num : 0;
    }


    /**
     * @今日已发放后续扶持
     *
     * @author: zsl
     * @since: 2020/3/18 11:33
     */
    public function todayAlreadySendFollowingNum($param)
    {
        $today = Time ::today();
        $param['only_send'] = true;
        $param['create_time'] = ['between', [$today[0], $today[1]]];
        return $this -> gameFollowingUsedNum($param);
    }

    /**
     * @今日所有后续扶持
     *
     * @author: zsl
     * @since: 2020/3/18 11:33
     */
    public function todayTotalFollowingNum($param)
    {
        $today = Time ::today();
        $param['create_time'] = ['between', [$today[0], $today[1]]];
        return $this -> gameFollowingUsedNum($param);
    }

    /**
     * @待审核新增扶持
     *
     * @author: zsl
     * @since: 2020/3/18 11:10
     */
    public function unCheckFirstNum($param)
    {
        $param['only_uncheck'] = true;
        $param['is_first'] = true;
        return $this -> gameFollowingUsedNum($param);
    }

    /**
     * @待审核后续扶持
     *
     * @author: zsl
     * @since: 2020/3/18 11:10
     */
    public function unCheckFollowingNum($param)
    {
        $param['only_uncheck'] = true;
        return $this -> gameFollowingUsedNum($param);
    }


    /**
     * @已发放新增扶持
     *
     * @author: zsl
     * @since: 2020/3/18 10:41
     */
    public function alreadySendFirstNum($param = [])
    {
        $param['only_send'] = true;
        $param['is_first'] = true;
        return $this -> gameFollowingUsedNum($param);
    }
}