<?php

class PeriodModelEdu extends EducationModelEdu
{
    protected $tableName            =   'course_period';


    public function period_lists ($where=null)
    {
        $_where     =   ['c.status'=>['eq',1], 'c_per.status'=>['eq',1]];
        if( !is_null($where) && is_array($where) )  $_where =   array_merge( $_where, $where );
        return $this->field("c_per.id,c_per.course_id,c.member_type,c_per.name period_name, c.name course_name, 
                            DATE_FORMAT(min(c_sch.start_time),'%Y.%m.%d') start_day,
                           DATE_FORMAT(max(c_sch.start_time),'%Y.%m.%d') end_day,
                           count(1) schedule_count,
                           datediff( max(c_sch.start_time), min(c_sch.start_time) )+1 cycle,
                           sum(c_sec.duration) time_long,mxu.full_name headmaster,mxu2.full_name creator_name,
                           c_per.create_at,c.pic,c.detail course_detail")
            ->join( "c_per LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id" )
            ->join("LEFT JOIN {$this->dbName}.course_schedule c_sch ON c_per.id = c_sch.period_id")
            ->join("LEFT JOIN {$this->dbName}.course_section c_sec ON c_sec.id = c_sch.section_id")
            ->join('LEFT JOIN mx_user mxu ON mxu.user_id = c_per.headmaster_id')
            ->join('LEFT JOIN mx_user mxu2 ON mxu2.user_id = c_per.creator_id')
            ->where($_where)
            ->group('c_per.id')
            ->select();
    }


    public function student_list ($where=null)
    {
        $_where     =   ['c.status'=>['eq',1], 'c_per.status'=>['eq',1]];
        if( !is_null($where) && is_array($where) )  $_where =   array_merge( $_where, $where );

        return $this->field('p_s.id,p_s.create_at,mx_u.full_name creator_name,p_s.student_id,s.realname,s.mobile,s.email')
            ->join("c_per LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN {$this->dbName}.period_student p_s ON p_s.period_id = c_per.id")
            ->join("LEFT JOIN {$this->dbName}.students s ON s.id = p_s.student_id")
            ->join('LEFT JOIN mxcrm.mx_user mx_u ON mx_u.user_id = p_s.creator_id')
            ->where($_where)
            ->select();
    }


}