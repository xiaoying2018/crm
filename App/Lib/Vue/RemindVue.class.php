<?php
/**
 *提醒相关
 **/
class RemindVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add', 'delete', 'view')
		);
		B('VueAuthenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		
		Global $role;
		$this->role = $role;
		Global $roles;
		$this->roles = $roles;

		if($roles == 2){
			$this->ajaxReturn('','您没有此权限！',-2);
		}

		if($roles == 3){
			$this->ajaxReturn('','请先登录！',-1);
		}
	}

	/**
	 * 添加提醒
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$module = trim($_POST['module']);
			$module_id =  intval($_POST['module_id']);
			$m_remind = M('Remind');
			$m_remind->create();
			$remind_type = intval($_POST['remind_type']);
			if ($remind_type) {
				switch($remind_type){
					case 1 : $remind_time = time()+3600; break;
					case 2 : $remind_time = time()+3600*2; break;
					case 3 : $remind_time = time()+3600*3; break;
					case 4 : $remind_time = time()+3600*6; break;
					case 5 : $remind_time = time()+3600*8; break;
					case 6 : $remind_time = time()+3600*24; break;
				}
			} else {
				$remind_time_a = $_POST['remind_time_a'];
				$remind_time_b = $_POST['remind_time_b'];
				if ($remind_time_a == '' || $remind_time_b == '') {
					$remind_time = time()+600;
				} else {
					$remind_time_a = str_replace('年','-',$remind_time_a);
					$remind_time_a = str_replace('月','-',$remind_time_a);
					$remind_time_a = str_replace('日','',$remind_time_a);
					$remind_time = strtotime($remind_time_a)+(strtotime($remind_time_b)-strtotime(date('Y-m-d',time())));
				}
			}
			$m_remind->remind_time = $remind_time;
			$m_remind->create_role_id = session('role_id');
			if ($remind_id = $m_remind->add()) {
				//关联日程
				$event_res = dataEvent('提醒',$remind_time,'remind',$remind_id);
				$this->ajaxReturn('','设置提醒成功！',1);
			} else {
				$this->ajaxReturn('','设置提醒失败，请重试！',0);
			}
		}
	}

	/**
	 * 提醒删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		
		if ($this->isPost()) {
			$module_id = intval($_POST['module_id']);
			$module = trim($_POST['module']);
			$remind_id = intval($_POST['remind_id']);
			$m_remind = M('Remind');
			$where = array();
			$where['create_role_id'] = session('role_id');
			if ($remind_id) {
				$where['remind_id'] = $remind_id;
			} else {
				$where['module'] = $module;
				$where['module_id'] = $module_id;
			}
			if (!$m_remind->where($where)->find()) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if ($m_remind->where($where)->delete()) {
				//删除关联日程
				$event_res = M('Event')->where(array('module'=>$module,'module_id'=>$module_id))->delete();;
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败，请重试！',0);
			}
		}
	}
}