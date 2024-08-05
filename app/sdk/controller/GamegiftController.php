<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\sdk\controller;

use app\game\model\GiftbagModel;

class GamegiftController extends BaseController
{

    /**
     * [获取礼包列表]
     * @author 郭家屯[gjt]
     */
    public function gift_list()
    {
        #获取SDK上POST方式传过来的数据 然后base64解密 然后将json字符串转化成数组
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $model = new GiftbagModel();
        if($data['sdk_version'] == 1){
            $map['vip'] = empty($data['is_vip'])?['eq',0]:['gt',0];
            $lists = $model->getGiftLists($data['game_id'], $data['user_id'], $data['sdk_version'], $map, 'gb.sort desc,gb.id desc', $data['row']?:10000, $data['p']?:1);
        }else{
            if($data['is_vip'] == 0){
                $lists = $model->getGiftLists($data['game_id'], $data['user_id'], $data['sdk_version'], $map, 'gb.sort desc,gb.id desc', $data['row']?:10000, $data['p']?:1);
            }else{
                $map['vip'] = 0;
                $lists['nvip'] = $model->getGiftLists($data['game_id'], $data['user_id'], $data['sdk_version'], $map, 'gb.sort desc,gb.id desc', $data['row']?:10000, $data['p']?:1);
                $map['vip'] = ['gt',0];
                $lists['vip'] = $model->getGiftLists($data['game_id'], $data['user_id'], $data['sdk_version'], $map, 'gb.sort desc,gb.id desc', $data['row']?:10000, $data['p']?:1);
            }
        }
        $this->set_message(200, "获取成功",$lists);
    }

    /**
     * [礼包详情]
     * @author 郭家屯[gjt]
     */
    public function gift_detail()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $model = new GiftbagModel();
        $gift = $model->getDetail($data['gift_id'], $data['user_id']);
        $this->set_message(200, "获取成功",$gift);
    }

    /**
     * [领取礼包]
     * @author 郭家屯[gjt]
     */
    public function receive_gift()
    {
        $data = json_decode(base64_decode(file_get_contents("php://input")), true);
        $data = get_real_promote_id($data);
        $model = new GiftbagModel();
        $result = $model->receiveGift($data['gift_id'], $data['user_id']);
        switch ($result) {
            case -1:
                $this->set_message(1031, '礼包不存在或已过期');
                break;
            case -2:
                $this->set_message(1032, '礼包已领取完');
                break;
            case -3:
                $this->set_message(1033, '您已领取过该礼包');
                break;
            case -4:
                $this->set_message(1034, '领取礼包失败');
                break;
            default:
                break;
        }
        $this->set_message(200, "获取成功",$result);
    }
}