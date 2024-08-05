<?php

namespace app\webplatform\model;

use think\Model;

class WebPlatformModel extends Model
{
    protected $table = 'tab_web_platform';

    protected $autoWriteTimestamp = true;

    /**
     * @获取平台列表
     * @param array $map
     * @param int $limite
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function get_platform($map = [],$limite = 10)
    {
        $data = $this
            ->field('id,platform_name,platform_url,api_key,type,status,create_time,my_url')
            ->where($map)
            ->order('create_time desc')
            ->limit($limite)
            ->select();
        return $data?:[];

    }

    /**
     * @新增平台
     * @param array $param
     * @return int|string
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function add($param = [])
    {
        $data = [];
        $data['platform_name'] = $param['platform_name'];
        $data['platform_url'] = $param['platform_url'];
        $data['api_key'] = $param['api_key'];
        $data['status'] = 1;
        $data['create_time'] =time();
        $data['update_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $data['op_nickname'] = cmf_get_current_admin_name();
        $data['promote_id'] = $param['promote_id'];
        $data['promote_account'] = $param['promote_account'];
        $data['my_url'] = $param['my_url'];
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * @获取平台详情
     * @param int $id
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author: Juncl
     * @time: 2021/08/11 11:44
     */
    public function detail($id = 0, $field='*')
    {
        $data = $this->field($field)->where('id',$id)->find();
        return $data;
    }

    /**
     * @编辑额平台
     * @param array $param
     * @return PlatformModel
     * @author: Juncl
     * @time: 2021/08/11 11:45
     */
    public function edit($param = [])
    {
        $data['id'] = $param['id'];
        $data['platform_name'] = $param['platform_name'];
        $data['platform_url'] = $param['platform_url'];
        $data['api_key'] = $param['api_key'];
        $data['status'] = $param['status'];
        $data['my_url'] = $param['my_url'];
        $data['update_time'] = time();
        $data['op_id'] = cmf_get_current_admin_id();
        $data['op_nickname'] = cmf_get_current_admin_name();
        $data['my_url'] = $param['my_url'];
        $result = $this->Update($data);
        return $result;
    }

    /**
     * @修改平台属性
     * @param int $id
     * @param string $field
     * @param string $value
     * @return int
     * @author: Juncl
     * @time: 2021/08/11 11:45
     */
    public function setStatus($id = 0,$field = 'status', $value='1')
    {
        $result = $this->where('id',$id)->setField($field, $value);
        return $result;
    }


}