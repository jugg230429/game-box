<?php

namespace app\webplatform\controller;

use app\webplatform\logic\WebPlatformLogic;

class WebPlatformController extends BaseController
{
    /**
     * @获取平台列表
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/10 14:46
     */
    public function lists()
    {
        $PlatLogic = new WebPlatformLogic();
        $request = $this->request->param();
        $map = [];
        if($request['status'] != ''){
            $map['status'] = $request['status'];
        }
        if($request['name'] != ''){
            $map['platform_name'] = $request['name'];
        }
        $data = $PlatLogic->lists($map);
        $page = $data->render();
        $this->assign('page', $page);
        $this->assign('data_lists', $data);
        return $this->fetch();
    }

    /**
     * @新增平台列表
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/10 17:31
     */
    public function add()
    {
        $PlatLogic = new WebPlatformLogic();
        if($this->request->isPost()){
            $request = $this->request->param();
            $result = $PlatLogic->add($request);
            if($result['status']){
                $this->success('添加成功',url('lists'));
            }else{
                $this->error($result['msg']);
            }
        }
        return $this->fetch();
    }

    /**
     * @编辑平台
     * @param int $id
     * @return mixed
     * @author: Juncl
     * @time: 2021/08/11 10:20
     */
    public function edit($id = 0)
    {
        $PlatLogic = new WebPlatformLogic();
        if($this->request->isPost()) {
            $request = $this->request->param();
            $result = $PlatLogic->edit($request);
            if(!$result['status']){
                $this->error($result['msg']);
            }else{
                $this->success('修改成功',url('lists'));
            }
        }
        $field = 'id,platform_name,platform_url,api_key,status,my_url';
        $data = $PlatLogic->detail($id,$field);
        if(!$data){
            $this->error('数据不存在');
        }else{
            $this->assign('data',$data);
            return $this->fetch();
        }
    }

    /**
     * @修改平台状态
     * @param int $id
     * @param string $field
     * @param string $value
     * @author: Juncl
     * @time: 2021/08/11 11:39
     */
    public function setstatus($id = 0,$field = 'status', $value='1')
    {
        $PlatLogic = new WebPlatformLogic();
        $result = $PlatLogic->setStatus($id,$field,$value);
        if(!$result){
            $this->error('修改失败');
        }else{
            $this->success('修改成功');
        }
    }
}