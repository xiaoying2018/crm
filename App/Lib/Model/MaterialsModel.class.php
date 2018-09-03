<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsModel extends Model
{
    /**
     * 获取材料列表
     */
    public function getMaterials($condition=[])
    {
        $fields = 'mx_materials.id,mx_materials.student_id, mx_materials.name, mx_materials.file, mx_materials.program_id, mx_materials.create_time, mx_materials.cate_id, cate.name as cname';

        if ($condition) return $this->join('mx_materials_sample_cate as cate ON mx_materials.cate_id = cate.id')->field($fields)->order('mx_materials.id DESC')->where($condition)->select();

        return $this->join('mx_materials_sample_cate as cate ON mx_materials.cate_id = cate.id')->field($fields)->order('mx_materials.id DESC')->select();
    }

}