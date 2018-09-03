<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsAbroadModel extends Model
{
    // 编号 学员编号 申请项目分类编号 需要提交的材料 初审截止时间 最终截止时间 材料提交时间 考试时间 面试时间 offer发布时间
    // 材料老师 快递编号 状态 附加服务 标签/分类 COE项目名称 申请项目分类名称 学员姓名
    protected $fields = '
     mx_materials_abroad.id,
     mx_materials_abroad.student_id,
     mx_materials_abroad.cate_id,
     mx_materials_abroad.create_time,
     mx_materials_abroad.notice_time,
     mx_materials_abroad.desc,
     mx_materials_abroad.create_user,
     mx_materials_abroad.status,
     cate.cate_name as cname
     ';

    /**
     * 获取学员列表
     */
    public function getAbroad()
    {
        return $this
            ->join('mx_materials_abroad_cate as cate ON mx_materials_abroad.cate_id = cate.id')
            ->field($this->fields)
            ->order('mx_materials_abroad.id DESC')
            ->select();
    }

    public function findAbroad($id)
    {
        return $this
            ->join('mx_materials_abroad_cate as cate ON mx_materials_abroad.cate_id = cate.id')
            ->field($this->fields)
            ->order('mx_materials_abroad.id DESC')
            ->where('mx_materials_abroad.id='.$id)
            ->find();
    }

}