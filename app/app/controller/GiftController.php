<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/15
 * Time: 11:01
 */

namespace app\app\controller;

use app\game\model\GiftbagModel;
use app\game\model\GiftRecordModel;

class GiftController extends BaseController
{

    /**
     * [礼包首页]
     * @return mixed
     * @author chen
     */
    public function gift()
    {
        $request = $this->request->param();
        if($request['model'] == 1){
            $map['tab_game_giftbag.giftbag_version'] = ['like','%'.$request['sdk_version'].'%'];
        }else{
            $map['tab_game_giftbag.giftbag_version'] = ['like','%3%'];
        }
        if(PROMOTE_ID > 0){
            $game_ids = get_promote_game_id(PROMOTE_ID);
            $map['and_id|ios_id|h5_id'] = ['in',$game_ids];
        }
        $giftbgmodel = new GiftbagModel();

        //排除测试状态中的游戏 和 渠道独占游戏
        $map['g.test_game_status'] = 0;  // 测试游戏不显示,但可以正常进入
        $map['g.only_for_promote'] = 0;  // 渠道独占的游戏不显示
        // $test_game_ids = get_test_game_ids(true);
        // if ($request['model'] == 1) {
        //     if ($request['sdk_version'] == '1') {
        //         $map['gb.and_id'] = ['not in', $test_game_ids];
        //     } else {
        //         $map['gb.ios_id'] = ['not in', $test_game_ids];
        //     }
        // } else {
        //     $map['gb.h5_id'] = ['not in', $test_game_ids];
        // }

        $lists = $giftbgmodel->getGameGiftLists($map, USER_ID);
        $this->set_message(200,'获取成功',$lists);
    }


    /**
     * [领取礼包]
     * @return \think\response\Json
     * @author chen
     */
    public function getgift()
    {
        $request = $this->request->param();
        $giftbgmodel = new GiftbagModel();
        $result = $giftbgmodel->receiveGift($request['gift_id'], USER_ID);
        switch ($result) {
            case -1:
                $this->set_message(1021,'礼包不存在或已过期');
                break;
            case -2:
                $this->set_message(1022,'礼包已领取完');
                break;
            case -3:
                $this->set_message(1023,'您已领取过该礼包');
                break;
            case -4:
                $this->set_message(1024,'领取礼包失败');
                break;
        }
        $data['novice'] = $result;
        $this->set_message(200,'领取成功',$data);
    }

    /**
     * [礼包详情]
     * @author chen
     */
    public function giftdetail()
    {
        $request = $this->request->param();
        $model = new GiftbagModel();
        $gift = $model->getDetail($request['gift_id'], USER_ID);
        $gift['start_time'] = $gift['start_time'] ? date('Y-m-d H:i:s',$gift['start_time']) : '永久';
        $gift['end_time'] = $gift['end_time'] ? date('Y-m-d H:i:s',$gift['end_time']) : '永久';
        $gift['desribe'] = $gift['desribe'] == '' ? '' : str_replace(PHP_EOL,'', trim($gift['desribe']));
        $gift['notice'] = $gift['notice'] == '' ? '' : str_replace(PHP_EOL,'', trim($gift['notice']));
        $gift['digest'] = $gift['digest'] == '' ? '' : str_replace(PHP_EOL, '', trim($gift['digest']));
        $this->set_message(200,'获取成功',$gift);
    }

    /**
     * @函数或方法说明
     * @删除已过期礼包
     * @author: 郭家屯
     * @since: 2020/2/27 11:28
     */
    public function deleteGift()
    {
        $request = $this->request->param();
        $id = $request['gift_id'];
        if (empty($id)) {
            $this->set_message(1031,'请选择要删除的数据');
        }
        $map['user_id'] = USER_ID;
        if($id == 'all'){
            $map['end_time'] = array(['elt', time()], ['neq', 0]);
        }else{
            $map['id'] = $id;
        }
        $model = new GiftRecordModel();
        $result = $model->deleteMyGift($map);
        if ($result) {
            $this->set_message(200,'删除成功');
        } else {
            $this->set_message(1032,'删除失败');
        }
    }
}
