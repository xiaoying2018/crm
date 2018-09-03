<?php
class CourseProductModelEdu extends EducationModelEdu
{
    protected $tableName            =   'course_product';


    public function getCourseBlongsTo ($where=null)
    {
        $_where     =   ['mx_p.is_deleted'=>['neq',1]];
        if( !is_null($where) )  $where = array_merge( $where, $_where );
        return $this->field('c_p.id,c_p.course_id,c_p.product_id,mx_p.name,mx_p.code,mx_p.cost_price, c_p.create_at, mx_u.full_name creator_name')
            ->join("c_p LEFT JOIN mxcrm.mx_product mx_p ON c_p.product_id = mx_p.product_id")
            ->join("LEFT JOIN mxcrm.mx_user mx_u ON c_p.creator_id = mx_u.user_id ")
            ->where($where)
            ->select();
    }

    public function getProductIncludes ($where=null)
    {
        $_where     =   ['c.status'=>['eq',1]];
        if( !is_null($where) )  $_where = array_merge( $where, $_where );
        return $this->field( 'c_p.id,c_p.course_id,c.name,c.category,c.member_type,c.detail,c.create_at,mx_u.full_name creator_name,c.pic,count(*) section_count,sum(c_sec.duration) time_long' )
            ->join("c_p LEFT JOIN {$this->dbName}.course c ON c_p.course_id = c.id")
            ->join("LEFT JOIN {$this->dbName}.course_section c_sec ON c.id = c_sec.course_id")
            ->join("LEFT JOIN mxcrm.mx_user mx_u ON c_p.creator_id = mx_u.user_id")
            ->where($_where)
            ->group('c_p.course_id')
            ->select();
    }
}