<?php
/**
 *合同相关
 **/
class ContractVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('product','finance','dynamic','checklist')
		);
		B('VueAuthenticate',$action);
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
	}

	/**
	 * 合同列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		$contract_custom = M('Config') -> where('name="contract_custom"')->getField('value');
		if (!$contract_custom) {
			$contract_custom = '5k_crm';
		} 
		if ($this->isPost()) {
			//获取权限
			$permission_list = apppermission('contract','index');
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			$m_user = M('User');
			$d_contract = D('ContractView');
			$by = $_POST['by'] ? trim($_POST['by']) : 'all';
			$where = array();
			//按合同编号查询
			if (trim($_POST['search'])) {
				$search = trim($_POST['search']);
				//获取客户ID
				$cus_where['name'] = array('like','%'.$search.'%');
				$customer_ids = M('Customer')->where($cus_where)->getField('customer_id',true);
				$customer_str = implode(',',$customer_ids);
				//获取商机ID
				$b_where['name'] = array('like','%'.$search.'%');
				$business_ids = M('business')->where($b_where)->getField('business_id',true);
				$business_str = implode(',',$business_ids);
				if($customer_str && $business_str){
					$where['_string'] = 'contract.contract_name like "%'.$search.'%" or contract.number like "%'.$search.'%" or contract.customer_id in ('.$customer_str.') or contract.business_id in ('.$business_str.')';
				}elseif($customer_str){
					$where['_string'] = 'contract.contract_name like "%'.$search.'%" or contract.number like "%'.$search.'%" or contract.customer_id in ('.$customer_str.')';
				}elseif($business_str){
					$where['_string'] = 'contract.contract_name like "%'.$search.'%" or contract.number like "%'.$search.'%" or contract.business_id in ('.$business_str.')';
				}else{
					$where['_string'] = 'contract.contract_name like "%'.$search.'%" or contract.number like "%'.$search.'%"';
				}
			}
			$below_ids = getPerByAction('contract','index',true);
			$sub_ids = getSubRoleId(false);
			$where['contract.owner_role_id'] = array('in', $this->_permissionRes);
			$order = 'contract.update_time desc,contract.contract_id asc';

			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'contract.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',contract.contract_id asc';
			}

			//查询条件
			switch ($by){
				case 'create':
					$where['creator_role_id'] = session('role_id');
					break;
				case 'sub' :
					$where['contract.owner_role_id'] = array('in',implode(',', $sub_ids));
					break;
				case 'subcreate' :
					$where['creator_role_id'] = array('in',implode(',', $below_ids));
					break;
				case 'today' :
					$where['due_time'] =  array('between',array(strtotime(date('Y-m-d')) -1 ,strtotime(date('Y-m-d')) + 86400));
					break;
				case 'week' :
					$week = (date('w') == 0)?7:date('w');
					$where['due_time'] =  array('between',array(strtotime(date('Y-m-d')) - ($week-1) * 86400 -1 ,strtotime(date('Y-m-d')) + (8-$week) * 86400));
					break;
				case 'month' :
					$next_year = date('Y')+1;
					$next_month = date('m')+1;
					$month_time = date('m') ==12 ? strtotime($next_year.'-01-01') : strtotime(date('Y').'-'.$next_month.'-01');
					$where['due_time'] = array('between',array(strtotime(date('Y-m-01')) -1 ,$month_time));
					break;
				case 'add' :
					$order = 'contract.create_time desc,contract.contract_id asc';
					break;
				case 'deleted' :
					$where['is_deleted'] = 1;
					break;
				case 'update' :
					$order = 'contract.update_time desc,contract.contract_id asc';
					break;
				case 'me' :
					$where['contract.owner_role_id'] = session('role_id');
					break;
				default: $where['contract.owner_role_id'] = array('in',getPerByAction(MODULE_NAME,ACTION_NAME));break;
			}
			//多选类型字段
			$check_field_arr = M('Fields')->where(array('model'=>'contract','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
			//高级搜索
			if(!$_POST['field']){
				$no_field_array = array('act','content','p','search','listrows','by','contract_checked','order_field','order_type','token');
				foreach($_POST as $k => $v){
					if(!in_array($k,$no_field_array)){
						if(is_array($v)){
							if ($v['state']){
								$address_where[] = '%'.$v['state'].'%';

								if($v['city']){
									$address_where[] = '%'.$v['city'].'%';

									if($v['area']){
										$address_where[] = '%'.$v['area'].'%';
									}
								}
								if($v['search']) $address_where[] = '%'.$v['search'].'%';

								if($v['condition'] == 'not_contain'){
									$where[$k] = array('notlike', $address_where, 'OR');
								}else{
									$where[$k] = array('like', $address_where, 'AND');
								}
							}elseif(in_array($k,array('is_checked'))){
								if(!empty($v)){
									$where[$k] = $v['value'];
								}
							}elseif (($v['start'] != '' || $v['end'] != '')){
								if($k == 'create_time'){
									$k = 'contract.create_time';
								}elseif($k == 'update_time'){
									$k = 'contract.update_time';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
								}
							}elseif($k =='customer_name'){
								if(!empty($v['value'])){
									$c_where['name'] = array('like','%'.$v['value'].'%');
									$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true); 
									if($customer_ids){
										$where['customer_id'] = array('in',$customer_ids);
									}else{
										$where['customer_id'] = -1;
									}
								}
							}elseif($k =='code'){
								if(!empty($v['value'])){
									$b_where['code'] = array('like','%'.$v['value'].'%');
									$business_ids = M('Business')->where($b_where)->getField('business_id',true); 
									if($business_ids){
										$where['business_id'] = array('in',$business_ids);
									}else{
										$where['business_id'] = -1;
									}
								}
							}elseif($k =='owner_role_id' || $k =='creator_role_id'){
								if(!empty($v)){
									$where['contract.'.$k] = $v['value'];
								}
							} elseif(($v['value']) != '') {
								if (in_array($k,$check_field_arr)) {
									$where[$k] = field($v['value'],'contains');
								} else {
									if($k == 'status_id'){
										$business_map['status_id'] = $v['value'];
									}else{
										$where[$k] = field($v['value'],$v['condition']);
									}
								}
							}
						} else {
							if(!empty($v)){
								$where[$k] = field($v);
							}
						}
	                }
	            }
	            //过滤不在权限范围内的role_id
				if(isset($where['contract.owner_role_id'])){
					if(!empty($where['contract.owner_role_id']) && $where['owner_role_id']['1'] && !in_array(intval($where['contract.owner_role_id']),$this->_permissionRes)){
						$where['contract.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					}
				}
			}
			//按分类 1销售 2采购
			$where['contract.type'] = 1;

			if (!isset($where['is_deleted'])) {
				$where['is_deleted'] = 0;
			}
			//商机下的合同
			if ($_POST['business_id']) {
				$contract_ids = M('rBusinessContract')->where('business_id = %d', $_POST['business_id'])->getField('contract_id', true);
				$where['contract.contract_id'] = array('in',$contract_ids);
			}
			//客户下的合同
			if ($_POST['customer_id']) {
				$where['contract.customer_id'] = $_POST['customer_id'];
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$list = $d_contract->where($where)->page($p.',10')->order($order)->field('number,price,customer_id,contract_id,type,owner_role_id,create_time,is_checked')->select();

			$m_customer = M('Customer');
			$m_receivables = M('Receivables');
			$d_receivingorder = D('ReceivingorderView');
			foreach ($list as $k=>$v) {
				if ($v['type'] == 1) {
					$customer_name = $m_customer->where('customer_id = %d',$v['customer_id'])->getField('name');
					if ($customer_name) {
						$list[$k]['customer_name'] = $customer_name;
					} else {
						$list[$k]['customer_name'] = '';
					}
				}
				$owner_role_id = $v['owner_role_id'];
				
				//合同到期时间
				$end_date = 0;
				$end_date = $d_contract->where('contract_id = %d', $v['contract_id'])->getField('end_date');
				if ($end_date) {
					$list[$k]['days'] = round(($end_date-time())/86400);
				} else {
					$list[$k]['days'] = '';
				}

				//应收款
				$receivables_info = $m_receivables->where('is_deleted <> 1 and contract_id = %d',$v['contract_id'])->find();
				$sum_money = $d_receivingorder->where('receivingorder.is_deleted <> 1 and receivingorder.receivables_id = %d and receivingorder.status = 1', $receivables_info['receivables_id'])->sum('money');
				//收款进度
				$schedule = 0;
				if ($sum_money) {
					if ($receivables_info['price'] == 0 || $receivables_info['price'] == '') {
						$schedule = 100;
					} else {
						$schedule = round(($sum_money/$receivables_info['price'])*100,0);
					}
				}
				$list[$k]['schedule'] = $schedule ? $schedule : 0;

				//获取操作权限
				$list[$k]['permission'] = permissionlist(MODULE_NAME,$owner_role_id);
			}
			$count = $d_contract->where($where)->count();

			//自定义场景
			if($p == 1 && $_POST['search'] == ''){
				$m_scene = M('Scene');
				$scene_where = array();
				$scene_where['role_id']  = session('role_id');
				$scene_where['type']  = 1;
				$scene_where['_logic'] = 'or';
				$map_scene['_complex'] = $scene_where;
				$map_scene['module'] = 'contract';
				$map_scene['is_hide'] = 0;
				$scene_list = $m_scene->where($map_scene)->order('order_id asc,id asc')->field('id,name,data,type,by')->select();
				foreach ($scene_list as $k=>$v) {
					if ($v['type'] == 0) {
						eval('$scene_data = '.$v["data"].';');
					} else {
						$scene_data = array();
					}
					if ($scene_id && $scene_id == $v['id']) {
						$fields_search = $scene_data;
					}
					$scene_list[$k]['cut_name'] = cutString($v['name'],8);
				}
			}

			//获取查询条件信息
			if($p == 1 && $_POST['search'] == ''){
				$where_field = array();
				$where_field['model'] = array('in',array('','contract'));
				$where_field['is_main'] = '1';
				$where_field['field'] = array('not in',array('customer_id','business_id','delete_role_id','is_deleted','delete_time'));
				$fields_list = M('Fields')->where($where_field)->field('name,field,setting,form_type,input_tips')->order('order_id')->select();
				foreach($fields_list as $k=>$v){
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
					} elseif ($v['field'] == 'owner_role_id' || $v['field'] == 'create_role_id') {
						$fields_list[$k]['form_type'] = 'user';
					} else {
						$fields_list[$k]['setting'] = '';
					}
				}
				//追加其他字段信息
				$contract_field_list = array(
					'0'=>array('field'=>'customer_name','form_type'=>'text','input_tips'=>'','name'=>'客户名称','setting'=>''),
					'1'=>array('field'=>'code','form_type'=>'text','input_tips'=>'','name'=>'商机编号','setting'=>'')
				);
				$fields_list = array_merge($fields_list,$contract_field_list);
			}

			$page = ceil($count/10);
			if ($p == 1 && $_POST['search'] == '') {
				$data['contract_custom'] = $contract_custom;
				$data['fields_list'] = $fields_list ? $fields_list : array();
				//场景信息
				$data['scene_list'] = $scene_list ? $scene_list : array();
			} else {
				$data['fields_list'] = array();
				$data['scene_list'] = array();
			}
			$data['list'] = empty($list) ? array() : $list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 合同动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic(){
		if ($this->isPost()) {
			$m_contract = M('Contract');
			$contract_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$contract_info = $m_contract->where(array('contract_id'=>$contract_id))->field('contract_id,contract_name,create_time,customer_id,business_id,is_checked,owner_role_id')->find();
			if (!$contract_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			//判断权限
			if (!in_array($contract_info['owner_role_id'], getPerByAction('contract','view'))) {
				$this->ajaxReturn('','您没有此权限！',-2);
			}
			$customer_name = M('Customer')->where(array('customer_id'=>$contract_info['customer_id']))->getField('name');
			$business_name = M('Business')->where(array('business_id'=>$contract_info['business_id']))->getField('name');
			$contract_info['customer_name'] = $customer_name ? $customer_name : '';
			$contract_info['business_name'] = $business_name ? $business_name : '';
			switch ($contract_info['is_checked']) {
				case '0' : $check_name = '待审'; break;
				case '1' : $check_name = '通过'; break;
				case '2' : $check_name = '驳回'; break;
			}
			$contract_info['check_name'] = $check_name;
			//判断审批权限
			if (checkPerByAction('contract','check')) {
				$contract_info['is_permission'] = 1;
			} else {
				$contract_info['is_permission'] = 0;
			}

			$data['data'] = $contract_info ? $contract_info : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 合同详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$contract_id = intval($_POST['id']);
			$d_contract = D('ContractView');
			$m_user = M('User');
			$m_contacts = M('Contacts');
			$m_customer = M('Customer');
			$m_business = M('Business');
			$m_product = M('Product');
			$contract_info = $d_contract->where('contract.contract_id = %d',$contract_id)->find();
			//权限判断
			if (empty($contract_info) || empty($contract_id)) {
				$this->ajaxReturn('','合同不存在或已被删除！',0);
			}
			if (!in_array($contract_info['owner_role_id'], $this->_permissionRes)) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//自定义字段
			$where_field = array();
			$where_field['field'] = array('not in',array('business_id','customer_id','number','contract_name','owner_role_id','price','due_time','start_date','end_date','description'));
			$where_field['model'] = 'contract';
			$fields_list = M('Fields')->where($where_field)->order('is_main desc,order_id asc')->field('is_main,field,name,form_type,default_value,max_length,is_unique,is_null,is_validate,in_add,input_tips,setting')->select();
			foreach ($fields_list as $k=>$v) {
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
					$fields_list[$k]['form_type'] = $v['form_type'];
				}
				$data_a = trim($contract_info[$v['field']]);
				if($v['form_type'] == 'address') {
					$address_array = str_replace(chr(10),' ',$data_a);
					$fields_list[$k]['val'] = $address_array;
					$fields_list[$k]['type'] = 0;
				} else {
					$fields_list[$k]['val'] = $data_a;
					$fields_list[$k]['type'] = 0;
				}
				$fields_list[$k]['id'] = '';
			}
			//模拟自定义字段返回数据
			$field_array = array(
				'0'=>array('field'=>'number','name'=>'合同编号','form_type'=>'text'),
				'1'=>array('field'=>'start_date','name'=>'签约时间','form_type'=>'datetime'),
				'2'=>array('field'=>'customer_id','name'=>'签约客户','form_type'=>'text'),
				'3'=>array('field'=>'business_id','name'=>'相关商机','form_type'=>'text'),
				'4'=>array('field'=>'contract_name','name'=>'合同名称','form_type'=>'text'),
				'5'=>array('field'=>'owner_role_id','name'=>'合同签约人','form_type'=>'text'),
				'6'=>array('field'=>'price','name'=>'合同金额','form_type'=>'text'),
				'7'=>array('field'=>'due_time','name'=>'签约时间','form_type'=>'datetime'),
				'8'=>array('field'=>'start_date','name'=>'生效时间','form_type'=>'datetime'),
				'9'=>array('field'=>'end_date','name'=>'到期时间','form_type'=>'datetime'),
				'10'=>array('field'=>'creator_role_id','name'=>'合同创建人','form_type'=>'text'),
				'11'=>array('field'=>'create_time','name'=>'创建时间','form_type'=>'datetime'),
				'12'=>array('field'=>'description','name'=>'合同备注','form_type'=>'textarea')
			);

			$contract_list = array();
			foreach ($field_array as $k=>$v) {
				$contract_list[$k]['field'] = $v['field'];
				$contract_list[$k]['name'] = $v['name'];
				$contract_list[$k]['form_type'] = $v['form_type'];
				switch ($v['field']) {
					case 'customer_id' :
						$customer_name = $m_customer->where(array('customer_id'=>$contract_info[$v['field']]))->getField('name');
						$id = $contract_info[$v['field']];
						$val = $customer_name ? $customer_name : '';
						$type = 3;
						break;
					case 'business_id' :
						$business_name = $m_business->where(array('business_id'=>$contract_info[$v['field']]))->getField('name');
						$id = $contract_info[$v['field']];
						$val = $business_name ? $business_name : '';
						$type = 4;
						break;
					case 'owner_role_id' : 
					case 'creator_role_id' : 
						$user_info = $m_user->where('role_id = %d',$contract_info[$v['field']])->field('full_name,role_id')->find();
						$id = $user_info['role_id'];
						$val = $user_info['full_name'];
						$type = 1;
						break;
					default :
						$id = '';
						$val = $contract_info[$v['field']];
						$type = 0;
						break;
				}
				$contract_list[$k]['id'] = $id;
				$contract_list[$k]['val'] = $val;
				$contract_list[$k]['type'] = $type;
			}
			//合并自定义字段
			$fields_list = array_merge($contract_list,$fields_list);

			//相关产品
			$sales_id = M('rContractSales')->where(array('contract_id'=>$contract_id,'sales_type'=>0))->getField('sales_id');
			$sales_info = M('Sales')->where(array('sales_id'=>$sales_id))->field('prime_price,final_discount_rate,sales_price,total_amount')->find();
			$product_list = M('SalesProduct')->where(array('sales_id'=>$sales_id))->field('sales_product_id,product_id,amount,ori_price,unit_price,unit,cost_price,discount_rate,subtotal')->select();
			foreach ($product_list as $k=>$v) {
				$product_name = $m_product->where(array('product_id'=>$v['product_id']))->getField('name');
				$product_list[$k]['product_name'] = $product_name ? : '';
			}
			$product_info = array();
			$product_info['total_subtotal_val'] = $sales_info['prime_price'];
			$product_info['final_discount_rate'] = $sales_info['final_discount_rate'];
			$product_info['final_price'] = $sales_info['sales_price'];
			$product_info['total_amount'] = $sales_info['total_amount'];
			$product_info['product_list'] = $product_list ? $product_list : array();

			$owner_role_id = $contract_info['owner_role_id'];
			//获取权限
			$data['permission'] = permissionlist(MODULE_NAME,$owner_role_id);
			$data['data'] = $fields_list ? : array();
			$data['product_info'] = $product_info ? : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 合同删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete(){
		if ($this->isPost()) {
			$contract_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$contract_id) {
				$this->ajaxReturn('','参数错误！',0);
			} else {
				$m_contract = M('Contract');
				$m_receivables = M('Receivables');
				$m_payables = M('Payables');
				$m_r_contract_product = M('rContractProduct');
				$m_r_contract_file = M('rContractFile');
				//权限判断
				$contracts = $m_contract->where('contract_id = %d', $contract_id)->find();
				if (!in_array($contracts['owner_role_id'], $this->_permissionRes)){
					$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
				}
				//如果合同下有产品，财务和文件信息，提示先删除产品，财务和文件数据。
				// $data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
				$contract = $m_contract->where('contract_id = %d',$contract_id)->find();
				$contract_product = $m_r_contract_product->where('contract_id = %d',$contract_id)->select();//合同关联的产品记录
				$contract_file = $m_r_contract_file->where('contract_id = %d',$contract_id)->select();//合同关联的文件
				$contract_receivables = $m_receivables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->select();//合同关联的应收款
				$contract_payables = $m_payables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->select();//合同关联的应付款

				if(empty($contract_product) && empty($contract_file) && empty($contract_receivables) && empty($contract_payables)){
					if(!$m_contract->where('contract_id = %d', $contract_id)->delete()){
						$this->ajaxReturn('删除失败，请联系管理员！','删除失败，请联系管理员！',2);
					} else {
						//附表删除
						M('ContractData')->where(array('contract_id'=>$contract_id))->delete();
						//关联日程
						$event_res = M('Event')->where(array('module'=>'contract','module_id'=>$contract_id))->delete();
					}
				}else{
					if(!empty($contract_product)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同下的产品信息!','删除失败！请先删除'.$contract['number'].'合同下的产品信息!',2);
					}elseif(!empty($contract_file)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同下的文件信息!','删除失败！请先删除'.$contract['number'].'合同下的文件信息!',2);
					}elseif(!empty($contract_receivables)){
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!','删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!',2);
					}else{
						$this->ajaxReturn('删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!','删除失败！请先删除'.$contract['number'].'合同中财务下的应收款信息!',2);
					}
				}
				$this->ajaxReturn('删除成功','删除成功',1);
			}
		}
	}

	/**
	 * 合同审核历史
	 * @param 
	 * @author 
	 * @return 
	 */
	public function checkList() {
		if ($this->isPost()) {
			$m_contract_check = M('contract_check');
			$m_user = M('user');
			$contract_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if ($contract_id) {
				$check_list = $m_contract_check ->where('contract_id =%d',$contract_id)->order('check_id asc')->select();
				foreach($check_list as $kk=>$vv){
					$check_list[$kk]['user'] = $m_user ->where('role_id =%d',$vv['role_id'])->field('full_name,role_id,thumb_path')->find();
				}
				$data['list'] = $check_list ? $check_list : array();
				$data['info'] = 'success'; 
				$data['status'] = 1; 			
				$this->ajaxReturn($data,'JSON');
			}
		}
	}

	/**
	 * 合同审核
	 * @param 
	 * @author 
	 * @return 
	 */
	public function check(){
		if ($this->isPost()) {
			$contract_id = $_POST['contract_id'] ? intval($_POST['contract_id']) : 0;
			$is_agree = $_POST['is_agree'] ? intval($_POST['is_agree']) : 0;
			$is_receivables = $_POST['is_receivables'] ? intval($_POST['is_receivables']) : 0;
			$description = trim($_POST['description']);	
			$m_contract = M('Contract');
			if (!$contract_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			if (!$contract_info = $m_contract->where('contract_id = %d', $contract_id)->find()) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			M('User')->where('role_id =%d',session('role_id'))->setField('is_receivables',$is_receivables);
			$m_r_contract_sales = M('rContractSales');
			$r_contract_sales_info = $m_r_contract_sales->where(array('contract_id'=>$contract_id,'sales_type'=>array('neq',1)))->find();
			$sales_id = $m_r_contract_sales->where('contract_id = %d && sales_type = 0', $contract_id)->getField('sales_id');
			$sales_status = M('sales')->where('sales_id =%d',$sales_id)->getField('status');
			if ($contract_info['is_checked'] != 1) {
				if ($sales_status == 97 || $sales_status == 99 || $r_contract_sales_info['sales_type'] == 2 || empty($r_contract_sales_info)) {
					if ($is_agree == 1) {
						$data['is_checked'] = 1;
					} elseif ($is_agree == 2) {
						$data['is_checked'] = 2;
					} else {
						$this->ajaxReturn('','参数错误！',0);
					}

					$data['check_des'] = $description;
					$data['update_time'] = time();
					$data['examine_role_id'] = session('role_id');
					$data['check_time'] = time();
					$result = $m_contract->where('contract_id = %d', $contract_id)->save($data);

					$c_data['role_id'] = session('role_id');
					$c_data['is_checked'] = $data['is_checked'];
					$c_data['contract_id'] = $contract_id;
					$c_data['content'] = $description;
					$c_data['check_time'] = time();
					M('ContractCheck')->add($c_data);
					//商机状态改变为项目成功
					if ($contract_info['business_id']) {
						M('Business')->where('business_id =%d',$contract_info['business_id'])->setField('status_id',100);
					}
					M('sales') ->where('sales_id =%d',$sales_id)->setField('is_checked',$data['is_checked']);
					if ($result) {
						actionLog($contract_id);
						$sales = M('sales')->where('sales_id =%d',$sales_id)->find();
						//同时创建应收款单
						if ($is_agree == 1) {
							//判断是否生成应收款
							if ($is_receivables == 1) {
								$receivables = M('receivables');
								$r_data['type'] = 1;
								//应收款编号
								$receivables_custom = M('config')->where('name = "receivables_custom"')->getField('value');
								$receivables_max_id = $receivables->max('receivables_id');
								$receivables_max_id = $receivables_max_id+1;
								$receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
								$code = $receivables_custom.date('Ymd').'-'.$receivables_max_code;

								// $r_data['name'] = $receivables_custom.date('Ymd').mt_rand(1000,9999);
								$r_data['name'] = $code;
								$r_data['prefixion'] = $receivables_custom;
								$r_data['price'] = !empty($sales['sales_price']) ? $sales['sales_price'] : $contract_info['price'];
								$r_data['customer_id'] = $contract_info['customer_id'];
								$r_data['contract_id'] = $contract_id;
								$r_data['sales_id'] = $sales_id;
								$r_data['pay_time'] = $_POST['pay_time'] ? : time();
								$r_data['creator_role_id'] = $contract_info['creator_role_id'];
								$r_data['owner_role_id'] = $contract_info['owner_role_id'];
								$r_data['create_time'] = time();
								$r_data['update_time'] = time();
								$r_data['status'] = 0;
								$receivables->add($r_data);
							}
							//发送站内信
							$url = U('contract/view','id='.$contract_id);
							sendMessage($contract_info['creator_role_id'],'您创建的合同《<a href="'.$url.'">'.$contract_info['number'].'-'.$contract_info['contract_name'].'</a>》<font style="color:green;">已通过审核</font>！',1);
							
							//发站内信给财务
							if ($sales['type'] == '1') {
								$receivables_userId = getRoleByPer(array('finance/add_receivables'));
								foreach($receivables_userId as $v){
									$c = U('contract/view','id='.$contract_id);
									sendMessage($v,'《<a href="'.$c.'">'.$contract_info['number'].'-'.$contract_info['contract_name'].'</a>》<font style="color:green;">已通过审核，财务人员可添加应收款单据</font>！',1);
								}
							}
							
						} elseif ($is_agree == 2) {
							sendMessage($contract_info['creator_role_id'],'您创建的合同《<a href="'.$url.'">'.$contract_info['number'].'-'.$contract_info['contract_name'].'</a>》<font style="color:red;">经审核已被拒绝！请及时更正！</font>！',1);
						}
						$this->ajaxReturn('','审核成功！',1);
					} else {
						$this->ajaxReturn('','审核失败！',0);
					}
				} else {
					$this->ajaxReturn('','审核失败！',0);
				}
			} else {
				$this->ajaxReturn('','已审核，请勿重复操作！',0);
			}
		}
	}

	/**
	 * 合同下财务信息
	 * @param 
	 * @author 
	 * @return 
	 */
	public function finance() {
		if ($this->isPost()) {
			$contract_id = $_POST['id'] ? intval($_POST['id']) : '';
			if (!$contract_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$d_contract = D('ContractView');
			$d_role = D('RoleView');
			$m_quote_product = M('QuoteProduct');
			$contract_info = $d_contract->where(array('contract.contract_id'=>$contract_id))->find();
			//权限判断
			if (empty($contract_info)) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if (!in_array($contract_info['owner_role_id'], getPerByAction('contract','view'))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//应收款
			$m_receivables = M('Receivables');
			$m_receivingorder = M('Receivingorder');
			$m_user = M('User');
			//查询应收款列表
			$receivables_list = $m_receivables->where('is_deleted <> 1 and contract_id = %d',$contract_id)->field('receivables_id,price,create_time')->select();
			$price_all = array();
			//应收总金额
			$receivables_price_all = '0.00';
			//已收金额 
			$ys_receivables_price_all = '0.00';
			foreach ($receivables_list as $k=>$v) {
				$receivables_price_all += $v['price'];
				//单个应收款的已收金额
				$ys_receivables_price = '0.00';
				//获取该应收款对应的收款单
				$receivingorder_list = $m_receivingorder->where('is_deleted <> 1 and receivables_id = %d',$v['receivables_id'])->field('status,money,pay_time,owner_role_id,receipt_account')->select();
				foreach ($receivingorder_list as $ki=>$vi) {
					if ($vi['status'] == 1) {
						$ys_receivables_price += $vi['money'];
						$ys_receivables_price_all += $vi['money'];
					}
					switch ($vi['status']) {
						case 0 : $status_name = '待审'; break;
						case 1 : $status_name = '通过'; break;
						case 2 : $status_name = '驳回'; break;
						default : $status_name = '待审'; break;
					}
					$receivingorder_list[$ki]['status_name'] = $status_name;
					$receivingorder_list[$ki]['owner_role'] = $m_user->where(array('role_id'=>$vi['owner_role_id']))->getField('full_name');
				}
				$receivables_list[$k]['ys_receivables_price'] = trim($ys_receivables_price);
				$receivables_list[$k]['receivingorder'] = $receivingorder_list ? $receivingorder_list : array();
			}
			$price_all['receivables_price_all'] = trim($receivables_price_all);
			$price_all['sub_receivables_price_all'] = trim($receivables_price_all-$ys_receivables_price_all);
			$data['price'] = $price_all;
			$data['list'] = $receivables_list ? $receivables_list : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 合同(商机)下产品
	 * @param 
	 * @author 
	 * @return 
	 */
	public function product(){
		if ($this->isPost()) {
			$contract_id = intval($_POST['contract_id']);
			$business_id = intval($_POST['business_id']);
			if (!$contract_id && !$business_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			
			if ($contract_id) {
				$m_contract = M('Contract');
				$contract_info = $m_contract->where('contract_id = %d',$contract_id)->find();
				//权限判断
				if (empty($contract_info)) {
					$this->ajaxReturn('','合同不存在或已被删除！',0);
				}
				$m_product = M('Product');
				
				$sales_id = M('rContractSales')->where('contract_id = %d && sales_type = 0',$contract_id)->getField('sales_id');
				$sales_info = M('Sales')->where('sales_id = %d', $sales_id)->field('sales_price,final_discount_rate,prime_price')->find();
				$sales_product = M('salesProduct')->where('sales_id = %d',$sales_id)->order('sales_product_id asc')->field('product_id,ori_price,discount_rate,unit_price,amount,unit,subtotal')->select();
				foreach ($sales_product as $k=>$v) {
					$product_name = $m_product->where('product_id = %d',$v['product_id'])->getField('name');
					$sales_product[$k]['product_name'] = $product_name;
				}

				$data['data'] = $sales_info ? $sales_info : array();
				$data['list'] = $sales_product ? $sales_product : array();
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}

			if ($business_id) {
				//产品信息
				$m_product = M('Product');
				$business_info = M('Business')->where(array('business_id'=>$business_id))->field('final_price,final_discount_rate,total_subtotal_val')->find();
				//权限判断
				if (empty($business_info)) {
					$this->ajaxReturn('','商机不存在或已被删除！',0);
				}
				$product_list = M('rBusinessProduct')->where('business_id = %d', $business_id)->field('product_id,ori_price,discount_rate,unit_price,amount,unit,subtotal')->select();
				foreach ($product_list as $k=>$v) {
					$product_name = $m_product->where('product_id = %d',$v['product_id'])->getField('name');
					$product_list[$k]['product_name'] = $product_name;
				}
				$data['data'] = $business_info ? $business_info : array();
				$data['list'] = $product_list ? $product_list : array();
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}
		}
	}

	/**
	 * 合同添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		$m_contract = M('Contract');
		$m_product = M('Product');
		$m_r_contacts_customer = M('RContactsCustomer');
		$contract_custom = M('Config')->where('name="contract_custom"')->getField('value');
		if (!$contract_custom) {
			$contract_custom = '5k_crm';
		}
		if ($this->isPost()) {
			if (!is_array($_POST)) {
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			//判断合同编号是否存在
			if ($m_contract->where(array('number'=>$contract_custom.trim($_POST['number'])))->find()) {
				$this->ajaxReturn('','该合同编号已存在！',0);
			}
			if (!$_POST['customer_id']) {
				$this->ajaxReturn('','请先选择客户！',0);
			}

			$d_contract = D('Contract');
			$d_contract_data = D('ContractData');
			$field_list = M('Fields')->where('model = "contract" and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v) {
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if ($_POST[$v['field']] == '') {
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('contract',$v['field'],$_POST[$v['field']]);
						if ($res) {
							$this->ajaxReturn('',$v['name'].':'.$_POST[$v['name']].'已存在',0);
						}
					}
				}
				if ($_POST[$v['field']]) {
					switch ($v['form_type']) {
						case 'address':
							$_POST[$v['field']] = implode(chr(10),json_decode($_POST[$v['field']],true));
							break;
						case 'datetime':
							$_POST[$v['field']] = $_POST[$v['field']];
							break;
						case 'box':
							eval('$field_type = '.$v['setting'].';');
							if($field_type['type'] == 'checkbox'){
								$a = array_filter(json_decode($_POST[$v['field']],true));
								$_POST[$v['field']] = !empty($a) ? implode(chr(10),$a) : '';
							}
							break;
						default : break;
					}
				}
			}
			
			if ($d_contract->create() && $d_contract_data->create() !== false) {
				$d_contract->type = 1;
				if (empty($_POST['customer_id']) && isset($_POST['business_id'])) {
					$customer_id = M('Business')->where('business_id = %d', $_POST['business_id'])->getField('customer_id');
					$d_contract->customer_id = empty($customer_id) ? 0 : $customer_id;
				} else {
					$d_contract->customer_id = intval($_POST['customer_id']);
				}
				$d_contract->owner_role_id = $_POST['owner_role_id'] ? $_POST['owner_role_id'] : session('role_id');
				$d_contract->creator_role_id = session('role_id');
				$d_contract->create_time = time();
				$d_contract->update_time = time();
				$d_contract->count_nums = $_POST['count_nums'] ? $_POST['count_nums'] : 0; //产品总数
				$d_contract->status = L('HAS_BEEN_CREATED');

				$d_contract->number = $contract_custom.trim($_POST['number']);
				$d_contract->prefixion = $contract_custom;
				if ($contractId = $d_contract->add()) {
					$d_contract_data->contract_id = $contractId;
                	$d_contract_data->add();
					//关联日程
					$event_res = dataEvent('合同到期',$_POST['end_date'],'contract',$contractId);

					//相关附件
					if ($_POST['file']) {
						$m_contract_file = M('RContractFile');
						foreach ($_POST['file'] as $v) {
							$file_data = array();
							$file_data['contract_id'] = $contractId;
							$file_data['file_id'] = $v;
							$m_contract_file->add($file_data);
						}
					}
					//创建销售单//生成序列号
					$table_info = getTableInfo('sales');
					$m_sales = M('Sales');
					if ($m_sales->create()) {
						$m_sales->creator_role_id = session('role_id');
						$m_sales->status = 97;//未出库
						$m_sales->type = 0;
						$m_sales->sn_code = 'XSD'.date('Ymd His',time());
						$m_sales->sales_time = time();
						$m_sales->create_time = time();
						$m_sales->prime_price = $_POST['total_subtotal_val'] ? $_POST['total_subtotal_val'] : 0.00; //产品合计
						$m_sales->sales_price = $_POST['final_price'] ? $_POST['final_price'] : 0.00; // 合同总计

						if ($sales_id = $m_sales->add()) {
							$m_r_contract_sales = M('rContractSales');
							$r_data['contract_id'] = $contractId;
							$r_data['sales_id'] = $sales_id;
							$m_r_contract_sales->add($r_data);

							if (!empty($_POST['product'])) {
								if ($sales_id) {
									$add_product_flag = true;
									$m_sales_product = M('salesProduct');
									foreach ($_POST['product'] as $v) {
										$data = array();
										if (!empty($v['product_id'])) {
											$count_nums += 1;
											$data['sales_id'] = $sales_id;
											$data['product_id'] = $v['product_id'];
											$data['amount'] = $v['amount'];
											$data['unit_price'] = $v['unit_price'];
											$data['discount_rate'] = $v['discount_rate'];
											$data['subtotal'] = $v['subtotal'];
											$data['unit'] = $v['unit'];
											//产品成本
											$cost_price = '0.00';
											$cost_price = $m_product->where('product_id = %d',$v['product_id'])->getField('cost_price');
											$data['cost_price'] = $cost_price ? $cost_price : 0;
											//销售时产品售价
											$data['ori_price'] = $v['ori_price'];
											$sales_product_id = $m_sales_product->add($data);
											if (empty($sales_product_id)) {
												$add_product_flag = false;
												break;
											}
										}
									}
									if (!$add_product_flag) {
										$this->ajaxReturn('','合同产品信息创建失败！',0);
									}
								}else{
									$this->ajaxReturn('','合同产品信息创建失败！',0);
								}
							}
						}
					}
					
					//商机状态改为合同签订，客户自动锁定
					$business_id = intval($_POST['business_id']);
					$customer_id = intval($_POST['customer_id']);
					$m_business = M('Business');
					$status_type_id = $m_business->where(array('business_id'=>$business_id))->getField('status_type_id');
					$status_id = M('BusinessStatus')->where(array('type_id'=>$status_type_id,'is_end'=>3))->getField('status_id');
					$m_business->where(array('business_id'=>$business_id))->setField('status_id',$status_id);
					M('Customer')->where('customer_id =%d',$customer_id)->setField('is_locked',1);
					M('RBusinessContract')->add(array('contract_id'=>$contractId,'business_id'=>$business_id));
					actionLog($contractId);
					
					//通知合同相关审核人
					$url = U('contract/view','id='.$contractId);
					//合同审核人
					$position_ids = M('Permission')->where(array('url'=>'contract/check'))->getField('position_id',true);
					$position_ids = !empty($position_ids) ? $position_ids : array();
					$role_ids_a = M('Role')->where(array('position_id'=>array('in',$position_ids)))->getField('role_id',true);
					//管理员
					$role_ids_b = M('User')->where(array('category_id'=>1,'status'=>1))->getField('role_id',true);
					if ($role_ids_a) {
						$role_ids = array_merge($role_ids_a,$role_ids_b);
					} else {
						$role_ids = $role_ids_b;
					}
					foreach ($role_ids as $v) {
						sendMessage($v,$_SESSION['name'].'&nbsp;&nbsp;创建了新的合同《<a href="'.$url.'">'.trim($_POST['number']).'-'.trim($_POST['contract_name']).'</a>》<font style="color:green;">需要进行审核</font>！',1);
					}
					$this->ajaxReturn('','添加成功！',1);
				}else{
					$this->ajaxReturn('','添加失败,'.$d_contract->getError(),0);
				}
			}
		}
	}

	/**
	 * 合同修改
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			if (!is_array($_POST)) {
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			$m_contract = M('Contract');
			$m_sales = M('Sales');
			$contract_id = $_POST['id'] ? intval($_POST['id']) : '';
			$_POST['contract_id'] = $contract_id;

			$contract_info = D('ContractView')->where('contract.contract_id = %d',$contract_id)->find();
			if ($contract_info['is_checked'] == 1) {
				$this->ajaxReturn('','已审核的合同无法编辑！',0);
			}
			$m_product = M('Product');
			$m_sales_product = M('SalesProduct');
			if (!$contract_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if($this->_permissionRes && !in_array($contract_info['owner_role_id'], $this->_permissionRes)){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			if (!$_POST['customer_id']) {
				$this->ajaxReturn('','请先选择客户！',0);
			}
			$field_list = M('Fields')->where('model = "contract"')->order('order_id')->select();
			$d_contract = D('Contract');
			$d_contract_data = D('ContractData');
			foreach ($field_list as $v) {
				switch ($v['form_type']) {
					case 'address':
						$_POST[$v['field']] = implode(chr(10),json_decode($_POST[$v['field']]));
						break;
					case 'datetime':
						$_POST[$v['field']] = $_POST[$v['field']];
						break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$_POST[$v['field']] = implode(chr(10),json_decode($_POST[$v['field']]));
						}
						break;
					case 'editor':
						unset($_POST[$v['field']]);
						break;
				}
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if($_POST[$v['field']] == ''){
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('contract',$v['field'],$_POST[$v['name']],$contract_id);
						if($res == 1){
							$this->ajaxReturn('',$v['name'].':'.$_POST[$v['name']].'已存在',0);
						}
					}
				}
			}
			//查询合同附表是否存在
			$res_contract_data = $d_contract_data->where(array('contract_id'=>$contract_id))->find();

			if ($d_contract->create() && $d_contract_data->create() !== false) {
				$d_contract->update_time = time();
				$d_contract->contract_id = $contract_id;
				$d_contract->type = 1;
				$d_contract->owner_role_id = intval($_POST['owner_role_id']) ? : session('role_id');
				$d_contract->count_nums = $_POST['count_nums'] ? $_POST['count_nums'] : 0;

				$a = $d_contract->where(array('contract_id'=>$contract_id))->save();
				if ($res_contract_data) {
					$b = $d_contract_data->where(array('contract_id'=>$contract_id))->save();
				} else {
					$d_contract_data->contract_id = $contract_id;
					$b = $d_contract_data->add();
				}
				
				if ($a !== false && $b !== false) {
					$d_contract->where(array('contract_id'=>$contract_id))->setField('is_checked',0);
					//关联日程
					$event_res = dataEvent('合同到期',$_POST['end_date'],'contract',$contract_id);
					//修改销售单
					if ($m_sales->create()) {
						$m_sales->update_time = time();
						$m_sales->prime_price = $_POST['total_subtotal_val'];
						$m_sales->final_discount_rate = $_POST['final_discount_rate'];
						$m_sales->sales_price = $_POST['final_price'];
						$m_sales->total_amount = $_POST['total_amount'] ? $_POST['total_amount'] : 0;
						$sales_id = M('rContractSales')->where('contract_id = %d && sales_type = 0',$contract_id)->getField('sales_id');

						if (!$sales_id) {
							//处理之前编辑时没有创建相关sales数据导致无法编辑产品的问题
							$m_sales->creator_role_id = session('role_id');
							$m_sales->status = 97;//未出库
							$m_sales->type = 0;
							$m_sales->sn_code = 'XSD'.date('Ymd His',time());
							$m_sales->sales_time = time();
							$m_sales->create_time = time();

							if ($sales_id = $m_sales->add()) {
								//关系表
								$m_r_contract_sales = M('rContractSales');
								$r_data['contract_id'] = $contract_id;
								$r_data['sales_id'] = $sales_id;
								$m_r_contract_sales->add($r_data);

								if ($_POST['product']) {
									$add_product_flag = true;
									$m_sales_product = M('salesProduct');
									foreach($_POST['product'] as $v){
										if(!empty($v['product_id'])){
											$count_nums += 1;
											$data_product['sales_id'] = $sales_id;
											$data_product['product_id'] = $v['product_id'];
											$data_product['amount'] = $v['amount'];
											$data_product['unit_price'] = $v['unit_price'];
											$data_product['discount_rate'] = $v['discount_rate'];
											$data_product['subtotal'] = $v['subtotal'];
											$data_product['unit'] = $v['unit'];
											//产品成本
											$cost_price = 0;
											$cost_price = $m_product->where('product_id = %d',$v['product_id'])->getField('cost_price');
											$data_product['cost_price'] = $cost_price ? $cost_price : 0;
											//销售时产品售价
											$data_product['ori_price'] = $v['ori_price'];
											$sales_product_id = $m_sales_product->add($data_product);
											if(empty($sales_product_id)){
												$add_product_flag = false;
												break;
											}
										}
									}
									if (!$add_product_flag) {
										$this->ajaxReturn('','合同产品信息创建失败！',0);
									} else {
										$this->ajaxReturn('','修改成功！',1);
									}
								} else {
									$this->ajaxReturn('','修改成功！',1);
								}
							}
						} else {
							if ($m_sales->where('sales_id = %d',$sales_id)->save() !== false) {
								//旧的sales_product_id
								$old_sales_product_ids = array();
								$old_sales_product_ids = $m_sales_product->where('sales_id = %d',$sales_id)->getField('sales_product_id',true);
								$new_sales_product_ids = array();
								if ($_POST['product']) {
									foreach ($_POST['product'] as $v) {
										$add_product_flag = true;
										$data = array();
										if (!empty($v['product_id'])) {
											if (!empty($v['sales_product_id'])) {
												$data['amount'] = $v['amount'];
												$data['unit_price'] = $v['unit_price'];
												$data['discount_rate'] = $v['discount_rate'];
												$data['subtotal'] = $v['subtotal'];
												$data['unit'] = $v['unit'];
												//产品成本
												$cost_price = 0;
												$cost_price = $m_product->where('product_id = %d',$v['product_id'])->getField('cost_price');
												$data['cost_price'] = $cost_price ? $cost_price : 0;
												$sales_product_id = $m_sales_product->where('sales_product_id = %d',$v['sales_product_id'])->save($data);
												//剩余的的sales_product_id
												$new_sales_product_ids[] = $v['sales_product_id'];
											} else {
												$data['sales_id'] = $sales_id;
												$data['product_id'] = $v['product_id'];
												$data['amount'] = $v['amount'];
												$data['unit_price'] = $v['unit_price'];
												$data['discount_rate'] = $v['discount_rate'];
												$data['subtotal'] = $v['subtotal'];
												$data['unit'] = $v['unit'];
												//产品成本
												$cost_price = 0;
												$cost_price = $m_product->where('product_id = %d',$v['product_id'])->getField('cost_price');
												$data['cost_price'] = $cost_price ? $cost_price : 0;
												//销售时产品售价
												$data['ori_price'] = $v['ori_price'];
												$sales_product_id = $m_sales_product->add($data);
												if(empty($sales_product_id)){
													$add_product_flag = false;
													break;
												}
											}
										}
									}
									//需要删除的sales_product_id
									$del_sales_product_ids = array();
									$del_sales_product_ids = array_diff($old_sales_product_ids, $new_sales_product_ids);
									if ($del_sales_product_ids) {
										if (!$m_sales_product->where(array('sales_product_id'=>array('in',$del_sales_product_ids)))->delete()) {
											$add_product_flag = false;
										}
									}
									if ($add_product_flag) {
										$m_sales->where('sales_id =%d',$sales_id)->setField('is_checked',0);
										$this->ajaxReturn('','修改成功！',1);
									} else {
										$this->ajaxReturn('','合同产品信息修改失败！',0);
									}
								} else {
									$this->ajaxReturn('','修改成功！',1);
								}
							} else {
								$this->ajaxReturn('','修改失败！',0);
							}
						}
					}
				} else {
					$this->ajaxReturn('','修改失败！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败,'.$d_contract->getError().$d_contract_data->getError(),0);
			}
		}
	}
}