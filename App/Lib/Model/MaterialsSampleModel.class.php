<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsSampleModel extends Model
{
    /**
     * 获取材料样本列表
     */
    public function getSamples($condition)
    {
        $fields = 'mx_materials_sample.id, mx_materials_sample.name, mx_materials_sample.create_time, mx_materials_sample.cate_id, mx_materials_sample.file, cate.name as cname';

        if ($condition)
        {
            return $this->join('mx_materials_sample_cate as cate ON mx_materials_sample.cate_id = cate.id')->field($fields)->where($condition)->order('mx_materials_sample.id DESC')->select();
        }else{
            return $this->join('mx_materials_sample_cate as cate ON mx_materials_sample.cate_id = cate.id')->field($fields)->order('mx_materials_sample.id DESC')->select();
        }

    }

}