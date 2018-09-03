<?php 
class LogViewModel extends ViewModel{
	protected $viewFields;
	public function _initialize(){
		$log_talk = array('talk_id');
		$log_talk['_on'] = 'log.log_id=log_talk.log_id and log_talk.send_role_id = '.session('role_id');
		$log_talk['_type'] = "LEFT";
		$this->viewFields = array(
			'log'=>array('log_id', 'role_id', 'category_id','create_date','subject', 'content', '_type'=>'LEFT'),
			'log_talk'=>$log_talk,
			'role'=>array('_on'=>'log.role_id=role.role_id', '_type'=>'LEFT'),
			'user'=>array('full_name'=>'user_name','thumb_path'=>'img', '_on'=>'user.user_id=role.user_id',  '_type'=>'LEFT'),
			'position'=>array('name'=>'role_name', '_on'=>'position.position_id=role.position_id', '_type'=>'LEFT'),
			'role_department'=>array('name'=>'department_name', '_on'=>'role_department.department_id=position.department_id')
		);
	}
}