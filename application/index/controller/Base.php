<?php
/**
 * 基础控制器
 * User: kongjian
 * Date: 2017/1/21
 * Time: 13:23
 */

namespace app\index\controller;

use think\Cache;
use think\Controller;
use think\Request;

class Base extends Controller
{
    public function _initialize($token_allow = [], $request = null)
    {
        parent::_initialize();

        //判断是否需要token验证
        if ($token_allow) {
            $request = Request::instance();
            $action = $request->action();
            $uid = $request->param('uid', 0);
            $token = $request->param('token', '');
            if (in_array($action, $token_allow)) {
                $this->validate_token($uid, $token);
            }
        }

        // 常量定义
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $abis_this_url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        define('__CHT_THIS_URL__', $abis_this_url);
        $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
        define('__CHT_ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
        define('__CHT_APP__', __CHT_ROOT__ . '/application');
        define('__CHT_STATIC__', __CHT_ROOT__ . '/static');
        define('__CHT_MODULE__', __CHT_ROOT__ . '/' . request()->module());
        define('__CHT_CONTROLLER__', __CHT_MODULE__ . '/' . request()->controller());
        define('__CHT_ACTION__', __CHT_CONTROLLER__ . '/' . request()->action());
    }

    //验证token
    function validate_token($uid = 0, $token = "")
    {
        if (empty($uid)) {
            data_format_json(-1001, '', '缺少参数uid');
        }
        if (empty($token)) {
            data_format_json(-1001, '', '缺少参数token');
        }
        $class_xcrypt = new \common\lib\Xcrypt(INTERFACE_KEY, "ofb", INTERFACE_KEY);
        $result = $class_xcrypt->decrypt($token);
        $result_arr = explode("|", $result);
        if (count($result_arr) < 4) {
            data_format_json(-1001, '', 'token err');
        }
        if ($result_arr[1] != $uid) {
            data_format_json(-1001, '', 'token err1');
        }

        $key_token = "cht_user_auth_token_" . $result_arr[1] . $result_arr[2];
        $app_token_server = Cache::get($key_token);
        if ($token != $app_token_server) {
            data_format_json(-1001, '', 'token err2');
        }
        return TRUE;

    }
}