<?php

class TeacherModelEdu extends EducationModelEdu
{
    protected $tableName            =   'teacher_user';


    public function teacher_lists ($where=null,$page=1,$pagesize=15)
    {
        $build = $this->field('tu.user_id,mxu1.full_name teacher_name,GROUP_CONCAT(er.name) roles,tu.create_at,mxu2.full_name creator_name,count(*) role_count')
            ->join("tu LEFT JOIN {$this->dbName}.edu_role er ON tu.role_id = er.id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON tu.user_id = mxu1.user_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON tu.creator_id = mxu2.user_id");

        if( !is_null( $where ) ){
            $build =  $build->where($where);
        }
        $data = $build->group('tu.user_id')->order('tu.create_at desc')->limit(($page-1)*$pagesize,$pagesize)->select();

        $builds = $this->field('tu.user_id,mxu1.full_name teacher_name,GROUP_CONCAT(er.name) roles,tu.create_at,mxu2.full_name creator_name,count(*) role_count')
            ->join("tu LEFT JOIN {$this->dbName}.edu_role er ON tu.role_id = er.id")
            ->join("LEFT JOIN mxcrm.mx_user mxu1 ON tu.user_id = mxu1.user_id")
            ->join("LEFT JOIN mxcrm.mx_user mxu2 ON tu.creator_id = mxu2.user_id");

        if( !is_null( $where ) ){
            $builds =  $builds->where($where);
        }
        $count = $builds->group('tu.user_id')->order('tu.create_at desc')->select();
        return ['data'=>$data,'count'=>count($count)];
    }

    public function teacher_roles ($where)
    {
        return $this->field('t_u.id,t_u.user_id,t_u.role_id,mx_u1.full_name creator_name,t_u.create_at,e_r.name rolename')
            ->join("t_u LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = t_u.creator_id")
            ->join("LEFT JOIN {$this->dbName}.edu_role e_r ON e_r.id = t_u.role_id")
            ->where($where)
            ->select();
    }

}