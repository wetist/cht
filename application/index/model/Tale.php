<?php
/**
 * 吐槽模型类
 * User: kongjian
 * Date: 2017/1/17
 * Time: 10:28
 */

namespace app\index\model;

use think\Cache;

class Tale extends Base
{
    protected $pk = 'tale_id';

    protected $readonly = ['uid', 'user_name', 'geohash', 'longitude', 'latitude', 'create_time', 'img_head'];

    /**
     * 获取附近原始数据
     * @author kongjian
     * @param Decimal $long 经度
     * @param Decimal $lat 纬度
     * @param int $near_error 范围经度，默认为6，代表附近2km
     * @param int $limit
     * @param int $cache_time
     * @return mixed
     */
    function get_tale_list($long = 0, $lat = 0, $near_error = 6, $limit = 40, $cache_time = 3600)
    {
        $key_redis = 'tale_list_' . substr(geohash_encode($long, $lat), 0, $near_error) . '_limit_' . $limit;

        $tale_list_redis = Cache::get($key_redis);

        if ($tale_list_redis) {
            $tale_list = $tale_list_redis;
        } else {
            $neighbors = getNeighbors($long, $lat, $near_error);
            if ($neighbors) {
                $tale_list = $this->query("SELECT * FROM cht_tale WHERE is_deleted = 0 AND status = 0 AND top = 0 AND left(geohash,$near_error) IN ($neighbors) ORDER BY update_time DESC limit $limit");
                Cache::set($key_redis, $tale_list, $cache_time);
            } else {
                $tale_list = [];
            }
        }

        return $tale_list;
    }

    /**
     * 判断uid,tale_id是否匹配
     * @author kongjian
     * @param int $uid
     * @param int $tale_id
     * @return int
     */
    function match_uid_tale_id($uid = 0, $tale_id = 0)
    {
        $where['uid'] = $uid;
        $where['tale_id'] = $tale_id;
        return $this->where($where)->count();
    }

    /**
     * 改变主表中评论数
     * @author kongjian
     * @param $tale_id
     * @param int $type 1代表自增，2代表自减
     */
    function change_comment_num($tale_id, $type = 1)
    {
        if ($type == 1) {
            $this->where('tale_id', $tale_id)->setInc('comment_num');
            $this->isUpdate(true)->save(['update_time' => time()], ['tale_id' => $tale_id]);
        } elseif ($type == 2) {
            $this->where('tale_id', $tale_id)->setDec('comment_num');
        }
    }


    /**
     * 当有新匿名用户评论时anon_comment_num自增
     * $author kongjian
     * @param $tale_id
     */
    function change_anon_user_num($tale_id)
    {
        $this->where('tale_id', $tale_id)->setInc('anon_comment_num');
    }

    /**
     * 改变like_num值
     * @author kongjian
     * @param $tale_id
     * @param int $type 1自增，2自减
     * @return int|true|false
     */
    function change_tale_like_num($tale_id, $type = 1)
    {
        if ($type == 1) {
            $this->where('tale_id', $tale_id)->setInc('like_num');
            $this->update(['update_time' => time()], ['tale_id' => $tale_id]);
            $like_num = $this->where('tale_id', $tale_id)->value('like_num');
        } elseif ($type == 2) {
            $this->where('tale_id', $tale_id)->setDec('like_num');
            $this->update(['update_time' => time()], ['tale_id' => $tale_id]);
            $like_num = $this->where('tale_id', $tale_id)->value('like_num');
        } else {
            $like_num = false;
        }
        return $like_num;
    }

    /**
     * 我发布的
     * @author kongjian
     * @param int $uid
     * @param int $page 分页
     * @return array
     */
    function my_tale_list($uid, $page)
    {
        $where['uid'] = $uid;
        $where['status'] = 0;
        $where['is_deleted'] = 0;
        $field = 'tale_id,uid,user_name,sex,img_head,longitude,latitude,like_num,comment_num,description,type,img,update_time';
        $list = $this->where($where)->order('tale_id', 'desc')->page($page, 20)->field($field)->select();
        $list = jsonToArray($list);
        return $list;
    }

    /**
     * 获取置顶
     * @author kongjian
     * @return array|false|\PDOStatement|string|\think\Collection|\think\Model
     */
    function get_top($top)
    {
        $where['status'] = 0;
        $where['is_deleted'] = 0;
        $where['top'] = ['in', $top];
        $field = '*';
        $top = $this->fetchWhere($where, $field, 1, 1);
        return $top;
    }
}