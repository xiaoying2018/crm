<?php
/**
 *签到
 **/
class SignVue extends Action{
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('index','view','add')
		);
		B('VueAuthenticate', $action);

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
	 * 签到
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add() {
		if ($this->isPost()) {
			$m_sign = M('Sign');
			$m_sign->create();
			$m_sign->role_id = session('role_id');
			$m_sign->create_time = time();
			$sign_id = $m_sign->add();
			if ($sign_id) {
				if ($_POST['customer_id']) {
					$m_log = M('Log');
					$m_log->role_id = session('role_id');
					$m_log->category_id = 1;
					$m_log->sign = 1;
					$m_log->create_date = time();
					$m_log->update_date = time();
					if ($log_id = $m_log->add()) {
						$data['log_id'] = $log_id;
						$data['customer_id'] = $_POST['customer_id'];
						M('RCustomerLog')->add($data);
						$m_sign->where('sign_id = %d',$sign_id)->setField('log_id',$log_id);
					}
				}
				if (isset($_FILES['img']['size']) && $_FILES['img']['size'] != null) {
					//如果有文件上传 上传附件
					import('@.ORG.UploadFile');
					//导入上传类
					$upload = new UploadFile();
					//设置上传文件大小
					$upload->maxSize = 20000000;
					//设置附件上传目录
					$dirname = UPLOAD_PATH . '/sign/'.date('Ym', time()).'/'.date('d', time()).'/';
					$upload->allowExts  = array('jpg','jpeg','png','gif');// 设置附件上传类型
					if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
						$this->ajaxReturn('','上传目录不可写',0);
					}
					$upload->savePath = $dirname;

					if (!$upload->upload()) {// 上传错误提示错误信息
						$this->ajaxReturn('',$upload->getErrorMsg(),0);
					} else {// 上传成功 获取上传文件信息
						$info = $upload->getUploadFileInfo();
						//写入数据库
						foreach ($info as $iv) {
							$img_data['sign_id'] = $sign_id;
							$img_data['name'] = $iv['name'];
							$img_data['save_name'] = $iv['savename'];
							//$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
							$img_data['path'] = $iv['savepath'].$iv['savename'];
							$img_data['create_time'] = time();
							M('SignImg')->add($img_data);
						}
						actionLog($sign_id);
						$this->ajaxReturn('','签到成功！',1);
					}
				} else {
					actionLog($sign_id);
					$this->ajaxReturn('','签到成功1！',1);
				}
			} else {
				$this->ajaxReturn('','签到失败，请重试！',0);
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 签到列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			$below_ids = getPerByAction('sign','index');
			$m_sign = M('Sign');
			$m_user = M('User');
			$m_customer = M('Customer');
			$m_sign_img = M('SignImg');

			$where = array();
			$date = $_POST['date'] ? $_POST['date'] : time();
			$start_time = strtotime(date('Y-m-d',$date));
			$end_time = $start_time+86400;
			$where['create_time'] = array('between',array($start_time,$end_time));

			$role_ids = array();
			
			//查询
			$department_id = $_POST['department_id'] ? $_POST['department_id'] : array(session('department_id'));
			$role_id = $_POST['role_id'];
			if (is_array($department_id)) {
				foreach ($department_id as $k=>$v) {
					foreach (getRoleByDepartmentId($v, true) as $key=>$val) {
						$role_ids[] = $val['role_id'];
					}
				}
			}
			if (is_array($role_id)) {
				$role_ids = $role_ids ? array_merge($role_ids,$role_id) : $role_id;
			} 
			// if (!$department_id && !$role_id) {
			// 	//默认本部门权限范围内role_id
			// 	$role_ids = D('RoleView')->where(array('position.role_department'=>session('department_id')))->getField('role_id',true);
			// }

			if ($role_ids) {
				//数组交集
				$role_id_array = array_intersect($role_ids, $below_ids);
			} else {
				$role_id_array = array(session('role_id'));
			}
			$where['role_id'] = array('in',$role_id_array);

			$p = isset($_POST['p']) ? intval($_POST['p']) : 1;
			$sign_list = $m_sign->where($where)->page($p,10)->order('create_time desc')->select();
			$count = $m_sign->where($where)->count();
			$page = ceil($count/10);

			foreach ($sign_list as $k=>$v) {
				$role_info = array();
				$role_info = $m_user->where('role_id = %d', $v['role_id'])->field('full_name,thumb_path')->find();
				$sign_list[$k]['user_name'] = $role_info['full_name'];
				$sign_list[$k]['user_img'] = $role_info['thumb_path'];
				//客户
				$sign_customer_name = $m_customer->where('customer_id = %d',$v['customer_id'])->getField('name');
				$sign_list[$k]['sign_customer_name'] = empty($sign_customer_name) ? '' : $sign_customer_name;
				//图片
				$sign_img = $m_sign_img->where(array('sign_id'=>$v['sign_id']))->getField('path',true);
				$sign_list[$k]['sign_img'] = $sign_img ? $sign_img : array();
			}
			$info = array();
			$info['list'] = $sign_list ? $sign_list : array();
			$info['today_count'] = $count;

			$data['page'] = $page;
			$data['data'] = $info;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 足迹分布
	 * @param type=role 员工数组  sign 坐标数组
	 * @author 
	 * @return 
	 */
	public function view() {
		if ($this->isPost()) {
			$below_ids = getPerByAction('sign','index');
			$type = $_POST['type'] ? trim($_POST['type']) : 'role';
			$date = $_POST['date'] ? $_POST['date'] : time();
			$start_time = strtotime(date('Y-m-d',$date));
			$end_time = $start_time+86400;

			$where = array();
			$m_sign = M('Sign');
			$m_user = M('User');
			$role_ids = array();
			
			//查询
			$department_id = $_POST['department_id'];
			$role_id = $_POST['role_id'];

			if (is_array($department_id)) {
				foreach ($department_id as $k=>$v) {
					foreach (getRoleByDepartmentId($v, true) as $key=>$val) {
						$role_ids[] = $val['role_id'];
					}
				}
			}
			if (is_array($role_id)) {
				$role_ids = $role_ids ? array_merge($role_ids,$role_id) : $role_id;
			} 
			if (!$department_id && !$role_id) {
				//默认本部门权限范围内role_id
				$role_ids = D('RoleView')->where(array('position.role_department'=>session('department_id')))->getField('role_id',true);
			}

			if ($role_ids) {
				//数组交集
				$role_id_array = array_intersect($role_ids, $below_ids);
			} else {
				$role_id_array = array(session('role_id'));
			}

			if ($type == 'role') {
				$where['role_id'] = array('in',$role_id_array);
				$where['create_time'] = array('between',array($start_time,$end_time));
				
				$role_arr = $m_sign->where($where)->group('role_id')->getField('role_id',true);
				$role_info_list = $m_user->where(array('role_id'=>array('in',$role_arr)))->field('role_id,full_name,thumb_path')->select();

				$data['list'] = $role_info_list ? $role_info_list : array();
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}
			if ($type == 'sign') {
				$where_sign['create_time'] = array('between',array($start_time,$end_time));
				$where['role_id'] = array('in',$role_id_array);
				$sign_list = $m_sign->where($where_sign)->field('x,y,address,title')->select();
				$data['list'] = $sign_list ? $sign_list : array();
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}
			$this->ajaxReturn('','参数错误！',0);
		}
	}
}