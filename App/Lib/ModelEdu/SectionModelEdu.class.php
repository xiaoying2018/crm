<?php

class SectionModelEdu extends EducationModelEdu
{
    protected $tableName            =   'course_section';


    public function sectionByCourseId ($courseId)
    {
        return $this->field('c_s.id,c_s.cate,c_s.video_path,c_s.node,c_s.name,c_s.title,c_s.detail,c_s.duration,mx_u.full_name creator_name,c_s.creator_id,c_s.create_at')
            ->join("c_s LEFT JOIN mxcrm.mx_user mx_u ON mx_u.user_id = c_s.creator_id")
            ->where(['c_s.course_id'=>['eq',$courseId]])
            ->order('c_s.node ASC')
            ->select();
    }


    public function period_schedule ($where=null)
    {
        $_where             =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;
        $_fields        =   'c_sch.id schedule_id,c_sch.homework,c_sch.video,c_sec.title,c_sch.section_id,c_sec.node section_node,
        c_sec.name section_name,c_sec.duration,c_sch.start_time,date_add( c_sch.start_time, INTERVAL c_sec.duration MINUTE ) end_time,
        mxu1.full_name teacher_name,mxu2.full_name creator_name,c_sch.create_at,UNIX_TIMESTAMP(c_sch.start_time) stamp,mh.name homework_name,mh.path homework_path,mv.name video_name,mv.path video_path';
        return $this->field($_fields)
            ->join("c_sec LEFT JOIN {$this->dbName}.course_schedule c_sch ON c_sec.id = c_sch.section_id")
            ->join("LEFT JOIN {$this->dbName}.material mh ON mh.id = c_sch.homework")
            ->join("LEFT JOIN {$this->dbName}.material mv ON mv.id = c_sch.video")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON c_sch.teacher_id = mxu1.user_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON c_sch.creator_id = mxu2.user_id")
            ->where($_where)
            ->order('c_sec.node asc')
            ->select();
    }
}