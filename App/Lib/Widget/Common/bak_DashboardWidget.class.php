<?php 

class DashboardWidget extends Widget 
{
	public function render($data)
	{
		/**
		 * @author 		: myron
		 * @function	: 获取任务、日程信息
		 * @return		: json格式的今天日程和任务
		 **/
		$role_id = session('role_id');
		//任务
		$m_task = M('task');
		$where['owner_role_id']  = array('like', "%,$role_id,%");
		$where['about_roles']  = array('like',"%,$role_id,%");
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$map['create_date'] = array('between', array(strtotime("-6 month"), strtotime("+3 month")));//半年前和三个月后
		$map['is_deleted'] = array('eq', 0);
		$map['status'] = array('neq', '完成');
		$map['isclose'] = array('eq', 0);
		$task = $m_task->field('task_id, subject, create_date, "task" as type')->where($map)->order('create_date asc')->select();
		$task = $task ? $task : array();
		
		//日程
		$m_event = M('event');
		$condition['owner_role_id']  = array('eq', $role_id);
		$condition['create_date'] = array('between', array(strtotime("-6 month"), strtotime("+3 month")));//半年前和三个月后
		$condition['is_deleted'] = array('eq', 0);
		$condition['status'] = array('neq', '完成');
		$condition['isclose'] = array('eq', 0);
		$event = $m_event->field('event_id,subject, create_date, "event" as type')->where($condition)->order('create_date desc')->select();
		$event = $event ? $event : array();
		
		$calendarData = array_merge($task, $event);
		return $this->renderFile ("index",array('calendarData'=>$calendarData));
	}
}