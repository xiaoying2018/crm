<?php 
class RoleViewModel extends ViewModel{
	public $viewFields = array(
	    //岗位表
		'role'=>array('user_id', 'role_id', 'position_id', '_type'=>'LEFT'),
		//用户表
		'user'=>array('user_id','extid','name'=>'login_name','full_name'=>'user_name','status','nick_name','weixinid','category_id', 'sex', 'address', 'email', 'img','thumb_path', 'telephone','hometown','customer_num','birthday','entry','introduce','office_tel','qq','dashboard','full_name','prefixion','number','type','wechat', '_on'=>'user.user_id=role.user_id',  '_type'=>'LEFT'),
		//岗位表控制权限
        'position'=>array('name'=>'role_name', 'parent_id',  'department_id', 'description','_on'=>'position.position_id=role.position_id', '_type'=>'LEFT'),
		//部门信息
        'role_department'=>array('name'=>'department_name', '_on'=>'role_department.department_id=position.department_id')
	);
}