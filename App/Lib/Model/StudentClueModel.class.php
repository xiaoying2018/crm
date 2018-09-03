<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 16:43
 */

class StudentClueModel extends Model
{
    /**
     * 获取评估数据列表
     * @param array $where
     * @param int $start
     * @param int $limit
     * @return mixed
     * TODO 如果需要分页,$limit需要修改默认值
     */
    public function getDatas($where=[],$start=0,$limit=100000)
    {
        if ( !$where )
            return $this->order('time desc')->limit($start,$limit)->select();

        return $this->where($where)->order('time desc')->limit($start,$limit)->select();
    }

    /**
     * 统计条数
     * @param array $where
     * @return mixed
     */
    public function countDatas($where=[])
    {
        if ( !$where )
            return $this->count();

        return $this->where($where)->count();
    }

}