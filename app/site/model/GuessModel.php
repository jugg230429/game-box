<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class GuessModel extends Model
{

    protected $table = 'tab_guess';

    protected $autoWriteTimestamp = true;

    /**
     * [获取猜你喜欢列表]
     * @author 郭家屯[gjt]
     */
    public function getLists()
    {
        $lists = $this
            ->field('title,url,icon')
            ->where('start_time', ['<', time()], ['=', 0], 'or')
            ->where('end_time', ['>', time()], ['=', 0], 'or')
            ->order('sort desc')
            ->select()->each(function ($item, $key) {
                $item['icon'] = cmf_get_image_url($item['icon']);
                return $item;
            });
        return $lists ? $lists->toArray() : [];
    }
}