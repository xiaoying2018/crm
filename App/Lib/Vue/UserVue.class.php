<?php
/**
 *用户相关
 **/
class UserVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array('login'),
			'allow'=>array('permission','logout','view','checkuser','listdialog','permission_data')
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

		// if(!$this->isPost()){
		// 	$this->ajaxReturn(0, '请求方式不正确',0);
		// }
	}

	/**
	 * 获得岗位对应的模块权限
	 * @param 
	 * @author 
	 * @return 
	 */
	public function permission(){
		$m_permission = M('Permission');
		$row = $m_permission->where(array('position_id'=>session('position_id'),'url'=>array('like','%/index%')))->field('url')->select();
		$permission = array();
		$model = '';
		$existModel = array('customer','business','knowledge','contacts','product','leads','contract','announcement','examine','event','sign','task');
		foreach($row as $v){
			$tmp = explode('/',$v['url']);
			if($model != $tmp[0] && $tmp[1] == 'index'){
				$model = $tmp[0];
				if(in_array($model,$existModel) && !in_array($model,$permission)){
					$permission[] = $model;
				}
			}
		}
		//通讯录权限
		$mail_list = $m_permission->where(array('position_id'=>session('position_id'),'url'=>'user/contacts'))->find();
		if ($mail_list) {
			$permission[] = 'address_book';
		}
		//财务权限
		$finance_per = $m_permission->where(array('position_id'=>session('position_id'),'url'=>array('in','finance/index_receivables,finance/index_receivingorder,finance/index_payables,finance/index_paymentorder')))->find();
		if ($finance_per) {
			$permission[] = 'finance';
		}
		return $permission;
	}

	/**
	 * 获取模块权限数据
	 * @param 
	 * @author 
	 * @return 
	 */
	public function permission_data(){
		//财务模块下详细权限
		$finance_data = array('receivables','receivingorder','payables','paymentorder');
		$m_permission = M('Permission');
		$finance_type = array();
		foreach ($finance_data as $k=>$v) {
			$row_res = $m_permission->where(array('position_id'=>session('position_id'),'url'=>'finance/index_'.$v))->find();
			if ($row_res) {
				$finance_type[] = $v;
			}
		}
		if (session('?admin')) {
			$data['admin'] = 1;
		} else {
			$data['admin'] = 0;
			$data['permission'] = $this->permission();
			$data['finance_type'] = $finance_type ? $finance_type : array();
		}
		$data['info'] = 'success';
		$data['status'] = 1;
		$this->ajaxReturn($data,'JSON');
	}

	/**
	 * 用户登录
	 * @param 
	 * @author 
	 * @return 
	 */
	public function login(){
		if ($this->isPost()) {
			$m_user = M('User');
			$user = $m_user->where(array('name' => trim($_REQUEST['name'])))->find();
			if (!$user) {
				$this->ajaxReturn('','此账号不存在！',0);
			}
			if ($user['password'] == md5(trim($_POST['password']) . $user['salt'])){
				if (0 == $user['status']) {
					$this->ajaxReturn('','该账号未激活！',0);
				} elseif($user['status'] == 2) {
					$this->ajaxReturn('','该账号已停用！',0);
				} else {
					$d_role = D('RoleView');
					$role = $d_role->where('user.user_id = %d', $user['user_id'])->find();
					if (!is_array($role) || empty($role)) {
						$this->ajaxReturn('','此账号不存在！',0);
					} else {
						if ($user['category_id'] == 1) {
							session('admin', 1);
						} else {
							session('admin', null);
						}
						
						$m_config = M('Config');
						if ($m_config->where('name = "num_id"')->find()) {
							$m_config->where('name = "num_id"')->setField('value', trim($_POST['num_id']));
						} else {
							$data['value'] = trim($_POST['num_id']);
							$data['name'] = "num_id";
							$m_config->add($data);
						}
						
						session('role_id', $role['role_id']);
						session('position_id', $role['position_id']);
						session('role_name', $role['role_name']);
						session('department_id', $role['department_id']);
						session('name', $user['name']);
						session('user_id', $user['user_id']);

						$data['img'] = empty($user['thumb_path']) ? '' : $user['thumb_path'];
						$data['session_id'] = session_id();

						//生成token
						if ($token = M('User')->where(array('user_id'=>$user['user_id']))->getField('token')) {
							$data['token'] = $token;
							$m_user->where('role_id = %d',session('role_id'))->setField(array('token_time'=>time()));
						} else {
							$data['token'] = md5(md5($data['session_id']).time());
							$m_user->where('role_id = %d',session('role_id'))->setField(array('token'=>$data['token'],'token_time'=>time()));
						}

						if (session('?admin')) {
							$data['admin'] = 1;
						} else {
							$data['admin'] = 0;
							$data['permission'] = $this->permission();
							//财务模块下详细权限
							$finance_data = array('receivables','receivingorder','payables','paymentorder');
							$m_permission = M('Permission');
							$finance_type = array();
							foreach ($finance_data as $k=>$v) {
								$row_res = $m_permission->where(array('position_id'=>session('position_id'),'url'=>'finance/index_'.$v))->find();
								if ($row_res) {
									$finance_type[] = $v;
								}
							}
							$data['finance_type'] = $finance_type ? $finance_type : array();
						}
						$data['role_id'] = $role['role_id'];
						$data['name'] = $user['full_name'];
						$data['department_id'] = $role['department_id'];
						$data['department_name'] = $role['department_name'];
						$data['role_name'] = $role['role_name'];
						//系统名称
						$defaultinfo = M('Config')->where('name = "defaultinfo"')->getField('value');
						$defaultinfo_info = unserialize($defaultinfo);
						$data['system_name'] = trim($defaultinfo_info['name']) ? : 'CRM';

						$data['info'] = 'success';
						$data['status'] = 1;
						$this->ajaxReturn($data,'JSON');
					}
				}
			} else {
				$this->ajaxReturn('','密码错误！',0);
			}
		} else {
			$this->ajaxReturn(0, '请求方式不正确',0);
		}
	}

	/**
	 * 用户退出
	 * @param 
	 * @author 
	 * @return 
	 */
	public function logout(){
		//清空token
		$data = array();
		$data['token'] = '';
		$data['token_time'] = '';
		M('User')->where(array('role_id'=>session('role_id')))->save($data);
		session(null);
		$this->ajaxReturn('',"退出成功！",1);
	}

	/**
	 * 用户个人中心
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$role_id = $_POST['id'] ? intval($_POST['id']) : session('role_id');
			$d_role = D('RoleView');
			$role_info = $d_role->where('role.role_id = %d', $role_id)->find();
			if (!$role_info) {
				$this->ajaxReturn('','数据异常，请稍后重试！',0);
			}
			//是否有编辑登录名和员工编号的权限
            $is_edit = 0;
            if (session('?admin') || checkPerByAction('user','edit') || intval($_POST['id']) == session('role_id')) {
            	$is_edit = 1;
            }
            $data['is_edit'] = $is_edit;
			$data['data'] = $role_info;
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn(0, '请求方式不正确',0);
		}
	}

	/**
	 * 用户修改资料
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			$role_id = $_POST['id'] ? intval($_POST['id']) : session('role_id');
			if(!session('?admin') && (session('role_id') != $role_id)){
            	if(!checkPerByAction('user','edit')){
            		$this->ajaxReturn('','您没有此权限！',-2);
				}
            }
			if ($_POST['email'] && !ereg('^[_\.0-9a-zA-Z]+@([0-9a-zA-Z][A-Za-z0-9_-]+\.)+[a-zA-Z]{2,4}$', trim($_POST['email']))) {
				$this->ajaxReturn('','邮箱格式不正确！',0);
			}
            if ($_POST['telephone'] && !ereg('^1[34758][0-9]{9}$', trim($_POST['telephone']))) {
            	$this->ajaxReturn('','手机号码格式不正确！',0);
			}
            $m_user = M('User');
			$m_role = M('Role');
			$user = $m_user->where('role_id = %d', $role_id)->find();
			
			if ($user['name'] !== trim($_POST['name']) && trim($_POST['name'])) {
				$name_result = $m_user->where(array('name'=>trim($_POST['name']),'role_id'=>array('neq',$role_id)))->find();
				if ($name_result) {
					$this->ajaxReturn('','登录账号不能重复！',0);
				}
			}

			if ($m_user->create()) {
				//权限控制
				$is_update = false;
				if (session('?admin') || checkPerByAction(MODULE_NAME,ACTION_NAME)) {
					$is_update = $m_role->where('role_id = %d', intval($_POST['role_id']))->setField('position_id', $_POST['position_id']);
				} else {
					unset($m_user->$user['name']);
					unset($m_user->$user['prefixion']);
					unset($m_user->$user['number']);
					unset($m_user->$user['type']);
				}				
				unset($m_user->category_id);
				if (isset($_POST['password']) && $_POST['password'] != '') {
					$m_user->password = md5(md5(trim($_POST["password"])) . $user['salt']);
				} else {
					unset($m_user->password);
				}
				//判断是否管理员（管理员账户不能停用）
				if($user['category_id'] == 1){
					unset($m_user->status);
				}

				//头像上传
				if (isset($_FILES['img']['size']) && $_FILES['img']['size'] > 0) {
					import('@.ORG.UploadFile');
					import('@.ORG.Image');//引入缩略图类
					$Img = new Image();//实例化缩略图类
					$upload = new UploadFile();
					$upload->maxSize = 2000000;
					$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
					$dirname = UPLOAD_PATH.'head/';
					$upload->thumb = true;//生成缩图
					$upload->thumbRemoveOrigin = false;//是否删除原图
					if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
						//附件上传目录不可写(不会返回)  
						$this->ajaxReturn('附件上传目录不可写','附件上传目录不可写',2);
					}
					$upload->savePath = $dirname;
					if(!$upload->upload()) {
						  $data['info'] = $upload->getErrorMsg();
						  $data['status'] = 0;
						  $this->ajaxReturn($data,'JSON');
					}else{
						$info = $upload->getUploadFileInfo();
					}
					if(is_array($info[0]) && !empty($info[0])){
						$upload = UPLOAD_PATH.'/head/' . $info[0]['savename'];
					}else{
						//上传失败(不会返回)  $this->ajaxReturn('','error',2);
					}
					$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);
					//保存 上传的图片
					$m_user->img = $upload;
					$m_user->thumb_path = $thumb_path;
				}
				if ($m_user->where(array('role_id'=>$role_id))->save() || $is_update) {
					//删除旧头像文件
					// $oldImg = $m_user->where('role_id = %d',$role_id)->getField('img');
					// if ($oldImg) {
					// 	if (file_exists($oldImg)) {
					// 		@unlink($oldImg);
					// 	}
					// }

					actionLog($_POST['role_id']);
					if ($role_id == session('role_id')) {
						unset ($_SESSION['name']);
						session('name', $_POST['name']);
					}
					$data_img = $thumb_path ? $thumb_path : $user['thumb_path'];
					$this->ajaxReturn($data_img,'修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败！',0);
			}
		}
	}

	/**
	 * 获取负责人列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function listdialog(){
		if ($this->isPost()) {
			$p = intval($_POST['p']) ? : 1;
			$by = $_POST['by'] ? trim($_POST['by']) : '';
			$field = $_POST['field'] ? trim($_POST['field']) : '';
			$d_role = D('RoleView');
			$where = array();
			$below_ids = getSubRoleId();

			if ($p == 1) {
				$m_role_department = M('RoleDepartment');
				//获取部门列表
				$departments = $m_role_department->select();
				$department_id = M('Position')->where('position_id = %d', session('position_id'))->getField('department_id');
				$departmentList[] = $m_role_department->where('department_id = %d', $department_id)->find();
				$departmentList = array_merge($departmentList, getSubDepartment($department_id,$departments,''));
				$data['departmentList'] = $departmentList;
			}			
			if ($_POST['department_id']) {
				$where['position.department_id'] = $_POST['department_id'];
			}
			$where['user.role_id'] = array('in',implode(',', $below_ids));
			$where['user.status'] = '1';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			
			if ($by == 'examine') {
				$position_ids = M('Permission')->where(array('url'=>'examine/add_examine'))->getField('position_id',true);
				array_unshift($position_ids,"1");
				unset($where['user.role_id']);
				$where['role.position_id'] = array('in',$position_ids);

				$role_list = $d_role->where($where)->order('role_id')->page($p.',10')->field('role_id,user_name,thumb_path')->select();
			} elseif ($by == 'task') {
				$defaultinfo = M('Config')->where('name = "defaultinfo"')->getField('value');
				$defaultinfo = unserialize($defaultinfo);
				$task_model = $defaultinfo['task_model'] == 2 ? 1 : 0; //2随意分配，1只分配给下级

				unset($where['user.role_id']);
				if (!$task_model && !session('?admin') && $field != 'owner_role_id') {
					$where['user.role_id'] = array('in',getSubRoleId(true));
				}
				$role_list = $d_role->where($where)->order('role_id')->page($p.',10')->field('role_id,user_name,thumb_path,department_name')->select();
			} else {
				$role_list = $d_role->where($where)->order('role_id')->page($p.',10')->field('role_id,user_name,thumb_path,department_name')->select();
			}
			$role_list = empty($role_list) ? array() : $role_list;
			$count = $d_role->where($where)->count();
			$page = ceil($count/10);
			$data['list'] = $role_list;
			$data['page'] = $page;
			$data['info'] = 'success'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 通讯录列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function contacts(){
		if ($this->isPost()) {
			//导入汉字类
			import('@.ORG.GetPY');
			//实例化
			$py = new GetPY();
			//员工详情
			if ($_POST['from_role_id']) { 
				$user_info = D('RoleView')->where(array('role_id'=>intval($_POST['from_role_id'])))->field('email,telephone,address,user_name,sex,role_id,department_name,role_name,img')->find();
				$user_info['img'] = empty($user_info['img']) ? '' : $user_info['img'];
				$user_info = empty($user_info) ? array() : $user_info;
				$this->ajaxReturn($user_info,'success',1);
			}
			$d_role = D('RoleView');

			//部门下所有员工列表
			if ($_POST['department_id']) {
				if ($_REQUEST["name"]) {
					$where['user_name'] = array('like','%'.$_REQUEST["name"].'%');
				}
				$where['status'] = 1;
				$where['position.department_id'] = intval($_POST['department_id']);
				$role_list = $d_role->where($where)->field('user_name,role_id')->select();

			} elseif ($_POST['by'] == 'sub'){
				//返回当前用户下属列表
				$below_ids = getSubRoleId(session('role_id'),false);
				$role_list = empty($below_ids) ? array() : $below_ids;
			} else {
				//返回所有部门以及默认当前登录用户所属部门员工列表
				$role_list = M('User')->where(array('status'=>'1'))->field('full_name,role_id')->select();
			}

			$new_list = array();
			foreach ($role_list as $k=>$v) {
				$first_char = '';
				//传入名称 返回汉字首字母
				$first_char = $py->getFirstPY($v['full_name']);
				$new_list[$first_char][] = $v['role_id'];
			}
			//对数据进行ksort排序，以key的值升序排序
			ksort($new_list); 

			$user_list = array();
			$i = 0;
			foreach ($new_list as $k=>$v) {
				$user_list[$i]['name'] = $k;
				$role_array = array();
				foreach ($v as $key=>$val) {
					$role_info = array();
					$role_info = $d_role->where('role.role_id = %d',$val)->field('full_name,role_id,thumb_path,telephone,role_name,department_name')->find();
					$role_array[] = $role_info;
				}
				$user_list[$i]['role_array'] = $role_array;
				$i++;
			}

			//部门信息
			$departments_list = M('roleDepartment')->field('department_id,name')->select();
			$department_id = $d_role->where('role.role_id = %d', session('role_id'))->getField('department_id');
			if ($departments_list) {
				foreach ($departments_list as $k=>$v) {
					if ($v['department_id'] == $department_id) {
						$departments_list[$k]['check'] = 'check';
					} else {
						$departments_list[$k]['check'] = ' ';
					}
				}
			}
				
			$data['departments_list'] = $departments_list ? $departments_list : array();
			$data['user_list'] = $user_list ? $user_list : array();
			$this->ajaxReturn($data,'success',1);
		}
	}

	/**
	 * 选择人员（签到）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function checkuser(){
		if ($this->isPost()) {
			$below_ids = getPerByAction('sign','index');
			$module = $_POST['module'] ? trim($_POST['module']) : 'sign';
			$d_role = D('RoleView');
			$m_role_department = M('RoleDepartment');
			//判断权限
			if ($module == 'sign') {
				$per = checkPerByAction('sign','index');
			}
			//根据权限返回部门数组
			if ($_POST['department_id']) {
				$de_parent_id = intval($_POST['department_id']);
			} else {
				if (session('?admin')) {
					$de_parent_id = '0';
				} else {
					switch($per){
						//1自己和下属,2所有人,3仅自己,4部门所有人
						case 2: $de_parent_id = 0; break;
						//部门所有人
						default : $de_parent_id = $d_role->where('role.role_id = %d',session('role_id'))->getField('department_id'); break;
					}
				}
			}
			$sub_department_list = $m_role_department->where(array('parent_id'=>$de_parent_id))->field('department_id,name')->select();
			//部门人数
			foreach ($sub_department_list as $k=>$v) {
				$where_role = array();
				$where_role['position.department_id'] = $v['department_id'];
				//权限范围role_id数组
				$where_role['role.role_id'] = array('in',$below_ids);
				$role_count = $d_role->where($where_role)->count();
				$sub_department_list[$k]['role_count'] = $role_count ? $role_count : '0';
				//是否有子部门或是否本部门有员工
				$is_sub = $m_role_department->where(array('parent_id'=>$v['department_id']))->count();
				$sub_department_list[$k]['is_sub'] = $is_sub || $role_count ? '1' : '0';
			}
			$where_role = array();
			$where_role['position.department_id'] = $de_parent_id;
			//权限范围role_id数组
			$where_role['role.role_id'] = array('in',$below_ids);
			$role_list = $d_role->where($where_role)->field('role_id,user_name,thumb_path,department_name')->select();

			$data['sub_department_list'] = $sub_department_list ? $sub_department_list : array();
			$data['role_list'] = $role_list ? $role_list : array();
			$this->ajaxReturn($data,'success',1);
		}
	}
}