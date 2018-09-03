<?php
/**
*任务模块
*
**/
class TaskAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('close', 'revert','analytics','changecontent','getcurrentstatus','open','mycommont','commentshow','myreply','replyalldel','replydel','addtype','archive','edittypeajax','deltypeajax','addsub','subedit','subdel','tasksort','typesort','excelexport')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*增加任务
	*
	**/
	public function add(){		
		if($this->isPost()){
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			if($task = $m_task->create()){
				//任务列表
				if(!$_POST['type_id']){
					$this->ajaxReturn('','请选择所属任务列表',0);
				}
				if(!$_POST['subject']){
					$this->ajaxReturn('',L('NEED_TASK_TITLE'),0);
				}
				$task['subject'] = trim($_POST['subject']);
				$task['create_date'] = time();
				$task['update_date'] = time();
				$task['due_date'] = isset($_POST['due_date']) ? strtotime($_POST['due_date']) : time();
				$task['creator_role_id'] = session('role_id');
				$task['owner_role_id'] = ','.session('role_id').',';
				$task['status'] = '进行中';
				$task['about_roles'] = trim($_POST['about_role_id']);
				//order_id
				$max_order_id = $m_task->where(array('type_id'=>intval($_POST['type_id'])))->max('order_id');
				$task['order_id'] = $max_order_id+1;

				//任务相关(目前只关联客户)
				$task['module'] = trim($_POST['module']);
				$task['module_id'] = intval($_POST['module_id']);
				if($task_id = $m_task->add($task)){
					$task['task_id'] = $task_id;
					actionLog($task_id);
					taskActionLog($task_id,1);
					
					if($_POST['about_role_id']){
						$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
						$about_role_ids = array_filter(explode(',', trim($_POST['about_role_id'])));
						//站内信、邮件
						$message_content = L('MESSAGE_CONTENT' ,array($task_id, $_POST['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'], $_POST['priority'], $_POST['description']));
						// $email_content = L('EMAIL_CONTENT', array($_POST['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$_POST['due_date'] ,$_POST['priority'] , $_POST['description']));
						foreach($about_role_ids as $k=>$v){
							if($v != session('role_id')){
								sendMessage($v,$message_content,1);
								// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
							}
						}
						//操作记录
						taskActionLog($task_id,7,$_POST['about_role_id']);
					}
					$this->ajaxReturn($task,L('SUCCESS_ADD'),1);
				}else{
					$this->ajaxReturn($task,L('ERROR_ADD'),0);
				}
			}else{
				$this->ajaxReturn('',L('ADDING FAILS CONTACT THE ADMINISTRATOR' ,array(L('TASK'))),0);
			}
		}else{
			//任务分类
			$where = array();
			$where['_string'] = 'creator_role_id = "'.session('role_id').'" OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';
			$type_ids = M('Task')->where($where)->group('type_id')->getField('type_id',true);
			$where_type = array();

			$where_type['role_id']  = session('role_id');
			$where_type['id'] = $type_ids ? array('in',$type_ids) : '';
			$where_type['_logic'] = 'or';
			$map['_complex'] = $where_type;
			$map['is_deleted']  = array('neq',1);
			
			$type_list = M('TaskType')->where($map)->order('order_id asc,id asc')->select();
			$this->type_list = $type_list;
			$this->display();
		}
	}

	/**
	*编辑任务ajax
	*
	**/
	public function edit(){
		$task_id = $this->_post('task_id','intval',$this->_get('id','intval'));
		$m_task = M('Task');
		$task_info = $m_task->where('task_id = %d',$task_id)->find();
		if(empty($task_info)){
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}elseif($task_info['is_deleted'] == 1){
			$this->ajaxReturn('','任务已归档，不能编辑！',0);
		}

		//权限判断(创建人、分配人、关注人包含自己的)
		if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		if($this->isPost()){
			$m_task->create();
			if($_POST['due_date']){
				$m_task->due_date = strtotime($_POST['due_date']);
			}
			$m_task->update_date = time();
			$m_task->priority = $_POST['priority'] ? trim($_POST['priority']) : '普通';
			$status = $_POST['status'] ? trim($_POST['status']) : '进行中';
			if($status == '完成'){
				$m_task->finish_date = time();
			}
			if($_POST['about_roles']){
				$about_roles = trim($_POST['about_roles']);
				$m_task->about_roles = $about_roles;
			}
			if($_POST['owner_role_id']){
				$owner_role_id = trim($_POST['owner_role_id']);
				$m_task->owner_role_id = $owner_role_id;
			}
			//任务相关
			$m_task->module = trim($_POST['module']);
			$m_task->module_id = intval($_POST['module_id']);

			//单个人移除
			$remove_role_id = array();
			if($_POST['field'] == 'about_roles' && intval($_POST['remove_role_id'])){
				$remove_role_id[] = intval($_POST['remove_role_id']);
				$about_roles = array_filter(explode(',',$task_info['about_roles']));
				//数组差集
				$about_roles = implode(',',array_diff($about_roles,$remove_role_id));
				$m_task->about_roles = ','.$about_roles.',';
			}
			if($_POST['field'] == 'owner_roles' && intval($_POST['remove_role_id'])){
				$remove_role_id[] = intval($_POST['remove_role_id']);
				$owner_role_id = array_filter(explode(',',$task_info['owner_roles']));
				//数组差集
				$owner_role_id = implode(',',array_diff($owner_role_id,$remove_role_id));
				$m_task->owner_role_id = ','.$owner_role_id.',';
			}
			$is_updated = false;
			if($m_task->where('task_id = %d', $task_id)->save()){
				$is_updated = true;
			}else{
				$this->ajaxReturn('',L('ONLY_STATUS_CAN_CHANGED_MODIFICATION_FAILS'),0);
			}

			if($is_updated){
				actionLog($task_id);
				$params_content = '';
				if($task_info['subject'] != trim($_POST['subject']) && $_POST['subject']){
					$params_content = '名称为:&nbsp;'.trim($_POST['subject']);
				}
				if($task_info['description'] != trim($_POST['description']) && $_POST['description']){
					$params_content = '描述为:&nbsp;'.trim($_POST['description']);
				}
				if($params_content){
					taskActionLog($task_id,2,'',$params_content);
				}
				if($status == '完成' && $task_info['status'] != '完成'){
					taskActionLog($task_id,3,'');
				}
				if($task_info['status'] == '完成' && $status != '完成'){
					taskActionLog($task_id,4,'');
				}

				$new_task_info = $m_task->where(array('task_id'=>$task_id))->find();
				//判断新增分配人、取消分配人
				if($about_roles){
					$old_about_role_ids = array_filter(explode(',', $task_info['about_roles']));
					$new_about_role_ids = array_filter(explode(',', trim($about_roles)));
					//数组并集
					$same_about_role_ids = array_intersect($new_about_role_ids,$old_about_role_ids);
					//新增分配人
					$add_about_role_ids = array();
					//取消分配人
					$sub_about_role_ids = array();
					if($same_about_role_ids){
						$add_about_role_ids = array_diff($new_about_role_ids, $same_about_role_ids);
						$sub_about_role_ids = array_diff($old_about_role_ids, $same_about_role_ids);
					}else{
						$add_about_role_ids = $new_about_role_ids;
						$sub_about_role_ids = $old_about_role_ids;
					}
					//站内信、邮件
					$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
					$message_content = L('MESSAGE_CONTENT' ,array($task_id, $new_task_info['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'],$new_task_info['priority'], $new_task_info['description']));
					$email_content = L('EMAIL_CONTENT', array($new_task_info['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$new_task_info['due_date'] ,$new_task_info['priority'] , $new_task_info['description']));
					foreach($add_about_role_ids as $k=>$v){
						if($v != session('role_id')){
							sendMessage($v,$message_content,1);
							// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
						}
					}
					//操作记录
					if($add_about_role_ids){
						taskActionLog($task_id,7,','.implode(',',$add_about_role_ids).',');
					}
					if($sub_about_role_ids){
						taskActionLog($task_id,8,','.implode(',',$sub_about_role_ids).',');
					}
					
				}

				//判断新增关注人、取消关注人
				if($owner_role_id){
					$old_owner_role_ids = array_filter(explode(',', $task_info['owner_role_id']));
					$new_owner_role_ids = array_filter(explode(',', trim($owner_role_id)));
					//数组并集
					$same_owner_role_ids = array_intersect($new_owner_role_ids,$old_owner_role_ids);
					//新增关注人
					$add_owner_role_ids = array();
					//取消关注人
					$sub_owner_role_ids = array();
					if($same_owner_role_ids){
						$add_owner_role_ids = array_diff($new_owner_role_ids, $same_owner_role_ids);
						$sub_owner_role_ids = array_diff($old_owner_role_ids, $same_owner_role_ids);
					}else{
						$add_owner_role_ids = $new_owner_role_ids;
						$sub_owner_role_ids = $old_owner_role_ids;
					}
					//站内信、邮件
					$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
					$message_content = L('MESSAGE_CONTENT' ,array($task_id, $new_task_info['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'], $new_task_info['priority'], $new_task_info['description']));
					$email_content = L('EMAIL_CONTENT', array($new_task_info['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$new_task_info['due_date'] ,$new_task_info['priority'] , $new_task_info['description']));
					foreach($add_owner_role_ids as $k=>$v){
						if($v != session('role_id')){
							sendMessage($v,$message_content,1);
							// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
						}
					}
					//操作记录
					if($add_owner_role_ids){
						taskActionLog($task_id,9,','.implode(',',$add_owner_role_ids).',');
					}
					if($sub_owner_role_ids){
						taskActionLog($task_id,10,','.implode(',',$sub_owner_role_ids).',');
					}
				}
				$update_task_info = $m_task->where('task_id = %d',$task_id)->find();
				$this->ajaxReturn($update_task_info,L('MODIFY_TASK_SUCCESS'),1);
			}else{
				$this->ajaxReturn('',L('DATA_DID_NOT_CHANGE_MODIFY_FAILED'),1);
			}
		}else{
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}
	}
	
	/**
	*归档任务
	*
	**/
	public function delete(){
		if($this->isAjax()){
			$m_task = M('Task');
			$task_id = $_POST['task_id'] ? intval($_POST['task_id']) : '';
			if(!$task_id){
				$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
			}
			$task_info = $m_task->where(array('task_id'=>$task_id,'is_deleted'=>0))->find();
			if(!$task_info){
				$this->ajaxReturn('','任务不存在或已归档！',0);
			}
			//权限判断(创建人、分配人、关注人包含自己的)
			if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
				$this->ajaxReturn('','您没有此权利！',0);
			}else{
				$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time(),'status'=>'完成','finish_date'=>time());
				if($m_task->where(array('task_id'=>$task_id))->save($data)){
					actionLog($task_id);
					taskActionLog($task_id,5,'');
					$this->ajaxReturn('','归档成功！',1);
				} else {
					$this->ajaxReturn('','归档失败，请重试！',0);
				}
			}
		}
	}
	
	/**
	*激活任务
	*
	**/
	public function revert(){
		$task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : '';
		if ($task_id) {
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			$task_info = $m_task->where('task_id = %d', $task_id)->find();

			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',0);
			} else {
				//相关任务分类是否删除
				$type_info = $m_task_type->where(array('id'=>$task_info['type_id'],'is_deleted'=>0))->find();
				if(!$type_info){
					$res = $m_task_type->where('id = %d',$task_info['type_id'])->setField('is_deleted', 0);
				}
				if ($m_task->where('task_id = %d', $task_id)->setField('is_deleted', 0)) {
					taskActionLog($task_id,6,'');
					$this->ajaxReturn('',L('RESTORE SUCCESSFUL'),1);
				} else {
					$this->ajaxReturn('',L('RESTORE FAILURE'),0);
				}
			}
		} else {
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}
	}

	/**
	*任务列表页面（默认页面）
	*
	**/
	public function index(){
		//更新最后阅读时间
		$m_user = M('user');
		$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
		$last_read_time = json_decode($last_read_time_js, true);
		$last_read_time['task'] = time();
		$m_user->where('role_id = %d', session('role_id'))->setField('last_read_time',json_encode($last_read_time));
		
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$m_task = M('Task');
		$m_task_type = M('TaskType');
		$m_task_sub = M('TaskSub');
		$m_r_task_file = M('RTaskFile');
		$m_task_talk = M('TaskTalk');
		$d_role = D('RoleView');
		$where = array();
		$where_type = array();
		
		$order = "order_id asc,task_id asc";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,true);
		$all_ids = getSubRoleId();
		
		$params = array();
		switch ($by) {
			case 'create' : $where['creator_role_id'] = session('role_id');break;
			case 'own' : $where['_string'] = 'owner_role_id like "%,'.session('role_id').',%"'; break;
			case 'about' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%"'; break;
			default :  $where['_string'] = 'creator_role_id = "'.session('role_id').'" OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';
				break;
		}
		if (!isset($where['isclose'])) {
			$where['isclose'] = 0;
		}
		// if (!isset($where['is_deleted'])) {
		// 	$where['is_deleted'] = 0;
		// }
		// if (!isset($where['status'])) {
		// 	if(!isset($where['isclose'])){
		// 		$where['status'] = array('neq','完成');
		// 	}
		// }
		if (!isset($where['_string'])  && !isset($where['creator_role_id']) && !session('?admin')){
			$where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id = "'.session('role_id').'" ';
		}
		if(session('?admin')){
			unset($where['_string']);
		}
		
		if ($_REQUEST["field"]) {
			$field = trim($_GET['field']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('due_date' == $field || $field == 'update_date' || $field == 'create_date') {
				$search = is_numeric($search) ? $search : strtotime($search);
			}
			switch ($condition) {
				case "is" : if($field == 'owner_role_id'){
								$where[$field] = array('like','%,'.$search.',%');
							}else{
								$where[$field] = array('eq',$search);
							}
							break;
				case "isnot" :  $where[$field] = array('notlike','%,'.$search.',%');break;
				case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
				case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
				case "start_with" :  $where[$field] = array('like',$search.'%');break;
				case "end_with" :  $where[$field] = array('like','%'.$search);break;
				case "is_empty" :  $where[$field] = array('eq','');break;
				case "is_not_empty" :  $where[$field] = array('neq','');break;
				case "gt" :  $where[$field] = array('gt',$search);break;
				case "egt" :  $where[$field] = array('egt',$search);break;
				case "lt" :  $where[$field] = array('lt',$search);break;
				case "elt" :  $where[$field] = array('elt',$search);break;
				case "eq" : $where[$field] = array('eq',$search);break;
				case "neq" : $where[$field] = array('neq',$search);break;
				case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
				case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
				case "tgt" :  $where[$field] = array('gt',$search+86400);break;
				default : $where[$field] = array('eq',$search);
			}
			$params = array('field='.$field, 'condition='.$condition, 'search='.trim($_REQUEST['search']));
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'owner_role_id'){
				if(!in_array(trim($search),$below_ids)){
					$where['owner_role_id'] = array('in',$below_ids);
				}
			}
		}		
		if(trim($_GET['act']) == 'excel'){
			if(!checkPerByAction('task','excelexport')){
				alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
			}else{
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				$task_list = $m_task->order('create_date desc')->where($where)->limit($limit)->select();
				session('export_status', 1);
				$this->excelExport($task_list);
			}
		}
		//过滤相关任务列表
		$type_ids = $m_task->where($where)->group('type_id')->getField('type_id',true);

		if (session('?admin')) {
			$all_ids = M('User')->getField('role_id',true);
			$where_type['role_id'] = array('in',$all_ids);
		} else {
			$where_type['role_id'] = session('role_id');
		}
		if($type_ids){
			$where_type['id'] = array('in',$type_ids);
			$where_type['_logic'] = 'or';
		}
		$map['_complex'] = $where_type;
		$map['is_deleted']  = array('neq',1);

		$type_list = $m_task_type->where($map)->order('order_id asc,id asc')->select();
		foreach($type_list as $k=>$v){
			$task_list = array();
			// $where = array();
			$where['type_id'] = $v['id'];
			$where['is_deleted'] = 0;
			if (!isset($where['creator_role_id']) && !session('?admin')){
				$where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id = "'.session('role_id').'" ';
			}
			if(session('?admin')){
				unset($where['_string']);
			}
			$task_list = $m_task->where($where)->order($order)->select();
			foreach ($task_list as $key=>$value) {
				$task_list[$key]['owner'] = $d_role->where('role.role_id in (%s)', '0'.$task_list[$key]['owner_role_id'].'0')->select();
				$due_time = $task_list[$key]['due_date'];
				if($due_time){
					$tomorrow_time = strtotime(date('Y-m-d', time()))+86400;
					$diff_days = ($due_time-$tomorrow_time)%86400>0 ? intval(($due_time-$tomorrow_time)/86400)+1 : intval(($due_time-$tomorrow_time)/86400);
					$task_list[$key]['diff_days'] = $diff_days;
				}
				//任务相关人
				$about_roles_id = $value['about_roles'] ? array_filter(explode(',',$value['about_roles'])) : '';
				$task_list[$key]['about_roles'] = $d_role->where(array('role.role_id'=>array('in',$about_roles_id)))->select();
				//任务优先级
				switch($value['priority']){
					case '高': $task_list[$key]['priority_class'] = 'danger-element';break; 
					case '普通': $task_list[$key]['priority_class'] = 'success-element';break; 
					case '低': $task_list[$key]['priority_class'] = 'warning-element';break; 
				}
				//子任务进度
				$schedule = 0;
				$sub_task_list = array();
				$sub_task_list = $m_task_sub->where(array('task_id'=>$value['task_id']))->select();
				$sub_count = count($sub_task_list);
				$done_count = 0;
				foreach($sub_task_list as $k2=>$v2){
					if($v2['is_done'] == 1){
						$done_count ++;
					}
				}
				if($sub_task_list){
					if($done_count == 0){
						$schedule = 0;
					}else{
						$schedule = round(($done_count/$sub_count)*100,2);
					}
				}
				$task_list[$key]['sub_count'] = $sub_count;
				$task_list[$key]['done_count'] = $done_count;
				$task_list[$key]['schedule'] = $schedule;
				//附件数
				$file_count = 0;
				$file_count = $m_r_task_file->where('task_id = %d',$value['task_id'])->count();
				$task_list[$key]['file_count'] = $file_count;
				//评论数
				$talk_count = 0;
				$talk_count = $m_task_talk->where('task_id = %d',$value['task_id'])->count();
				$task_list[$key]['talk_count'] = $talk_count;
			}
			$type_list[$k]['task_list'] = $task_list;
		}
		$this->type_list = $type_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*已归档任务列表
	*
	**/
	public function archive(){
		//权限判断
		$below_ids = getPerByAction('task','index');
		if(empty($below_ids) && !session('?admin')){
			alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
		}

		$m_task = M('Task');
		$m_task_sub = M('TaskSub');
		$d_role = D('RoleView');
		$m_user = M('User');

		$where = array();
		$where['is_deleted'] = 1;
		$where['_string'] = 'creator_role_id = "'.session('role_id').'" OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';

		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import("@.ORG.Page");

		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$count = $m_task->where($where)->count();
		$p_num = ceil($count/$listrows);
		if($p_num<$p){
			$p = $p_num;
		}
		$task_list = $m_task->where($where)->order('delete_time desc')->page($p.','.$listrows)->select();
		foreach($task_list as $k=>$v){
			//任务相关人
			$about_roles_id = $v['about_roles'] ? array_filter(explode(',',$v['about_roles'])) : '';
			$task_list[$k]['about_roles'] = $m_user->where(array('role_id'=>array('in',$about_roles_id)))->field('full_name,role_id,thumb_path')->select();

			$task_list[$k]['creator_info'] = $m_user->where('role_id = %d',$v['creator_role_id'])->field('full_name,role_id')->find();
			//子任务进度
			$schedule = 0;
			$sub_task_list = array();
			$sub_task_list = $m_task_sub->where(array('task_id'=>$v['task_id']))->select();
			$sub_count = count($sub_task_list);
			$done_count = 0;
			foreach($sub_task_list as $k2=>$v2){
				if($v2['is_done'] == 1){
					$done_count ++;
				}
			}
			if($sub_task_list){
				if($done_count == 0){
					$schedule = 0;
				}else{
					$schedule = round(($done_count/$sub_count)*100,2);
				}
			}
			$task_list[$k]['schedule'] = $schedule;
		}

		$Page = new Page($count,$listrows);
		$this->parameter = implode('&', $params);
		$Page->parameter = implode('&', $params);
		$show = $Page->show();
		$this->assign('page',$show);
		$this->assign("listrows",$listrows);

		$this->task_list = $task_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*任务详情页面
	*
	**/
	public function view() {
		$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;
		if (0 == $task_id) {
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		} else {
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			$d_role = D('RoleView');
			$m_user = M('User');
			$task = $m_task->where('task_id = %d',$task_id)->find();

			if(!session('?admin') && $task['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task['owner_role_id'])))){
				echo '<div class="alert alert-error">您没有此权利！</div>';die();
			}else{
				$task['owner'] = $m_user->where('role_id in (%s)', '0'.$task['owner_role_id'].'0')->field('role_id,full_name,thumb_path')->select();
				$task['creator'] = getUserByRoleId($task['creator_role_id']);
				$task['about_roles_list'] = $m_user->where('role_id in (%s)', '0'.$task['about_roles'].'0')->field('role_id,full_name,thumb_path')->select();

				$task['week_name'] = getTimeWeek($task['create_date']);
				//任务列表名
				$task['type_name'] = $m_task_type->where(array('id'=>$task['type_id']))->getField('name');
				//子任务
				$task['sub_list'] = M('TaskSub')->where(array('task_id'=>$task['task_id']))->select();

				switch($task['status']){
					case '未启动': $task['status_color'] = '#ed5565';break;
					case '推迟': $task['status_color'] = '#f8ac59';break;
					case '进行中': $task['status_color'] = '#1ab394';break;
					case '完成': $task['status_color'] = '#00aaef';break;
				}
				//附件
				$file_id_array = M('RTaskFile')->where('task_id = %d',$task_id)->getField('file_id',true);
				$task['file_list'] = M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select();
				foreach ($task['file_list'] as $key => $value) {
					$task['file_list'][$key]['size'] = ceil($value['size']/1024);
					$task['file_list'][$key]['pic'] = show_picture($value['name']);
				}
				$task['file_count'] = $file_id_array ? count($file_id_array) : '';

				//活动
				$m_task_action = M('TaskAction');
				$group_date = $m_task_action->where('task_id = %d',$task_id)->group('create_date')->order('action_id desc')->getField('create_date',true);
				$group_list = array();
				foreach($group_date as $key=>$val){
					$action_list = $m_task_action->where(array('create_date'=>$val,'task_id'=>$task_id))->order('action_id desc')->select();
					$group_list[$key]['week_name'] = getTimeWeek(strtotime($val));
					$group_list[$key]['create_date'] = date('m-d',strtotime($val));
					foreach($action_list as $k=>$v){
						$create_role_info = $m_user->where('role_id = %d',$v['role_id'])->field('role_id,full_name,thumb_path')->find();
						$action_list[$k]['create_role_info'] = $create_role_info;
						$role_list = array();
						if($v['about_role_id']){
							$role_ids = explode(',',$v['about_role_id']);
							$role_list = $m_user->where(array('role_id'=>array('in',$role_ids)))->field('role_id,full_name,thumb_path')->select();
						}
						$action_list[$k]['role_list'] = $role_list;
						//logo
						switch($v['type']){
					    	case 1 : $i_class = 'fa fa-square-o'; $color_class = 'ai-blue'; break;
					    	case 2 : $i_class = 'fa fa-square-o'; $color_class = 'ai-yellow'; break;
					    	case 3 : $i_class = 'fa fa-check-square'; $color_class = 'ai-green'; break;
					    	case 4 : $i_class = 'fa fa-square-o'; $color_class = 'ai-red'; break;
					    	case 5 : $i_class = 'fa fa-archive'; $color_class = 'ai-dark-blue'; break;
					    	case 6 : $i_class = 'fa fa-archive'; $color_class = 'ai-red'; break;
					    	case 7 : $i_class = 'fa fa-user'; $color_class = 'ai-green'; break;
					    	case 8 : $i_class = 'fa fa-user'; $color_class = 'ai-red'; break;
					    	case 9 : $i_class = 'fa fa-eye'; $color_class = 'ai-purple'; break;
					    	case 10 : $i_class = 'fa fa-eye'; $color_class = 'ai-red'; break;
					    	case 11 : $i_class = 'fa fa-th-list'; $color_class = 'ai-blue'; break;
					    	case 12 : $i_class = 'fa fa-check-square'; $color_class = 'ai-green'; break;
					    	case 13 : $i_class = 'fa fa-square-o'; $color_class = 'ai-red'; break;
					    	case 14 : $i_class = 'fa fa-share-square-o'; $color_class = 'ai-orange'; break;
					    	case 15 : $i_class = 'fa fa-square-o'; $color_class = 'ai-yellow'; break;
					    }
					    $action_list[$k]['i_class'] = $i_class;
					    $action_list[$k]['color_class'] = $color_class;
					}
					$group_list[$key]['action_list'] = $action_list;
				}
				$task['group_list'] = $group_list;
				//相关(客户)
				$relevant_name = '客户';
				if ($task['module'] && $task['module_id']) {
					switch($task['module']){
						case 'leads' : 
						case 'customer' : 
							$relevant_name = '客户';
							$module_name = M('Customer')->where('customer_id = %d',$task['module_id'])->getField('name');
							break;
						default : 
							$relevant_name = '';
							$module_name = '';
							break;
					}
				}
				$task['relevant_name'] = $relevant_name;
				$task['module_name'] = $module_name ? : '';
				$this->task = $task;
				$this->display();
			}
		}
	}
	
	/**
	*关闭任务
	*
	**/
	// public function close(){
	// 	$id = isset($_GET['id']) ? $_GET['id'] : 0; 
	// 	if ($id >= 0) {
	// 		$m_task = M('task');
	// 		$task = $m_task->where('task_id = %d',$id)->find();
	// 		if ($task['creator_role_id'] == session('user_id') || session('?admin')) {
	// 			if($m_task->where('task_id = %d', $id)->setField('isclose', 1)){
	// 				alert('success', L('CLOSE_SUCCESS'), $_SERVER['HTTP_REFERER']);
	// 			} else {
	// 				alert('error', L('FAIL_TO_CLOSE_TASK'), $_SERVER['HTTP_REFERER']);
	// 			}
	// 		} else {
	// 			alert('error', L('DO NOT HAVE PRIVILEGES'), $_SERVER['HTTP_REFERER']);
	// 		}
	// 	}else{
	// 		alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
	// 	}
	// }
	
	/**
	*开启任务
	*
	**/
	// public function open(){
	// 	$id = isset($_GET['id']) ? $_GET['id'] : 0; 
	// 	if ($id >= 0) {
	// 		$m_task = M('task');
	// 		$task = $m_task->where('task_id = %d',$id)->find();
	// 		if ($task['creator_role_id'] == session('user_id') || session('?admin')) {
	// 			if($m_task->where('task_id = %d', $id)->setField('isclose', 0)){
	// 				alert('success', L('OPEN_SUCCESS'), $_SERVER['HTTP_REFERER']);
	// 			} else {
	// 				alert('error', L('OPEN_FAILURE'), $_SERVER['HTTP_REFERER']);
	// 			}
	// 		} else {
	// 			alert('error', L('DO NOT HAVE PRIVILEGES'), $_SERVER['HTTP_REFERER']);
	// 		}
	// 	}else{
	// 		alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
	// 	}
	// }
	
	/**
	*任务Ajax弹出页面
	*
	**/
	public function listDialog(){
		$m_task = M('task');
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,2);
		//if($this->_permissionRes) $where['owner_role_id'] = array('in', implode(',',$this->_permissionRes));
		$where['_string'] = 'creator_role_id in ('.implode(',', $this->_permissionRes).')  OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';
		$where['is_deleted'] = 0;
		$where['isclose'] = 0;
		$list = $m_task->where($where)->order('due_date desc')->limit('10')->select();
		foreach ($list as $key=>$value) {
			$list[$key]['owner'] = D('RoleView')->where('role.role_id in (%s)', '0'.$value['owner_role_id'].'0')->select();
			$list[$key]['creator'] = getUserByRoleId($value['creator_role_id']);
			$list[$key]['deletor'] = getUserByRoleId($value['delete_role_id']);
			//关联模块
			$r_module = array('Business'=>'RBusinessTask', 'Contacts'=>'RContactsTask', 'Customer'=>'RCustomerTask', 'Product'=>'RProductTask','Leads'=>'RLeadsTask');
			foreach ($r_module as $k=>$v) {
				$r_m = M($v);
				if($module_id = $r_m->where('task_id = %d', $value['task_id'])->getField($k . '_id')){			
					$name = M($k)->where($k.'_id = %d', $module_id)->getField('name');
					$is_deleted = M($k)->where($k.'_id = %d', $module_id)->getField('is_deleted');
					$name_str = msubstr($name,0,20,'utf-8',false);
					$name_str .= $is_deleted == 1 ? '<font color="red">('.L("DELETED").')</font>' : '';
					switch ($k){
						case 'Product' : $module_name= L('PRODUCT'); 
							$name = '<a target="_blank" href="index.php?m=product&a=view&id='.$module_id.'" title="'.$name.'">'.$name_str.'</a>';
							break;
						case 'Leads' : $module_name= L('LEADS'); 
							$name = '<a target="_blank" href="index.php?m=leads&a=view&id='.$module_id.'" title="'.$name.'">'.$name_str.'</a>';
						break;
						case 'Contacts' : $module_name= L('CONTACTS'); 
							$name = '<a target="_blank" href="index.php?m=contacts&a=view&id='.$module_id.'" title="'.$name.'">'.$name_str.'</a>';
						break;
						case 'Business' : $module_name= L('BUSINESS'); 
							$name = '<a target="_blank" href="index.php?m=business&a=view&id='.$module_id.'" title="'.$name.'">'.$name_str.'</a>';
						break;
						case 'Customer' : $module_name= L('CUSTOMER'); 
							$name = '<a target="_blank" href="index.php?m=customer&a=view&id='.$module_id.'" title="'.$name.'">'.$name_str.'</a>';
						break;
					}
					$list[$key]['module']=array('module'=>$k,'module_name'=>$module_name,'name'=>$name,'module_id'=>$module_id);
					break;
				}
			}
		}
		$this->task_list = $list;
		$count = $m_task->where($where)->count();
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		$this->display();
	}
	
	/**
	*导出任务到excel表
	*
	**/
	public function excelExport($taskList=false){
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Task Data");    
		$objProps->setSubject("mxcrm Task Data");    
		$objProps->setDescription("mxcrm Task Data");    
		$objProps->setKeywords("mxcrm Task Data");    
		$objProps->setCategory("Task");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
		$objActSheet->setCellValue('A1', L('THEME'));
		$objActSheet->setCellValue('B1', L('OWNER_ROLE'));
		$objActSheet->setCellValue('C1', L('DEADLINE'));
		$objActSheet->setCellValue('D1', L('STATUS'));
		$objActSheet->setCellValue('E1', L('PRECEDENCE'));
		$objActSheet->setCellValue('F1', L('WHETHER_SEND_EMAIL_NOTIFICATION'));
		$objActSheet->setCellValue('G1', L('CREATOR_ROLE'));
		$objActSheet->setCellValue('H1', L('CREATE_TIME'));
		
		if(is_array($taskList)){
			$list = $taskList;
		}else{
			$where2['status'] = array('neq','完成');
			$m_task = M('task')->where($where2)->select();
			foreach($m_task as $k=>$v){
				$owner_role_id = explode(',',$v['owner_role_id']);
				if(in_array(session('role_id'),$owner_role_id)){
					$task_id_arr[] = $v['task_id'];
				}
			}
			
			$where['task_id'] = array('in',implode(',', $task_id_arr));
			$where['is_deleted'] = 0;
			$list = M('task')->where($where)->select();
		}
	
		$i = 1;
		foreach ($list as $k => $v) {
			$i++;
			$role_id = array_filter(explode(',',$v['owner_role_id']));
			$where1['role_id'] = array('in',$role_id);
			$role_name = M('user') ->where($where1)->getField('name',true);
			$role_name_str = implode(',',$role_name);
			$creator = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			$objActSheet->setCellValue('A'.$i, $v['subject']);
			$objActSheet->setCellValue('B'.$i, $role_name_str);
			$v['due_date'] == 0 || strlen($v['due_date']) != 10 ? $objActSheet->setCellValue('C'.$i, '') : $objActSheet->setCellValue('C'.$i, date("Y-m-d H:i:s", $v['due_date']));
			$objActSheet->setCellValue('D'.$i, $v['status']);
			$objActSheet->setCellValue('E'.$i, $v['priority']);
			$v['send_email'] == 0 ? $objActSheet->setCellValue('F'.$i, L('NO')) : $objActSheet->setCellValue('F'.$i, L('YES'));
			$objActSheet->setCellValue('G'.$i, $creator['user_name'].'['.$creator['department_name'].'-'.$creator['role_name'].']');
			$objActSheet->setCellValue('H'.$i, date("Y-m-d H:i:s", $v['create_date']));
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_task_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
		session('export_status', 0);
	}
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
	}
	
	/**
	*从excel表导入到任务
	*
	**/
	public function excelImport(){
		$m_task = M('task');
		if($this->isPost()){
			if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('xls');
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error', L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'), U('task/index'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					alert('error', $upload->getErrorMsg(), U('task/index'));
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}
			if(is_array($info[0]) && !empty($info[0])){
				$savePath = $dirname . $info[0]['savename'];
			}else{
				alert('error', L('UPLOAD FAILED'), U('task/index'));
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
				$data['owner_role_id'] = $currentSheet->getCell('E'.$currentRow)->getValue();
				$data['due_date'] = strtotime($currentSheet->getCell('G'.$currentRow)->getValue());
				$data['status'] = $currentSheet->getCell('H'.$currentRow)->getValue();
				$data['priority'] = $currentSheet->getCell('I'.$currentRow)->getValue();
				$data['send_email'] = $currentSheet->getCell('J'.$currentRow)->getValue();
				$data['description'] = $currentSheet->getCell('K'.$currentRow)->getValue();
				$data['creator_role_id'] = $currentSheet->getCell('N'.$currentRow)->getValue();
				$data['create_time'] = strtotime($currentSheet->getCell('P'.$currentRow)->getValue());
				$data['update_time'] = strtotime($currentSheet->getCell('Q'.$currentRow)->getValue());
				if(!$m_task->add($data)) {
					if($this->_post('error_handing','intval',0) == 0){
							alert('error', L('ERROR INTRODUCED INTO THE LINE', array($currentRow, $m_task->getError())), U('task/index'));
						}else{
							$error_message .= L('LINE ERROR' ,array($currentRow , $m_task->getError()));
							$m_task->clearError();
						}
					break;
				}
			}
			alert('success', $error_message .L('IMPORT SUCCESS'), U('task/index'));
		}else{
			$this->display();
		}
	}
	
	/**
	*任务统计
	*
	**/
	public function analytics(){
		$m_task = M('Task');
		if($_GET['role']) {
			$role_id = intval($_GET['role']);
		}else{
			$role_id = 'all';
		}
		if($_GET['department'] && $_GET['department'] != 'all'){
			$department_id = intval($_GET['department']);
		}else{
			$department_id = D('RoleView')->where('role.role_id = %d', session('role_id'))->getField('department_id');
		}
		if($_GET['start_time']) $start_time = strtotime($_GET['start_time']);
		$end_time = $_GET['end_time'] ?  strtotime($_GET['end_time']) : time();
		$where_completion['is_deleted'] = 0;
		$where_completion['isclose'] = 0;
		if($start_time){
			$where_create_time = array(array('lt',$end_time),array('gt',$start_time), 'and');
			$where_completion['create_time'] = $where_create_time;
		}else{
			$where_completion['create_time'] = array('lt',$end_time);
		}
		if($role_id == "all") {
			$roleList = getRoleByDepartmentId($department_id);
			$role_id_array = array();
			foreach($roleList as $v){
				$role_id_array[] = '%,'.$v['role_id'].',%';
			}
			$where_completion['owner_role_id'] = array('like',$role_id_array,'or');
		}else{
			$where_completion['owner_role_id'] = array('like','%,'.$role_id.',%');
		}
		
		
		$completion_count_array = array();
		$statusList = array(L('NOT_START'), L('DELAY'), L('ONGOING'), L('COMPLETE'));
		
		foreach($statusList as $v){
			$where_completion['status'] = $v;
			$target_count = $m_task ->where($where_completion)->count();
			$completion_count_array[] = '['.'"'.$v.'",'.$target_count.']';
		}
		$this->completion_count = implode(',', $completion_count_array);
		$role_id_array = array();
		if($role_id == "all"){
			if($department_id != "all"){
				$roleList = getRoleByDepartmentId($department_id);
				foreach($roleList as $v){
					$role_id_array[] = $v['role_id'];
				}
			}else{
				$role_id_array = getSubRoleId();
			}
		}else{
			$role_id_array[] = $role_id;
		}
		if($start_time){
			$create_time= array(array('lt',$end_time),array('gt',$start_time), 'and');
		}else{
			$create_time = array('lt',$end_time);
		}
		
		$own_count_total = 0;
		$new_count_total = 0;
		$late_count_total = 0;
		$deal_count_total = 0;
		$success_count_total = 0;
		foreach($role_id_array as $v){
			$user = getUserByRoleId($v);
			$owner_role_id = array('like', '%,'.$v.',%');
			$own_count = $m_task->where(array('is_deleted'=>0,'isclose'=>0, 'owner_role_id'=>$owner_role_id, 'create_date'=>$create_time))->count();
			$new_count = $m_task->where(array('is_deleted'=>0,'isclose'=>0, 'status'=>L('NOT_START'),'owner_role_id'=>$owner_role_id, 'create_date'=>$create_time))->count();
			$late_count = $m_task->where(array('is_deleted'=>0,'isclose'=>0,'status'=>L('DELAY'), 'owner_role_id'=>$owner_role_id, 'create_date'=>$create_time))->count();
			$deal_count = $m_task->where(array('is_deleted'=>0,'isclose'=>0,'status'=>L('ONGOING'), 'owner_role_id'=>$owner_role_id, 'create_date'=>$create_time))->count();
			$success_count =  $m_task->where(array('is_deleted'=>0,'isclose'=>0,'status'=>L('COMPLETE'), 'owner_role_id'=>$owner_role_id, 'create_date'=>$create_time))->count();
			
			$reportList[] = array("user"=>$user,"new_count"=>$new_count,"late_count"=>$late_count,"own_count"=>$own_count,"success_count"=>$success_count,"deal_count"=>$deal_count);
			$late_count_total += $late_count;
			$own_count_total += $own_count;
			$success_count_total += $success_count;
			$deal_count_total += $deal_count;
			$new_count_total += $new_count;
		}
		$this->total_report = array("new_count"=>$new_count_total,"late_count"=>$late_count_total, "own_count"=>$own_count_total, "success_count"=>$success_count_total, "deal_count"=>$deal_count_total);
		$this->reportList = $reportList;
		
		$idArray = getSubRoleId();
		$roleList = array();
		foreach($idArray as $roleId){
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		
		$departments = M('roleDepartment')->select();
		$department_id = D('RoleView')->where('role.role_id = %d', session('role_id'))->getField('department_id');
		$departmentList[] = M('roleDepartment')->where('department_id = %d', $department_id)->find();$departmentList = array_merge($departmentList, getSubDepartment($department_id,$departments,''));
		$this->assign('departmentList', $departmentList);
		$this->display();
	}

	/**
	*增加任务列表
	*
	**/
	public function addtype(){
		//权限判断
		$below_ids = getPerByAction('task','add');
		if(!session('?admin') && !$below_ids){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}

		if($this->isPost()){
			$m_task_type = M('TaskType');
			$max_order_id = $m_task_type->max('order_id');
			if($_POST['name']){
				$data = array();
				$data['name'] = trim($_POST['name']);
				$data['role_id'] = session('role_id');
				$data['create_time'] = time();
				$data['update_time'] = time();
				$data['order_id'] = $max_order_id+1;
				if($task_id = $m_task_type->add($data)){
					$task_data['id'] = $task_id;
					$task_data['name'] = $data['name'];
					$this->ajaxReturn($task_data,'创建成功！',1);
				}else{
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','请填写列表名称！',0);
			}
		}else{
			$this->display();
		}
	}

	/**
	*编辑任务列表
	*
	**/
	public function editTypeAjax(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if(!session('?admin') && !$below_ids){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}
		if($this->isAjax()){
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : '';
			if(!$type_id){
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_task_type = M('TaskType');
			if($_POST['name']){
				$data = array();
				$data['name'] = trim($_POST['name']);
				$data['update_time'] = time();
				if($m_task_type->where(array('id'=>$type_id))->save($data)){
					$this->ajaxReturn($data,'修改成功！',1);
				}else{
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','请填写列表名称！',0);
			}
		}
	}

	/**
	*删除（归档）任务列表
	*
	**/
	public function delTypeAjax(){
		//权限判断
		$below_ids = getPerByAction('task','delete');
		if(!session('?admin') && !$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		if($this->isAjax()){
			$m_task_type = M('TaskType');
			$m_task = M('Task');
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : '';
			if(!$type_id){
				$this->ajaxReturn('','参数错误！',0);
			}
			$data = array();
			$data['is_deleted'] = 1;
			$data['del_role_id'] = session('role_id');
			//判断权限(任务列表下的所有任务是否都包含自己)
			if (!session('?admin')) {
				$task_ids = $m_task->where(array('type_id'=>$type_id))->getField('task_id',true);
				if ($task_ids) {
					$where_pre = array();
					$where_pre['task_id'] = array('in',$task_ids);
					$where_pre['creator_role_id'] = array('neq',session('role_id'));
					$where_pre['about_roles'] = array('notlike','%,'.session('role_id').',%');
					$where_pre['owner_role_id'] = array('notlike','%,'.session('role_id').',%');
					$task_pre = $m_task->where($where_pre)->find();
					if ($task_pre) {
						$this->ajaxReturn('','此列表下包含其他您未参与的任务，如需删除请联系管理员！',0);
					}
				}
			}
			//判断任务列表下任务是否都已完成

			if($m_task_type->where(array('id'=>$type_id))->save($data)){
				$data_task = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time(),'status'=>'完成','finish_date'=>time());
				M('Task')->where('type_id = %d',$type_id)->save($data_task);
				$this->ajaxReturn($data,'任务归档成功！',1);
			}else{
				$this->ajaxReturn('','数据已归档，请勿重复操作！',0);
			}
		}
	}

	//添加子任务
	public function addSub(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if(!session('?admin') && !$below_ids){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}

		if($this->isAjax()){
			$task_id = $_POST['task_id'] ? intval($_POST['task_id']) : '';
			$content = $_POST['content'] ? trim($_POST['content']) : '';
			if(!$task_id || !$content){
				$this->ajaxReturn('','参数错误！',0);
			}
			$task_info = M('Task')->where('task_id = %d',$task_id)->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
				$this->ajaxReturn('','您没有此权利！',0);
			}

			$m_task_sub = M('TaskSub');
			if($m_task_sub->create()){
				$m_task_sub->create_role_id = session('role_id');
				$m_task_sub->create_time = time();
				$m_task_sub->update_time = time();
				$data['content'] = $content;
				if($sub_id = $m_task_sub->add()){
					$data['sub_id'] = $sub_id;
					taskActionLog($task_id,11,'',trim($_POST['content']));
					$this->ajaxReturn($data,'创建成功！',1);
				}else{
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','创建失败，请重试！',0);
			}
		}
	}

	/**
	 * 最初的评论(回复)
	 * @return [type] [description]
	 */
	public function myCommont(){
		if($this->isAjax()){
			$task_id = $this->_post('task_id','intval');
			// $send_role_id = $this->_post('send_role_id','intval');
			$send_role_id = session('role_id');
			$content = $this->_post('content','trim');
			if(!$task_id) $this->ajaxReturn('','当前日志发生跑路现象，暂不支持回复！',3);
			if(!$send_role_id) $this->ajaxReturn('','回复者处于隐身状态哦！别闹！',4);
			if(!$content) $this->ajaxReturn('','回复内容必填哦！',5);
			$m_task = M('Task');//任务表
			$task_info = $m_task->where('task_id = %d',$task_id)->find();

			$receive_role_id = $task_info['creator_role_id'];//接收者role_id

			$receive_role_ids = array();
			$owner_role_ids = array_filter(explode(',',$task_info['owner_role_id']));
			$about_roles = array_filter(explode(',',$task_info['about_roles']));
			if($owner_role_ids && $about_roles){
				$receive_role_ids = array_merge($owner_role_ids,$about_roles);
			}else{
				if($owner_role_ids){
					$receive_role_ids = $owner_role_ids;
				}elseif($about_roles){
					$receive_role_ids = $about_roles;
				}
			}

			if(!$receive_role_id){
				$this->ajaxReturn('','该任务不存在或已删除！',6);
			}
			$data['task_id'] = $task_id;
			$data['send_role_id'] = $send_role_id;
			$data['receive_role_id'] = $receive_role_id;//接收者role_id
			$data['content'] = $content;
			$data['create_time'] = time();
			$m_task_talk = M('TaskTalk');//日志评论回复表
			$talk_id = $m_task_talk->add($data);
			if($talk_id){
				$sendor = getUserByRoleId($send_role_id);
				$message_content = '<a class="task_view" href="javascript:void(0);" rel="'.$task_id.'">'.$sendor['user_name'].' 评论了你的任务</a>';
				foreach($receive_role_ids as $k=>$v){
					//发送站内信
					sendMessage($v,$message_content,1);
				}
				$g_mark = 'wk_'.$talk_id;
				$m_task_talk->where('talk_id = %d',$talk_id)->save(array('g_mark'=>$g_mark));
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','发表失败，程序员正在火速检修！',6);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	//(评论/回复)显示
	public function commentShow(){
		$task_id = $this->_request('task_id','intval');
		$m_task_talk = M('TaskTalk');//日志评论回复表
		$m_user = M('User');
		$m_task = M('Task');
		$task_info = $m_task->where('task_id = %d',$task_id)->find();

		//权限判断(创建人、分配人、关注人包含自己的)
		if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
			echo '<div class="alert alert-error">您没有此权利！</div>';die();
		}

		$comment_list = $m_task_talk->where(array('task_id'=>$task_id,'parent_id'=>0))->order('create_time asc')->select();
		foreach ($comment_list as $key => $value) {
			$creator_info = array();
			$creator_info = $m_user->where('role_id = %d',$value['send_role_id'])->field('thumb_path,role_id,name,full_name')->find();
			$comment_list[$key]['img'] = $creator_info['thumb_path'];
			$comment_list[$key]['creator'] = $creator_info;
			$comment_list[$key]['content'] = htmlspecialchars_decode($value['content']);
			
			//是否有删除回复权限
			$comment_list[$key]['delete'] = 0;
			if(session('?admin') || $value['send_role_id'] == session('role_id') || $task_info['creator_role_id'] == session('role_id')){
				$comment_list[$key]['delete'] = 1;
			}
			$comment_list_child = $m_task_talk->where('parent_id =%d and g_mark = "%s"',$value['talk_id'],$value['g_mark'])->select();
			foreach ($comment_list_child as $childkey => $childvalue) {
				$creator_child_info = array();
				$creator_child_info = $m_user->where('role_id = %d',$childvalue['send_role_id'])->field('thumb_path,role_id,name,full_name')->find();
				$comment_list_child[$childkey]['childimg'] = $creator_child_info['thumb_path'];
				$comment_list_child[$childkey]['creator_child'] = $creator_child_info;
				$comment_list_child[$childkey]['content'] = htmlspecialchars_decode($childvalue['content']);
				//是否有删除回复权限
				$comment_list_child[$childkey]['delete'] = 0;
				if(session('?admin') || $childvalue['send_role_id'] == session('role_id') || $task_info['creator_role_id'] == session('role_id')){
					$comment_list_child[$childkey]['delete'] = 1;
				}
			}
			$comment_list[$key]['comment_list_child'] = $comment_list_child;
		}
		$this->current_img = $current_img = M('User')->where('role_id = %d',session('role_id'))->getField('thumb_path');//当前头像	
		$this->assign('comment_list',$comment_list);
		$this->assign('task_id',$task_id);
		$this->display();
	}	
	//添加回复
	public function myReply(){
		if($this->isAjax()){
			$talk_id = $this->_post('talk_id','intval');
			$receive_role_id = $this->_post('receiveid','intval');
			$content = $this->_post('content','trim');			
			if(!$talk_id) $this->ajaxReturn('','当前回复发生跑路现象，暂不支持回复！',3);
			if(!$receive_role_id) $this->ajaxReturn('','当前回复对象发生跑路现象，暂不支持回复！',6);
			if(!$content) $this->ajaxReturn('','回复内容必填哦！',4);
			$m_task_talk = M('TaskTalk');//任务评论回复表
			$talk_info = $m_task_talk->where('talk_id = %d',$talk_id)->find();
			$rep_data['parent_id'] = $talk_info['parent_id'] ? $talk_info['parent_id'] : $talk_id;
			$rep_data['task_id'] = $talk_info['task_id'];
			$rep_data['send_role_id'] = session('role_id');
			$rep_data['receive_role_id'] = $receive_role_id;
			$rep_data['content'] = $content;
			$rep_data['create_time'] = time();
			$rep_data['g_mark'] = $talk_info['g_mark'];
			$talk_id = $m_task_talk->add($rep_data);
			if($talk_id){
				$sendor = getUserByRoleId(session('role_id'));
				$message_content = '<a class="task_view" rel="'.$talk_info['task_id'].'" href="javascript:void(0);">'.$sendor['user_name'].' 回复了你的评论</a>';
				sendMessage($receive_role_id,$message_content,1);
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','发表失败，程序员正在火速检修！',5);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	/**
	 * 评论(回复) 删除
	 * @return [type] true or false
	 * 1全部删除 包括回复的所有 replyAllDel() 2删除单一的回复 replyDel()
	 * 便于后续的其他操作 分开写方法
	 * replyAllDel()   replyDel()
	 */
	public function replyAllDel(){
		if($this->isAjax()){
			$talk_id = $this->_post('id','intval');
			if(!$talk_id) $this->ajaxReturn('','当前被删除项发生跑路现象，暂不支持此操作！',3);
			$m_task_talk = M('TaskTalk');//任务评论回复表
			$talkinfo = $m_task_talk->where('talk_id = %d',$talk_id)->find();
			$role_id = M('Task')->where('log_id = %d',$talkinfo['task_id'])->getField('creator_role_id');
			if($talkinfo){
				if($talkinfo['send_role_id'] != session('role_id') && $role_id != session('role_id') && !session('?admin')){
					$this->ajaxReturn('','sorry,您没有权限删除！',6);
				}else{
					$msg = $m_task_talk->where('g_mark = "%s"',$talkinfo['g_mark'])->delete();
					if($msg){
						$this->ajaxReturn('','success',1);
					}else{
						$this->ajaxReturn('','删除失败，程序员正在火速检修！',5);
					}	
				}							
			}else{
				$this->ajaxReturn('','数据查询失败！',4);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	public function replyDel(){
		if($this->isAjax()){			
			$talk_id = $this->_post('id','intval');
			if(!$talk_id) $this->ajaxReturn('','当前被删除项发生跑路现象，暂不支持此操作！',3);
			$m_task_talk = M('TaskTalk');//任务评论回复表
			$send_role_id = $m_task_talk->where('talk_id = %d',$talk_id)->getField('send_role_id');
			$task_id = $m_task_talk->where('talk_id = %d',$talk_id)->getField('task_id');
			$role_id = M('Task')->where('task_id = %d',$task_id)->getField('creator_role_id');
			if($send_role_id != session('role_id') && $role_id != session('role_id') && !session('?admin')){
				$this->ajaxReturn('','sorry,您没有权限删除！',5);		
			}else{
				$msg = $m_task_talk->where('talk_id = %d',$talk_id)->delete();
				if($msg){
					$this->ajaxReturn('','success',1);
				}else{
					$this->ajaxReturn('','删除失败，程序员正在火速检修！',4);
				}
			}

		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}

	/**
	 * 子任务修改
	 */
	public function subEdit(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if(!session('?admin') && !$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		if($this->isAjax()){
			$m_task_sub = M('TaskSub');
			$sub_id = $_POST['sub_id'] ? intval($_POST['sub_id']) : '';
			if(!$sub_id){
				$this->ajaxReturn('','参数错误！',0);
			}
			$sub_info = $m_task_sub->where(array('id'=>$sub_id))->find();

			$task_info = M('Task')->where('task_id = %d',$sub_info['task_id'])->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
				$this->ajaxReturn('','您没有此权利！',0);
			}

			$is_done = $_POST['is_done'] ? intval($_POST['is_done']) : 0;
			$content = $_POST['content'] ? trim($_POST['content']) : '';
			$m_task_sub->update_time = time();
			if($is_done){
				if($sub_info['is_done']){
					$m_task_sub->is_done = 0;
				}else{
					$m_task_sub->is_done = 1;
				}				
				$m_task_sub->done_role_id = session('role_id');
				
				if($m_task_sub->save()){
					if($sub_info['is_done']){
						taskActionLog($sub_info['task_id'],13,'',$sub_info['content']);
					}else{
						taskActionLog($sub_info['task_id'],12,'',$sub_info['content']);
					}
					$data = array();
					$done_count = $m_task_sub->where(array('id'=>$sub_id,'is_done'=>1))->count();
					$count = $m_task_sub->where(array('id'=>$sub_id))->count();
					$data['done_pro'] = $done_count.'/'.$count;
					$this->ajaxReturn($data,'修改成功！',1);
				}else{
					$this->ajaxReturn('','数据无变化！',0);
				}
			} elseif ($content) {
				$m_task_sub->content = $content;
				$data = array();
				$data['content'] = $content;
				if($m_task_sub->save()){
					$this->ajaxReturn($data,'修改成功！',1);
				}
			}
		}
	}

	/**
	 * 子任务删除
	 */
	public function subDel(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if(!session('?admin') && !$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		if($this->isAjax()){
			$m_task_sub = M('TaskSub');
			$sub_id = $_POST['sub_id'] ? intval($_POST['sub_id']) : '';
			if(!$sub_id){
				$this->ajaxReturn('','参数错误！',0);
			}
			$sub_info = $m_task_sub->where(array('id'=>$sub_id))->find();

			$task_info = M('Task')->where('task_id = %d',$sub_info['task_id'])->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
				$this->ajaxReturn('','您没有此权利！',0);
			}

			if($sub_info){
				if($m_task_sub->where(array('id'=>$sub_id))->delete()){
					$this->ajaxReturn('','删除成功！',1);
				}else{
					$this->ajaxReturn('','删除失败！',0);
				}
			}else{
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
		}
	}

	/**
	*  任务排序
	*
	**/
	public function taskSort(){
		//权限判断
		$below_ids = getPerByAction('task','index');
		if(!session('?admin') && !$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		$m_task = M('Task');
		$m_task_type = M('TaskType');

		//判断类别是否已删除
		$type_id = $_GET['type_id'] ? intval($_GET['type_id']) : '';
		$postion = $_GET['postion'] ? explode(',', $_GET['postion']) : array();
		if($type_id){
			$type_info = $m_task_type->where(array('id'=>$type_id,'is_deleted'=>'0'))->find();
			if(!$type_info){
				$this->ajaxReturn('0', '参数错误！', 0);
			}
		}else{
			$this->ajaxReturn('0', '参数错误！', 0);
		}
		if(isset($_GET['postion']) && $type_id){
			//获取原列表任务id
			$old_task_ids = $m_task->where(array('type_id'=>$type_id,'is_deleted'=>0))->getField('task_id',true);
			$new_task_ids = explode(',', trim($_POST['position']));
			//数组差集(获取移动的任务ID)
			$diff_task_id = array_diff($old_task_ids, $new_task_ids);
			if($diff_task_id && !is_array($diff_task_id)){
				$old_type_id = $m_task->where('task_id = %d',$diff_task_id)->getField('type_id');
				$old_type_name = $m_task_type->where('id = %d',$old_type_id)->getField('name');
				$new_type_name = $type_info['name'];
				//操作记录
				taskActionLog($diff_task_id,14,'','&nbsp;'.$old_type_name.'移动任务到列表'.$new_type_name);
			}

			foreach($postion as $k=>$v) {
				$data = array('task_id'=> $v, 'order_id'=>$k ,'type_id'=>$type_id);
				$m_task->save($data);
			}
			$this->ajaxReturn('', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('', L('EDIT FAILED'), 0);
		}
	}

	/**
	*  任务列表排序
	*
	**/
	public function typeSort(){
		//权限判断
		$below_ids = getPerByAction('task','index');
		if(!session('?admin') && !$below_ids){
			$this->ajaxReturn('','您没有此权利！',0);
		}

		if(isset($_GET['postion'])){
			$m_task_type = M('TaskType');
			foreach(explode(',', $_GET['postion']) AS $k=>$v) {
				$data = array('id'=> $v, 'order_id'=>$k);
				$m_task_type->save($data);
			}
			$this->ajaxReturn('', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('', L('EDIT FAILED'), 0);
		}
	}

	/**
	 * 任务删除
	 * @param 
	 * @author 
	 * @return 
	**/
	public function del(){
		if ($this->isPost()) {
			$m_task = M('Task');
			$task_id = $_POST['task_id'] ? intval($_POST['task_id']) : '';
			if (!$task_id) {
				$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
			}
			$task_info = $m_task->where(array('task_id'=>$task_id))->find();
			if (!$task_info) {
				$this->ajaxReturn('','任务不存在或已删除！',0);
			}
			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',0);
			} else {
				if ($m_task->where(array('task_id'=>$task_id))->delete()) {
					actionLog($task_id);
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败，请重试！',0);
				}
			}
		}
	}
}