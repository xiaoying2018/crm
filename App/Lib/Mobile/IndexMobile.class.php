<?php

class IndexMobile extends Action{
	/**
	 *	permission 未登录可访问
	 * 	allow 登录访问
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('home','index','view','inbox','outbox','boxdelete','boxview','send','message','messagehistory','comment','replay','validate','fields','permission','content_info')
		);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		B('AppAuthenticate', $action);
		Global $roles;
		$this->roles = $roles;
	}
	/*
	 * 首页动态信息
	 */
	public function home(){
		if($this->isPost()){
			if(!empty($_POST['role_id'])){
				$where['role_id'] = $_POST['role_id'];
			}else{
				$user_info = D('RoleView')->where('role.role_id = %d', session('role_id'))->find();
				$user['user_name'] = $user_info['user_name'];
				$user['department_name'] = $user_info['department_name'];
				$user['role_name'] = $user_info['role_name'];
				if(!session('?admin')){
					$where['role_id'] = array('in',implode(',', getSubRoleId()));
				}
			}
			$where['action_name'] = array('not in',array('completedelete','delete','view'));
			$where['module_name'] = array('in',array('business','customer','sign','leads'));
			$map['business.is_deleted'] = array('neq',1);
			$map['customer.is_deleted'] = array('neq',1);
			$map['sign.sign_id'] = array("gt",0);
			$map['_logic'] = 'or';
			$where['_complex'] = $map;

			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$log = D('ActionLogView')->where($where)->page($p,10)->order('create_time desc')->select();
			$logCount = D('ActionLogView')->where($where)->count();
			$page = ceil($logCount/10);
			$action_name = array('add'=>'新建','delete'=>'删除','view'=>'查看','edit'=>'修改','sign_in'=>'进行');
			$module_name = array('customer'=>'客户','business'=>'商机','sign'=>'签到');
			$list = array();

			foreach($log as $k=>$v){
				$role = array();
				if($v['module_name'] != 'sign'){
					$aname = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('name');
					$sign_log = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('log');
					if(empty($aname) && empty($sign_log)){
						continue;
					}
				}
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
					$tmp['sign_customer_name'] = M('Customer')->where('customer_id = %d',$v['sign_customer_id'])->getField('name');
				}else{
					$tmp['aname'] = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('name');
					$tmp['url'] = 'm='.ucfirst($v['module_name']).'&a=view&id='.$v['action_id'];
				}
				$tmp['id'] = $v['action_id'];
				$tmp['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
				$list[] = $tmp;
			}
			if(empty($_POST['role_id'])){
				$count = array();
				$time_now = time();
				$compare_time = $time_now - 86400*3;
				$customer['owner_role_id'] = array('in',implode(',', getSubRoleId()));
				$customer['is_deleted'] != 1;
				$customer['update_time'] = array('gt',$compare_time);
				$count['customer'] = M('Customer')->where($customer)->count();
				$business['owner_role_id'] = array('in',implode(',', getSubRoleId()));
				$business['is_deleted'] != 1;
				$business['update_time'] = array('gt',$compare_time);
				$count['business'] = M('Business')->where($business)->count();
				$daily['role_id'] = array('in',implode(',', getSubRoleId()));
				$daily['update_date'] = array('gt',$compare_time);
				$count['log'] = M('Log')->where($daily)->count();
				$data['count'] = $count;
				$data['user'] = $user;
			}
			$data['page'] = $page;
			$data['list'] = $list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	/*
	 * 3.2版本首页动态信息--个人信息拆分
	 */
	public function homeuser(){
		if($this->isPost()){
			$user_info = D('RoleView')->where('role.role_id = %d', session('role_id'))->find();
			$user['user_name'] = $user_info['user_name'];
			$user['department_name'] = $user_info['department_name'];
			$user['role_name'] = $user_info['role_name'];
			$count = array();
			$time_now = time();
			$compare_time = $time_now - 86400*3;
			$customer_below_ids = getPerByAction('customer','index');
			$customer['owner_role_id'] = array('in',$customer_below_ids);
			$customer['is_deleted'] = 0;
			$customer['update_time'] = array('gt',$compare_time);
			$count['customer'] = M('Customer')->where($customer)->count();
			$business_below_ids = getPerByAction('business','index');
			$business['owner_role_id'] = array('in',$business_below_ids);
			$business['is_deleted'] = 0;
			$business['update_time'] = array('gt',$compare_time);
			$count['business'] = M('Business')->where($business)->count();
			$daily['role_id'] = array('in',implode(',', getSubRoleId()));
			$daily['update_date'] = array('gt',$compare_time);
			$daily['category_id'] = array('neq',1);
			$count['log'] = M('Log')->where($daily)->count();
			$data['count'] = $count;
			$data['user'] = $user;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	
	//获得岗位权限的模块数组
	public function permission_list(){
		$m_permission = M('Permission');
		$row = $m_permission->where(array('position_id'=>session('position_id')))->field('url')->select();
		$permission = array();
		$model = '';
		$existModel = array('customer','business','knowledge','contacts','product','leads','contract','task','announcement','examine');
		foreach($row as $v){
			$tmp = explode('/',$v['url']);
			if($model != $tmp[0] && $tmp[1] == 'index'){
				$model = $tmp[0];
				if(in_array($model,$existModel) && !in_array($model,$permission)){
					$permission[] = $model;
				}
			}
		}
		return $permission;
	}
	
	/*
	 * 3.2版本首页动态信息
	 */
	public function homenew(){
		if($this->isPost()){
			$m_customer = M('Customer');
			$m_leads = M('Leads');
			$m_business = M('Business');
			$m_task = M('Task');
			$m_sales = M('Sales');
			$m_event = M('Event');
			$m_examine = M('Examine');
			$m_contacts = M('Contacts');
			$m_contract = M('Contract');
			$m_product = M('Product');
			$m_fields = M('Fields');
			$m_comment = M('Comment');
			$m_praise = M('Praise');
			if(!empty($_POST['role_id'])){
				$where['role_id'] = $_POST['role_id'];
			}else{
				if(!session('?admin')){
					$where['role_id'] = array('in',implode(',', getSubRoleId()));
				}
			}
			$where['action_name'] = array('not in',array('completedelete','delete','view'));
			$by = isset($_GET['by']) ? $_GET['by'] : '';
			
			//获取权限
			$permission_list = $this->permission_list();
			//无权限控制的模块
			$arr_a = array('sign','log');
			//权限模块（数组组合）
			if(session('?admin')){
				$my_permission = array('business','customer','sign','log','leads','sales','user','event','contract','product');
			}else{
				$my_permission = array_merge($arr_a,$permission_list);
				if(!in_array($by,$my_permission) && $by != ''){
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}
			
			switch ($by) {
				case 'business' : $where['module_name'] = 'business'; break;
				case 'customer' : $where['module_name'] = 'customer'; break;
				case 'sign' : $where['module_name'] = 'sign'; break;
				case 'log' : $where['module_name'] = 'log'; break;
				case 'leads' : $where['module_name'] = 'leads';break;
				case 'sales' : $where['module_name'] = 'sales';break;
				case 'user' : $where['module_name'] = 'user';break;
				case 'event' : $where['module_name'] = 'event';break;
				case 'contract' : $where['module_name'] = 'contract';break;
				case 'product' : $where['module_name'] = 'product';break;
				//default :  $where['module_name'] = array('in',array('business','customer','sign','log','leads','sales','user','event','contract','product')); break;
				default :  $where['module_name'] = array('in',$my_permission); break;
			}
			$map['business.is_deleted'] = array('neq',1);
			$map['customer.is_deleted'] = array('neq',1);
			$map['leads.is_deleted'] = array('neq',1);
			$map['contract.is_deleted'] = array('neq',1);
			$map['sign.sign_id'] = array("gt",0);
			$map['log.log_id'] = array("gt",0);
			$map['_logic'] = 'or';
			$where['_complex'] = $map;
			$d_actionlog_view = D('ActionLoglistView');
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$log = $d_actionlog_view->where($where)->page($p,10)->order('create_time desc')->select();
			$logCount = $d_actionlog_view->where($where)->count();
			$page = ceil($logCount/10);
			$action_name = array('add'=>'新建','delete'=>'删除','view'=>'查看','edit'=>'修改','sign_in'=>'进行','advance'=>'推进','mylog_add'=>'新建','log_delete'=>'删除','check'=>'审核','revokecheck'=>'撤销审核');
			$module_name = array('customer'=>'客户','business'=>'商机','sign'=>'签到','log'=>'日志','leads'=>'线索','contract'=>'合同','sales'=>'销售');
			$list = array();
			foreach($log as $k=>$v){
				$role = array();
				$role = D('RoleView')->where('role.role_id = %d', $v['role_id'])->find();
				$tmp = array();
				$tmp['role_id'] = $v['role_id'];
				$tmp['user_name'] = $role['user_name'];
				$tmp['role_name'] = $role['department_name'].'-'.$role['role_name'];
				$tmp['img'] = $role['img'];
				$tmp['content'] = $action_name[$v['action_name']].'了'.$module_name[$v['module_name']];
				if('sign' == $v['module_name']){
					$tmp['type'] = 9;
					$tmp['log'] = $v['log'];
					$tmp['address'] = $v['address'];
					$tmp['x'] = $v['x'];
					$tmp['y'] = $v['y'];
					$tmp['title'] = $v['title'];
					$tmp['sign_customer_id'] = $v['sign_customer_id'];
					$sign_customer_name = M('Customer')->where('customer_id = %d',$v['sign_customer_id'])->getField('name');
					$tmp['sign_customer_name'] = !empty($sign_customer_name)?$sign_customer_name:'';
				}else{
					//获取阶段
					switch ($v['module_name']) {
						case 'log' :
							$d_log = D('LogView');
							$log_info = $d_log->where('log_id = %d',$v['action_id'])->find();
							if(empty($log_info['subject'])){
								$tmp['subject'] = msubstr($log_info['content'],0,15);
							}else{
								$tmp['subject'] = $log_info['subject'];
							}
							
							//过滤html代码
							$str = htmlspecialchars_decode($log_info['content']); //内容全部反编译
							$str = preg_replace( "@<script(.*?)</script>@is", "", $str );
							$str = preg_replace( "@<div(.*?)</div>@is", "", $str );
							$str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str );
							$str = preg_replace( "@<style(.*?)</style>@is", "", $str );
							$str = preg_replace( "@<(.*?)>@is", "", $str );
							$str = str_replace( "&nbsp;","", $str );
							$content_info = preg_replace("/<(.*?)>/","",$str);
							
							$tmp['content'] = msubstr($content_info,0,50);
							$comment_cont = $m_comment->where("module='log' and module_id=%d", $log_info['log_id'])->count();
							$tmp['comment_count'] = $comment_cont;
							$tmp['praise_count'] = $m_praise->where('log_id = %d',$log_info['log_id'])->count();
							if($m_praise->where('log_id = %d and role_id = %d',$log_info['log_id'],session('role_id'))->find()){
								$tmp['is_praised'] = 1;
							}else{
								$tmp['is_praised'] = 0;
							}
							if($log_info['category_id'] == 0){
								$category_id = 1;
							}else{
								$category_id = $log_info['category_id'];
							}
							$tmp['category_id'] = $category_id;
							$tmp['type'] = 12;
							break;
						case 'customer' :
							$customer_info = $m_customer ->where('customer_id =%d',$v['action_id'])->find();
							$tmp['customer_id'] = $v['action_id'];
							$tmp['dataa'] = $customer_info['industry'];
							//$tmp['dataa_field'] = '';
							$tmp['datab'] = $customer_info['origin'];
							$tmp['type'] = 3;
							break;
						case 'contract' :
							$contract_info = $m_contract ->where('contract_id =%d',$v['action_id'])->find();
							if($contract_info['type'] == 1){
								$customer_name = M('customer')->where('customer_id = %d',$contract_info['customer_id'])->getField('name');
							}elseif($contract_info['type'] == 2){
								$customer_name = M('supplier')->where('supplier_id = %d',$info['supplier_id'])->getField('name');
							}
							$tmp['dataa'] = $customer_name;
							$tmp['datab'] = $contract_info['status'];
							$tmp['type'] = 8;
							break;
						case 'business' :
							$business_info = $m_business ->where('business_id =%d',$v['action_id'])->find();
							$status_name = M('business_status')->where('status_id =%d',$business_info['status_id'])->getField('name');
							$tmp['dataa'] = $status_name;
							$tmp['datab'] = $business_info['nextstep_time'] ? date("Y-m-d H:i", $business_info['nextstep_time']):'';
							$tmp['type'] = 4;
							break;
						case 'sales' :
							$sales_info = $m_sales ->where('sales_id =%d',$v['action_id'])->find();
							if($sales_info['status'] == 97){$status ='未出库';}
							elseif($sales_info['status'] == 98){$status ='已出库';}
							elseif($sales_info['status'] == 99){$status ='未入库';}
							else{$status ='已入库';}
							$customer_name = M('customer')->where('customer_id = %d',$sales_info['customer_id'])->getField('name');
							$tmp['dataa'] = $customer_name;
							$tmp['datab'] = $status;
							$tmp['type'] = 0;
							break;
						case 'leads' :
							$leads_info = $m_leads ->where('leads_id =%d',$v['action_id'])->find();
							$tmp['dataa'] = $leads_info['source'];
							$tmp['datab'] = $leads_info['nextstep_time'] ? date("Y-m-d H:i", $leads_info['nextstep_time']):'';
							$tmp['type'] = 7;
							break;
						case 'product' :
							$product_info = $m_product ->where('product_id =%d',$v['action_id'])->find();
							$category_name = M('product_category')->where('category_id =%d',$product_info['category_id'])->getField('name');
							$tmp['dataa'] = $category_name;
							$tmp['datab'] = $product_info['standard'];
							$tmp['type'] = 6;
							break;
						case 'event' :
							$event_info = $m_event ->where('event_id =%d',$v['action_id'])->find();
							$start_date = $event_info['start_date'] ? date("Y-m-d H:i", $event_info['start_date']):'';
							$end_date = $event_info['end_date'] ? date("Y-m-d H:i", $event_info['end_date']):'';
							$tmp['dataa'] = $start_date;
							$tmp['datab'] = $end_date;
							$tmp['type'] = 0;
							break;
						case 'user' :
							$user_info = D('UserView') ->where('user.user_id =%d',$v['action_id'])->find();
							$tmp['dataa'] = $user_info['category_name'];
							$tmp['datab'] = $user_info['role_name'];
							$tmp['type'] = 1;
							break;
					}
					if($v['module_name'] == 'contract'){
						$aname = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('number');
					}else{
						$aname = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('name');
					}
					$tmp['aname'] = !empty($aname)?$aname:'';
				}
				$tmp['id'] = $v['action_id'];
				$tmp['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
				$list[] = $tmp;
			}
			$data['page'] = $page;
			$data['list'] = $list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	//公告
	public function index(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			getDateTime('announcement');
			$m_announcement = M('announcement');
			if($_REQUEST["search"]) {
				$where['title'] = array('like','%'.$_REQUEST["search"].'%');
			}
			if($this->_permissionRes) $where['role_id'] = array('in',getPerByAction('announcement','index'));
			$where['department'] = array('like', '%('.session('department_id').')%');
			$where['status'] = array('eq', 1);
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;

			$announcement_list = $m_announcement->where($where)->order('order_id')->field('title,announcement_id,update_time,role_id')->select();
			$announcementCount = $m_announcement->where($where)->count();
			$page = ceil($announcementCount/10);

			foreach($announcement_list as $k=>$v){
				$announcement_list[$k]['role_name'] = M('User')->where(array('role_id'=>$v['role_id'],'status'=>1))->getField('name');
				$owner_role_id = $v['role_id'];
				//获取操作权限
				// $announcement_list[$k]['permission'] = permissionlist('announcement',$owner_role_id);
				$announcement_list[$k]['permission'] = permissionlist('announcement');
			}
			if(empty($announcement_list)){
				$announcement_list = array();
			}
			$data['page'] = $page;
			$data['list'] = $announcement_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	//公告详情
	public function view(){
		// if($this->roles == 2){
		// 	$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		// }
		if($this->isPost()){
			if($_GET['id']){
				$announcement = M('announcement')->where('announcement_id = %d',intval($_GET['id']))->find();
				if($announcement){
					if(getPerByAction('announcement','view')){
						$announcement['name'] = M('User')->where('role_id = %d',$announcement['role_id'])->getField('name');
						$this->ajaxReturn($announcement,'success',1);
					}else{
						$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
					}
				}else{
					$this->ajaxReturn('数据不存在或已删除！','数据不存在或已删除！',2);
				}
			}
		}
	}

	//收件箱
	/* public function inbox(){
		if($this->isPost()){
			import("@.ORG.Page");
			$p1 = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$m_r_message = D('MessageReceiveView');
			$r_where['to_role_id'] = session('role_id');
			$r_where['message.status'] = array('neq', 1);
			if($_REQUEST["name"]) {
				$r_where['content'] = array('like','%'.$_REQUEST["name"].'%');
			}
			$receive_list = $m_r_message->where($r_where)->order('read_time<>0 asc,send_time desc')->page($p1.',10')->field('from_role_id,to_role_id,message_id,content,send_time,read_time')->select();
			$count1 = $m_r_message->where($r_where)->count();
			$page = ceil($count1/10);
			if(empty($receive_list)){
				$receive_list = array();
			}else{
				foreach($receive_list as &$v){
					if(empty($v['from_role_id'])){
						$v['from_user_name'] = "系统管理员";
					}else{
						$user_from = getUserByRoleId($v['from_role_id']);
						$v['from_user_name'] = $user_from['user_name'];
					}
					$user_to = getUserByRoleId($v['to_role_id']);
					$v['to_user_name'] = $user_to['user_name'];
				}
			}
			$data['list'] = $receive_list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	//发件箱
	public function outbox(){
		if($this->isPost()){
			import("@.ORG.Page");
			$p1 = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$m_s_message = D('MessageSendView');
			$s_where['from_role_id'] = session('role_id');
			$s_where['message.status'] = array('neq', 2);
			if($_REQUEST["name"]) {
				$s_where['content'] = array('like','%'.$_REQUEST["name"].'%');
			}
			$send_list = $m_s_message->where($s_where)->order('send_time desc')->page($p1.',10')->field('from_role_id,message_id,to_role_id,content,send_time,read_time')->select();
			$count1 = $m_s_message->where($s_where)->count();
			$page = ceil($count1/10);
			if(empty($send_list)){
				$send_list = array();
			}else{
				foreach($send_list as &$v){
					$user_from = getUserByRoleId($v['from_role_id']);
					$v['from_user_name'] = $user_from['user_name'];
					if(empty($v['to_role_id'])){
						$v['from_user_name'] = '系统管理员';
					}else{
						$user_to = getUserByRoleId($v['to_role_id']);
						$v['to_user_name'] = $user_to['user_name'];
					}
				}
			}
			$data['list'] = $send_list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	} */

	//消息列表
	public function message(){
		$m_message = M('message');
		$role_id = session('role_id');

		$where['to_role_id'] = $role_id;
		$where['from_role_id'] = $role_id;
		$where['_logic'] = 'OR';

		$message_list = $m_message->where($where)->select();

		$role_id_array = array();
		foreach($message_list as $v){
			$temp = $v['from_role_id'] == $role_id ? $v['to_role_id'] : $v['from_role_id'] ;
			$role_id_array[$temp] = $temp;
			if($v['read_time'] == 0){
				$data['read_time'] = time();
				M('message')->where('to_role_id = %d',$role_id)->save($data);
			}
		}

		$role_where['role_id'] = array('in', $role_id_array);
		$role_list = D('RoleView')->where($role_where)->getField('user_name,role_id,img', true);

		$data_array = array();

		foreach($role_list as $k=>$v){
			$temp_role_id = $v['role_id'];

			$map['to_role_id&from_role_id'] =array($role_id,$temp_role_id,'_multi'=>true);
			$map['from_role_id&to_role_id'] =array($role_id,$temp_role_id,'_multi'=>true);
			$map['_logic'] = 'or';

			$res = $m_message->where($map)->order('send_time desc')->find();

			$temp_role['user_name'] = $v['user_name'];
			$temp_role['role_id'] = $v['role_id'];
			$temp_role['img'] = $v['img'];
			$temp_role['content']  = $res['content'];
			$temp_role['last_send_time']  = date('Y年m月d日 H:i', $res['send_time']);

			$data_array_info[] = $temp_role;
		}
		//二维数组排序
		$sort = array(
        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
        'field'     => 'last_send_time',       //排序字段
		);
		$arrSort = array();
		foreach($data_array_info AS $uniqid => $row){
			foreach($row AS $key=>$value){
				$arrSort[$key][$uniqid] = $value;
			}
		}
		if($sort['direction']){
			array_multisort($arrSort[$sort['field']], constant($sort['direction']), $data_array_info);
		}
		//$data_array = array_multisort('last_send_time','SORT_DESC',$data_array_info);
		//系统消息
		$m_message = M('Message');
		$system['to_role_id'] = $role_id;
		$system['from_role_id'] = 0;
		$system['read_time'] = 0;
		$system_count = $m_message->where($system)->count();
		$data['system_count'] = $system_count;
		//公告数量
		$time_now = time();
		$compare_time = $time_now - 86400*3;//3天范围
		$m_announcement = M('announcement');
		$announcement['department'] = array('like', '%('.session('department_id').')%');
		$announcement['status'] = array('eq', 1);
		$announcement['update_time'] = array('gt',$compare_time);
		$data['announcement_count'] = $m_announcement->where($announcement)->count();
		//日志评论数量
		$log_list = M('log')->where(array('role_id'=>$role_id))->select();
		foreach($log_list as $k=>$v){
			$comment['module'] = 'log';
			$comment['module_id'] = $v['log_id'];
			$comment_list = M('comment')->where($comment)->select();
			if($comment_list){
				foreach($comment_list as $v){
					if($v['update_time'] > $compare_time){
						$log_update = 1;
					}
				}
			}
		}
		if($log_update == 1){
			$data['log_count'] = 0;
		}else{
			$data['log_count'] = 0;
		}
		if($data_array_info){
			$data['message'] = $data_array_info;
		}else{
			$data['message'] = array();
		}
		$this->ajaxReturn($data,'success',1);
	}
	//系统消息
	public function system_message(){
		if($this->isPost()){
			$m_message = M('Message');
			$role_id = session('role_id');
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$message_list = $m_message->where(array('to_role_id'=>$role_id,'from_role_id'=>0))->page($p,'10')->order('send_time desc')->select();
			foreach($message_list as $k=>$v){
				$now_time = time();
				$m_message->where('message_id = %d',$v['message_id'])->setField('read_time',$now_time);
			}
			$count = $m_message->where(array('to_role_id'=>$role_id,'from_role_id'=>0))->count();
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$page = ceil($count/10);
			$data_array = empty($message_list) ? array() : $message_list;
			$data['list'] = $data_array;
			$data['page'] = $page;
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		}
	}

	//站内信历史详情
	public function messagehistory(){
		$m_message = M('message');
		$role_id = $_REQUEST['role_id'];
		$page = isset($_POST['p']) ? intval($_POST['p']) : 1 ;

		$map['to_role_id&from_role_id'] =array($role_id,session('role_id'),'_multi'=>true);
		$map['from_role_id&to_role_id'] =array($role_id,session('role_id'),'_multi'=>true);
		$map['_logic'] = 'or';

		$res = $m_message->where($map)->order('send_time desc')->page($page, '20')->select();
		$count_num = $m_message->where($map)->count();
		$page = ceil($count_num/20);
		foreach($res as $k=>$v){
			$temp['message_id'] = $v['message_id'];
			$temp['content'] = $v['content'];
			$temp['send_time'] =  $v['send_time'];
			$temp['self'] = session('role_id') == $v['from_role_id'] ? 1 : 0;
			$data_array[] = $temp;
		}
		$data_array = empty($data_array) ? array() : $data_array;
		$data['data'] = $data_array;
		$data['page'] = $page;
		$data['status'] = 1;
		$data['info'] = 'success';
		$this->ajaxReturn($data,'JSON');
	}

	//删除站内信
	public function boxdelete(){
		if($this->isPost()){
			$message_id = intval($_GET['message_id']);
			if($message_id){
				if(M('Message')->where(array('message_id'=>$message_id))->delete()){
					$this->ajaxReturn('','success',1);
				}else{
					$this->ajaxReturn('','error',2);
				}
			}else{
				$this->ajaxReturn('','error',2);
			}
		}
	}


	//站内信详情
	public function boxview(){
		if($this->isPost()){
			$id = intval($_GET['id']);
			if($id){
				$m_message = D('MessageView');
				$where['message_id'] = $id;
				$where['_complex'] = array('to_role_id'=>session('role_id'),'from_role_id'=>session('role_id'),'_logic'=>'or');
				$info = $m_message->where($where)->order('read_time<>0 asc,send_time desc')->page($p1.',10')->field('send_time,content,to_role_id,from_role_id')->find();
				if($info){
					if($info['read_time'] == 0 && $info['to_role_id'] == session('role_id')){
						$m_message->where(array('message_id'=>$id,'to_role_id'=>session('role_id')))->save(array('read_time'=>time()));
					}
					if($info['from_role_id'] != session('role_id')){
						$name = M('User')->where('role_id = %d',$info['from_role_id'])->getField('name');
					}else{
					    $name = M('User')->where('role_id = %d',$info['to_role_id'])->getField('name');
					}
					$info['name'] = $name ? $name : '系统管理员';
					foreach($info as &$v){
						$v = empty($v) ? ' ' : $v;
					}
					$this->ajaxReturn($info,'success',1);
				}else{
					$this->ajaxReturn('获取数据出错','获取数据出错',2);
				}
			}else{
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
		}
	}

	//发送站内信
	public function send(){
		$m_token = M('Userudid');
		if($this->isPost()){
			if($_POST['to_role_id']){
				$role_id = explode(',',trim($_POST['to_role_id']));
				foreach($role_id as $v){
					$to_role = intval($v);
					sendMessage($to_role,trim($_POST['content']));
					$where = array();
					$where['role_id'] = $v;
					$where['online'] = 1;
					$model = $m_token->where($where)->select();
					foreach($model as $val){
						if($val == 1){
							$token_ios[] = $m_token->where('role_id = %d',$v)->getField('token');
						}elseif($val == 2){
							$token_and[] = $m_token->where('role_id = %d',$v)->getField('token');
						}
					}
				}
				$title = '站内信';
				if($token_ios){
					Xinge($token_ios,$title,$_POST['content'],2,1);
				}
				if($token_and){
					Xinge($token_and,$title,$_POST['content'],2,2);
				}
				$this->ajaxReturn('','发送成功！',1);
			}
		}
	}


	public function comment1(){
		$role_id = session('role_id');
		/* $time_now = time();
		$compare_time = $time_now - 86400*3;//3天范围
		$where['update_time'] = array('gt',$compare_time); */
		$where['module'] = 'log';
		$comment_list = D('CommentRoleView')->where($where)->order('update_time desc')->select();
		if($comment_list){
			//去除二维数组相同值
			function array_multi_unique($ar, $filter=array()){
				if(!empty($filter)) {
					$_v = array_fill_keys($filter, ' ');
					$_ar = array();
					foreach($ar as $k => $v) {
						$_ar[$k] = array_intersect_key($v, $_v);
					}
				} else {
					$_ar = $ar;
				}
				$_ar = array_map('serialize', $_ar);
				$_ar = array_unique($_ar);
				$_ar = array_map('unserialize', $_ar);

				if(!empty($filter)) {
					return array_intersect_key($ar, $_ar);
				} else {
					return $_ar;
				}
			}
			$comment_arr = array_multi_unique($comment_list, array('module_id'));
			foreach($comment_arr as $k=>$v){
				$log_info = D('LogRoleView')->where(array('log_id'=>$v['module_id']))->find();
				$comment_arr[$k] = array_merge($log_info,$v);
			}
			foreach ($comment_arr as $k => $v) {
				$edition[] = $v['update_time'];
			}
			array_multisort($edition, SORT_ASC, $comment_arr);
			$this->ajaxReturn($comment_arr,'success',1);
		}else{
			$this->ajaxReturn('','error',2);
		}
	}
	public function comment2(){
		$role_id = session('role_id');
		$page = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
		$comment_sql = M()->query("(select * from `mxcrm_comment` where module='log' and to_role_id = ".$role_id." group by module_id order by create_time desc) order by create_time desc limit 10");
		$comment_count_array = mysql_query("select count(*) from `mxcrm_comment` where module='log' and to_role_id = ".$role_id." group by module_id");
		while($count = mysql_fetch_array($comment_count_array)){
			$count_array[] = $count;
		}
		$count_num = count($count_array);
		$comment_list=array();
		while($row = mysql_fetch_array($comment_sql)){
			$comment_list[] = $row;
		}
		foreach($comment_list as $k=>$v){
			$log_info = D('LogRoleView')->where(array('log_id'=>$v['module_id']))->find();
			$omment_list[$k] = array_merge($log_info,$v);
		}
		$page = ceil($count_num/10);
		$data['comment_list'] = $omment_list;
		$data['page'] = $page;
		if($comment_list){
			$this->ajaxReturn($data,'success',1);
		}else{
			$this->ajaxReturn('','error',2);
		}
	}
	//查看全部评论
	public function comment3(){
		$where['to_role_id'] = session('role_id');
		$where['module'] = 'log';
		$p = isset($_POST['p']) ? intval($_POST['p']) : 1;
		$comment_list = D('CommentRoleView')->where($where)->page($p.',10')->order('update_time desc')->select();
		foreach($comment_list as $k=>$v){
			$log_info = D('LogRoleView')->where(array('log_id'=>$v['module_id']))->find();
			$comment_list_array[$k] = array_merge($log_info,$v);
		}
		foreach($comment_list_array as $k=>$v){
			if($v){
				$comment_list_info[] = $v;
			}
		}
		$comment_count = count($comment_list_info);
		$page = ceil($comment_count/10);
		$data['comment_list'] = $comment_list_info;
		$data['page'] = $page;
		if($comment_list_info){
			$this->ajaxReturn($data,'success',1);
		}else{
			$this->ajaxReturn('','error',2);
		}
	}
	//评论我的
	public function comment(){
		if($this->isPost()){
			$where['to_role_id'] = session('role_id');
			$where['module'] = 'log';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1;
			$m_comment = M('Comment');
			$m_role = M('Role');
			$m_user = M('User');
			$m_position = M('Position');
			$m_role_department = M('RoleDepartment');
			$m_log = M('Log');
			$d_comment_log = D('CommentLogView');
			$comment_list = $d_comment_log->where($where)->page($p.',10')->order('update_time desc')->select();
			foreach($comment_list as $k=>$v){
				$log_info = $m_log->where('log_id = %d',$v['module_id'])->find();
				if($log_info){
					$comment_list[$k]['subject'] = $log_info['subject'];
					$comment_list[$k]['update_date'] = $log_info['update_date'];
					$comment_list[$k]['role_id'] = $log_info['role_id'];
					$comment_list[$k]['log_id'] = $log_info['log_id'];
					$role_info = $m_role->where(array('role_id'=>$v['creator_role_id']))->field('user_id,position_id')->find();
					$log_role_info = $m_role->where(array('role_id'=>$v['role_id']))->field('user_id,position_id')->find();
					$user_info = $m_user->where(array('user_id'=>$role_info['user_id']))->field('name,img')->find();
					$log_user_info = $m_user->where(array('user_id'=>$log_role_info['user_id']))->field('name,img')->find();
					$department_id = $m_position->where(array('position_id'=>$role_info['position_id']))->getField('department_id');
					$log_department_id = $m_position->where(array('position_id'=>$log_role_info['position_id']))->getField('department_id');
					$comment_list[$k]['user_name'] = $user_info['name'];
					$comment_list[$k]['log_user_name'] = $log_user_info['name'];
					$comment_list[$k]['img'] = $user_info['img'];
					$comment_list[$k]['log_img'] = $log_user_info['img'];
					$comment_list[$k]['role_name'] = $m_position->where(array('position_id'=>$role_info['position_id']))->getField('name');
					$comment_list[$k]['log_role_name'] = $m_position->where(array('position_id'=>$log_role_info['position_id']))->getField('name');
					$comment_list[$k]['department_name'] = $m_role_department->where(array('department_id'=>$department_id))->getField('name');
					$comment_list[$k]['log_department_name'] = $m_role_department->where(array('department_id'=>$log_department_id))->getField('name');
				}
				
			}
			$comment_count = D('CommentLogView')->where($where)->count();
			$page = ceil($comment_count/10);
			$data['comment_list'] = $comment_list;
			$data['page'] = $page;
			if($comment_list){
				$this->ajaxReturn($data,'success',1);
			}else{
				$data['comment_list'] = array();
				$data['page'] = 0;
				$this->ajaxReturn($data,'success',1);
			}
		}
	}
	//查看我评论的
	public function replay(){
		if($this->isPost()){
			$m_role = M('Role');
			$m_user = M('User');
			$m_position = M('Position');
			$m_role_department = M('RoleDepartment');
			$m_log = M('Log');
			$d_comment_log = D('CommentLogView');
			$m_comment = M('Comment');
			$m_log = M('Log');
			$where['creator_role_id'] = session('role_id');
			$where['to_role_id'] = array('neq',0);
			$where['module'] = 'log';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1;
			$comment_list = $d_comment_log->where($where)->page($p.',10')->order('update_time desc')->select();
			foreach($comment_list as $k=>$v){
				$log_info = $m_log->where('log_id = %d',$v['module_id'])->find();
				$comment_list[$k]['subject'] = $log_info['subject'];
				$comment_list[$k]['update_date'] = $log_info['update_date'];
				$comment_list[$k]['role_id'] = $log_info['role_id'];
				$comment_list[$k]['log_id'] = $log_info['log_id'];
				$role_info = $m_role->where(array('role_id'=>$v['creator_role_id']))->field('user_id,position_id')->find();
				$log_role_info = $m_role->where(array('role_id'=>$v['role_id']))->field('user_id,position_id')->find();
				$user_info = $m_user->where(array('user_id'=>$role_info['user_id']))->field('name,img')->find();
				$log_user_info = $m_user->where(array('user_id'=>$log_role_info['user_id']))->field('name,img')->find();
				$department_id = $m_position->where(array('position_id'=>$role_info['position_id']))->getField('department_id');
				$log_department_id = $m_position->where(array('position_id'=>$log_role_info['position_id']))->getField('department_id');
				$comment_list[$k]['user_name'] = $user_info['name'];
				$comment_list[$k]['log_user_name'] = $log_user_info['name'];
				$comment_list[$k]['img'] = $user_info['img'];
				$comment_list[$k]['log_img'] = $log_user_info['img'];
				$comment_list[$k]['role_name'] = $m_position->where(array('position_id'=>$role_info['position_id']))->getField('name');
				$comment_list[$k]['log_role_name'] = $m_position->where(array('position_id'=>$log_role_info['position_id']))->getField('name');
				$comment_list[$k]['department_name'] = $m_role_department->where(array('department_id'=>$department_id))->getField('name');
				$comment_list[$k]['log_department_name'] = $m_role_department->where(array('department_id'=>$log_department_id))->getField('name');
			}
			$comment_count = $d_comment_log->where($where)->count();
			$page = ceil($comment_count/10);
			$data['comment_list'] = $comment_list;
			$data['page'] = $page;
			if($comment_list){
				$this->ajaxReturn($data,'success',1);
			}else{
				$data['comment_list'] = array();
				$data['page'] = 0;
				$this->ajaxReturn($data,'success',1);
			}
		}
	}
	//获取权限
	public function permission(){
		if($this->isPost()){
			$params = json_decode($_POST['params'],true);
			$m = trim($params['module']);
			$a = trim($params['action']);
			if(checkPerByAction($m, $a)){
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('您没有权限','您没有权限',-2);
			}
		}
	}
	//获取自定义字段
	public function fields(){
		if($this->isPost()){
			$m_fields = M('Fields');
			$params = json_decode($_POST['params'],true);
			$m = trim($params['module']);
			$a = trim($params['action']);
			$leads_id = intval($params['leads_id']);
			$array_m = array('business','leads','customer','contacts','product');
			//创建一个空对象
			//$empty_object = new stdClass();
			$empty_object = '';
			$where = array();
			if($m == 'customer'){
				$where['field'] = array('not in',array('tags','customer_owner_id'));
			}
			$where['model'] = $m;
			if(checkPerByAction($m, $a)){
				if($m && in_array($m,$array_m)){
					$fields_list = $m_fields->where($where)->order('is_main desc,order_id asc')->field('is_main,field,name,form_type,default_value,max_length,is_unique,is_null,is_validate,in_add,input_tips,setting')->select();
					if($m == 'customer' && $a == 'edit'){
						$fields_contacts[0]['is_main'] = 1;
						$fields_contacts[0]['field'] = 'contacts_id';
						$fields_contacts[0]['name'] = '首要联系人';
						$fields_contacts[0]['form_type'] = 'contacts';
						$fields_contacts[0]['default_value'] = '';
						$fields_contacts[0]['max_length'] = '';
						$fields_contacts[0]['is_unique'] = 0;
						$fields_contacts[0]['is_null'] = 0;
						$fields_contacts[0]['is_validate'] = 0;
						$fields_contacts[0]['in_add'] = 1;
						$fields_contacts[0]['input_tips'] = '';
						$fields_contacts[0]['setting'] = $empty_object;
					}
					foreach($fields_list as $k=>$v){
						if($v['field'] != 'contacts_id'){
							if($m == 'business' && $v['field'] == 'status_id'){
								//获取商机状态
								$business_status = M('BusinessStatus')->order('order_id asc')->select();
								foreach($business_status as $key=>$val){
									$fields_status[$val['status_id']] = $val['name'];
								}
								$fields_list[$k]['form_type'] = 'b_box';
								$setting['type'] = 'select';
								$setting['data'] = $fields_status;
								$fields_list[$k]['setting'] = $setting;
							}else{
								if($v['setting']){
									//将内容为数组的字符串格式转换为数组格式
									eval("\$setting = ".$v['setting'].'; ');
									$fields_list[$k]['setting'] = $setting;
								}else{
									$fields_list[$k]['setting'] = $empty_object;
								}
							}
						}
					}
					if($m == 'customer' || $m == 'leads'){
						$fields_list[$k+1]['is_main'] = 1;
						$fields_list[$k+1]['field'] = 'owner_role_id';
						$fields_list[$k+1]['name'] = '负责人';
						$fields_list[$k+1]['form_type'] = 'user';
						$fields_list[$k+1]['default_value'] = '';
						$fields_list[$k+1]['max_length'] = 255;
						$fields_list[$k+1]['is_unique'] = 0;
						$fields_list[$k+1]['is_null'] = 0;
						$fields_list[$k+1]['is_validate'] = 1;
						$fields_list[$k+1]['in_add'] = 1;
						$fields_list[$k+1]['input_tips'] = '';
						$fields_list[$k+1]['setting'] = $empty_object;
					}
					if($m == 'customer' && $a == 'edit'){
						$fields_list = array_merge($fields_contacts,$fields_list);
					}
					//客户下首要联系人字段
					if($m == 'customer' && $a == 'add'){
						//线索转换客户
						if(!empty($leads_id)){
							$leads_info = D('LeadsView')->where('leads.leads_id = %d',$leads_id)->find();
							//客户名称、客户地址和首要联系人信息
							foreach($fields_list as $k=>$v){
								$data_a = trim($leads_info[$v['field']]); //值
								if($v['form_type'] == 'address'){
									$address_array = str_replace(chr(10),' ',$data_a);
									$fields_list[$k]['default_value'] = $address_array;
								}else{
									$fields_list[$k]['default_value'] = $data_a;
								}
							}
						}
						$arr = array('con_name','saltname','con_email','con_post','con_qq','con_telephone','con_description');
						foreach($arr as $key=>$val){
							$arr_contacts = array('姓名'=>'con_name','尊称'=>'saltname','邮箱'=>'con_email','职位'=>'con_post','QQ'=>'con_qq','手机'=>'con_telephone','备注'=>'con_description');
							foreach($arr_contacts as $ke=>$va){
								if($val == $va){
									$field = $ke;
								}
							}
							$fields_list_contacts[$key]['is_main'] = 2;
							$fields_list_contacts[$key]['field'] = $val;
							$fields_list_contacts[$key]['name'] = $field;
							if($val == 'con_description'){
								$fields_list_contacts[$key]['form_type'] = 'textarea';
							}elseif($val == 'con_telephone'){
								$fields_list_contacts[$key]['form_type'] = 'mobile';
							}else{
								$fields_list_contacts[$key]['form_type'] = 'text';
							}
							switch ($val){
								case 'con_name' : $default_value = $leads_info['contacts_name'];break;
								case 'saltname' : $default_value = $leads_info['saltname'];break;
								case 'con_email' : $default_value = $leads_info['email'];break;
								case 'con_post' : $default_value = $leads_info['position'];break;
								case 'con_qq' : $default_value = '';break;
								case 'con_telephone' : $default_value = $leads_info['mobile'];break;
								case 'con_description' : $default_value = $leads_info['description'];break;
							}
							$fields_list_contacts[$key]['default_value'] = !empty($default_value) ? $default_value : '';
							$fields_list_contacts[$key]['max_length'] = '';
							$fields_list_contacts[$key]['is_unique'] = 0;
							$fields_list_contacts[$key]['is_null'] = 0;
							$fields_list_contacts[$key]['is_validate'] = 0;
							$fields_list_contacts[$key]['in_add'] = 1;
							$fields_list_contacts[$key]['input_tips'] = '';
							$fields_list_contacts[$key]['setting'] = $empty_object;
						}
						$fields_list = array_merge($fields_list,$fields_list_contacts);
					}
					//商机字段
					if($m == 'business'){
						//商机编号
						$business_custom = M('Config')->where('name = "business_custom"')->getField('value');
						// $business_max_id = $m_config->where(array('name'=>'business_code'))->getField('value');
						$business_max_id = M('Business')->max('business_id');
						$business_max_code = str_pad($business_max_id+1,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
						$code = $business_custom.date('Ymd').'-'.$business_max_code;

						$arr = array('code','name','customer_id','contacts_id','status_id','possibility');
						$fields_list_business = array();
						foreach($arr as $key=>$val){
							$arr_business = array('商机编号'=>'code','商机名称'=>'name','客户名称'=>'customer_id','联系人'=>'contacts_id','商机阶段'=>'status_id','可能性'=>'possibility');
							foreach($arr_business as $ke=>$va){
								if($val == $va){
									$field = $ke;
								}
							}
							$fields_list_business[$key]['is_main'] = 2;
							$fields_list_business[$key]['field'] = $val;
							$fields_list_business[$key]['name'] = $field;

							$setting = '';
							if($val == 'customer_id'){
								$fields_list_business[$key]['form_type'] = 'customer';
								$fields_list_business[$key]['setting'] = '';
							}elseif($val == 'contacts_id'){
								$fields_list_business[$key]['form_type'] = 'contacts';
								$fields_list_business[$key]['setting'] = '';
							}elseif($val == 'status_id'){
								//获取商机状态
								$business_status = M('BusinessStatus')->order('order_id asc')->select();
								foreach($business_status as $key1=>$val1){
									$fields_status[$val1['status_id']] = $val1['name'];
								}
								$fields_list_business[$key]['form_type'] = 'b_box';
								$setting['type'] = 'select';
								$setting['data'] = $fields_status;
								$fields_list_business[$key]['setting'] = $setting;
							}elseif($val == 'possibility'){
								$fields_list_business[$key]['form_type'] = 'box';
								$setting['type'] = 'select';
								$setting['data'] = array('10%'=>'10%','20%'=>'20%','30%'=>'30%','40%'=>'40%','50%'=>'50%','60%'=>'60%','70%'=>'70%','80%'=>'80%','90%'=>'90%','100%'=>'100%');
								$fields_list_business[$key]['setting'] = $setting;
							}else{
								$fields_list_business[$key]['form_type'] = 'text';
								$fields_list_business[$key]['setting'] = '';
							}
							
							$default_value = '';
							switch ($val){
								case 'code' : $default_value = $code;break;
								case 'name' : $default_value = $code;break;
							}
							$fields_list_business[$key]['default_value'] = !empty($default_value) ? $default_value : '';
							$fields_list_business[$key]['max_length'] = '';
							$fields_list_business[$key]['is_unique'] = 0;
							$fields_list_business[$key]['is_null'] = 0;
							$fields_list_business[$key]['is_validate'] = 0;
							$fields_list_business[$key]['in_add'] = 1;
							$fields_list_business[$key]['input_tips'] = '';
						}
						$fields_list = $fields_list_business;
					}
					$data['data'] = $fields_list;
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
				}else{
					$this->ajaxReturn('参数错误！','参数错误！',2);
				}
			}else{
				$this->ajaxReturn('您没有权限！','您没有权限！',-2);
			}
		}
	}
	//自定义字段验重
	//params : field 字段名, val 值 ,id 排除当前数据验重,model = 需要查询的模块名
	public function validate() {
		if($this->isPost()){
			$params = json_decode($_POST['params'],true);
			$model = trim($params['model']);
			$field = trim($params['field']);
			$val = trim($params['val']);
			if(!$val){
				$this->ajaxReturn('填写内容不能为空！','填写内容不能为空！',2);
			}
			if(!$field){
				$this->ajaxReturn('数据验证错误，请联系管理员！','数据验证错误，请联系管理员！',2);
			}
			$field_info = M('Fields')->where('model = "%s" and field = "%s"',$model,$field)->find();
			if($model == 'contacts'){
				$m_fields = $field_info['is_main'] ? D('contacts') : D('ContactsData');
			}elseif($model == 'customer'){
				$m_fields = $field['is_main'] ? D('Customer') : D('CustomerData');
			}elseif($model == 'product'){
				$m_fields = $field['is_main'] ? D('Product') : D('ProductData');
			}elseif($model == 'leads'){
				$m_fields = $field['is_main'] ? D('Leads') : D('LeadsData');
			}
			$where[$field] = array('eq',$val);
			if($params['id']){
                $where[$m_fields->getpk()] = array('neq',$params['id']);
            }
			if($m_fields->where($where)->find()) {
				$this->ajaxReturn('该数据已存在，请修改后提交！','该数据已存在，请修改后提交！',2);
			} else {
				$this->ajaxReturn('','success',1);
			}
		}
	}
	//编辑器类型字段展示
	public function content_info(){
		$m = $this->_get('fields_m','trim');
		$a = 'view';
		$field_name = $this->_get('field_name','trim');
		$data_id = $this->_get('id','intval');
		if(!$m || !$field_name || !$data_id){
			$content_error = '参数错误！';
		}
		
		if($m == 'leads'){
			$outdays = M('config') -> where('name="leads_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
			$where['have_time'] = array('egt',$outdate);
			$where['owner_role_id'] = array('neq',0);
			$where['leads_id'] = $data_id;
			$info = D('Leads')->where($where)->find();
			if(!in_array($info['owner_role_id'],getPerByAction('leads','view')) && session('admin') !== 1){
				$content_error = '您没有此权利';
			}
		}elseif($m == 'customer'){
			$info = D('CustomerView')->where('customer.customer_id = %d', $data_id)->find();
			if(!$info || $info['is_deleted'] == 1){
				$content_error = '客户不存在或已删除';
			}
			$outdays = M('config') -> where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
			if($info['owner_role_id'] != 0 && ($info['update_time'] > $outdate || $info['is_locked'] == 1)){
				if(!in_array($info['owner_role_id'],getPerByAction('customer','view')) && session('admin') !== 1){
					$content_error = '您没有此权利';
				}
			}
		}elseif($m == 'business'){
			$m_business = M('Business');
			$info = $m_business ->where('business_id = %d',$data_id)->find();
			if(!in_array($info['owner_role_id'],getPerByAction('business','view')) && session('admin') !== 1){
				$content_error = '您没有此权利';
			}
		}elseif($m == 'contract'){
			$contract = D('ContractView');
			$info = $contract->where('contract_id = %d',$data_id)->find();
			//权限判断
			if(!in_array($info['owner_role_id'], getPerByAction('contract','view')) && session('admin') !== 1) {
				$content_error = '您没有此权利';
			}
		}elseif($m == 'contacts'){
			$rContactsCustomer = M('RContactsCustomer');
			$d_contacts = D('ContactsView');
			$m_customer = M('Customer');
			$info = $d_contacts->where('contacts.contacts_id = %d' , $data_id)->find();
			if (!$info || $info['is_deleted'] == 1) {
				$content_error = '您没有此权利';
			}
			//检查权限
			$all_ids = getPerByAction('contacts','view');
			$customer_idArr = $m_customer->where(array('owner_role_id'=>array('in', $all_ids)))->getField('customer_id', true);
			$customer_id = $rContactsCustomer->where('contacts_id = %d', $data_id)->getField('customer_id');
			$owner_role_id = $m_customer->where('customer_id = %d',$customer_id)->getField('owner_role_id');
			
			//判断联系人所在客户是否在客户池，如果在则不判断权限
		
			 //查询客户数据
			$customer = D('CustomerView')->where('customer.customer_id = %d', $customer_id)->find();
			$outdays = M('config') -> where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
			$m_customer_share = M('customer_share')->select();
			$sharing_id = session('role_id');
			foreach($m_customer_share as $k=>$v){
				$by_sharing_id = explode(',',$v['by_sharing_id']);
				if(in_array($sharing_id,$by_sharing_id)){
					$customerid[] = $v['customer_id'];
				}
			}
			$is_share = in_array($customer_id,$customerid);
			if($customer['owner_role_id'] != 0 && ($customer['update_time'] > $outdate || $customer['is_locked'] == 1) && $is_share ==0 && !in_array($customer_id, $customer_idArr) && session('admin') !== 1){
				$content_error = '您没有此权利';
			}
		}
		if(empty($info) || empty($data_id)){
			$content_error = '数据不存在或已被删除！';
		}
		
		if(!empty($content_error)){
			$this->info = $content_error;
		}else{
			$this->info = $info[''.$field_name.''];
		}
		$this->display('Public:info');
	}
}
