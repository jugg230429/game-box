<?php

namespace app\issue\model;

use think\Model;

class IssueGameApplyModel extends Model
{

    protected $table = 'tab_issue_game_apply';
    protected $autoWriteTimestamp = true;


    /**
     * @新增申请数据
     *
     * @author: zsl
     * @since: 2020/7/14 17:11
     */
    public function add($gameInfo, $platformId, $openUserId)
    {

        $gameInfo['game_name'] = str_replace('(苹果版)', '', $gameInfo['game_name']);
        $gameInfo['game_name'] = str_replace('(安卓版)', '', $gameInfo['game_name']);
        $this -> game_id = $gameInfo['id'];
        $this -> game_name = $gameInfo['game_name'];
        $this -> platform_id = $platformId;
        $this -> open_user_id = $openUserId;
        $this -> ratio = $gameInfo['ff_ratio'];
        $this -> status = 0;
        $this -> enable_status = 0;
        $this -> register_url = '';
        $this -> platform_config = '';
        $this -> sdk_version = $gameInfo['sdk_version'];
        $this -> pt_type = get_pt_type($platformId);
        $result = $this -> allowField(true) -> save();
        return $result;
    }


}

