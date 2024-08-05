<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 11:01
 */

namespace app\mobile\controller;

use app\game\model\GiftbagModel;

class GiftController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('index/index'));
            };
        }
        $action = request()->action();
        if ($action == 'getgift' && AUTH_USER != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买用户权限');
            } else {
                $this->error('请购买用户权限', url('index/index'));
            };
        }
    }

    /**
     * [礼包首页]
     * @return mixed
     * @author chen
     */
    public function index()
    {
        if(MODEL == 1){
            $sdk_version = get_devices_type();
        }else{
            $sdk_version = 3 ;
        }
        //$map['g.sdk_version'] = $sdk_version;
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['tab_game_giftbag.giftbag_version'] = ['like','%'.$sdk_version.'%'];
        //联盟游戏
        if (MOBILE_PID > 0) {
            $map['and_id|ios_id|h5_id'] = ['in', get_promote_game_id(MOBILE_PID)];
        }
        $giftbgmodel = new GiftbagModel();

        //排除测试状态中的游戏
        $test_game_ids = get_test_game_ids(true);
        switch ($sdk_version) {
            case '1':
                $map['gb.and_id'] = ['not in', $test_game_ids];
                break;
            case '2':
                $map['gb.ios_id'] = ['not in', $test_game_ids];
                break;
            case '3':
                $map['gb.h5_id'] = ['not in', $test_game_ids];
                break;
            default:
                break;
        }

        $lists = $giftbgmodel->getGameGiftLists($map, session('member_auth.user_id'));
        $this->assign('data', $lists);
        return $this->fetch();
    }


    /**
     * [领取礼包]
     * @return \think\response\Json
     * @author chen
     */
    public function getgift()
    {
        if (!session('member_auth')) {
            $this->error('您还未登录，请登录后领取');
        }
        $gift_id = $this->request->param('gift_id/d');
        if (empty($gift_id)) $this->error('参数错误');
        $giftbgmodel = new GiftbagModel();
        $result = $giftbgmodel->receiveGift($gift_id, session('member_auth.user_id'));
        switch ($result) {
            case -1:
                $this->error('礼包不存在或已过期');
                break;
            case -2:
                $this->error('礼包已领取完');
                break;
            case -3:
                $this->error('您已领取过该礼包');
                break;
            case -4:
                $this->error('领取礼包失败');
                break;
        }
        return json(['code' => 1, 'novice' => $result]);
    }

    /**
     * [礼包详情]
     * @author chen
     */
    public function detail()
    {
        $gift_id = $this->request->param('gift_id');
        if (empty($gift_id)) $this->error('参数错误');
        $model = new GiftbagModel();
        $gift = $model->getDetail($gift_id, session('member_auth.user_id'));
        if (empty($gift)) $this->error('礼包不存在或是已过期');
        $gift['game_name'] = mb_substr($gift['game_name'], 0, -5);
        $gift['desribe'] = $gift['desribe'] == '' ? '' : explode(PHP_EOL, trim($gift['desribe']));
        $gift['notice'] = $gift['notice'] == '' ? '' : explode(PHP_EOL, trim($gift['notice']));
        $gift['digest'] = $gift['digest'] == '' ? '' : explode(PHP_EOL, trim($gift['digest']));
        $this->assign('data', $gift);
        return $this->fetch();
    }

    /**
     * [搜索礼包]
     * @return mixed
     * @author chen
     */
    public function searchGift($gift_name = '')
    {
        if (empty($gift_name)) {
            return json(['code' => 0, 'msg' => '']);
        }
        $sdk_version = get_devices_type();
        $map['tab_game_giftbag.giftbag_version']= $map['g.sdk_version']  = $sdk_version;
        $map['remain_num'] = ['gt', 0];
        $map['tab_game_giftbag.game_name'] = ['like', '%' . $gift_name . '%'];
        //联盟游戏
        if (MOBILE_PID > 0) {
            $map['game_id'] = ['in', get_promote_game_id(MOBILE_PID)];
        }
        $giftbgmodel = new GiftbagModel();
        $data = $giftbgmodel->getGiftIndexLists(session('member_auth.user_id'), $map);
        foreach ($data as $k => $v) {
            $data[$k]['url'] = url('Gift/detail', ['gift_id' => $v['gift_id']]);
        }
        if (empty($data)) {
            return json(['code' => 0, 'msg' => '']);
        } else {
            return json(['code' => 1, 'data' => $data]);
        }
    }


}
