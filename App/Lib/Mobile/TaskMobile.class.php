<?php
class TaskMobile extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('AppAuthenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		Global $roles;
		$this->roles = $roles;
		Global $roles;
		$this->roles = $roles;
	}
	//任务统计列表
	public function dynamic(){
		$by = $this->_get('by','trim');
		if($this->isPost()){
			$m_task = M('Task');
			$where = array();
			switch ($by) {
				case 'me' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"'; break; //我的任务
				case 'create' : $where['creator_role_id'] = session('role_id');break; //我分配的任务
				default :  $where['_string'] = 'creator_role_id in ('.implode(',', $this->_permissionRes).') OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"'; break;
			}
			$where['is_deleted'] = 0;
			$where['isclose'] = 0;
			$count = array();
			//未启动
			$where1['status'] = '未启动';
			$where_arr1 = array_merge($where,$where1);
			$count[0] = $m_task->where($where_arr1)->count();
			//推迟
			$where2['status'] = '推迟';
			$where_arr2 = array_merge($where,$where2);
			$count[1] = $m_task->where($where_arr2)->count();
			//进行中
			$where3['status'] = '进行中';
			$where_arr3 = array_merge($where,$where3);
			$count[2] = $m_task->where($where_arr3)->count();
			//已完成
			$where4['status'] = '完成';
			$where_arr4 = array_merge($where,$where4);
			$count[3] = $m_task->where($where_arr4)->count();
			//关闭
			$where5['status'] = '关闭';
			$where_arr5 = array_merge($where,$where5);
			$count[4] = $m_task->where($where_arr5)->count();
			$this->ajaxReturn($count,'success',1);
		}
	}
	//任务列表
	public function index(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			//获取添加权限
			$permission_list = apppermission(MODULE_NAME,ACTION_NAME);
			if($permission_list){
				$data['permission_list'] = $permission_list;
			}else{
				$data['permission_list'] = array();
			}
			//更新最后阅读时间
			$m_user = M('user');
			$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
			$last_read_time = json_decode($last_read_time_js, true);
			$last_read_time['task'] = time();
			$m_user->where('role_id = %d', session('role_id'))->setField('last_read_time',json_encode($last_read_time));
			
			$by = isset($_GET['by']) ? trim($_GET['by']) : '';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$m_task = M('Task');
			$where = array();
			
			if(isset($_POST['search'])){
				$where['subject'] = array('like','%'.trim($_POST['search']).'%');
			}	
			$order = "create_date desc";
			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,true);
			$all_ids = getSubRoleId();
			
			$get_ab = $this->_get('ab','trim');
			$ab = isset($get_ab) ? $get_ab : '';
			if($ab){
				switch($ab){
					case 'me' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"'; break;
					case 'create' : $where['creator_role_id'] = session('role_id');break;
				}
			}
			$params = array();
			switch ($by) {
				case 'create' : $where['creator_role_id'] = session('role_id');break;
				case 's1' : $where['status'] = L('NOT_START');  break;
				case 's2' : $where['status'] = L('DELAY');  break;
				case 's3' : $where['status'] = L('ONGOING');  break;
				case 's4' : $where['status'] = L('COMPLETE');  break;
				case 'closed' : $where['isclose'] = 1; break;
				case 'deleted' : $where['is_deleted'] = 1; break;
				case 'today' : 
					$where['due_date'] =  array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400)); 
					break;
				case 'week' : 
					$week = (date('w') == 0)?7:date('w');
					$where['due_date'] =  array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
					break;
				case 'month' : 
					$next_year = date('Y')+1;
					$next_month = date('m')+1;
					$month_time = date('m') ==12 ? strtotime($next_year.'-01-01') : strtotime(date('Y').'-'.$next_month.'-01');
					$where['due_date'] = array('between',array(strtotime(date('Y-m-01')) -1 ,$month_time));
					break;
				case 'add' : $order = 'create_date desc';  break;
				case 'update' : $order = 'update_date desc';  break;
				case 'me' : $where['_string'] = 'about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"'; break;
				default :  $where['_string'] = 'creator_role_id in ('.implode(',', $this->_permissionRes).') OR about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%"'; break;
			}
			if (!isset($where['is_deleted'])) {
				$where['is_deleted'] = 0;
			}
			if (!isset($where['_string'])  && !isset($where['creator_role_id'])){
				if($this->_permissionRes) $where['_string'] = ' about_roles like "%,'.session('role_id').',%" OR owner_role_id like "%,'.session('role_id').',%" OR creator_role_id in ('.implode(',', $this->_permissionRes).') ';
			}
			//客户下的任务
			if(intval($_GET['customer_id'])){
				$task_ids = M('rCustomerTask')->where('customer_id = %d', intval($_GET['customer_id']))->getField('task_id', true);
				$where['task_id'] = array('in',$task_ids);
			}else{
				if (!isset($where['status'])) {
					$where['status'] = array('neq','完成');
				}
				if (!isset($where['isclose'])) {
					$where['isclose'] = 0;
				}
			}
			//商机下的任务
			if(intval($_GET['business_id'])){
				$task_ids = M('rBusinessTask')->where('business_id = %d',intval($_GET['business_id']))->getField('task_id', true);
				$where['task_id'] = array('in',$task_ids);
			}
			$tasklist = $m_task->where($where)->order($order)->page($p.',10')->field('task_id,subject,status,due_date,owner_role_id,creator_role_id,about_roles')->select();
			foreach($tasklist as $k=>$v){
				$task_list[$k]['task_id'] = $v['task_id'];
				$task_list[$k]['subject'] = $v['subject'];
				$status = $v['status'];
				$due_time = $v['due_date'];
				if($due_time){
					$tomorrow_time = strtotime(date('Y-m-d', time()))+86400;
					$diff_days = ($due_time-$tomorrow_time)%86400>0 ? intval(($due_time-$tomorrow_time)/86400)+1 : intval(($due_time-$tomorrow_time)/86400);
					if($v['status'] == '完成' || $v['status'] == '未启动'){
						$task_list[$k]['diff_days'] = '';
					}else{
						if($diff_days > 0){
							$task_list[$k]['diff_days'] = '还有'.$diff_days.'天';
							$task_list[$k]['color'] = '#000000';
						}elseif($diff_days == 0){
							$task_list[$k]['diff_days'] = '今天完成';
							//粉色
							$task_list[$k]['color'] = '#FF00E0';
						}else{
							$diff_days = abs($diff_days);
							$task_list[$k]['diff_days'] = '超期'.$diff_days.'天';
							//红色
							$task_list[$k]['color'] = '#FF0000';
						}
					}
				}
				$task_list[$k]['status'] = $status;
				
				//权限判断
				$owner_role_id = in_array(session('role_id'),explode(',',$v['owner_role_id']));
				$about_roles = in_array(session('role_id'),explode(',',$v['about_roles']));
				$role_arr = getPerByAction(MODULE_NAME,'view');
				$res = in_array($v['creator_role_id'],$role_arr);
				$creator_role_id = $v['creator_role_id'];
				$below_ids = getSubRoleId(false);
				$permission = array();
				$permission['view'] = 0;
				$permission['edit'] = 0;
				$permission['delete'] = 0;
			//查看权限
				if(session('?admin') || $creator_role_id == session('role_id') || $owner_role_id || $about_roles || $res){
					$permission['view'] = 1;
				}
			//编辑权限
				if( in_array($v['creator_role_id'],$below_ids) || session('role_id') == $v['creator_role_id'] || session('?admin') || in_array(session('role_id'),getPerByAction(MODULE_NAME,'edit'))){
					$permission['edit'] = 1;
				}
			//删除权限
				if(in_array($task_info['creator_role_id'],getPerByAction(MODULE_NAME,'delete')) || session('?admin')){
					$permission['delete'] = 1;
				}
				
				$task_list[$k]['permission'] = (object)$permission;
				//负责人
				$owner_role_id = substr($v['owner_role_id'],1,-1);
				$owner_role_ids = explode(',',$owner_role_id);
				foreach($owner_role_ids as $key=>$val){
					$owner_name = M('User')->where(array('role_id'=>$val))->getField('name');
					if($owner_name){
						$task_list[$k]['owner_name'][] = $owner_name;
					}else{
						$task_list[$k]['owner_name'][] = '';
					}
				}
			}
			if(empty($task_list)){
				$task_list = array();
			}
			$count = $m_task->where($where)->count();
			$page = ceil($count/10);
			$data['list'] = $task_list;
			$data['page'] = $page;
			$this->ajaxReturn($data,'success',1);	
		}
	}
	//任务详情
	public function view(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$m_task = M('Task');
			$task = $m_task->where('task_id = %d',$task_id)->field('subject,create_date,owner_role_id,about_roles,status,priority,due_date,creator_role_id,description')->find();
			$owner_role_id = in_array(session('role_id'),explode(',',$task['owner_role_id']));
			$about_roles = in_array(session('role_id'),explode(',',$task['about_roles']));
			$res = in_array($task['creator_role_id'],$this->_permissionRes);
			if($owner_role_id || $about_roles || $res){
				$i = 0;
				foreach($task as $k=>$v){
					$task_list[$i]['field'] = $k;
					$task_list[$i]['name'] = '';
					$task_list[$i]['val'] = $v;
					if($k == 'owner_role_id'){
						if($v == ','){
							$task_list[$i]['id'] = '';
						}else{
							$task_list[$i]['id'] = $v;
						}
						if($v){
							unset($task_list[$i]['val']);
						}
						$owner = D('RoleView')->where('role.role_id in (%s)', '0'.$task['owner_role_id'].'0')->field('user_name,role_id')->select();
						$task_list[$i]['val'] = $owner;
						$task_list[$i]['type'] = 2; 
					}elseif($k == 'creator_role_id'){
						$task_list[$i]['id'] = $v;
						if($v){
							unset($task_list[$i]['val']);
						}
						$creator_name = M('User')->where('role_id = %d',$v)->getField('name');
						$task_list[$i]['id'] = $v;
						$task_list[$i]['val'] = $creator_name;
						$task_list[$i]['type'] = 1; 
					}elseif($k == 'about_roles'){
						if($v == ','){
							$task_list[$i]['id'] = '';
						}else{
							$task_list[$i]['id'] = $v;
						}
						if($v){
							unset($task_list[$i]['val']);
						}
						$owner = D('RoleView')->where('role.role_id in (%s)', '0'.$task['about_roles'].'0')->field('user_name,role_id')->select();
						$task_list[$i]['val'] = $owner;
						$task_list[$i]['type'] = 2; 
					}elseif($k == 'description'){
						$task_list[$i]['id'] = '';
						$task_list[$i]['val'] = '-暂不支持-';
						$task_list[$i]['type'] = 0;
					}else{
						$task_list[$i]['id'] = '';
						$task_list[$i]['type'] = 0;
					}
					$i++;
				}
				$r_module = array('Business'=>'RBusinessTask', 'Contacts'=>'RContactsTask', 'Customer'=>'RCustomerTask', 'Product'=>'RProductTask','Leads'=>'RLeadsTask');
				foreach ($r_module as $key=>$value) {
					$r_m = M($value);
					if($module_id = $r_m->where('task_id = %d', $task_id)->getField($key . '_id')){			
						if($key == 'Leads') {
							$leads = M($key)->where($key.'_id = %d', $module_id)->find();
							$name = $leads['first_name'].$leads['last_name'].$leads['saltname'].' ' . $leads['company'];
						} else {
							$name = M($key)->where($key.'_id = %d', $module_id)->getField('name');
						}
						switch ($key){
							case 'Product' : $module_name= L('PRODUCT');$type = 6; break;
							case 'Leads' : $module_name= L('LEADS');$type = 7; break;
							case 'Contacts' : $module_name= L('CONTACTS');$type = 5; break;
							case 'Business' : $module_name= L('BUSINESS');$type = 4; break;
							case 'Customer' : $module_name= L('CUSTOMER');$type = 3; break;
						}
						$task_list[$i]['field'] = 'module';
						$task_list[$i]['name'] = '相关'.$module_name;
						$task_list[$i]['val'] = $name;
						$task_list[$i]['id'] = $module_id;
						$task_list[$i]['type'] = $type;
					}
				}
				$data['data'] = $task_list;
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}else{
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
		}
	}
	/**
	*任务详情（新）
	*
	**/
	public function viewnew(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$task_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			if(!$task_id){
				$this->ajaxReturn('参数错误!','参数错误!',2);
			}
			$m_task = M('Task');
			$task_info = $m_task->where('task_id = %d',$task_id)->field('subject,create_date,owner_role_id,about_roles,status,priority,due_date,creator_role_id,description')->find();
			$owner_roles = in_array(session('role_id'),explode(',',$task_info['owner_role_id']));
			$about_roles = in_array(session('role_id'),explode(',',$task_info['about_roles']));
			$res = in_array($task_info['creator_role_id'],$this->_permissionRes);
			if($owner_roles || $about_roles || $res){
				//任务成员
				//$owner_role_id = substr($task_info['owner_role_id'],1,-1);
				//$about_roles_id = substr($task_info['about_roles'],1,-1);
				$owner_role_arr = array_filter(explode(',',$task_info['owner_role_id']));
				$about_roles_arr = array_filter(explode(',',$task_info['about_roles']));
				$role_arr = array_unique(array_merge($owner_role_arr,$about_roles_arr));
				$role_count = count($role_arr);
				$role_list = array();
				$d_role = D('RoleView');
				foreach($role_arr as $k=>$v){
					$role_list[$k] = $d_role->where('role.role_id = %d',$v)->field('user_name,role_id,img')->find();
				}
				if($role_list){
					$data['role_list'] = array_merge($role_list);
					$data['role_count'] = $role_count;
				}
				//任务负责人
				$owner_role_name = array();
				foreach($owner_role_arr as $k=>$v){
					$owner_role_name[] = $d_role->where('role.role_id = %d',$v)->getField('user_name');
				}
				$task_info['owner_role_name'] = implode(',',$owner_role_name);
				//任务相关人
				$about_roles_name = array();
				foreach($about_roles_arr as $k=>$v){
					$about_roles_name[] = $d_role->where('role.role_id = %d',$v)->getField('user_name');
				}
				$task_info['about_roles_name'] = implode(',',$about_roles_name);
				//进度日志
				$log_ids = M('RLogTask')->where('task_id = %d',$task_id)->getField('log_id',true);
				$m_log = M('Log');
				$task_log = array();
				foreach($log_ids as $k=>$v){
					$log_info = $m_log->where('log_id = %d',$v)->field('role_id,create_date,content')->find();
					$role_info = $d_role->where('role.role_id = %d',$log_info['role_id'])->field('img,role_id,user_name')->find();
					$log_info['img'] = $role_info['img'];
					$log_info['user_name'] = $role_info['user_name'];
					$log_info['role_id'] = $role_info['user_name'];
					$task_log[$k] = $log_info;
				}
				$data['task_log'] = $task_log;
				$r_module = array('Business'=>'RBusinessTask', 'Contacts'=>'RContactsTask', 'Customer'=>'RCustomerTask', 'Product'=>'RProductTask','Leads'=>'RLeadsTask');
				foreach ($r_module as $key=>$value) {
					$r_m = M($value);
					if($module_id = $r_m->where('task_id = %d', $task_id)->getField($key . '_id')){			
						if($key == 'Leads') {
							$leads = M($key)->where($key.'_id = %d', $module_id)->find();
							$name = $leads['first_name'].$leads['last_name'].$leads['saltname'].' ' . $leads['company'];
						} else {
							$name = M($key)->where($key.'_id = %d', $module_id)->getField('name');
						}
						switch ($key){
							case 'Product' : $type = 6; break;
							case 'Leads' : $type = 7; break;
							case 'Contacts' : $type = 5; break;
							case 'Business' : $type = 4; break;
							case 'Customer' : $type = 3; break;
						}
						$module_info = array();
						$module_info['name'] = $name;
						$module_info['type'] = $type;
						$module_info['id'] = $module_id;
					}
				}
				$task_info['module_info'] = empty($module_info) ? array() : $module_info;
				$description = $task_info['description'];
				//过滤html代码
				$str = htmlspecialchars_decode($description); //内容全部反编译
				$str = preg_replace( "@<script(.*?)</script>@is", "", $str );
				$str = preg_replace( "@<div(.*?)</div>@is", "", $str );
				$str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str );
				$str = preg_replace( "@<style(.*?)</style>@is", "", $str );
				$str = preg_replace( "@<(.*?)>@is", "", $str );
				$str = str_replace( "&nbsp;","", $str );
				$description_info = preg_replace("/<(.*?)>/","",$str);
				
				$task_info['description'] = $description_info;
				$data['task_info'] = $task_info; //任务详情
				$this->ajaxReturn($data,'success',1);
			}else{
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
		}
	}
	/**
	*任务进度日志
	*
	**/
	public function tasklog(){
		if($this->isPost()){
			$task_id = $this->_post('id','intval');
			$params = json_decode($this->_post('params','trim'),true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式!',2);
			}
			if(empty($params['content'])){
				$this->ajaxReturn('','内容描述不能为空！',2);
			}
			$m_log = M('Log');
			$m_task = M('Task');
			$m_log->create();
			$m_log->category_id = 1;
			$m_log->create_date = time();
			$m_log->update_date = time();
			$m_log->content = $params['content'];
			$m_log->role_id = session('role_id');
			if($log_id = $m_log->add()){
				if(!empty($params['status'])){
					$m_task->where('task_id = %d', $task_id)->setField('status',$params['status']);
				}
				$data['log_id'] = $log_id;
				$data['task_id'] = $task_id;
				if(M('RLogTask') -> add($data)){
					$this->ajaxReturn('','添加成功',1);
				}
			}else{
				$this->ajaxReturn('','添加失败，请重试！',2);
			}
		}
	}
	/**
	*增加任务
	*
	**/
	public function add(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$m_task = M('Task');
			$params = json_decode($this->_post('params','trim'),true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式!',2);
			}
			if ($task = $m_task->create($params)) {
				$task['create_date'] = time();
				$task['update_date'] = time();
				$task['due_date'] = isset($params['due_date']) ? $params['due_date'] : time();
				
				if($task['status'] == '完成'){
					$task['finish_date'] = time();
				}
				switch($params['status']){
					case '未启动' : $params['status'] = '未启动';break;
					case '推迟' : $params['status'] = '推迟';break;
					case '进行中' : $params['status'] = '进行中';break;
					case '完成' : $params['status'] = '完成';break;
					default : $params['status'] = '未启动';break;
				}
				
				$task['creator_role_id'] = session('role_id');
				if(!$params['subject']) $this->ajaxReturn('','请填写任务主题!',2);
				$owner_role_id = $params['owner_role_id_str'];
				//$task['owner_role_id'] = ','.implode(',',$owner_role_id).',';
				$task['owner_role_id'] = $owner_role_id;
				$about_roles = $params['about_roles'];
				//$task['about_roles'] = ','.implode(',',$about_roles).',';
				$task['about_roles'] = $about_roles;
				$send_email_array = ($task['owner_role_id']).($task['about_roles']);
				if($send_email_array){
					$owner_role_id_array = explode(',', $send_email_array);
					$creator = getUserByRoleId(session('role_id'));
					if ($task_id = $m_task->add($task)) {
						$message_content = '您有新的任务，这是一封CRM系统自动生成的任务站内信通知!<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 主题：'.$params['subject'].'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 分配者:'.$creator['user_name'].'['.$creator['department_name'] .'-'. $creator['role_name'] .']'. '<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 任务截止日期:'.date('Y-m-d H:i:s',$params['due_date']) .'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 优先级：'.$params['priority'].'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 描述：'.$params['description'];
						//$email_content = '这是一封CRM系统自动生成的任务通知邮件!<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 主题：'.$params['subject'] .'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 分配者:'.$creator['user_name'] .'['.$creator['department_name'] .'-'. $creator['role_name'] .']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 任务截止日期：'.$params['due_date'] .'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 优先级：'.$params['priority'] .'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 描述：'.$params['description'];
						
						$module = isset($params['module']) ? $params['module'] : '';
						
						if($module != ''){
							switch ($module) {
								case 'contacts' : $m_r = M('RContactsTask'); $module_id = 'contacts_id'; break;
								case 'leads' : $m_r = M('RLeadsTask'); $module_id = 'leads_id'; break;
								case 'customer' : $m_r = M('RCustomerTask'); $module_id = 'customer_id'; break;
								case 'product' : $m_r = M('RProductTask'); $module_id = 'product_id'; break;
								case 'business' : $m_r = M('RBusinessTask'); $module_id = 'business_id'; break;
							}
							if ($params['module_id']) {
								$data[$module_id] = intval($params['module_id']);
								$data['task_id'] = $task_id;
								$rs = $m_r->add($data);
								if ($rs<=0) {
									$this->error('关联失败！');
								}
							}
						}
						foreach(array_unique($owner_role_id_array) as $k => $v){
							if($v && $v != session('role_id')) {
								sendMessage($v,$message_content,1);
							}
						}
					}
					actionLog($task_id);
					$this->ajaxReturn('添加成功！','添加成功！',1);
				} else {
					$this->ajaxReturn('请选择任务负责人！','请选择任务负责人！',1);
				}
			} else {
				$this->ajaxReturn('','添加失败，请重试!',2);
			}
		}
	}
	/**
	*编辑任务
	*
	**/
	public function edit(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$task_id = $this->_post('id','intval');
			$post_params = $this->_post('params','trim');
			$params = json_decode($post_params,true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式!',2);
			}
			$m_task = M('Task');
			$task = $m_task->where('task_id = %d',$task_id)->find();
			if(empty($task)){
				$this->ajaxReturn('','数据不存在或已删除',2);
			}elseif($this->_permissionRes && !in_array(session('role_id'),$this->_permissionRes)){
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
			$below_ids = getSubRoleId(false);
			$d_task = D('Task');
			$d_task->create($params);
			$d_task->due_date = $params['due_date'];
			$d_task->update_date = time();
			
			$status = $params['status'];
			if($status == '完成'){
				$d_task->finish_date = time();
			}
			
			$owner_role_id = $params['owner_role_id_str'];
			//$d_task->owner_role_id = ','.implode(',',$owner_role_id).',';
			$d_task->owner_role_id = $owner_role_id;
			//原描述不做修改
			$d_task->description = $task['description'];
			$is_updated = false;
			$module = isset($params['module']) ? $params['module'] : '';
			if( in_array($task['creator_role_id'],$below_ids) || session('role_id') == $task['creator_role_id'] || session('?admin')){
				if ($module != '') {
					switch ($module) {
						case 'contacts' : $m_r = M('RContactsTask'); $module_id = 'contacts_id'; break;
						case 'leads' : $m_r = M('RLeadsTask'); $module_id = 'leads_id'; break;
						case 'customer' : $m_r = M('RCustomerTask'); $module_id = 'customer_id'; break;
						case 'product' : $m_r = M('RProductTask'); $module_id = 'product_id'; break;
						case 'business' : $m_r = M('RBusinessTask'); $module_id = 'business_id'; break;
					}
					if ($params['module_id']) {
						if (!$m_r->where('task_id = %d and '.$module.'_id = %d', $task_id, intval($params['module_id']))->find()) {
							$r_module = array('Business'=>'RBusinessTask', 'Contacts'=>'RContactsTask', 'Customer'=>'RCustomerTask', 'Product'=>'RProductTask','Leads'=>'RLeadsTask');
							foreach ($r_module as $key=>$value) {
								$r_m = M($value);
								$r_m->where('task_id = %d', $task_id)->delete();
							}
							$data[$module_id] = intval($params['module_id']);
							$data['task_id'] = $task_id;
							$rs = $m_r->add($data);
							if ($rs<=0) {
								$this->ajaxReturn('','关联失败！',2);
							}
							$is_updated = true;
						}
					} else {
						$this->ajaxReturn('','请选择对应项！',2);
					}
				}else{
					//如果已设置相关，在编辑时又把相关设置为空时，删除所有相关
					$r_module = array('RBusinessTask', 'RContactsTask', 'RCustomerTask', 'RProductTask');
					foreach ($r_module as $value) {
						$r_m = M($value);
						$r_m->where('task_id = %d', $task_id)->delete();
					}
				}
				if ($d_task->where('task_id = %d',$task_id)->save()) $is_updated = true;
			}elseif(in_array(session('role_id'),$this->_permissionRes)){
				$data['status'] = $params['status'];
				if($data['status'] == '完成'){
					$complete_message = '您创建的任务'.$task['subject'].',负责人已完成，请及时查看评论！';
					sendMessage($task['creator_role_id'],$complete_message, 1);
				}
				if($d_task->where('task_id = %d', $task_id)->save($data)){
					$is_updated = true;
				}else{
					$this->ajaxReturn('','修改失败，您只可以改变任务的状态或数据变化！',2);
				}
			}else{
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
			if($is_updated){
				actionLog($task_id);
				$this->ajaxReturn('','修改成功！',1);
			}else{
				$this->ajaxReturn('','修改失败或数据无变化！',2);
			}
		}
	}
	/**
	*删除任务到回收站
	*
	**/
	public function delete(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		$m_task = M('Task');
		if($this->isPost()){
			$task_id = $this->_post('id','intval');
			if($task_id == ''){
				$this->ajaxReturn('','参数错误！',2);
			}else{
				$task_info = $m_task->where('task_id = %d', $task_id)->find();
				if(!in_array($task_info['creator_role_id'],$this->_permissionRes) && !session('?admin')){
					$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
				}
				$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
				if($m_task->where('task_id in (%s)', $task_id)->save($data)){	
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败，请重试！',2);
				}
			}
		}
	}
}