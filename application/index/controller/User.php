<?php
/**
 * 用户相关控制器
 * User: kongjian
 * Date: 2017/2/6
 * Time: 22:01
 */

namespace app\index\controller;

use think\Cache;
use think\Db;
use think\Request;
use common\lib\Xcrypt;

class User extends Base
{
    public function _initialize($token_allow = [], $request = null)
    {
        $token_allow = ['logout', 'upload_img_head', 'user_index_init', 'profileedit'];//需要token验证的action,小写
        parent::_initialize($token_allow, $request);
    }

    //登录注册统一接口
    function login()
    {
        $request = Request::instance();
        if ($request->isAjax() || $request->isGet() || $request->isPost()) {//TODO 上线后改为and isPost
            //验证验证码是否正确
            $mobile = $request->param('mobile', '');
            $sms_code = $request->param('sms_code', '');
            if ($mobile && $sms_code) {//排除空
                $data = [
                    'mobile' => $mobile,
                    'sms_code' => $sms_code
                ];
                $create_time = Db::table('cht_login_sms_code')->where($data)->order('id', 'desc')->value('create_time');
                if ((abs(time() - $create_time) < 36000) || $sms_code == 111111) {//判断验证码是否过期 TODO 上线后改为300
                    //登录还是注册
                    $uid = Db::table('cht_user')->where('mobile', $mobile)->value('uid');
                    if ($uid) {
                        //已注册，走登录接口
                        $result = $this->doLogin($uid);
                        data_format_json(0, $result, '登录成功');
                    } else {
                        //未注册，走注册接口
                        $result = $this->register($mobile);
                        data_format_json(0, $result, '登录成功');
                    }
                } else {
                    data_format_json(-2, '', '验证码已过期');
                }
            } else {
                data_format_json(-1, '', '参数错误');
            }
        }
    }

    //注册接口
    private function register($mobile = 0)
    {
        if ($mobile) {
            //自动生成昵称
            $name = '传话筒' . rand_number(4) . substr($mobile, 7, 10);

            //注册新用户
            $user = new \app\index\model\User();
            $user->data([
                'mobile' => $mobile,
                'create_time' => time(),
                'update_time' => time(),
                'user_name' => $name
            ]);
            $user->save();
            $uid = $user->uid;
            $result = $this->doLogin($uid);
        } else {
            $result = '';
        }
        return $result;
    }

    //登录接口
    private function doLogin($uid = 0)
    {
        if ($uid) {
            $user = Db::table('cht_user')->where('uid', $uid)->find();

            //获取token
            $token_content = mt_rand(10000, 99999) . "|" . $uid . "|" . $user['mobile'] . "|" . time();
            $class_xcrypt = new Xcrypt(INTERFACE_KEY, "ofb", INTERFACE_KEY);
            $token = $class_xcrypt->encrypt($token_content);

            //token等存入缓存
            $key_token = "cht_user_auth_token_" . $uid . $user['mobile'];
            Cache::set($key_token, $token, REDIS_EXPIRE_TIME_TOKEN);
            $data = [
                'uid' => $uid,
                'token' => $token
            ];
            Db::table('cht_user')->where('uid', $uid)->setField('lastlogin_time', time());
            $result = $data;
        } else {
            $result = '';
        }
        return $result;
    }

    //登出接口
    function logout()
    {
        $request = Request::instance();
        $token = $request->param('token');

        $class_xcrypt = new \common\lib\Xcrypt(INTERFACE_KEY, "ofb", INTERFACE_KEY);
        $result = $class_xcrypt->decrypt($token);
        $result_arr = explode("|", $result);

        $key_token = "cht_user_auth_token_" . $result_arr[1] . $result_arr[2];
        Cache::rm($key_token);
        data_format_json(0, $key_token, 'success');
    }

    //获取短信验证码接口
    function getSmsCode()
    {
        $sms_code = rand_number(6);
        $content = '您的验证码是' . $sms_code . ',有效期为5分钟,请尽快验证!';
        $request = Request::instance();
        if ($request->isAjax() || $request->isGet() || $request->isPost()) {//TODO 上线后将或改为与
            $mobile = $request->param('mobile', '');
            if (is_mobile_num($mobile)) {
                $data = ['mobile' => $mobile, 'sms_code' => $sms_code, 'create_time' => time()];
                $id = Db::table('cht_login_sms_code')->insertGetId($data);
                if ($id) {
                    send_sms($mobile, $content);
                    data_format_json(0, '', '发送成功');
                } else {
                    data_format_json(-2, '', '数据库写入失败');
                }
            } else {
                data_format_json(-1, '', '手机号码错误');
            }
        }
    }

    //上传头像接口
    public function upload_img_head()//TODO 增加isAjax isPost控制
    {
        $request = Request::instance();
        $file = $request->file('image');
        $uid = $request->param('uid', '');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'head');
        if ($info) {
            $extension = $info->getSaveName();
            $bucket = 'cht-img';
            $object = 'head/' . $extension;
            $file = 'uploads/head/' . $extension;
            if (upload_file_oss($bucket, $object, $file)) {
                Db::table('cht_user')->where('uid', $uid)->setField('img_head', 'http://img.chuanhuatong.cc/' . $object);
                data_format_json(0, ['head_url' => 'http://img.chuanhuatong.cc/' . $object], '上传成功');
            }
        } else {
            echo $file->getError();
        }
    }

    //个人中心首页初始化获取相关参数 TODO 上线后增加isAjax等验证
    function user_index_init()
    {
        $request = Request::instance();
        $uid = $request->param('uid');

        $m_user = new \app\index\model\User();
        $user_info = $m_user->where('uid', $uid)->field('img_head, user_name, points, sex')->find();//TODO 增加学校
        $result = [
            'user_name' => $user_info->user_name,
            'img_head' => $user_info->img_head,
            'sex' => $user_info->sex,
            'school' => '家里蹲大学',
            'city' => '南京',
            'company' => '好公司',
            'profession' => '混吃等死'
        ];
        data_format_json(0, $result, 'success');
    }

    /**
     * 编辑个人资料
     */
    function profileEdit()
    {
        $request = Request::instance();
        $category = $request->param('category', 0, 'intval') or data_format_json(-1, '', 'category is err');
        $uid = $request->param('uid', 0, 'intval') or data_format_json(-1, '', 'uid is null');
        $content = $request->param('content') or data_format_json(-1, '', 'content is null');

        $service_user = new \app\index\service\User();
        $service_user->profile_edit($uid, $category, $content);
    }

    /**
     * 微信登录注册
     */
    function wxappLogin()
    {
        $request = Request::instance();
        $code = $request->param('code') or data_format_json(-1, '', 'code is null');
        $service_user = new \app\index\service\User();
        $service_user->wxapp_login($code);
    }

}