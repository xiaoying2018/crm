<?PHP 
/**
*公告模块
*
**/
class AnnouncementAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('getannouncement','read_list')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
	}
	
	/**
	*公告列表页（默认页面）
	*
	**/
	public function index(){
		$m_announcement = M('Announcement');
		import('@.ORG.Page');
		$where = array();
		$params = array();
		
		$order = "order_id";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		
		/* if($this->_permissionRes) $where['role_id'] = array('in', $this->_permissionRes); */
		if($field = $this->_request('field','trim','')) {
			$field = $field == 'all' ? 'title|content' : $field;
			$search = $this->_request('search','trim','');
			$condition = $this->_request('condition','trim','is');
			if('create_time' == $field || 'update_time' == $field) $search = is_numeric($search)?$search:strtotime($search);
			switch ($condition) {
				case "is" : $where[$field] = array('eq',$search);break;
				case "isnot" :  $where[$field] = array('neq',$search);break;
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
			$params = array('field='.$field, 'condition='.$condition, 'search='.$search);
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'role_id'){
				if(!in_array(trim($search),$this->_permissionRes)){
					$where['role_id'] = array('in',$this->_permissionRes);
				}
			}
		}
		//高级搜索
		if(!$_GET['field']){
			$fields_search = array();
			foreach($_GET as $kd => $vd){
                if ($kd != 'act' && $kd != 'content' && $kd != 'p' && $kd != 'search') {
					if(in_array($kd,array('role_id'))){
						if(!empty($vd)){
							$where[$kd] = $vd['value'];
							$fields_search[$kd]['field'] = $kd;
							$fields_search[$kd]['value'] = $vd['value'];
						}
					}elseif(in_array($kd,array('create_time','update_time'))){
						$where[$kd] = field($vd['value'], $vd['condition']);
						$fields_search[$kd]['field'] = $kd;
						$fields_search[$kd]['start'] = $vd['start'];
						$fields_search[$kd]['end'] = $vd['end'];
						$fields_search[$kd]['form_type'] = 'datetime';

						//时间段查询
						if ($vd['start'] && $vd['end']) {
							$where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
						} elseif ($vd['start']) {
							$where[$kd] = array('egt',strtotime($vd['start']));
						} else {
							$where[$kd] = array('elt',strtotime($vd['end'])+86399);
						}
					}else{
						if(is_array($vd)) {
							if(!empty($vd['value'])){
								$where[$kd] = field($vd['value'], $vd['condition']);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['condition'] = $vd['condition'];
								$fields_search[$kd]['value'] = $vd['value'];
							}
						}else{
							if(!empty($vd)){
								$where[$kd] = field($vd);
								$fields_search[$kd]['field'] = $kd;
								$fields_search[$kd]['value'] = $vd;
							} 
						}
					}
                }
				if($kd != 'search'){
					if(is_array($vd)){
						foreach ($vd as $key => $value) {
							$params[] = $kd . '[' . $key . ']=' . $value;
						}
					}else{
						$params[] = $kd . '=' . $vd; 
					} 
				} 
            } 
			$this->fields_search = $fields_search;
		}
		$p = $this->_request('p','intval',1);
		$count = $m_announcement->count();
		$list = $m_announcement->where($where)->order($order)->Page($p.',10')->select();

		$this->parameter = implode('&', $params);
		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$userRole = M('userRole');
		foreach($list as $k => $v){
			$list[$k]['owner'] = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
		}
		
		$this->assign('list',$list);
		$this->assign("count",$count);
		$this->assign('page',$Page->show());
		$this->alert=parseAlert();
		$this->display();
	}
	
	/**
	*公告排序
	*
	**/
	public function announcementOrder(){
		if ($this->isGet()) {
			$m_announcement = M('Announcement');
			$a = 0;
			foreach (explode(',', $_GET['postion']) as $v) {
				$a++;
				$m_announcement->where('announcement_id = %d', $v)->setField('order_id',$a);
			}
			$this->ajaxReturn('1', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('0', L('EDIT FAILED'), 1);
		}
	}
	
	/**
	*添加公告
	*
	**/
	public function add(){
		if($this->isPost()){
			$title = $this->_post('title','trim','');
			if ($title == '' || $title == null) {
				alert('error',L('TITLE CAN NOT NULL'),$_SERVER['HTTP_REFERER']);
			}
			$d_announcement = M('Announcement');
			if($d_announcement->create()){
				$d_announcement->role_id = session('role_id');
				$d_announcement->department = '('.implode('),(', $_POST['announce_department']).')';
				$d_announcement->create_time = time();
				$d_announcement->update_time = time();
				$d_announcement->add();
				if($this->_post('submit','trim') == L('SAVE')) {
					alert('success', L('NOTICE TO ADD SUCCESS'), U('announcement/index'));
				} else {
					alert('success', L('ADD A SUCCESS'), U('announcement/add'));
				}
			}else{
				$this->error(L('ADD FAILURE'));
			}
		}else{
			$m_department = M('RoleDepartment');
			$department_list = $m_department->select();	
			$this->assign('department_list', getSubDepartment(0,$department_list,'', 1));
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*公告详情页面
	*
	**/
	public function view(){
		$m_announcement = M('Announcement');
		$m_announcement_data = M('AnnouncementData');
		$announcement_id = $this->_get('id','intval',0);
		//权限判断
		$announcement_info = $m_announcement->where('announcement_id = %d',$announcement_id)->find();
		if(empty($announcement_info)){
			$this->error(L('PARAMETER_ERROR'));
		}
		if(!in_array('('.session('department_id').')', explode(',',$announcement_info['department']))){
			alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
		}
		if($announcement_id){
			//公告阅读记录
			if(!$m_announcement_data->where(array('announcement_id'=>$announcement_id,'role_id'=>session('role_id')))->find()){
				$data['announcement_id'] = $announcement_id;
				$data['role_id'] = session('role_id');
				$data['read_time'] = time();
				$m_announcement_data ->add($data);
			}
			$m_announcement->where('announcement_id=%d',$announcement_id)->setInc('hits');
			$announcement_info['owner'] = D('RoleView')->where('role.role_id = %d', $announcement_info['role_id'])->find();
			$this->announcement = $announcement_info;
			$this->alert = parseAlert();
			$this->display();
		}else{
			$this->error(L('PARAMETER ERROR'));
		}
	}

	/**
	*公告阅读人
	*
	**/
	public function read_list(){
		$announcement_id = $_GET['id'] ? intval($_GET['id']) : '';
		//权限判断
		$m_announcement = M('Announcement');
		$m_announcement_data = M('AnnouncementData');
		$m_user = M('User');
		$announcement_info = $m_announcement->where('announcement_id = %d',$announcement_id)->find();
		if(!$announcement_info){
			$this->error(L('PARAMETER_ERROR'));
		}
		if(!in_array('('.session('department_id').')', explode(',',$announcement_info['department']))){
			alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
		}
		$read_list = $m_announcement_data->where('announcement_id = %d',$announcement_id)->order('read_time desc')->select();
		foreach($read_list as $k=>$v){
			$read_list[$k]['user_info'] = $m_user->where('role_id = %d',$v['role_id'])->field('full_name,thumb_path')->find();
		}
		$this->read_list = $read_list;
		$this->display();
	}
	
	/**
	*修改公告的状态（发布或停用）
	*
	**/
	public function changeStatus(){
		$m_announcement = M('Announcement');
		$announcement_id = $this->_get('id','intval',0);
		if ($announcement_id) {
			$announcement = $m_announcement->where('announcement_id = %d', $announcement_id)->find();
			if(!session('?admin') && $announcement['role_id'] != session('role_id')){
				alert('error','HAVE NOT PRIVILEGES', $_SERVER['HTTP_REFERER']);
			}
			if ($announcement['status'] == 1) {
				$m_announcement->where('announcement_id = %d', $announcement_id)->setField('status', 2);
				alert('success',L('MODIFY SUCCESS HAS BEEN DISCONTINUED'),$_SERVER['HTTP_REFERER']);
			} elseif($announcement['status'] == 2) {
				$m_announcement->where('announcement_id = %d', $announcement_id)->setField('status', 1);
				alert('success',L('MODIFY SUCCESS HAS BEEN PUBLISHED'),$_SERVER['HTTP_REFERER']);
			} else {
				alert('success',L('SYSTEM ERROR PLEASE CONTACT YOUR ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error',L('PARAMETER ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	/**
	*修改公告
	*
	**/
	public function edit(){
		$m_announcement = M('Announcement');
		$announcement_id = $this->_post('announcement_id','intval',$this->_get('id','intval'));
		$announcement = $m_announcement->where('announcement_id = %d',$announcement_id)->find();
		//权限判断
		if($announcement_id == 0){
			$this->error(L('PARAMETER ERROR'));
		}elseif(empty($announcement)){
			$this->error(L('PARAMETER_ERROR'));
		}elseif($this->_permissionRes && !in_array($announcement['role_id'], $this->_permissionRes)){
			alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
		}
		
		if($this->isPost()){
			$title = $this->_post('title','trim','');
			if ($title == '') {
				alert('error',L('THE NAME CANNOT BE EMPTY'),$_SERVER['HTTP_REFERER']);
			}
			if($m_announcement->create()){
				$m_announcement->department = '('.implode('),(', $this->_post('announce_department')).')';
				$m_announcement->update_time = time();
				if($m_announcement->save()){
					alert('success', L('ANNOUNCEMENT SAVED SUCCESSFULLY'), U('announcement/index'));
				} else {
					$this->error(L('TO MODIFY DATA FAILED NO CHANGE'));
				}
			}else{
				$this->error(L('TO MODIFY DATA FAILED NO CHANGE'));
			}
		}else{
			$m_department = M('RoleDepartment');
			$department_list = getSubDepartment(0,$m_department->order('department_id')->select(),'', 1);
			$announcement = $m_announcement->where('announcement_id = %d',$announcement_id)->find();
			$department_id_array = explode(',', $announcement['department']);

			foreach($department_list as $k=>$v){
				$checked = '';
				if(in_array('('.$v['department_id'].')', $department_id_array)){
					$checked = 'checked';
				}
				$department_list[$k]['checked'] = $checked;
			}
			$this->assign('department_list', $department_list);
			$this->announcement = $announcement;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*删除公告
	*
	**/
	public function delete(){
		if($this->isPost()){
			$m_announcement = M('Announcement');
			$announcement_idarray = $this->_post('announcement_id');
			if (is_array($announcement_idarray)) {
				$announcement_ids = array();
				//权限判断
				foreach ($announcement_idarray as $v) {
					$announcement = $m_announcement->where('announcement_id = %d', intval($v))->find();
					if(!in_array($announcement['role_id'], $this->_permissionRes)){
						$this->ajaxReturn('',L('DO NOT HAVE PRIVILEGES'),0);
					}else{
						$announcement_ids[] = intval($v);
					}
				}
				if ($m_announcement->where('announcement_id in (%s)', join(',', $announcement_ids))->delete()) {
					$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
				} else {
					$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
				}
			}else{
				$this->ajaxReturn('',L('PLEASE CHOOSE TO DELETE ANNOUNCEMENT'),0);
			}
		}elseif($this->isGet()){
			$m_announcement = M('Announcement');
			$announcement_id = $this->_get('id','intval',0);
			if($announcement_id == 0){
				alert('error', L('PLEASE CHOOSE TO DELETE ANNOUNCEMENT'),U('Announcement/index'));
			}
			$announcement = $m_announcement->where('announcement_id = %d', $announcement_id)->find();
			//权限判断
			if ($this->_permissionRes) {
				if (!in_array($announcement['role_id'],$this->_permissionRes)){
					alert('error', L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
			}
			if($m_announcement->where('announcement_id = %d', $announcement_id)->delete()){
				alert('success', L('DELETED SUCCESSFULLY'),U('Announcement/index'));
			}else{
				alert('error', L('DELETE FAILED CONTACT THE ADMINISTRATOR'),U('Announcement/index'));
			}
		}else{
			alert('error', L('PLEASE CHOOSE TO DELETE ANNOUNCEMENT'),U('Announcement/index'));
		}
	}
	
	/**
	 * 首页获取公告
	 **/
	public function getAnnouncement(){
		$m_announcement = M('announcement');
		$where['department'] = array('like', '%('.session('department_id').')%');
		$where['status'] = array('eq', 1);
		//公告列表权限判断
		$where['role_id'] = array('in', getPerByAction('announcement','index'));
		$announcement = $m_announcement->where($where)->order('order_id')->limit(7)->select();
		if(!empty($announcement)){
			$this->ajaxReturn($announcement,'success',1);
		}else{
			$this->ajaxReturn('','---暂无数据---',0);
		}
	}
}