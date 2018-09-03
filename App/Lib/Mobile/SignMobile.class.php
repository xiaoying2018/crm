<?php
/**
 *	签到
 **/
class SignMobile extends Action{
	/**
	 *	permission 未登录可访问
	 * 	allow 登录访问
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('index','view','sign_in','customer_list','outworker')
		);
		B('AppAuthenticate', $action);
	}
	public function index(){
		if($this->isPost()){
			$where = array();
			$by = isset($_GET['by']) ? trim($_GET['by']) : '';
			$subrole_ids = getSubRoleId(false);
			if($subrole_ids){
				$below_ids = array();
				foreach($subrole_ids as $v){
					if($v !== session('role_id')){
						$below_ids[] = $v;
					}
				}
			}else{
				$below_ids = array();
			}
			switch ($by) {
				case 'sub' : $where['role_id'] = array('in',implode(',', $below_ids)); break;
				case 'me' : $where['role_id'] = session('role_id'); break;
				default :
					$where['role_id'] = array('in',implode(',', getSubRoleId())); break;
				break;
			}
			$where['action_name'] = array('not in',array('completedelete','delete','view'));
			$where['module_name'] = array('in',array('sign'));
			$map['business.is_deleted'] = array('neq',1);
			$map['customer.is_deleted'] = array('neq',1);
			$map['sign.sign_id'] = array("gt",0);
			$map['_logic'] = 'or';
			$where['_complex'] = $map;

			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$log = D('ActionLogView')->where($where)->page($p,10)->order('create_time desc')->select();
			$logCount = D('ActionLogView')->where($where)->count();
			$page = ceil($logCount/10);
			$action_name = array('sign_in'=>'进行');
			$module_name = array('sign'=>'签到');
			$list = array();

			foreach($log as $k=>$v){
				$role = array();
				$role = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
				$tmp = array();
				$tmp['type'] = $v['module_name'];
				$tmp['role_id'] = $v['role_id'];
				$tmp['user_name'] = $role['user_name'];
				$tmp['role_name'] = $role['department_name'].'-'.$role['role_name'];
				$tmp['img'] = $role['img'];
				$tmp['content'] = $action_name[$v['action_name']].'了'.$module_name[$v['module_name']];
				if('sign'==$v['module_name']){
					$tmp['log'] = $v['log'];
					$tmp['address'] = $v['address'];
					$tmp['x'] = $v['x'];
					$tmp['y'] = $v['y'];
					$tmp['title'] = $v['title'];
					$tmp['sign_customer_id'] = $v['sign_customer_id'];
					$sign_customer_name = M('Customer')->where('customer_id = %d',$v['sign_customer_id'])->getField('name');
					$tmp['sign_customer_name'] = empty($sign_customer_name) ? '' : $sign_customer_name;
				}
				$tmp['id'] = $v['action_id'];
				$tmp['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
				$list[] = $tmp;
			}
			if(empty($_POST['role_id'])){
				$count = array();
				$time_now = time();
				$compare_time = $time_now - 86400*3;
				$daily['role_id'] = array('in',implode(',', getSubRoleId()));
				$daily['update_date'] = array('gt',$compare_time);
				$count['log'] = M('Log')->where($daily)->count();
				$data['count'] = $count;
			}
			$data['page'] = $page;
			$data['list'] = $list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}

	public function view(){
		if($this->isPost()){
			$sign_id = $_REQUEST['sign_id'];
			if(empty($sign_id)){
				$this->ajaxReturn('','参数错误',2);
			}else{
				$sign = M('Sign')->where('sign_id = %d',$sign_id)->find();
				$customer = M('Customer')->where('customer_id=%d',$sign['customer_id'])->find();
				$sign['customer_name'] = $customer['name'];
				$img = M('SignImg')->where('sign_id = %d',$sign['sign_id'])->select();
				if($img){
					foreach($img as $k => $v){
						$sign['img'][$k] = $v['path'];
					}
				}
				$this->ajaxReturn($sign,'success',1);
			}
		}
	}
	public function sign_in(){
		if($this->isPost()){
			$m_sign = M('Sign');
			$m_sign->create();
			$m_sign->role_id = session('role_id');
			$m_sign->create_time = time();
			$sign_id = $m_sign->add();
			if($sign_id){
				if($_POST['customer_id']){
					$m_log = M('Log');
					$m_log->role_id = session('role_id');
					$m_log->category_id = 1;
					$m_log->sign = 1;
					$m_log->create_date = time();
					$m_log->update_date = time();
					if($log_id = $m_log->add()){
						$data['log_id'] = $log_id;
						$data['customer_id'] = $_POST['customer_id'];
						M('RCustomerLog')->add($data);
						$m_sign->where('sign_id = %d',$sign_id)->setField('log_id',$log_id);
					}
				}
				if (array_sum($_FILES['img']['size'])) {
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
						$this->ajaxReturn('上传目录不可写',"上传目录不可写",2);
					}
					$upload->savePath = $dirname;

					if(!$upload->upload()) {// 上传错误提示错误信息
						$this->ajaxReturn('',$upload->getErrorMsg(),2);
					}else{// 上传成功 获取上传文件信息
						$info = $upload->getUploadFileInfo();
						//写入数据库
						foreach($info as $iv){
							$img_data['sign_id'] = $sign_id;
							$img_data['name'] = $iv['name'];
							$img_data['save_name'] = $iv['savename'];
							//$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
							$img_data['path'] = $iv['savepath'].$iv['savename'];
							$img_data['create_time'] = time();
							M('SignImg')->add($img_data);
						}
						actionLog($sign_id);
						$this->ajaxReturn('',"success",1);
					}
				}else{
					actionLog($sign_id);
					$this->ajaxReturn('',"success",1);
				}
			}else{
				$this->ajaxReturn('',"签到失败，请重试！",2);
			}
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}

	public function customer_list(){
		if($this->isPost()){
			//$role_id = session('role_id');
			//$where['owner_role_id'] = $role_id;
			$where['owner_role_id'] = array('in',implode(',', getPerByAction('customer','index')));
			$where['is_deleted'] = 0;
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$list = M('Customer')->where($where)->order("create_time desc")->page($p.',10')->field('customer_id,name')->select();
			$count = M('Customer')->where($where)->count();
			$page = ceil($count/10);
			$list = empty($list) ? array() : $list;
			$data['list'] = $list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}
	//外勤签到列表
	public function outworker(){
		if($this->isPost()){
			$role_id = empty($_POST['role_id']) ? session('role_id') : $_POST['role_id'];
			if(!$role_id){
				$this->ajaxReturn('参数错误','参数错误',2);
			}
			$d_actionlog = D('ActionLogView');
			$m_sign = M('Sign');
			$m_customer = M('Customer');
			$m_signimg = M('SignImg');
			$where = array();
			$create_time = empty($_POST['create_time']) ? time() : $_POST['create_time'];
			//获取已知时间戳的当天开始结束时间
			$start = strtotime(date("Y-m-d 00:00:00",$create_time));
			$end = strtotime(date("Y-m-d 23:59:59",$create_time));
			$where['create_time'] = array('between',array($start,$end));

			$where['role_id'] = $role_id;
			$where['action_name'] = array('not in',array('completedelete','delete','view'));
			$where['module_name'] = array('in',array('sign'));
			$map['business.is_deleted'] = array('neq',1);
			$map['customer.is_deleted'] = array('neq',1);
			$map['sign.sign_id'] = array("gt",0);
			$map['_logic'] = 'or';
			$where['_complex'] = $map;

			/* //单个坐标查询详情
			$id = intval($_POST['id']);
			$action_id = isset($id) ? $id : 0;
			if($action_id){
				$where['action_id'] = $action_id;
			} */
			$log = $d_actionlog->where($where)->order('create_time desc')->select();
			$action_name = array('sign_in'=>'进行');
			$module_name = array('sign'=>'签到');
			$list = array();
			foreach($log as $k=>$v){
				$role = array();
				$role = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
				$tmp = array();
				$tmp['type'] = $v['module_name'];
				$tmp['role_id'] = $v['role_id'];
				$tmp['user_name'] = $role['user_name'];
				$tmp['role_name'] = $role['department_name'].'-'.$role['role_name'];
				$tmp['log'] = $v['log'];
				$tmp['address'] = $v['address'];
				$tmp['x'] = $v['x'];
				$tmp['y'] = $v['y'];
				$tmp['title'] = $v['title'];
				$tmp['customer_id'] = $v['sign_customer_id'];
				$sign_customer_name = $m_customer->where('customer_id = %d',$v['sign_customer_id'])->getField('name');
				$tmp['customer_name'] = empty($sign_customer_name) ? '' : $sign_customer_name;
				$tmp['id'] = $v['action_id'];
				$tmp['create_time'] = $v['create_time'];

				$img = $m_signimg->where('sign_id = %d',$v['action_id'])->select();
				if($img){
					foreach($img as $key => $val){
						$tmp['img'][$key] = $val['path'];
					}
				}
				$list[] = $tmp;
				//获取坐标
				$coordinate[$k]['coordinate_x'] = empty($v['x']) ? '' : $v['x'];
				$coordinate[$k]['coordinate_y'] = empty($v['y']) ? '' : $v['y'];
			}
			//$data['coordinate'] = empty($coordinate) ? array() : $coordinate;
			$data['list'] = $list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}else{
			$this->ajaxReturn('非法请求',"非法请求",2);
		}
	}
}