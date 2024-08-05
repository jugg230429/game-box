<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 11:01
 */

namespace app\media\controller;

use app\game\model\GameModel;
use app\game\model\GiftbagModel;
use app\site\model\AdvModel;
use app\common\controller\BaseHomeController;
use app\game\model\GiftRecordModel;

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
     * @author 郭家屯[gjt]
     */
    public function gift()
    {
        $this->slide('slider_gift');//广告图
        $giftbag_name = $this->request->param('giftbag_name/s');
        if (!empty($giftbag_name)) {
            $map['tab_game_giftbag.giftbag_name|tab_game_giftbag.game_name'] = ['like', '%' . $giftbag_name . '%'];
        }
        $type = $this->request->param('type');
        if($type == 1){
            $map['and_id|ios_id'] = ['gt',0];
        }elseif($type == 2){
            $map['pc_id'] = ['gt',0];
        }elseif($type == 3){
            $map['h5_id'] = ['gt',0];
        }
        if(input('vip')>0){
            $map['vip'] = ['between',[1,input('vip')]];
        }
        //$map['g.game_status'] = 1;
        $map['tab_game_giftbag.status'] = 1;
        $map['remain_num'] = ['gt',0];
        //联盟游戏
        if (MEDIA_PID > 0) {
            $map['and_id|ios_id|h5_id'] = ['in', get_promote_game_id(MEDIA_PID)];
        }

        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示

        $map['tab_game_giftbag.remain_num'] = ['gt', 0];
        $map['g.game_status'] = 1;
        $map['g.sdk_area'] = 0; //不显示海外游戏
        $extend['field'] = 'tab_game_giftbag.id as gift_id,g.icon,g.relation_game_name as game_name,g.relation_game_id as game_id,tab_game_giftbag.remain_num,tab_game_giftbag.giftbag_name,tab_game_giftbag.vip,novice_num';
        $extend['order'] = 'tab_game_giftbag.sort desc,tab_game_giftbag.id desc';
        $extend['join1'] = array(['tab_game' => 'g'], 'g.id=tab_game_giftbag.game_id', 'left');
        $extend['row'] = 24;
        $model = new GiftbagModel();
        $base = new BaseHomeController();
        $lists = $base->data_list_join_time($model, $map, $extend)->each(function ($item, $key) {
            if (session('member_auth.user_id')) {
                $giftRecordModel = new GiftRecordModel();
                $gift_record = $giftRecordModel
                    ->where('gift_id', $item['gift_id'])
                    ->where('user_id', session('member_auth.user_id'))
                    ->find();
                //是否已领取
                if ($gift_record) {
                    $gift_record = $gift_record->toArray();
                    $item['received'] = 1;
                    $item['novice'] = $gift_record['novice'];
                } else {
                    $item['received'] = 0;
                }
            } else {
                $item['received'] = 0;
            }
            return $item;
        });
        $vipOption = array_filter(explode(',',cmf_get_option('vip_set')['vip']));
        $page = $lists->render();
        $this->assign('vip_level', $vipOption?$vipOption:[]);
        $this->assign('page', $page);
        $this->assign('data', $lists);
        return $this->fetch();
    }


    /**
     * @函数或方法说明
     * @页游游戏礼包
     * @author: 郭家屯
     * @since: 2020/9/22 17:13
     */
    public function game_gift()
    {
        $game_id = $this->request->param('game_id');
        $map['tab_game_giftbag.status'] = 1;
        $map['pc_id'] = $game_id;
        //$map['tab_game_giftbag.remain_num'] = ['gt', 0];
        $extend['field'] = 'tab_game_giftbag.id as gift_id,g.icon,g.relation_game_name as game_name,g.relation_game_id as game_id,tab_game_giftbag.remain_num,tab_game_giftbag.giftbag_name,tab_game_giftbag.vip,novice_num,digest';
        $extend['order'] = 'tab_game_giftbag.sort desc,tab_game_giftbag.id desc';
        $extend['join1'] = array(['tab_game' => 'g'], 'g.id=tab_game_giftbag.game_id', 'left');
        $extend['row'] = 50;
        $model = new GiftbagModel();
        $base = new BaseHomeController();
        $lists = $base->data_list_join_time($model, $map, $extend)->each(function ($item, $key) {
            if (session('member_auth.user_id')) {
                $giftRecordModel = new GiftRecordModel();
                $gift_record = $giftRecordModel
                        ->where('gift_id', $item['gift_id'])
                        ->where('user_id', session('member_auth.user_id'))
                        ->find();
                //是否已领取
                if ($gift_record) {
                    $gift_record = $gift_record->toArray();
                    $item['received'] = 1;
                    $item['novice'] = $gift_record['novice'];
                } else {
                    $item['received'] = 0;
                }
            } else {
                $item['received'] = 0;
            }
            return $item;
        });
        $this->assign('data', $lists);
        $game = get_game_entity($game_id,'groom');
        $this->assign('game',$game);
        return $this->fetch();
    }

    /**
     * [领取礼包]
     * @return \think\response\Json
     * @author 郭家屯[gjt]
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
     * @author 郭家屯[gjt]
     */
    public function detail()
    {
        $gift_id = $this->request->param('gift_id');
        if (empty($gift_id)) $this->error('参数错误');
        $model = new GiftbagModel();
        $gift = $model->getDetail($gift_id, session('member_auth.user_id'));
        if (empty($gift)) $this->error('礼包不存在或是已过期');
        if($gift['h5_id']){//H5游戏是否开启
            $h5_game = get_game_entity($gift['h5_id'],'game_status');
            if($h5_game['game_status'] ==0){
                $gift['h5_id'] = 0;
            }
        }
        $this->assign('data', $gift);
        //获取游戏下载信息
        $gamemodel = new GameModel();
        $game_id = $gift['and_id'] ? : ($gift['ios_id']?:0);
        if($game_id > 0){
            $relation_game_id = get_game_entity($game_id,'relation_game_id')['relation_game_id'];
            $gameinfo = $gamemodel->getRelationGameDetail($relation_game_id);
            $gameinfo['url'] = cmf_get_domain() . url('Downfile/index', ['pid' => $gameinfo['relation_game_id']]);
            $this->assign('gameinfo', $gameinfo);
        }
        $this->hotGift();//热门礼包
        $game_ids = $gamemodel->getRelationGameId($gift['relation_game_id']);
        $ids = array_column($game_ids, 'id');
        $map['gb.game_id'] = ['in', $ids];
        $map['gb.id'] = ['neq', $gift_id];
        $this->gameOtherGift($map);//游戏其它礼包
        return $this->fetch();
    }

    public function hotGift()
    {
        $model = new GiftbagModel();
        //联盟游戏
        if (MEDIA_PID > 0) {
            $map['and_id|ios_id|h5_id'] = ['in', get_promote_game_id(MEDIA_PID)];
        }
        $lists = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 6);
        $this->assign('gift', $lists);
    }

    public function gameOtherGift($map = [])
    {
        $model = new GiftbagModel();
        $lists = $model->getGiftIndexLists(session('member_auth.user_id'), $map, 8);
        $this->assign('othergift', $lists);
    }
}
