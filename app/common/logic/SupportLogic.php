<?php

namespace app\common\logic;

use app\common\lib\constant\SupportConstant;
use app\common\model\SupportModel;
use app\game\model\GameAttrModel;
use think\Db;

class SupportLogic
{


    private function mSupport()
    {
        return new SupportModel();
    }


    /**
     * @新增扶持
     *
     * @author: zsl
     * @since: 2020/9/14 16:04
     */
    public function add($param = [])
    {
        $result = ['code' => 1, 'msg' => '新增成功', 'data' => []];
        //验证提交参数
        $valiRes = $this -> mSupport() -> addValidate($param);
        if ($valiRes['code'] == 0) {
            $result['code'] = 0;
            $result['msg'] = $valiRes['msg'];
            return $result;
        }
        //整理数据
        $promoteInfo = get_promote_entity(PID, 'id,account');
        $supportData = array_merge($param['support']);
        foreach ($supportData as &$v) {
            $v['promote_id'] = $promoteInfo['id'];
            $v['promote_account'] = $promoteInfo['account'];
            $v['user_id'] = get_user_entity($v['user_account'], true, 'id')['id'];
            $v['game_id'] = $param['game_id'];
            $v['game_name'] = get_game_name($param['game_id']);
            $v['server_id'] = $param['server_id'];
            $v['server_name'] = $this -> get_server_name($param['server_id']);
            $v['support_type'] = SupportConstant::FIRST;
            $v['create_time'] = time();
            $v['usable_num'] = $this -> mSupport() -> gameFirstSupportNum($param['game_id']);
            //是否自动审核
            $status = Db ::table('tab_promote_config') -> where(['name' => 'promote_auto_audit_support']) -> value('status');
            $v['status'] = $status == 1 ? SupportConstant::CHECKED : SupportConstant::UNCHECKED;
            $v['audit_time'] = $status == 1 ? time() : 0;
        }
        $res = $this -> mSupport() -> saveAll($supportData);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '保存失败';
        }
        return $result;


    }


    /**
     * @获取区服列表
     *
     * @since: 2020/9/14 17:01
     * @author: zsl
     */
    public function getServer($pararm = [])
    {
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        if (empty($pararm['game_id'])) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $where = [];
        $where['game_id'] = $pararm['game_id'];
        $lists = Db ::table('tab_game_server') -> field('id,server_name') -> where($where) -> order('id desc') -> select();
        if (!empty($lists)) {
            $result['data'] = $lists;
        }
        return $result;
    }


    /**
     * @扶持列表
     *
     * @author: zsl
     * @since: 2020/3/16 16:24
     */
    public function lists($param, $where = [], $isPage = true)
    {
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        $page = intval($param['p']);
        $page = $page ? $page : 1; //默
        $row = empty($param['row']) ? 10 : $param['row'];
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        if ($isPage) {
            $lists = $this -> mSupport() -> where($where) -> order('create_time desc,id desc') -> paginate($row, false, ['query' => $param])->each(function($item, $key) use ($ys_show_promote){

                if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                    $item['user_account'] = get_ys_string($item['user_account'],$ys_show_promote['account_show_promote']);
                }
                //增加处理角色查看隐私情况
                if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                    $item['role_name'] = get_ys_string($item['role_name'],$ys_show_promote['role_show_promote']);
                }
            });
        } else {
            $lists = $this -> mSupport() -> where($where) -> order('create_time desc,id desc') -> select();
            // 判断当前渠道是否有权限显示完成整手机号或完整账号
            foreach($lists as $k5=>$v5){
                if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                    $lists[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
                }
                //增加处理角色查看隐私情况
                if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                    $lists[$k5]['role_name'] = get_ys_string($v5['role_name'],$ys_show_promote['role_show_promote']);
                }
            }
        }
        $result['data'] = $lists;
        return $result;
    }


    /**
     * @扶持申请列表
     *
     * @author: zsl
     * @since: 2020/9/15 11:00
     */
    public function applyLists($param, $promote_id)
    {
        $where = [];
        if (!empty($param['user_account'])) {
            $where['user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['server_id'])) {
            $where['server_id'] = $param['server_id'];
        }
        if (!empty($param['role_name'])) {
            $where['role_name'] = ['like', '%' . $param['role_name'] . '%'];
        }
        if (!empty($param['create_time'])) {
            $where['create_time'] = $param['create_time'];
        }
        $where['promote_id'] = $promote_id;
        $where['support_type'] = SupportConstant::FIRST;
        $where['status'] = ['in', [SupportConstant::SEND]];
        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $result = $this -> lists($param, $where);
        //查询累计扶持数量 和 累计扶持次Support/following数
        if (!empty($result['data'])) {
            $data = $result['data'];
            foreach ($data as &$v) {
                //获取扶持次数
                $totalCount = $this -> mSupport() -> getTotalSupportCount($v);
                //获取扶持数量
                $totalNum = $this -> mSupport() -> getTotalSupportNum($v);
                $v['totalCount'] = $totalCount;
                $v['totalNum'] = $totalNum;
            }
            $result['data'] = $data;
        }
        return $result;
    }


    private function get_server_name($id = '')
    {
        if ($id == '') {
            return false;
        }
        $server = Db ::table("tab_game_server") -> field('server_name') -> where("id", $id) -> find();
        return $server['server_name'];
    }


    /**
     * @推广后台 申请扶持 游戏列表
     *
     * @author: zsl
     * @since: 2020/3/27 14:28
     */
    public function applyGameLists($promote_id)
    {
        $where['promote_id'] = $promote_id;
        $where['support_type'] = SupportConstant::FIRST;
        $where['status'] = ['in', [SupportConstant::SEND]];
        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $lists = $this -> mSupport() -> field('game_id as id,game_name') -> where($where) -> group('game_id') -> order('create_time desc,id desc') -> select();
        return $lists ? $lists : [];
    }

    /**
     * @获取可用额度
     *
     * @author: zsl
     * @since: 2020/3/16 19:30
     */
    public function usableNum($param)
    {
        //验证是否存在首次申请
        $where = [];
        $where['id'] = $param['id'];
        $where['promote_id'] = PID;
        $where['support_type'] = SupportConstant::FIRST;
        $where['status'] = ['in', [SupportConstant::SEND]];
        $supportInfo = $this -> mSupport() -> field('id,promote_id,game_id,server_id') -> where($where) -> find();
        if (empty($supportInfo)) {
            return 0;
        }
        //获取渠道下游戏区服后续扶持总额度
        $supportNum = $this -> mSupport() -> gameFollowingSupportNum($supportInfo);
        //获取渠道下游戏区服后续扶持已使用额度
        $usedNum = $this -> mSupport() -> gameFollowingUsedNum($supportInfo);
        $usableNum = $supportNum - $usedNum;
        return $usableNum;
    }


    /**
     * @后续扶持申请
     *
     * @author: zsl
     * @since: 2020/3/16 19:08
     */
    public function following($param = [])
    {
        $result = ['code' => 1, 'msg' => '申请成功', 'data' => []];
        $valiRes = $this -> mSupport() -> followValidate($param);
        if ($valiRes['code'] == 0) {
            $result['code'] = 0;
            $result['msg'] = $valiRes['msg'];
            return $result;
        }
        //获取可用额度
        $usableNum = $this -> usableNum($param);
        if ($usableNum < $param['apply_num']) {
            $result['code'] = 0;
            $result['msg'] = '扶持额度不足，最大额度：' . $usableNum;
            return $result;
        }
        //整理数据,新增记录
        $info = $this -> mSupport() -> where(['id' => $param['id'], 'promote_id' => PID]) -> find();
        if (empty($info)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return $result;
        }
        $addData = [];
        $addData['promote_id'] = $info['promote_id'];
        $addData['promote_account'] = $info['promote_account'];
        $addData['user_id'] = $info['user_id'];
        $addData['user_account'] = $info['user_account'];
        $addData['game_id'] = $info['game_id'];
        $addData['game_name'] = $info['game_name'];
        $addData['server_id'] = $info['server_id'];
        $addData['server_name'] = $info['server_name'];
        $addData['role_name'] = $info['role_name'];
        $addData['apply_num'] = $param['apply_num'];
        $addData['support_type'] = SupportConstant::FOLLOWING;
        $addData['remark'] = $param['remark'];
        $addData['create_time'] = time();
        $addData['usable_num'] = $usableNum;
        $status = Db ::table('tab_promote_config') -> where(['name' => 'promote_auto_audit_support']) -> value('status');
        $addData['status'] = $status == 1 ? SupportConstant::CHECKED : SupportConstant::UNCHECKED;
        $addData['audit_time'] = $status == 1 ? time() : 0;
        $res = $this -> mSupport() -> isUpdate(false) -> save($addData);
        if (false === $res) {
            $result['code'] = 0;
            $result['msg'] = '申请失败,请稍后重试';
            return $result;
        }
        return $result;
    }


    /**
     * @修改用户昵称
     *
     * @author: zsl
     * @since: 2020/3/17 11:39
     */
    public function changeRoleName($param = [])
    {
        $result = ['code' => 1, 'msg' => '修改成功', 'data' => []];
        if (empty($param['role'])) {
            $result['code'] = 0;
            $result['msg'] = '请输入角色名';
            return $result;
        }
        $supInfo = $this -> mSupport() -> field('id,user_id,game_id,server_id') -> where(['id' => $param['id']]) -> find();
        if (empty($supInfo)) {
            $result['code'] = 0;
            $result['msg'] = '新增扶持信息不存在';
            return $result;
        }
        $where = [];
        $where['user_id'] = $supInfo['user_id'];
        $where['game_id'] = $supInfo['game_id'];
        $where['server_id'] = $supInfo['server_id'];
        $data = [];
        $data['role_name'] = $param['role'];
        if (!$this -> reStatus($where, $data)) {
            $result['code'] = 0;
            $result['msg'] = '修改失败';
            return $result;
        }
        return $result;
    }

    /**
     * @更新状态
     *
     * @author: zsl
     * @since: 2020/3/13 16:42
     */
    public function reStatus($where, $data)
    {
        try {
            $this -> mSupport() -> isUpdate(true) -> save($data, $where);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @扶持申请记录列表
     *
     * @author: zsl
     * @since: 2020/9/15 17:59
     */
    public function recordLists($param, $promote_id)
    {
        $where = [];
        $where['promote_id'] = $promote_id;
        $where['status'] = ['neq', SupportConstant::DELETE];
        $status = input('status');
        if (isset($status) && $status !== '') {
            $where['status'] = $status;
        }
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        if (!empty($param['server_id'])) {
            $where['server_id'] = $param['server_id'];
        }
        if (!empty($param['role_name'])) {
            $where['role_name'] = ['like', '%' . $param['role_name'] . '%'];
        }
        if (!empty($param['user_account'])) {
            $where['user_account'] = ['like', '%' . $param['user_account'] . '%'];
        }
        if (!empty($param['create_time'])) {
            $where['create_time'] = $param['create_time'];
        }
        $support_type = input('support_type');
        if (isset($support_type) && $support_type !== '') {
            $where['support_type'] = $support_type;
        }
        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $result = $this -> lists($param, $where);
        return $result;
    }


    public function statistics($param = [])
    {
        $result = ['code' => 1, 'msg' => '', 'data' => [], 'page' => ''];
        $row = empty($param['row']) ? 10 : $param['row'];
        //查询游戏数量
        $where = [];
        $where['status'] = ['neq', SupportConstant::DELETE];
        if (!empty($param['game_id'])) {
            $where['game_id'] = $param['game_id'];
        }
        
        // 隐藏的游戏不显示
        $hidden_game_ids = get_hidden_game_ids();
        $where['game_id'] = ['notin', $hidden_game_ids];

        $mSupport = $this -> mSupport();
        $lists = $mSupport -> field('game_id,game_name') -> where($where) -> order('create_time desc,id desc')
                -> group('game_id') -> paginate($row, false, ['query' => $param]);
        if (!empty($lists)) {
            foreach ($lists as &$v) {
                //累计额度
                $v['total_num'] = $mSupport -> gameFollowingSupportNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
                //已发放后续扶持
                $v['send_following_num'] = $mSupport -> gameFollowingUsedNum(['game_id' => $v['game_id'], 'promote_id' => PID,'only_send'=>true]);
                //已发放新增扶持
                $v['send_first_num'] = $mSupport -> alreadySendFirstNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
                //待审核后续扶持
                $v['uncheck_following_num'] = $mSupport -> unCheckFollowingNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
                //待审核新增扶持
                $v['uncheck_frist_num'] = $mSupport -> unCheckFirstNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
//                //可申请扶持
//                $v['usable_num'] = $v['total_num'] - $mSupport -> gameFollowingUsedNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
                //今日已申请数量
                $v['today_num'] = $mSupport -> todayAlreadySendFollowingNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
                //今日新申请总数
                $v['today_total_num'] = $mSupport -> todayTotalFollowingNum(['game_id' => $v['game_id'], 'promote_id' => PID]);
            }
        }
        $result['data'] = $lists;
        return $result;
    }


    /**
     * @获取扶持介绍
     *
     * @author: zsl
     * @since: 2021/9/8 21:31
     */
    public function getIntroduction($param)
    {
        $result = ['code' => 1, 'msg' => '', 'data' => []];
        $mGameAttr = new GameAttrModel();
        $support_introduction = $mGameAttr -> where(['game_id' => $param['game_id']]) -> value('support_introduction');

        if(empty($support_introduction)){
            $result['code'] = 0;
            $result['msg'] = '暂未设置';
            return $result;
        }
        $result['data']['support_introduction'] = explode(PHP_EOL,$support_introduction);
        return $result;
    }


}
