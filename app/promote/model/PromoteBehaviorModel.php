<?php

namespace app\promote\model;

use think\Model;
use think\Db;

class PromoteBehaviorModel extends Model
{

    protected $table = 'tab_promote_behavior';

    protected $autoWriteTimestamp = true;

    /**
     * @函数或方法说明
     * @记录推广员阅读公告行为
     * @param int $promote_id
     *
     * @return bool|int|string
     *
     * @author: 郭家屯
     * @since: 2020/4/3 10:20
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addBehavior($promote_id=0)
    {
        if(empty($promote_id)){
            return false;
        }
        $record = $this->field('id')
                ->where('promote_id',$promote_id)
                ->find();
        if($record){
            $result = $this->where('promote_id',$promote_id)->setField('update_time',time());
        }else{
            $save['promote_id'] = $promote_id;
            $save['update_time'] = time();
            $save['create_time'] = time();
            $result = $this->insert($save);
        }
        return $result;
    }

}