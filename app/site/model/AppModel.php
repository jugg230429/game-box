<?php

namespace app\site\model;

use think\Model;

/**
 * gjt
 */
class AppModel extends Model
{

    protected $table = 'tab_app';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @获取app列表
     * @author: 郭家屯
     * @since: 2020/2/19 20:29
     */
    public function getlist()
    {
        $data = $this->field('id,name,version,file_size,file_url,op_account,create_time')
                ->select()->toArray();
        return $data;
    }


    /**
     * @函数或方法说明
     * @获取推广员APP申请链接
     * @param int $promote_id
     *
     * @author: 郭家屯
     * @since: 2020/2/24 16:40
     */
    public function getPromotelist($promote_id=0)
    {
        $data = $this->alias('a')
                ->field('p.status,p.enable_status,a.file_size,a.version,a.id,a.file_url,p.id as apply_id,p.app_version,p.plist_url,p.type,p.is_user_define,p.app_new_name,p.app_new_icon,p.super_url,p.is_user_define,p.file_size2')
                ->join(['tab_promote_app'=>'p'],'a.id=p.app_id and p.promote_id='.$promote_id,'left')
                ->select()->toArray();
        return $data ?:[];
    }
}