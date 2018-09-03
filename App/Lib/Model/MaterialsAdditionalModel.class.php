<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsAdditionalModel extends Model
{
    protected $fields = '
     mx_materials_additional.id,
     mx_materials_additional.student_id,
     mx_materials_additional.server_cate,
     mx_materials_additional.create_time,
     mx_materials_additional.notice_time,
     mx_materials_additional.teacher,
     mx_materials_additional.status,
     student.name as sname
     ';

    /**
     * 获取附加服务列表
     */
    public function getAdditional($cate)
    {
        if ($cate)
        {
            return $this
                ->join('mx_customer as student ON mx_materials_additional.student_id = student.customer_id')
                ->field($this->fields)
                ->where('server_cate='.intval($cate))
                ->order('mx_materials_additional.id DESC')
                ->select();
        }

        return $this
            ->join('mx_customer as student ON mx_materials_additional.student_id = student.customer_id')
            ->field($this->fields)
            ->order('mx_materials_additional.id DESC')
            ->select();
    }

}