<?php
/**
 * @Copyright (c) 2020  XIGU Inc. All rights reserved.
 * @Link https://www.vlcms.com
 * @License江苏溪谷网络科技有限公司版权所有
 * 2020-07-07
 */
namespace app\site\controller;

use app\site\logic\AppLogic;
use app\site\model\AppModel;
use app\site\model\AppSupersignOrderModel;
use cmf\controller\AdminBaseController;
use think\Db;


class AppController extends AdminBaseController
{
    public function lists()
    {
        $model = new AppModel();
        $list = $model->getList();
        if(!$list){
            $list[0]=['id'=>1,'version'=>1,'name'=>'游戏盒子'];
            $list[1]=['id'=>2,'version'=>2,'name'=>'游戏盒子'];
        }else{
            if(count($list)==1){
                if($list[0]['id'] == 1){
                    $list[]=['id'=>2,'version'=>2,'name'=>'游戏盒子'];
                }else{
                    $list[]=['id'=>1,'version'=>1,'name'=>'游戏盒子'];
                }
            }
        }
        $this->assign('data_lists',$list);
        return $this->fetch();
    }


    /**
     * [编辑链接]
     * @return mixed
     * @author 郭家屯[gjt]
     */
    public function edit()
    {
        $id = $this->request->param('id/d',0);
        if(!$id)$this->error('参数错误');
        $model = new AppModel();
        if($this->request->isPost()){
            $request = $this->request->param();

            if ($request['pay_download'] == 0) {
                $request['pay_price'] = 0;
            }elseif($request['pay_price']<0.01){
                $this->error('请输入付费金额');
            }

            $request['name']="游戏盒子";
            $request['create_time']= time();
            if($request['type']){
                if(empty($request['super_url'])){
                    $this->error('请输入超级签地址');
                }
                if(empty($request['file_size'])){
                    $this->error('请输入原包大小');
                }
                $request['version'] = 2;
                $request['file_url'] = $request['super_url'];
                $request['file_name'] = '';
            }else{
                if(empty($request['file_name']))$this->error('未上传原包');
                $houzui = pathinfo($request['file_name'],PATHINFO_EXTENSION);
                if($id == 1 ){
                    $request['version'] = 1;
                    if($houzui != 'apk')$this->error('请选择文件后缀为apk的文件');
                }else{
                    $request['version'] = 2;
                    if($houzui != 'ipa')$this->error('请选择文件后缀为ipa的文件');
                }
                if(empty($request['bao_name']))$this->error('包名不能为空');
                if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $request['bao_name'])>0) {
                    $this->error('包名不能包含中文');
                }
            }
            $logic = new AppLogic();
            $result = $logic->update_source($request);
            if($result){
                $this->success('原包上传成功',url('lists'));
            }else{
                $this->error('原包上传失败');
            }
        }
        $data = $model->field('id,file_name,file_url,file_size,bao_name,remark,file_name,type,version,pay_download,pay_price')->find($id);
        if(!$data){
            $data['id'] = $id;
        }else{
            $data->toArray();
        }
        $this->assign('data',$data);
        return $this->fetch();
    }


    /**
     * @超级签付费下载记录
     *
     * @author: zsl
     * @since: 2021/7/12 15:34
     */
    public function superSignOrder()
    {
        $model = new AppSupersignOrderModel();
        $param = $this -> request -> param();
        $data_lists = $model -> adminLists($param);
        $this -> assign('data_lists', $data_lists);
        return $this -> fetch();
    }

}
