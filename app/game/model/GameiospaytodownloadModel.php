<?php

namespace app\game\model;

use think\Model;

/**
 * wjd 2021-8-30 17:39:30
 */
class GameiospaytodownloadModel extends Model
{

    protected $table = 'tab_game_ios_pay_to_download';

    protected $autoWriteTimestamp = true;

    // 查询游戏超级签是否需要付费下载, 付费多少 (传入relation_game_id)



}

