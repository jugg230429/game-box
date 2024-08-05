<?php

namespace app\recharge\command;

use app\game\model\ServerModel;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\Db;

class Notice extends Command
{

    protected function configure()
    {
        $this
            ->setName('notice')
            ->setDescription('区服开服提醒');
    }

    protected function execute(Input $input, Output $output)
    {
        //今日开服查询
        $model  = new ServerModel();
        $map['start_time'] = ['between',total(1, 2)];
        $order = "start_time asc";
        $list = $model->getlists($map,$order);
        foreach ($list as $key=>$v){
            $notice[] = $this->create_data($v);
        }
        if(empty($notice)){
            echo "执行结束";die;
        }
        $list = array_reduce($notice, 'array_merge', []);
        $result = Db::table('tab_tip')->insertAll($list);
        if($result){
            echo "执行结束";
        }else{
            echo "执行失败";
        }
    }

    /**
     * @函数或方法说明
     * @代金券数据
     * @author: 郭家屯
     * @since: 2020/8/13 13:50
     */
    protected function create_data($server=[])
    {
        $list = Db::table('tab_game_server_notice')
                ->field('user_id')
                ->where('server_id',$server['id'])
                ->where('status',1)
                ->select();
        $data = [];
        foreach ($list->toArray() as $key=>$v){
            $notice['user_id'] = $v['user_id'];
            $notice['title'] = "开服提醒";
            $notice['content'] = "您预约的《".$server['game_name']."》".$server['server_name']."将于".date('m-d H:i',$server['start_time'])."开服";
            $notice['game_id'] = $server['game_id'];
            $notice['create_time'] = time();
            $notice['type'] = 4; // 添加类型
            $data[] = $notice;
        }
        return $data;
    }
}