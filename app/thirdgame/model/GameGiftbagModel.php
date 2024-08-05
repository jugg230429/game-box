<?php

namespace app\thirdgame\model;

use think\Model;

class GameGiftbagModel extends Model
{
    protected $table = 'tab_game_giftbag';

    public function importGift($game_id=0, $param=[])
    {
        // 导入失败个数
        $err_num = 0;
        // 导入成功个数
        $suc_num = 0;
        $game_info = get_game_entity($game_id,'game_name,sdk_version');
        foreach ($param as $key => $val)
        {
             $GiftData = [];
             $GiftData['game_id'] = $game_id;
             $GiftData['game_name'] = $game_info['game_name'];
             $GiftData['giftbag_name'] = $val['giftbag_name']?:'';
             $GiftData['novice'] = $val['novice']?:'';
             $GiftData['digest'] = $val['digest']?:'';
             $GiftData['desribe'] = $val['desribe']?:'';
             $GiftData['competence'] = $val['competence']?:'';
             $GiftData['notice'] = $val['notice']?:'';
             $GiftData['start_time'] = $val['start_time']?:'';
             $GiftData['end_time'] = $val['end_time']?:'';
             $GiftData['create_time'] = time();
             $GiftData['giftbag_version'] = $game_info['sdk_version']?:1;
             $GiftData['novice_num'] = $val['novice_num']?:0;
             $GiftData['remain_num'] = $val['novice_num']?:0;
             $GiftData['sort'] = $val['sort']?:0;
             $GiftData['type'] = $val['type']?:1;
             $GiftData['vip'] = $val['vip']?:0;
             $GiftData['third_gift_id'] = $val['id']?:0;
             $GiftId = $this->insertGetId($GiftData);
             if($GiftId > 0){
                 $suc_num++;
             }else{
                 $err_num++;
             }
        }
        $data = [];
        $data['err_num'] = $err_num;
        $data['suc_num'] = $suc_num;
        return $data;
    }
}