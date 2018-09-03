<?php 

class AuthenticateBehavior extends Behavior {
	protected $options = array();
	
	public function run(&$params) {
		$m = MODULE_NAME;
		$a = ACTION_NAME;
		$allow = $params['allow'];
		$permission = $params['permission'];

		if(!session('?user_id') && intval(cookie('user_id')) != 0 && trim(cookie('name')) != '' && trim(cookie('salt_code')) != ''){
			$user = M('user')->where(array('user_id' => intval(cookie('user_id'))))->find();
			if (md5(md5($user['user_id'] . $user['name']).$user['password']) == trim(cookie('salt_code'))) {
				$d_role = D('RoleView');
				$role = $d_role->where('user.user_id = %d', $user['user_id'])->find();
				if($user['category_id'] == 1){
					session('admin', 1);
				}
				M('user')->where('user_id = %d',$user['user_id'])->setField('last_login_time',time());
				session('role_id', $role['role_id']);
				session('position_id', $role['position_id']);
				session('role_name', $role['role_name']);
				session('department_id', $role['department_id']);
				session('name', $user['name']);
				session('user_id', $user['user_id']);
			}
		}
		
		if (session('?admin')) {
			return true;
		}
		if (in_array($a, $permission)) {
			return true;
		} elseif (session('?position_id') && session('?role_id')) {
			if (in_array($a, $allow)) {
				return true;
			} else {
				if($m == 'Finance' && $a !== 'check' && $a != 'revokeCheck'){
					$a = $a.'_'.trim($_REQUEST['t']);
				}
				if($a == 'view_ajax'){
					$a = 'view';
				}
				if(!checkPerByAction($m, $a)){
					if(isAjaxRequest()){
						if($a == 'delete' || $a == 'log_delete' || $a == 'delete_'.trim($_REQUEST['t']) || ($m == 'Task' && $a != 'add' && $a != 'view')){
							$error_data = array();
							$error_data['data'] = '';
							$error_data['info'] = '您没有此权利！';
							$error_data['status'] = 0;
							echo json_encode($error_data);die();
						}else{
							echo '<div class="alert alert-error">您没有此权利！</div>';die();
						}
					}else{
						$url = empty($_SERVER['HTTP_REFERER']) ? U('index/index') : $_SERVER['HTTP_REFERER'];
						alert('error', '您没有此权利!', $url);
					}
				}else{
					return true;
				}
			}
		} elseif(!session('?role_id')) {
			$return_url = $m.'/'.$a;
			if($m == C('DEFAULT_MODULE') && $a == C('DEFAULT_ACTION')){
				alert('error',  '请先登录...', U('user/login'));
			}else{
				if(isAjaxRequest()){
					// request('error',  '请先登录...', U('user/loginajax'));
					redirect(U('user/loginajax'));
				}else{
					alert('error',  '请先登录...', U('user/login'));
				}
			}
		}
	}
}