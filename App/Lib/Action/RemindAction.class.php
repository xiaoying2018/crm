<?php
/**
*相关提醒模块
*
**/
class RemindAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add', 'delete', 'view')
		);
		B('Authenticate', $action);
	}

	/**
	*  创建提醒
	*
	**/
	public function add(){
		if($this->isPost()){
			$module = trim($_REQUEST['module']);
			$module_id =  intval($_REQUEST['module_id']);
			$m_remind = M('Remind');
			$m_remind->create();
			$remind_type = intval($_POST['remind_type']);
			if($remind_type){
				switch($remind_type){
					case 1 : $remind_time = time()+3600; break;
					case 2 : $remind_time = time()+3600*2; break;
					case 3 : $remind_time = time()+3600*3; break;
					case 4 : $remind_time = time()+3600*6; break;
					case 5 : $remind_time = time()+3600*8; break;
					case 6 : $remind_time = time()+3600*24; break;
				}
			}else{
				$remind_time_a = $_POST['remind_time_a'];
				$remind_time_b = $_POST['remind_time_b'];
				if($remind_time_a == '' || $remind_time_b == ''){
					$remind_time = time()+600;
				}else{
					$remind_time_a = str_replace('年','-',$remind_time_a);
					$remind_time_a = str_replace('月','-',$remind_time_a);
					$remind_time_a = str_replace('日','',$remind_time_a);
					$remind_time = strtotime($remind_time_a)+(strtotime($remind_time_b)-strtotime(date('Y-m-d',time())));
				}
			}
			$m_remind->remind_time = $remind_time;
			$m_remind->create_role_id = session('role_id');
			if($remind_id = $m_remind->add()){
				//关联日程
				$module = $_POST['module'];
				if($module == 'customer'){
					
				}
				$event_res = dataEvent('提醒',$remind_time,'remind',$remind_id);
				alert('success','设置提醒成功！',$_SERVER['HTTP_REFERER']);
			}else{
				alert('error', '设置提醒失败，请重试！',$_SERVER['HTTP_REFERER']);
			}
		} elseif ($_GET['module'] && $_GET['module_id']) {
			$this->module = $_GET['module'];
			$this->module_id = intval($_GET['module_id']);
			$this->create_role_id = session('role_id');
			$this->display();
		} else {
			alert('error', L('PARAMETER ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}

	/**
	 * 提醒详情
	 *
	**/
	public function view(){
		$module_id = intval($_REQUEST['module_id']);
		$module = trim($_REQUEST['module']);
		$m_remind = M('Remind');
		if(!$module_id){
			echo '<div class="alert alert-error">参数错误！</div>';die();
		}
		//权限判断（根据客户详情权限）
		$below_ids = getPerByAction('customer','view');

		$remind_list = $m_remind->where(array('module'=>$module,'module_id'=>$module_id,'create_role_id'=>array('in',$below_ids),'is_remind'=>0))->order('remind_time asc')->select();
		// if(!$remind_list){
		// 	echo '<div class="alert alert-error">数据不存在或已删除！</div>';die();
		// }
		$this->remind_list = $remind_list;
		$this->module_id = $module_id;
		$this->module = $module;
		$this->display();
	}

	/**
	 * 提醒删除
	 *
	**/
	public function delete(){
		$module_id = intval($_REQUEST['module_id']);
		$module = trim($_REQUEST['module']);
		$remind_id = intval($_REQUEST['remind_id']);
		$m_remind = M('Remind');
		if($this->isPost()){
			$where = array();
			$where['create_role_id'] = session('role_id');
			if($remind_id){
				$where['remind_id'] = $remind_id;
			}else{
				$where['module'] = $module;
				$where['module_id'] = $module_id;
			}
			if(!$m_remind->where($where)->find()){
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if($m_remind->where($where)->delete()){
				//删除关联日程
				$event_res = M('Event')->where(array('module'=>$module,'module_id'=>$module_id))->delete();;
				$this->ajaxReturn('','删除成功！',1);
			}else{
				$this->ajaxReturn('','删除失败，请重试！',0);
			}
		}
	}
}
