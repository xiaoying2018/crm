<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class MaterialsInsuranceModel extends Model
{
    // 编号 学员编号 申请项目分类编号 需要提交的材料 初审截止时间 最终截止时间 材料提交时间 考试时间 面试时间 offer发布时间
    // 材料老师 快递编号 状态 附加服务 标签/分类 COE项目名称 申请项目分类名称 学员姓名
    protected $fields = '
     mx_materials_insurance.id,
     mx_materials_insurance.student_id,
     mx_materials_insurance.insurance_name,
     mx_materials_insurance.insurance_code,
     mx_materials_insurance.insurance_sum,
     mx_materials_insurance.start_time,
     mx_materials_insurance.end_time,
     mx_materials_insurance.desc,
     mx_materials_insurance.create_user
     ';

    /**
     * 获取保险服务列表
     */
    public function getInsurances()
    {
        return $this
            ->field($this->fields)
            ->order('mx_materials_insurance.id DESC')
            ->select();
    }

    public function findInsurance($id)
    {
        return $this
            ->field($this->fields)
            ->order('mx_materials_insurance.id DESC')
            ->where('mx_materials_insurance.id='.$id)
            ->find();
//        return $this
//            ->join('mx_materials_sample_cate as cate ON mx_materials_apply.cate_id = cate.id')
//            ->join('mx_customer as student ON mx_materials_apply.student_id = student.customer_id')
//            ->field($this->fields)
//            ->where('id='.$id)
//            ->order('mx_materials_apply.id DESC')
//            ->find();
    }

}