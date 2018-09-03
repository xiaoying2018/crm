<?php
/**
 *商机相关
 **/
class BusinessVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('advance','status','business_list','advancehistory','getbusinessstatus')
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
	}

	/**
	 * 商机列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			$permission_list = apppermission('business','index');
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			$d_v_business = D('BusinessTopView');
			$below_ids = getPerByAction('business','index',true);
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$by = $_POST['by'] ? trim($_POST['by']) : 'all';
			$order = "top.set_top desc, top.top_time desc ,business_id desc";
			$where = array();

			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'business.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',business.business_id asc';
			}
			switch ($by) {
				case 'create' : $where['creator_role_id'] = session('role_id'); break;
				case 'sub' : $where['owner_role_id'] =array('in',$below_ids); break;
				case 'subcreate' :
					$where['creator_role_id'] = array('in',$below_ids); break;
				case 'today' :
					$where['nextstep_time'] = array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt',0), 'and');
					break;
				case 'week' :
					$where['nextstep_time'] = array(array('lt',strtotime(date('Y-m-d', time())) + (8-date('N', time())) * 86400), array('gt', 0),'and');
					break;
				case 'month' :
					$where['nextstep_time'] = array(array('lt',strtotime(date('Y-m-01', strtotime('+1 month')))), array('gt', 0),'and');
					break;
				case 'd7' :
					$where['update_time'] = array('lt',strtotime(date('Y-m-d', time()))-86400*6);
					break;
				case 'd15' :
					$where['update_time'] = array('lt',strtotime(date('Y-m-d', time()))-86400*14);
					break;
				case 'd30' :
					$where['update_time'] = array('lt',strtotime(date('Y-m-d', time()))-86400*29);
					break;
				case 'deleted' : $where['is_deleted'] = 1; break;
				case 'add' : $order = 'business.create_time desc,business.business_id asc'; break;
				case 'update' : $order = 'business.update_time desc,business.business_id asc'; break;
				case 'me' : $where['business.owner_role_id'] = session('role_id'); break;
				default :
					$where['business.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
				break;
			}
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);

				if($field =="customer_id"){
					$c_where['name'] = array('like','%'.$search.'%');
					//权限
					$customer_ids = M('Customer')->where($c_where)->getField('customer_id',true);
					$where[$field] = array('in',$customer_ids);
				}elseif($field =="status_id"){
					unset($where['status_id']);
				}elseif($field == 'name'){
					//获取客户ID
					$cus_where['name'] = array('like','%'.$search.'%');
					$customer_ids = M('Customer')->where($cus_where)->getField('customer_id',true);
					$customer_str = implode(',',$customer_ids);
					//获取联系人ID
					$c_where['_string'] = 'name like "%'.$search.'%" or telephone like "%'.$search.'%"';
					$contacts_ids = M('contacts')->where($c_where)->getField('contacts_id',true);
					$contacts_str = implode(',',$contacts_ids);
					if($customer_str && $contacts_str){
						$where['_string'] = 'business.name like "%'.$search.'%" or business.customer_id in ('.$customer_str.') or business.contacts_id in ('.$contacts_str.')';
					}elseif($customer_str){
						$where['_string'] = 'business.name like "%'.$search.'%" or business.customer_id in ('.$customer_str.')';
					}elseif($contacts_str){
						$where['_string'] = 'business.name like "%'.$search.'%" or business.contacts_id in ('.$contacts_str.')';
					}else{
						$where['_string'] = 'business.name like "%'.$search.'%"';
					}
					
				}else{
					switch ($condition) {
						case "is" : $where[$field] = array('eq',$search);break;
						case "isnot" :  $where[$field] = array('neq',$search);break;
						case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
						case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
						case "start_with" :  $where[$field] = array('like',$search.'%');break;
						case "end_with" :  $where[$field] = array('like','%'.$search);break;
						case "is_empty" :  $where[$field] = array('eq','');break;
						case "is_not_empty" :  $where[$field] = array('neq','');break;
						case "gt" :  $where[$field] = array('gt',$search);break;
						case "egt" :  $where[$field] = array('egt',$search);break;
						case "lt" :  $where[$field] = array('lt',$search);break;
						case "elt" :  $where[$field] = array('elt',$search);break;
						case "eq" : $where[$field] = array('eq',$search);break;
						case "neq" : $where[$field] = array('neq',$search);break;
						case "between" : $where[$field] = array('between',array($search-1,$search+86400));break;
						case "nbetween" : $where[$field] = array('not between',array($search,$search+86399));break;
						case "tgt" :  $where[$field] = array('gt',$search+86400);break;
						default : $where[$field] = array('eq',$search);
					}
				}
				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$search );
				//过滤不在权限范围内的role_id
				if(trim($_REQUEST['field']) == 'owner_role_id'){
					if(!in_array(trim($search),$below_ids)){
						$where['owner_role_id'] = array('in',$below_ids);
					}
				}
			}

			if (!isset($where['is_deleted'])) {
				$where['business.is_deleted'] = 0;
			}
			if (!isset($where['business.owner_role_id'])) {
				$where['business.owner_role_id'] = array('in', $below_ids);
			}
			if ($_REQUEST["search"]) {
				$where['name'] = array('like','%'.$_REQUEST["search"].'%');
			}

			//客户下商机
			if (intval($_POST['customer_id'])) {
				unset($where);
				$where['customer_id'] = intval($_POST['customer_id']);
			}
			//联系人下商机
			if (intval($_POST['contacts_id'])) {
				unset($where);
				$business_ids = M('RBusinessContacts')->where('contacts_id = %d',intval($_POST['contacts_id']))->getField('business_id',true);
				if ($business_ids) {
					$where['business_id'] = array('in',$business_ids);
				} else {
					$where['business_id'] = 0;
				}
			}

			//多选类型字段
			$check_field_arr = M('Fields')->where(array('model'=>'business','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
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
							}elseif (($v['start'] != '' || $v['end'] != '')){
								if($k == 'create_time'){
									$k = 'business.create_time';
								}elseif($k == 'update_time'){
									$k = 'business.update_time';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
								}
							}elseif($k =='owner_role_id' || $k =='creator_role_id'){
								if(!empty($v)){
									$where['business.'.$k] = $v['value'];
								}
							} elseif(($v['value']) != '') {
								if (in_array($k,$check_field_arr)) {
									$where[$k] = field($v['value'],'contains');
								} else {
									$where[$k] = field($v['value'],$v['condition']);
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
				if(isset($where['owner_role_id'])){
					if(is_array($where['owner_role_id']) && $where['owner_role_id']['1'] && !in_array(intval($where['owner_role_id']['1']),$this->_permissionRes)){
						$where['business.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					}
				}
			}

			$list = $d_v_business->where($where)->field('name,business_id,final_price,customer_id,owner_role_id,status_id,create_time,status_type_id')->order($order)->page($p.',10')->select();

			$m_receivables = M('Receivables');
			$m_receivingorder = M('Receivingorder');
			$m_contract = M('Contract');
			$m_customer = M('Customer');
			$m_business_status = M('BusinessStatus');
			$m_business_data = M('BusinessData');

			foreach ($list as $k=>$v) {
				//判断附表
				if (!$m_business_data->where(array('business_id'=>$v['business_id']))->find()) {
					$res_data = array();
					$res_data['business_id'] = $v['business_id'];
					$m_business_data->add($res_data);
				}

				$list[$k]['customer_name'] = $m_customer->where('customer_id = %d',$v['customer_id'])->getField('name');
				//收款进度
				$contract_info = $m_contract->where('business_id = %d',$v['business_id'])->field('contract_id,price')->find();
				$schedule = 0;
				if($contract_info){
					//应收款
					$receivables_id = $m_receivables->where('contract_id = %d',$contract_info['contract_id'])->getField('receivables_id');
					//回款金额
					$sum_price = 0;
					$sum_price = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
					//当前收款进度
					if($sum_price){
						if($contract_info['price'] == 0 || $contract_info['price'] == ''){
							$schedule = 100;
						}else{
							$schedule = round(($sum_price/$contract_info['price'])*100,0);
						}
					}
				}
				$list[$k]['schedule'] = $schedule ? $schedule : 0;
				//进度名
				$status_name = $m_business_status->where(array('status_id'=>$v['status_id'],'type_id'=>$v['status_type_id']))->getField('name');
				$list[$k]['status_name'] = $status_name;
				//获取操作权限
				$list[$k]['permission'] = permissionlist('business',$v['owner_role_id']);
			}
			$list = empty($list) ? array() : $list;
			$count = $d_v_business->where($where)->count();

			//自定义场景
			if($p == 1 && $_POST['search'] == ''){
				$m_scene = M('Scene');
				$scene_where = array();
				$scene_where['role_id']  = session('role_id');
				$scene_where['type']  = 1;
				$scene_where['_logic'] = 'or';
				$map_scene['_complex'] = $scene_where;
				$map_scene['module'] = 'business';
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
				$where_field['model'] = array('in',array('','business'));
				$where_field['is_main'] = '1';
				$where_field['field'] = array('not in',array('delete_role_id','is_deleted','delete_time','status_id','customer_id','contacts_id'));
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
					} elseif ($v['field'] == 'possibility') {
						$fields_list[$k]['setting'] = array('10%'=>'10%','20%'=>'20%','30%'=>'30%','40%'=>'40%','50%'=>'50%','60%'=>'60%','70%'=>'70%','80%'=>'80%','90%'=>'90%','100%'=>'100%');
					} else {
						$fields_list[$k]['setting'] = '';
					}
				}
			}

			if ($p == 1 && $_POST['search'] == '') {
				$data['fields_list'] = $fields_list ? $fields_list : array();
				//场景信息
				$data['scene_list'] = $scene_list ? $scene_list : array();
			} else {
				$data['fields_list'] = array();
				$data['scene_list'] = array();
			}
			$page = ceil($count/10);
			$data['list'] = $list ? $list : array();
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}else{
			$this->ajaxReturn('','error',0);
		}
	}

	/**
	 * 商机添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add() {
		if ($this->isPost()) {
			$m_business = D('Business');
			$m_business_data = D('BusinessData');
			$m_r_business_product = M('RBusinessProduct');
			$params = $_POST;
			if (empty($params['customer_id'])) {
				$this->ajaxReturn('','客户不能为空！',0);
			}
			if (count($params['product']) == 0) {
				$this->ajaxReturn('','请至少选择一个产品！',0);
			}
			if (!$params['status_type_id']) {
				$this->ajaxReturn('','请选择商机状态组！',0);
			}
			if (!$params['status_id']) {
				$this->ajaxReturn('','请选择商机进度！',0);
			}
			if($m_business->create()){
				if($m_business_data->create() !== false){
					$m_business->create_time = $m_business->update_time = time();
					$m_business->creator_role_id = $m_business->owner_role_id = session('role_id');
					//商机编号
					$business_custom = M('Config')->where('name = "business_custom"')->getField('value');
					$business_max_id = $m_business->max('business_id');
					$business_max_code = str_pad($business_max_id+1,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
					$code = $business_custom.date('Ymd').'-'.$business_max_code;

					if(empty($params['name'])){
						$m_business->name = $code;
					}
					$m_business->code = $params['code'] ? trim($params['code']) : $code;
					$m_business->prefixion = $business_custom;
					if ($business_id = $m_business->add()) {
						$m_business_data->business_id = $business_id;
						if ($m_business_data->add()) {
							//客户到期时间
							$m_customer = M('Customer');
							$m_customer->where('customer_id = %d',$customer_id)->setField('update_time',time());
							//关联产品信息
							$business_product_ids = $params['product'];
							foreach($business_product_ids as $k=>$v){
								$product_data = array();
								$product_data['business_id'] = $business_id;
								$product_data['product_id'] = $v['product_id'];
								$product_data['ori_price'] = $v['ori_price'];
								$product_data['discount_rate'] = $v['discount_rate'];
								$product_data['unit_price'] = $v['unit_price'];
								$product_data['amount'] = $v['amount'];
								$product_data['subtotal'] = $v['subtotal'];
								$product_data['unit'] = $v['unit'];
								$m_r_business_product->add($product_data);
							}
							//相关附件
							if($params['file']){
								$m_business_file = M('RBusinessFile');
								foreach($params['file'] as $v){
									$file_data = array();
									$file_data['business_id'] = $business_id;
									$file_data['file_id'] = $v;
									$m_business_file->add($file_data);
								}
							}
							//商机状态为签订合同时，将客户锁定
							if(intval($params['status_id']) == 100){
								$m_customer->where('customer_id = %d', $customer_id)->setField('is_locked',1);
							}
							actionLog($business_id);
							$this->ajaxReturn('','添加成功！',1);
						}
					} else {
						$this->ajaxReturn('','添加失败！',0);
					}
				} else {
					$this->ajaxReturn('',$m_business_data->getError(),0);
				}
			}else{
				$this->ajaxReturn('',$m_business->getError(),0);
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 商机编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit() {
		if ($this->isPost()) {
			$params = $_POST;
			$business_id = $params['id'] ? intval($params['id']) : '';
			$m_business = M('Business');
			$m_customer = M('Customer');
			$m_contacts = M('Contacts');
			$m_r_business_product = M('RBusinessProduct');
			$m_product = M('Product');

			$business_info = $m_business->where(array('business_id'=>$business_id))->find();
			if (!$business_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			$m_business_data = M('BusinessData');
			//判断附表
			if (!$m_business_data->where(array('business_id'=>$business_info['business_id']))->find()) {
				$res_data = array();
				$res_data['business_id'] = $business_info['business_id'];
				$m_business_data->add($res_data);
			}
			//判断权限
			$below_ids = getPerByAction('business','edit');
			if ($business_info && !in_array($business_info['owner_role_id'],$below_ids)) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			if (!$params['status_type_id']) {
				$this->ajaxReturn('','请选择商机状态组！',0);
			}
			if (!$params['status_id']) {
				$this->ajaxReturn('','请选择商机进度！',0);
			}

			$d_business = D('business');
			$d_business_data = D('BusinessData');
			if ($d_business->create($params) && $d_business_data->create($params) !== false) {
				$d_business->update_time = time();
				$d_business->business_id = $business_id;

				$a = $d_business->where('business_id = %d', $business_id)->save();
				if ($d_business_data->where(array('business_id'=>$business_id))->find()) {
					$b = $d_business_data->where('business_id = %d', $business_id)->save();
				} else {
					$d_business_data->business_id = $business_id;
					$b = $d_business_data->add();
				}
				if ($a !== false && $b !== false) {
					if ($params['contacts_id']) {
						$m_r_business_contacts = M('RBusinessContacts');
						$contacts_data = array();
						$contacts_data['contacts_id'] = intval($params['contacts_id']);
						$res = $m_r_business_contacts->where('business_id = %d',$business_id)->find();
						if ($res) {
							$m_r_business_contacts->where('business_id = %d',$business_id)->save($contacts_data);
						} else {
							$contacts_data['business_id'] = $business_id;
							$m_r_business_contacts->add($contacts_data);
						}
					}
					$update_res = true;
					$add_res = true;
					$delete_res = true;
					//有r_id的为更新，之前有现在无的为删除，其他的为新增
					$old_r_ids = $m_r_business_product->where('business_id = %d', $business_id)->getField('id',true);
					$new_r_ids = array();
					$business_product_ids = $params['product'];
					foreach ($business_product_ids as $v) {
						$new_r_ids[] = $v['r_id'];
					}
					//获取差集(需要删除的r_id)
					$delete_r_ids = array_diff($old_r_ids,$new_r_ids);
					foreach ($business_product_ids as $v) {
						$product_data = array();
						$product_data['business_id'] = $business_id;
						$product_data['product_id'] = $v['product_id'];
						$product_data['ori_price'] = $v['ori_price'];
						$product_data['discount_rate'] = $v['discount_rate'];
						$product_data['unit_price'] = $v['unit_price'];
						$product_data['amount'] = $v['amount'];
						$product_data['subtotal'] = $v['subtotal'];
						$product_data['unit'] = $v['unit'];
						if (!empty($v['r_id'])) {
							//更新
							$update_res = $m_r_business_product->where('id = %d',$v['r_id'])->save($product_data);
						} else {
							//添加
							$add_res = $m_r_business_product->add($product_data);
						}
					}
					//删除
					if ($delete_res) {
						$delete_res = $m_r_business_product->where(array('id'=>array('in',$delete_r_ids)))->delete();
					}
					$this->ajaxReturn('','修改商机成功！',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			}
		}
	}

	/**
	 * 商机删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		$business_id = intval($_POST['id']) ? : '';
		$m_business = M('Business');
		$m_contract = M('Contract');
		$m_log = M('Log');
		$r_module = array('RBusinessCustomer', 'Event'=>'RBusinessEvent', 'File'=>'RBusinessFile', 'Log'=>'RBusinessLog', 'RBusinessProduct', 'Task'=>'RBusinessTask');

		$business_info = $m_business->where(array('business_id'=>$business_id))->find();
		if (!$business_info) {
			$this->ajaxReturn('','数据不存在或已删除！',0);
		}
		//判断权限
		$below_ids = getPerByAction('business','view');
		if (!in_array($business_info['owner_role_id'],$below_ids)) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}
		//判断是否有相关合同(如有合同，则需先删除合同信息)
		$contract_info = $m_contract->where(array('business_id'=>$business_id))->find();
		if ($contract_info) {
			$this->ajaxReturn('','该商机下已有合同，请先删除相关合同信息！',0);
		}
		if ($m_business->where('business_id = %d', $business_id)->delete()) {
			M('BusinessData')->where(array('business_id'=>$business_id))->delete();
			actionLog($business_id);
			foreach ($r_module as $key2=>$value2) {
				if (!is_int($key2)) {
					$module_ids = M($value2)->where('business_id = %d', $business_id)->getField($key2 . '_id',true);
					$m_key = M($key2);
					$m_key->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
					M($value2)->where('business_id = %d', $business_id)->delete();
				}
			}
			$this->ajaxReturn('','删除成功！',1);
		} else {
			$this->ajaxReturn('','删除失败！',0);
		}
	}

	/**
	 * 商机详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view() {
		if ($this->isPost()) {
			$business_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			//判断附表有无数据（没有则新建）
			$m_business_data = M('BusinessData');
			$res_data = $m_business_data->where(array('business_id'=>$business_id))->find();
			if (!$res_data) {
				$bus_data = array();
				$bus_data['business_id'] = $business_id;
				$m_business_data->add($bus_data);
			}
			$d_business = D('BusinessView');
			$m_customer = M('Customer');
			$m_contacts = M('Contacts');
			$business_info = $d_business ->where('business.business_id = %d',$business_id)->find();
			if (!$business_info) {
				$this->ajaxReturn('','商机不存在或已被删除！',0);
			} elseif (!in_array($business_info['owner_role_id'],getPerByAction('business','view'))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			//关联联系人
			$contacts_id = M('RBusinessContacts')->where('business_id = %d',$business_id)->order('id desc')->getField('contacts_id');
			$contacts_info = $m_contacts->where(array('contacts_id'=>$contacts_id))->field('name,telephone')->find();
			//模拟自定义字段返回数据
			$field_array = array(
				'0'=>array('field'=>'name','name'=>'商机名称'),
				'1'=>array('field'=>'code','name'=>'商机编号'),
				'2'=>array('field'=>'customer_id','name'=>'相关客户'),
				'3'=>array('field'=>'contacts_id','name'=>'联系人'),
				'4'=>array('field'=>'status_type_id','name'=>'商机状态组'),
				'5'=>array('field'=>'status_id','name'=>'商机进度'),
				'6'=>array('field'=>'possibility','name'=>'可能性')
			);
			$business_field_list = array();
			foreach ($field_array as $k=>$v) {
				$business_field_list[$k]['field'] = $v['field'];
				$business_field_list[$k]['name'] = $v['name'];
				switch ($v['field']) {
					case 'customer_id' :
						$customer_name = $m_customer->where(array('customer_id'=>$business_info[$v['field']]))->getField('name');
						$id = $business_info[$v['field']] ? $business_info[$v['field']] : '';
						$val = $customer_name ? $customer_name : '';
						$type = 3;
						break;
					case 'contacts_id' :
						$id = $contacts_id ? $contacts_id : '';
						$val = $contacts_info ? $contacts_info['name'] : '';
						$type = 5;
						break;
					case 'status_type_id' : 
						$id = $business_info['status_type_id'];
						$type_name = M('BusinessType')->where(array('id'=>$business_info['status_type_id']))->getField('name');
						$val = $type_name ? $type_name : '';
						$type = 0;
						break;
					case 'status_id' : 
						$id = $business_info['status_id'];
						$status_name = M('BusinessStatus')->where(array('status_id'=>$business_info['status_id']))->getField('name');
						$val = $status_name ? $status_name : '';
						$type = 0;
						break;
					case 'possibility' : 
						$id = $business_info['possibility'];
						$val = $business_info['possibility'];
						$type = 0;
						break;
					default :
						$id = '';
						$val = $business_info[$v['field']] ? $business_info[$v['field']] : '';
						$type = 0;
						break;
				}
				$business_field_list[$k]['id'] = $id;
				$business_field_list[$k]['val'] = $val;
				$business_field_list[$k]['type'] = $type;
			}
			//取得字段列表
			$where_field = array();
			$where_field['model'] = 'business';
			$where_field['field'] = array('not in',array('name','customer_id','contacts_id','status_id','possibility'));
			$field_list = M('Fields')->where($where_field)->order('order_id')->select();
			$i = 6;
			foreach ($field_list as $k=>$v) {
				$field = trim($v['field']);
				$data_list[$i]['field'] = $field;
				$data_list[$i]['name'] = trim($v['name']);
				if ($v['setting']) { 
					//将内容为数组的字符串格式转换为数组格式
					eval("\$setting = ".$v['setting'].'; ');
					$data_list[$i]['form_type'] = $setting['type'] == 'checkbox' ? 'checkbox' : 'select';
				} else {
					$data_list[$i]['form_type'] = $v['form_type'];
				}
				$data_a = trim($business_info[$v['field']]);
				if($v['form_type'] == 'address') {
					$address_array = str_replace(chr(10),' ',$data_a);
					$data_list[$i]['val'] = $address_array;
					$data_list[$i]['type'] = 0;
				} else {
					$data_list[$i]['val'] = $data_a;
					$data_list[$i]['type'] = 0;
				}
				$data_list[$i]['id'] = '';
				$i++;
			}
			$business_list = array_merge($business_field_list,$data_list);

			//获取产品信息
			$product_info = array();
			$m_product = M('Product');
			$product_list = M('rBusinessProduct')->where(array('business_id'=>$business_id))->field('id,product_id,amount,ori_price,unit_price,unit,discount_rate,subtotal')->select();
			foreach ($product_list as $k=>$v) {
				$product_name = $m_product->where(array('product_id'=>$v['product_id']))->getField('name');
				$product_list[$k]['product_name'] = $product_name ? : '';
				$product_list[$k]['r_id'] = $v['id'];
			}
			$product_info['product_list'] = $product_list ? $product_list : array();
			$product_info['total_subtotal_val'] = $business_info['total_subtotal_val'];
			$product_info['final_discount_rate'] = $business_info['final_discount_rate'];
			$product_info['final_price'] = $business_info['final_price'];
			$product_info['total_amount'] = $business_info['total_amount'];

			$data['product_info'] = $product_info ? : array();
			//获取权限
			$data['permission'] = permissionlist('business',$business_info['owner_role_id']);
			$data['data'] = $business_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 商机动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic() {
		if ($this->isPost()) {
			if ($this->roles == 2) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$business_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$business_info = M('Business')->where('business_id = %d', $business_id)->field('name,customer_id,owner_role_id,final_price,status_id,status_type_id')->find();
			if (!$business_info) {
				$this->ajaxReturn('','数据不存在！',0);
			} elseif (!in_array($business_info['owner_role_id'], getPerByAction('business','view'))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			$m_receivables = M('Receivables');
			$m_receivingorder = M('Receivingorder');
			$m_contract = M('Contract');
			$m_customer = M('Customer');
			$m_business_status = M('BusinessStatus');

			$business_info['owner'] = M('User')->where(array('role_id'=>$business_info['owner_role_id']))->field('full_name,role_id')->find();
			$business_info['customer_name'] = $m_customer->where('customer_id = %d',$business_info['customer_id'])->getField('name');
			//收款进度
			$contract_info = $m_contract->where('business_id = %d',$business_id)->field('contract_id,price')->find();
			$schedule = 0;
			if($contract_info){
				//应收款
				$receivables_id = $m_receivables->where('contract_id = %d',$contract_info['contract_id'])->getField('receivables_id');
				//回款金额
				$sum_price = 0;
				$sum_price = $m_receivingorder->where(array('receivables_id'=>$receivables_id,'status'=>1))->sum('money');
				//当前收款进度
				if($sum_price){
					if($contract_info['price'] == 0 || $contract_info['price'] == ''){
						$schedule = 100;
					}else{
						$schedule = round(($sum_price/$contract_info['price'])*100,0);
					}
				}
			}
			$business_info['schedule'] = $schedule ? $schedule : 0;
			//进度名
			$business_info['status_name'] = $m_business_status->where(array('status_id'=>$business_info['status_id'],'type_id'=>$business_info['status_type_id']))->getField('name');
			//获取操作权限
			$business_info['permission'] = permissionlist('business',$business_info['owner_role_id']);

			$data['data'] = $business_info;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 商机推进
	 * @param 
	 * @author 
	 * @return 
	 */
	public function advance() {
		if ($this->isPost()) {
			$business_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$params = $_POST;
			$m_r_bs = M('RBusinessStatus');
			$m_customer = M('Customer');

			//权限判断
			$m_business = M('Business');
			$business_info = $m_business ->where('business_id = %d',$business_id)->find();
			if (!$business_info) {
				$this->ajaxReturn('','商机不存在或已被删除！',0);
			}
			if (!in_array($business_info['owner_role_id'],getPerByAction('business','edit'))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			if (!$params['status_id']) {
				$this->ajaxReturn('','请选择推进阶段！',0);
			}

			//商机推进记录
			$data = array();
			$data['business_id'] = $business_id;
			$data['gain_rate'] = '';
			$data['status_id'] = $params['status_id'];
			$data['description'] = $params['description'];
			$data['owner_role_id'] = $business_info['owner_role_id'];
			$data['update_time'] = time();
			$data['update_role_id'] = session('role_id');
			$m_r_bs->add($data);

			$m_business = M('Business');
			$data2 = array();
			$data2['update_time'] = time();
			$data2['status_id'] = $params['status_id'];
			$data2['nextstep_time'] = $params['nextstep_time'];
			$data2['nextstep'] = $params['nextstep'];
			$data2['update_role_id'] = session('role_id');

			$status_info = M('BusinessStatus')->where(array('type_id'=>$business_info['status_type_id'],'status_id'=>$params['status_id']))->find();
			if($status_info['is_end'] == 3){
				//锁定客户
				$m_customer->where('customer_id = %d', $business_info['customer_id'])->setField('is_locked',1);
			}
			if ($m_business->where('business_id = %d', $business_id)->save($data2)) {
				//跟新客户时间
				$m_customer->where('customer_id = %d',$business_info['customer_id'])->setField('update_time',time());
				actionLog($business_id);
				$this->ajaxReturn('','推进成功！',1);
			} else {
				$this->ajaxReturn('','推进失败，请重试！',0);
			}
		}
	}

	/**
	 * 获取商机推进状态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function status() {
		if ($this->isPost()) {
			$business_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if ($business_id) {
				$business_info = M('Business')->where('business_id = %d', $business_id)->field('status_id,status_type_id')->find();
				$status_id = $business_info['status_id'];
				$order_id = M('BusinessStatus')->where('status_id = %d', $status_id)->getField('order_id');
				if (!$order_id) {
					$order_id = 0;	
				}
				$status_list = M('BusinessStatus')->where(array('order_id'=>array('egt',$order_id),'type_id'=>$business_info['status_type_id']))->order('order_id')->select();
				$data['list'] = $status_list;
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			} else {
				$this->ajaxReturn('','参数错误！',0);
			}
		}
	}
	
	/**
	 * 商机推进历史
	 * @param 
	 * @author 
	 * @return 
	 */
	public function advancehistory() {
		if ($this->isPost()) {
			$business_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (!$business_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$advance_list = M('RBusinessStatus')->where('business_id = %d',$business_id)->field('status_id,description,update_time,update_role_id')->order('update_time desc')->select();
			if ($advance_list) {
				$m_business_status = M('BusinessStatus');
				$m_user = M('User');
				foreach ($advance_list as $k=>$v) {
					$status_name = $m_business_status->where('status_id = %d',$v['status_id'])->getField('name');
					$advance_list[$k]['status_name'] = empty($status_name) ? '' : $status_name;
					//负责人
					$user_info = $m_user->where('role_id = %d',$v['update_role_id'])->field('name,thumb_path')->find();
					$advance_list[$k]['role_name'] = $user_info['name'];
					$advance_list[$k]['img'] = $user_info['thumb_path'];
				}
			}
			$data['list'] = empty($advance_list) ? array() : $advance_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 获取商机状态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function getbusinessStatus(){
		$type_id = $_POST['status_type_id'] ? intval($_POST['status_type_id']) : 0;
		if (!$type_id) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$status_list = M('BusinessStatus')->where(array('type_id'=>$type_id))->order('order_id asc')->select();
		$this->ajaxReturn($status_list,'',1);
	}
}