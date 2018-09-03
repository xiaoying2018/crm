<?php
/**
*日程模块
*
**/
class EventVue extends Action{
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add','edit','view','delete')
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
	 * 添加日程
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',-2);
		}
		if($this->isPost()){
			$m_event = M('Event');
			if($m_event->create()){
				$subject = trim($_POST['subject']);
				if($subject == '' || $subject == null){
					$this->ajaxReturn('','请填写日程内容！',0);
				}
				$m_event->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
				$m_event->start_date = $_POST['start_date'] ? $_POST['start_date'] : strtotime(date('Y-m-d',time()));
				$m_event->end_date = $_POST['end_date'] ?$_POST['end_date'] : strtotime(date('Y-m-d',time()));
				$m_event->create_date = time();
				$m_event->update_date = time();
				$m_event->creator_role_id = session('role_id');
				if($event_id = $m_event->add()){
					
					if($_POST['send_email']) {
						$to_user = M('User')->where('role_id = %d', $_POST['owner_role_id'])->field('full_name,email')->find();
						$subjectUrl = '<a href="'.U("event/view",array('id'=>$event_id),'','',true).'">'.$subject.'</a>';
						$content = L('DEAR',array($to_user['full_name'],$subjectUrl,$_POST['start_date'],$_POST['end_date'],$_POST['venue'],$_POST['description']));
						$send =  SendMail($to_user['email'],L('WUKONG_NOTIFICATIONS'),$content,L('WUKONG_SYS'));
					}
					$this->ajaxReturn('','日程添加成功！',1);
				}
			}else{
				$this->ajaxReturn('','添加失败，请重试！',0);
			}
		}
	}
	
	/**
	 * 编辑日程
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){	
		if ($this->isPost()) {
			//权限判断
			$below_ids = getPerByAction('event','index');
			if (!$below_ids) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$m_event = M('Event');
			$event_id = intval($_POST['id']);
			$event_info = $m_event->where('event_id = %d',$event_id)->find();
			//权限判断
			if (empty($event_info)) {
				$this->ajaxReturn('','参数错误！',0);
			} 
			if ($event_info['owner_role_id'] != session('role_id')) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$subject = trim($_POST['subject']);
			if ($subject == '' || $subject == null) {
				$this->ajaxReturn('','请填写日程内容！',0);
			}
			
			$m_event->create();
			if ($_POST['start_date']) {
				$m_event->start_date = $_POST['start_date'];
			}
			if ($_POST['start_date']) {
				$m_event->end_date = $_POST['end_date'];
			}
			$m_event->update_date = time();
			$m_event->event_id = $event_id;
			if ($m_event->save()) {
				$this->ajaxReturn('','修改成功！',1);
			} else {
				$this->ajaxReturn('','修改失败，请重试！',0);
			}
		}
	}
	
	/**
	 * 删除日程
	 * @param 
	 * @author 
	 * @return 
	 */

	public function delete() {
		if ($this->isPost()) {
			//权限判断
			$below_ids = getPerByAction('event','index');
			if (!$below_ids) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$m_event = M('Event');
			$event_id = $_POST['id'] ? intval($_POST['id']) : '';
			if ('' == $event_id) {
				$this->ajaxReturn('','参数错误！',0);
			} else {
				//权限判断
				$event_info = $m_event->where('event_id = %d',$event_id)->find();
				if ($event_info['owner_role_id'] != session('role_id')) {
					$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),-2);
				}
				if ($m_event->where('event_id = %d', $event_id)->delete()) {
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败，请重试！',0);
				}
			}			
		}
	}
	
	/**
	 * 日程
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			//权限判断
			if(!getPerByAction('event','index')){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$m_event = M('Event');
			$m_user = M('User');
			$m_remind = M('Remind');
		 	$m_contract = M('Contract');
		 	$m_customer = M('Customer');

			// $where = array();
			$order = "start_date asc,event_id asc";
			//获取当月时间范围
			$date = $_POST['date'] ? date('Y-m',strtotime($_POST['date'])) : date('Y-m',time());
			$timestamp = strtotime($date);
			$mdays = date('t',$timestamp);
			$start_time = strtotime(date($date.'-1 00:00:00',$timestamp));
			$end_time = strtotime(date($date.'-'.$mdays.' 23:59:59',$timestamp));

			// $where['start_date'] = array('between',array($start_time,$end_time));
			// $where['owner_role_id'] = session('role_id');
			$session_role_id = session('role_id');

			$where = "(`start_date` BETWEEN $start_time AND $end_time AND `owner_role_id` = $session_role_id) OR (`end_date` BETWEEN $start_time AND $end_time AND `owner_role_id` = $session_role_id) OR (`start_date` < $start_time AND `end_date` > $end_time AND `owner_role_id` = $session_role_id)";

			$event_date = $m_event->where($where)->order($order)->select();			
			//生成从开始日期到结束日期的日期数组
			// $date_arr = dateList($start_time,$end_time);

			//获取该月日程日期数组
			$event_arr = array();
			foreach ($event_date as $val) {
				$date_arr = array();
				$date_arr = dateList($val['start_date'],$val['end_date']);
				foreach ($date_arr as $k=>$v) {
					$event_arr[] = date('Y-m-d',$v['sdate']);
				}
			}
			$data['list'] = $event_arr ? $event_arr : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	
	/**
	 * 日程详情
	 * @param 
	 * @author 
	 * @return 
	 */

	public function view(){
		if ($this->isPost()) {
			$m_event = M('Event');
			//根据时间获取该天日程列表
			$start_date = $_POST['date'] ? date('Y-m-d',strtotime($_POST['date'])) : date('Y-m-d',time());
			$start_time = strtotime($start_date);
			$end_time = $start_time+86400;

			$where = array();
			$where['start_date']  = array('between', array($start_time, $end_time));
			$where['end_date']  = array('between', array($start_time, $end_time));
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
			$map['owner_role_id'] = session('role_id');
			$event_id_a = $m_event->where($map)->getField('event_id',true);

			$where = array();
			$where['start_date'] = array('elt',$start_time);
			$where['end_date'] = array('egt',$start_time);
			$where['owner_role_id'] = session('role_id');		
			$event_id_b = $m_event->where($where)->getField('event_id',true);

			if ($event_id_a && $event_id_b) {
				$event_ids = array_merge($event_id_a,$event_id_b);
			} elseif ($event_id_a) {
				$event_ids = $event_id_a;
			} elseif ($event_id_b) {
				$event_ids = $event_id_b;
			} else {
				$event_ids = array();
			}
			$event_list = $m_event->where(array('event_id'=>array('in',$event_ids)))->field('event_id,owner_role_id,subject,start_date,end_date,description,color,module,module_id')->select();
			$m_leads = M('Leads');
			$m_business = M('Business');
			$m_product = M('Product');
			$m_customer = M('Customer');
			$m_contacts = M('Contacts');
			$m_contract = M('Contract');
			$m_remind = M('Remind');
			$event_type = 1; //1普通日程2特殊日程

			foreach ($event_list as $k=>$v) {
				switch($v['module']){
					case 'leads' : 
						$relevant_name = '线索';
						$leads_info = $m_leads->where('leads_id = %d',$v['module_id'])->field('name,company')->find();
						$name = $leads_info['name']. ' ' .$leads_info['company'];
						break;
					case 'business' :
						$relevant_name = '商机';
						$name = $m_business->where('business_id = %d',$v['module_id'])->getField('name');
						break;
					case 'product' :
						$relevant_name = '产品';
						$name = $m_product->where('product_id = %d',$v['module_id'])->getField('name');
						break;
					case 'customer' : 
						$relevant_name = '客户';
						$name = $m_customer->where('customer_id = %d',$v['module_id'])->getField('name');
						break;
					case 'contacts' :
						$relevant_name = '联系人';
						$name = $m_contacts->where('contacts_id = %d',$v['module_id'])->getField('name');
						break;
					case 'remind' :
						$remind_info = $m_remind->where(array('remind_id'=>$v['module_id']))->find();
						$name = M($remind_info['module'])->where(array($remind_info['module'].'_id'=>$remind_info['module_id']))->getField('name');
						$v['module'] = $remind_info['module'];
						$v['module_id'] = $remind_info['module_id'];
						$v['remind_info'] = $remind_info;
						$event_type = 2;
						break;
					case 'contract' :
						$name = $m_contract->where(array('contract_id'=>$v['module_id']))->getField('contract_name');
						$event_type = 2;
						break;
					default :
						$relevant_name = '';
						$name = '';
						break;
				}
				$event_list[$k]['event_type'] = $event_type;
				$event_list[$k]["owner"] = M('User')->where('role_id = %d', $v['owner_role_id'])->field('full_name,role_id')->find();
				$event_list[$k]['module_name'] = $name;
				$event_list[$k]['relevant_name'] = $relevant_name;
			}
			
			$data['list'] = $event_list ? $event_list : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
}