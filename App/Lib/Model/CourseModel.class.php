<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/20
 * Time: 14:29
 */

class CourseModel extends Model
{
    /**
     * 获取课程列表
     * @return mixed
     */
    public function getCourses()
    {
        $fields = 'mx_course.id, mx_course.name, mx_course.desc, mx_course.logo, mx_course.cate_id, cate.name as cname';
        return $this->join('mx_course_cate as cate ON mx_course.cate_id = cate.id')->field($fields)->order('mx_course.id DESC')->select();
    }

    /**
     * @return mixed 统计总数
     */
    public function countCourses()
    {
        return $this->count();
    }

}