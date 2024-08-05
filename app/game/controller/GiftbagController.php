<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\common\controller\BaseController;
use app\game\model\GiftbagModel;
use app\game\model\GiftRecordModel;
use app\game\model\ServerModel;
use app\game\validate\GiftbagValidate;
use cmf\controller\AdminBaseController;
use think\Db;

class GiftbagController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
        $action = request()->action();
        $array = ['record'];
        if (in_array($action, $array)) {
            if (AUTH_USER != 1) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                    $this->error('请购买用户权限');
                } else {
                    $this->error('请购买用户权限', url('admin/main/index'));
                };
            }
        }
    }

    /**
     * @函数或方法说明
     * @更新旧数据礼包
     * @author: 郭家屯
     * @since: 2020/6/11 14:04
     */
    public function updategift()
    {
        $list = Db::table('tab_game_giftbag')->field('id,game_id,giftbag_version')->select();
        foreach ($list as $key=>$v){
            $save=[];
            $game = Db::table('tab_game')->field('relation_game_name')->order('id desc')->find($v['game_id']);
            $sdk_version = explode(',',$v['giftbag_version']);
            $games = Db::table('tab_game')->field('id,sdk_version')->where('relation_game_name',$game['relation_game_name'])->where('sdk_version','in',$sdk_version)->select()->toArray();
            foreach ($games as $k=>$vo){
                if($vo['sdk_version'] == 1){
                    $save['and_id'] = $vo['id'];
                }elseif($vo['sdk_version'] == 2){
                    $save['ios_id'] = $vo['id'];
                }else{
                    $save['h5_id'] = $vo['id'];
                }
            }
            $save['giftbag_version'] = implode(',',array_column($games,'sdk_version'));
            $save['game_name'] = $game['relation_game_name'];
            Db::table('tab_game_giftbag')->where('id',$v['id'])->update($save);
        }
        $this->success("更新成功");
    }

    public function lists()
    {
        $base = new BaseController();
        $model = new GiftbagModel();
        //添加搜索条件
        $data = $this->request->param();
        $id = $data['id'];
        if ($id != '') {
            $map['id'] = $id;
        }
        $game_name = $data['game_name'];
        if ($game_name) {
            $map['game_name'] = $game_name;
        }
        //原-根据游戏名称筛选更改为根据添加时的关联游戏id筛选-20210918-byh
//        $game_id = $data['game_name'];
//        if ($game_id) {
//            //get_relation_game_id()
//            $map['game_id'] = $game_id;
//        }
        $giftbag_version = $data['giftbag_version'];
        if ($giftbag_version) {
            $map['giftbag_version'] = ['like', "%" . addcslashes($giftbag_version, '%') . '%'];
        }
        $status = $data['status'];
        if ($status != '') {
            $map['status'] = $status;
        }
        $giftbag_name = $data['giftbag_name'];
        if ($giftbag_name != '') {
            $map['giftbag_name'] = ['like', '%' . $giftbag_name . '%'];
        }
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        $remain_num = $data['remain_num'];
        if (!empty($remain_num)) {
            $map['remain_num'] = ['lt', $remain_num];
        }
        //查询字段
        $exend['field'] = 'id,vip,game_name,game_id,server_name,giftbag_name,status,start_time,end_time,create_time,giftbag_version,novice_num,remain_num,sort,type';
        //更新时间倒序
        $exend['order'] = 'sort desc,id desc';
        $data = $base->data_list($model, $map, $exend)->each(function ($item, $key) {
            $item['game_name'] = explode('(安卓版)',$item['game_name'])[0];
            $item['game_name'] = explode('(苹果版)',$item['game_name'])[0];
            $item['status_name'] = get_info_status($item['status'], 4);
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [添加礼包]
     * @author 郭家屯[gjt]
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = new GiftbagValidate();
            if (!$validate->check($data)) {
                $this->error($validate->getError());
            }
            $game = get_game_entity($data['game_id'],'relation_game_name');
            if (empty($game)) $this->error('游戏不存在');
            //默认为普通码类型
            if($data['type'] !=2){
                $data['type'] = 1;
            }
            $model = new GiftbagModel();
            $data['create_time'] = time();
            $data['start_time'] = empty($data['start_time']) ? 0 : strtotime($data['start_time']);
            $data['end_time'] = empty($data['end_time']) ? 0 : strtotime($data['end_time']);
            $games = Db::table('tab_game')->field('id,sdk_version')->where('relation_game_name',$game['relation_game_name'])->where('sdk_version','in',$data['giftbag_version'])->select();
            foreach ($games as $k=>$vo){
                if($vo['sdk_version'] == 1){
                    $data['and_id'] = $vo['id'];
                }elseif($vo['sdk_version'] == 2){
                    $data['ios_id'] = $vo['id'];
                }elseif($vo['sdk_version'] == 3){
                    $data['h5_id'] = $vo['id'];
                }else{
                    $data['pc_id'] = $vo['id'];
                }
            }
            $data['giftbag_version'] = implode(',',$data['giftbag_version']);
            $data['game_name'] = $game['relation_game_name'];
//            $data['server_name'] = get_server_name($data['server_id']);
            //激活码去空值和空格
            if($data['type'] == 1){
                $data['novice'] = str_replace(' ', '', $data['novice']);
                $novice = array_filter(explode(',', str_replace(array("\r\n", "\r", "\n"), ",", $data['novice'])));
                $data['novice'] = implode(',', $novice);
                $data['remain_num'] = count($novice);
            }
            $data['novice_num'] = $data['remain_num'];
            $data['accumulate_recharge_limit'] = (float) $data['accumulate_recharge_limit'];
            $result = $model->insert($data);
            if ($result) {
                write_action_log("新增游戏【" . $data['game_name'] . "】礼包");
                $this->success('游戏礼包添加成功', url('lists'));
            } else {
                $this->error('游戏礼包添加失败');
            }
        }
        return $this->fetch();
    }

    /**
     * [编辑礼包]
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $model = new GiftbagModel();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $validate = new GiftbagValidate();
            if (!$validate->scene('edit')->check($data)) {
                $this->error($validate->getError());
            }
            $gift = $model->find($data['id']);
            if(strpos($gift['game_name'],'(苹果版)') !== false || strpos($gift['game_name'],'(安卓版)') !== false){
                $game = get_game_entity($gift['game_id'],'relation_game_id,relation_game_name');
                $data['game_id'] = $game['relation_game_id'];
                $data['game_name'] = $game['relation_game_name'];
            }
            //默认为普通码类型
            if($data['type'] !=2){
                $data['type'] = 1;
            }
            $data['create_time'] = time();
            $data['start_time'] = empty($data['start_time']) ? 0 : strtotime($data['start_time']);
            $data['end_time'] = empty($data['end_time']) ? 0 : strtotime($data['end_time']);
            $games = Db::table('tab_game')->field('id,sdk_version')->where('relation_game_name',$gift['game_name'])->where('sdk_version','in',$data['giftbag_version'])->select();
            $data['and_id'] = $data['ios_id'] = $data['h5_id'] = 0;
            foreach ($games as $k=>$vo){
                if($vo['sdk_version'] == 1){
                    $data['and_id'] = $vo['id'];
                }elseif($vo['sdk_version'] == 2){
                    $data['ios_id'] = $vo['id'];
                }elseif($vo['sdk_version'] == 3){
                    $data['h5_id'] = $vo['id'];
                }else{
                    $data['pc_id'] = $vo['id'];
                }
            }
            $data['giftbag_version'] = implode(',',$data['giftbag_version']);
//            $data['server_name'] = get_server_name($data['server_id']);
            //总共激活码数量
            $num = Db::table('tab_game_gift_record')->field('id')->where('gift_id', $data['id'])->count();
            if($data['type'] == 1){
                //激活码去空值和空格
                $data['novice'] = str_replace(' ', '', $data['novice']);
                $novice = array_filter(explode(',', str_replace(array("\r\n", "\r", "\n"), ",", $data['novice'])));
                $data['novice'] = implode(',', $novice);
                $data['novice_num'] = count($novice) + $num;
                //剩余激活码数量
                $data['remain_num'] = count($novice);
            }
            $data['novice_num'] = $data['remain_num'] + $num;
            $data['accumulate_recharge_limit'] = (float) $data['accumulate_recharge_limit'];
            
            $result = $model->where('id', $data['id'])->update($data);
            if ($result) {
                if($gift['start_time'] != $data['start_time'] || $gift['end_time'] != $data['end_time']){
                    $save['start_time'] = $data['start_time'];
                    $save['end_time'] = $data['end_time'];
                    Db::table('tab_game_gift_record')->where('gift_id',$data['id'])->update($save);
                }
                write_action_log("编辑游戏【" . $gift['game_name'] . "】礼包");
                $this->success('游戏礼包编辑成功', url('lists'));
            } else {
                $this->error('游戏礼包编辑失败');
            }
        }
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $data = $model->find($id);
        if (empty($data)) $this->error('礼包不存在');
        $this->assign('data', $data);
        $game = Db::table('tab_game')->field('group_concat(sdk_version) as sdk_version')->where('relation_game_name',$data->game_name)->find();
        $this->assign('sdk_version',$game['sdk_version']);
        return $this->fetch();
    }


    /**
     * [设置开启状态]
     * @return string
     * @author 郭家屯[gjt]
     */
    public function setstatus()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) $this->error('参数错误');
        $model = new GiftbagModel();
        $status = $this->request->param('status', 0, 'intval');
        $newstatus = $status == 1 ? 0 : 1;
        $result = $model->where('id', $id)->setField('status', $newstatus);
        if ($result) {
            $this->success('设置成功');
        } else {
            $this->error('设置失败');
        }
    }

    /**
     * [删除礼包]
     * @author 郭家屯[gjt]
     */
    public function del()
    {
        $ids = $this->request->param('ids/a');
        if (count($ids) < 1) $this->error('请选择要操作的数据');
        $model = new GiftbagModel();
        if (count($ids) == 1) {
            $gift = $model->find($ids);
        }
        $result = $model->where('id', 'in', $ids)->delete();
        if ($result) {
            if (count($ids) > 1) {
                write_action_log("批量删除礼包");
            } else {
                write_action_log("删除游戏【" . $gift['game_name'] . "】礼包");
            }
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * [ajax获取游戏区服]
     * @return \think\response\Json
     * @author 郭家屯[gjt]
     */
    public function get_ajax_area_list()
    {
        $area = new ServerModel();
        $game_id = $this->request->param('game_id', 0, 'intval');
        if (empty($game_id)) {
            return json([]);
        }
        $list = $area->where('game_id', $game_id)->field('id,server_name,server_num')->select();
        if (empty($list)) {
            return json([]);
        }
        return json($list->toArray());
    }

    /**
     * [礼包领取记录]
     * @author 郭家屯[gjt]
     */
    public function record()
    {
        $base = new BaseController();
        $model = new GiftRecordModel();
        //添加搜索条件
        $data = $this->request->param();
        $user_account = $data['user_account'];
        if ($user_account != '') {
            $map['user_account'] = ['like', '%' . $user_account . '%'];
        }
        $game_id = $data['game_id'];
        if ($game_id) {
            $game_ids = get_game_list('id',['relation_game_id|id'=>$game_id]);
            $map['game_id'] = ['in',array_column($game_ids,'id')];
        }
        $gift_version = $data['gift_version'];
        if ($gift_version) {
            $map['gift_version'] = ['like', "%" . addcslashes($gift_version, '%') . '%'];
        }
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        if ($start_time && $end_time) {
            $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
        } elseif ($end_time) {
            $map['create_time'] = ['lt', strtotime($end_time) + 86400];
        } elseif ($start_time) {
            $map['create_time'] = ['egt', strtotime($start_time)];
        }
        //查询字段
        $exend['field'] = 'id,user_account,game_name,game_id,gift_name,create_time,novice,gift_version';
        //更新时间倒序
        $exend['order'] = 'create_time desc';
        $data = $base->data_list($model, $map, $exend)->each(function ($item, $key) {
            $item['game_name'] = explode('(安卓版)', $item['game_name'])[0];
            $item['game_name'] = explode('(苹果版)', $item['game_name'])[0];
        });
        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }


        $this->assign("data_lists", $data);
        return $this->fetch();
    }

    /**
     * [设置优先级]
     * @author 郭家屯[gjt]
     */
    public function setsort()
    {
        if ($this->request->isPost()) {
            $id = $this->request->param('id', 0, 'intval');
            $sort = $this->request->param('sort', 0, 'intval');
            if (empty($id)) {
                return json(['code' => 0, 'msg' => '设置失败']);
            }
            $model = new GiftbagModel();
            $result = $model->where('id', $id)->setField('sort', $sort);
            if ($result !== false) {
                return json(['code' => 1, 'msg' => '设置成功']);
            } else {
                return json(['code' => 0, 'msg' => '设置失败']);
            }
        }
    }


}
