<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class LinkModel extends Model
{

    protected $table = 'tab_links';

    protected $autoWriteTimestamp = true;

    /**
     * [获取友情链接列表]
     * @return false|\PDOStatement|string|\think\Collection
     * @author 郭家屯[gjt]
     */
    public function getLists()
    {
        $lists = $this->field('title,link_url')
            ->where('status', 1)
            ->select();
        return $lists;
    }


}