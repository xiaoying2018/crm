<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsApplyModel extends Model
{
    // 编号 学员编号 申请项目分类编号 需要提交的材料 初审截止时间 最终截止时间 材料提交时间 考试时间 面试时间 offer发布时间
    // 材料老师 快递编号 状态 附加服务 标签/分类 COE项目名称 申请项目分类名称 学员姓名
    protected $fields = '
     mx_materials_apply.id,
     mx_materials_apply.student_id,
     mx_materials_apply.cate_id,
     mx_materials_apply.materials,
     mx_materials_apply.first_end_time,
     mx_materials_apply.end_time,
     mx_materials_apply.submit_time,
     mx_materials_apply.exam_time,
     mx_materials_apply.ms_time,
     mx_materials_apply.offer_time,
     mx_materials_apply.teacher,
     mx_materials_apply.ems_code,
     mx_materials_apply.status,
     mx_materials_apply.outer,
     mx_materials_apply.tag,
     mx_materials_apply.project_name,
     cate.name as cname,
     user.full_name as adviser_name,
     user.email as adviser_email,
     user.role_id as adviser_role_id
     ';

    /**
     * 获取列表
     */
    public function getApply($condition)
    {
        return $this
            ->join('mx_materials_sample_cate as cate ON mx_materials_apply.cate_id = cate.id')
            ->join('mx_user as user ON mx_materials_apply.adviser = user.user_id')
            ->field($this->fields)
            ->where($condition)
            ->order('mx_materials_apply.id DESC')
            ->select();
    }

    public function findApply($id)
    {
        return $this
            ->join('mx_materials_sample_cate as cate ON mx_materials_apply.cate_id = cate.id')
            ->join('mx_user as user ON mx_materials_apply.adviser = user.user_id')
            ->field($this->fields)
            ->order('mx_materials_apply.id DESC')
            ->where('mx_materials_apply.id='.$id)
            ->find();
    }

}