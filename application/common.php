<?php
//短信发送公共方法
function send_sms($mobile = '', $text = '')
{
    if ($mobile && $text) {
        include_once EXTEND_PATH . 'sms-yunpian/YunpianAutoload.php';
        $smsOperator = new SmsOperator();
        $data['mobile'] = $mobile;
        $data['text'] = $text;
        $smsOperator->single_send($data);
    }
}

//json结果返回
function data_format_json($error_code = 0, $content = [], $msg = '')
{
    $data = [];
    $data['ret'] = $error_code;
    $data['data'] = $content;
    $data['msg'] = $msg;

    echo json_encode($data);
    exit;
}

/**
 * 判断是否是手机号
 * @param int $mobile_num
 * @return int 0代表不是 1代表是
 */
function is_mobile_num($mobile_num = 0)
{
    $pattern_mobile = "/^1\d{10}$/";
    $is_mobile = preg_match($pattern_mobile, $mobile_num);
    return $is_mobile;
}

//随机六位数
function rand_number($length = 6)
{
    if ($length < 1) {
        $length = 6;
    }

    $min = 1;
    for ($i = 0; $i < $length - 1; $i++) {
        $min = $min * 10;
    }
    $max = $min * 10 - 1;

    return rand($min, $max);
}

/**
 * 将geohash值解码为经纬度
 * @param string $hash geohash值
 * @return array|bool
 */
function geohash_decode($hash = '')
{
    if (!$hash) {
        return false;
    } else {
        $geo = new \app\index\controller\GeoHash();
        $result = $geo->decode($hash);
        $lat = $result[0];
        $long = $result[1];
        $result = [$long, $lat];
        return $result;
    }
}

/**
 * 将普通经纬度进行geohash编码
 * @param int $long 经度
 * @param int $lat 纬度
 * @return bool|string 返回编码后的geohash
 */
function geohash_encode($long = 0, $lat = 0)
{
    if ($lat == 0 && $long == 0) {
        return false;
    } else {
        $geo = new \app\index\controller\GeoHash();
        $result = $geo->encode($lat, $long);
        return $result;
    }
}

/**
 * 根据geohash值得到与自身相近的八个区域的geohash
 * @param int $long 经度
 * @param int $lat 纬度
 * @param int $str_num geohash经度，默认为6,代表附近2km内
 * @return bool|string
 */
function getNeighbors($long = 0, $lat = 0, $str_num = 6)
{
    if ($lat == 0 && $long == 0) {
        return false;
    } else {
        $geo = new \app\index\controller\GeoHash();

        $hash = $geo->encode($lat, $long);

        $pre_hash = substr($hash, 0, $str_num);

        //取出相邻八个区域
        $neighbors = $geo->neighbors($pre_hash);
        array_push($neighbors, $pre_hash);

        $values = '';
        foreach ($neighbors as $key => $val) {
            $values .= '\'' . $val . '\'' . ',';
        }
        $values = substr($values, 0, -1);

        return $values;
    }
}

/**
 * 计算两点地理坐标之间的距离
 * @param  Decimal $longitude1 起点经度
 * @param  Decimal $latitude1 起点纬度
 * @param  Decimal $longitude2 终点经度
 * @param  Decimal $latitude2 终点纬度
 * @param  Int $decimal1 公里精度 保留小数位数
 * @param  Int $decimal2 米精度 保留小数位数
 * @return string
 */
function getDistance($longitude1 = 0, $latitude1 = 0, $longitude2 = 0, $latitude2 = 0, $decimal1 = 1, $decimal2 = 0)
{
    if ($latitude1 == 0 || $longitude1 == 0 || $latitude2 == 0 || $longitude2 == 0) {
        return '';
    }
    $EARTH_RADIUS = 6370.996; // 地球半径系数
    $PI = 3.1415926;

    $radLat1 = $latitude1 * $PI / 180.0;
    $radLat2 = $latitude2 * $PI / 180.0;

    $radLng1 = $longitude1 * $PI / 180.0;
    $radLng2 = $longitude2 * $PI / 180.0;

    $a = $radLat1 - $radLat2;
    $b = $radLng1 - $radLng2;

    $distance = 2 * asin(sqrt(pow(sin($a / 2), 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2)));
    $distance = $distance * $EARTH_RADIUS * 1000;

    if ($distance >= 1000) {
        $distance = $distance / 1000;
        $distance = round($distance, $decimal1) . 'km';
    } else {
        $distance = round($distance, $decimal2) . 'm';
    }

    return $distance;
}

/**
 * 获取时间差
 * @param $time
 * @return false|float|int|string
 */
function getTimeDifference($time = 0)
{
    if (!$time) {
        $result = '';
    } else {
        $time = is_int($time) ? $time : strtotime($time);
        $d_value = time() - $time;
        if ($d_value < 3600) {
            $result = $d_value / 60;
            if ($result < 1) {
                $result = '1分钟前';
            } else {
                $result = intval($result) . '分钟前';
            }
        } elseif ($d_value < 86400) {
            $result = $d_value / 3600;
            $result = intval($result) . '小时前';
        } else {
            $result = date('Y-m-d H:i', $time);
        }
    }
    return $result;
}

/**
 * 对数据进行处理，不是正正数返回1
 * @param $num
 * @return int
 */
function positive_intval($num)
{
    $num = intval($num);
    $num = $num > 0 ? $num : 1;
    return $num;
}

/**
 * 将select产生的数据转换成纯数组数据
 * @author kongjian
 * @param $data
 * @return mixed
 */
function jsonToArray($data)
{
    $data = json_decode(json_encode($data), true);
    return $data;
}

//php curl GET 方法
function http_get($url)
{ // 模拟获取内容函数
    $ua = "cht://v1.0.0(Linux;v0.01;zh_cn;)-chtweb";
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_USERAGENT, $ua); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的GET请求
    curl_setopt($curl, CURLOPT_TIMEOUT, 10); // 最多10秒 超过10超时
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $output = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        return array(
            'ret' => -100,
            'msg' => 'Errno' . curl_error($curl)
        );
    }
    curl_close($curl); // 关闭CURL会话
    return $output; // 返回数据
}

function http_post($url, $data = '')
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 最多10秒 超过10超时
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

//获取微信access_token
function wxapp_access_token()
{
    $accesstoken = \think\Cache::get('wxapp_access_token');
    if ($accesstoken) {
        $access_token = $accesstoken;
    } else {
        $appid = 'wxbfbc582268450e07';
        $secret = '2017a2e0fcc6c11927ad3e62a17d76ae';
        $grant_type = 'client_credential';
        $arr = file_get_contents("https://api.weixin.qq.com/cgi-bin/token?appid=" . $appid . "&secret=" . $secret . "&grant_type=" . $grant_type);
        $arr = json_decode($arr);
        $access_token = $arr->access_token;
        \think\Cache::set('wxapp_access_token', $access_token, 3600);
    }

    return $access_token;
}