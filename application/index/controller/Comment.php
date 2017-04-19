<?php
/**
 * 评论控制器
 * User: kongjian
 * Date: 2017/3/21
 * Time: 10:21
 */

namespace app\index\controller;


use think\Cache;
use think\Request;

class Comment extends Base
{
    public function _initialize($token_allow = [], $request = null)
    {
        $token_allow = [];//需要token验证的action,小写
        parent::_initialize($token_allow, $request);
    }

    /**
     * 创建评论
     */
    function createComment()
    {
        $request = Request::instance();
        $data['uid'] = $request->param('uid', 0, 'intval') or data_format_json(-1, '', 'uid is null');
        $data['comment_id'] = $request->param('comment_id', 0, 'intval');
        $data['tale_id'] = $request->param('tale_id', 0, 'intval') or data_format_json(-1, '', 'tale_id is null');
        $data['content'] = $request->param('content', '') or data_format_json(-1, '', 'content is null');
        $data['is_anon'] = $request->param('is_anon', 0, 'intval');
        $data['longitude'] = $request->param('longitude', null, 'floatval') or data_format_json(-1, '', 'longitude is null');
        $data['latitude'] = $request->param('latitude', null, 'floatval') or data_format_json(-1, '', 'latitude is null');
        $service_comment = new \app\index\service\Comment();
        $service_comment->create_comment($data);
    }

    /**
     * 获取评论列表
     */
    function commentList()
    {
        $request = Request::instance();
        $data['tale_id'] = $request->param('tale_id', 0, 'intval') or data_format_json(-1, '', 'tale_id is null');
        $data['page'] = $request->param('page', 1, 'intval');
        $data['longitude'] = $request->param('longitude', null, 'floatval');
        $data['latitude'] = $request->param('latitude', null, 'floatval');
        $service_comment = new \app\index\service\Comment();
        $service_comment->get_comment_list_by_tale_id($data);
    }

    function test()
    {
        Cache::clear('comment_list_tale_1');
    }
}