<?php

class ScheduleModelEdu extends EducationModelEdu
{
    protected $tableName            =   'course_schedule';

    public function schedule_list ($where=null)
    {
        $_where                 =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;

        $_fields        =   'c_sch.id,c_sch.period_id,c_sec.name section_name,c_per.name period_name,c_sec.node section_node,c_sec.duration,
        c_sch.start_time,date_add( c_sch.start_time, INTERVAL c_sec.duration MINUTE ) end_time,c_sch.teacher_id,mxu1.full_name teacher_name,
        c_sch.create_at,mxu2.full_name creator_name,c.name course_name,c.id course_id,c_sec.name section_name,mxu3.full_name headmaster_name,
        UNIX_TIMESTAMP(c_sch.start_time) stamp';

        $build              =   $this->field( $_fields )
            ->join("c_sch LEFT JOIN {$this->dbName}.course_section c_sec ON c_sec.id = c_sch.section_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = c_sch.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON mxu1.user_id = c_sch.teacher_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON mxu2.user_id = c_sch.creator_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu3 ON mxu3.user_id = c_per.headmaster_id")
            ->where($_where)
            ->order('c_sec.node asc')
            ->select();

        return $build;
    }

    ##防止上面方法被占用
    public function schedule_lists ($where=null,$page=1,$pagesize=15)
    {
        $_where                 =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;

        $_fields        =   'c_sch.id,c_sch.period_id,c_sec.name section_name,c_per.name period_name,c_sec.node section_node,c_sec.duration,
        c_sch.start_time,date_add( c_sch.start_time, INTERVAL c_sec.duration MINUTE ) end_time,c_sch.teacher_id,mxu1.full_name teacher_name,
        c_sch.create_at,mxu2.full_name creator_name,c.name course_name,c.id course_id,c_sec.name section_name,mxu3.full_name headmaster_name,
        UNIX_TIMESTAMP(c_sch.start_time) stamp';

        $data              =   $this->field( $_fields )
            ->join("c_sch LEFT JOIN {$this->dbName}.course_section c_sec ON c_sec.id = c_sch.section_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = c_sch.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON mxu1.user_id = c_sch.teacher_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON mxu2.user_id = c_sch.creator_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu3 ON mxu3.user_id = c_per.headmaster_id")
            ->where($_where)
            ->limit(($page-1)*$pagesize,$pagesize)
            ->order('c_sec.node asc')
            ->select();
        $count              =   $this->field( $_fields )
            ->join("c_sch LEFT JOIN {$this->dbName}.course_section c_sec ON c_sec.id = c_sch.section_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = c_sch.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON mxu1.user_id = c_sch.teacher_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON mxu2.user_id = c_sch.creator_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu3 ON mxu3.user_id = c_per.headmaster_id")
            ->where($_where)
            ->order('c_sec.node asc')
            ->select();
        return ['data'=>$data,'count'=>count($count)];
    }


    public function teacher_schedule ($where=null, $order='c_sch.start_time asc')
    {
        $_where                 =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;

        $_fields                =   "c_sch.id,c_sch.serial,c_sch.teacher_id,mxu1.full_name teacher_name,c_sch.start_time,c_sec.duration,DATE_ADD( c_sch.start_time, INTERVAL c_sec.duration MINUTE ) end_time,
        c_per.name period_name,c.name course_name,mxu1.full_name teacher,mxu2.full_name headmaster,UNIX_TIMESTAMP(c_sch.start_time) stamp,
        c_sec.name section_name";
        $builder                =   $this->field($_fields)
            ->join("c_sch LEFT JOIN {$this->dbName}.course_period c_per ON c_sch.period_id = c_per.id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN {$this->dbName}.course_section c_sec ON c_sch.section_id = c_sec.id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON mxu1.user_id = c_sch.teacher_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON mxu2.user_id = c_per.headmaster_id")
            ->where($_where)
            ->order($order);

        return $builder->select();

    }

    public function new_teacher_schedule($where=null, $order='c_sch.start_time desc')
    {
        $_where                 =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;

        $_fields                =   "c_sch.id,c_sch.serial,c_sch.teacher_id,mxu1.full_name teacher_name,c_sch.start_time,c_sec.duration,DATE_ADD( c_sch.start_time, INTERVAL c_sec.duration MINUTE ) end_time,
        c_per.name period_name,c.name course_name,mxu1.full_name teacher,mxu2.full_name headmaster,UNIX_TIMESTAMP(c_sch.start_time) stamp,
        c_sec.name section_name";
        $builder                =   $this->field($_fields)
            ->join("c_sch LEFT JOIN {$this->dbName}.course_period c_per ON c_sch.period_id = c_per.id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_per.course_id")
            ->join("LEFT JOIN {$this->dbName}.course_section c_sec ON c_sch.section_id = c_sec.id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON mxu1.user_id = c_sch.teacher_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON mxu2.user_id = c_per.headmaster_id")
            ->where($_where)
            ->order($order);
        return $builder;

    }



    public function schedule_signin ($where=null)
    {
        $_where                 =   [];
        if( !is_null($where) && is_array($where) )
            $_where     =   $where;

        // TODO this -> period_student -> student -> schedule_student
        $_fields                =   "c_sch.id,p_s.student_id,s.realname student_name,s.code,s_in.status,s_in.create_at,mxu1.full_name creator_name,s_in.update_at,
        mxu2.full_name updator_name,s_in.id signin_id";
        $builder                =   $this->field($_fields)
            ->join("c_sch LEFT JOIN {$this->dbName}.period_student p_s ON p_s.period_id = c_sch.period_id") // 改期下的学院
            ->join("LEFT JOIN {$this->dbName}.students s ON s.id = p_s.student_id")
            ->join("LEFT JOIN {$this->dbName}.schedule_signin s_in ON c_sch.id = s_in.schedule_id AND p_s.student_id = s_in.student_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON s_in.creator_id = mxu1.user_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON s_in.updator_id = mxu2.user_id")
            ->where($_where)
            ->select();

        return $builder;
    }
}