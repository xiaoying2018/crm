<?php
/**
 *联系人相关
 **/
class ContactsVue extends Action{
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('reltobusiness')
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
	 * 联系人列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			//获取添加权限
			$permission_list = apppermission('contacts','index');
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			$rcc = M('RContactsCustomer');
			$m_contacts = M('Contacts');
			$d_contacts = D('ContactsView');
			$m_fields = M('Fields');
			$m_customer = M('Customer');

			$below_ids = getPerByAction('contacts','index');
			$where = array();

			$order = "contacts.update_time desc,contacts.contacts_id asc";
			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'contacts.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',contacts.contacts_id asc';
			}
			if (isset($_POST['search'])) {
				$where['name'] = array('like','%'.trim($_POST['search']).'%');
			}
			switch ($by) {
				case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
				case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
				case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
				case 'add' : $order = 'create_time desc'; break;
				case 'update' : $order = 'update_time desc'; break;
				case 'deleted' : $where['is_deleted'] = 1; break;
				default : 
					//权限范围内的customer_id
					$where['customer.owner_role_id'] = array('in', $below_ids);
					break;
			}
			if (!isset($where['customer.owner_role_id'])) {
				$where['customer.owner_role_id'] = array('in', $below_ids);
			}
			//客户下联系人
			if ($_POST['customer_id']) {
				if (isset($where['RContactsCustomer.customer_id'])) {
					unset($where['RContactsCustomer.customer_id']);
				}
				$contacts_ids = M('rContactsCustomer')->where('customer_id = %d', intval($_POST['customer_id']))->getField('contacts_id', true);
				if ($contacts_ids) {
					$where['contacts.contacts_id'] = array('in',$contacts_ids);
				} else {
					$where['contacts.contacts_id'] = 0;
				}
			}
			//商机下联系人
			if ($_POST['business_id']) {
				$contacts_ids = M('RBusinessContacts')->where('business_id = %d',intval($_POST['business_id']))->getField('contacts_id',true);
				if ($contacts_ids) {
					$where['contacts.contacts_id'] = array('in',$contacts_ids);
				} else {
					$where['contacts.contacts_id'] = 0;
				}
			}
			$where['is_deleted'] = 0;

			//多选类型字段
			$check_field_arr = M('Fields')->where(array('model'=>'contacts','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
			//高级搜索
			if(!$_POST['field']){
				$no_field_array = array('act','content','p','search','listrows','by','contract_checked','order_field','order_type','token');
				foreach($_POST as $k => $v){
					if(!in_array($k,$no_field_array)){
						if(is_array($v)){
							if ($v['state']) {
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
							} elseif (($v['start'] != '' || $v['end'] != '')) {
								if($k == 'create_time'){
									$k = 'contacts.create_time';
								}elseif($k == 'update_time'){
									$k = 'contacts.update_time';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
								}
							} elseif (($v['value']) != '') {
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
					if($k != 'search'){
						if(is_array($v)){
							foreach ($v as $key => $value) {
								$params[] = $k . '[' . $key . ']=' . $value;
							}
						}else{
							$params[] = $k . '=' . $v;
						} 
					}
	            }
	            //所属客户
				if(isset($where['customer_id'])){
					$c_where['name'] = array('like','%'.$where['customer_id'][1].'%');
					$c_where['is_deleted'] = 0;
					$c_where['owner_role_id'] = array('in',$below_ids); //过滤权限
					$customer_str = M('Customer')->where($c_where)->getField('customer_id',true);
					unset($where['customer_id']);
				}
			}
			//所属客户
			if ($customer_str) {
				$where['rContactsCustomer.customer_id'] = array('in',$customer_str);
			}

			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$list = $d_contacts->where($where)->page($p.',10')->order($order)->field('contacts_id,name,post,telephone')->select();
			$count = $d_contacts->where($where)->count();
			foreach ($list as $key=>$value) {
				$customer_id = $rcc->where('contacts_id = %d', $value['contacts_id'])->getField('customer_id');
				$customer_info = $m_customer->where('customer_id = %d', $customer_id)->field('name,owner_role_id')->field('name,owner_role_id')->find();
				$list[$key]['customer_name'] = $customer_info['name'];
				//获取操作权限
				$list[$key]['permission'] = permissionlist('contacts',$customer_info['owner_role_id']);
			}

			//自定义场景
			if($p == 1 && $_POST['search'] == ''){
				$m_scene = M('Scene');
				$scene_where = array();
				$scene_where['role_id']  = session('role_id');
				$scene_where['type']  = 1;
				$scene_where['_logic'] = 'or';
				$map_scene['_complex'] = $scene_where;
				$map_scene['module'] = 'contacts';
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
				$where_field['model'] = array('in',array('','contacts'));
				$where_field['is_main'] = '1';
				$where_field['field'] = array('not in',array('delete_role_id','is_deleted','delete_time'));
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
		}
	}

	/**
	 * 联系人添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add() {
		if ($this->isPost()) {
			$params = $_POST;
			$customer_id = intval($params['customer_id']);
			if ($params['name'] == '' || $params['name'] == null) {
				$this->ajaxReturn('','联系人姓名不能为空！',0);
			}
			if ($customer_id == '' || $customer_id == null) {
				$this->ajaxReturn('','所属客户不能为空！',0);
			}
			$d_contacts = D('Contacts');
			$d_contacts_data = D('ContactsData');
			//自定义字段
			$field_list = M('Fields')->where('model = "contacts" and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v){
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if ($params[$v['field']] == '') {
							$this->ajaxReturn($v['name'].'不能为空',$v['name'].'不能为空',2);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('contacts',$v['field'],$params[$v['field']]);
						if($res){
							$this->ajaxReturn($v['name'].':'.$params[$v['name']].'已存在',$v['name'].':'.$params[$v['name']].'已存在',2);
						}
					}
				}
				if ($params[$v['field']]) {
					switch ($v['form_type']) {
						case 'address':
							$params[$v['field']] = implode(chr(10),json_decode($params[$v['field']],true));
							break;
						case 'datetime':
							$params[$v['field']] = $params[$v['field']];
							break;
						case 'box':
							eval('$field_type = '.$v['setting'].';');
							if($field_type['type'] == 'checkbox'){
								$a = array_filter(json_decode($params[$v['field']],true));
								$params[$v['field']] = !empty($a) ? implode(chr(10),$a) : '';
							}
							break;
						default : break;
					}
				}
			}
			if ($d_contacts->create($params) && $d_contacts_data->create($params)!==false) {
				$d_contacts->create_time = time();
				$d_contacts->update_time = time();
				$d_contacts->creator_role_id = session('role_id');
				if ($contacts_id = $d_contacts->add()) {
					if ($contacts_id) {
						$rContactsCustomer['contacts_id'] =  $contacts_id;
						$rContactsCustomer['customer_id'] =  $customer_id;
						if (M('RContactsCustomer') ->add($rContactsCustomer)) {
							$d_contacts_data->contacts_id = $contacts_id;
							if ($d_contacts_data->add()) {
								$this->ajaxReturn('','添加成功！',1);
							} else {
								$this->ajaxReturn('','添加失败！',0);
							}
						}
					}
				} else {
					$this->ajaxReturn('','添加失败！',0);
				}
			} else {
				$this->ajaxReturn('','添加失败'.$d_contacts->getError().$d_contacts_data->getError(),0);
			}
		}
	}

	/**
	 * 联系人修改
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			$params = $_POST;
			$d_contacts = D('ContactsView');
			$rContactsCustomer = M('RContactsCustomer');

			$contacts_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (empty($contacts_id)) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$params['contacts_id'] = $contacts_id;

			//检查权限
			$customer_id = M('RContactsCustomer')->where('contacts_id = %d', $contacts_id)->getField('customer_id');
			
			//判断联系人所在客户是否在客户池，如果在则不判断权限
			$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
			$m_config = M('Config');
			$outdays = $m_config->where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
			if ($openrecycle == 2) {
				if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
					if (!in_array($customer_info['owner_role_id'], $this->_permissionRes)) {
						$this->ajaxReturn('','您没有此权利！',-2);
					}
				}
			}

			$contacts_info = $d_contacts->where(array('contacts_id'=>$contacts_id))->find();
			if (empty($contacts_info)){
				$this->ajaxReturn('','记录不存在或已被删除',0);
			} 
			$d_contacts = D('Contacts');
			$d_contacts_data = D('ContactsData');

			$field_list = M('Fields')->where('model = "contacts"')->order('order_id')->select();
			foreach ($field_list as $v){
				switch ($v['form_type']) {
					case 'address':
						$params[$v['field']] = implode(chr(10),json_decode($params[$v['field']]));
					break;
					case 'datetime':
						$params[$v['field']] = $params[$v['field']];
					break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$params[$v['field']] = implode(chr(10),json_decode($params[$v['field']]));
						}
					break;
					case 'editor':
						unset($params[$v['field']]);
					break;
				}
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if ($params[$v['field']] == '') {
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('contacts',$v['field'],$params[$v['name']],$contacts_id);
						if ($res == 1) {
							$this->ajaxReturn('',$v['name'].':'.$params[$v['name']].'已存在',0);
						}
					}
				}
			}
			if ($d_contacts->create($params)) {
				if ($d_contacts_data->create($params) !== false) {
					$d_contacts->update_time = time();
					if (!empty($params['customer_id'])) {
						if (empty($customer_id)) {
							$data['contacts_id'] = $_POST['contacts_id'];
							$data['customer_id'] = $params['customer_id'];
							$rContactsCustomer ->where('contacts_id = %d', $_POST['contacts_id'])->delete();
							$rContactsCustomer -> add($data);
						} elseif ($params['customer_id'] != $customer_id) {
							M('RContactsCustomer') -> where('contacts_id = %d' , $_POST['contacts_id']) -> setField('customer_id',$params['customer_id']);
						}
					} else {
						$this->ajaxReturn('','所属客户不能为空！',0);
					}
					$a = $d_contacts->where('contacts_id= %d',$contacts_id)->save();
					$contacts_field = M('Fields')->where('model = "%s" and is_main = 0','contacts')->find();
					if ($contacts_field) {
						$b = $d_contacts_data->where('contacts_id= %d', $contacts_info['contacts_id'])->save();
					} else {
						$b = 0;
					}
					if ($a !== false && $b !== false) {
						$this->ajaxReturn('','修改成功',1);
					} else {
						$this->ajaxReturn('','修改失败',0);
					}
				} else {
					$this->ajaxReturn('','修改失败,'.$d_contacts_data->getError(),0);
				}
			} else {
				$this->ajaxReturn('','修改失败,'.$d_contacts->getError(),0);
			}
		}
	}

	/**
	 * 联系人详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$contacts_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$rContactsCustomer = M('RContactsCustomer');
			$d_contacts = D('ContactsView');
			$m_customer = M('Customer');
			$contacts_info = $d_contacts->where('contacts.contacts_id = %d' , $contacts_id)->find();
			if (!$contacts_info || $contacts_info['is_deleted'] == 1) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if (empty($contacts_id)) {
				$this->ajaxReturn('参数错误','参数错误',2);
			} else {
				//检查权限
				$customer_id = $rContactsCustomer->where('contacts_id = %d', $contacts_id)->getField('customer_id');
				
				//判断联系人所在客户是否在客户池，如果在则不判断权限
				$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
				$m_config = M('Config');
				$outdays = $m_config->where('name="customer_outdays"')->getField('value');
				$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

				$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
				$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
				$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
				$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
				if ($openrecycle == 2) {
					if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
						if (!in_array($customer_info['owner_role_id'], $this->_permissionRes)) {
							$this->ajaxReturn('','您没有此权利！',-2);
						}
					}
				}

				//联系人二维码
				$qrcode = 'index.php?m=contacts&a=qrcode&contacts_id='.$contacts_id;
				//自定义字段显示
				$field_list = M('Fields')->where('model = "contacts"')->order('order_id')->select();
				foreach ($field_list as $k=>$v) {
					$field = trim($v['field']);
					$data_list[$k]['field'] = $field;
					$data_list[$k]['name'] = trim($v['name']);
					$data_a = trim($contacts_info[$v['field']]);
					if ($v['setting']) { 
						//将内容为数组的字符串格式转换为数组格式
						eval("\$setting = ".$v['setting'].'; ');
						$data_list[$k]['form_type'] = $setting['type'] == 'checkbox' ? 'checkbox' : 'select';
					} else {
						$data_list[$k]['form_type'] = $v['form_type'];
					}
					if ($v['form_type'] == 'address') {
						$address_array = str_replace(chr(10),' ',$data_a);
						$data_list[$k]['val'] = $address_array;
					} else {
						$data_list[$k]['val'] = $data_a;
					}
					if ($field == 'customer_id') {
						unset($data_list[$k]['val']);
						$customer_name = $m_customer->where(array('customer_id'=>$customer_id))->getField('name');
						$data_list[$k]['val'] = $customer_name;
						$data_list[$k]['type'] = 3;
						$data_list[$k]['id'] = $customer_id;
						$data_list[$k]['form_type'] = 'customer';
					} else {
						$data_list[$k]['type'] = 0;
						$data_list[$k]['id'] = '';
					}
				}
				//获取权限
				$data['permission'] = permissionlist('contacts',$customer_info['owner_role_id']);
				$data['customer_id'] = $customer_id;
				$data['qrcode'] = $qrcode;
				$data['data'] = $data_list;
				$data['info'] = 'success';
				$data['status'] = 1;
				$this->ajaxReturn($data,'JSON');
			}
		}
	}

	/**
	 * 联系人删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		if ($this->isPost()) {
			$contacts_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if ($contacts_id == '' || $contacts_id == null) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_contacts = M('Contacts');
			//检查权限
			$customer_id = M('RContactsCustomer')->where('contacts_id = %d', $contacts_id)->getField('customer_id');
			
			//判断联系人所在客户是否在客户池，如果在则不判断权限
			$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
			$m_config = M('Config');
			$outdays = $m_config->where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
			if ($openrecycle == 2) {
				if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
					if (!in_array($customer_info['owner_role_id'], $this->_permissionRes)) {
						$this->ajaxReturn('','您没有此权利！',-2);
					}
				}
			}

			if ($m_contacts->where(array('contacts_id'=>$contacts_id))->delete()) {
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败',0);
			}
		}
	}

	/**
	 * 联系人动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic() {
		if ($this->isPost()) {
			$contacts_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$contacts_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_contacts = M('Contacts');
			$m_customer = M('Customer');
			$rContactsCustomer = M('rContactsCustomer');

			$customer_id = $rContactsCustomer->where('contacts_id = %d', $contacts_id)->getField('customer_id');
				
			//判断联系人所在客户是否在客户池，如果在则不判断权限
			$customer_info = M('Customer')->where(array('customer_id'=>$customer_id))->find();
			$m_config = M('Config');
			$outdays = $m_config->where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config->where('name="openrecycle"')->getField('value');
			if ($openrecycle == 2) {
				if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
					if (!in_array($customer_info['owner_role_id'], getPerByAction('contacts','view'))) {
						$this->ajaxReturn('','您没有此权利！',-2);
					}
				}
			}
			$contacts_info = $m_contacts->where(array('contacts_id'=>$contacts_id))->field('name,role,telephone')->find();
			$contacts_info['customer_name'] = $customer_info['name'];
			$contacts_info['customer_id'] = $customer_id;

			$data['data'] = $contacts_info;
			$data['info'] = 'success'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');
		}
	}

	public function object_array($array){
		if(is_object($array)){
			$array = (array)$array;
		}
		if(is_array($array)){
			foreach($array as $key=>$value){
			  $array[$key] = object_array($value);
			}
		}
		return $array;
	}

	/**
	 * 商机关联联系人
	 * @param 
	 * @author 
	 * @return 
	 */
	public function relToBusiness(){
		if ($this->isPost()) {
			$act_n = intval($_POST['act_n']);
			$business_id = intval($_POST['business_id']);
			$contacts_id = $_POST['contacts_id'];
			if (!$business_id || !$contacts_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_r_business_contacts = M('RBusinessContacts');
			//关联商机
			if ($act_n == 1) {
				$contacts_id_array = explode(',',$contacts_id);
				if (count($contacts_id_array) > 1) {
					$data = array();
					foreach ($contacts_id_array as $k => $v) {
						$data['business_id'] = $business_id;
						$data['contacts_id'] = $v;
						$is_rel = $m_r_business_contacts->where('business_id = %d and contacts_id = %d',$business_id,$v)->find();
						if ($is_rel) {
							continue;
						}
						$ret = $m_r_business_contacts->add($data);
						if (!$ret) {
							$this->ajaxReturn('','批量绑定出错！',0);
						}
					}
					$this->ajaxReturn('','绑定成功！',1);
				} else {
					$data = array();
					$data['business_id'] = $business_id;
					$data['contacts_id'] = $contacts_id;
					$is_rel = $m_r_business_contacts->where('business_id = %d and contacts_id = %d',$business_id,$contacts_id)->find();
					if ($is_rel) {
						$this->ajaxReturn('','联系人已绑定该商机！',0);
					}
					$ret = $m_r_business_contacts->add($data);
					if ($ret) {
						$this->ajaxReturn('','绑定成功！',1);
					} else {
						$this->ajaxReturn('','绑定失败！',0);
					}
				}				
			} else {//解绑关联商机
				$ret = $m_r_business_contacts->where('business_id = %d and contacts_id = %d',$business_id,$contacts_id)->delete();
				if ($ret) {
					$this->ajaxReturn('','解绑成功！',1);
				} else {
					$this->ajaxReturn('','解绑失败！',0);
				}
			}
		}
	}
}