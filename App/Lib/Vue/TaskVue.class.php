<?php
/**
 *任务相关
 **/
class TaskVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('typelist','addtype','edittypeajax','deltypeajax','revert','addsub','subedit','subdel','comment_add','replydel','order')
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
	 * 任务项目列表
	 * @param 
	 * @author 
	 * @return
	 */
	public function typeList() {
		if ($this->isPost()) {
			//获取添加权限
			$permission_list = apppermission('task','index');
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}

			$by = isset($_POST['by']) ? trim($_POST['by']) : '';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			$d_role = D('RoleView');
			$where = array();
			$where_type = array();
			
			$below_ids = getPerByAction('task','index',true);
			$all_ids = getSubRoleId();
			
			$params = array();
			switch ($by) {
				case 'create' : $where['creator_role_id'] = session('role_id');break;
				case 'about' : $where['_string'] = 'owner_role_id like "%,'.session('role_id').',%"'; break; //我负责的
				case 'own' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%"'; break; //我关注的
				default :  $where['_string'] = 'creator_role_id = "'.session('role_id').'" OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';
					break;
			}
			if (!isset($where['isclose'])) {
				$where['isclose'] = 0;
			}
			if (!isset($where['_string'])  && !isset($where['creator_role_id']) && !session('?admin')){
				$where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id = "'.session('role_id').'" ';
			}
			if (session('?admin')) {
				unset($where['_string']);
			}
			
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if ('due_date' == $field || $field == 'update_date' || $field == 'create_date') {
					$search = is_numeric($search)?$search:strtotime($search);
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
				//过滤不在权限范围内的role_id
				if (trim($_REQUEST['field']) == 'owner_role_id') {
					if (!in_array(trim($search),$below_ids)) {
						$where['owner_role_id'] = array('in',$below_ids);
					}
				}
				if ($field == 'type_name') {
					$map['name'] = $where['type_name'];
					unset($where['type_name']);
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
			if ($type_ids) {
				$where_type['id'] = array('in',$type_ids);
				$where_type['_logic'] = 'or';
			}
			$map['_complex'] = $where_type;
			$map['is_deleted']  = array('neq',1);
			$type_list = $m_task_type->where($map)->field('id,name,create_time')->page($p.',10')->order('order_id asc,id asc')->select();
			$count = $m_task_type->where($map)->count();
			foreach ($type_list as $k=>$v) {
				//类型下相关任务
				$sum_count = 0;
				$done_count = 0;
				$where = array();
				$where['type_id'] = $v['id'];
				$where['is_deleted'] = 0;
				if (!session('?admin')) {
					$where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id = "'.session('role_id').'" ';
				}
				//总任务数(我相关的)
				$sum_count = $m_task->where($where)->count();
				//已完成任务数(我相关的)
				$where['status'] = '完成';
				$done_count = $m_task->where($where)->count();

				$type_list[$k]['sum_count'] = $sum_count ? $sum_count : 0;
				$type_list[$k]['done_count'] = $done_count ? $done_count : 0;
			}
			$page = ceil($count/10);
			$data['list'] = $type_list ? $type_list : array();
			$data['page'] = $page;
			$data['info'] = '查询成功'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 任务列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			//获取添加权限
			$permission_list = apppermission('task','index');
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			$where = array();
			
			$by = isset($_POST['by']) ? trim($_POST['by']) : '';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : 0;
			if (!$type_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$where['type_id'] = $type_id;

			$status = trim($_POST['status']) ? : '完成';
			if ($status == '完成') {
				$where['status'] = '完成';
			} else {
				$where['status'] = array('neq','完成');
			}

			$m_task = M('Task');
			$m_task_sub = M('TaskSub');
			$m_r_task_file = M('RTaskFile');
			$m_task_talk = M('TaskTalk');
			$d_role = D('RoleView');
			$m_user = M('User');
			
			$order = "order_id asc,task_id asc";
			if ($_POST['desc_order']) {
				$order = trim($_POST['desc_order']).' desc';
			} elseif ($_POST['asc_order']) {
				$order = trim($_POST['asc_order']).' asc';
			}
			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,true);
			$all_ids = getSubRoleId();
			
			$params = array();
			switch ($by) {
				case 'create' : $where['creator_role_id'] = session('role_id');break;
				case 'own' : $where['_string'] = 'owner_role_id like "%,'.session('role_id').',%"'; break; //我负责的
				case 'about' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%"'; break; //我关注的
				default :  $where['_string'] = 'creator_role_id = "'.session('role_id').'" OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"';
					break;
			}
			if (!isset($where['isclose'])) {
				$where['isclose'] = 0;
			}
			if (!isset($where['_string'])  && !isset($where['creator_role_id']) && !session('?admin')){
				$where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id = "'.session('role_id').'" ';
			}
			if (session('?admin')) {
				unset($where['_string']);
			}
			
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if ('due_date' == $field || $field == 'update_date' || $field == 'create_date') {
					$search = is_numeric($search)?$search:strtotime($search);
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
				//过滤不在权限范围内的role_id
				if(trim($_REQUEST['field']) == 'owner_role_id'){
					if(!in_array(trim($search),$below_ids)){
						$where['owner_role_id'] = array('in',$below_ids);
					}
				}
				//去除完成条件
				unset($where['status']);
			}
			$where['is_deleted'] = 0;
			$task_list = $m_task->where($where)->page($p.',10')->order($order)->select();
			$count = $m_task->where($where)->count();

			foreach ($task_list as $key=>$value) {
				//任务相关人
				$about_roles_id = $value['about_roles'] ? array_filter(explode(',',$value['about_roles'])) : array();
				$task_list[$key]['about_roles_list'] = $about_roles_id ? $m_user->where(array('role_id'=>array('in',$about_roles_id)))->field('full_name,role_id,thumb_path')->select() : array();
				//子任务进度
				$schedule = 0;
				$sub_task_list = array();
				$sub_task_list = $m_task_sub->where(array('task_id'=>$value['task_id']))->select();
				$sub_count = count($sub_task_list);
				$done_count = 0;
				foreach ($sub_task_list as $k2=>$v2) {
					if ($v2['is_done'] == 1) {
						$done_count ++;
					}
				}
				if ($sub_task_list) {
					if ($done_count == 0) {
						$schedule = 0;
					} else {
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

			$page = ceil($count/10);
			$data['list'] = $task_list ? : array();
			$data['page'] = $page;
			$data['info'] = '查询成功！'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');	
		}
	}

	/**
	 * 任务添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			if ($task = $m_task->create()) {
				//任务列表
				if (!$_POST['type_id']) {
					$this->ajaxReturn('','请选择所属任务列表',0);
				}
				if (!$_POST['subject']) {
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
				if ($task_id = $m_task->add($task)) {
					$task['task_id'] = $task_id;
					actionLog($task_id);
					taskActionLog($task_id,1);
					
					if ($_POST['about_role_id']) {
						$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
						$about_role_ids = array_filter(explode(',', trim($_POST['about_role_id'])));
						//站内信、邮件
						$message_content = L('MESSAGE_CONTENT' ,array($task_id, $_POST['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'], $_POST['priority'], $_POST['description']));
						// $email_content = L('EMAIL_CONTENT', array($_POST['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$_POST['due_date'] ,$_POST['priority'] , $_POST['description']));
						foreach ($about_role_ids as $k=>$v) {
							if ($v != session('role_id')) {
								sendMessage($v,$message_content,1);
								// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
							}
						}
						//操作记录
						taskActionLog($task_id,7,$_POST['about_role_id']);
					}
					$this->ajaxReturn('',L('SUCCESS_ADD'),1);
				} else {
					$this->ajaxReturn('',L('ERROR_ADD'),0);
				}
			} else {
				$this->ajaxReturn('',L('ADDING FAILS CONTACT THE ADMINISTRATOR' ,array(L('TASK'))),0);
			}
		}
	}

	/**
	 * 任务编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			$task_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$m_task = M('Task');
			$task_info = $m_task->where('task_id = %d',$task_id)->find();
			if (empty($task_info)) {
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			if ($task_info['is_deleted'] == 1) {
				$this->ajaxReturn('','任务已归档，不能编辑！',0);
			}

			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
		
			$m_task = M('Task');
			$m_task->create();
			if ($_POST['due_date']) {
				$m_task->due_date = strtotime($_POST['due_date']);
			}
			$m_task->update_date = time();
			if ($_POST['priority']) {
				$m_task->priority = trim($_POST['priority']);
			}
			if ($_POST['status']) {
				$status = trim($_POST['status']);
				if ($status == '完成') {
					$m_task->finish_date = time();
				}
			}
			if ($_POST['about_role_id']) {
				$about_roles = trim($_POST['about_role_id']);
				$m_task->about_roles = $about_roles;
			}
			if ($_POST['owner_role_id']) {
				$owner_role_id = trim($_POST['owner_role_id']);
				$m_task->owner_role_id = $owner_role_id;
			}
			$is_updated = false;
			if ($m_task->where('task_id = %d', $task_id)->save()) {
				$is_updated = true;
			} else {
				$this->ajaxReturn('',L('ONLY_STATUS_CAN_CHANGED_MODIFICATION_FAILS'),0);
			}

			if ($is_updated) {
				actionLog($task_id);
				$params_content = '';
				if ($task_info['subject'] != trim($_POST['subject']) && $_POST['subject']) {
					$params_content = '名称为:&nbsp;'.trim($_POST['subject']);
				}
				if ($task_info['description'] != trim($_POST['description']) && $_POST['description']) {
					$params_content = '描述为:&nbsp;'.trim($_POST['description']);
				}
				if ($params_content) {
					taskActionLog($task_id,2,'',$params_content);
				}
				if ($status == '完成' && $task_info['status'] != '完成') {
					taskActionLog($task_id,3,'');
				}
				if ($task_info['status'] == '完成' && $status != '完成') {
					taskActionLog($task_id,4,'');
				}

				$new_task_info = $m_task->where(array('task_id'=>$task_id))->find();
				//判断新增分配人、取消分配人
				if ($about_roles) {
					$old_about_role_ids = array_filter(explode(',', $task_info['about_roles']));
					$new_about_role_ids = array_filter(explode(',', trim($about_roles)));
					//数组并集
					$same_about_role_ids = array_intersect($new_about_role_ids,$old_about_role_ids);
					//新增分配人
					$add_about_role_ids = array();
					//取消分配人
					$sub_about_role_ids = array();
					if ($same_about_role_ids) {
						$add_about_role_ids = array_diff($new_about_role_ids, $same_about_role_ids);
						$sub_about_role_ids = array_diff($old_about_role_ids, $same_about_role_ids);
					} else {
						$add_about_role_ids = $new_about_role_ids;
						$sub_about_role_ids = $old_about_role_ids;
					}
					
					//站内信、邮件
					$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
					$message_content = L('MESSAGE_CONTENT' ,array($task_id, $new_task_info['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'],$new_task_info['priority'], $new_task_info['description']));
					// $email_content = L('EMAIL_CONTENT', array($new_task_info['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$new_task_info['due_date'] ,$new_task_info['priority'] , $new_task_info['description']));
					foreach ($add_about_role_ids as $k=>$v) {
						if ($v != session('role_id')) {
							sendMessage($v,$message_content,1);
							// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
						}
					}
					//操作记录
					if ($add_about_role_ids) {
						taskActionLog($task_id,7,','.implode(',',$add_about_role_ids).',');
					}
					if ($sub_about_role_ids) {
						taskActionLog($task_id,8,','.implode(',',$sub_about_role_ids).',');
					}
				}

				//判断新增关注人、取消关注人
				if ($owner_role_id) {
					$old_owner_role_ids = array_filter(explode(',', $task_info['owner_role_id']));
					$new_owner_role_ids = array_filter(explode(',', trim($owner_role_id)));
					//数组并集
					$same_owner_role_ids = array_intersect($new_owner_role_ids,$old_owner_role_ids);
					//新增关注人
					$add_owner_role_ids = array();
					//取消关注人
					$sub_owner_role_ids = array();
					if ($same_owner_role_ids) {
						$add_owner_role_ids = array_diff($new_owner_role_ids, $same_owner_role_ids);
						$sub_owner_role_ids = array_diff($old_owner_role_ids, $same_owner_role_ids);
					} else {
						$add_owner_role_ids = $new_owner_role_ids;
						$sub_owner_role_ids = $old_owner_role_ids;
					}
					//站内信、邮件
					$creator = D('RoleView')->where('role.role_id = %d',session('role_id'))->find();
					$message_content = L('MESSAGE_CONTENT' ,array($task_id, $new_task_info['subject'], $creator['user_name'], $creator['department_name'], $creator['role_name'], $new_task_info['priority'], $new_task_info['description']));
					// $email_content = L('EMAIL_CONTENT', array($new_task_info['subject'] ,$creator['user_name'] ,$creator['department_name'] ,$creator['role_name'] ,$new_task_info['due_date'] ,$new_task_info['priority'] , $new_task_info['description']));
					foreach ($add_owner_role_ids as $k=>$v) {
						if ($v != session('role_id')) {
							sendMessage($v,$message_content,1);
							// sysSendEmail($v,L('EMAIL_TITLE'),$email_content);
						}
					}
					//操作记录
					if ($add_owner_role_ids) {
						taskActionLog($task_id,9,','.implode(',',$add_owner_role_ids).',');
					}
					if ($sub_owner_role_ids) {
						taskActionLog($task_id,10,','.implode(',',$sub_owner_role_ids).',');
					}
				}
				if ($status == '完成') {
					$this->ajaxReturn('','任务已完成！',1);
				} else {
					$this->ajaxReturn('',L('MODIFY_TASK_SUCCESS'),1);
				}
			} else {
				$this->ajaxReturn('',L('DATA_DID_NOT_CHANGE_MODIFY_FAILED'),1);
			}
		} else {
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}
	}

	/**
	 * 任务详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view() {
		if ($this->isPost()) {
			$task_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$task_id) {
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			$m_task = M('Task');
			$m_task_type = M('TaskType');
			$m_user = M('User');
			$m_task_sub = M('TaskSub');
			$task_info = $m_task->where('task_id = %d',$task_id)->find();

			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$task_info['owner_roles_list'] = $task_info['owner_role_id'] ? $m_user->where('role_id in (%s)', '0'.$task_info['owner_role_id'].'0')->field('role_id,full_name,thumb_path')->select() : array();
			$task_info['about_roles_list'] = $task_info['about_roles'] ? $m_user->where('role_id in (%s)', '0'.$task_info['about_roles'].'0')->field('role_id,full_name,thumb_path')->select() : array();

			$task_info['creator'] = $m_user->where(array('role_id'=>$task_info['creator_role_id']))->field('full_name,role_id')->find();
			$task_info['week_name'] = getTimeWeek($task_info['create_date']);
			//任务列表名
			$task_info['type_name'] = $m_task_type->where(array('id'=>$task_info['type_id']))->getField('name');
			//子任务
			$sub_task_list = $m_task_sub->where(array('task_id'=>$task_info['task_id']))->select();
			$task_info['sub_list'] = $sub_task_list ? : array();

			//子任务进度
			$schedule = 0;
			$sub_count = count($sub_task_list);
			$done_count = 0;
			foreach ($sub_task_list as $k2=>$v2) {
				if ($v2['is_done'] == 1) {
					$done_count ++;
				}
			}
			if ($sub_task_list) {
				if ($done_count == 0) {
					$schedule = 0;
				} else {
					$schedule = round(($done_count/$sub_count)*100,2);
				}
			}
			$task_info['sub_count'] = $sub_count;
			$task_info['done_count'] = $done_count;
			$task_info['schedule'] = $schedule;

			switch ($task_info['status']) {
				case '未启动': $task_info['status_color'] = '#ed5565';break;
				case '推迟': $task_info['status_color'] = '#f8ac59';break;
				case '进行中': $task_info['status_color'] = '#1ab394';break;
				case '完成': $task_info['status_color'] = '#00aaef';break;
			}
			//附件
			$file_id_array = M('RTaskFile')->where('task_id = %d',$task_id)->getField('file_id',true);
			$file_list = $file_id_array ? M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select() : array();
			foreach ($file_list as $key => $value) {
				$file_type = '';
				$file_type = end(explode('.',$value['name']));
				$file_list[$key]['file_type'] = $file_type;
				$file_list[$key]['size'] = round($value['size']/1024,2).'Kb';
				if (intval($value['size']) > 1024*1024) {
					$file_list[$key]['size'] = round($value['size']/(1024*1024),2).'Mb';
				}
			}
			$task_info['file_list'] = $file_list ? $file_list : array();

			//活动
			$m_task_action = M('TaskAction');
			$action_list = $m_task_action->where(array('task_id'=>$task_id))->order('action_id desc')->select();
			foreach ($action_list as $k=>$v) {
				$create_role_info = $m_user->where('role_id = %d',$v['role_id'])->field('role_id,full_name,thumb_path')->find();
				$action_list[$k]['create_role_info'] = $create_role_info;
				$action_list[$k]['content'] = str_replace('&nbsp;', '', $v['content']);
				$role_list = array();
				if ($v['about_role_id']) {
					$role_ids = explode(',',$v['about_role_id']);
					$role_list = $m_user->where(array('role_id'=>array('in',$role_ids)))->field('full_name,thumb_path,role_id')->select();
				}
				$action_list[$k]['role_list'] = $role_list ? : array();
			}
			$task_info['action_list'] = $action_list;
			//相关模块名
			if ($task_info['module_id'] && $task_info['module']) {
				// $pk = M($task_info['module'])->getPk();
				// $module_name = M($task_info['module'])->where(array($pk=>$task_info['module_id']))->getField('name');
				$module_name = M('Customer')->where(array('customer_id'=>$task_info['module_id']))->getField('name');
			}
			$task_info['module_name'] = $module_name ? $module_name : '';

			//评论
			$m_task_talk = M('TaskTalk');//日志评论回复表
			$comment_list = $m_task_talk->where(array('task_id'=>$task_id,'parent_id'=>0))->order('create_time asc')->select();
			foreach ($comment_list as $key => $value) {
				$creator_info = array();
				$user_info = $m_user->where('role_id = %d',$value['send_role_id'])->field('thumb_path,role_id,full_name')->find();
				$comment_list[$key]['user_name'] = $user_info['full_name'];
				$comment_list[$key]['img'] = $user_info['thumb_path'];
				$comment_list[$key]['content'] = str_replace('src="', 'src="'.'http://'.$_SERVER['HTTP_HOST'], htmlspecialchars_decode($value['content']));
				
				//子回复
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
				$comment_list[$key]['comment_list_child'] = $comment_list_child ? $comment_list_child : array();
			}
			$task_info['comment_list'] = $comment_list ? $comment_list : array();
			$comment_cont = $m_task_talk->where(array('task_id'=>$task_id))->count();
			$task_info['comment_count'] = $comment_cont ? $comment_cont : '0';
			
			//获取权限
			$edit = 1;
			$delete = 1;
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$edit = 0;
			}
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$delete = 0;
			}
			$data['permission'] = array('edit'=>$edit,'delete'=>$delete);
			$data['data'] = $task_info;
			$data['info'] = '查询成功！'; 
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	
	/**
	 * 任务归档
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete(){
		if ($this->isPost()) {
			$m_task = M('Task');
			$task_id = $_POST['id'] ? intval($_POST['id']) : '';
			if (!$task_id) {
				$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
			}
			$task_info = $m_task->where(array('task_id'=>$task_id,'is_deleted'=>0))->find();
			if (!$task_info) {
				$this->ajaxReturn('','任务不存在或已归档！',0);
			}
			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			} else {
				$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time(),'status'=>'完成','finish_date'=>time());
				if ($m_task->where(array('task_id'=>$task_id))->save($data)) {
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
	 * 激活任务
	 * @param 
	 * @author 
	 * @return 
	**/
	public function revert(){
		if ($this->isPost()) {
			$task_id = isset($_POST['id']) ? intval($_POST['id']) : '';
			if ($task_id) {
				$m_task = M('Task');
				$m_task_type = M('TaskType');
				$task_info = $m_task->where('task_id = %d', $task_id)->find();

				//权限判断(创建人、分配人、关注人包含自己的)
				if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
					$this->ajaxReturn('','您没有此权利！',-2);
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
	}

	/**
	 * 增加任务分类
	 * @param 
	 * @author 
	 * @return 
	**/
	public function addtype(){
		if($this->isPost()){
			//权限判断
			if (!session('?admin') && !checkPerByAction('task','add')) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$m_task_type = M('TaskType');
			$max_order_id = $m_task_type->max('order_id');
			if ($_POST['name']) {
				$data = array();
				$data['name'] = trim($_POST['name']);
				$data['role_id'] = session('role_id');
				$data['create_time'] = time();
				$data['update_time'] = time();
				$data['order_id'] = $max_order_id+1;
				if ($task_id = $m_task_type->add($data)) {
					$task_data['id'] = $task_id;
					$task_data['name'] = $data['name'];
					$this->ajaxReturn('','创建成功！',1);
				} else {
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','请填写列表名称！',0);
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 编辑任务分类
	 * @param 
	 * @author 
	 * @return 
	**/
	public function editTypeAjax(){
		if ($this->isPost()) {
			//权限判断
			if (!session('?admin') && !checkPerByAction('task','edit')) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : '';
			if (!$type_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_task_type = M('TaskType');
			if ($_POST['name']) {
				$data = array();
				$data['name'] = trim($_POST['name']);
				$data['update_time'] = time();
				if ($m_task_type->where(array('id'=>$type_id))->save($data)) {
					$this->ajaxReturn($data,'修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','请填写列表名称！',0);
			}
		}
	}

	/**
	 * 删除（归档）任务列表
	 * @param 
	 * @author 
	 * @return 
	**/
	public function delTypeAjax(){
		if ($this->isPost()) {
			//权限判断
			if (!session('?admin') && !checkPerByAction('task','delete')) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$m_task_type = M('TaskType');
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : '';
			if (!$type_id) {
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
			if ($m_task_type->where(array('id'=>$type_id))->save($data)) {
				$data_task = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time(),'status'=>'完成','finish_date'=>time());
				M('Task')->where('type_id = %d',$type_id)->save($data_task);
				$this->ajaxReturn($data,'任务归档成功！',1);
			} else {
				$this->ajaxReturn('','数据已归档，请勿重复操作！',0);
			}
		}
	}

	/**
	 * 添加子任务
	 * @param 
	 * @author 
	 * @return 
	**/
	public function addSub(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if (!session('?admin') && !$below_ids) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}

		if ($this->isPost()) {
			$task_id = $_POST['id'] ? intval($_POST['id']) : '';
			$content = $_POST['content'] ? trim($_POST['content']) : '';
			if (!$task_id || !$content) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$task_info = M('Task')->where('task_id = %d',$task_id)->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			$m_task_sub = M('TaskSub');
			$data = array();
			$data['content'] = trim($_POST['content']);
			$data['task_id'] = $task_id;
			$data['create_role_id'] = session('role_id');
			$data['create_time'] = time();
			$data['update_time'] = time();
			if ($sub_id = $m_task_sub->add($data)) {
				taskActionLog($task_id,11,'',$content);
				$this->ajaxReturn('','创建成功！',1);
			} else {
				$this->ajaxReturn('','创建失败，请重试！',0);
			}
		}
	}

	/**
	 * 子任务修改
	 * @param 
	 * @author 
	 * @return 
	**/
	public function subEdit(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if (!session('?admin') && !$below_ids) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}

		if ($this->isPost()) {
			$m_task_sub = M('TaskSub');
			$sub_id = $_POST['sub_id'] ? intval($_POST['sub_id']) : '';
			if (!$sub_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$sub_info = $m_task_sub->where(array('id'=>$sub_id))->find();

			$task_info = M('Task')->where('task_id = %d',$sub_info['task_id'])->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if(!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			$is_done = $_POST['is_done'] ? intval($_POST['is_done']) : 0;
			$content = $_POST['content'] ? trim($_POST['content']) : '';
			$m_task_sub->update_time = time();
			if ($is_done) {
				if ($sub_info['is_done']) {
					$m_task_sub->is_done = 0;
				} else {
					$m_task_sub->is_done = 1;
				}				
				$m_task_sub->done_role_id = session('role_id');
				
				if ($m_task_sub->save()) {
					if ($sub_info['is_done']) {
						taskActionLog($sub_info['task_id'],13,'',$sub_info['content']);
					} else {
						taskActionLog($sub_info['task_id'],12,'',$sub_info['content']);
					}
					$this->ajaxReturn('','恭喜您，子任务完成！',1);
				} else {
					$this->ajaxReturn('','数据无变化！',0);
				}
			} elseif ($content) {
				$m_task_sub->content = $content;
				if ($m_task_sub->save()) {
					$this->ajaxReturn('','修改成功！',1);
				}
			}
		}
	}

	/**
	 * 子任务删除
	 * @param 
	 * @author 
	 * @return 
	**/
	public function subDel(){
		//权限判断
		$below_ids = getPerByAction('task','edit');
		if (!session('?admin') && !$below_ids) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}

		if ($this->isPost()) {
			$m_task_sub = M('TaskSub');
			$sub_id = $_POST['sub_id'] ? intval($_POST['sub_id']) : '';
			if (!$sub_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$sub_info = $m_task_sub->where(array('id'=>$sub_id))->find();

			$task_info = M('Task')->where('task_id = %d',$sub_info['task_id'])->find();
			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			if ($sub_info) {
				if ($m_task_sub->where(array('id'=>$sub_id))->delete()) {
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			} else {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
		}
	}

	/**
	 * 任务评论
	 * @param 
	 * @author 
	 * @return 
	**/
	public function comment_add() {
		if ($this->isPost()) {
			$talk_id = $_POST['talk_id'] ? intval($_POST['talk_id']) : 0;
			if ($talk_id) {
				//子回复
				$talk_id = $this->_post('talk_id','intval');
				$receive_role_id = $this->_post('receiveid','intval');
				$content = $this->_post('content','trim');			
				if (!$talk_id) $this->ajaxReturn('','当前回复发生跑路现象，暂不支持回复！',0);
				if (!$receive_role_id) $this->ajaxReturn('','当前回复对象发生跑路现象，暂不支持回复！',0);
				if (!$content) $this->ajaxReturn('','回复内容必填哦！',0);

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
				if ($talk_id) {
					$user_name = M('User')->where(array('role_id'=>session('role_id')))->getField('full_name');
					$message_content = '<a class="task_view" rel="'.$talk_info['task_id'].'" href="javascript:void(0);">'.$user_name.' 回复了你的评论</a>';
					sendMessage($receive_role_id,$message_content,1);
					$this->ajaxReturn('','评论发表成功！',1);
				} else {
					$this->ajaxReturn('','发表失败，程序员正在火速检修！',0);
				}
			} else {
				//主评论
				$task_id = $this->_post('task_id','intval');
				$send_role_id = session('role_id');
				$content = $this->_post('content','trim');
				if (!$task_id) $this->ajaxReturn('','当前日志发生跑路现象，暂不支持回复！',0);
				if (!$send_role_id) $this->ajaxReturn('','当前评论对象发生错误，暂不支持评论！',0);
				if (!$content) $this->ajaxReturn('','评论内容必填！',0);
				$m_task = M('Task');//任务表
				$task_info = $m_task->where('task_id = %d',$task_id)->find();

				$receive_role_id = $task_info['creator_role_id'];//接收者role_id

				$receive_role_ids = array();
				$owner_role_ids = array_filter(explode(',',$task_info['owner_role_id']));
				$about_roles = array_filter(explode(',',$task_info['about_roles']));
				if ($owner_role_ids && $about_roles) {
					$receive_role_ids = array_merge($owner_role_ids,$about_roles);
				} else {
					if ($owner_role_ids) {
						$receive_role_ids = $owner_role_ids;
					} elseif ($about_roles) {
						$receive_role_ids = $about_roles;
					}
				}

				if (!$receive_role_id) {
					$this->ajaxReturn('','该任务不存在或已删除！',0);
				}
				$data = array();
				$data['task_id'] = $task_id;
				$data['send_role_id'] = $send_role_id;
				$data['receive_role_id'] = $receive_role_id;//接收者role_id
				$data['content'] = $content;
				$data['create_time'] = time();
				$m_task_talk = M('TaskTalk');//日志评论回复表
				$talk_id = $m_task_talk->add($data);
				if ($talk_id) {
					$user_name = M('User')->where(array('role_id'=>session('role_id')))->getField('full_name');
					$message_content = '<a class="task_view" href="javascript:void(0);" rel="'.$task_id.'">'.$user_name.' 评论了你的任务</a>';
					foreach ($receive_role_ids as $k=>$v) {
						//发送站内信
						sendMessage($v,$message_content,1);
					}
					$g_mark = 'wk_'.$talk_id;
					$m_task_talk->where('talk_id = %d',$talk_id)->save(array('g_mark'=>$g_mark));
					$this->ajaxReturn('','评论成功！',1);
				} else {
					$this->ajaxReturn('','发表失败，程序员正在火速检修！',0);
				}
			}
		}
	}

	/**
	 * 评论删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function replyDel() {
		if ($this->isPost()) {
			$talk_id = $this->_post('talk_id','intval');
			if(!$talk_id) $this->ajaxReturn('','当前被删除项发生跑路现象，暂不支持此操作！',0);
			$m_task_talk = M('TaskTalk');//任务评论回复表
			$talkinfo = $m_task_talk->where('talk_id = %d',$talk_id)->find();
			$role_id = M('Task')->where('log_id = %d',$talkinfo['task_id'])->getField('creator_role_id');
			if($talkinfo){
				if($talkinfo['send_role_id'] != session('role_id') && $role_id != session('role_id') && !session('?admin')){
					$this->ajaxReturn('','sorry,您没有权限删除！',-2);
				}else{
					if ($talkinfo['parent_id' == 0]) {
						$msg = $m_task_talk->where(array('g_mark'=>$talkinfo['g_mark'],'parent_id'=>$talk_id))->delete();
					} else {
						$msg = $m_task_talk->where(array('g_mark'=>$talkinfo['g_mark']))->delete();
					}
					if ($msg) {
						$this->ajaxReturn('','success',1);
					} else {
						$this->ajaxReturn('','删除失败，程序员正在火速检修！',0);
					}	
				}							
			} else {
				$this->ajaxReturn('','数据查询失败！',0);
			}
		} else {
			$this->ajaxReturn('','跑神了-D',0);
		}
	}

	/**
	 * 任务移动
	 * @param 
	 * @author 
	 * @return 
	 */
	public function order() {
		if ($this->isPost()) {
			$task_id = $_POST['task_id'] ? intval($_POST['task_id']) : 0;
			$type_id = $_POST['type_id'] ? intval($_POST['type_id']) : 0;
			$m_task = M('Task');
			$task_info = M('Task')->where(array('task_id'=>$task_id))->find();
			if (!$task_info || !$type_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			//权限判断(创建人、分配人、关注人包含自己的)
			if (!session('?admin') && $task_info['creator_role_id'] != session('role_id') && !in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) && !in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//列表移动
			if ($type_id == $task_info['type_id']) {
				$this->ajaxReturn('','任务移动成功！',2);
			}
			if ($m_task->where(array('task_id'=>$task_id))->setField('type_id',$type_id)) {
				$this->ajaxReturn('','任务移动成功！',1);
			} else {
				$this->ajaxReturn('','移动失败，请重试！',0);
			}
		}
	}
}