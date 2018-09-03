<?php 
/**
 * User Related
 * 用户相关模块
 *
 **/ 

class UserAction extends Action {

	public function _initialize(){
		$action = array(
			'permission'=>array('login','lostpw','resetpw','active','notice','loginajax'),
			'allow'=>array('logout','role_ajax_add','getrolebydepartment','dialoginfo','edit', 'listdialog', 'mutilistdialog', 'getrolelist', 'getpositionlist','changecontent','getactionauthority','department','userimg','contacts','yanchong','editpassword','getpositionlistbydepartment')
		);
		B('Authenticate', $action);
	}
	/**
	 *员工角色
	 *
	**/
	public $user_type = array('0'=>'销售角色','1'=>'财务角色','2'=>'行政角色','99'=>'其他');

	//登录
	public function login() {
		//手机访问跳转
		if (isMobile()) {
			$mobile = str_replace('index.php', 'mobile', $_SERVER["PHP_SELF"]);
			header("Location: http://".$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"].$mobile);
		}
		$m_announcement = M('announcement');
		$where['status'] = array('eq', 1);
		$where['isshow'] = array('eq', 1);
		$this->announcement_list = $m_announcement->where($where)->order('order_id')->select();
		$this->carousel = M('carouselImages')->order('listorder')->select();
		if (session('?name')){
			$this->redirect('index/index',array(), 0, '');
		}elseif($this->isPost()){
			if((!isset($_POST['name']) || $_POST['name'] =='')||(!isset($_POST['password']) || $_POST['password'] =='')){
				alert('error', L('INVALIDATE_USER_NAME_OR_PASSWORD'),$_SERVER['HTTP_REFERER']); 
			}elseif (isset($_POST['name']) && $_POST['name'] != ''){
				$m_user = M('user');
				$user = $m_user->where(array('name' => trim($_POST['name'])))->find();
				if ($user['password'] == md5(trim($_POST['password']) . $user['salt'])) {				
					if (-1 == $user['status']) {
						alert('error', L('YOU_ACCOUNT_IS_UNAUDITED'));
					} elseif (0 == $user['status']) {
						alert('error', L('YOU_ACCOUNT_IS_AUDITEDING'));
					}elseif (2 == $user['status']) {
						alert('error', L('YOU_ACCOUNT_IS_DISABLE'),U('user/login'));
					}else {
						$d_role = D('RoleView');
						$role = $d_role->where('user.user_id = %d', $user['user_id'])->find();
						if ($_POST['autologin'] == 'on') {
							session(array('expire'=>259200));
							cookie('user_id',$user['user_id'],259200);
							cookie('name',$user['name'],259200);
							cookie('salt_code',md5(md5($user['user_id'] . $user['name']).$user['password']),259200);
						}else{
							session(array('expire'=>3600));
						}
						if (!is_array($role) || empty($role)) {
							alert('error', L('HAVE_NO_POSITION')); 
						} else {
							if(3 == $user['status']){
								$m_user ->where('user_id =%d',$user['user_id'])->setField('status',1);
							}
							if($user['category_id'] == 1){
								session('admin', 1);
							}
							session('role_id', $role['role_id']);
							if($user['img']){
								session('user_img', $user['img']);
							}
							session('full_name',$user['full_name']);
							session('position_id', $role['position_id']);
							session('role_name', $role['role_name']);
							session('department_id', $role['department_id']);
							session('name', $user['name']);
							session('user_id', $user['user_id']);

							if (C('CALL_CENTER') == 1) {
								session('extid', $user['extid']); //坐席号
							}

							//解决升级出错，造成的下次联系时间重复问题
							$m_fields = M('Fields');
							if ($m_fields->where(array('model'=>'customer','field'=>'nextstep_time','name'=>'下次联系时间'))->count() > 1) {
								$fields_list = $m_fields->where(array('model'=>'customer','field'=>'nextstep_time','name'=>'下次联系时间'))->select();
								foreach ($fields_list as $k=>$v) {
									if ($m_fields->where(array('model'=>'customer','field'=>'nextstep_time','name'=>'下次联系时间'))->count() > 1) {
										$m_fields->where(array('field_id'=>$v['field_id'],'model'=>'customer','field'=>'nextstep_time'))->delete();
									}
								}
							}

							// 忽略登录状态
                            cookie('remb_un',null); // 清空
                            cookie('remb_up',null); // 清空
                            // 记住密码
                            if ( $_POST['remember'] )
                            {
                                // 有效期为一年
                                cookie('remb_un',base64_encode($_POST['name']),31536000);
                                cookie('remb_up',base64_encode($_POST['remb_up']),31536000);
                            }

							alert('success', L('LOGIN_SUCCESS'), U('index/index'));		
						}
					}
				} else {
					alert('error', L('INCORRECT_USER_NAME_OR_PASSWORD'), $_SERVER['HTTP_REFERER']); 				
				}
			}
		}else{
			$config = unserialize(M('Config')->where('name = "defaultinfo"')->getField('value'));
			// $this->logo = $config['logo_thumb_path']; //缩略图
			$this->logo = $config['logo'];
			$this->alert = parseAlert();
			$this->display();
		}
	}
	//找回密码
	public function lostpw() {
		if($this->isPost()){
			if ($_POST['name'] || $_POST['email']){
				$user = M('User');
				if ($_POST['name']){
					$info = $user->where('name = "%s"',trim($_POST['name']))->find();
					if(!isset($info) || $info == null){
						$this->error(L('NOT_FIND_USER_NAME'));
					}
				} elseif ($_POST['email']){
					$info = $user->where('email = "%s"',trim($_POST['email']))->find();
					if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['email'])){
						if (!isset($info) || $info == null){
							$this->error(L('EMAIL_NOT_BE_USEED'));
						}
					}else{
						$this->error(L('INVALIDATE_EMAIL'));
					}					
				}				
				$time = time();				
				$user->where('user_id = ' . $info['user_id'])->save(array('lostpw_time' => $time));
				$verify_code = md5(md5($time) . $info['salt']);
				C(F('smtp'),'smtp');
				import('@.ORG.Mail');
				$url = U('user/resetpw', array('user_id'=>$info['user_id'], 'verify_code'=>$verify_code),'','',true);
				$content = L('FIND_PASSWORD_EMAIL' ,array($_POST['name'] , $url));
				if (SendMail($info['email'],L('FIND_PASSWORD_LINK'),$content,L('MXCRM_ADMIN'))){
					$this->success(L('SEND_FIND_PASSWORD_EMAIL_SUCCESS'));
				}
			} else {
				$this->error(L('INPUT_USER_NAME_OR_EMAIL'));
			}
		} else{
			if (!F('smtp')) {
				$this->error(L('CAN_NOT_USER_THIS_FUNCTION_FOR_NOT_SET_SMTP'));
			}
			$config = unserialize(M('Config')->where('name = "defaultinfo"')->getField('value'));
			$this->logo = $config['logo'];
			$this->alert = parseAlert();
			$this->display();			
		}
	}
	//密码重置
	public function resetpw(){
		$verify_code = trim($_REQUEST['verify_code']);
		$user_id = intval($_REQUEST['user_id']);
		$m_user = M('User');
		$user = $m_user->where('user_id = %d', $user_id)->find();
		if (is_array($user) && !empty($user)) {
			if ((time()-$user['lostpw_time'])>86400){
				alert('error', L('LINK_DISABLE_PLEASE_FIND_PASSWORD_AGAIN'),U('user/lostpw'));
			}elseif (md5(md5($user['lostpw_time']) . $user['salt']) == $verify_code) {
				if ($_REQUEST['password']) {
					$password = md5(md5(trim($_REQUEST["password"])) . $user['salt']);
					$m_user->where('user_id = %d', $user_id)->save(array('password'=>$password, 'lostpw_time'=>0));
					alert('success', L('EDIT_PASSWORD_SUCCESS_PLEASE_LOGIN'), U('user/login'));
				} else {
					$this->alert = parseAlert();
					$this->display();
				}
			} else{
				$this->error(L('FIND_PASSWORD_LINK_DISABLE'));
			}		
		} else {
			$this->error(L('FIND_PASSWORD_LINK_DISABLE'));
		}
	}
	
	//退出
	public function logout() {
		session(null);
		cookie('user_id',null);
		cookie('name',null);
		cookie('salt_code',null);
		F('img_id',null);
		alert('success','',U('User/login'));
		// $this->success(L('LOGIN_OUT_SUCCESS'), U('User/login'));
	}
	
	public function listDialog() {
		//1表示所有人  2表示下属
		if($_GET['by'] == 'task'){
			$all_or_below = C('defaultinfo.task_model') == 2 ? 1 : 0;
		}else{
			$all_or_below = ($_GET['by'] == 'all' || $_GET['by'] == 'message' || session('?admin')) ? 1 : 0;
		}
		$this->by = trim($_GET['by']) ? : '';
		$d_role = D('RoleView');
		$m_role_department = M('RoleDepartment');
		$where = '';
		import("@.ORG.DialogListPage");
		$p = intval($_GET['p']) ? : 1;

		//部门查询
		if($_GET['d_department'] != '' && $_GET['d_department'] != 'all'){
			$department_id = $_GET['d_department'];
			$where['position.department_id'] = $department_id;
		}
		if($_GET['d_name']){
			$where['user.full_name'] = array('like', '%'.trim($_GET['d_name']).'%');
		}
		$role_arr = array();

		if ($all_or_below == 1) {
			$role_arr = getSubRoleId(true,1);
		} else {
			$role_arr = getSubRoleId(true);
		}
		$where['user.role_id'] = array('in', $role_arr);
		$where['user.status'] = array('eq',1);
		//审批审核权限
		if($_GET['by'] == 'examine'){
			$position_ids = M('Permission')->where(array('url'=>'examine/add_examine'))->getField('position_id',true);
			array_unshift($position_ids,'1');
			unset($where['user.role_id']);
			$where['role.position_id'] = array('in',$position_ids);
		}
		//合同审核权限
		if($_GET['by'] == 'contract'){
			$position_ids = M('Permission')->where(array('url'=>'contract/check'))->getField('position_id',true);
			array_unshift($position_ids,'1');
			unset($where['user.role_id']);
			$where['role.position_id'] = array('in',$position_ids);
		}
		$role_list = $d_role->where($where)->page($p.',10')->order('role_id')->select();
		$count = $d_role->where($where)->count();
		
		$departments = $m_role_department->select();
		$department_id = M('Position')->where('position_id = %d', session('position_id'))->getField('department_id'); 
		$departmentList[] = $m_role_department->where('department_id = %d', $department_id)->find();
		$departmentList = array_merge($departmentList, getSubDepartment($department_id,$departments,''));
		$this->assign('departmentList', $departmentList);

		$Page = new Page($count,10);
		$this->assign('page', $Page->show());
		$this->search_field = $_REQUEST;//搜索信息
		$this->role_list = $role_list;
		$this->display();
	}
	
	public function mutiListDialog(){
		//1表示所有人  2表示下属
		if($_GET['by'] == 'task'){
			$defaultinfo = M('Config')->where('name = "defaultinfo"')->getField('value');
			$defaultinfo = unserialize($defaultinfo);
			$all_or_below = $defaultinfo['task_model'] == 2 ? 1 : 0;
		}else{
			$all_or_below = $_GET['by'] == 'all' ? 1 : 0;
		}
		$d_role = D('RoleView');
		$sub_role_id = getSubRoleId(true);
		$departments_list = M('roleDepartment')->select();	
		foreach($departments_list as $k=>$v){
			$where = array();
			if(!$all_or_below && !session('?admin')){
				$where['role_id'] = array('in', $sub_role_id);
			}
			$where['status'] = 1;
			$where['position.department_id'] =  $v['department_id'];
			$roleList = $d_role->where($where)->select();
			$departments_list[$k]['user'] = $roleList;
		}
		$this->departments_list = $departments_list;
		$this->display();
	}
	
	//停用(启用)账号
	public function delete(){
		$m_user = M('user');
		if($this->isAjax()){
			if(!session('?admin') && !checkPerByAction('user','edit')){
				$this->ajaxReturn('','您没有此权限！',0);
			}
			$user_ids = $_POST['user_id'];
			if(!$user_ids){
				$this->ajaxReturn('','请先选择需操作的用户！',0);
			}
			$del_ids = array();
			$add_ids = array();
			foreach($user_ids as $k=>$v){
				//判断是否管理员（管理员账户不能停用）
				$user_info = array();
				$user_info = $m_user->where(array('user_id'=>$v))->field('status,category_id')->find();
				if($user_info['status'] == 2){
					$add_ids[] = $v;
				}else{
					if($user_info['category_id'] != 1){
						$del_ids[] = $v;
					}
				}
			}
			if($add_ids){
				$res_add = $m_user->where(array('user_id'=>array('in',$add_ids)))->setField('status',1);
			}
			if($del_ids){
				$res_del = $m_user->where(array('user_id'=>array('in',$del_ids)))->setField('status',2);
			}
			if($res_add || $res_del){
				$this->ajaxReturn('','操作成功！',1);
			}else{
				$this->ajaxReturn('','操作失败，请重试！',0);
			}
		}else{
			$this->ajaxReturn('','您没有此权限！',0);
		}
	}
	
	//修改头像
	public function userimg() {
		//dump($_POST);dump($_FILES);die();		
		if ($this->isAjax() && $_FILES['blob']['error'] != 4) {
			if(intval($_POST['user_id'])){
				if(session('?admin')){
					$user_id = intval($_POST['user_id']);
				}else{
					if(session('user_id') != intval($_POST['user_id'])){
						if(!session('?admin') && !checkPerByAction('user','edit')){
							// alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),$_SERVER['HTTP_REFERER']);
							$ajaxRet['status'] = 4;
							$ajaxRet['msg'] = '您没有此权限！';
							$this->ajaxReturn($ajaxRet);
						}else{
							$user_id = intval($_POST['user_id']);
						}
					}else{
						$user_id = session('user_id');
					}
				}
			}else{
				$user_id = session('user_id');
			}
			
			$ajaxRet = array();
			// dump($_FILES['img']);die;
			import('@.ORG.UploadFile');
			import('@.ORG.Image');//引入缩略图类
			$Img = new Image();//实例化缩略图类
			$upload = new UploadFile();
			$upload->maxSize = 20000000;
			$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
			$dirname = UPLOAD_PATH.'head/';
			$upload->thumb = true;//生成缩图
			$upload->thumbRemoveOrigin = false;//是否删除原图
			
			if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
				$ajaxRet['status'] = 2;
				$ajaxRet['msg'] = '创建文件夹失败！';
				$this->ajaxReturn($ajaxRet);
			}
			$upload->savePath = $dirname;
			if(!$upload->upload()) {
				$ajaxRet['status'] = 3;
				$ajaxRet['msg'] = $upload->getErrorMsg();
				$this->ajaxReturn($ajaxRet);
			}else{
				$info =  $upload->getUploadFileInfo();
			}		

			if(is_array($info[0]) && !empty($info[0])){
				$upload = $dirname . $info[0]['savename'];
			}else{
				$ajaxRet['status'] = 4;
				$ajaxRet['msg'] = '头像提交失败！';
				$this->ajaxReturn($ajaxRet);
			}
			$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);
			
			$thumb_old = M('User')->where('user_id =%d', $user_id)->getField('img');
			//保存 上传的图片
			$img_thumb['img'] = $upload; 
			$img_thumb['thumb_path'] = $thumb_path;

			$result = M('user')->where('user_id =%d',$user_id)->setField($img_thumb);

			//end 
			if($result){
				unlink($thumb_old);
				session('user_img', $upload);
				$ajaxRet['status'] = 1;
				$ajaxRet['msg'] = '头像设置成功！';
				$this->ajaxReturn($ajaxRet);
			}else{
				$ajaxRet['status'] = 5;
				$ajaxRet['msg'] = '头像设置失败！';
				$this->ajaxReturn($ajaxRet);
			}
		}else{
			$ajaxRet['status'] = 0;
			$ajaxRet['msg'] = '数据无变化';
			$this->ajaxReturn($ajaxRet);
		}
	}
	//修改密码
	public function editPassword(){
		$m_user = M('user');
		if($this->isAjax()){
			if(intval($_POST['user_id']) == session('user_id')){
				$user_id = session('user_id');
			}else{
				if(session('?admin') || checkPerByAction('user','edit')){
					$user_id = intval($_POST['user_id']);
				}else{
					$this->ajaxReturn('','您没有此权限！',0);
				}
			}
			$old_password = trim($_POST['old_password']);
			$new_password = trim($_POST['new_password']);
			$confirm_password = trim($_POST['confirm_password']);
			if($new_password != $confirm_password){
				$this->ajaxReturn('','两次密码不一致！',0);
			}
			$user_info = $m_user ->where('user_id =%d',$user_id)->find();



			if($user_info['password'] == md5($old_password.$user_info['salt'])){
				$password = md5($new_password.$user_info['salt']);
				$result = $m_user ->where('user_id =%d',$user_id)->setField('password',$password);
				if($result){
					$this->ajaxReturn('','密码修改成功！请重新登录！',1);
				}else{
					$this->ajaxReturn('','数据无变化！',0);
				}
			}else{
				$this->ajaxReturn('','原密码不正确！',0);
			}
		}
	}
	
	//修改自己的信息
	public function edit(){
		if ($this->isPost()) {
            if(!session('?admin') && (session('user_id') != $_POST['user_id'])){
            	if(!checkPerByAction(MODULE_NAME,ACTION_NAME)){
            		if(checkPerByAction('user','index')){
						alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),U('user/index'));
            		}else{
            			alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),U('index/index'));
            		}
				}
            }
			if ($_POST['email'] && !ereg('^[_\.0-9a-zA-Z]+@([0-9a-zA-Z][A-Za-z0-9_-]+\.)+[a-zA-Z]{2,4}$', $_POST['email'])){
				$this->error(L('INVALIDATE_EMAIL'));
			}
            if ($_POST['telephone'] && !ereg('^1[34758][0-9]{9}$', $_POST['telephone'])){
				$this->error(L('INVALIDATE_TELEPHONE'));
			}
            $m_user = M('User');
			$m_role = M('Role');
			$user = $m_user->where('user_id = %d', intval($_POST['user_id']))->find();
			$new_number = trim($_POST['prefixion']).trim($_POST['number']);
			if($user['number'] != $new_number){
				$result = $m_user->where(array('number'=>$new_number,'user_id'=>array('neq',intval($_POST['user_id']))))->find();
				if($result){
					alert('error','员工编号不能重复！',$_SERVER['HTTP_REFERER']);
				}
			}
			if ($user['name'] !== trim($_POST['name'])) {
				$name_result = $m_user->where(array('name'=>trim($_POST['name']),'user_id'=>array('neq',intval($_POST['user_id']))))->find();
				if($name_result){
					alert('error','登录账号不能重复！',$_SERVER['HTTP_REFERER']);
				}
			}

			//检查坐席号
			$extid = intval($_POST['extid']);
			if (!empty($extid)) {
				$extid_user = $m_user->where('extid = %d', $extid)->find();
				if ($user['extid'] != $extid && $extid_user) {
					$m_user->where('extid = %d', $extid)->setField('extid', 0);
				}
			}
			
			if ($m_user->create()) {
				
				//权限控制
				$is_update = false;
				if (session('?admin') || checkPerByAction(MODULE_NAME,ACTION_NAME)) {
					$m_user->name = trim($_POST['name']);
					$m_user->prefixion = trim($_POST['prefixion']);
					$m_user->number = $new_number;
					$is_update = $m_role->where('user_id = %d', intval($_POST['user_id']))->setField('position_id', $_POST['position_id']);
				} else {
					unset($m_user->$user['name']);
					unset($m_user->$user['prefixion']);
					unset($m_user->$user['number']);
					unset($m_user->$user['type']);
				}
				
				unset($m_user->category_id);
				if(isset($_POST['password']) && $_POST['password']!=''){
					$m_user->password = md5(md5(trim($_POST["password"])) . $user['salt']);
				} else {
					unset($m_user->password);
				}

				//判断是否管理员（管理员账户不能停用）
				if($user['category_id'] == 1){
					unset($m_user->status);
					unset($m_user->name);   //演示站点
				}
				
				$m_user->birthday = $this->_post('birthday','trim');
				$m_user->entry = $this->_post('entry','trim');
				if($m_user->save() || $is_update){
					actionLog($_POST['user_id']);
					if($_POST['user_id'] ==session('user_id')){
						unset ($_SESSION['name']) ;
						session('name', trim($_POST['name']));
					}
					if(session('?admin')){
						alert('success',L('EDIT_USER_INFO_SUCCESS'),U('user/index'));
					}else{
						if(strstr($_POST['r_url'],'a=')){
							alert('success',L('EDIT_USER_INFO_SUCCESS'),U('user/edit','id='.$_POST['user_id']));
						}else{
							alert('success',L('EDIT_USER_INFO_SUCCESS'),$_POST['r_url']);
						}
					}
				}else{
					alert('error',L('USER_INFO_NOT_CHANGE'),$_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error',L('EDIT_USER_INFO_FAILED'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			$user_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : session('user_id');
            if(!session('?admin') && (session('user_id') != $user_id)){
				if(!checkPerByAction(MODULE_NAME,ACTION_NAME)){
					if(checkPerByAction('user','index')){
						alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),U('user/index'));
            		}else{
            			alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),U('index/index'));
            		}
				}
            }
            //是否有编辑登录名和员工编号的权限
            $is_edit = 0;
            if(session('?admin') || checkPerByAction(MODULE_NAME,ACTION_NAME)){
            	$is_edit = 1;
            }
            $this->is_edit = $is_edit;
			$d_user = D('RoleView');
			$user = $d_user->where('user.user_id = %d', $user_id)->find();
			$user['number'] = str_replace($user['prefixion'],'',$user['number']);
			if($user['birthday'] == '0000-00-00'){
				$user['birthday'] = '';
			}
			if($user['entry'] == '0000-00-00'){
				$user['entry'] = '';
			}
			$user['category'] = M('user_category')->where('category_id = %d', $user['category_id'])->getField('name');
			$this->categoryList = M('user_category')->select();
			$status_list = array(1=>'启用', 2=>'停用',3=>'未激活');
			$this->assign('statuslist', $status_list);
			if($user['department_id']){
				$this->position_list = M('position')->where('department_id = %d', $user['department_id'])->select();
			}
			$department_list = getSubDepartment(0, M('role_department')->select());
			$this->assign('department_list', $department_list);
			
			//判断是否启用客户数限制
			$customer_num = M('config')->where('name="opennum"')->getField('value');
			$this->customer_num = $customer_num;
			//员工角色
			$user_type_list = $this->user_type;
			$this->user_id = $user_id;
			$this->assign('user_type_list',$user_type_list);
			$user['type_name'] = $user_type_list[$user['type']];
			$this->user = $user;
			$this->r_url = $_SERVER['HTTP_REFERER'];
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	public function dialogInfo(){
		$role_id = intval($_REQUEST['id']);
		$role = D('RoleView')->where('role.role_id = %d', $role_id)->find();
		$user = M('user')->where('user_id = %d', $role['user_id'])->find();
		$user[role] = $role;
		$this->user = $user;
		$this->categoryList = M('user_category')->select();
		$this->alert = parseAlert();
		$this->display();
	}

	
	public function changeContent(){
		if($this->isAjax()){
			$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
			$d_role_view = D('RoleView');
			$where = '';
			if($_GET['department'] == 'all'){
				$department_id = array('in',M('RoleDepartment')->getField('department_id',true));
			}else{
				$department_id = $this->_get('department');
			}
			if($_GET['by'] == 'all'){
				$departRoleArr = getRoleByDepartmentId($department_id,$sub=true);
			}elseif($_GET['by'] == 'examine'){
				$departRoleArr = getRoleByDepartmentId($department_id,$sub=true);
				$position_ids = M('Permission')->where("url = 'examine/add_examine'")->getField('position_id',true);
			}elseif($_GET['by'] == 'message'){
				$departRoleArr = getRoleByDepartmentId($department_id,$sub=true);
			}else{
				$departRoleArr = getRoleByDepartmentId($department_id);
			}

			$departRoleIdArr = array();
			if($_GET['by'] == 'examine'){
				foreach($departRoleArr as $k=>$v){
					if(in_array($v['position_id'],$position_ids)){
						$departRoleIdArr[] = $v['role_id'];
					}elseif($v['category_id'] == 1){
						$departRoleIdArr[] = $v['role_id'];
					}
				}
			}else{
				foreach($departRoleArr as $k=>$v){
					$departRoleIdArr[] = $v['role_id'];
				}
			}
			$where['status'] = array('eq', 1);
			$where['role_id'] = array('in', $departRoleIdArr);
			if($this->_get('name','trim') == ''){
				$list = $d_role_view->where($where)->order('role_id')->page($p.',10')->select();
				$data['list'] = $list;
				$count = $d_role_view->where($where)->count();
			}else{
				$where['user.name'] = array('like', '%'.trim($_GET['name']).'%');
				$list = $d_role_view->where($where)->order('role_id')->page($p.',10')->select();
				$count = $d_role_view->where($where)->count();
				$data['list'] = $list;
			}
			$data['p'] = $p;
			$data['count'] = $count ? $count : 0;
			$data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
			$this->ajaxReturn($data,"",1);
		}
	}
	
	public function getPositionlistByDepartment(){
		if($_GET['id']){
			$m_position = M('Position');
			$res_list = array();
			$department_id = intval($_GET['id']);
			$position_ids = $m_position->where('department_id = %d', $department_id)->getField('position_id',true);
			$subpositiontree = '';
			foreach($position_ids as $v){
				$charge_position = $m_position->where('position_id = %d', $v)->find();
				if(!in_array($charge_position['parent_id'],$position_ids)){

					$role_list = getSubPosition($v, $m_position->where('department_id = %d', $department_id)->select(), '--');
					array_unshift($role_list, array('position_id'=>$charge_position['position_id'], 'name'=>$charge_position['name']));
					if($role_list) $res_list = array_merge($res_list, $role_list);
				}
			}
			$this->ajaxReturn($res_list, L('GET_SUCCESS'), 1);
		}else{
			$this->ajaxReturn(array(), L('SELECT_DEPARTMENT_FIRST'), 0);
		}
	}

	public function getRoleByPosition(){
		if($this->isAjax()){
			$position_id = $this->_get('position_id','intval');
			$role_ids = M('Role')->where('position_id = %d',$position_id)->select();
			$m_user = M('User');
			$role_list = array();
			foreach($role_ids as $k=>$v){
				$user_info = $m_user->where('role_id = %d',$v['role_id'])->field('role_id,full_name,status')->find();
				if($user_info['status'] == 1){
					$role_list[] = $user_info;
				}
			}
			$this->ajaxReturn($role_list,'',1);
		}
	}

	public function getPositionList() {
		if($_GET['id']){
			$m_position = M('position');
			$where['department_id'] = $_GET['id'];
			$position_list = getSubPosition(session('position_id'), $m_position->where($where)->select());
			$position_id_array = array();
			$position_id_array[] = session('position_id');
			foreach($position_list as $k => $v){
				$position_id_array[] = $v['position_id'];
			}
			if(!session('?admin')){
				$where['position_id'] = array('in', implode(',', $position_id_array));
			}
			$role_list = $m_position->where($where)->select();
			$this->ajaxReturn($role_list, L('GET_SUCCESS'), 1);
		}else{
			$this->ajaxReturn(array(), L('SELECT_DEPARTMENT_FIRST'), 0);
		}
	}
	
	public function active() {
		$verify_code = trim($_REQUEST['verify_code']);
		$user_id = intval($_REQUEST['user_id']);
		$m_user = M('User');
		$user = $m_user->where('user_id = %d', $user_id)->find();
		if (is_array($user) && !empty($user)) {
			if (md5(md5($user['reg_time']) . $user['salt']) == $verify_code) {
				if ($_REQUEST['password']) {
					$password = md5(md5(trim($_REQUEST["password"])) . $user['salt']);
					$m_user->where('user_id =' . $_REQUEST['user_id'])->save(array('password'=>$password,'status'=>1, 'reg_time'=>time(), 'reg_ip'=>get_client_ip()));
					alert('success', L('SET_PASSWORD_SUCCESS_PLEASE_LOGIN'), U('user/login'));
				} else {
					$this->alert = parseAlert();
					$this->display();
				}
			} else {
				$this->error(L('FIND_PASSWORD_LINK_DISABLE'));
			}
		} else {
			$this->error(L('FIND_PASSWORD_LINK_DISABLE'));
		}
	}
	
	public function view(){
		if($this->isGet()){
			$user_id = isset($_GET['id']) ? $_GET['id'] : 0;
			$d_user = D('RoleView');
			$user = $d_user->where('user.user_id = %d', $user_id)->find();
			$this->categoryList = M('UserCategory')->select();
			$this->user = $user;
			//判断是否启用客户数限制
			$customer_num = M('config')->where('name="opennum"')->getField('value');
			$this->customer_num = $customer_num;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	public function index(){
		if(!in_array(session('role_id'), getPerByAction('user','index'))) {
			alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
		}
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$status = isset($_GET['status']) ? intval($_GET['status']) : '';
		// $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$d_user = D('UserView'); // 实例化User对象
		$m_user = M('User');
		// if(!session('?admin')){
		// 	$where['role_id'] = array('in', getSubRoleId(true));
		// }
		if($status){
			$where['status'] = $status;
			$params[] = "status=".$status;
		}
		if(trim($_GET['search'])){
			$where['full_name'] = array('like','%'.trim($_GET['search']).'%');
			$params[] = "search=".trim($_GET['search']);
		}
		// if($id){
		// 	$where['category_id'] = $id;
		// 	$params[] = "status=".$status;
		// }
		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=".$listrows;
		}
		import('@.ORG.Page');// 导入分页类
		$count = $m_user->where($where)->count();
		$p_num = ceil($count/$listrows);
		if($p_num<$p){
			$p = $p_num;
		}
		$this->parameter = implode('&', $params);
		
		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		$Page->parameter = implode('&', $params);
		$show  = $Page->show();// 分页显示输出
		$user_list = M('User')->order('field(status,1,3,2),reg_time asc')->where($where)->page($p.','.$listrows)->select();
		foreach($user_list as $k=>$v){
			$user_list[$k]['role_info'] = $d_user->where('user.user_id = %d',$v['user_id'])->find();
			//角色
			$type_name = '';
			switch($v['type']){
				case 1 : $type_name = '销售角色';break;
				case 2 : $type_name = '财务角色';break;
				case 3 : $type_name = '行政角色';break;
				case 100 : $type_name = '其他';break;
			}
			$user_list[$k]['type_name'] = $type_name;
		}
		$this->assign('user_list',$user_list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->assign("listrows",$listrows);
		$category = M('user_category');
		$this->categoryList = $category->select();
		$this->alert = parseAlert();
		$this->display();
	}
	
	public function department(){
		$role_vip = M('Config')->where('name = "role_vip"')->getField('value');
		$role_vip_ids = array_filter(explode(',',$role_vip)); 
		
		if(!session('?admin') && !in_array(session('role_id'),$role_vip_ids)){
			alert('error',L('YOU_HAVE_NO_PERMISSION'),U('index/index'));
		}

		$department_id = intval($_GET['department_id']) ? intval($_GET['department_id']) : 0;
		$department_tree = getSubDepartmentTreeCode(0,1);

		if($department_id){
			$this->department = $dep = M('RoleDepartment')->where('department_id = %d', $department_id)->find();
			$this->role_count = $pos = M('position')->where('department_id = %d', $department_id)->count();
			$position_ids = M('position')->where('department_id = %d', $dep['department_id'])->getField('position_id',true);
			$subpositiontree = '';
			foreach($position_ids as $v){
				$parent_id = M('position')->where('position_id = %d', $v)->getField('parent_id');
				if(!in_array($parent_id,$position_ids)){
					$subpositiontree .= getSubPositionTreeCode($v, 1, 1, $department_id);
				}
			}
			$this->assign('position_tree', $subpositiontree);
		}else{
			$position_id = 0;
			$this->assign('position_tree', getSubPositionTreeCode($position_id, 1, 1, $department_id));
		}

		$this->department_tree = $department_tree;
		$this->display();

	}
	//添加部门信息
	public function department_add(){
		
		if($this->isPost()){
			$department = D('RoleDepartment');
			if($department->create()){
				$department->name ? '' :alert('error',L('PLEASE_INPUT_DEPARTMENT_NAME'),$_SERVER['HTTP_REFERER']);
				if($department->add()){
    				$success = array(
    					status => 1,
    					url => U('index'),
    				);
    				$this->success($success);
    				exit;
				}else{
    				$error = array(
    					status => 0,
    					info => $d_department->getError(),
    				);
    				$this->ajaxReturn($error);
				}
			}else{
				$error = array(
					status => 0,
					info => $d_department->getError(),
				);
				$this->ajaxReturn($error);
			}
		}else{
			$department = M('roleDepartment');
			$department_list = $department->select();	
			$this->assign('departmentList', getSubDepartment(0,$department_list,''));
			$this->display();
		}
	}


	/*编辑部门*/
	public function department_edit(){
		if($this->isPost()){
    		$d_department = $_POST['name'] ? D('RoleDepartment') : M('RoleDepartment');
    		//判断是否修改上级部门
    		$old_parent_id = M('RoleDepartment')->where('department_id = %d',$_POST['department_id'])->getField('parent_id');
    		if($d_department->create()){
				//$d_department->update_time = time();
				//$d_department->update_role_id = 1;

    			if($d_department->save()){
    				
    				//如果修改上级部门，则岗位也随着变动（部门下最高岗位的父级ID更改为移动后所在部门的最高岗位）
    				if(!empty($old_parent_id) && $old_parent_id != intval($_POST['parent_id'])){
    					$m_position = M('Position');
    					//移动后部门下最高岗位
						$top_position_id = $m_position->where(array('department_id'=>intval($_POST['parent_id'])))->order('parent_id asc')->getField('position_id');
						//移动前部门下最高岗位
						$parent_id = intval($_POST['parent_id']);
						$top_parent_position_id = $m_position->where(array('department_id'=>intval($_POST['department_id'])))->order('parent_id asc')->getField('position_id');
						//如果移动后部门没有岗位，则岗位为上一级部门的最高岗位（逐层往上一级查询）
						if(!$top_position_id){
							$get_top_position_id = getTopPositionByDepartment(intval($_POST['parent_id']));
							$top_position_id = $get_top_position_id ? $get_top_position_id : 1;
						}
						//改变移动的部门下最高岗位的父级Id
						$res_position = $m_position->where('position_id = %d',$top_parent_position_id)->setField('parent_id',$top_position_id);

    				}
    				$success = array(
    					status => 1,
    					url => U('index'),
    				);
    				$this->success($success);
    				exit;
    			}else{
    				$error = array(
						status => 0,
						info => $d_department->getError(),
					);
					$this->ajaxReturn($error);
    			}
    		}else{
    			$error = array(
					status => 0,
					info => $d_department->getError(),
				);
				$this->ajaxReturn($error);
    		}
    	}else{
			$department = M('roleDepartment');
			$this->assign('department',$department->where('department_id = %d',intval($_GET['id']))->find());

			$department_list = $department->select();	
			//去除自己部门
			foreach($department_list as $key=>$value){
				if($value['department_id'] == intval($_GET['id'])){
					unset($department_list[$key]);
				}
				if($value['parent_id'] == intval($_GET['id'])){
					unset($department_list[$key]);
				}
			}
			$this->assign('department_list', getSubDepartment(0,$department_list,''));
			$this->display();
		}
	}

	/*删除部门*/
	public function department_delete(){
		if(!session('?name') || !session('?user_id')){
			alert(L('PLEASE_LOGIN_FIRSET'),U('User/login/'), 'error');
		}

		$department = M('roleDepartment');
		if(1 == intval($_REQUEST['id'])){
			$error = array(
				status => 0,
				info => '无法删除顶级部门',
			);
			$this->ajaxReturn($error);
		}
		$department_id = intval($_REQUEST['id']); 
		$name = $department->where('department_id = %d', $department_id)->getField('name');
		if($department->where('parent_id=%d', $department_id)->select()){
			$error = array(
				status => 0,
				info => '请先删除“'.$name.'”的下级部门',
			);
			$this->ajaxReturn($error);
		}
		$m_position = M('position');
		if($m_position->where('department_id=%d', $department_id)->select()){
			$error = array(
				status => 0,
				info => '请先删除"'.$name.'"的下级岗位',
			);
			$this->ajaxReturn($error);
		}
		if($department->where('department_id = %d', $department_id)->delete()){
			$success = array(
				status => 1,
				url => U('index'),
			);
			$this->success($success);
		}else{
			$error = array(
				status => 0,
				info => '操作错误',
			);
			$this->ajaxReturn($error);
		}
	}

	/*创建岗位*/
	public function position_create(){
		if($this->isPost()){
			$parent_id = intval($_POST['parent_id']);//I('post.parent_id')
			$department_id = intval($_POST['department_id']);
			$name = trim($_POST['name']);
			$description = trim($_POST['description']);
			$position_data = array(
				'parent_id'=>$parent_id,
				'name'=>$name,
				'description'=>$description,
				'department_id'=>$department_id,
			);

			$d_position = D('Position');
			if($ret_id = $d_position->add($position_data)){
				$this->sReturn();
				exit;
			}
			$this->eReturn($d_position->getError());
		}

		$department_id = intval($_GET['department_id']);
		$department_name = M('RoleDepartment')->where('department_id = '.$department_id)->getField('name');
		$department_list = getSubDepartment(0,M('RoleDepartment')->select(),'');
		$position_list = M('Position')->select();
		$this->assign(array(
				'department_list'=>$department_list,
				'department_id'=>$department_id,
				'department_name'=>$department_name,
				'position_list'=>$position_list,
			));
		$this->display();
	}

	/*修改岗位$this->isPost()*/
	public function position_edit(){
		if($this->isPost()){ 
			$d_position = D('Position');
			if($d_position->create()){
				if(false !== $d_position->save()){
					$this->sReturn();
				}
			}
		}

		$position_info = $this->positionInfo();
		$this->assign(array(
			'position_id'=>$position_info['position_id'],
			'name'=>$position_info['name'],
			'description'=>$position_info['description'],
			));
		$this->display();
	}

	/*添加用户*/
	public function user_add(){
		$user_custom = M('config') -> where('name="user_custom"')->getField('value');
		if(!$user_custom)  $user_custom = '5k_crm';
		$user_max_id = M('user')->max('user_id');
		$user_max_id = $user_max_id+1;
		for($user_max_id;$user_max_id <99999;$user_max_id++){
			$user_max_code = str_pad($user_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
			$result = M('user')->where('number="%s"',$user_custom.$user_max_code)->find();
			if(!$result){
				break;
			}
		}
		if($this->isPost()){
			$name = trim($_POST['name']);
			$pd = trim($_POST['password']);
			$confirmpd = trim($_POST['confirmpd']);
			$position_id = intval($_POST['position_id']);
			$department_id = intval($_POST['department_id']);
			
			if($pd !== $confirmpd){
				$this->eReturn('两次输入密码不一致');
			}
			//用户名是否重复
			$where = array();
			$where['name'] = array('eq', $name);
			$ret = M('User')->where($where)->getField('user_id');
			if($ret){
				$this->eReturn('登录账号信息已存在!');
			}
			if(!$position_id || !$department_id){
				$this->eReturn('请选择岗位！');
			}
			//存用户信息
			$salt = substr(md5(time()),0,4);
			$pd = md5(md5($pd).$salt);
			
			//检查坐席号
			$extid = intval($_POST['extid']);
			if (!empty($extid)) {
				M('user')->where('extid = %d', $extid)->setField('extid', 0);
			}

			$user_data = array(
				'category_id'=>2,
				'status'=>3,
				'name'=>$name,
				'email'=>$this->_post('email','trim'),
				'full_name'=>$this->_post('full_name','trim'),
				'type'=>$this->_post('type','intval'),
				'telephone'=>$this->_post('telephone','trim'),
				'sex'=>$this->_post('sex'),
				'password'=>$pd,
				'salt'=>$salt,
				'reg_time'=>time(),
				'reg_ip'=>get_client_ip(),
				'number' => $user_custom.'_'.$this->_post('number'),
				'prefixion' => $user_custom,
				'extid' => intval($_POST['extid']),
				);
			$user_id = M('User')->add($user_data);
			if($user_id){
				$role_data = array(
				'position_id'=>$position_id,
				'user_id'=>$user_id,
				);
				$role_id = M('Role')->add($role_data);
				if($role_id){
					$data = array(
						'user_id'=>$user_id,
						'role_id'=>$role_id,
						);
					if(false !== M('User')->save($data)){
						$success = array(
							'status' => 1,
							'info' => '添加成功!',
							);
						$this->ajaxReturn($success);

					}
				}
			}

			$error = array(
				'status' => 0,
				'info' => '系统错误!',
				);
			$this->ajaxReturn($error);
			
		}else{
			$user_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : session('user_id');
    		//if(!session('?admin')){
    		//if(!in_array(session('role_id'),getPerByAction('user','add'))){
				// 	alert('error',L('YOU_DO_NOT_HAVE_THIS_RIGHT'),$_SERVER['HTTP_REFERER']);
				// }
    		//}
			$d_user = D('RoleView');
			$user = $d_user->where('user.user_id = %d', $user_id)->find();
			$user['category'] = M('user_category')->where('category_id = %d', $user['category_id'])->getField('name');
			$this->categoryList = M('user_category')->select();
			$status_list = array(1=>'启用', 2=>'停用');
			$this->assign('statuslist', $status_list);
			if($user['department_id']){
				$this->position_list = M('position')->where('department_id = %d', $user['department_id'])->select();
			}
			$department_list = getSubDepartment(0, M('role_department')->select());
			$this->assign('department_list', $department_list);
			
			$position_info = $this->positionInfo();
			$position_name = $position_info['name'];
			$department_name = M('RoleDepartment')->where('department_id = '.$position_info['department_id'])->getField('name');
			$de_po = "$department_name ———— $position_name";
			$this->assign(array(
				'position_id'=>$position_info['position_id'],
				'de_po'=>$de_po,
				'department_id'=>$position_info['department_id'],
				));
			//判断是否启用客户数限制
			$customer_num = M('config')->where('name="opennum"')->getField('value');
			$this->customer_num = $customer_num;
			
			$this->number = $user_max_code;
			$this->prefixion = $user_custom;
			//员工角色
			$this->assign('user_type_list',$this->user_type);
			$this->display();
		}
	}
/*插入上级*/
	public function position_up(){
		if($this->isPost()){
			$position_id = intval($_POST['position_id']);
			$parent_id = intval($_POST['parent_id']);
			$department_id = intval($_POST['department_id']);
			$name = trim($_POST['name']);
			$description = trim($_POST['description']);
			$position_data = array(
				'parent_id'=>$parent_id,
				'name'=>$name,
				'description'=>$description,
				'department_id'=>$department_id,
				);
			if($ret_id = M('Position')->add($position_data)){
				if(false !== M('Position')->where('position_id = '.$position_id)->setField('parent_id', $ret_id)){
					$this->sReturn();
				}
			}
			$this->eReturn('未知错误!');
		}

		$position_info = $this->positionInfo();
		$parent_id = $position_info['parent_id'];
		/*dump($parent_id);
		die;*/
		$position_name = M('Position')->where('position_id = '.$parent_id)->getField('name');
		$department_name = M('RoleDepartment')->where('department_id = '.$position_info['department_id'])->getField('name');
		$this->assign(array(
				'parent_id'=>$parent_id,
				'position_id'=>$position_info['position_id'],
				'department_name'=>$department_name,
				'department_id'=>$position_info['department_id'],
				'position_name'=>$position_name,
			));
		$this->display();
	}
	//顶级岗位不能插入上级
	public function is_parent(){
		if(IS_AJAX){
			$position_info = $this->positionInfo();
			if($position_info['parent_id'] == 0){
				echo 1;
			}
		}
	}
/*插入下级*/
	public function position_down(){
		if(IS_POST){
			$position_id = intval($_POST['position_id']);
			$department_id = intval($_POST['department_id']);
			$name = trim($_POST['name']);
			$description = trim($_POST['description']);

			$position_data = array(
				'parent_id'=>$position_id,
				'name'=>$name,
				'description'=>$description,
				'department_id'=>$department_id,
				);

			if(M('Position')->add($position_data)){
				$success = array(
							'status' => 1,
							'info' => '添加成功!',
							);
				$this->ajaxReturn($success);
			}else{
				$error = array(
							'status' => 0,
							'info' => '系统错误!',
							);
				$this->ajaxReturn($error);
			}
		}
		$position_info = $this->positionInfo();
		$department_name = M('RoleDepartment')->where('department_id = '.$position_info['department_id'])->getField('name');
		$this->assign(array(
				'position_id'=>$position_info['position_id'],
				'department_name'=>$department_name,
				'department_id'=>$position_info['department_id'],
				'position_name'=>$position_info['name'],
			));

		$this->display();

	}

	/*授权*/
	public function user_authorize(){
		if($this->isAjax() && $this->isPost()){

			$position_id = isset($_POST['position_id']) ? $_POST['position_id'] : 0;
			if($position_id != 0){
				$per = $_POST['per'] ? $_POST['per'] : array();
				$m_permission = M('Permission');
				$owned_permission = $m_permission->where('position_id = %d', $position_id)->getField('url', true);

				if(!empty($owned_permission)){
					$add_permission = array_diff($per,$owned_permission); 				//需要增加的
					$delete_permission = array_diff($owned_permission,$per);			//需要删除的
				} else {
					$add_permission = $per;
				}
				if(!empty($add_permission)){
					$data['position_id'] = $position_id;
					foreach($add_permission as $key=>$value){
						$data['url'] = $value;
						if(0>=$m_permission->add($data)){
							$this->ajaxReturn(L('PART_OF_THE_AUTHORIZATION_FAILED'),'info',1);
						}
					}
				}
				if(!empty($delete_permission)){
					$map['url'] = array('in',$delete_permission);
					$map['position_id'] = $position_id;
					$a = $m_permission->where($map)->delete();
					if($a<=0){
						$this->ajaxReturn(L('PART_OF_THE_AUTHORIZATION_FAILED'),'info',1);
					}
				}

				foreach($per as $k=>$v){
					$url = explode('/', $v); 
					if($_POST[$url['0']][$url[1]])
						$m_permission->where(array('url'=>$v,'position_id'=>$position_id))->setField('type', $_POST[$url['0']][$url[1]]);
				}
				
				//改变首页widget权限
				$user_list = D('RoleView')->where('position.position_id = %d', $position_id)->select();
				foreach($user_list as $v){
					$dashboard = unserialize($v['dashboard']);
					if(!empty($dashboard)){
						foreach($dashboard as $kk=>$vv){
							//如果有权限，根据权限设置首页图表的权限，如果没有获取相应权限，则去除对应权限的首页图表
							//客户权限图表：销售漏斗、客户来源、产品月销售、产品月度最高销量
							if(in_array('customer/index',$_POST['per'])){
								//1：自己和下属  3：自己
								if($_POST['customer']['index'] == 1 || $_POST['customer']['index'] == 2 || $_POST['customer']['index'] == 4){
									if($vv['widget'] == 'Salesfunnel' || $vv['widget'] == 'Customerorigin' || $vv['widget'] == 'Productmonthlysales' || $vv['widget'] == 'Productmonthlyamount'){
										$dashboard[$kk]['level'] = 1;
									}
								}
								if($_POST['customer']['index'] == 3){
									if($vv['widget'] == 'Salesfunnel' || $vv['widget'] == 'Customerorigin' || $vv['widget'] == 'Productmonthlysales' || $vv['widget'] == 'Productmonthlyamount'){
										$dashboard[$kk]['level'] = 0;
									}
								}
							}else{
								if($vv['widget'] == 'Salesfunnel' || $vv['widget'] == 'Customerorigin' || $vv['widget'] == 'Productmonthlysales' || $vv['widget'] == 'Productmonthlyamount'){
									unset($dashboard[$kk]);
								}
							}
							//如果有权限，根据权限设置首页图表的权限，如果没有获取相应权限，则去除对应权限的首页图表
							//财务权限图表：财务月度统计、财务年度对比
							if(in_array('finance/index_receivables',$_POST['per'])){
								if($_POST['finance']['index_receivables'] == 1 || $_POST['finance']['index_receivables'] == 2 || $_POST['finance']['index_receivables'] == 4){
									if($vv['widget'] == 'Receivemonthly' || $vv['widget'] == 'Receiveyearcomparison'){
										$dashboard[$kk]['level'] = 1;
									}
								}
								if($_POST['finance']['index_receivables'] == 3){
									if($vv['widget'] == 'Receivemonthly' || $vv['widget'] == 'Receiveyearcomparison'){
										$dashboard[$kk]['level'] = 0;
									}
								}
							}else{
								if($vv['widget'] == 'Receivemonthly' || $vv['widget'] == 'Receiveyearcomparison'){
									unset($dashboard[$kk]);
								}
							}
						}
						$newDashboard = serialize($dashboard);
						M('user')->where('user_id = %d', $v['user_id'])->setField('dashboard',$newDashboard);
					}
				}
				$this->ajaxReturn(L('OPERATION_IS_CHANGED'),'info',1);
			}else{
				$this->ajaxReturn( L('PLEASE_RETRY_AFTER_LOGIN_AGAIN'),'info',1);
			}
			
		} elseif($_GET['position_id']) {

			$m_permission = M('Permission');
			if($_GET['allow_id']){
				$position_id = intval($_GET['allow_id']);
			}else{
				$position_id = intval($_GET['position_id']);
			}
			$temp_array = $m_permission->where('position_id = %d', $position_id)->select();
			foreach($temp_array as $v){
				$owned_permission[$v['url']] = $v['type'];
			}
			//println($owned_permission);
			$this->owned_permission = $owned_permission;
			$this->position_id = intval($_GET['position_id']);
			$this->display();
		} else{
			alert('error', L('PLEASE_CHOOSE_TO_AUTHORIZE_JOBS'), $_SERVER['HTTP_REFERER']);
		}
		
	}

	//删除岗位
	public function position_del(){
		$position_id = intval($_GET['id']);
		if($position_id == 1){
			$error = array(
					'status' => 0,
					'info' => '不能删除顶级权限者!',
					);
			$this->eReturn('不能删除顶级权限者!');
			exit;
		}
		$ret = M('Role')->where('position_id = '.$position_id)->find();
		if(!empty($ret)){
			$error = array(
					'status' => 0,
					'info' => '此岗位上已有员工在职!',
					);
			$this->eReturn('此岗位上已有员工在职!');
			exit;
		}
		$ret = M('Position')->where('parent_id = '.$position_id)->find();
		if(!empty($ret)){
			$error = array(
					'status' => 0,
					'info' => '请先删除此下级岗位!',
					);
			$this->eReturn('请先删除此下级岗位!');
			exit;
		}else{
			if(M('Position')->delete($position_id)){
				$this->sReturn();
				exit;
			}
		}
		$this->eReturn();

	}

	/*岗位信息*/
	private function positionInfo(){
		$position_id = intval($_GET['id']);
		$position_info = M('Position')->where('position_id = '.$position_id)->find();
		return $position_info;
	}

	public function role(){
		if(!session('?admin')){
			alert('error',L('YOU_HAVE_NO_PERMISSION'),$_SERVER['HTTP_REFERER']);
		}
		$this->assign('tree_code', getSubPositionTreeCode(0, 1));
		$this->assign('tree_select', getSubPositionTreeCode(0, 1, 2));
		$this->alert=parseAlert();
		$this->display();
	}
	
	public function role_ajax_add(){
		if($_POST['name']){
			$role = D('role');
			if($role->create()){
				$role->name ? '' :alert('error',L('PLEASE_INPUT_POSITION_NAME'),$_SERVER['HTTP_REFERER']);
				if($role_id = $role->add()){
					$role_list = M('role')->select();
					if (session('?admin')) {
						$role_list = getSubRole(0, $role_list, '');
					} else {
						$role_list = getSubRole(session('role_id'), $role_list, '');
					}
					foreach ($role_list as $key=>$value) {
						if ($value['user_id'] == 0) {
							$rs_role[] = $role_list[$key];
						}
					}
				
					$data['role_id'] = $role_id;
					$data['role_list'] = $rs_role;
					$this->ajaxReturn($data,L('SEND_SUCCESS'),1);
				}else{
					$this->ajaxReturn("",L('SEND_FAILED'),0);
				}
			}else{
				$this->ajaxReturn("",L('SEND_FAILED'),0);
			}
		}else{
			$department = M('roleDepartment');
			$department_list = $department->select();	
			$this->assign('departmentList', getSubDepartment(0,$department_list,''));
			$role = M('role');
			$role_list = $role->select();	
			$this->assign('roleList', getSubRole(0,$role_list,''));
			$this->display();
		}
	}
	
	public function role_add(){
		if ($this->isPost()) {
			$d_position = D('Position');
			if($d_position->create()){
				$d_position->name ? '' :alert('error',L('PLEASE_INPUT_POSITION_NAME'),$_SERVER['HTTP_REFERER']);
				if(!intval($_POST['parent_id'])){
					alert('error', '父级岗位不能为空!', $_SERVER['HTTP_REFERER']);
				}
				if($position_id = $d_position->add()){
					if($_POST['son_id']){
						$d_position ->where('position_id = %d', intval($_POST['son_id']))->setField('parent_id', $position_id);
					}
					if($_POST['charge_department_id']){
						M('RoleDepartment')->where('department_id = %d', intval($_POST['charge_department_id']))->setField('charge_position', $position_id);
					}
					alert('success',L('ADD_POSITION_SUCCESS'),$_SERVER['HTTP_REFERER']);
				}else{
					$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR' ,array('')));
				}
			}else{
				$this->error(L('ADDING FAILS CONTACT THE ADMINISTRATOR' ,array('')));
			}
		} else {
			$department_list = M('RoleDepartment')->select();
			$position_list = M('Position')->select();
			if($_GET['id']){
				$position = M('Position')->where('position_id = %d', intval($_GET['id']))->find();
				$this->department_id = $position['department_id'];
				$this->position_id = $position['position_id'];
				$this->act = 'son';
			}elseif($_GET['pid']){
				$position_info = M('Position')->where('position_id = %d', intval($_GET['pid']))->find();
				$parent_position = M('Position')->where('position_id = %d', $position_info['parent_id'])->find();

				$this->department_id = $parent_position['department_id'];
				$this->position_id = $parent_position['position_id'];
				$this->son_id = $position_info['position_id'];
				$this->act = 'parent';
			}elseif($_GET['department_id']){
				$this->department_id = intval($_GET['department_id']);
				$this->act = 'first';
			}
			$this->assign('departmentList', getSubDepartment(0,$department_list,'--'));
			//$this->assign('positionList', getSubPosition(0,$position_list,''));
			$this->display();
		}
	}
	
	public function getRoleByDepartment(){
		if($this->isAjax()) {
			$department_id = $_GET['department_id'];
			$roleList = getRoleByDepartmentId($department_id, true);
			$this->ajaxReturn($roleList, '', 1); 
		}
	}
	
	public function adduserdialog(){
		$position_id = intval($_GET['position_id']);
		$position = M('Position')->where('position_id = %d', $position_id)->find();

		$department = M('RoleDepartment')->where('department_id = %d', $position['department_id'])->find();

		$this->position = $position;
		$this->department = $department;
		$this->display();
	}

    public function getRoleByDepartments() {
        if ($this->isAjax()) {
            $department_id = $_GET['department_id'];
            $roleList = getRoleByDepartmentId($department_id, true);
            foreach($roleList as $k=>$v){
                if($v['status']==1){
                    $newlist[] = $v;
                }
            }
            $this->ajaxReturn($newlist, '', 1);
        }
    }
	
	public function roleEdit(){
		if($_GET['auth']){
			$data['name'] = trim($_GET['name']);
			$data['description'] = trim($_GET['description']);
			//$data['department_id'] = intval($_GET['department_id']);
			//$data['parent_id'] = intval($_GET['parent_id']);
			if(M('Position')->where('position_id = %d', intval($_GET['position_id']))->save($data)){
				$this->ajaxReturn(L('EDIT SUCCESSFULLY'),'info',1);
			}else{
				$this->ajaxReturn(L('DATA_NOT_CHANGED_EDIT_FAILED'),'info',1);
			}
		}elseif($_GET['id']){
			$m_position = M('position');
			$department_list = M('RoleDepartment')->select();	
			$position_list = $m_position->select();
			$this->assign('position', $m_position->where('position_id=%d', $_GET['id'])->find());
			$this->assign('departmentList', getSubDepartment(0,$department_list,''));
			$this->assign('positionList', getSubPosition(0,$position_list,''));
			$this->display();
		}else{
			$this->error(L('PARAMETER_ERROR'));
		}
	}
	

	public function role_delete(){
		$m_position = M('position');
		$d_role = D('RoleView');
		if($_POST['roleList']){
			if(in_array(1,$_POST['roleList'],true)){
				$this->error(L('CAN_NOT_DELETE_THE_TOP_PERMISSION_USER'));
			}else{
				foreach($_POST['roleList'] as $key=>$value){
					$name = $m_position->where('role_id = %d', $value)->getField('name');
					if($d_role->where('position_id = %d', $value)->select()){
						alert('error',L('HAVE_USER_ON_THIS_POSITION',array($name)), $_SERVER['HTTP_REFERER']);
					}
				}
				if($m_position->where('role_id in (%s)', join($_POST['roleList'],','))->delete()){
					alert('success', L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
				}else{
					$this->error(L('DELETE FAILED CONTACT THE ADMINISTRATOR'));
				}
			}
		}elseif($_GET['id']){
			if(1 == intval($_GET['id'])){
				$this->error(L('CAN_NOT_DELETE_THE_TOP_PERMISSION_USER'));
			}
			if($d_role->where('position.position_id = %d', intval($_GET['id']))->select()){
				alert('error', L('HAVE_USER_ON_THIS_POSITION',array($name)), $_SERVER['HTTP_REFERER']);
			}else if($m_position->where('parent_id = %d', intval($_GET['id']))->find()){
				alert('error', '请先删除下级岗位!', $_SERVER['HTTP_REFERER']);
			}else{
				if($m_position->where('position_id = %d', intval($_GET['id']))->delete()){
					alert('success', L('DELETED SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
				}else{
					$this->error(L('DELETE FAILED CONTACT THE ADMINISTRATOR'));
				}
			}
		}else{
			alert('error', L('SELECT_POSITION_TO_DELETE'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	
	public function getRoleList(){
		$module = trim($_GET['module']);
		$action = trim($_GET['action']);
		$t = trim($_GET['t']);
		if($t && $t != 'null'){
			$action = $action.'_'.$t;
		}
		//判断权限
		if($module && $action){
			$per = checkPerByAction($module,$action);
		}
		if(session('?admin') || $_GET['type'] == 1 || $per == 2){
			$idArray = getSubRoleId('',1);
		}elseif($per == 4){
			//部门所有人
			$idArray = getPerByAction($module,$action);
		} elseif ($per == 3) {
			$idArray[] = session('role_id');
		}else{
			$idArray = getSubRoleId();
		}
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->ajaxReturn($roleList, '', 1);
	}
	public function notice(){
		$this->alert = parseAlert();
		$this->display();
	}
	
	//widget获取用户权限类型
	public function getActionAuthority(){
		$module = strtolower($_GET['module']);
		$action = strtolower($_GET['action']);
		$authority = checkPerByAction($module, $action);
		$this->ajaxReturn($authority, 'success',1);
	}

	private function sReturn(){
		$success = array(
					'status' => 1,
					'info' => '添加成功!',
					);
		$this->ajaxReturn($success);
	}

	private function eReturn($error_info=''){
		$error = array(
					'status' => 0,
					'info' => $error_info,
					);
		$this->ajaxReturn($error);
	}
	//重置密码
	public function reset_password(){
		if($this->isAjax()){
			if(!session('?admin') && !checkPerByAction('user','edit')){
				$this->ajaxReturn('','你没有权限！',0);
			}
			$new_password = $this->_get('new_password','trim');
			if(!$new_password){
				$this->ajaxReturn('','重置密码不能为空！',0);
			}
			$user_id = intval($_GET['id']);
			if(!$user_id){
				$this->ajaxReturn('','参数错误！',0);
			}


			
			$salt = M('User')->where('user_id = %d',$user_id)->getField('salt');
			$password = md5(md5($new_password).$salt);
			if(M('User')->where('user_id = %d',$user_id)->setField('password',$password)){
				$this->ajaxReturn('','重置成功！',1);
			}else{
				$this->ajaxReturn('','重置失败或数据无变化！',0);
			}
		}
	}
	//通讯录
	public function contacts(){
		$type = $this->_get('type','intval');
		$d_role = D('RoleView');
		$m_role_department = M('RoleDepartment');
		$m_position = M('Position');
		if($type == 1){
			//部门信息
			$where = array();
			if($_GET['department_id']){
				$where['department_id'] = intval($_GET['department_id']);
			}
			$department_list = $m_role_department->where($where)->order('parent_id asc,department_id asc')->select();
			foreach($department_list as $k=>$v){
				$position_ids = array();
				$role_count = 0;
				$position_ids = $m_position->where('department_id = %d',$v['department_id'])->getField('position_id',true);
				$department_role_list = array();
				$department_role_list = $d_role->limit(6)->where(array('position_id'=>array('in',$position_ids),'status'=>1))->order('user_id asc')->select();
				$role_count = $d_role->where(array('position_id'=>array('in',$position_ids),'status'=>1))->count();
				$department_list[$k]['role_count'] = $role_count ? $role_count : '0';
				$department_list[$k]['department_role_list'] = $department_role_list;
			}
			$this->all_department_list =$m_role_department->select();
			$this->department_list = $department_list;
		}else{
			import("@.ORG.Page");
			$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
			if($_GET['listrows']){
				$listrows = intval($_GET['listrows']);
				$params[] = "listrows=" . intval($_GET['listrows']);
			}else{
				$listrows = 15;
				$params[] = "listrows=".$listrows;
			}
			$where = array();
			$role_id_array = array();
			if($_GET['role_id'] && $_GET['role_id'] != 'all'){
				$where['role_id'] = intval($_GET['role_id']);
			}else{
				if(intval($_GET['department_id'])){
					$department_id = intval($_GET['department_id']);
					// foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					// 	$role_id_array[] = $v['role_id'];
					// }
					$position_ids = $m_position->where('department_id = %d',$department_id)->getField('position_id',true);
					$where['position_id'] = array('in',$position_ids);
				}
			}
			if($_GET['search']){
				$where['user.full_name'] = array('like','%'.trim($_GET['search']).'%');
			}
			$where['status'] = 1;
			$role_list = $d_role->where($where)->order('user_id asc')->page($p.','.$listrows)->select();
			$count = $d_role->where($where)->count();
			$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
			$show = $Page->show();// 分页显示输出
			$this->assign('page',$show);// 赋值分页输出
			$department_list = $m_role_department->select();
			$this->department_list = $department_list;
			$this->role_list = $role_list;
			$this->listrows = $listrows;
		}
		$this->alert = parseAlert();
		$this->display();
	}
	public function yanchong(){
		$m_user = M('user');
		$number = trim($_POST['number']);
		$prefixion = trim($_POST['prefixion']);
		$user_id = intval($_POST['user_id']);
		if($number){
			if($user_id > 0){
				$user_info = $m_user ->where('user_id =%d',$user_id)->find();
				if($user_info['number'] != $prefixion.$number){
					$result = $m_user ->where('number ="%s"',$prefixion.$number)->find();
					if($result){
						$this->ajaxReturn('','员工编号不能重复！',0);
					}else{
						$this->ajaxReturn('','',1);
					}
				}else{
					$this->ajaxReturn('','',1);
				}
			}else{
				$result = $m_user ->where('number ="%s"',$prefixion.$number)->find();
				if($result){
					$this->ajaxReturn('','员工编号不能重复！',0);
				}else{
					$this->ajaxReturn('','',1);
				}
			}
		}else{
			$this->ajaxReturn('','参数错误！',0);
		}
	}

	/**
	 * 用户登录超时弹出模态框
	 * @param 
	 * @author
	 * @return 
	 */
	public function loginAjax(){
		if(session('role_id')){
			$this->ajaxReturn('','您已登录！',2);
		}
		//标记为已打开登录窗口，则自动请求的登录不再弹出
		session('login_show',1);
		if($this->isPost()){
			if((!isset($_POST['name']) || $_POST['name'] =='')||(!isset($_POST['password']) || $_POST['password'] =='')){
				$this->ajaxReturn('',L('INVALIDATE_USER_NAME_OR_PASSWORD'),0);
			}elseif (isset($_POST['name']) && $_POST['name'] != ''){
				$m_user = M('user');
				$user = $m_user->where(array('name' => trim($_POST['name'])))->find();
				if ($user['password'] == md5(trim($_POST['password']) . $user['salt'])) {				
					if (-1 == $user['status']) {
						$this->ajaxReturn('',L('YOU_ACCOUNT_IS_UNAUDITED'),0);
					} elseif (0 == $user['status']) {
						$this->ajaxReturn('',L('YOU_ACCOUNT_IS_AUDITEDING'),0);
					}elseif (2 == $user['status']) {
						$this->ajaxReturn('',L('YOU_ACCOUNT_IS_DISABLE'),0);
					}else {
						$d_role = D('RoleView');
						$role = $d_role->where('user.user_id = %d', $user['user_id'])->find();
						if ($_POST['autologin'] == 'on') {
							session(array('expire'=>259200));
							cookie('user_id',$user['user_id'],259200);
							cookie('name',$user['name'],259200);
							cookie('salt_code',md5(md5($user['user_id'] . $user['name']).$user['password']),259200);
						}else{
							session(array('expire'=>3600));
						}
						if (!is_array($role) || empty($role)) {
							$this->ajaxReturn('',L('HAVE_NO_POSITION'),0);
						} else {
							if(3 == $user['status']){
								$m_user ->where('user_id =%d',$user['user_id'])->setField('status',1);
							}
							if($user['category_id'] == 1){
								session('admin', 1);
							}
							session('role_id', $role['role_id']);
							if($user['img']){
								session('user_img', $user['img']);
							}
							session('full_name',$user['full_name']);
							session('position_id', $role['position_id']);
							session('role_name', $role['role_name']);
							session('department_id', $role['department_id']);
							session('name', $user['full_name']);
							session('user_id', $user['user_id']);
							$this->ajaxReturn('',L('LOGIN_SUCCESS'),1);	
						}
					}
				} else {
					$this->ajaxReturn('',L('INCORRECT_USER_NAME_OR_PASSWORD'),0);				
				}
			}
		}
		$this->display();
	}
}