<?php

class SignEduRoleBehavior extends Behavior
{
    public function run(&$params)
    {
        // TODO: Implement run() method.
        // 教务角色
        if( session('?edu_roles') ) return ;
        // 超级用户
        if( session('?admin') ) return ;
        // 标记角色
        $roleIds        =   [];
        if( $user_id=(int)session('user_id') ){
            // 获取当前角色的教务角色
            $teacherModel               =   new TeacherModelEdu();
            // 角色列表
            $roles                      =   $teacherModel->teacher_roles( ['t_u.user_id'=>['eq',$user_id]] ) ?: [];
            // 角色ids
            $roleIds                    =   array_map( function($r){
                return $r['role_id'];
            }, $roles );

            session('edu_roles',$roleIds ? $roleIds : false);
        }
        return ;
    }
}