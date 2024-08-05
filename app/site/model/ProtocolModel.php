<?php

namespace app\site\model;

use think\Model;

class ProtocolModel extends Model
{

    protected $table = 'tab_protocol';

    protected $autoWriteTimestamp = true;

    /**
     * 方法 getProtocolTitle
     *
     * @descript 获取用户注册协议标题
     *
     * @param $map
     * @return mixed|string
     *
     * @author 鹿文学 fyj301415926@126.com
     * @since 2021/4/23 0023 10:37
     */
    public function getProtocolTitle($map)
    {
        try {
            $protocol = $this -> field('title') -> where($map) -> order('update_time desc') -> find();
            if (!empty($protocol)) {
                return $protocol['title'];
            }
            return '';
        } catch (\Exception $e) {
            return '';
        }
    }
    
}