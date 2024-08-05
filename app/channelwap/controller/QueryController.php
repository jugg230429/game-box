<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yyh
// +----------------------------------------------------------------------
namespace app\channelwap\controller;

use app\member\model\UserPlayInfoModel;
use app\member\model\UserModel;
use think\Db;
use think\Request;
use app\promote\model\PromoteModel;
use app\common\controller\BaseHomeController;
use cmf\paginator\Bootstrap;
use app\recharge\model\SpendModel;
use app\datareport\event\PromoteController as Promote;
use app\datareport\event\DatabaseController as Database;

class QueryController extends BaseController
{
    protected function _initialize()
    {
        parent::_initialize();
        if (AUTH_GAME != 1 || AUTH_PAY != 1) {
            $this->error('请购买充值权限和游戏权限');
        }
    }
    /**
     * @函数或方法说明
     * @数据汇总
     * @author: 郭家屯
     * @since: 2020/4/9 9:44
     */
    public function summary()
    {
        $this->promotedata();
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @汇总数据
     * @author: 郭家屯
     * @since: 2020/4/9 9:46
     */
    private function promotedata()
    {
        $promoteevent = new Promote();
        $endtime = date('Y-m-d');
        $starttime0 = date('Y-m-d');
        $starttime1 = date('Y-m-d', strtotime(date('Y-m-d')) - 1 * 24 * 3600);
        $starttime7 = date('Y-m-d', strtotime(date('Y-m-d')) - 6 * 24 * 3600);
        $starttime30 = date('Y-m-d', strtotime(date('Y-m-d')) - 29 * 24 * 3600);
        if(PID_LEVEL == 1){
            //今日
            $new_data = $promoteevent->promote_base($starttime0, $endtime, PID);
            $data[0] = $new_data[PID];
            //昨日
            $new_data = $promoteevent->promote_base($starttime1, $starttime1, PID);
            $data[1] = $new_data[PID];
            //7天
            $new_data = $promoteevent->promote_base($starttime7, $endtime, PID);
            $data[7] = $new_data[PID];
            //30天
            $new_data = $promoteevent->promote_base($starttime30, $endtime, PID);
            $data[30] = $new_data[PID];
            //all
            $new_data = $promoteevent->promote_base('2019-01-01', $endtime, PID);
            $data['all'] = $new_data[PID];
        }else{
            //今日
            $sonids = array_column(get_song_promote_lists(PID,2),'id');
            $son_data = [];
            $new_data = $promoteevent->promote_data($starttime0, $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime0, $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[1] = $new_data;
            //昨日
            $new_data = $promoteevent->promote_data($starttime1, $starttime1, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime1, $starttime1, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[1] = $new_data;
            //7天
            $new_data = $promoteevent->promote_data($starttime7, $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime7, $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[7] = $new_data;
            //30天
            $new_data = $promoteevent->promote_data($starttime30, $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data($starttime30, $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data[30] = $new_data;
            //all
            $new_data = $promoteevent->promote_data('2019-01-01', $endtime, [PID])['data'];
            if(!empty($sonids)){
                $son_data = $promoteevent->promote_data('2019-01-01', $endtime, $sonids)['data'];
            }
            $new_data = $this->son_data_merge(reset($new_data),$son_data);
            $data['all'] = $new_data;
        }

        $this->assign('operat_data', $data);
    }
    private function son_data_merge($parent_data,$son_data){
        if(empty($son_data)){
            return $parent_data;
        }
        foreach ($parent_data as $llk=>$llv){
            if(in_array($llk,['new_register_user','active_user','pay_user','new_pay_user','total_pay'])){
                if($llk == 'total_pay'){
                    $list_data[$llk] = $llv+array_sum(array_column($son_data,$llk));
                }else{
                    $list_data[$llk] = $llv.','.implode(',',array_column($son_data,$llk));
                    $list_data[$llk] = trim($list_data[$llk],',');//合并
                    $value = str_unique($list_data[$llk], 1);//去重
                    $list_data['count_'.$llk] = arr_count($value);
                }

            }
        }
        return $list_data;
    }
    //数据汇总
    public function get_summary()
    {
        $request = $this->request->param();
        $starttime = $request['start_time'];
        $endtime = $request['end_time'];
        $promote_id = $this->request->param('promote_id', '');
        if(empty($promote_id)){
            $promote_id =  array_column(get_song_promote_lists(PID),'id');
            $promote_id[] = PID;
        }else{
            $promote_id = [$promote_id];
        }
        $promoteevent = new Promote();
        $new_data = $promoteevent->promote_data($starttime, $endtime, $promote_id);
        $list_data = $new_data['data'];
        $last_pids = array_column($list_data,'promote_id');//直属下级渠道id
        foreach ($list_data as $lk=>$lv){
            if($lv['promote_id']==PID){
                continue;
            }else{
                $zi_promote_ids = array_column(get_song_promote_lists($lv['promote_id']),'id');
                if(!$zi_promote_ids){
                    continue;
                }
                $zi_data = $promoteevent->promote_data($starttime, $endtime, $zi_promote_ids)['data'];
                $last_pids = array_merge($last_pids,$zi_promote_ids);
                foreach ($list_data[$lk] as $llk=>$llv){
                    if(in_array($llk,['new_register_user','active_user','pay_user','new_pay_user','total_pay'])){
                        if($llk == 'total_pay'){
                            $list_data[$lk][$llk] = null_to_0($llv+array_sum(array_column($zi_data,$llk)));
                        }else{
                            $list_data[$lk][$llk] = $llv.','.implode(',',array_column($zi_data,$llk));
                            $list_data[$lk][$llk] = trim($list_data[$lk][$llk],',');
                            $value = str_unique($list_data[$lk][$llk], 1);
                            $list_data[$lk]['count_'.$llk] = arr_count($value);
                        }

                    }
                }
            }
        }
        $promote_id = $last_pids;
        $new_data = $promoteevent->promote_data($starttime, $endtime, $promote_id);
        $total_data = $new_data['total_data'];
        //排序
        $list_data = parent::array_order($list_data, $request['sort_type']?:'total_pay', $request['sort']);
        $page = $this->request->param('p/d',1);
        $row = $this->request->param('row/d',10);
        $return['data'] = array_slice($list_data,($page-1)*$row,$row);
        $return['total'] = $total_data;
        return json($return);
    }

    /**
     * @函数或方法说明
     * @充值记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/8 16:20
     */
    public function recharge()
    {
        return $this->fetch();
    }
    /**
     * [recharge 充值明细]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function get_recharge()
    {
        $base = new BaseHomeController;
        //条件
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $request = $this->request->param();
        if($request['start_time'] && $request['end_time']){
            $map['pay_time'] = ['between', [strtotime($request['start_time']), strtotime($request['end_time'])+86399]];
        }elseif($request['end_time']){
            $map['pay_time'] = ['lt',strtotime($request['end_time'])+86399];
        }elseif($request['start_time']){
            $map['pay_time'] = ['gt',strtotime($request['start_time'])];
        }
        $game_id = $this->request->param('game_id', 0, 'intval');
        $game_ids = get_promote_apply_game_id(PID,1);//已申请游戏列表
        if(empty($game_ids)) {
            $game_ids = -1;
        }
        if ($game_id > 0) {
            $map['game_id'] = $game_id;
        } else {
//            $map['game_id'] = ['in', $game_ids];//不做游戏的限制,属于渠道的都显示-20210804-byh
        }
        $promote_id = $this->request->param('promote_id', 0, 'intval');
        if ($promote_id > 0) {
            if ($promote_id == PID) {
                $map['p.id'] = $promote_id;
            } else {
                $map['p.id|p.parent_id'] = $promote_id;
            }
        } else {
            $map['p.id|p.parent_id|top_promote_id'] = PID;
        }
        $pay_way = $this->request->param('pay_way', '');
        if ($pay_way != '') {
            $map['pay_way'] = $pay_way;
        }
        $pay_status = $this->request->param('pay_status',0,'intval');
        if($pay_status > 0){
            $map['pay_status'] = $pay_status-1;
        }
        $model = new SpendModel();
        $exend['field'] = 'p.id as pid,p.parent_id,p.promote_level,tab_spend.id,p.account,user_id,user_account,game_id,game_name,server_id,server_name,game_player_name,role_level,pay_order_number,cost,pay_amount,pay_time,pay_way,spend_ip,pay_status';
        $exend['order'] = 'pay_time desc';
        $exend['join1'][] = ['tab_promote' => 'p'];
        $exend['join1'][] = 'tab_spend.promote_id=p.id';
        $data = $base->data_list_join($model, $map, $exend)->each(function($item,$key){
            if(PID_LEVEL == 1){
                $item['promote_account'] = $item['pid']==PID?'自己':($item['promote_level']!=3?$item['account']:get_parent_name($item['pid']));
            }else{
                $item['promote_account'] = $item['pid'] == PID ? "自己" : $item['account'];
            }
            return $item;
        });
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
        }

        //累计充值
        $map['pay_status'] = 1;
        $exend['order'] = null;
        $exend['field'] = 'sum(cost) as scost,sum(pay_amount) as spay_amount';
        $total = $base->data_list_join_select($model, $map, $exend);
        $return['data'] = $data->toarray()['data'];
        $return['total'] = $total[0];
        return json($return);
    }
    /**
     * @函数或方法说明
     * @角色查询
     * @author: 郭家屯
     * @since: 2020/2/10 9:40
     */
    public function role()
    {
        return $this->fetch();
    }
    /**
     * @函数或方法说明
     * @角色查询
     * @author: 郭家屯
     * @since: 2020/2/10 9:40
     */
    public function get_role()
    {
        $User = new UserModel;
        $condition['id|parent_id|top_promote_id'] = PID;
        $promote_ids = array_column(get_promote_list($condition,'id,account'),'id');
        $user_id = $User->where('promote_id','in',$promote_ids)->where('puid',0)->column('id');
        if($user_id){
            $user_str = '(' . implode(',',$user_id) . ')';
            $where = 'user_id In '.$user_str.' or puid In '.$user_str;
        }else{
            $map['user_id'] = -1;
        }
        $model = new UserPlayInfoModel();
        //添加搜索条件
        $data = $this->request->param();
        $page = intval($data['p']);
        $page = $page ? $page : 1; //默认显示第一页数据
        $row = ($this->request->param('row') ?: config('paginate.list_rows'));//每页数量
        $user_account = $data['user_account'];
        if ($user_account != '') {
            $user_id = $User->field('id')->where('promote_id','in',$promote_ids)->where('account',$user_account)->find();
            if($user_id['id']){
                $where = 'user_id = '.$user_id['id'].' or puid = '.$user_id['id'];
            }else{
                $map['user_id'] = -1;
            }
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $map['game_id'] = $game_id;
        }
        $server_id = $data['server_id'];
        if ($server_id != '') {
            $map['server_id'] = $server_id;
        }
        $rangepickdate = $this->request->param('rangepickdate');
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['play_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
        }
        $role_name = $data['role_name'];
        if ($role_name != '') {
            $map['role_name'] = ['like', '%' . $role_name . '%'];;
        }
        $data = $model
            ->field('id,user_id,user_account,game_name,server_name,role_id,server_id,role_name,role_level,play_time,play_ip,update_time,combat_number,puid,promote_id,promote_account,nickname')
            ->where($map)
            ->where($where)
            ->order('id desc')
            ->paginate($row,false,['query' => $this->request->param()])
            ->each(function($item,$key){
                if($item['puid']>0){
                    $item['user_account'] = get_user_entity($item['puid'],false,'account')['account'].'（' . $item['nickname'] . '）';
                }
            });

        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $data[$k5]['role_name'] = get_ys_string($v5['role_name'],$ys_show_promote['role_show_promote']);
            }
        }

        // 获取分页显示
        return json($data);
    }

    /**
     * @函数或方法说明
     * @arpu统计
     * @author: 郭家屯
     * @since: 2020/11/5 16:06
     */
    public function arpu()
    {
        $map['id|parent_id|top_promote_id'] = PID;
        $promote_ids = array_column(get_promote_list($map,'id,account'),'id');
        $this->assign('promote_ids',$promote_ids);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取arpu统计数据
     * @author: 郭家屯
     * @since: 2020/11/5 16:07
     */
    public function get_arpu()
    {
        $request = $this->request->param();
        if(!$request['start_time']){
            $starttime = date("Y-m-d", strtotime("-6 day"));
        }else{
            $starttime = $request['start_time'];
        }
        if(!$request['end_time']){
            $endtime = date("Y-m-d");
        }else{
            $endtime = $request['end_time'];
        }
        $promote_id = PID;
        $game_id = $this->request->param('game_id', '');
        if(PID_LEVEL == 1){
            $databaseevent = new Database();
            $array_keys = ['time', 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'];
            $data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, $array_keys,1,'wap');
        }else{
            $promoteevent = new Promote();
            $data = $promoteevent->promote_data_arpu($starttime, $endtime, $promote_id,$game_id,'datareporteverypid_','wap');
        }
        //排序
        $new_data = parent::array_order($data['data'], $request['sort_type'], $request['sort']);
        $page = $request['page']?:1;
        $row = $request['row']?:10;
        $fdata = array_slice($new_data,($page-1)*$row,$row);
        $retrun = ['data'=>array_values($fdata),'total'=>$data['total']];
        return json($retrun);
    }
    /**
     * [recharge 注册明细]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function register()
    {
        return $this->fetch();
    }
    /**
     * [recharge 注册明细]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function get_register()
    {
        $model = new \app\member\model\UserModel();
        $base = new BaseHomeController;
        //条件
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['tab_user.account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $request = $this->request->param();
        if($request['start_time'] && $request['end_time']){
            $map['register_time'] = ['between', [strtotime($request['start_time']), strtotime($request['end_time'])+86399]];
        }elseif($request['end_time']){
            $map['register_time'] = ['lt',strtotime($request['end_time'])+86399];
        }elseif($request['start_time']){
            $map['register_time'] = ['gt',strtotime($request['start_time'])];
        }
        $game_id = $this->request->param('game_id', 0, 'intval');
        if ($game_id > 0) {
            $map['fgame_id'] = $game_id;
        }
        $promote_id = $this->request->param('promote_id', 0, 'intval');
        if ($promote_id > 0) {
            if ($promote_id == PID) {
                $map['p.id'] = $promote_id;
            } else {
                $map['p.id|p.parent_id'] = $promote_id;
            }
        } else {
            $map['p.id|p.parent_id|top_promote_id'] = PID;
        }
        $map['tab_user.puid'] = 0;
        $exend['field'] = 'p.id as pid,promote_level,tab_user.id,tab_user.account as user_account,p.account,fgame_id,fgame_name,register_time,register_ip';
        $exend['order'] = 'register_time desc';
        $exend['join1'][] = ['tab_promote' => 'p'];
        $exend['join1'][] = 'tab_user.promote_id=p.id';
        $data = $base->data_list_join($model, $map, $exend)->each(function($item,$key){
            if(PID_LEVEL == 1){
                $item['promote_account'] = $item['pid']==PID?'自己':($item['promote_level']!=3?$item['account']:get_parent_name($item['pid']));
            }else{
                $item['promote_account'] = $item['pid'] == PID ? "自己" : $item['account'];
            }
            $item['register_time'] = date('m-d H:i',$item['register_time']);
            return $item;
        });
        return json($data);
    }

    /**
     * @函数或方法说明
     * @排行榜
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2019/7/17 9:17
     */
    public function rank()
    {
         $data1 = $this->get_rank_info(date("Y-m-d", strtotime("-1 day")),date("Y-m-d", strtotime("-1 day")));
         $data7 = $this->get_rank_info(date("Y-m-d", strtotime("-7 day")),date("Y-m-d", strtotime("-1 day")));
         $data30 = $this->get_rank_info(date("Y-m-d", strtotime("-30 day")),date("Y-m-d", strtotime("-1 day")));
         $this->assign('data1', $data1);
         $this->assign('data7', $data7);
         $this->assign('data30', $data30);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @获取渠道充值信息
     * @param string $starttime
     * @param string $endtime
     *
     * @author: 郭家屯
     * @since: 2019/9/24 17:42
     */
    private function get_rank_info($starttime='',$endtime=''){
        $promoteevent = new Promote();
        $promote = get_promote_entity(PID,'parent_id,pattern');
        if(PID_LEVEL == 3){
            $promote_id = array_column(get_song_promote_lists($promote['parent_id']),'id');
        }else{
            $promote_id =  array_column(get_song_promote_lists(PID),'id');
        }
        if(empty($promote_id)){
            return [];
        }
        $new_data = $promoteevent->promote_data($starttime, $endtime,$promote_id);

        if(PID_LEVEL == 1){
            foreach ($new_data['data'] as $key=>$v){
                $zi_promote_id = array_column(get_song_promote_lists($key),'id');
                if($zi_promote_id){
                    $zi_data = $promoteevent->promote_data($starttime, $endtime,$zi_promote_id);
                    $new_data['data'][$key]['total_pay'] = null_to_0($new_data['data'][$key]['total_pay']+$zi_data['total_data']['total_pay']);
                    $new_data['data'][$key]['count_new_register_user'] = $new_data['data'][$key]['count_new_register_user']+$zi_data['total_data']['new_register_user'];
                }
            }
        }
        if($promote['pattern'] == 0){
            $new_data['total_data']['total_pay'] = null_to_0(array_sum(array_column($new_data['data'],'total_pay')));
            $data = array_slice(parent::array_order($new_data['data'], 'total_pay', 3), 0, 15);
        }else{
            $data = array_slice(parent::array_order($new_data['data'], 'count_new_register_user', 3), 0, 15);
        }
        return $data;
    }

    /**
     * @函数或方法说明
     * @获取角色信息
     * @return string
     *
     * @author: 郭家屯
     * @since: 2020/1/14 16:27
     */
    public function get_game_server()
    {
        $game_id = $this->request->param('game_id');
        if (empty($game_id)) {
            return json_encode(['code' => 1, 'msg' => '确少game_id', 'data' => []]);
        } else {
            $server = new SpendModel();
            $base = new BaseHomeController;
            $map['game_id'] = $game_id;
            $map['server_name'] = ['neq',""];
            $extend['field'] = 'server_name';
            $extend['group'] = 'game_id,server_id,server_name';
            $base_data = $base->data_list($server, $map, $extend)->toarray();
            $data = array_filter($base_data['data']);
            echo json_encode(['code' => 1, 'msg' => '', 'data' => $data]);
        }
    }
    /**
     * @函数或方法说明
     * @获取角色区服信息
     * @return string
     *
     * @author: 郭家屯
     * @since: 2020/1/14 16:27
     */
    public function get_role_server()
    {
        $game_id = $this->request->param('game_id');
        if (empty($game_id)) {
            return json_encode(['code' => 1, 'msg' => '确少game_id', 'data' => []]);
        } else {
            $server = new UserPlayInfoModel();
            $base = new BaseHomeController;
            $map['game_id'] = $game_id;
            $map['server_name'] = ['neq',""];
            $extend['field'] = 'server_id,server_name';
            $extend['group'] = 'game_id,server_id,server_name';
            $base_data = $base->data_list($server, $map, $extend)->toarray();
            $data = array_filter($base_data['data']);
            echo json_encode(['code' => 1, 'msg' => '', 'data' => $data]);
        }
    }
}