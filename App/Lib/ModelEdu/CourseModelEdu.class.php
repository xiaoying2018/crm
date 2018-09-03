<?php

class CourseModelEdu extends EducationModelEdu
{
    protected $tableName            =   'course';

    public function course_lists ($where=null,$page=1,$pagesize=15)
    {
        $_where     =   ['c.status'=>['eq',1]];
        if( !is_null($where) && is_array($where) )  $_where =   array_merge( $_where, $where );
        $data =  $this->field( 'c.id,c.name,COUNT(1) section_total , sum(c_sec.duration) time_long, c.member_type, c.detail, c.pic, c.create_at, c.update_at, mxu_c.full_name creator_name, mxu_u.full_name updator_name' )
                    ->join("c LEFT JOIN {$this->dbName}.course_section c_sec ON c.id = c_sec.course_id")
            ->join('LEFT JOIN mxcrm.mx_user mxu_c ON mxu_c.user_id = c.creator_id')
            ->join('LEFT JOIN mxcrm.mx_user mxu_u ON mxu_u.user_id = c.updator_id')
            ->where($_where)
            ->limit(($page-1)*$pagesize,$pagesize)
            ->group('c.id')->order('c.create_at desc')->select();
       $count =  $this->field( 'c.id,c.name,COUNT(1) section_total , sum(c_sec.duration) time_long, c.member_type, c.detail, c.pic, c.create_at, c.update_at, mxu_c.full_name creator_name, mxu_u.full_name updator_name' )
            ->join("c LEFT JOIN {$this->dbName}.course_section c_sec ON c.id = c_sec.course_id")
            ->join('LEFT JOIN mxcrm.mx_user mxu_c ON mxu_c.user_id = c.creator_id')
            ->join('LEFT JOIN mxcrm.mx_user mxu_u ON mxu_u.user_id = c.updator_id')
            ->where($_where)
            ->group('c.id')->order('c.create_at desc')->select();
       return ['data'=>$data,'count'=>count($count)];
    }
}