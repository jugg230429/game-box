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
namespace app\channelsite\controller;

use app\member\model\UserPlayInfoModel;
use app\member\model\UserPlayModel;
use app\member\model\UserModel;
use think\Db;
use think\Request;
use app\promote\model\PromoteModel;
use app\common\controller\BaseHomeController;
use app\datareport\event\DatabaseController as Database;
use cmf\paginator\Bootstrap;
use app\recharge\model\SpendModel;
use app\datareport\event\PromoteController as Promote;

class QueryController extends BaseController
{
    //数据汇总
    public function summary()
    {
        if (AUTH_GAME != 1 || AUTH_PAY != 1) {
            $this->error('请购买充值权限和游戏权限');
        }
        $request = $this->request->param();
        $date = $request['rangepickdate'];
        if($date) {
            $dateexp = explode('至', $date);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
        }
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
                            $list_data[$lk][$llk] = $llv+array_sum(array_column($zi_data,$llk));
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
        parent::array_page($list_data, $request);
        $this->assign('total_data',$total_data);
        return $this->fetch();
    }

    /**
     * [recharge 充值明细]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function recharge()
    {
        if (AUTH_GAME != 1 || AUTH_PAY != 1) {
            return $this->fetch();
        }
        $base = new BaseHomeController;
        //条件
        $account = $this->request->param('user_account', '');
        if ($account != '') {
            $map['user_account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        $pay_order_number = $this->request->param('pay_order_number', '');
        if ($pay_order_number != '') {
            $map['pay_order_number'] = ['like', "%" . addcslashes($pay_order_number, '%') . '%'];
        }
        $server_id = $this->request->param('server_id');
        if($server_id){
            $map['server_name'] = $server_id;
        }
        $rangepickdate = $this->request->param('rangepickdate');
        $rangepickdate = urldecode($rangepickdate);
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['pay_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
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
        $pay_status = $this->request->param('pay_status');
        if($pay_status != ''){
            $map['pay_status'] = $pay_status;
        }
        $model = new SpendModel();
        $exend['field'] = 'p.id as pid,p.parent_id,p.promote_level,tab_spend.id,p.account,user_id,user_account,game_id,server_id,server_name,game_player_name,role_level,pay_order_number,cost,pay_amount,pay_time,pay_way,spend_ip,pay_status';
        $exend['order'] = 'pay_time desc';
        $exend['join1'][] = ['tab_promote' => 'p'];
        $exend['join1'][] = 'tab_spend.promote_id=p.id';
        $data = $base->data_list_join($model, $map, $exend);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
            //增加处理角色查看隐私情况
            if($ys_show_promote['role_show_promote_status'] == 1){//开启了角色查看隐私1
                $data[$k5]['game_player_name'] = get_ys_string($v5['game_player_name'],$ys_show_promote['role_show_promote']);
            }
        }

        //累计充值
        $map['pay_status'] = 1;
        $exend['order'] = null;
        $exend['field'] = 'sum(cost) as scost,sum(pay_amount) as spay_amount';
        $total = $base->data_list_join_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("total", $total[0]);
        $this->assign("page", $page);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @角色查询
     * @author: 郭家屯
     * @since: 2020/2/10 9:40
     */
    public function role()
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
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }


    /**
     * @函数或方法说明
     * @APPU统计
     * @author: 郭家屯
     * @since: 2020/10/16 11:45
     */
    public function arpu()
    {
        $request = $this->request->param();
        //时间
        if (empty($request['rangepickdate'])) {
            $date = date("Y-m-d", strtotime("-6 day")) . '至' . date("Y-m-d");
        } else {
            $date = $request['rangepickdate'];
        }
        $dateexp = explode('至', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $promote_id = PID;
        $game_id = $this->request->param('game_id', '');
        if(PID_LEVEL == 1){
            $databaseevent = new Database();
            $array_keys = ['time', 'new_register_user', 'active_user', 'pay_user', 'total_order', 'total_pay', 'new_pay_user', 'new_total_pay', 'fire_device'];
            $new_data = $databaseevent->basedata($starttime, $endtime, $promote_id, $game_id, $array_keys);
        }else{
            $promoteevent = new Promote();
            $new_data = $promoteevent->promote_data_arpu($starttime, $endtime, $promote_id,$game_id);
        }
        //排序
        $new_data = parent::array_order($new_data, $request['sort_type'], $request['sort']);
        parent::array_page($new_data, $request);
        $map['id|parent_id|top_promote_id'] = PID;
        $promote_ids = array_column(get_promote_list($map,'id,account'),'id');
        $this->assign('promote_ids',$promote_ids);
        return $this->fetch();
    }

    /**
     * [recharge 注册明细]
     * @return [type] [description]
     * @author [yyh] <[<email address>]>
     */
    public function register()
    {
        $model = new \app\member\model\UserModel();
        $base = new BaseHomeController;
        //条件
        $account = $this->request->param('user_account', '');
        $equipment_num = $this->request->param('equipment_num', '');
        if ($account != '') {
            $map['tab_user.account'] = ['like', '%' . addcslashes($account, '%') . '%'];
        }
        if ($equipment_num != '') {
            $map['tab_user.equipment_num'] = ['like', '%' . addcslashes($equipment_num, '%') . '%'];
        }
        $user_id = $this->request->param('user_id');
        if($user_id > 0){
            $map['tab_user.id'] = $user_id;
        }
        $rangepickdate = $this->request->param('rangepickdate');
        if($rangepickdate) {
            $dateexp = explode('至', $rangepickdate);
            $starttime = $dateexp[0];
            $endtime = $dateexp[1];
            $this -> assign('start', $starttime);
            $this -> assign('end', $endtime);
            $map['register_time'] = ['between', [strtotime($starttime), strtotime($endtime) + 86399]];
        }else{
            $this -> assign('start', date("Y-m-d", strtotime("-7 day")));
            $this -> assign('end', date("Y-m-d", strtotime("-1 day")));
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
            $map['p.id|p.parent_id|p.top_promote_id'] = PID;
        }
        $promote_zi = get_promote_list(['id|parent_id|top_promote_id'=>PID],'id');
        if($promote_zi){
            $promote_zi = array_column($promote_zi,'id');
        }
        $promote_zi[] = PID;
        $promote_zi = implode(',',$promote_zi);
        $map['tab_user.puid'] = 0;
        $exend['field'] = 'p.id as pid,sum(s.cost) as total_cost,sum(s.pay_amount) as total_pay,promote_level,tab_user.id,tab_user.account as user_account,p.account,fgame_id,fgame_name,register_time,register_ip,device_name,equipment_num';
        $exend['order'] = 'register_time desc';
        $exend['join1'][] = ['tab_promote' => 'p'];
        $exend['join1'][] = 'tab_user.promote_id=p.id';
        $exend['join1'][] = 'left';
        $exend['join2'][] = ['tab_spend' => 's'];
        $exend['join2'][] = 'tab_user.id=s.user_id and s.pay_status=1 and s.promote_id in ('.$promote_zi.')';
        $exend['join2'][] = 'left';
        $exend['group'] = 'tab_user.id';
        $data = $base->data_list_join($model, $map, $exend);
        // 判断当前渠道是否有权限显示完成整手机号或完整账号
        $ys_show_promote = get_promote_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_promote['account_show_promote_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_promote['account_show_promote']);
            }
        }

        //累计充值
        $exend['order'] = null;
        $exend['group'] = null;
        $exend['field'] = 'count(tab_user.id) as ucount,sum(s.cost) as total_cost,sum(s.pay_amount) as total_pay';
        $total = $base->data_list_join_select($model, $map, $exend);
        // 获取分页显示
        $page = $data->render();
        $this->assign("data_lists", $data);
        $this->assign("total", $total[0]);
        $this->assign("page", $page);
        return $this->fetch();
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
        $data1 = $this->get_rank_info(date("Y-m-d"),date("Y-m-d"));
        $data7 = $this->get_rank_info(date("Y-m-d", strtotime("-6 day")),date("Y-m-d"));
        $data30 = $this->get_rank_info(date("Y-m-d", strtotime("-29 day")),date("Y-m-d"));
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
        $promote = get_promote_entity(PID,'parent_id');
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
                    $new_data['data'][$key]['total_pay'] = $new_data['data'][$key]['total_pay']+$zi_data['total_data']['total_pay'];
                }
            }
        }
        $new_data['total_data']['total_pay'] = array_sum(array_column($new_data['data'],'total_pay'));
        $data = array_slice(parent::array_order($new_data['data'], 'total_pay', 3), 0, 15);
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

    /**
     * @函数或方法说明
     * @获取绑币记录
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/11/9 10:24
     */
    public function bind_record()
    {
        $user_id = $this->request->param('user_id');
        $base = new BaseHomeController();
        $model = new UserPlayModel();
        $map['user_id'] = $user_id;
        $extend['field'] = 'g.game_name,bind_balance,g.icon,g.sdk_version';
        $extend['join1'] = [['tab_game'=>'g'],'g.id = tab_user_play.game_id','left'];
        $list = $base->data_list_join_select($model,$map,$extend);
        $this->assign('data_lists',$list);
        return $this->fetch();
    }
}