<?php
/**
 *
 * 帮助文档 文档整理
 *
 */

namespace app\game\controller;

use app\common\controller\BaseController;
use app\game\model\GamecommentModel;
use cmf\controller\AdminBaseController;
use think\Request;
use think\Db;

class CommentController extends AdminBaseController
{
    public function __construct()
    {
        parent::__construct();
        if (AUTH_GAME != 1) {
            if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest") {
                $this->error('请购买游戏权限');
            } else {
                $this->error('请购买游戏权限', url('admin/main/index'));
            };
        }
    }

    /**
     * @函数或方法说明
     * @评论列表
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/8/6 17:38
     */
    public function lists()
    {
        $base = new BaseController();
        $model = new GamecommentModel();
        //添加搜索条件
        $data = $this->request->param();
        if($data['user_account']){
            $map['user_account'] = ['like','%'.$data['user_account'].'%'];
        }
        if($data['game_id']){
            $map['game_id'] = $data['game_id'];
        }
        if($data['status'] != ''){
            $map['status'] = $data['status'];
        }
        $map['top_id'] = 0;
        //按照时间倒序
        $exend['order'] = 'id desc';
        $data = $base->data_list($model, $map, $exend);

        foreach ($data as $key => $v) {
            //评论数量和详情
            $map1['top_id'] = $v['id'];
            $comment = $model -> field('id as zi_id,user_account,comment_id,comment_account,content,top_id,status')
                    -> where($map1)
                    -> select() -> toArray();
            $data[$key]['comment_count'] = count($comment);
            $data[$key]['comment'] = $comment;
            $data[$key]['comment_data'] = json_encode($comment);

        }

        // 获取分页显示
        $page = $data->render();
        $this->assign('page', $page);

        // 判断当前管理员是否有权限显示完成整手机号或完整账号
        $ys_show_admin = get_admin_privicy_two_value();
        foreach($data as $k5=>$v5){
            if($ys_show_admin['account_show_admin_status'] == 1){//开启了账号查看隐私
                $data[$k5]['user_account'] = get_ys_string($v5['user_account'],$ys_show_admin['account_show_admin']);
            }
        }

        $this->assign("data_lists", $data);
        //自动审核
        $autostatus = Db::table('tab_game_config')->where(array('name' => 'comment_auto_audit'))->value('status');
        $this->assign('autostatus', $autostatus);
        return $this->fetch();
    }

    /**
     * @函数或方法说明
     * @设置评论状态
     * @author: 郭家屯
     * @since: 2020/8/7 9:18
     */
    public function changestatus()
    {
        $status = $this->request->param('status');
        $model = new GamecommentModel();
        if(empty($status)){
            $ids = $this->request->param('ids');
            $comment = $model->field('id,user_account,comment_id,status')->where('id',$ids)->find();
            if(!$comment)$this->error('评论不存在');
            $comment = $comment->toArray();
            $changestatus = $comment['status'] == 0 ? 1 : ($comment['status'] == 1 ? 2 : 1);
            if($comment['status'] == 0){
                $result = $model->where('id',$ids)->setField('status',$changestatus);
                if($result && $comment['comment_id'] >0){
                    $parent_comment = $model->field('id,user_id')->where('id',$comment['comment_id'])->find();
                    $save['user_id'] = $parent_comment['user_id'];
                    $save['title'] = '评论回复';
                    $save['content'] = $comment['user_account'].'对您的评论进行了回复，请及时查看';
                    $save['create_time'] = time();
                    $save['comment_id'] = $comment['id'];
                    $save['type'] = 5; // 添加类型
                    Db::table('tab_tip')->insert($save);
                }
            }else{
                $result = $model->where('id|top_id',$ids)->setField('status',$changestatus);
            }
        }else{
            $ids = $this->request->param('ids/a');
            if(!$ids)$this->error('请选择要操作的数据');
            $map['status'] = $status == 1 ? 0 : 1;
            if($status == 1){
                $map1['status'] = 0;
                $map1['comment_id'] = ['gt',0];
                $lists = $model->where('id','in',$ids)->field('id,comment_id,user_account')->where($map1)->select()->toArray();
                $result = $model->where('id','in',$ids)->setField('status',$status);
                if($result){
                    $insert_data = [];
                    //批量添加消息通知
                    foreach ($lists as $key=>$v){
                        $parent_comment = $model->field('id,user_id,user_account')->where('id',$v['comment_id'])->find();
                        $save['user_id'] = $parent_comment['user_id'];
                        $save['title'] = '评论回复';
                        $save['content'] = $v['user_account'].'对您的评论进行了回复，请及时查看';
                        $save['create_time'] = time();
                        $save['comment_id'] = $v['id'];
                        $save['type'] = 5; // 添加类型
                        $insert_data[] = $save;
                    }
                    Db::table('tab_tip')->insertAll($insert_data);
                }
            }else{
                $result = $model->where('id|top_id','in',$ids)->where($map)->setField('status',$status);
            }
        }
        if($result !== false){
            $this->success('操作成功');
        }else{
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @设置自动审核
     * @author: 郭家屯
     * @since: 2020/8/7 9:45
     */
    public function set_config_auto_audit($status = '')
    {
        $config['status'] = $status == 0 ? 1 : 0;
        $res = Db::table('tab_game_config')->where(array('name' => 'comment_auto_audit'))->update($config);
        if ($res !== false) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * @函数或方法说明
     * @查看评论详情
     * @author: 郭家屯
     * @since: 2020/8/7 10:09
     */
    public function show()
    {
        $id = $this->request->param('id');
        $model = new GamecommentModel();
        $comment = $model->field('id,user_account,content')->where('id',$id)->find();
        $this->assign('comment',$comment);
        $data = $model->field('id,user_account,comment_id,comment_account,content')->where('top_id',$id)->select();
        $this->assign('data',$data);
        return $this->fetch();
    }


















}
