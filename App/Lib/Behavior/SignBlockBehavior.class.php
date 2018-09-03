<?php

class SignBlockBehavior extends Behavior
{
    public function run(&$params)
    {
        // TODO: Implement run() method.
//        session( 'block', null );
        // 已有区
        if( session('?block') ) return ;
        // 未登录、超级管理员
        if( !session('user_id') || session('?admin') )   return ;
        // 是否是负责人
//        if( session('?person') )
//            return session( 'block', session('person') );
        // 递归查找
        $model      =   M('RoleDepartment');
        return $this->getDepartmentLevelOne( session('department_id'), $model );

    }
    protected function getDepartmentLevelOne ($department_id, $model)
    {
        // 获取当前部门信息
        $departmentInfo         =   $model->field('name,department_id,parent_id')->where("department_id={$department_id}")->find();
        // 是否是顶级部门
        if( $departmentInfo['parent_id'] == 1 ) {
            $blockModel     =   M('Block');
            $blockInfo      =   $blockModel->field('id,name')
                ->where("department_id={$departmentInfo['department_id']}")->find();
//            dump($blockInfo);exit;
            if( $blockInfo ) {
                session( 'block', $blockInfo['id'] );
                session( 'block_name', $blockInfo['name'] );
            }else{
                session( 'block', -7 );
            }
            return ;
        }elseif( $departmentInfo['parent_id'] == 0 ){
            session( 'block', -7 );
            return ;
        }
        //
        $this->getDepartmentLevelOne( $departmentInfo['parent_id'], $model );
    }
}