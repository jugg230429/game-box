<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/26
 * Time: 19:13
 */

namespace app\datareport\controller;

use app\common\controller\BaseController as Base;
use app\site\model\EquipmentGameModel;
use app\site\model\EquipmentLoginModel;
use think\Db;

class DeviceController extends Base
{

    /**
     * @函数或方法说明
     * @应用概况
     * @return mixed
     *
     * @author: 郭家屯
     * @since: 2020/4/1 11:55
     */
    public function survey()
    {
        $this->updateversion();
        $request = $this->request->param();
        //时间
        if (empty($request['datetime'])) {
            $date = date("Y-m-d") . '~' . date("Y-m-d");
        } else {
            $date = $request['datetime'];
        }
        $dateexp = explode('~', $date);
        $starttime = $dateexp[0];
        $endtime = $dateexp[1];
        $this->assign('start', $starttime);
        $this->assign('end', $endtime);
        $device_record = new EquipmentLoginModel();
        $device_game = new EquipmentGameModel();

        //增加对登录管理员是否有游戏查看的限制-20210624-byh-start
        $request['game_id'] = get_admin_view_game_ids(session('ADMIN_ID'),$request['game_id']);
        //增加对登录管理员是否有游戏查看的限制-20210624-byh-end

        // 统计
//        $total = $device_record->all_device();
        $total = $device_record->all_device(['game_id'=>['IN',$request['game_id']]]);

        $todaystart = mktime(0,0,0,date('m'),date('d'),date('Y'));
        if(empty($request['game_id'])){
            $today = $device_game->total_device(['create_time'=>array('between',[$todaystart,$todaystart+86399])]);
            $yesterday = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400,$todaystart-1])]);
            $day7 = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400*6,$todaystart+86399])],1);
            $day30 = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400*29,$todaystart+86399])],1);
            $duration = $device_record->single_duration(['time'=>array('between',[date('Y-m-d',$todaystart-86400*6),date('Y-m-d')])],$day7);
        }else{
            //增加game_id权限参数-20210624-byh-start
            $today = $device_game->total_device(['create_time'=>array('between',[$todaystart,$todaystart+86399]),'game_id'=>['IN',$request['game_id']]]);
            $yesterday = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400,$todaystart-1]),'game_id'=>['IN',$request['game_id']]]);
            $day7 = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400*6,$todaystart+86399]),'game_id'=>['IN',$request['game_id']]],1);
            $day30 = $device_game->total_device(['create_time'=>array('between',[$todaystart-86400*29,$todaystart+86399]),'game_id'=>['IN',$request['game_id']]],1);
            $duration = $device_record->single_duration(['time'=>array('between',[date('Y-m-d',$todaystart-86400*6),date('Y-m-d')])],$day7);
            //增加game_id权限参数-20210624-byh-end
        }

        $this->assign('total',$total);
        $this->assign('today',$today);
        $this->assign('yesterday',$yesterday);
        $this->assign('day7',$day7);
        $this->assign('day30',$day30);
        $this->assign('duration7',$this->second_to_duration($duration/7));

        // 折线图
        $this->foldLineDiagram($starttime,$endtime,$request['game_id'],$request['promote_id']);

        $this->display();
        return $this->fetch();
    }

    /*
		 * 折线图
		 * @param integer  $start  开始时间
		 * @param integer  $end  	 结束时间
		 * @param boolean  $flag   是否ajax返回
		 * @author 鹿文学
		 */
    public function foldLineDiagram($start='',$end='',$game_id=0,$promote_id=0) {
        $starttime = $start?strtotime($start):mktime(0,0,0,date('m'),date('d')-1,date('Y'));

        $endtime = $end?strtotime($end)+86399:$starttime+86399;

        $start = date('Y-m-d',$starttime);
        $end = date('Y-m-d',$endtime);

        $device_record = new EquipmentLoginModel();
        $device_game = new EquipmentGameModel();
        if($game_id > 0){
            $map['game_id'] = $game_id;
            $map1['game_id'] = $game_id;
        }
        if($promote_id != ''){
            $map['promote_id'] = $promote_id;
            $map1['promote_id'] = $promote_id;
        }
        $map['create_time'] = ['between',[$starttime,$endtime]];
        $map1['time'] = ['between',[date('Y-m-d',$starttime),date('Y-m-d',$endtime)]];
        if ($start == $end) {
            $day_count = 1;

            $hours = ['00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23'];

            $data['date'] = [$start];

            $data['hours'] = 1;

            foreach($hours as $v) {
                $data['news']['ios'][$v] = 0;
                $data['news']['and'][$v] = 0;
                $data['active']['ios'][$v] = 0;
                $data['active']['and'][$v] = 0;
            }

            // 新增设备
            $hoursnews = $device_game->news_on_time($map,'news',5,'time,sdk_version');

            // 活跃设备
            $hoursactive = $device_record->active_on_time($map1,'active',5,'login_time,sdk_version');

            // 启动机型
            $hoursmodel = $device_record->model($map1);
            foreach($hours as $v) {
                foreach($hoursnews as $h) {
                    $time = explode(' ',$h['time']);
                    if ($time[1] == $v){
                        $data['news'][$h['sdk_version']==1?'and':'ios'][$v]+= (integer)$h['news'];
                    }
                }

                foreach($hoursactive as $h) {
                    $time = explode(' ',$h['login_time']);
                    if ($time[1] == $v){
                        $data['active'][$h['sdk_version']==1?'and':'ios'][$v] += (integer)$h['active'];
                    }
                }

            }

            foreach($hoursmodel as $k=>$h) {
                $data['xmodel'][$h['sdk_version']==1?'and':'ios'][] = "'".($h['device_name']?:'其他')."'";
                $data['model'][$h['sdk_version']==1?'and':'ios'][$h['device_name']] = (integer)$h['count'];
            }


        } else {
            $num = 1;
            if(get_days($endtime,$starttime) >=365){
                $num = 7;
            }
            $datelist = $this->get_date_list($starttime,$endtime,$num==7?4:1);
            $day_count = count($datelist);
            $data['date'] = $datelist;

            $data['hours'] = 0;

            foreach($datelist as $k => $v) {
                $data['news']['ios'][$v] = 0;
                $data['news']['and'][$v] = 0;
                $data['active']['ios'][$v] = 0;
                $data['active']['and'][$v] = 0;
            }

            // 新增设备
            $news = $device_game->news_on_time($map,'news',$num==7?2:1,'time,sdk_version');

            // 活跃设备
            $active = $device_record->active_on_time($map1,'active',$num==7?2:1,'time,sdk_version');

            // 启动机型
            $model = $device_record->model($map1);
            foreach($datelist as $v) {
                foreach($news as $h) {
                    if ($v == $h['time']) {
                        $data['news'][$h['sdk_version']==1?'and':'ios'][$v] += (integer)$h['news'];
                    }
                }

                foreach($active as $h) {
                    if ($v == $h['time']) {
                        $data['active'][$h['sdk_version']==1?'and':'ios'][$v] += (integer)$h['active'];
                    }
                }

            }

            foreach($model as $k=>$h) {
                $data['xmodel'][$h['sdk_version']==1?'and':'ios'][] = "'".($h['device_name']?:'其他')."'";
                $data['model'][$h['sdk_version']==1?'and':'ios'][$h['device_name']] = (integer)$h['count'];
            }


        }

        foreach($data as $k => $v) {

            if (is_array($v)) {
                if ($k == 'date'){
                    $data[$k] = '"'.implode('","',$v).'"';
                    $table[$k] = $v;
                }elseif($k == 'xmodel'){
                    $table[$k]['ios'] = $v['ios'];
                    $table[$k]['and'] = $v['and'];
                    $data[$k]['ios'] = implode(',',$v['ios']);
                    $data[$k]['and'] = implode(',',$v['and']);
                }elseif($k == 'model'){
                    $table[$k]['ios'] = $v['ios'];
                    $table[$k]['and'] = $v['and'];
                    $tempexport=[];
                    foreach($v['ios'] as $t => $s) {
                        $tempexport[]=['device_name'=>$t?:'其他','count'=>$s,'sdk_version'=>'ios'];
                    }
                    foreach($v['and'] as $t => $s) {
                        $tempexport[]=['device_name'=>$t?:'其他','count'=>$s,'sdk_version'=>'android'];
                    }
                    $export[$k]=$tempexport;
                    $data[$k]['ios'] = implode(',',$v['ios']);
                    $data[$k]['and'] = implode(',',$v['and']);
                }else{
                    $sum = 0;$x='';$y=0;$tempexport=[];$count=0;

                    foreach($v['ios'] as $t => $s){
                        $sum += $s;$count++;
                        if($data['hours']==1){
                            if ($t%2==1) {$tab['ios'][$x.'~'.$t] = $y+$s;$x='';$y=0;}else{$x .= $t;$y += $s;}
                            $tempexport[]=['time'=>$start.' '.(substr($t,0,2)).':00','count'=>$s,'sdk_version'=>'ios'];
                        }else{
                            $tempexport[]=['time'=>$t,'count'=>$s,'sdk_version'=>'ios'];
                        }
                    }

                    foreach($v['and'] as $t => $s){
                        $sum += $s;$count++;
                        if($data['hours']==1){
                            if ($t%2==1) {$tab['and'][$x.'~'.$t] = $y+$s;$x='';$y=0;}else{$x .= $t;$y += $s;}
                            $tempexport[]=['time'=>$start.' '.(substr($t,0,2)).':00','count'=>$s,'sdk_version'=>'android'];
                        }else{
                            $tempexport[]=['time'=>$t,'count'=>$s,'sdk_version'=>'android'];
                        }
                    }

                    if($data['hours']==1){
                        $table[$k]=$tab;
                        $export['average'][$k]=$table['average'][$k]=$sum;
                    }else{
                        $table[$k] = $v;
                        $export['average'][$k]=$table['average'][$k]=round($sum/$day_count,2);
                    }
                    $export['sum'][$k] = $table['sum'][$k]=$sum;
                    $export[$k]=$tempexport;
                    $data[$k]['ios'] = implode(',',$v['ios']);
                    $data[$k]['and'] = implode(',',$v['and']);
                }
            }
        }


        @file_put_contents(dirname(__FILE__).'/device_data_foldline.txt',json_encode($export));

        $this->assign('foldline',$data);

        $this->assign('table',$table);

        $this->assign('num',$num);

    }

    /**
     * @函数或方法说明
     * @秒数转时长(时分秒格式)
     * @param $times
     *
     * @return string
     *
     * @author: 郭家屯
     * @since: 2020/4/1 16:08
     */
    protected function second_to_duration($times){
        $result = '00:00:00';
        if ($times>0) {
            $hour = floor($times/3600);
            $minute = floor(($times-3600 * $hour)/60);
            $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
            $result = $hour.':'.$minute.':'.$second;
        }
        return $result;
    }

    /**
     * @函数或方法说明
     * @更新数据
     * @author: 郭家屯
     * @since: 2020/4/1 16:49
     */
    protected function updateversion()
    {
        $model = new EquipmentLoginModel();
        $data = $model->field('id')
                ->where('game_id','gt',0)
                ->where('sdk_version',0)
                ->find();
        if($data){
            $games = $model->field('game_id')
                    ->where('game_id','gt',0)
                    ->where('sdk_version',0)
                    ->group('game_id')
                    ->select()->toArray();
            $game_ids = array_column($games,'game_id');
            foreach ($game_ids as $key=>$v){
                $sdk_version = get_game_entity($v,'sdk_version')['sdk_version'];
                $sdk_version = $sdk_version ? : 1;//游戏删除默认安卓
                $model->where('game_id',$v)->setField('sdk_version',$sdk_version);
            }
        }
    }

    /**
     * @函数或方法说明
     * @获取日期间隔数组
     * @param string $d1
     * @param string $d2
     * @param int $flag
     *
     * @return array
     *
     * @author: 郭家屯
     * @since: 2020/4/1 19:32
     */
    protected function get_date_list($d1='',$d2='',$flag=1) {
        if ($flag == 1){/* 天 形如：array('2017-03-10','2017-03-11','2017-03-12','2017-03-13')*/
            $d1 = $d1?$d1:mktime(0,0,0,date('m'),date('d')-6,date('Y'));
            $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));
            $date = range($d1,$d2,86400);
            if(!is_array($date))
                $date = [date('Y-m-d',$d1)];
            else
                $date = array_map(create_function('$v','return date("Y-m-d",$v);'),$date);
        } elseif ($flag == 2) {/* 月 形如：array('2017-01','2017-02','2017-03','2017-04')*/
            $d1 = $d1?$d1:mktime(0,0,0,date('m')-5,1,date('Y'));
            $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));
            $i = false;
            while($d1<$d2) {
                $d1 = !$i?$d1:strtotime('+1 month',$d1);
                $date[]=date('Y-m',$d1);$i=true;
            }
            array_pop($date);
        } elseif ($flag == 3) {/* 周 形如：array('2017-01','2017-02','2017-03','2017-04')*/
            $d1 = $d1?$d1:mktime(0,0,0,date('m')-2,1,date('Y'));
            $d2 = $d2?$d2:mktime(0,0,0,date('m'),date('d'),date('Y'));

            $i = false;
            while($d1<$d2) {
                $d1 = !$i?$d1:strtotime('+1 week',$d1);
                $date[]=date('Y-W',$d1);$i=true;
            }
        } elseif ($flag == 4) {
            $d1 = $d1?date('Y-m',$d1):date('Y-m',strtotime('-5 month'));
            $d2 = $d2?date('Y-m',$d2):date('Y-m');
            $temp = explode('-',$d1);
            $year = $temp[0];
            $month = $temp[1];
            while(strtotime($d1)<=strtotime($d2)) {
                $date[]=$d1;$month++;if($month>12) {$month=1;$year+=1;}
                $t = strlen($month.'')==1?'0'.$month:$month;
                $d1=$year.'-'.$t;
            }

        }

        return $date;
    }
}