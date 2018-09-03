<?php
/**
*日程模块
*
**/
class EventAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(''),
			'allow'=>array('close','open','event_dialog','getcurrentstatus','editable','indexdata','add','view','delete','edit')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}

	/**
	*添加日程
	*
	**/
	public function add(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}
		if($this->isPost()){
			$m_event = M('Event'); 
			if($m_event->create()){
				$subject = trim($_POST['subject']);
				if($subject == '' || $subject == null){
					$this->ajaxReturn('',L('PLEASE_FILL_OUT_THE_AGENDA_TOPICS'),0);
				}
				$m_event->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
				$m_event->start_date = $_POST['start_date'] ? strtotime($_POST['start_date']) : strtotime(date('Y-m-d',time()));
				$m_event->end_date = $_POST['end_date'] ? strtotime($_POST['end_date']) : strtotime(date('Y-m-d',time()));
				$m_event->create_date = time();
				$m_event->update_date = time();
				$m_event->creator_role_id = session('role_id');
				if($event_id = $m_event->add()){
					
					if($_POST['send_email']) {
						$to_user = M('User')->where('role_id = %d', $_POST['owner_role_id'])->field('full_name,email')->find();
						$subjectUrl = '<a href="'.U("event/view",array('id'=>$event_id),'','',true).'">'.$subject.'</a>';
						$content = L('DEAR',array($to_user['full_name'],$subjectUrl,$_POST['start_date'],$_POST['end_date'],$_POST['venue'],$_POST['description']));
						$send = SendMail($to_user['email'],L('WUKONG_NOTIFICATIONS'),$content,L('WUKONG_SYS'));
					}
					// actionLog($event_id);
					if($_POST['submit'] == L('SAVE')) {
						if($_POST['dynamic_dialog_add']){
							$this->ajaxReturn('',L('ADD SUCCESS', array(L('EVENT'))),1);
						}else{
							$this->ajaxReturn('',L('ADD SUCCESS', array(L('EVENT'))),1);
						}
					} else {
						$this->ajaxReturn('',L('ADD SUCCESS', array(L('EVENT'))),1);
					}
				}
			}else{
				$this->ajaxReturn('',L('ADD SCHEDULE_TO_ADD_FAILURE'),0);
			}
		}elseif($_GET['r'] && $_GET['module'] && $_GET['id']){
			$this->r = $_GET['r'];
			$this->module2 = $_GET['module'];
			$this->id = $_GET['id'];
			$this->display('Event:add_dialog');
		}elseif($_POST['dialog_add']){
			$module = $_POST['module'];
			$r = $_POST['r'];
			$id = $_POST['id'];
			$m_event = M('Event');
			if($m_event->create()){
				$m_event->start_date = strtotime($_POST['start_date']);
				$m_event->end_date = strtotime($_POST['end_date']);
				$m_event->create_date = time();
				$m_event->update_date = time();
				if($event_id = $m_event->add()){
					$data[$module . '_id'] = $id;
					$data['event_id'] = $event_id;
					if(M($r)->add($data)){
						actionLog($event_id);
						alert('success', L('ADD SUCCESS', array(L('EVENT'))), $_SERVER['HTTP_REFERER']);
					}else{
						alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
					}
				}else{
					alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
			}
		}elseif($_GET['t']=='dynamic'){
			$this->alert = parseAlert();
			$this->display('Event:add_dialog');
		}else{
			// $this->alert = parseAlert();
			$this->start_date = $_GET['start_date'] ? date('Y-m-d H:i:s',strtotime($_GET['start_date'])) : date('Y-m-d H:i:s',time());
			$this->end_date = $_GET['start_date'] ? date('Y-m-d H:i:s',(strtotime($_GET['start_date'])+86399)) : date('Y-m-d H:i:s',strtotime(date('Y-m-d',(time())))+86399);
			$this->display();
		}
	}
	
	/**
	*编辑日程
	*
	**/
	public function edit(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}
		$m_event = M('Event');
		$event_id = intval($_REQUEST['event_id']);
		$event_info = $m_event->where('event_id = %d',$event_id)->find();
		//权限判断
		if(empty($event_info)){
			echo '<div class="alert alert-error">参数错误！</div>';die();
		}elseif($event_info['owner_role_id'] != session('role_id')){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}
		// if($event_info['isclose'] == 1){
		// 	echo '<div class="alert alert-error">已经关闭不能编辑！</div>';die();
		// }
		if ($this->isPost()) {
			$m_event->create();
			$subject = trim($_POST['subject']);
			if($subject=='' || $subject==null){
				$this->ajaxReturn('',L('PLEASE_FILL_OUT_THE_AGENDA_TOPICS'),0);
			}
			if($_POST['start_date']){
				$m_event->start_date = strtotime($_POST['start_date']);
			}
			if($_POST['start_date']){
				$m_event->end_date = strtotime($_POST['end_date']);
			}
			$m_event->update_date = time();
			$event_id = intval($_POST['event_id']);
			if($m_event->save()){
				// actionLog($event_id);
				$this->ajaxReturn('',L('CALENDAR_INFORMATION_MODIFY_SUCCESS'),1);
			}else{
				$this->ajaxReturn('',L('TO_CHANGE_MODIFY_FAILED'),0);
			}
		}
		$event_info['owner'] = D('RoleView')->where('role.role_id = %d', $event_info['owner_role_id'])->find();
		switch($event_info['module']){
			case 'leads' : 
				$leads_info = M('Leads')->where('leads_id = %d',$event_info['module_id'])->field('name,company')->find();
				$name = $leads_info['name']. ' ' .$leads_info['company'];
				break;
			case 'business' :
				$name = M('Business')->where('business_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'product' :
				$name = M('Product')->where('product_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'customer' : 
				$name = M('Customer')->where('customer_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'contacts' :
				$name = M('Contacts')->where('contacts_id = %d',$event_info['module_id'])->getField('name');
				break;
		}
		$event_info['module_name'] = $name ? $name : '';
		$this->event_info = $event_info;
		$this->display();
	}
	
	/**
	*删除日程
	*
	**/
	public function delete(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}
		$m_event = M('Event');
		if($this->isPost()){
			$event_id = $_POST['event_id'] ? intval($_POST['event_id']) : '';
			if ('' == $event_id) {
				$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
			} else {
				//权限判断
				$event_info = $m_event->where('event_id = %d',$event_id)->find();
				if($event_info['owner_role_id'] != session('role_id')){
					$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
				}
				if($m_event->where('event_id = %d', $event_id)->delete()){
					// actionLog($event_id);
					$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
				} else {
					$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
				}
			}			
		} else {
			$this->ajaxReturn('',L('PLEASE_SELECT_A_CLUE_TO_DELETE'),0);
		}
	}
	
	/**
	*日程列表页（默认页面）
	*
	**/
	public function index(){
		//更新最后阅读时间
		$m_user = M('user');
		$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
		$last_read_time = json_decode($last_read_time_js, true);
		$last_read_time['event'] = time();
		$m_user->where('role_id = %d', session('role_id'))->setField('last_read_time',json_encode($last_read_time));

		// $below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
		$m_event = M('Event');
		$order = "start_date asc,event_id asc";

		$where['owner_role_id'] = session('role_id');

		if(trim($_GET['act']) == 'excel'){
			if(!checkPerByAction('knowledge','excelexport')){
				alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}else{
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				$eventList = $m_event->where($where)->order($order)->limit($limit)->select();
				session('export_status', 1);				
				$this->excelExport($eventList);
			}
		}

		$this->now_date = date('Y-m-d',time());
		$this->alert = parseAlert();
		$this->display();
	}  
	
	/**
	*查看日程详情
	*
	**/
	public function view(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}
		$m_event = M('Event');
		$m_leads = M('Leads');
		$m_business = M('Business');
		$m_product = M('Product');
		$m_customer = M('Customer');
		$m_contacts = M('Contacts');
		$m_contract = M('Contract');
		$m_receivables = M('Receivables');
		$m_cycel = M('Cycel');

		switch (trim($_REQUEST['event_id'])) {
			case 'receivables' : 
				$module_id = intval($_REQUEST['module_id']);
				$cycel_info = $m_cycel->where(array('module'=>'receivables','module_id'=>$module_id))->find();
				$event_info['subject'] = '应收款提醒';
				$event_info['module'] = 'receivables';
				$event_info['module_id'] = $module_id;
				$event_info['t'] = 'receivables';
				$event_info['owner_role_id'] = $cycel_info['create_role_id'];
				break;
			default : 
				$event_id = intval($_REQUEST['event_id']);
				$event_info = $m_event->where('event_id = %d',$event_id)->find();
				break;
		}
		if(empty($event_info)){
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}elseif($event_info['owner_role_id'] != session('role_id')){
			$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
		}
		$m_remind = M('Remind');
		$event_type = 1; //1普通日程(改、删)2特殊日程（删）3特殊（无改删）
		switch($event_info['module']){
			case 'leads' : 
				$relevant_name = '线索';
				$leads_info = $m_leads->where('leads_id = %d',$event_info['module_id'])->field('name,company')->find();
				$name = $leads_info['name']. ' ' .$leads_info['company'];
				break;
			case 'business' :
				$relevant_name = '商机';
				$name = $m_business->where('business_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'product' :
				$relevant_name = '产品';
				$name = $m_product->where('product_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'customer' : 
				$relevant_name = '客户';
				$name = $m_customer->where('customer_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'contacts' :
				$relevant_name = '联系人';
				$name = $m_contacts->where('contacts_id = %d',$event_info['module_id'])->getField('name');
				break;
			case 'remind' :
				$remind_info = $m_remind->where(array('remind_id'=>$event_info['module_id']))->find();
				$field_name = 'name';
				if ($remind_info['module'] == 'contract') {
					$field_name = 'contract_name';
				}
				$name = M($remind_info['module'])->where(array($remind_info['module'].'_id'=>$remind_info['module_id']))->getField($field_name);
				$event_info['module'] = $remind_info['module'];
				$event_info['module_id'] = $remind_info['module_id'];
				$event_info['remind_info'] = $remind_info;
				$event_type = 2;
				break;
			case 'contract' :
				$name = $m_contract->where(array('contract_id'=>$event_info['module_id']))->getField('contract_name');
				$event_type = 2;
				break;
			case 'receivables' :
				$name = $m_receivables->where(array('receivables_id'=>$event_info['module_id']))->getField('name');
				$event_type = 3;
				break;
			default :
				$relevant_name = '';
				$name = '';
				break;
		}

		$event_info['event_type'] = $event_type;
		$event_info['module_name'] = $name;
		$event_info['relevant_name'] = $relevant_name;
		$this->event_info = $event_info;
		$this->display();
	}
	
	/**
	*导出日程到excel表格
	*
	**/
	public function excelExport($eventList=false){
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Event Data");    
		$objProps->setSubject("mxcrm Event Data");    
		$objProps->setDescription("mxcrm Event Data");    
		$objProps->setKeywords("mxcrm Event Data");    
		$objProps->setCategory("Event");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
		$objActSheet->setCellValue('A1', L('THEME'));
		$objActSheet->setCellValue('B1', L('PLACE'));
		$objActSheet->setCellValue('C1', L('OWNER_ROLE'));
		$objActSheet->setCellValue('D1', L('START_TIME'));
		$objActSheet->setCellValue('E1', L('END_TIME'));
		$objActSheet->setCellValue('F1', L('WHETHER_TO_SEND_A_NOTIFICATION_EMAIL'));
		$objActSheet->setCellValue('G1', L('CONTENT'));
		$objActSheet->setCellValue('H1', L('CREATOR_ROLE'));
		$objActSheet->setCellValue('I1', L('CREATE_TIME'));
		
		if(is_array($eventList)){
			$list = $eventList;
		}else{
			$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
			$where['is_deleted'] = 0;
			$list = M('event')->where($where)->select();
		}
		
		$i = 1;
		foreach ($list as $k => $v) {
			$i++;
			$creator = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			$owner = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
			$objActSheet->setCellValue('A'.$i, $v['subject']);
			$objActSheet->setCellValue('B'.$i, $v['venue']);
			$objActSheet->setCellValue('C'.$i, $owner['user_name'].'['.$owner['department_name'].'-'.$owner['role_name'].']');
			$v['start_date'] == 0 || strlen($v['start_date']) != 10 ? '' : $objActSheet->setCellValue('D'.$i, date("Y-m-d", $v['start_date']));
			$v['end_date'] == 0 || strlen($v['end_date']) != 10 ?  '': $objActSheet->setCellValue('E'.$i, date("Y-m-d", $v['end_date']));
			$v['send_email'] == 0 ? $objActSheet->setCellValue('F'.$i, L('NO')) : $objActSheet->setCellValue('F'.$i, L('YES'));
			$objActSheet->setCellValue('G'.$i, $v['description']);
			$objActSheet->setCellValue('H'.$i, $creator['user_name'].'['.$creator['department_name'].'-'.$creator['role_name'].']');
			$objActSheet->setCellValue('I'.$i, date("Y-m-d H:i:s", $v['create_date']));
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_event_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
		session('export_status', 0);
	}
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
	}
	
	/**
	*导入excel表格
	*
	**/
	public function excelImport(){
		$m_event = M('event');
		if($this->isPost()){
			if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('xls');
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error', L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'), U('event/index'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					alert('error', $upload->getErrorMsg(), U('event/index'));
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}
			if(is_array($info[0]) && !empty($info[0])){
				$savePath = $dirname . $info[0]['savename'];
			}else{
				alert('error', L('UPLOAD FAILED'), U('event/index'));
			};
			import("ORG.PHPExcel.PHPExcel");
			$PHPExcel = new PHPExcel();
			$PHPReader = new PHPExcel_Reader_Excel2007();
			if(!$PHPReader->canRead($savePath)){
				$PHPReader = new PHPExcel_Reader_Excel5();
			}
			$PHPExcel = $PHPReader->load($savePath);
			$currentSheet = $PHPExcel->getSheet(0);
			$allRow = $currentSheet->getHighestRow();
			for ($currentRow = 2;$currentRow <= $allRow;$currentRow++) {
				$data['subject'] = $currentSheet->getCell('B'.$currentRow)->getValue();
				$data['venue'] = $currentSheet->getCell('C'.$currentRow)->getValue();
				$data['owner_role_id'] = $currentSheet->getCell('F'.$currentRow)->getValue();
				$data['start_date'] = strtotime($currentSheet->getCell('H'.$currentRow)->getValue());
				$data['end_date'] = strtotime($currentSheet->getCell('I'.$currentRow)->getValue());
				$data['recurring'] = $currentSheet->getCell('J'.$currentRow)->getValue();
				$data['send_email'] = $currentSheet->getCell('K'.$currentRow)->getValue();
				$data['description'] = $currentSheet->getCell('L'.$currentRow)->getValue();
				$data['creator_role_id'] = $currentSheet->getCell('O'.$currentRow)->getValue();
				$data['create_date'] = strtotime($currentSheet->getCell('P'.$currentRow)->getValue());
				$data['update_date'] = strtotime($currentSheet->getCell('Q'.$currentRow)->getValue());
				if(!$m_event->add($data)) {
					if($this->_post('error_handing','intval',0) == 0){
							alert('error', L('ERROR INTRODUCED INTO THE LINE',array($currentRow,$m_event->getError())), U('event/index'));
						}else{
							$error_message .= L('LINE ERROR',array($currentRow,$m_event->getError()));
							$m_event->clearError();
						}
					break;
				}
			}
			alert('success', L('IMPORT SUCCESS',array($error_message)), U('event/index'));
		}else{
			$this->display();
		}
	}
	/**
	*动态页日程列表页（默认页面）
	*
	**/
	public function event_dialog(){
		//更新最后阅读时间
		$m_event = M('Event');
		$month_years = $_GET['month'];
		$days = $_GET['days'];
		$yue = strpos($month_years,'月');
		$month = substr($month_years,0,$yue);
		$years = substr($month_years,$yue+3);
		$str_time = $years.'-'.$month.'-'.$days;
		$times = strtotime($str_time);
		$event = $m_event ->where('is_deleted = 0 and isclose = 0 and owner_role_id =%d',session('role_id'))->select();
		foreach($event as $k=>$v){
			if($times >= $v['start_date'] && $times <= $v['end_date']){
				$event_ids[] += $v['event_id'];
			}
		}
		$event_ids = implode(',',$event_ids);
		$m_user = M('user');
		$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
		$last_read_time = json_decode($last_read_time_js, true);
		$last_read_time['event'] = time();
		$m_user->where('role_id = %d', session('role_id'))->setField('last_read_time',json_encode($last_read_time));
	
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,$sub_role=true);
		$where = array();
		$params = array();
		$where['event_id'] = array('in',$event_ids) ;
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$list = $m_event->where($where)->order($order)->page($p.',15')->select();
		$count = $m_event->where($where)->count();
		import("@.ORG.Page");
		$Page = new Page($count,15);
		$params[] = 'by=' . trim($_GET['by']);
		$this->parameter = implode('&', $params);
		$Page->parameter = implode('&', $params);
		$show = $Page->show();		
		$this->assign('page',$show);

		$user = M('User');
		foreach($list as $key=>$value){
			$list[$key]["owner"] = D('RoleView')->where('role.role_id = %d', $value['owner_role_id'])->find();
		}
		$this->assign('eventlist',$list);
		$this->alert = parseAlert();
		$this->display();
	}  

	/**
	*日程数据调用
	*
	**/
	public function indexdata(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}
		$m_event = M('Event');
		$m_user = M('User');
		$m_remind = M('Remind');
	 	$m_receivingorder = M('Receivingorder');
	 	$m_contract = M('Contract');
	 	$m_customer = M('Customer');
	 	$m_receivables = M('Receivables');

		// $where = array();
		$order = "start_date asc,event_id asc";
		$type = $_POST['type'] ? trim($_POST['type']) : '';

		if($_GET['search_date']){
			$timestamp = strtotime($_POST['search_date']);
			$mdays = date('t',$timestamp);
			$start_time = strtotime(date('Y-m-1 00:00:00',$timestamp));
			$end_time = strtotime(date('Y-m-'.$mdays.' 23:59:59',$timestamp));
		}else{
			if($_GET['start'] && $_GET['end']){
				$start_time = trim($_GET['start']);
				$end_time = trim($_GET['end']);
			}else{
				$start_time = strtotime(date('Y-m-01')); 
				$end_time = strtotime(date("Y")."-".date("m")."-".date("t"))+86400;
			}
		}
		$session_role_id = session('role_id');
		// $where['start_date'] = array('between',array($start_time,$end_time));
		// $where['end_date'] = array('between',array($start_time,$end_time));
		// $where['_logic'] = 'or';
		// $map['_complex'] = $where;
		// $map['owner_role_id'] = session('role_id');

		$where = "(`start_date` BETWEEN $start_time AND $end_time AND `owner_role_id` = $session_role_id) OR (`end_date` BETWEEN $start_time AND $end_time AND `owner_role_id` = $session_role_id) OR (`start_date` < $start_time AND `end_date` > $end_time AND `owner_role_id` = $session_role_id)";

		$list = $m_event->where($where)->order($order)->field('event_id,subject,start_date,end_date,color,owner_role_id,module,module_id')->select();
		//追加周期性提醒
		$cycel_event_list = cycel_event($start_time,$end_time);
		if ($cycel_event_list && $list) {
			$list = array_merge($list,$cycel_event_list);
		} else {
			if ($cycel_event_list) {
				$list = $cycel_event_list;
			}
		}

		$new_list = array();
		foreach($list as $k=>$v){
			$new_list[$k]['id'] = $v['event_id'];
			switch ($v['module']) {
				case 'contract' : 
					$contract_name = $m_contract->where(array('contract_id'=>$v['module_id']))->getField('contract_name');
					$subject_info = '【 合同 】'.$contract_name;
					break;
				case 'remind' : 
					$remind_info = $m_remind->where(array('remind_id'=>$v['module_id']))->find();
					$field_name = 'name';
					if ($remind_info['module'] == 'contract') {
						$field_name = 'contract_name';
					}
					$module_name = M($remind_info['module'])->where(array($remind_info['module'].'_id'=>$remind_info['module_id']))->getField($field_name);
					$subject_info = '【 提醒 】'.$module_name;
					break;
				case 'customer' :
					$customer_name = $m_customer->where(array('customer_id'=>$v['module_id']))->getField('name');
					$subject_info = '【 '.$customer_name.' 】';
					break;
				case 'receivables' :
					$receivables_name = $m_receivables->where(array('receivables_id'=>$v['module_id']))->getField('name');
					$subject_info = '【 应收款提醒 】'.$receivables_name;
					break;
				default : 
					$subject_info = cutString($v['subject'],15);
					break;
			}
			$new_list[$k]['title'] = $subject_info;
			$new_list[$k]['start'] = date('Y-m-d H:i:s',$v['start_date']);
			$new_list[$k]['end'] = date('Y-m-d H:i:s',$v['end_date']);
			if(date('H:i:s',$v['start_date']) == '00:00:00' && date('H:i:s',$v['end_date']) == '23:59:59'){
				$new_list[$k]['allDay'] = true;
			}else{
				$new_list[$k]['allDay'] = false;
			}
			$new_list[$k]['color'] = $v['color'];
			$new_list[$k]['module'] = $v['module'];
			$new_list[$k]['module_id'] = $v['module_id'];
		}
		
		if($this->isAjax()){
			if($type == 'save'){
				$this->ajaxReturn($new_list,'',1);
			}else{
				echo $event_list = json_encode($new_list);
			}
		}
	}

	/**
	*日程拖动保存
	*@dayDelta 日程向前或者向后移动了多少天
	*@minuteDelta  这个值只有在agenda视图有效，移动的时间
	*@allDay   如果是月视图，或者是agenda视图的全天日程，此值为true,否则为false
	*@type   1拖动，2缩放
	*
	**/

	public function editable(){
		//权限判断
		$below_ids = getPerByAction('event','index');
		if(!$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}
		$m_event = M('Event');
		$m_cycel = M('Cycel');

		switch (trim($_REQUEST['event_id'])) {
			case 'receivables' : 
				$module_id = intval($_REQUEST['module_id']);
				$cycel_info = $m_cycel->where(array('module'=>'receivables','module_id'=>$module_id))->find();
				$event_info['subject'] = '应收款提醒';
				$event_info['module'] = 'receivables';
				$event_info['module_id'] = $module_id;
				$event_info['t'] = 'receivables';
				$event_info['owner_role_id'] = $cycel_info['create_role_id'];
				break;
			default : 
				$event_id = intval($_REQUEST['event_id']);
				$event_info = $m_event->where('event_id = %d',$event_id)->find();
				break;
		}
		if (empty($event_info)) {
			$this->ajaxReturn('','参数错误！',0);
		} elseif($event_info['owner_role_id'] != session('role_id')) {
			$this->ajaxReturn('','您没有此权利！',0);
		} elseif($event_info['module'] == 'remind' || $event_info['module'] == 'contract' || $event_info['module'] == 'receivables') {
			$this->ajaxReturn('','该类型不支持拖动！',0);
		}
		// if($event_info['isclose'] == 1){
		// 	$this->ajaxReturn('','已经关闭不能编辑！',0);
		// }
		$type = $_POST['type'] ? intval($_POST['type']) : 1;
		if ($this->isPost()) {
			$m_event->create();
			if($_POST['daydiff'] || $_POST['minudiff']){
				if($type == 1){
					//计算时间
					$move_time = $_POST['daydiff']*86400+$_POST['minudiff']*60;
					$m_event->start_date = $event_info['start_date']+$move_time;
					$m_event->end_date = $event_info['end_date']+$move_time;
				}elseif($type == 2){
					//计算时间
					$move_time = $_POST['daydiff']*86400+$_POST['minudiff']*60;
					$m_event->end_date = $event_info['end_date']+$move_time;
				}
			}
			$m_event->update_date = time();
			if ($m_event->save()){
				$this->ajaxReturn('',L('CALENDAR_INFORMATION_MODIFY_SUCCESS'),1);
			}else{
				$this->ajaxReturn('',L('TO_CHANGE_MODIFY_FAILED'),0);
			}
		}
	}
}