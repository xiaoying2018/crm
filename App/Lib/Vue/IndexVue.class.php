<?php
/**
*CRM数据看板
*
**/
class IndexVue extends Action{
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('dynamic','index','fields','validate','loglist','filelist','work','scene')
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
	 * 获得岗位权限的模块数组
	 * @param 
	 * @author 
	 * @return 
	 */
	public function permission_list() {
		$m_permission = M('Permission');
		$row = $m_permission->where(array('position_id'=>session('position_id')))->field('url')->select();
		$permission = array();
		$model = '';
		$existModel = array('leads','customer','contacts','business','contract','knowledge','product','log','announcement','examine','event','task');
		if (session('?admin')) {
			$permission = $existModel;
		} else {
			foreach ($row as $v) {
				$tmp = explode('/',$v['url']);
				if ($model != $tmp[0] && $tmp[1] == 'index') {
					$model = $tmp[0];
					if (in_array($model,$existModel) && !in_array($model,$permission)) {
						$permission[] = $model;
					}
				}
			}
		}
		return $permission;
	}

	/**
	 * CRM数据看板
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			$m_customer = M('Customer');
			$m_contacts = M('Contacts');
			$m_business = M('Business');
			$m_log = M('Log');
			$m_contract = M('Contract');
			$m_user = M('User');
			//查询今日数据(首页简报)，默认为自己和下属的数据
			$briefing_role_ids = array();
			if (session('?admin')) {
				$briefing_role_ids = getSubRoleId(true, 1);
			} else {
				$briefing_role_ids = getSubRoleId();
			}
			$create_time = array();

			//今日时间范围
			// $start_time_day = strtotime(date('Y-m-d'));
			// $end_time_day = strtotime(date('Y-m-d'))+86400;
			// $create_time[0] = array('between',array($start_time_day,$end_time_day));

			//本周时间范围
			$now_date = date("Y-m-d"); //当前日期 
			$first = 1; //$first =1 表示每周星期一为开始时间 0表示每周日为开始时间 
			$w = date("w", strtotime($now_date)); //获取当前周的第几天 周日是 0 周一 到周六是 1 -6 
			$d = $w ? $w - $first : 6; //如果是周日 -6天 
			$start_time_week = strtotime("".$now_date." -".$d." days"); //本周开始时间 
			$end_time_week = strtotime("".date("Y-m-d",$start_time_week)." +7 days"); //本周结束时间
			$create_time[0]['time'] = array('between',array($start_time_week,$end_time_week));
			$create_time[0]['name'] = 'week';

			//本月时间范围
			$start_time_month = strtotime(date('Y-m-01')); 
			$end_time_month = strtotime(date("Y")."-".date("m")."-".date("t"))+86400;
			$create_time[1]['time'] = array('between',array($start_time_month,$end_time_month));
			$create_time[1]['name'] = 'month';

			//本年时间范围
			$year = @date("Y",time());
			$year_next = $year+1;
			$start_time_year = strtotime("$year-01-01");
			$end_time_year = strtotime("$year_next-01-01");
			$create_time[2]['time'] = array('between',array($start_time_year,$end_time_year));
			$create_time[2]['name'] = 'year';

			$customer_count = array();
			$contacts_count = array();
			$business_count = array();
			$log_count = array();
			$mylog_count = array();
			foreach ($create_time as $k=>$v) {
				$customer_count[$v['name']] = $m_customer->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v['time']))->count();
				$contacts_count[$v['name']] = $m_contacts->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v['time']))->count();
				$business_count[$v['name']] = $m_business->where(array('creator_role_id'=>array('in',$briefing_role_ids),'is_deleted'=>0,'create_time'=>$v['time']))->count();
				$log_count[$v['name']] = $m_log->where(array('role_id'=>array('in',$briefing_role_ids),'category_id'=>1,'create_date'=>$v['time']))->count();//沟通日志
				$mylog_count[$v['name']] = $m_log->where(array('role_id'=>array('in',$briefing_role_ids),'category_id'=>array('neq',1),'create_date'=>$v))->count();//工作日志
			}

			//指标数据
			$blows_id = getPerByAction('finance','target'); //权限判断
			$m_receivables = M('Receivables');
			$m_receivingorder = M('Receivingorder');
			$sum_receivables_price = 0.00;
			$sum_price = 0.00;
			$sum_price_month = 0.00;
			$sum_price_week = 0.00;
			$sum_price_year = 0.00;
			$schedule = 0;

			if ($blows_id) {
				$owner_customer_ids = $m_customer->where(array('owner_role_id'=>session('role_id')))->getField('customer_id',true);
				$owner_contract_ids = $m_contract->where(array('customer_id'=>array('in',$owner_customer_ids),'is_checked'=>1))->getField('contract_id',true);
				
				//应收款
				$receivables_ids = array();
				$receivables_list = $m_receivables->where(array('contract_id'=>array('in',$owner_contract_ids)))->field('receivables_id,price')->select();
				foreach ($receivables_list as $k=>$v) {
					$sum_receivables_price += $v['price']; //应收款总额
					$receivables_ids[] = $v['receivables_id'];
				}
				//总回款(历史收款)
				$sum_price = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1))->sum('money');
				//收款进度
				if ($sum_receivables_price == 0 || $sum_receivables_price == 0.00 || $sum_price > $sum_receivables_price) {
					$schedule = 100;
				} else {
					$schedule = round(($sum_price/$sum_receivables_price),4)*100;
				}
				
				//本周回款
				$sum_price_week = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[0]))->sum('money');
				//本月回款
				$sum_price_month = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[1]))->sum('money');
				//本年回款
				$sum_price_year = $m_receivingorder->where(array('receivables_id'=>array('in',$receivables_ids),'status'=>1,'pay_time'=>$create_time[2]))->sum('money');
				$sum_price = !empty($sum_price) ? $sum_price : '0.00';
				$sum_price_month = !empty($sum_price_month) ? $sum_price_month : '0.00';
				$sum_price_week = !empty($sum_price_week) ? $sum_price_week : '0.00';
				$sum_price_year = !empty($sum_price_year) ? $sum_price_year : '0.00';
				$schedule = !empty($schedule) ? $schedule : '0.00';
			}

			$anly_count = array('customer_count'=>$customer_count,'contacts_count'=>$contacts_count,'business_count'=>$business_count,'log_count'=>$log_count,'mylog_count'=>$mylog_count,'sum_price'=>$sum_price,'sum_price_month'=>$sum_price_month,'sum_price_week'=>$sum_price_week,'sum_price_year'=>$sum_price_year,'schedule'=>$schedule);

			//回款金额PK（本月）
			$all_role = M('User')->where(array('status'=>array('neq',2)))->getField('role_id',true);
			$all_role_array = array();
			foreach ($all_role as $k=>$v) {
				$where_receivingorder = array();
				$where_receivingorder['owner_role_id'] = $v;
				$where_receivingorder['status'] = array('eq',1);
				$where_receivingorder['pay_time'] = $create_time[1]['time'];
				//总回款
				$sum_price = 0.00;
				$sum_price = $m_receivingorder->where($where_receivingorder)->sum('money');
				$all_role_array[$k]['sum_price'] = $sum_price ? $sum_price : '0.00';
				$all_role_array[$k]['role'] = $v;
				$all_role_array[$k]['role_info'] = $m_user->where('role_id = %d',$v)->field('full_name,thumb_path')->find();
			}

			//根据总回款排序(二维数组排序)
			foreach ($all_role_array as $key=>$val) {
				$volume[$key]  = $val['sum_price'];
		        $edition[$key] = $val['role'];
			}
			array_multisort($volume, SORT_DESC, $edition, SORT_ASC, $all_role_array);
			//取10个数据
			$all_role_array = array_slice($all_role_array,'0','10',true);
			$anly_count['all_role_array'] = $all_role_array;

			//返回权限模块
			$anly_count['permission_list'] = $this->permission_list();

			$this->ajaxReturn($anly_count,'success',1);
		} else {
			$this->ajaxReturn(0, '请求方式不正确',0);
		}
	}

	/**
	 * 动态信息
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic(){
		if ($this->isPost()) {
			if (!empty($_POST['role_id'])) {
				$where['role_id'] = $_POST['role_id'];
			} else {
				if (!session('?admin')) {
					$where['role_id'] = array('in',implode(',', getSubRoleId()));
				}
			}
			$where['action_name'] = array('not in',array('completedelete','delete','view'));
			$by = isset($_POST['by']) ? $_POST['by'] : '';
			
			//获取权限
			$permission_list = $this->permission_list();
			//无权限控制的模块
			$arr_a = array('sign','log');
			//权限模块（数组组合）
			if (session('?admin')) {
				$my_permission = array('business','customer','sign','log','leads','sales','user','event','contract','product');
			} else {
				$my_permission = array_merge($arr_a,$permission_list);
				if (!in_array($by,$my_permission) && $by != '') {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}

			$m_customer = M('Customer');
			$m_leads = M('Leads');
			$m_business = M('Business');
			$m_task = M('Task');
			$m_event = M('Event');
			$m_examine = M('Examine');
			$m_contacts = M('Contacts');
			$m_contract = M('Contract');
			$m_product = M('Product');
			$m_fields = M('Fields');
			$m_comment = M('Comment');
			$m_praise = M('Praise');
			$d_role = D('RoleView');
			
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
				$role = $d_role->where('role.role_id = %d', $v['role_id'])->find();
				$tmp = array();
				$tmp['role_id'] = $v['role_id'];
				$tmp['user_name'] = $role['user_name'];
				$tmp['role_name'] = $role['department_name'].'-'.$role['role_name'];
				$tmp['img'] = $role['thumb_path'];
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
							//过滤HTML
							$content_info = strip_tags($log_info['content']);
							$tmp['content'] = msubstr($content_info,0,50);
							//评论数
							$comment_cont = $m_comment->where("module='log' and module_id=%d", $log_info['log_id'])->count();
							$tmp['comment_count'] = $comment_cont;
							//点赞数
							$tmp['praise_count'] = $m_praise->where('log_id = %d',$log_info['log_id'])->count();
							if($m_praise->where('log_id = %d and role_id = %d',$log_info['log_id'],session('role_id'))->find()){
								$tmp['is_praised'] = 1;
							}else{
								$tmp['is_praised'] = 0;
							}
							//日志类型
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
							$tmp['datab'] = $customer_info['origin'];
							$tmp['type'] = 3;
							break;
						case 'contract' :
							$contract_info = $m_contract ->where('contract_id =%d',$v['action_id'])->find();
							$tmp['dataa'] = $contract_info['price'];
							$tmp['datab'] = $contract_info['end_date'] ? date('Y-m-d',$contract_info['end_date']) : '';
							$tmp['type'] = 8;
							break;
						case 'business' :
							$business_info = $m_business ->where('business_id =%d',$v['action_id'])->find();
							$status_name = M('business_status')->where('status_id =%d',$business_info['status_id'])->getField('name');
							$tmp['dataa'] = $status_name;
							$tmp['datab'] = $business_info['final_price'] ? $business_info['final_price'] : '0.00';
							$tmp['type'] = 4;
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
							$tmp['dataa'] = $role['department_name'];
							$tmp['datab'] = $role['role_name'];
							$tmp['type'] = 1;
							break;
					}
					// if ($v['module_name'] == 'contract') {
					// 	$aname = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('number');
					// } else {
						if ($v['module_name'] == 'business') {
							$v['module_name'] = 'customer';
							$v['action_id'] = $business_info['customer_id'];
						} elseif ($v['module_name'] == 'contract') {
							$v['module_name'] = 'customer';
							$v['action_id'] = $contract_info['customer_id'];
						}
						$aname = M($v['module_name'])->where($v['module_name'].'_id = %d',$v['action_id'])->getField('name');
					// }
					$tmp['aname'] = !empty($aname) ? $aname : '';
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

	/**
	 * 获取自定义字段
	 * @param 
	 * @author 
	 * @return 
	 */
	public function fields() {
		if ($this->isPost()) {
			$m_fields = M('Fields');
			$params = json_decode($_POST['params'],true);
			$m = trim($params['module']);
			$a = trim($params['action']);
			$module_id = intval($params['module_id']);
			$leads_id = intval($params['leads_id']);
			$array_m = array('business','leads','customer','contacts','product','contract');
			//创建一个空对象
			//$empty_object = new stdClass();
			$empty_object = '';
			$where = array();
			if ($m == 'customer') {
				$where['field'] = array('not in',array('tags','customer_owner_id'));
			}
			if ($m == 'business') {
				$where['field'] = array('not in',array('name','customer_id','contacts_id','status_id','possibility'));
			}
			if ($m == 'contract') {
				$where['field'] = array('not in',array('business_id','customer_id','number','contract_name','owner_role_id','price','due_time','start_date','end_date','description'));
			}
			$where['model'] = $m;
			if (checkPerByAction($m, $a)) {
				if ($m && in_array($m, $array_m)) {
					$fields_list = $m_fields->where($where)->order('is_main desc,order_id asc')->field('is_main,field,name,form_type,default_value,max_length,is_unique,is_null,is_validate,in_add,input_tips,setting')->select();
					foreach ($fields_list as $k=>$v) {
						if ($v['field'] != 'contacts_id') {
							if ($v['setting']) {
								//将内容为数组的字符串格式转换为数组格式
								eval("\$setting = ".$v['setting'].'; ');
								$setting_arr = array();
								$data_arr = array();
								foreach ($setting['data'] as $key=>$val) {
									$key = $key-1;
									$data_arr[$key]['key'] = $val;
									$data_arr[$key]['value'] = $val;
								}
								$fields_list[$k]['setting'] = $data_arr;
								$fields_list[$k]['form_type'] = $setting['type'] == 'checkbox' ? 'checkbox' : 'select';
							} else {
								$fields_list[$k]['setting'] = $empty_object;
							}
						}
					}
					// if ($m == 'customer' || $m == 'leads') {
					// 	$fields_list[$k+1]['is_main'] = '1';
					// 	$fields_list[$k+1]['field'] = 'owner_role_id';
					// 	$fields_list[$k+1]['name'] = '负责人';
					// 	$fields_list[$k+1]['form_type'] = 'user';
					// 	$fields_list[$k+1]['default_value'] = '';
					// 	$fields_list[$k+1]['max_length'] = '255';
					// 	$fields_list[$k+1]['is_unique'] = '0';
					// 	$fields_list[$k+1]['is_null'] = '0';
					// 	$fields_list[$k+1]['is_validate'] = '1';
					// 	$fields_list[$k+1]['in_add'] = '1';
					// 	$fields_list[$k+1]['input_tips'] = '';
					// 	$fields_list[$k+1]['setting'] = $empty_object;
					// }
					// if ($m == 'customer' && $a == 'edit') {
					// 	$fields_list = array_merge($fields_contacts,$fields_list);
					// }
					//客户下首要联系人字段
					if ($m == 'customer' && $a == 'add') {
						//线索转换客户
						if (!empty($leads_id)) {
							$leads_info = D('LeadsView')->where('leads.leads_id = %d',$leads_id)->find();
							//客户名称、客户地址和首要联系人信息
							foreach ($fields_list as $k=>$v) {
								$data_a = trim($leads_info[$v['field']]); //值
								if ($v['form_type'] == 'address') {
									$address_array = str_replace(chr(10),' ',$data_a);
									$fields_list[$k]['default_value'] = $address_array;
								} else {
									$fields_list[$k]['default_value'] = $data_a;
								}
							}
						}
						$arr = array('con_name','saltname','con_email','con_post','con_qq','con_telephone','con_description');
						foreach ($arr as $key=>$val) {
							$arr_contacts = array('姓名'=>'con_name','尊称'=>'saltname','邮箱'=>'con_email','职位'=>'con_post','QQ'=>'con_qq','手机'=>'con_telephone','备注'=>'con_description');
							foreach ($arr_contacts as $ke=>$va) {
								if ($val == $va) {
									$field = $ke;
								}
							}
							$fields_list_contacts[$key]['is_main'] = '2';
							$fields_list_contacts[$key]['field'] = $val;
							$fields_list_contacts[$key]['name'] = $field;
							if ($val == 'con_description') {
								$fields_list_contacts[$key]['form_type'] = 'textarea';
							} elseif ($val == 'con_telephone') {
								$fields_list_contacts[$key]['form_type'] = 'mobile';
							} else {
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
							$fields_list_contacts[$key]['is_unique'] = '0';
							$fields_list_contacts[$key]['is_null'] = '0';
							$fields_list_contacts[$key]['is_validate'] = '0';
							$fields_list_contacts[$key]['in_add'] = '1';
							$fields_list_contacts[$key]['input_tips'] = '';
							$fields_list_contacts[$key]['setting'] = $empty_object;
						}
						$fields_list = array_merge($fields_list,$fields_list_contacts);
					}

					//商机字段
					if ($m == 'business') {
						//商机编号
						$business_custom = M('Config')->where('name = "business_custom"')->getField('value');
						$business_max_id = M('Business')->max('business_id');
						$business_max_code = str_pad($business_max_id+1,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
						$code = $business_custom.date('Ymd').'-'.$business_max_code;
						//自定义字段

						$arr = array('code','name','customer_id','contacts_id','status_type_id','status_id','possibility','product[]');
						$fields_list_business = array();
						foreach ($arr as $key=>$val) {
							$arr_business = array('商机编号'=>'code','商机名称'=>'name','客户名称'=>'customer_id','联系人'=>'contacts_id','商机状态组'=>'status_type_id','商机进度'=>'status_id','成交几率'=>'possibility','产品'=>'product[]');
							foreach ($arr_business as $ke=>$va) {
								if ($val == $va) {
									$field = $ke;
								}
							}
							$fields_list_business[$key]['is_main'] = '1';
							$fields_list_business[$key]['field'] = $val;
							$fields_list_business[$key]['name'] = $field;

							$setting = '';
							if ($val == 'customer_id') {
								$fields_list_business[$key]['form_type'] = 'customer';
								$fields_list_business[$key]['setting'] = '';
							} elseif ($val == 'contacts_id') {
								$fields_list_business[$key]['form_type'] = 'contacts';
								$fields_list_business[$key]['setting'] = '';
							} elseif ($val == 'status_type_id') {
								//获取商机状态组
								$business_type = M('BusinessType')->select();
								foreach ($business_type as $key1=>$val1) {
									$fields_status[$key1]['key'] = $val1['id'];
									$fields_status[$key1]['value'] = $val1['name'];
								}
								$fields_list_business[$key]['setting'] = $fields_status;
								$fields_list_business[$key]['form_type'] = 'select';
							} elseif ($val == 'status_id') {
								if ($module_id) {
									$status_type_id = M('Business')->where(array('business_id'=>$module_id))->getField('status_type_id');
									$business_status = M('BusinessStatus')->where(array('type_id'=>$status_type_id))->order('order_id asc')->select();
								} else {
									//获取商机状态
									$business_status = M('BusinessStatus')->where(array('type_id'=>1))->order('order_id asc')->select();
								}
								foreach ($business_status as $key1=>$val1) {
									$fields_status[$key1]['key'] = $val1['status_id'];
									$fields_status[$key1]['value'] = $val1['name'];
								}
								$fields_list_business[$key]['setting'] = $fields_status;
								$fields_list_business[$key]['form_type'] = 'select';
							} elseif ($val == 'possibility') {
								$fields_list_business[$key]['setting'] = array('0'=>'10%','1'=>'20%','2'=>'30%','3'=>'40%','4'=>'50%','5'=>'60%','6'=>'70%','7'=>'80%','8'=>'90%','9'=>'100%');
								$fields_list_business[$key]['form_type'] = 'select';
							} elseif ($val == 'product[]') {
								$fields_list_business[$key]['setting'] = array();
								$fields_list_business[$key]['form_type'] = 'product';
							} else {
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
							$fields_list_business[$key]['is_unique'] = '0';
							$fields_list_business[$key]['is_null'] = '0';
							$fields_list_business[$key]['is_validate'] = '0';
							$fields_list_business[$key]['in_add'] = '1';
							$fields_list_business[$key]['input_tips'] = '';
						}
						$fields_list = array_merge($fields_list_business,$fields_list);
					}

					//合同字段
					if ($m == 'contract') {
						$m_contract = M('Contract');
						//合同编号
						$contract_custom = M('Config') -> where('name="contract_custom"')->getField('value');
						if(!$contract_custom)  $contract_custom = 'crm_';
						$contract_max_id = $m_contract->max('contract_id');
						$contract_max_id = $contract_max_id+1;
						$contract_max_code = str_pad($contract_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
						$code = $contract_custom.date('Ymd').'-'.$contract_max_code;

						//自定义字段

						$arr = array('customer_id','business_id','number','contract_name','owner_role_id','price','due_time','start_date','end_date','description','product[]');
						$fields_list_contract = array();
						foreach ($arr as $key=>$val) {
							$arr_contract = array('来源客户'=>'customer_id','来源商机'=>'business_id','合同编号'=>'number','合同名称'=>'contract_name','合同签约人'=>'owner_role_id','合同金额'=>'price','签约时间'=>'due_time','合同生效时间'=>'start_date','合同到期时间'=>'end_date','合同描述'=>'description','产品'=>'product[]');
							foreach ($arr_contract as $ke=>$va) {
								if ($val == $va) {
									$field = $ke;
								}
							}
							$fields_list_contract[$key]['is_main'] = '1';
							$fields_list_contract[$key]['field'] = $val;
							$fields_list_contract[$key]['name'] = $field;

							$setting = '';
							if ($val == 'number') {
								$fields_list_contract[$key]['form_type'] = 'text';
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'contract_name') {
								$fields_list_contract[$key]['form_type'] = 'text';
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'customer_id') {
								$fields_list_contract[$key]['form_type'] = 'customer';
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'business_id') {
								$fields_list_contract[$key]['form_type'] = 'business';
								$fields_list_contract[$key]['setting'] = '';
							} elseif ($val == 'owner_role_id') {
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['form_type'] = 'user';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'price') {
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['form_type'] = 'floatnumber';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'start_date' || $val == 'end_date' || $val == 'due_time') {
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['form_type'] = 'datetime';
								$fields_list_contract[$key]['is_validate'] = '1';
								$fields_list_contract[$key]['is_null'] = '1';
							} elseif ($val == 'product[]') {
								$fields_list_contract[$key]['setting'] = array();
								$fields_list_contract[$key]['form_type'] = 'product';
							} else {
								$fields_list_contract[$key]['form_type'] = 'text';
								$fields_list_contract[$key]['setting'] = '';
								$fields_list_contract[$key]['is_validate'] = '0';
								$fields_list_contract[$key]['is_null'] = '0';
							}
							
							$default_value = '';
							switch ($val){
								case 'number' : $default_value = $code;break;
								case 'contract_name' : $default_value = $code;break;
								case 'due_time' : $default_value = date('Y-m-d');break;
							}
							$fields_list_contract[$key]['default_value'] = !empty($default_value) ? $default_value : '';
							$fields_list_contract[$key]['max_length'] = '';
							$fields_list_contract[$key]['is_unique'] = '0';
							$fields_list_contract[$key]['in_add'] = '1';
							$fields_list_contract[$key]['input_tips'] = '';
						}
						$fields_list = array_merge($fields_list_contract,$fields_list);
						// $fields_list = $fields_list_contract;
					}
					$data['data'] = $fields_list;
					$data['info'] = 'success';
					$data['status'] = 1;
					$this->ajaxReturn($data,'JSON');
				} else {
					$this->ajaxReturn('','参数错误！',0);
				}
			} else {
				$this->ajaxReturn('','您没有权限！',-2);
			}
		}
	}

	/**
	 * 自定义字段验重
	 * @param params : field 字段名, val 值 ,id 排除当前数据验重,module = 需要查询的模块名
	 * @author 
	 * @return 
	 */
	public function validate() {
		if ($this->isPost()) {
			$params = $_POST;
			$module = trim($params['module']);
			$field = trim($params['field']);
			$val = trim($params['val']);
			if (!$val) {
				$this->ajaxReturn('','填写内容不能为空！',0);
			}
			if (!$field) {
				$this->ajaxReturn('','数据验证错误，请联系管理员！',0);
			}
			$field_info = M('Fields')->where('model = "%s" and field = "%s"',$module,$field)->find();
			if ($module == 'contacts') {
				$m_fields = $field_info['is_main'] ? D('contacts') : D('ContactsData');
			} elseif ($module == 'customer') {
				$m_fields = $field['is_main'] ? D('Customer') : D('CustomerData');
			} elseif ($module == 'product') {
				$m_fields = $field['is_main'] ? D('Product') : D('ProductData');
			} elseif ($module == 'leads') {
				$m_fields = $field['is_main'] ? D('Leads') : D('LeadsData');
			}
			$where[$field] = array('eq',$val);
			if ($params['id']) {
                $where[$m_fields->getpk()] = array('neq',$params['id']);
            }
			if ($m_fields->where($where)->find()) {
				$this->ajaxReturn('','该数据已存在，请修改后提交！',0);
			} else {
				$this->ajaxReturn('','success',1);
			}
		}
	}

	/**
	 * 附件列表
	 * @param 
	 * @author 
	 * @return
	 */
	public function filelist() {
		if ($this->isPost()) {
			$module = $_POST['module'] ? trim($_POST['module']) : '';
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			$module_array = array('leads', 'customer', 'business', 'contract', 'log', 'product', 'task', 'finance', 'examine');
			if (!in_array($module, $module_array) || !$id) {
				$this->ajaxReturn('', '参数错误！', 0);
			} else {
				$is_permission = 0;
				$m_file = M('File');
				switch ($module) {
					case 'leads' : 
						$m_r_file = M('RFileLeads'); 
						//判断权限
						$outdays = M('Config')->where('name="leads_outdays"')->getField('value');
						$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
						$where['have_time'] = array('egt',$outdate);
						$where['owner_role_id'] = array('neq',0);
						$where['leads_id'] = $id;

						if ($leads_info = D('Leads')->where($where)->find()) {
							if (in_array($leads_info['owner_role_id'],getPerByAction('leads','view'))) {
								$is_permission = 1;
							}
						}
						break;
					case 'customer' : 
						$m_r_file = M('RCustomerFile'); 

						//判断权限
						$m_config = M('Config');
						$customer_info = D('CustomerView')->where('customer.customer_id = %d', $id)->find();
						$outdays = $m_config->where('name="customer_outdays"')->getField('value');
						$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

						$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
						$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
						$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
						$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
						if ($openrecycle == 2) {
							if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
								if (in_array($customer_info['owner_role_id'], getPerByAction('customer','view'))) {
									$is_permission = 1;
								}
							} else {
								//客户池
								$is_permission = 1;
							}
						} else {
							$is_permission = 1;
						}
						break;
					case 'business' : 
						$m_r_file = M('RBusinessFile'); 
						$m_customer = M('Customer');
						//判断权限
						$below_ids = getPerByAction('business','view');
						$where_pre = array();
						$where_pre['customer_id'] = M('Business')->where('business_id = %d',$id)->getField('customer_id');
						
						//过滤客户池条件
						$outdays = M('Config')->where('name="customer_outdays"')->getField('value');
						$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
						$where_pre['owner_role_id'] = array('in',implode(',', $below_ids));
						$where_pre['is_deleted'] = array('neq',1);
						$where_pre['_string'] = 'update_time > '.$outdate.' OR is_locked = 1';
						$customer_info = $m_customer->where($where_pre)->field('owner_role_id')->find();

						if ($customer_info) {
							$is_permission = 1;
						}
						break;
					case 'contract' : 
						$m_r_file = M('RContractFile');
						$m_contract = M('Contract');
						//判断权限
						$contract_info = $m_contract->where(array('contract_id'=>$id))->find();
						if (in_array($contract_info['owner_role_id'], getPerByAction('contract','view'))) {
							$is_permission = 1;
						}
						break;
					case 'log' : 	
						$m_r_file = M('RFileLog'); 
						//判断权限
						$log_info = M('Log')->where('log_id = %d', $id)->find();
						if (in_array($log_info['role_id'],getPerByAction('log','mylog_view'))){
							$is_permission = 1;
						}
						break;
					case 'product' : 
						$m_r_file = M('RFileProduct');
						//判断权限
						if (in_array(session('role_id'),getPerByAction('product','view'))){
							$is_permission = 1;
						}
						break;
					case 'task' : 
						$m_r_file = M('RTaskFile');
						//判断权限
						$task_info = M('Task')->where('task_id = %d',$id)->find();
						if(session('?admin') || $task_info['creator_role_id'] == session('role_id') || in_array(session('role_id'),array_filter(explode(',',$task_info['about_roles']))) || in_array(session('role_id'),array_filter(explode(',',$task_info['owner_role_id'])))){
							$is_permission = 1;
						}
						break;
					case 'finance' : 
						$m_r_file = M('RFileFinance'); 
						break;
					case 'examine' : 
						$m_r_file = M('RExamineFile'); 
						//判断权限
						$m_examine = M('Examine');
						//非管理员权限限制
						if (!session('?admin')) {
							//已审核的人
							$examine_check_info = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$id))->find();
							//审核人或自己
							$c_where['creator_role_id'] = session('role_id'); 
							$c_where['examine_role_id'] = session('role_id');
							$c_where['_logic'] = 'or';
							$where['_complex'] = $c_where;
						}
						$where['examine_id'] = $id;
						$examine_info = $m_examine->where($where)->find();
						$creator_role_id = $m_examine->where('examine_id = %d',$id)->getField('creator_role_id');
						$below_ids = getPerByAction('examine','view');

						if (session('?admin') || $examine_check_info || $examine_info || in_array($creator_role_id, $below_ids)) {
							$is_permission = 1;
						}
						break;
					default : 
						$m_r_file = ''; 
						break;
				}
				$module_id_name = M($module)->getPk();
				//判断权限
				if ($is_permission == 1) {
					$file_ids = $m_r_file->where(array($module_id_name=>$id))->getField('file_id', true);
					$file_list = array();
					$file_list = $m_file->where(array('file_id'=>array('in',$file_ids)))->select();
					foreach ($file_list as $k=>$v) {
						$file_type = '';
						$file_type = end(explode('.',$v['name']));
						$file_list[$k]['file_type'] = $file_type;
						$file_list[$k]['size'] = round($v['size']/1024,2).'Kb';
						if (intval($v['size']) > 1024*1024) {
							$file_list[$k]['size'] = round($v['size']/(1024*1024),2).'Mb';
						}
					}

					$data['list'] = $file_list ? $file_list : array();
					$data['info'] = 'success'; 
					$data['status'] = 1; 			
					$this->ajaxReturn($data,'JSON');
				} else {
					$this->ajaxReturn('', '您没有此权限！', -2);
				}
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 沟通日志列表
	 * @param 
	 * @author 
	 * @return
	 */
	public function loglist() {
		if ($this->isPost()) {
			$module = $_POST['module'] ? trim($_POST['module']) : '';
			$id = $_POST['id'] ? intval($_POST['id']) : '';
			$module_array = array('leads', 'customer', 'business');
			if (!in_array($module, $module_array) || !$id) {
				$this->ajaxReturn('', '参数错误！', 0);
			} else {
				$is_permission = 0;
				$m_log = M('Log');
				switch ($module) {
					case 'leads' : 
						$m_r_log = M('RLeadsLog'); 
						//判断权限
						$outdays = M('Config')->where('name="leads_outdays"')->getField('value');
						$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
						$where['have_time'] = array('egt',$outdate);
						$where['owner_role_id'] = array('neq',0);
						$where['leads_id'] = $id;

						if ($leads_info = M('Leads')->where($where)->find()) {
							if (in_array($leads_info['owner_role_id'],getPerByAction('leads','view'))) {
								$is_permission = 1;
							}
						}
						break;
					case 'customer' : 
						$m_r_log = M('RCustomerLog');
						$m_config = M('Config');
						//判断权限
						$customer_info = M('Customer')->where('customer_id = %d', $id)->find();
						$outdays = $m_config->where('name="customer_outdays"')->getField('value');
						$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

						$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
						$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
						$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
						$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
						if ($openrecycle == 2) {
							if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
								if (in_array($customer_info['owner_role_id'], getPerByAction('customer','view'))) {
									$is_permission = 1;
								}
							} else {
								//客户池
								$is_permission = 1;
							}
						} else {
							$is_permission = 1;
						}
						break;
					case 'business' : 
						$m_r_log = M('RBusinessLog'); 
						//判断权限
						$below_ids = getPerByAction('business','view');
						$business_info = M('Business')->where('business_id = %d',$id)->find();
						if ($business_info && in_array($business_info['owner_role_id'],$below_ids)) {
							$is_permission = 1;
						}
						break;
					default : 
						$m_r_file = ''; 
						break;
				}
				$module_id_name = M($module)->getPk();
				//判断权限
				if ($is_permission == 1) {
					$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
					
					if ($module == 'customer') {
						//合并客户、商机沟通记录
						$customer_business_ids = M('business')->where('customer_id = %d', $id)->getField('business_id', true);
						$customer_log_ids = M('rCustomerLog')->where('customer_id = %d', $id)->getField('log_id', true);
						$customer_log_ids = $customer_log_ids ? $customer_log_ids : array();
						$business_log_ids = M('rBusinessLog')->where('business_id in (%s)', implode(',', $customer_business_ids))->getField('log_id', true);
						$business_log_ids = $business_log_ids ? $business_log_ids : array();

						$log_ids = array_merge($customer_log_ids,$business_log_ids);
						$log_list = $m_log->where(array('log_id'=>array('in',$log_ids)))->page($p.',10')->order('create_date desc')->select();
						$logcount = $m_log->where(array('log_id'=>array('in',$log_ids)))->count();
					} else {
						$log_ids = $m_r_log->where(array($module_id_name=>$id))->getField('log_id', true);
						$log_list = $m_log->where(array('log_id'=>array('in',$log_ids)))->page($p.',10')->order('create_date desc')->select();
						$log_count = $m_log->where(array('log_id'=>array('in',$log_ids)))->count();
					}

					$d_role = D('RoleView');
					$m_sign = M('Sign');
					$m_customer = M('Customer');
					$m_log_status = M('LogStatus');
					$m_r_business_log = M('RBusinessLog');
					$m_business = M('Business');
					$m_sign_img = M('SignImg');
					foreach ($log_list as $key=>$value) {
						$log_list[$key]['owner'] = $d_role->where('role.role_id = %d', $value['role_id'])->field('user_name,role_id,thumb_path,role_name,department_name')->find();
						$business_info = array();
						$business_id = '';
						if ($value['sign'] == 1) {
							//客户签到
							$sign_info = $m_sign->where('log_id = %d',$value['log_id'])->find();
							if ($sign_info) {
								$log_list[$key]['type'] = 9;
								$log_list[$key]['customer_id'] = $sign_info['customer_id'];
								$log_list[$key]['x'] = $sign_info['x'];
								$log_list[$key]['y'] = $sign_info['y'];
								$log_list[$key]['address'] = $sign_info['address'];
								$log_list[$key]['log'] = $sign_info['log'];
								$log_list[$key]['title'] = $sign_info['title'];
								$sign_customer_name = $m_customer->where('customer_id = %d',$sign_info['customer_id'])->getField('name');
								$log_list[$key]['sign_customer_name'] = !empty($sign_customer_name) ? $sign_customer_name : '';
								//图片
								$sign_img = $m_sign_img->where(array('sign_id'=>$sign_info['sign_id']))->getField('path',true);
								$log_list[$key]['sign_img'] = $sign_img ? $sign_img : array();
							}
							$log_list[$key]['type'] = 2;//签到
						} else {
							$log_list[$key]['type'] = 1;//沟通日志
							$status_name = '';
							if ($value['status_id']) {
								$status_name = $m_log_status->where('id = %d',$value['status_id'])->getField('name');
							}							
							if ($business_id = $m_r_business_log->where('log_id = %d',$value['log_id'])->getField('business_id')) {
								$business_info = $m_business->where('business_id = %d',$business_id)->field('name,code')->find();
							}
							$log_list[$key]['status_name'] = $status_name ? $status_name : '';
							$log_list[$key]['business_name'] = $business_info ? $business_info['name'] : '';
							$log_list[$key]['business_id'] = $business_id ? $business_id : '';
						}
					}
					$data['list'] = $log_list ? $log_list : array();
					$page = ceil($log_count/10);
					$data['page'] = $page;
					$data['info'] = 'success'; 
					$data['status'] = 1; 			
					$this->ajaxReturn($data,'JSON');
				} else {
					$this->ajaxReturn('', '您没有此权限！', -2);
				}
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 办公（待办事项）
	 * @param 
	 * @author 
	 * @return
	 */
	public function work () {
		if ($this->isPost()) {
			$m_event = M('Event');
			$m_contract = M('Contract');
			$m_remind = M('Remind');
			$m_config = M('Config');

			//今日日程数
			$current_start_time = strtotime(date('Y-m-d', time()))-1;
			$current_end_time = strtotime(date('Y-m-d', time()))+86400;
			$event_num = $m_event->where("owner_role_id = %d and isclose = 0 and is_deleted <> 1 and start_date > $current_start_time and end_date < $current_end_time", session('role_id'))->count();

			//即将到期合同数
			$m_contract = M('Contract');
			$days = C('defaultinfo.contract_alert_time') ? intval(C('defaultinfo.contract_alert_time')) : 30;
			$temp_time = time()+$days*86400;
			$contract_num = $m_contract->where("owner_role_id = %d and is_checked = 1 and contract_status = 0 and is_deleted <> 1 and $temp_time >= end_date", session('role_id'))->count();

			//提醒发送站内信
			$m_customer = M('Customer');
			$m_business = M('Business');
			$remind_list = $m_remind->where(array('create_role_id'=>session('role_id'),'is_remind'=>0,'remind_time'=>array('lt',time())))->select();
			if ($remind_list) {
				foreach ($remind_list as $k=>$v) {
					$customer_id = '';
					if ($v['module'] == 'business') {
						$customer_id = $m_business->where('business_id = %d',$v['module_id'])->getField('customer_id');
					} elseif ($v['module'] == 'customer') {
						$customer_id = $v['module_id'];
					}
					$customer_info = $m_customer->where('customer_id = %d',$customer_id)->field('name,customer_id')->find();
					$message_content = '';
					$message_content = '您有一条自动提醒，内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;'.$v['content'].'。<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;相关客户：<a href="'.U('customer/view','id='.$customer_info['customer_id']).'">'.$customer_info['name'].'</a>';
					$res = sendMessage($v['create_role_id'],$message_content,1);
					if ($res) {
						//标记为已提醒
						$m_remind->where('remind_id = %d',$v['remind_id'])->setField('is_remind',1);
					}
				}
			}
			//待审核的合同（数量以合同列表权限范围为准）
			$check_contract_num = 0;
			if (checkPerByAction('contract','check')) {
				$where_check_contract = array();
				$where_check_contract['owner_role_id'] = array('in',getPerByAction('contract','index'));
				$where_check_contract['is_checked'] = 0;
				$check_contract_num = M('Contract')->where($where_check_contract)->count();
			}
			//应收款提醒
			$m_receivables = M('receivables');
			$receivables_time = $m_config->where('name="receivables_time"')->getField('value');
			$f_outdate = empty($receivables_time) ? 0 : time()-86400*$receivables_time;
			$r_where['pay_time'] = array('elt',time()+$f_outdate);
			$r_where['status'] = array('lt',2);
			$r_where['owner_role_id'] = session('role_id');
			$receivables_num = $m_receivables ->where($r_where)->count();
			
			//待审批的单据（数量以审批列表权限范围为准）
			$examine_num = 0;
			$where_examine = array();
			$where_examine['examine_role_id'] = session('role_id');
			$where_examine['examine_status'] = array('lt',2);
			$examine_num = M('Examine')->where($where_examine)->count();
			
			//待确认的回款
			$receivingorder_num = 0;
			if (checkPerByAction('finance','check')) {
				$where_check_receivingorder = array();
				$where_check_receivingorder['owner_role_id'] = array('in',getPerByAction('finance','index_receivingorder'));
				$where_check_receivingorder['status'] = 0;
				$receivingorder_num = M('Receivingorder')->where($where_check_receivingorder)->count();
			}

			//今日需拜访客户(自己的)
			$today_customer = 0;
			$today_where = array();
			$today_where['owner_role_id'] = session('role_id');
			//过滤客户池条件
			$outdays = $m_config -> where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
			$c_outdays = $m_config -> where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config -> where('name="openrecycle"')->getField('value');
			if($openrecycle == 2){
				$today_where['_string'] = '(update_time > '.$outdate.' AND get_time > '.$contract_outdays.') OR is_locked = 1';
			}
			$today_where['is_deleted'] = array('neq',1);
			$today_where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt', strtotime(date('Y-m-d', time()))), 'and');
			$today_customer_num = $m_customer->where($today_where)->count();

			$num_arr = array('event_num'=>$event_num, 'contract_num'=>$contract_num, 'check_contract_num'=>$check_contract_num, 'receivables_num'=>$receivables_num, 'examine_num'=>$examine_num, 'receivingorder_num'=>$receivingorder_num, 'today_customer_num'=>$today_customer_num);

			$data['data'] = $num_arr;
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 获取自定义场景数据
	 * @param module :模块, module_id:模块ID
	 * @author 
	 * @return
	 */
	public function scene(){
		$scene_id = $_POST['scene_id'] ? intval($_POST['scene_id']) : '';

		$scene_where = array();
		$scene_where['role_id']  = session('role_id');
		$scene_where['type']  = 1;
		$scene_where['_logic'] = 'or';
		$map_scene['_complex'] = $scene_where;
		$map_scene['id'] = $scene_id;
		$scene_info = M('Scene')->where($map_scene)->find();
		if (!$scene_info) {
			$this->ajaxReturn('','参数错误！',0);
		}
		if ($scene_info['data']) {
			eval('$scene_info_data = '.$scene_info["data"].';');

			$field_list = M('Fields')->where(array('model'=>$scene_info['module']))->order('order_id')->select();
			$data_list = array();
			$i = 0;
			foreach ($scene_info_data as $k=>$v) {
				if ($v['form_type'] == 'address') {
					$data_a = $v['state'].' '.$v['city'].' '.$v['area'].' '.$v['value'];
				} elseif ($v['form_type'] == 'datetime') {
					$data_a = $v['start'].' '.$v['end'];
				} else {
					$data_a = trim($v['value']);
				}
				//客户模块（首要联系人、首要联系人电话）
				if ($v['field'] == 'contacts_telephone' || $v['field'] == 'contacts_name') {
					$field_name = '';
					switch ($v['field']) {
						case 'contacts_telephone' : $field_name = '首要联系人电话'; break;
						case 'contacts_name' : $field_name = '首要联系人姓名'; break;
					}
					$data_list[$i]['field'] = $v['field'];
					$data_list[$i]['name'] = $field_name;
					$data_list[$i]['form_type'] = 'text';
					$data_list[$i]['type'] = 0;
					$data_list[$i]['val'] = $v['value'];
				} else {
					foreach ($field_list as $key=>$val) {
						if ($v['field'] == $val['field']) {
							$field = trim($val['field']);
							$data_list[$i]['field'] = $field;
							$data_list[$i]['name'] = trim($val['name']);
							if ($val['setting']) { 
								//将内容为数组的字符串格式转换为数组格式
								eval("\$setting = ".$val['setting'].'; ');
								$data_list[$i]['form_type'] = $setting['type'] == 'checkbox' ? 'checkbox' : 'select';
							} else {
								$data_list[$i]['form_type'] = $val['form_type'];
							}
							$data_list[$i]['val'] = $data_a;
							$data_list[$i]['type'] = 0;
							$data_list[$i]['id'] = '';
							break;
						}
					}
				}
				$i++;
			}
		} else {
			$data_list = array();
		}
		$data['data'] = $data_list;
		$data['status'] = 1;
		$data['info'] = 'success';
		$this->ajaxReturn($data,'JSON');
	}
}
