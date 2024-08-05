<?php
/**
 * Created by gjt.
 * User: Administrator
 * Date: 2019/1/23
 * Time: 15:34
 */

namespace app\game\controller;

use app\common\controller\BaseController;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

class ExportController extends AdminBaseController
{
    function expUser()
    {
        $id = $this->request->param('id', 0, 'intval');
        $xlsName = $this->request->param('xlsname');
        $param = $this->request->param();
        switch ($id) {
            case 1://游戏区服列表导出
                $xlsCell = array(
                    array('id', "区服ID"),
                    array('game_name', "游戏名称"),
                    array('server_name', "区服名称"),
                    array('sdk_version', "运营平台"),
                    array('status', "显示状态"),
                    array('start_time', "开服时间"),
                );
                $game_id = $param['game_id'];
                if ($game_id > 0) {
                    $map['game_id'] = $game_id;
                }
                $sdk_version = $param['sdk_version'];
                if ($sdk_version != '') {
                    $map['gs.sdk_version'] = $sdk_version;
                }
                $status = $param['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                if ($start_time && $end_time) {
                    $map['start_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['start_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['start_time'] = ['egt', strtotime($start_time)];
                }
                $xlsData = Db::table('tab_game_server')->alias('gs')->field('gs.id,g.game_name,gs.server_name,gs.sdk_version,gs.status,gs.start_time')
                    ->join(['tab_game' => 'g'], "gs.game_id=g.id", 'left')
                    ->where($map)
                    ->order('start_time desc')
                    ->select()
                    ->each(function ($item, $key) {
                        $item['sdk_version'] = get_info_status($item['sdk_version'], 5);
                        $item['status'] = get_info_status($item['status'], 4);
                        $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
                        return $item;
                    });
                write_action_log("导出游戏区服");
                break;
            case 2://游戏礼包列表导出
                $xlsCell = array(
                    array('game_name', '游戏名称'),
                    array('giftbag_name', "礼包名称"),
                    array('vip', "会员等级"),
                    array('giftbag_version', "运营平台"),
                    array('type', "激活码类型"),
                    array('novice_num', "礼包总数"),
                    array('remain_num', "剩余数量"),
                    array('status', "显示状态"),
                    array('create_time', '更新时间'),
                    array('validity_time', "有效期限"),
                );
                $game_id = $param['game_name'];
                if ($game_id) {
                    $map['game_id'] = $game_id;
                }
                $giftbag_version = $param['giftbag_version'];
                if ($giftbag_version) {
                    $map['giftbag_version'] = ['like', "%" . addcslashes($giftbag_version, '%') . '%'];
                }
                $status = $param['status'];
                if ($status != '') {
                    $map['status'] = $status;
                }
                $giftbag_name = $param['giftbag_name'];
                if ($giftbag_name != '') {
                    $map['giftbag_name'] = ['like', '%' . $giftbag_name . '%'];
                }
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }
                $xlsData = Db::table('tab_game_giftbag')->field('giftbag_version,game_name,giftbag_name,status,start_time,end_time,create_time,novice_num,remain_num,type,vip')->where($map)->order('sort desc,id desc')->select()
                    ->each(function ($item, $key) {
                        $item['giftbag_version'] = get_info_status($item['giftbag_version'], 5);
                        $item['status'] = get_info_status($item['status'], 4);
                        $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                        $item['validity_time'] = (empty($item['start_time']) ? '永久' : date('Y-m-d', $item['start_time'])) . "~" . (empty($item['end_time']) ? '永久' : date('Y-m-d', $item['end_time']));
                        $item['vip'] = $item['vip'] >0 ? 'VIP' . $item['vip'] : '--';
                        $item['type'] =  $item['type'] == 1 ? '普通码' : '统一码';
                        return $item;
                    });
                write_action_log("导出游戏礼包");
                break;

            case 3://游戏礼包领取列表导出
                $xlsCell = array(
                    array('user_account', '玩家账号'),
                    array('game_name', "游戏名称"),
                    array('gift_name', "礼包名称"),
                    array('gift_version', "运营平台"),
                    array('novice', "礼包激活码"),
                    array('create_time', "领取时间"),
                );
                $user_account = $param['user_account'];
                if ($user_account != '') {
                    $map['user_account'] = ['like', '%' . $user_account . '%'];
                }
                $game_id = $param['game_id'];
                if ($game_id) {
                    $map['game_id'] = $game_id;
                }
                $gift_version = $param['gift_version'];
                if ($gift_version) {
                    $map['gift_version'] = ['like', "%" . addcslashes($gift_version, '%') . '%'];
                }
                $start_time = $param['start_time'];
                $end_time = $param['end_time'];
                if ($start_time && $end_time) {
                    $map['create_time'] = ['between', [strtotime($start_time), strtotime($end_time) + 86399]];
                } elseif ($end_time) {
                    $map['create_time'] = ['lt', strtotime($end_time) + 86400];
                } elseif ($start_time) {
                    $map['create_time'] = ['egt', strtotime($start_time)];
                }
                $xlsData = Db::table('tab_game_gift_record')->field('user_account,gift_version,game_name,gift_name,novice,create_time')->where($map)->order('create_time desc')->select()
                    ->each(function ($item, $key) {
                        $item['gift_version'] = get_info_status($item['gift_version'], 5);
                        $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
                        return $item;
                    });
                write_action_log("导出游戏礼包领取记录");
                break;
        }
        foreach ($xlsData as $key => $val) {
            foreach ($xlsCell as $k => $v) {
                if (isset($v[2])) {
                    $ar_k = array_search('*', $v);
                    if ($ar_k !== false) {
                        $v[$ar_k] = $val[$v[0]];
                    }
                    $fun = $v[2];
                    $param = $v;
                    unset($param[0], $param[1], $param[2]);
                    $xlsData[$key][$v[0]] = call_user_func_array($fun, $param);
                }
            }
        }
        $this->exportExcel($xlsName, $xlsCell, $xlsData);
    }

}
