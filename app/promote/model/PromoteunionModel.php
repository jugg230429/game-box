<?php

namespace app\promote\model;

use think\Model;
use think\Pinyin;

/**
 * gjt
 */
class PromoteunionModel extends Model
{

    protected $table = 'tab_promote_union';

    protected $autoWriteTimestamp = true;

    //申请
    public function apply_union($data = [])
    {
        $add['union_id'] = $data['union_id'];
        $add['union_account'] = $data['union_account'];
        $add['domain_url'] = $data['domain_url'];
        $add['apply_domain_type'] = $data['apply_domain_type'];

        $promote_auto_audit = \think\Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit_union'])->find();
        $add['status'] = $promote_auto_audit['status'] ? 1 : 0;
        $add['apply_time'] = time();
        $res = $this->insert($add);
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }

    //驳回后 修改
    public function update_union($data = [])
    {
        $d = $this->get($data['id']);
        $d->union_id = $data['union_id'];
        $d->union_account = $data['union_account'];
        $d->domain_url = $data['domain_url'];
        $d->apply_domain_type = $data['apply_domain_type'];
        $promote_auto_audit = \think\Db::table('tab_promote_config')->field('status')->where(['name' => 'promote_auto_audit_union'])->find();
        $d->status = $promote_auto_audit['status'];
        $d->apply_time = time();
        $res = $d->allowField(true)->save();
        if ($res != false) {
            return $res;
        } else {
            return false;
        }
    }
}