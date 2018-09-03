<?php
/**
 *客户相关
 **/
class CustomerVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('receive')
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
	 * 客户列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			//列表返回权限（添加）
			$permission_list = apppermission(MODULE_NAME,ACTION_NAME);
			if ($permission_list) {
				$customer_data['permission_list'] = $permission_list;
			} else {
				$customer_data['permission_list'] = array();
			}
			$d_v_customer = D('CustomerView');
			$m_contract = M('Contract');
			$by = $_POST['by'] ? trim($_POST['by']) : '';
			if(trim($_GET['content']) != 'resource'){
				if (!$by) {
					//客户列表默认场景
					$m_scene_default = M('SceneDefault');
					$m_scene = M('Scene');
					$customer_default_scene = $m_scene_default->where(array('role_id'=>session('role_id'),'module'=>'customer'))->getField('scene_id');
					if ($customer_default_info) {
						if ($customer_default_info['type'] == 1) {
							$by = $customer_default_info['by'];
						} else {
							$scene_id = $customer_default_info['id'];
						}
					} else {
						$by = 'all';
					}
				}
			}

			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME,true);
			$m_config = M('Config');
			$outdays = $m_config -> where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
			$c_outdays = $m_config -> where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config -> where('name="openrecycle"')->getField('value');

			$where = array();
			$params = array();
			$order = "top.set_top desc,top.top_time desc,customer.update_time desc,customer.customer_id asc";

			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'top.set_top desc,top.top_time desc,customer.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',customer.customer_id asc';
			}
			$m_share =  M('customerShare');
			switch ($by) {
				case 'todaycontact' :
					$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt',strtotime(date('Y-m-d', time()))), 'and');
					$where['owner_role_id'] = session('role_id');
					break;
				case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
				case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d')) - (date('N', time()) - 1) * 86400)); break;
				case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
				case 'add' : $order = 'customer.create_time desc,customer.customer_id asc'; break;
				case 'update' : $order = 'customer.update_time desc,customer.customer_id asc'; break;
				case 'sub' : $where['owner_role_id'] = array('in',$below_ids); break;
				case 'me' : $where['owner_role_id'] = session('role_id'); break;
				case 'share' : 
						//查询分享给我的
						$sharing_id = session('role_id');
						$customerid = $m_share->where('by_sharing_id =%d',$sharing_id)->getField('customer_id',true);
						$where['customer_id'] = array('in',$customerid);break;
				case 'myshare' : 
						//查询我分享的
						$share_customer_ids = $m_share->where('share_role_id =%d',session('role_id'))->getField('customer_id',true);
						$where['customer_id'] = array('in',$share_customer_ids);break;
				default :
					if($this->_get('content') == 'resource'){
			            $where['_string'] = "customer.owner_role_id=0 or (customer.update_time < $outdate and customer.is_locked = 0) or (customer.get_time < $contract_outdays and customer.is_locked = 0)";
			        }else{
						$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
			        }
				break;
			}
			if (!isset($where['owner_role_id']) && $this->_get('content') !== 'resource') {
				if($by != 'deleted' && $by != 'share'){
					$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
				}
			}
			if ($this->_get('content') != 'resource' ) {
				if($openrecycle == 2){
					$where['_string'] = '(customer.update_time > '.$outdate.' AND get_time > '.$contract_outdays.') OR is_locked = 1';
				}
			}
			//普通查询
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if ($this->_request('state')){
					$state = $this->_request('state', 'trim');
					$address_where[] = '%'.$state.'%';
					if($this->_request('city')){
						$city = $this->_request('city', 'trim');
						$address_where[] = '%'.$city.'%';
						if($this->_request('area')){
							$area = $this->_request('area', 'trim');
							$address_where[] = '%'.$this->_request('area', 'trim').'%';
						}
					}
					if($search) $address_where[] = '%'.$search.'%';
					if($condition == 'not_contain'){
						$where[$field] = array('notlike', $address_where, 'OR');
					}else{
						$where[$field] = array('like', $address_where, 'AND');
					}
				}else{
					$field_date = M('Fields')->where('is_main=1 and (model="" or model="customer") and form_type="datetime"')->select();
					foreach($field_date as $v){
						if($field == $v['field'] || $field == 'customer.create_time' || $field == 'customer.update_time') $search = is_numeric($search)?$search:strtotime($search);
					}
					if($field == 'name'){
						//$where['name'] = array('like',$search);
						$c_where['_string'] = 'name like "%'.$search.'%" or telephone like "%'.$search.'%"';
						$contacts_ids = M('contacts')->where($c_where)->getField('contacts_id',true);
						$contacts_str = implode(',',$contacts_ids);
						if($contacts_str){
							$where['_string'] = 'customer.name like "%'.$search.'%" or customer.contacts_id in ('.$contacts_str.')';
						}else{
							$where['_string'] = 'customer.name like "%'.$search.'%"';
						}
						
					}else{
						switch ($condition) {
							case "is" : $where[$field] = array('eq',$search);break;
							case "isnot" :  $where[$field] = array('neq',$search);break;
							case "contains" :  $where[$field] = array('like','%'.$search.'%');break;
							case "not_contain" :  $where[$field] = array('notlike','%'.$search.'%');break;
							case "start_with" :  $where[$field] = array('like',$search.'%');break;
							case "not_start_with" :  $where[$field] = array('notlike',$search.'%');break;
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
					$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$search);
				}
				//过滤不在权限范围内的role_id
				if(trim($_REQUEST['field']) == 'owner_role_id'){
					if(!in_array(trim($search),$this->_permissionRes)){
						$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					}
				}
			}

			//多选类型字段
			$check_field_arr = M('Fields')->where(array('model'=>'customer','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
			//高级搜索
			if(!$_POST['field']){
				$no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','scene_id','order_field','order_type','token');
				foreach($_POST as $k=>$v){
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
							} elseif (($v['start'] != '' || $v['end'] != '')) {
								if($k == 'create_time'){
									$k = 'customer.create_time';
								}elseif($k == 'update_time'){
									$k = 'customer.update_time';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
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
						}else{
							if(!empty($v)){
								$where[$k] = field($v);
							}
					    }
					}
				}
				//过滤不在权限范围内的role_id
				if(isset($where['owner_role_id'])){
					if(is_array($where['owner_role_id']) && $where['owner_role_id']['1'] && !in_array(intval($where['owner_role_id']['1']),$this->_permissionRes)){
						$where['customer.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					}
				}
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			//自定义场景
			if($p == 1 && $_POST['search'] == '' && trim($_GET['content']) != 'resource'){
				$m_scene = M('Scene');
				$scene_where = array();
				$scene_where['role_id']  = session('role_id');
				$scene_where['type']  = 1;
				$scene_where['_logic'] = 'or';
				$map_scene['_complex'] = $scene_where;
				$map_scene['module'] = 'customer';
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
			if ($scene_id) {
				$scene_info = $m_scene->where(array('id'=>$scene_id,'role_id'=>session('role_id')))->find();
				if (!$scene_info) {
					$this->ajaxReturn('','参数错误！',0);
				}
				eval('$scene_info_data = '.$scene_info["data"].';');
				if(is_array($scene_info_data)){
					foreach ($scene_info_data as $k=>$v) {
						if ($v['state']){
							$address_where[] = '%'.$v['state'].'%';
							if($v['city']){
								$address_where[] = '%'.$v['city'].'%';
								if($v['area']){
									$address_where[] = '%'.$v['area'].'%';
								}
							}
							if($v['condition'] == 'not_contain'){
								$where[$k] = array('notlike', $address_where, 'OR');
							}else{
								$where[$k] = array('like', $address_where, 'AND');
							}
						} elseif (($v['start'] != '' || $v['end'] != '')) {
							if($k == 'create_time'){
								$k = 'customer.create_time';
							}elseif($k == 'update_time'){
								$k = 'customer.update_time';
							}
							//时间段查询
							if ($v['start'] && $v['end']) {
								$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
							} elseif ($v['start']) {
								$where[$k] = array('egt',strtotime($v['start']));
							} else {
								$where[$k] = array('elt',strtotime($v['end'])+86399);
							} 
						} elseif ($v['value'] != ''){
							if($k == 'status_id'){
								$business_map['status_id'] = $v['value'];
							}else{
								$where[$k] = field($v['value'],$v['condition']);
							}
						}
					}
				}
			}

			//高级排序（暂只支持时间、数字类型字段）
			$order_fields = M('Fields')->where(array('model'=>'customer','form_type'=>array('in',array('number','datetime'))))->field('field,name')->select();
			$this->order_fields = $order_fields;
			$list = $d_v_customer->where($where)->order($order)->page($p.',10')->field('name,customer_id,owner_role_id,create_time')->select();
			$count = $d_v_customer->where($where)->count();
			
			$m_user = M('User');
			foreach($list as $k=>$v){
				$list[$k]['owner_name'] = $m_user->where('role_id = %d',$v['owner_role_id'])->getField('full_name');
				//获取操作权限
				if($_GET['content'] == 'resource'){
					$list[$k]['permission'] = array("view"=>1);
				}else{
					$list[$k]['permission'] = permissionlist(MODULE_NAME,$v['owner_role_id']);
				}
			}
			$list = empty($list) ? array() : $list;
			$page = ceil($count/10);

			//获取查询条件信息
			if($p == 1 && $_POST['search'] == ''){
				$where_field = array();
				$where_field['model'] = array('in',array('','customer'));
				$where_field['is_main'] = '1';
				$where_field['field'] = array('not in',array('customer_owner_id','delete_role_id','is_deleted','delete_time'));
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
				//追加首要联系人姓名、首要联系人电话
				$contacts_field_list = array(
					'0'=>array('field'=>'contacts_name','form_type'=>'text','input_tips'=>'','name'=>'首要联系人姓名','setting'=>''),
					'1'=>array('field'=>'contacts_telephone','form_type'=>'text','input_tips'=>'','name'=>'首要联系人电话','setting'=>'')
				);
				$fields_list = array_merge($fields_list,$contacts_field_list);
			}

			if($p == 1 && $_POST['search'] == ''){
				$customer_data['fields_list'] = $fields_list ? $fields_list : array();
				//场景信息
				$customer_data['scene_list'] = $scene_list ? $scene_list : array();
			}else{
				$customer_data['fields_list'] = array();
				$customer_data['scene_list'] = array();
			}

			$customer_data['list'] = $list;
			$customer_data['page'] = $page;
			$customer_data['info'] = 'success';
			$customer_data['status'] = 1;
			$this->ajaxReturn($customer_data,'JSON');
		}
	}

	/**
	 * 客户添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add() {		
		if ($this->isPost()) {
			$d_customer = D('Customer');
			$d_customer_data = D('CustomerData');

			$params = $_POST;
			if (!is_array($params)) {
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			//线索转客户
			$leads_id = intval($params['leads_id']);
			
			$field_list = M('Fields')->where('model = "customer" and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v) {
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if ($params[$v['field']] == '') {
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('customer',$v['field'],$params[$v['field']]);
						if ($res) {
							$this->ajaxReturn('',$v['name'].':'.$params[$v['name']].'已存在',0);
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
			if ($d_customer->create($params) && $d_customer_data->create($params) !== false) {
				if ($params['con_name']) {
					$contacts = array();
					if ($params['con_name']) $contacts['name'] = $params['con_name'];
					if ($params['owner_role_id']) $contacts['owner_role_id'] = $params['owner_role_id'];
					if ($params['saltname']) $contacts['saltname'] = $params['saltname'];
					if ($params['con_email']) $contacts['email'] = $params['con_email'];
					if ($params['con_post']) $contacts['post'] = $params['con_post'];
					if ($params['con_qq']) $contacts['qq_no'] = $params['con_qq'];
					if ($params['con_telephone']) $contacts['telephone'] = $params['con_telephone'];
					if ($params['con_description']) $contacts['description'] = $params['con_description'];
					if (!empty($contacts)) {
						$contacts['creator_role_id'] = session('role_id');
						$contacts['create_time'] = time();
						$contacts['update_time'] = time();
						$contacts_id = M('Contacts')->add($contacts);
					}
				}
				$d_customer->owner_role_id = session('role_id');
                $d_customer->create_time = time();
                $d_customer->update_time = time();
                $d_customer->get_time = time();
                if ($contacts_id) {
                	$d_customer->contacts_id = $contacts_id;
                }
                $d_customer->creator_role_id = session('role_id');
                if (!$customer_id = $d_customer->add()) {
                    $this->ajaxReturn('','添加失败！',0);
                }
                $d_customer_data->customer_id = $customer_id;
                $d_customer_data->add();
				//线索转换客户
				if ($leads_id) {
					$r_module = array(
						array('key'=>'log_id','r1'=>'RCustomerLog','r2'=>'RLeadsLog'), 
						array('key'=>'file_id','r1'=>'RCustomerFile','r2'=>'RFileLeads'),
						array('key'=>'event_id','r1'=>'RCustomerEvent','r2'=>'REventLeads'),
						array('key'=>'task_id','r1'=>'RCustomerTask','r2'=>'RLeadsTask')
					);
					foreach ($r_module as $key=>$value) {
						$key_id_array = M($value['r2'])->where('leads_id = %d', $leads_id)->getField($value['key'],true);
						$r1 = M($value['r1']);
						$data['customer_id'] = $customer_id;
						foreach($key_id_array as $k=>$v){
							$data[$value['key']] = $v;
							$r1->add($data);
						}
					}
					$leads_data['is_transformed'] = 1;
					$leads_data['update_time'] = time();
					$leads_data['customer_id'] = $customer_id;
					$leads_data['contacts_id'] = $contacts_id;
					$leads_data['transform_role_id'] = session('role_id');
					M('Leads')->where('leads_id = %d', $leads_id)->save($leads_data);
				}
                //记录操作记录
                actionLog($customer_id);

                //客户联系人绑定
                if ($contacts_id && $customer_id) {
                    $rcc['contacts_id'] = $contacts_id;
                    $rcc['customer_id'] = $customer_id;
                    M('RContactsCustomer')->add($rcc);
                }
				$this->ajaxReturn('','添加成功！',1);
			}else{
				$this->ajaxReturn('','添加失败'.$d_customer->getError().$d_customer_data->getError(),0);
            }
		}else{
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 客户编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit() {
		if ($this->isPost()) {
			$params = $_POST;
			if (!is_array($params)) {
				$this->ajaxReturn('','非法的数据格式!',0);
			}

			$customer_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (!$customer_id) {
				$this->ajaxReturn('','参数错误!',0);
			}
			$params['customer_id'] = $customer_id;
			$customer_info = D('CustomerView')->where('customer.customer_id = %d', $customer_id)->find();
			if (!$customer_info) {
				$this->ajaxReturn('','记录不存在或已被删除',0);
			} elseif (!in_array($customer_info['owner_role_id'],getPerByAction('customer','edit')) && !session('?admin')) {
				$this->ajaxReturn('','您没有此权利!',-2);
			}

			$field_list = M('Fields')->where('model = "customer"')->order('order_id')->select();
			$d_customer = D('Customer');
			$d_customer_data = D('CustomerData');
			foreach ($field_list as $v) {
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
						if($params[$v['field']] == ''){
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('customer',$v['field'],$params[$v['name']],$customer_id);
						if($res == 1){
							$this->ajaxReturn('',$v['name'].':'.$params[$v['name']].'已存在',0);
						}
					}
				}
			}
			if ($d_customer->create($params) && $d_customer_data->create($params) !== false) {
				$d_customer->update_time = time();
				$a = $d_customer->where('customer_id=' . $customer_info['customer_id'])->save();
				$b = $d_customer_data->where('customer_id=' . $customer_info['customer_id'])->save();
				if ($a !== false && $b !== false) {
					//操作记录
					actionLog($customer_info['customer_id']);
					$this->ajaxReturn('','修改成功!',1);
				} else {
					$this->ajaxReturn('','修改失败！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败,'.$d_customer->getError().$d_customer_data->getError(),0);
			}
		} else {
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 客户详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view() {
		if ($this->isPost()) {
			$m_user = M('User');
			$customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$customer_info = D('CustomerView')->where('customer.customer_id = %d', $customer_id)->find();
			if(!$customer_info || $customer_info['is_deleted'] == 1){
				$this->ajaxReturn('客户不存在或已删除!','客户不存在或已删除!',2);
			}
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
						$this->ajaxReturn('','您没有此权利!',-2);
					}
				}
			}

			$data_list = array();
			//查询固定信息
			//负责人
			$customer_owner = $m_user->where('role_id = %d', $customer_info['owner_role_id'])->field('full_name,role_id')->find();
			$data_list[0]['field'] = 'owner_role_id';
			$data_list[0]['name'] = '负责人';
			$data_list[0]['form_type'] = 'user';
			$data_list[0]['val'] = empty($customer_owner['full_name']) ? '' : $customer_owner['full_name'];
			$data_list[0]['id'] = empty($customer_owner['role_id']) ? '' : $customer_owner['role_id'];
			$data_list[0]['type'] = 1;
			//创建人
			$customer_create = $m_user->where('role_id = %d', $customer_info['creator_role_id'])->field('full_name,role_id')->find();
			$data_list[1]['field'] = 'creator_role_id';
			$data_list[1]['name'] = '创建人';
			$data_list[1]['form_type'] = 'user';
			$data_list[1]['val'] = $customer_create['full_name'];
			$data_list[1]['id'] = $customer_create['role_id'];
			$data_list[1]['type'] = 1;
			//获取首要联系人字段信息
			$contacts_id = $customer_info['contacts_id'];
			$contacts_info = M('Contacts')->where('contacts_id = %d',$contacts_id)->find();
			$data_list[2]['field'] = 'contacts_id';
			$data_list[2]['name'] = '首要联系人';
			$data_list[2]['form_type'] = 'contacts';
			if (!$contacts_info) {
				$data_list[2]['val'] = '';
				$data_list[2]['id'] = '';
			} else {
				$data_list[2]['val'] = $contacts_info['name'];
				$data_list[2]['id'] = $contacts_info['contacts_id'];
			}
			$data_list[2]['type'] = 5;
			//取得字段列表
			$where = array();
			$where['model'] = 'customer';
			$field_list = M('Fields')->where($where)->order('order_id')->select();
			$i = 3;
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
				$data_a = trim($customer_info[$v['field']]);
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
			//获取权限
			if (session('?admin')) {
				$data['permission'] = array('edit'=>1,'view'=>1,'delete'=>1);
			} else {
				//判断是否客户池,客户池只给看权限
				if ($openrecycle == 2 ){
					if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
						$data['permission'] = permissionlist('customer',$customer_info['owner_role_id']);
					} else {
						$data['permission'] = array('view'=>1);
					}
				} else {
					$data['permission'] = permissionlist('customer',$customer_info['owner_role_id']);
				}
			}
			$data['data'] = $data_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('',"非法请求",0);
		}
	}

	/**
	 * 客户删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		if ($this->isPost()) {
			$m_customer = M('Customer');
	        $m_business = M('Business');
	        $m_contract = M('Contract');
			$r_module = array('Log'=>'RCustomerLog', 'File'=>'RCustomerFile','RContactsCustomer');
	        $where = array();
	        $where_resource = array();
			$customer_id = $_POST['id'] ? intval($_POST['id']) : '';
			if (empty($customer_id)) {
				$this->ajaxReturn('','参数错误！',0);
			} else {
				if (!session('?admin')) {
					$where['owner_role_id'] = array('in',$this->_permissionRes);

					$where_resource['owner_role_id'] = array('in',$this->_permissionRes);
					//判断是否客户池（只有管理员能删除）
					$m_config = M('Config');
					$outdays = $m_config->where('name="customer_outdays"')->getField('value');
					$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
					$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
					$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
					$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
					$openrecycle = $m_config -> where('name="openrecycle"')->getField('value');

					if ($openrecycle == 2) {
						$where_resource['_string'] = '(update_time > '.$outdate.' AND get_time > '.$contract_outdays.') OR is_locked = 1';
					} else {
						$where_resource['_string'] = "owner_role_id=0 or (update_time < ".$outdate." and is_locked = 0) or (get_time < ".$contract_outdays." and is_locked = 0)";
					}
					$where_resource['customer_id'] = $customer_id;
					$resource_info = M('Customer')->where($where_resource)->find();
					if (!$resource_info) {
						$this->ajaxReturn('','您没有此权限！',-2);
					}
				}

                //判断客户下是否有非空商机（即有商机编号），如果有则不能删除
                $business_info = $m_business->where(array('customer_id'=>$customer_id,'code'=>array('neq','')))->find(); 
                if ($business_info) {
                     $this->ajaxReturn('','客户删除失败，请先删除客户下相关商机！',0);
                }
                if ($m_customer->where(array('customer_id'=>$customer_id))->delete()) {
                    //删除附表信息
                    M('CustomerData')->where(array('customer_id'=>$customer_id))->delete();
                    //记录操作记录
                    actionLog($customer_id);
                    //删除相关信息
                    foreach ($r_module as $key2=>$value2) {
                        $module_ids = M($value2)->where('customer_id = %d', $customer_id)->getField($key2 . '_id', true);
                        M($value2)->where('customer_id = %d', $customer_id) -> delete();
                        if (!is_int($key2)) {
                            M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
                        }
                    }
                    //删除客户关联空商机
                    $m_business->where(array('customer_id'=>$customer_id))->delete();
                    $this->ajaxReturn('','删除成功！',1);
                } else {
                	$this->ajaxReturn('','删除失败，请联系管理员！',0);
                }
			}
		}
	}

	/**
	 * 客户动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic() {
		if ($this->isPost()) {
			$customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$customer_info = M('Customer')->where('customer_id = %d', $customer_id)->field('name,owner_role_id,update_time,get_time,is_locked,contacts_id')->find();
			if (!$customer_info) {
				$this->ajaxReturn('','客户不存在或已删除！',2);
			}
			$m_config = M('Config');
			$outdays = $m_config -> where('name="customer_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
			$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
			$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
			$openrecycle = $m_config->where('name="openrecycle"')->getField('value');

			if ($openrecycle == 2) {
				if ($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)) {
					if (!in_array($customer_info['owner_role_id'], getPerByAction('customer','view'))) {
						$this->ajaxReturn('','您没有此权利!',-2);
					}
				}
			}
			$data = array();
			$data = $customer_info;
			//负责人
			$data['owner'] = M('User')->where(array('role_id'=>$customer_info['owner_role_id']))->field('full_name,role_id')->find();

			//联系电话（首要联系人或联系人中第一个）
			if ($customer_info['contacts_id']) {
				$contacts_id = $customer_info['contacts_id'];
			} else {
				$contacts_id = M('rContactsCustomer')->where('customer_id = %d', $customer_id)->order('id desc')->getField('contacts_id');
			}
			$contacts_info = M('Contacts')->where(array('contacts_id'=>$contacts_id))->field('telephone,name')->find();

			$data['contacts_telephone'] = $contacts_info['telephone'] ? $contacts_info['telephone'] : '';
			$data['contacts_name'] = $contacts_info['name'] ? $contacts_info['name'] : '';
			
			//是否关注
			$focus_id = M('CustomerFocus')->where(array('customer_id'=>$customer_id,'user_id'=>session('user_id')))->getField('focus_id');
			if (!empty($focus_id)) {
				$data['focus'] = 1; //已关注
			} else {
				$data['focus'] = 0;
			}
			$this->ajaxReturn($data,'success',1);
		}
	}

	/**
	 * 客户领取、分配
	 * @param type = 1领取,2分配
	 * @author 
	 * @return 
	 */
	public function receive(){
		if ($this->isPost()) {
			$type = $_POST['type'] ? intval($_POST['type']) : '';
			$m_customer = M('Customer');
			$m_config = M('Config');
			$m_customer_record = M('CustomerRecord');

			$owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
			$data['owner_role_id'] = $owner_role_id;
			$data['update_time'] = time();
			$data['get_time'] = time();
			if (!$type) {
				$ths->ajaxReturn('','参数错误！',0);
			}
			$customer_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (!$customer_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			//判断是否符合领取条件
			$opennum = $m_config->where(array('name'=>'opennum'))->getField('value');
			if ($opennum) {
				$outdays = $m_config->where('name="customer_outdays"')->getField('value');
				$outdate = empty($outdays) ? time() : time()-86400*$outdays;

				$c_outdays = $m_config -> where('name="contract_outdays"')->getField('value');
				$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
				$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
				$openrecycle = $m_config -> where('name="openrecycle"')->getField('value');

				if ($openrecycle == 2) {
					$c_where['_string'] = '(customer.update_time > '.$outdate.' AND get_time > '.$contract_outdays.') OR is_locked = 1';
				}
				$c_where['owner_role_id'] = session('role_id');
				$c_where['customer_status'] = '意向客户';
				$customer_count = M('Customer')->where($c_where)->count();
				$customer_num = M('User')->where('role_id = %d',session('role_id'))->getField('customer_num');

				if ($customer_count >= $customer_num) {
					if ($type == 1) {
						$this->ajaxReturn('','你的客户数量已超出限制,不能领取！',0);
					} elseif ($type == 2) {
						$this->ajaxReturn('','此负责人的客户数量已超出限制,操作失败！',0);
					}
				}
			}
			if ($type == 1) {
				//领取
				//客户领取周期限制
				$customer_limit_counts = $m_config->where('name = "customer_limit_counts"')->getField('value');
				$customer_record_count = $this->check_customer_limit(session('user_id'), 1);
				if ($customer_record_count < $customer_limit_counts) {
					if ($m_customer->where('customer_id = %d', $customer_id)->save($data)) {
						$info['customer_id'] = $customer_id;
						$info['user_id'] = session('user_id');
						$info['start_time'] = time();
						$info['type'] = 1;
						$m_customer_record->add($info);
						//增加操作记录
						add_record('领取','从客户池领取了此客户！','customer',$customer_id);
						$this->ajaxReturn('',L('GET_THE_SUCCESS'),1);
					} else {
						$this->ajaxReturn('',L('GET_THE_FAILURE'),0);
					}
				} else {
					$this->ajaxReturn('',L('GET_THE_FAILURE_OVER_GET'),0);
				}
			} elseif ($type == 2) {
				//分配
				$user_name = M('User')->where('role_id = %d',$owner_role_id)->getField('full_name');
				if ($m_customer->where(array('customer_id'=>$customer_id))->save($data)) {
					$info = array();
					$info['customer_id'] = $customer_id;
					$info['user_id'] = $owner_role_id;
					$info['start_time'] = time();
					$info['type'] = 2;
					$m_customer_record->add($info);
					//增加操作记录
					add_record('分配','将此客户分配给'.$user_name.'!','customer',$customer_id);
					$customer_name = $m_customer->where(array('customer_id'=>$customer_id))->getField('name');
					//分配发送站内信
					$content = L('THE_CUSTOMER_RESOURCES',array(session('name'),U('customer/view','id='.$customer_id),$customer_name));
					sendMessage($owner_role_id,$content,1);
					$this->ajaxReturn('',L('GET_THE_SUCCESS'),1);
				} else {
					$this->ajaxReturn('','操作失败！',0);
				}
			}
		}
	}

	/**
	 * 检查用户是否符合从客户池领取或被分配的资格
	 * @param @type 1：领取 2：分配
	 * @author 
	 * @return 
	 */
	private function check_customer_limit($user_id, $type){
		$m_config = M('Config');
		$m_customer_record = M('customer_record');
		$customer_limit_condition = $m_config->where('name = "customer_limit_condition"')->getField('value');

		$today_begin = strtotime(date('Y-m-d',time()));
		$today_end = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		$this_week_begin = ($today_begin -((date('w'))-1)*86400);
		$this_week_end = ($today_end+(7-(date('w')==0?7:date('w')))*86400);
		$this_month_begain = strtotime(date('Y-m', time()).'-01 00:00:00');
		$this_month_end = mktime(23,59,59,date('m'),date('t'),date('Y'));

		$condition['user_id'] = $user_id;
		$condition['type'] = $type;
		if ($customer_limit_condition == 'day') {
			$condition['start_time'] = array('between', array($today_begin, $today_end));
		} elseif ($customer_limit_condition == 'week') {
			$condition['start_time'] = array('between', array($this_week_begin, $this_week_end));
		} elseif ($customer_limit_condition == 'month') {
			$condition['start_time'] = array('between', array($this_month_begain, $this_month_end));
		}

		$customer_record = $m_customer_record->where($condition)->count();
		return $customer_record;
	}	
}