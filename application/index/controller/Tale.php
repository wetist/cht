<?php
/**
 * 吐槽控制器
 * User: kongjian
 * Date: 2017/2/16
 * Time: 22:24
 */

namespace app\index\controller;


use think\Cache;
use think\Request;

class Tale extends Base
{
    public function _initialize($token_allow = [], $request = null)
    {
        $token_allow = ['createtale', 'deletetale', 'upload_img', 'mytalelist'];//需要token验证的action,小写
        parent::_initialize($token_allow, $request);
    }

    //获取吐槽列表
    function taleList()
    {
        $request = Request::instance();
        if ($request->isAjax() || $request->isGet() || $request->isPost()) {
            $uid = $request->param('uid', 0, 'intval');
            $token = $request->param('token', '');
            $version = $request->param('version', '');
            $long = $request->param('longitude', null, 'floatval') or data_format_json(-5, '', 'longitude is null');
            $lat = $request->param('latitude', null, 'floatval') or data_format_json(-6, '', 'latitude is null');
            $page = $request->param('page', 1, 'positive_intval');
            $near_error = $request->param('near_error', 6, 'intval');//定位范围误差值，6代表2km内
            $service_tale = new \app\index\service\Tale();
            $tale_list = $service_tale->get_tale_list($long, $lat, $near_error, $page, $uid, $token, $version);
            if ($tale_list) {
                data_format_json(0, $tale_list, 'success');
            } else {
                data_format_json(-1, '', '没有数据');
            }

        }
    }

/**
 * 热门
 */
function taleListHot()//TODO
{
    $this->taleList();
}

//获取单条吐槽详情
function taleInfo()
{
    $request = Request::instance();
    $data['tale_id'] = $request->param('tale_id', 0, 'intval') or data_format_json(-1, '', 'tale_id is null');
    $data['longitude'] = $request->param('longitude', null, 'floatval') or data_format_json(-1, '', 'longitude is null');
    $data['latitude'] = $request->param('latitude', null, 'floatval') or data_format_json(-1, '', 'latitude is null');
    $service_tale = new \app\index\service\Tale();
    $service_tale->get_tale_info($data);
}

//创建吐槽接口
function createTale()
{
    $request = Request::instance();
    if ($request->isAjax() || $request->isGet() || $request->isPost()) {//TODO
        $data['uid'] = $request->param('uid', 0, 'intval');
        $data['form_id'] = $request->param('form_id');
        $data['longitude'] = $request->param('longitude', null, 'floatval') or data_format_json(-5, '', 'longitude is null');
        $data['latitude'] = $request->param('latitude', null, 'floatval') or data_format_json(-6, '', 'latitude is null');
        $data['description'] = $request->param('description', '') or data_format_json(-1, '', 'description is null');
        $data['is_anon'] = $request->param('is_anon', 0, 'intval');
        $data['type'] = $request->param('type', null, 'intval') or data_format_json(-7, '', 'type is null');
        if ($data['type'] == 2) {
            $data['img'] = $request->param('img');
        }
        $service_tale = new \app\index\service\Tale();
        $service_tale->create_tale($data);
    }
}

/**
 * 我发布的
 */
function myTaleList()
{
    $request = Request::instance();
    $uid = $request->param('uid', 0, 'intval') or data_format_json(-1, '', 'uid is null');
    $page = $request->param('page', 1, 'positive_intval');
    $long = $request->param('longitude', null, 'floatval') or data_format_json(-1, '', 'longitude is null');
    $lat = $request->param('latitude', null, 'floatval') or data_format_json(-1, '', 'latitude is null');
    $service_tale = new \app\index\service\Tale();
    $service_tale->my_tale_list($uid, $page, $long, $lat);
}

//删除吐槽
function deleteTale()//TODO
{
    $request = Request::instance();
    $where['uid'] = $request->param('uid', 0, 'intval');
    $where['tale_id'] = $request->param('tale_id', 0, 'intval');
    $service_tale = new \app\index\service\Tale();
    $service_tale->delete_tale($where);
}

//吐槽图片上传
function upload_img()//TODO
{
    $request = Request::instance();
    $file = $request->file('image');
    $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'tale');
    if ($info) {
        $extension = $info->getSaveName();
        $bucket = 'cht-img';
        $object = 'tale/' . $extension;
        $file = 'uploads/tale/' . $extension;
        if (upload_file_oss($bucket, $object, $file)) {
            $res = [
                'image_url' => 'http://img.chuanhuatong.cc/' . $object
            ];
            data_format_json(0, $res, '上传成功');
        }
    } else {
        data_format_json(-100, $file->getError(), '服务异常');
    }
}

}