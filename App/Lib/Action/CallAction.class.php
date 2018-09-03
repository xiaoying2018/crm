<?php
	class CallAction extends Action{
		public function _initialize(){
			$action = array(
				'permission'=>array(),
				'allow'=>array('teldata','analytics','record')
			);
			B('Authenticate', $action);
		}

		/**
		 * 呼叫中心(右下角提示信息)
		 * @param 
		 * @author 
		 * @return 
		 */
		public function call_content(){
			$tel = $_REQUEST['tel'] ? trim($_REQUEST['tel']) : '';
			$model_id = $_REQUEST['model_id'] ? intval($_REQUEST['model_id']) : '';
			$model = trim($_REQUEST['model']) ? : 'customer';
			if ($tel) {
				switch ($model) {
					case 'customer' : 
						$contacts_id = M('Contacts')->where(array('telephone'=>$tel))->getField('contacts_id');
						if ($contacts_id) {
							$customer_id = M('RContactsCustomer')->where(array('contacts_id'=>$contacts_id))->getField('customer_id');
							$info = D('CustomerView')->where(array('customer.customer_id'=>$customer_id))->field('name,contacts_telephone,contacts_name')->find();
							$info['telephone'] = $info['contacts_telephone'];
						}
						break;
					case 'leads' :
						$info = D('LeadsView')->where(array('leads.mobile'=>$tel))->find();
						break;
				}
			}
			$this->info = $info;
			$this->display();
		}

		/**
		 * 弹出页面(详情)
		 * @param 
		 * @author 
		 * @return 
		 */
		public function data() {
			$model = trim($_REQUEST['model']) ? : 'customer';
			$model_id = intval($_REQUEST['model_id']) ? : '';
			$tel = trim($_REQUEST['tel']) ? : '';
			$m_user = M('User');
			// 	echo '<div class="alert alert-error">参数错误！</div>';die();
			if ($model_id) {
				switch ($model) {
					case 'customer' : 
						$info = D('CustomerView')->where(array('customer.customer_id'=>$model_id))->find();
						$info['owner'] = $m_user->where('role_id = %d', $info['owner_role_id'])->field('role_id,full_name')->find();
						//自定义字段
						$field_list = M('Fields')->where(array('model'=>'customer','field'=>array('not in',array('name','grade','customer_owner_id','customer_code'))))->order('is_main desc ,order_id asc')->select();	
						//自定义快捷回复
						$this->status_list = M('LogStatus')->select();
						$m_business = M('Business');
						$r_business_log = M('rBusinessLog');
						//沟通日志
						$business_ids = $m_business->where(array('customer_id'=>$model_id))->getField('business_id',true);
						$business_log_ids = $r_business_log->where(array('business_id'=>array('in',$business_ids)))->getField('log_id', true);
						$customer_log_ids = M('rCustomerLog')->where(array('customer_id'=>$model_id))->getField('log_id',true);
						if ($customer_log_ids == '') {
							$log_ids = $business_log_ids;
						} elseif ($business_log_ids == '') {
							$log_ids = $customer_log_ids;
						} else {
							$log_ids = array_merge($business_log_ids, $customer_log_ids);
						}
						$m_sign = M('Sign');
						$m_sign_img = M('SignImg');
						$m_user = M('User');
						$m_log_status = M('LogStatus');
						$log_list = M('Log')->where(array('log_id'=>array('in',$log_ids)))->order('log_id desc')->select();
						foreach ($log_list as $k=>$v) {
							$business_info = array();
							$business_id = '';
							if ($business_log_ids && in_array($v['log_id'],$business_log_ids)) {
								$business_id = $r_business_log->where(array('log_id'=>$v['log_id']))->getField('business_id');
								$business_info = $m_business->where(array('business_id'=>$business_id))->field('code,business_id')->find();
							}
							$log_list[$k]['business_info'] = $business_info;
							//签到
							if ($v['sign'] == 1) {
								$sign_info = $m_sign->where('log_id = %d',$v['log_id'])->find();
								$log_list[$k]['sign_img'] = $m_sign_img ->where('sign_id = "%d"',$sign_info['sign_id'])->select();
								$log_list[$k]['sign_info'] = $sign_info;
							}
							$role_info = array();
							$role_info = $m_user->where('role_id = %d', $v['role_id'])->field('full_name,thumb_path,role_id')->find();
							if (!$role_info['thumb_path']) {
								$role_info['thumb_path'] = './Public/img/avatar_default.png';
							}
							$log_list[$k]['owner'] = $role_info;
							$log_list[$k]['log_type'] = 'rCustomerLog';
							$log_list[$k]['content'] = strip_tags($v['content']);
							$status_name = $m_log_status->where('id = %d',$v['status_id'])->getField('name');
							$log_list[$k]['status_name'] = $status_name ? $status_name : '';
						}
						break;
					case 'leads' :
						$info = D('LeadsView')->where(array('leads.leads_id'=>$model_id))->find();
						$info['owner'] = $m_user->where('role_id = %d', $info['owner_role_id'])->field('role_id,full_name')->find();
						//自定义字段
						$field_list = M('Fields')->where(array('model'=>'leads'))->order('is_main desc ,order_id asc')->select();	
						//自定义快捷回复
						$this->status_list = M('LogStatus')->select();
						$log_ids = M('rLeadsLog')->where(array('leads_id'=>$model_id))->getField('log_id',true);
						$m_sign = M('Sign');
						$m_sign_img = M('SignImg');
						$m_user = M('User');
						$m_log_status = M('LogStatus');
						$log_list = M('Log')->where(array('log_id'=>array('in',$log_ids)))->order('log_id desc')->select();
						foreach ($log_list as $k=>$v) {
							//签到
							if ($v['sign'] == 1) {
								$sign_info = $m_sign->where('log_id = %d',$v['log_id'])->find();
								$log_list[$k]['sign_img'] = $m_sign_img ->where('sign_id = "%d"',$sign_info['sign_id'])->select();
								$log_list[$k]['sign_info'] = $sign_info;
							}
							$role_info = array();
							$role_info = $m_user->where('role_id = %d', $v['role_id'])->field('full_name,thumb_path,role_id')->find();
							if (!$role_info['thumb_path']) {
								$role_info['thumb_path'] = './Public/img/avatar_default.png';
							}
							$log_list[$k]['owner'] = $role_info;
							$log_list[$k]['log_type'] = 'rCustomerLog';
							$log_list[$k]['content'] = strip_tags($v['content']);
							$status_name = $m_log_status->where('id = %d',$v['status_id'])->getField('name');
							$log_list[$k]['status_name'] = $status_name ? $status_name : '';
						}
						break;
				}
				$this->info = $info;
				$this->log_list = $log_list;
				$this->field_list = $field_list;
				$this->model = $model;
				$this->model_id = $model_id;
				$this->display('view');
			} else {
				if ($tel) {
					switch ($model) {
						case 'customer' : 
							$contacts_id = M('Contacts')->where(array('telephone'=>$tel))->getField('contacts_id');
							if ($contacts_id) {
								$customer_id = M('RContactsCustomer')->where(array('contacts_id'=>$contacts_id))->getField('customer_id');
								$info = D('CustomerView')->where(array('customer.customer_id'=>$customer_id))->find();
							}
							break;
						case 'leads' :
							$info = D('LeadsView')->where(array('leads.mobile'=>$tel))->find();
							break;
					}
				}
				if ($info) {
					$this->info = $info;
					$this->display('view');
				} else {
					$this->display('add');
				}
			}
		}

		/**
		 * 客户联系人信息
		 * @param 
		 * @author 
		 * @return 
		 */
		public function telData() {
			$model = trim($_REQUEST['model']) ? : 'customer';
			$model_id = intval($_REQUEST['model_id']) ? : '';
			if (!$model || !$model_id) {
				echo '<div class="alert alert-error">参数错误！</div>';die();
			}
			switch ($model) {
				case 'customer' : 
					$contacts_ids = M('RContactsCustomer')->where(array('customer_id'=>$model_id))->getField('contacts_id',true);
					$list = M('Contacts')->where(array('contacts_id'=>array('in',$contacts_ids)))->field('name,telephone')->select();
			}
			$this->list = $list;
			$this->display();
		}


		/**
		* 查询通话记录
		**/
		public function record(){
			$m_user = M('User');

			$user_list = $m_user->where('status = 1')->field('extid, name')->select();

			$extid_list = range(C('EXTID_MIN'), C('EXTID_MAX'));
			foreach ($extid_list as $k => $v) {
				$list[$k]['extid'] = $v;
				foreach ($user_list as $key => $value) {
					if ($v == $value['extid']) {
						$list[$k]['name'] = $value['name'];
						break;
					} else {
						$list[$k]['name'] = '未绑定';
					}
				}
			}

			$this->assign('list', $list);
			$this->alert = parseAlert();
			$this->display();
		}


		/**
		* 统计
		**/
		public function analytics(){
			if (session('?admin')) {
				$this->alert = parseAlert();
				$this->display();
			} else {
				alert('error', '无权访问！', $_SERVER['HTTP_REFERER']);
			}
		}


}