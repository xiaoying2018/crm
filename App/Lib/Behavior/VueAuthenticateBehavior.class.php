<?php

class VueAuthenticateBehavior extends Behavior {
	protected $options = array();

	public function run(&$params) {
		header("Access-Control-Allow-Origin: *"); //开发时使用（VUE跨域问题）

		$m = strtolower(MODULE_NAME);
		$a = strtolower(ACTION_NAME);
		if($m == 'finance' && $a != 'check' && $a != 'revokecheck' && $a != 'getcode' && $a != 'check_list'){
			$a = $a.'_'.trim($_REQUEST['t']);
		}
		if (($a == 'dynamic' && $m != 'index') || $a == 'content_info') {
			if ($m == 'examine' && $a == 'dynamic') {
				$a = 'index';
			}else {
				$a = 'view';
			}
		}
		$allow = $params['allow'];
		$permission = $params['permission'];
		if (in_array($a, $permission)) {
			return true;
		}
		$token = trim($_POST['token']) ? : '';
		if ($token) {
			$user_info = M('User')->where('token = "%s"',$token)->find();
			if ($user_info) {
				$role = D('RoleView')->where('user.user_id = %d', $user_info['user_id'])->find();
				if ((time() - $user_info['token_time']) < (60*60*24*3) && $user_info['role_id'] != session('role_id')) {
					if ($user_info['category_id'] == 1) {
						session('admin', 1);
					}
					session('role_id', $role['role_id']);
					session('position_id', $role['position_id']);
					session('role_name', $role['role_name']);
					session('department_id', $role['department_id']);
					session('name', $user_info['name']);
					session('user_id', $user_info['user_id']);
				} else {
					M('User')->where('user_id = %d',session('user_id'))->setField(array('token_time'=>time()));
					if (!session('?role_id')) {
						if ($user_info['category_id'] == 1) {
							session('admin', 1);
						}
						session('role_id', $role['role_id']);
						session('position_id', $role['position_id']);
						session('role_name', $role['role_name']);
						session('department_id', $role['department_id']);
						session('name', $user_info['name']);
						session('user_id', $user_info['user_id']);
					}
				}
				if ($user_info['category_id'] == 1) {
					return true;
				}
				if (in_array($a, $allow)) {
					return true;
				} else {
					if(!checkPerByAction($m, $a)){
						Global $roles;
						$roles = 2;
					}else{
						return true;
					}
				}
			}else{
				session(null);
				Global $roles;
				$roles = 3;
			}
		} else {
			session(null);
			Global $roles;
			$roles = 3;
		}
	}
}
