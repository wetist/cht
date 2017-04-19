<?php
/**
 * 评论模型类
 * User: kongjian
 * Date: 2017/1/17
 * Time: 10:28
 */

namespace app\index\model;

use think\Cache;

class Comment extends Base
{
    protected $pk = 'comment_id';

    protected $readonly = ['uid', 'tale_id', 'create_time', 'content'];

    /**
     * 判断tale_id,comment_id是否匹配
     * @param int $tale_id
     * @param int $comment_id
     * @return int
     */
    function match_tale_id_comment_id($tale_id, $comment_id)
    {
        $where['comment_id'] = $comment_id;
        $where['tale_id'] = $tale_id;
        return $this->where($where)->count();
    }

    /**
     * 改变主表中评论数
     * @author kongjian
     * @param $comment_id
     * @param int $type 1代表自增，2代表自减
     */
    function change_comment_num($comment_id, $type = 1)
    {
        if ($type == 1) {
            $this->where('comment_id', $comment_id)->setInc('comment_num');
            $this->isUpdate(true)->save(['update_time' => time()], ['comment_id' => $comment_id]);
        } elseif ($type == 2) {
            $this->where('comment_id', $comment_id)->setDec('comment_num');
        }
    }

    /**
     * 改变like_num值
     * @param $comment_id
     * @param int $type 1自增，2自减
     * @return int|true|false
     */
    function change_comment_like_num($comment_id, $type = 1)
    {
        if ($type == 1) {
            $this->where('comment_id', $comment_id)->setInc('like_num');
            $like_num = $this->where('comment_id', $comment_id)->value('like_num');
        } elseif ($type == 2) {
            $this->where('comment_id', $comment_id)->setDec('like_num');
            $like_num = $this->where('comment_id', $comment_id)->value('like_num');
        } else {
            $like_num = false;
        }
        return $like_num;
    }

    /**
     * 根据tale_id获取评论列表
     * @author kongjian
     * @param $tale_id
     * @param int $page
     * @return false|mixed|\PDOStatement|string|\think\Collection
     */
    function get_comment_list_by_tale_id($tale_id, $page = 1)
    {
        $key = 'comment_list_tale_' . $tale_id . '_' . $page;
        $comment_list_cache = Cache::get($key);
        if ($comment_list_cache) {
            return $comment_list_cache;
        } else {
            $where['tale_id'] = $tale_id;
            $where['status'] = 0;
            $where['is_deleted'] = 0;
            $comment_list = $this->where($where)->field('comment_id,is_anon,tale_id,uid,sex,user_name,img_head,content,create_time,longitude,latitude')->page($page, 20)->select();
            $comment_list = json_decode(json_encode($comment_list), true);
            Cache::tag('comment_list_tale_' . $tale_id)->set($key, $comment_list, 3600 * 24 * 7);
            return $comment_list;
        }
    }
}