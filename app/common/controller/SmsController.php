<?php
/**
 * 短信类
 *
 * @author: 鹿文学
 * @Datetime: 2019-03-26 13:45
 */

namespace app\common\controller;

use cmf\controller\HomeBaseController;
use think\Db;
use think\Request;
use think\sms\Suxuntong;

class SmsController extends HomeBaseController
{
    public static $error_info = [
        'no_config' => 100,
        'no_data' => 101,
        'invalid_time' => 102,
        'fail' => 103,
        'beyond_quantity' => 104,
        'limit_time' => 105,
        'code_empty' => 106,
        'code_input_empty' => 107,
        'code_input_error' => 108,
        'code_overtime' => 109,
        'success' => 200,
    ];
    /**
     * session前缀
     * @var string
     */
    private $prefix = '';

    /**
     * 短信配置信息
     * @var array|false|\PDOStatement|string|\think\Model
     */
    private $config = [];

    /**
     * 短信配置表
     * @var string
     */
    private static $table_config = 'tab_sms_config';

    /**
     * 短信记录表
     * @var string
     */
    private static $table_log = 'tab_sms_log';

    /**
     * 倒计时时间（分钟）
     * @var int
     */
    private static $count_down = 1;

    /**
     * 有效时间
     * @var int
     */
    private static $delay = 3;

    /**
     * 分钟汉字表示
     * @var array
     */
    private static $minute_cn = ['零', '一', '两', '三', '四', '五', '六', '七', '八', '九', '十'];

    /**
     * SmsController constructor.
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function __construct()
    {
        parent::__construct();
        $request = $this->request->param();
        if ($request['use_module']) {
            $this->prefix = strtolower($request['module']) . '_';
        } else {
            $this->prefix = $request['prefix'] ? (strtolower($request['prefix'] . '_')) : '';
        }
        if ($request['count_down']) {
            self::$count_down = $request['count_down'];
        }
        $this->config = Db::table(self::$table_config)->where('id', $request['sms_id'] ?: 1)->find();

    }

    /**
     * 发送短信
     *
     * @param string $phone
     * @param int $delay
     * @param bool $flag
     * @param int $pid
     * @param int $reg
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author: 鹿文学
     * @since: 2019-03-26 14:55
     */
    public function sendSmsCode($phone = '', $delay = '', $flag = true, $pid = 0,$reg)
    {
        $delay = $delay ?: self::$delay;
        if (empty($this->config)) {
            $sms_return = ['code' => self::$error_info['no_config'], 'msg' => '没有配置短信发送'];
        } else {

            if (empty($phone)) {

                $sms_return = ['code' => self::$error_info['no_data'], 'msg' => '数据不能为空'];

            } else {

                $session_name = $this->prefix . $phone;
                /*检查数据库中的此号码最新一条记录是否存在，存在是否间隔超过几分钟*/
                $data_code = Db::table(self::$table_log)
                    ->field('max(create_time) as time')
                    ->where(['pid' => $pid, 'phone' => $phone,'send_status'=>'000000'])
                    ->order('create_time desc')
                    ->find();

                if (!empty($data_code) && ((time() - $data_code['time']) / 60 < self::$count_down)) {

                    $sms_return = [
                        'code' => self::$error_info['invalid_time'],
                        'msg' => '请' . self::$minute_cn[self::$count_down] . '分钟后再次尝试'
                    ];

                } else {
                    $sms_code = session($session_name);
                    $sms_rand = sms_rand($session_name);
                    $rand = $sms_rand['rand'];
                    $new_rand = $sms_rand['rand'];
                    $data = array(
                        'pid' => $pid,
                        'phone' => $phone,
                        'create_time' => time()
                    );

                    $sms_id = Db::table(self::$table_log)
                        ->field('pid,phone,create_time')
                        ->insertGetId($data);

                    if ($this->config['status'] == 1) {
                        $this->checkSms($phone, $this->config['client_send_max'], true, $pid);
                        $sxt = new Suxuntong();
                        $send_result = $sxt->sendSM($phone,$rand,$reg);
                        if ($send_result->returnstatus != 'Success') {
                            $sms_return = ['code' => self::$error_info['fail'], 'msg' => '发送失败，请重新获取'];
                        }
                    } else {
                        $sms_return = ['code' => self::$error_info['no_config'], 'msg' => '没有配置短信发送'];
                    }
                }
                if (empty($sms_return)) {

                    // 存储到数据库
                    $result['send_status'] = 'Success';
                    $result['phone'] = $phone;
                    $result['create_time'] = time();
                    $result['pid'] = $pid;
                    $result['create_ip'] = get_client_ip();
                    if ($sms_id > 0) {
                        $map['id'] = $sms_id;
                        Db::table(self::$table_log)
                            ->where($map)
                            ->update($result);
                    } else {
                        Db::table(self::$table_log)->insert($result);
                    }
                    // 记录短信发送记录用于验证
                    $safe_code['code'] = $rand;
                    $safe_code['phone'] = $phone;
                    $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                    $safe_code['delay'] = $delay;
                    $safe_code['create'] = time();
                    session($session_name, $safe_code);
                    unset($safe_code['code']);
                    $sms_return = [
                        'code' => self::$error_info['success'],
                        'msg' => '短信已发放，请注意查收',
                        'data' => $safe_code
                    ];

                }

            }
        }
        if ($flag) {

            echo json_encode($sms_return);
            exit;

        } else {

            return $sms_return;

        }

    }

    /**
     * 发送出售小号短信
     *
     * @param string $phone
     * @param int $delay
     * @param bool $flag
     * @param int $pid
     *
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * @author: 鹿文学
     * @since: 2019-03-26 14:55
     */
    public function sendSellSmsCode($phone = '', $delay = '', $flag = true, $pid = 0)
    {
        $delay = $delay ?: self::$delay;
        if (empty($this->config)) {
            $sms_return = ['code' => self::$error_info['no_config'], 'msg' => '没有配置短信发送'];
        } else {

            if (empty($phone)) {

                $sms_return = ['code' => self::$error_info['no_data'], 'msg' => '数据不能为空'];

            } else {

                $session_name = $this->prefix . $phone;
                /*检查数据库中的此号码最新一条记录是否存在，存在是否间隔超过几分钟*/
                $data_code = Db::table(self::$table_log)
                        ->field('max(create_time) as time')
                        ->where(['pid' => $pid, 'phone' => $phone,'send_status'=>'000000'])
                        ->order('create_time desc')
                        ->find();

                if (!empty($data_code) && ((time() - $data_code['time']) / 60 < self::$count_down)) {

                    $sms_return = [
                            'code' => self::$error_info['invalid_time'],
                            'msg' => '请' . self::$minute_cn[self::$count_down] . '分钟后再次尝试'
                    ];

                } else {
                    $sms_code = session($session_name);
                    $sms_rand = sms_rand($session_name);
                    $rand = $sms_rand['rand'];
                    $new_rand = $sms_rand['rand'];
                    $param = $rand;
                    $data = array(
                            'pid' => $pid,
                            'phone' => $phone,
                            'create_time' => time()
                    );

                    $sms_id = Db::table(self::$table_log)
                            ->field('pid,phone,create_time')
                            ->insertGetId($data);

                    if ($this->config['status'] == 1) {
                        $this->checkSms($phone, $this->config['client_send_max'], true, $pid);

                        $xigu = new \think\xigusdk\Xigu($this->config);

                        $send_result = json_decode($xigu->sendSM( $phone, $this->config['sell_tid'], $param), true);

                        if ($send_result['send_status'] != '000000') {

                            $sms_return = ['code' => self::$error_info['fail'], 'msg' => '发送失败，请重新获取'];

                        }

                    } else {

                        $sms_return = ['code' => self::$error_info['no_config'], 'msg' => '没有配置短信发送'];

                    }
                }
                if (empty($sms_return)) {

                    // 存储到数据库
                    $result['send_status'] = '000000';
                    $result['phone'] = $phone;
                    $result['create_time'] = time();
                    $result['pid'] = $pid;
                    $result['create_ip'] = get_client_ip();
                    if ($sms_id > 0) {
                        $map['id'] = $sms_id;
                        Db::table(self::$table_log)
                                ->where($map)
                                ->update($result);
                    } else {
                        Db::table(self::$table_log)->insert($result);
                    }
                    // 记录短信发送记录用于验证
                    $safe_code['code'] = $rand;
                    $safe_code['phone'] = $phone;
                    $safe_code['time'] = $new_rand ? time() : $sms_code['time'];
                    $safe_code['delay'] = $delay;
                    $safe_code['create'] = $result['create_time'];
                    session($session_name, $safe_code);
                    unset($safe_code['code']);
                    $sms_return = [
                            'code' => self::$error_info['success'],
                            'msg' => '短信已发放，请注意查收',
                            'data' => $safe_code
                    ];

                }

            }
        }
        if ($flag) {

            echo json_encode($sms_return);
            exit;

        } else {

            return $sms_return;

        }

    }

    /**
     * 验证短信
     *
     * @param $phone
     * @param $code
     * @param bool $flag
     * @param bool $destroy
     *
     * @return array
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\27 0027 10:52
     */
    public function verifySmsCode($phone, $code, $flag = true, $destroy = true)
    {
        if (empty($phone)) {
            $sms_return = ['code' => self::$error_info['no_data'], 'msg' => '数据不能为空'];
        } else {
            $session_name = $this->prefix . $phone;
            if (empty($code)) {
                $sms_return = ['code' => self::$error_info['code_input_empty'], 'msg' => '验证码不能为空'];
            } else {

                $safe_code = session($session_name);
                if (empty($safe_code)) {
                    $sms_return = ['code' => self::$error_info['code_empty'], 'msg' => '请先获取验证码'];
                } else {

                    $time = (time() - $safe_code['time']) / 60;
                    if ($time <= $safe_code['delay']) {
                        
                        if (($safe_code['code'] == $code) && ($safe_code['phone'] == $phone)) {

                            unset($safe_code);
                            if ($destroy) {

                                session($session_name, null);
                                $sms_return = ['code' => self::$error_info['success'], 'msg' => ''];

                            } else {

                                $sms_return = [
                                    'code' => self::$error_info['success'],
                                    'msg' => '',
                                    'session_name' => $session_name
                                ];

                            }

                        } else {

                            $sms_return = ['code' => self::$error_info['code_input_error'], 'msg' => '验证码输入有误'];

                        }

                    } else {

                        unset($safe_code);
                        if ($destroy) {
                            session($session_name, null);
                            $sms_return = [
                                'code' => self::$error_info['code_overtime'],
                                'msg' => '时间超时,请重新获取'
                            ];
                        } else {
                            $sms_return = [
                                'code' => self::$error_info['code_overtime'],
                                'msg' => '时间超时,请重新获取',
                                'session_name' => $session_name
                            ];
                        }

                    }

                }
            }
        }
        if ($flag) {
            echo json_encode($sms_return);
            exit;
        } else {
            return $sms_return;
        }

    }

    /**
     * 检查短信验证码
     *
     * @param $phone
     * @param $limit
     * @param bool $isCheckTime
     * @param int $pid
     * @param bool $flag
     *
     * @return array
     *
     * @throws \think\Exception
     * @since: 2019\3\27 0027 10:06
     * @author: fyj301415926@126.com
     */
    public function checkSms($phone, $limit, $isCheckTime = true, $pid = 0, $flag = true)
    {

        $number = DB::table(self::$table_log)
            ->where(['pid' => $pid, 'create_ip' => get_client_ip(), 'send_status' => '000000', 'send_time' => array(array('egt', strtotime('today')), array('elt', strtotime('tomorrow')))])
            ->count();

        if (!empty($limit) && $number >= $limit) {

            $sms_return = ['code' => self::$error_info['beyond_quantity'], 'msg' => '每天发送数量不能超过' . $limit . '条'];

        } else {

            if ($isCheckTime) {

                $request_time = time();
                $map = array('phone' => $phone);
                $map = array('send_status' => '000000');
                $map['create_time'] = array(array('egt', ($request_time - self::$count_down * 60)), array('elt', $request_time));
                $number = DB::table(self::$table_log)->where($map)->count();
                if ($number > 1) {

                    $sms_return = ['code' => self::$error_info['limit_time'], 'msg' => '请' . self::$minute_cn[self::$count_down] . '分钟后再次尝试'];

                }

            }


        }
        if (!empty($sms_return)) {

            if ($flag) {
                echo json_encode($sms_return);
                exit;
            } else {
                return $sms_return;
            }

        }

    }

    /**
     * 删除服务端保存的短信信息
     *
     * @param string $session_name
     *
     * @author: fyj301415926@126.com
     * @since: 2019\3\28 0028 11:12
     */
    public static function clearSmsCodeStore($session_name = '')
    {

        session($session_name, null);

    }

}
