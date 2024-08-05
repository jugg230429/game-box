<?php

namespace app\member\model;

use think\Model;

class UserConfigModel extends Model
{

    protected $table = 'tab_user_config';

    protected $autoWriteTimestamp = true;

    /**
     * [获取实名认证设置信息]
     * @param string $type
     * @return array|false|\PDOStatement|string|Model
     * @author 郭家屯[gjt]
     */
    public function getSet($type = 'age')
    {
        $config = $this->where('name', $type)->find()->toArray();
        $config['config'] = json_decode($config['config'], true);
        return $config;
    }
}