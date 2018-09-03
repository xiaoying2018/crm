<?php
/**
 *线索相关
 **/
class LeadsVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('changecustomer','remove')
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
	 * 线索列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			//获取权限
			$permission_list = apppermission(MODULE_NAME,ACTION_NAME);
			if ($permission_list) {
				$data['permission_list'] = $permission_list;
			} else {
				$data['permission_list'] = array();
			}
			// getDateTime('leads');			
			$d_leads = D('LeadsView');
			$by = isset($_POST['by']) ? trim($_POST['by']) : 'all';

			$order = "create_time desc";
			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'leads.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',leads.leads_id asc';
			}
			//权限及线索池
			$below_ids = getPerByAction('leads','index',true);
			$outdays = M('Config') -> where('name="leads_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

			$where = array();
			switch ($by) {
				case 'today' :
					$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt',0), 'and'); 
					break;
				case 'week' : 
					$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time())) + (date('N', time()) - 1) * 86400), array('gt', 0),'and'); 
					break;
				case 'month' : 
					$where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-01', strtotime('+1 month')))), array('gt', 0),'and'); 
					break;
				case 'd7' : 
					$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*6); 
					break;
				case 'd15' : 
					$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*14); 
					break;
				case 'd30' : 
					$where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*29); 
					break;
				case 'add' : $order = 'create_time desc';  break;
				case 'update' : $order = 'update_time desc';  break;
				case 'sub' : $where['owner_role_id'] = array('in',implode(',', $below_ids)); break;
				case 'subcreate' : $where['creator_role_id'] = array('in',implode(',', $below_ids)); break;
				case 'public' :
					unset($where['have_time']);
					$where['_string'] = "leads.owner_role_id=0 or leads.have_time < $outdate";
					break;
				case 'deleted': $where['is_deleted'] = 1;unset($where['have_time']); break;
				case 'transformed' : $where['is_transformed'] = 1; break;
				case 'me' : $where['owner_role_id'] = session('role_id'); break;
				default :
					$where['owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					break;
			}
			if ($by != 'deleted') {
				$where['is_deleted'] = array('neq',1);
			}
			if ($by != 'transformed') {
				$where['is_transformed'] = array('neq',1);
			}
			if ($this->_permissionRes && !isset($where['owner_role_id']) && $by != 'public') {
				if($by != 'deleted') {
					$where['owner_role_id'] = array('in', $this->_permissionRes);
				} else {
					$where['owner_role_id'] = array('in', '0,'.implode(',', $this->_permissionRes));
				}
			}
			$where['have_time'] = array('egt',$outdate);
			if ($params_search) {
				$where[$params_search['field']] = array('like','%'.trim($params_search['val']).'%');
			}
			if ($_REQUEST['name'] != "") {
				$where['name'] = array('like','%'.$_REQUEST['name'].'%');
			}
			
			if (isset($_POST['search'])) {
				$where['name'] = array('like','%'.trim($_POST['search']).'%');
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;

			//多选类型字段
			$check_field_arr = M('Fields')->where(array('model'=>'leads','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
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
							} elseif (($v['start'] != '' || $v['end'] != '')){
								if($k == 'create_time'){
									$k = 'leads.create_time';
								}elseif($k == 'update_time'){
									$k = 'leads.update_time';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
								}
							} elseif ($k =='owner_role_id' || $k =='creator_role_id') {
								if(!empty($v)){
									$where['leads.'.$k] = $v['value'];
								}
							} elseif(($v['value']) != '') {
								if (in_array($k,$check_field_arr)) {
									$where[$k] = field($v['value'],'contains');
								} else {
									$where[$k] = field($v['value'],$v['condition']);
								}
							}
						} else {
							if (!empty($v)) {
								$where[$k] = field($v);
							}
					    }
	                }
	            }
	            //过滤不在权限范围内的role_id
				if(isset($where['leads.owner_role_id'])){
					if(!empty($where['leads.owner_role_id']) && !in_array(intval($where['leads.owner_role_id']),$this->_permissionRes)){
						$where['leads.owner_role_id'] = array('in',implode(',', $this->_permissionRes));
					}
				}
			}

			$list = $d_leads->where($where)->order($order)->page($p.',10')->select();
			$m_user = M('User');
			$new_list = array();
			foreach ($list as $k=>$v) {
				$new_list[$k]['leads_id'] = $v['leads_id'];
				$new_list[$k]['name'] = $v['name'] ? $v['name'] : '查看详情';
				$new_list[$k]['mobile'] = $v['mobile'];
				$new_list[$k]['owner_role_id'] = $v['owner_role_id'];
				$new_list[$k]['create_time'] = $v['create_time'];
				//获取操作权限
				$new_list[$k]['permission'] = permissionlist(MODULE_NAME,$v['owner_role_id']);
			}
			$list = empty($new_list) ? array() : $new_list;
			$count = $d_leads->where($where)->count();

			//自定义场景
			if($p == 1 && $_POST['search'] == ''){
				$m_scene = M('Scene');
				$scene_where = array();
				$scene_where['role_id']  = session('role_id');
				$scene_where['type']  = 1;
				$scene_where['_logic'] = 'or';
				$map_scene['_complex'] = $scene_where;
				$map_scene['module'] = 'leads';
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
				$where_field['model'] = array('in',array('','leads'));
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
			$data['list'] = $list;
			$data['page'] = $page;
			$data['info'] = 'success'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 线索添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$m_leads = D('Leads');
			$m_leads_data = D('LeadsData');

			$params = $_POST;
			if(!is_array($params)){
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			$field_list = M('Fields')->where('model = "leads"  and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v){
				if ($v['is_validate'] == 1) {
					if ($v['is_null'] == 1) {
						if($params[$v['field']] == ''){
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('leads',$v['field'],$params[$v['field']]);
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
			if ($m_leads->create($params)) {
				if ($m_leads_data->create($params) !== false) {
					if ($params['nextstep_time']) {
						$m_leads->nextstep_time = $params['nextstep_time'];
					}
					$m_leads->creator_role_id = session('role_id');
					$m_leads->owner_role_id = session('role_id');
					$m_leads->create_time = time();
					$m_leads->update_time = time();
					$m_leads->have_time = time();
					if ($leads_id = $m_leads->add()) {
						$m_leads_data->leads_id = $leads_id;
						$m_leads_data->add();
						actionLog($leads_id);
						$this->ajaxReturn('','添加成功！',1);
					} else {
						$this->ajaxReturn('','添加失败！',0);
					}
				} else {
					$this->ajaxReturn('','添加失败！'.$m_leads_data->getError(),0);
				}
			} else {
				$this->ajaxReturn('','添加失败！'.$m_leads->getError(),0);
			}
		} else {
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 线索编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->roles == 2) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}
		if ($this->isPost()) {
			$params = $_POST;
			if(!is_array($params)){
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			$leads_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (!$leads_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$leads_info = M('Leads')->where('leads_id = %d',$leads_id)->find();
			if (!$leads_info) {
				$this->ajaxReturn('','线索不存在或已被删除！',0);
			}
			if (!in_array($leads_info['owner_role_id'],getPerByAction('leads','edit'))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			$params['leads_id'] = $leads_id;
			$field_list = M('Fields')->where('model = "leads"')->order('order_id')->select();
			$m_leads = M('Leads');
			$m_leads_data = M('LeadsData');
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
						if ($params[$v['field']] == '') {
							$this->ajaxReturn('',$v['name'].'不能为空',0);
						}
					}
					if ($v['is_unique'] == 1) {
						$res = validate('leads',$v['field'],$params[$v['name']],$leads_id);
						if ($res == 1) {
							$this->ajaxReturn('',$v['name'].':'.$params[$v['name']].'已存在',0);
						}
					}
				}
			}
			if ($m_leads->create($params)) {
				if ($m_leads_data->create($params) !== false) {
					$m_leads->update_time = time();
					$a = $m_leads->where('leads_id= %d',$leads_id)->save();
					$b = $m_leads_data->where('leads_id=%d',$leads_id)->save();
					if ($a && $b!==false) {
						actionLog($leads_id);
						$this->ajaxReturn('','修改成功！',1);
					} else {
						$this->ajaxReturn('','修改失败！',0);
					}
				} else {
					$this->ajaxReturn('','修改失败！'.$m_leads_data->getError(),0);
				}
			} else {
				$this->ajaxReturn('','修改失败！'.$m_leads->getError(),0);
			}
		} else {
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 线索详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$leads_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			$outdays = M('Config') -> where('name="leads_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
			$where['have_time'] = array('egt',$outdate);
			$where['owner_role_id'] = array('neq',0);
			$where['leads_id'] = $leads_id;

			if (!$leads_id) {
				$this->ajaxReturn('','参数错误！',0);
			} elseif ($temp = D('Leads')->where($where)->find()) {
				if(!in_array($temp['owner_role_id'],getPerByAction('leads','view'))){
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}
			$leads = D('LeadsView')->where('leads.leads_id = %d', $leads_id)->find();
			if (!$leads || $leads['is_deleted'] == 1) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			$d_role = D('RoleView');
			$m_user = M('User');
			//查询固定信息

			//负责人
			$leads_owner = $m_user->where('role_id = %d', $leads['owner_role_id'])->field('full_name,role_id')->find();
			$data_list[0]['field'] = 'owner_role_id';
			$data_list[0]['name'] = '负责人';
			$data_list[0]['form_type'] = 'user';
			if ($leads_owner) {
				$data_list[0]['val'] = $leads_owner['full_name'];
				$data_list[0]['id'] = $leads_owner['role_id'];
			} else {
				$data_list[0]['val'] = '';
				$data_list[0]['id'] = '';
			}
			$data_list[0]['type'] = 1;

			//创建人
			$leads_creator = $m_user->where('role_id = %d', $leads['creator_role_id'])->field('full_name,role_id')->find();
			$data_list[1]['field'] = 'creator_role_id';
			$data_list[1]['name'] = '创建人';
			$data_list[1]['form_type'] = 'user';
			$data_list[1]['val'] = $leads_creator['full_name'];
			$data_list[1]['id'] = $leads_creator['role_id'];
			$data_list[1]['type'] = 1;

			//自定义字段
			$field_list = M('Fields')->where('model = "leads"')->order('order_id')->select();
			$i = 2;
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
				$data_a = trim($leads[$field]);
				if ($v['form_type'] == 'address') {
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

			//获取权限(判断是否线索池)
			if ($leads['owner_role_id'] && $leads['have_time'] >= $outdate) {
				$data['permission'] = permissionlist('leads',$leads['owner_role_id']);
			} else {
				$data['permission'] = array('edit'=>1,'view'=>1,'delete'=>1);
			}

			$data['data'] = $data_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('',"非法请求！",0);
		}
	}

	/**
	 * 线索删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete(){
		if ($this->isPost()) {
			$m_leads = M('Leads');
			$m_leads_data = M('LeadsData');
			$r_module = array('Log'=>'RLeadsLog', 'File'=>'RFileLeads', 'Event'=>'REventLeads', 'Task'=>'RLeadsTask');
			$leads_id = $_POST['id'] ? intval($_POST['id']) : '';
			if (!$leads_id) {
				$this->ajaxReturn('',L('HAVE_NOT_CHOOSE_ANY_CONTENT'),0);
			}
			$where = array();
			if (!session('?admin')) {
				$where['owner_role_id'] = array('in',$this->_permissionRes);
				//判断是否属于线索池
				$where_public = array();
				$where_public['owner_role_id'] = array('in',$this->_permissionRes);
				$where_public['leads_id'] = $leads_id;
				$outdays = M('Config') -> where('name="leads_outdays"')->getField('value');
				$outdate = empty($outdays) ? 0 : time()-86400*$outdays;
				$where_public['_string'] = "owner_role_id=0 or have_time < $outdate";
				$public_leads_info = D('LeadsView')->where($where_public)->find();
				if (!$public_leads_info) {
					$this->ajaxReturn('','您没有此权限！',-2);
				}
			}
			
			if (($m_leads->where(array('leads_id'=>$leads_id))->delete()) && ($m_leads_data->where(array('leads_id'=>$leads_id))->delete())) {
				actionLog($leads_id);
				foreach ($r_module as $key2=>$value2) {
					$module_ids = M($value2)->where('leads_id = %d', $leads_id)->getField($key2 . '_id', true);
					M($value2)->where('leads_id = %d', $leads_id) -> delete();
					if(!is_int($key2)){	
						M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
					}
				}
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败，请联系管理员！',0);
			}
		}
	}
	
	/**
	 * 线索动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic() {
		if ($this->isPost()) {
			if ($this->roles == 2) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$leads_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;

			$outdays = M('Config')->where('name="leads_outdays"')->getField('value');
			$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
			$where['have_time'] = array('egt',$outdate);
			$where['owner_role_id'] = array('neq',0);
			$where['leads_id'] = $leads_id;

			$leads_info = M('Leads')->where($where)->field('name,mobile,update_time,owner_role_id,is_transformed')->find();
			if (!$leads_info) {   
				$this->ajaxReturn('','数据不存在，或已删除',0);
			}
			if(!in_array($leads_info['owner_role_id'],getPerByAction('leads','view'))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			$leads_info['owner'] = M('User')->where(array('role_id'=>$leads_info['owner_role_id']))->field('full_name,role_id')->find();
			$data['data'] = $leads_info;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 放入线索池
	 * @param 
	 * @author 
	 * @return 
	 */
	public function remove(){
		if ($this->isPost()) {
			$leads_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			if ($leads_id) {
				$m_leads = M('Leads');
				$leads_info = $m_leads->where(array('leads_id'=>$leads_id))->find();
				if (in_array($leads_info['owner_role_id'],getPerByAction('leads','edit'))) {
					$data = array();
					$data['owner_role_id'] = 0;
					$data['have_time'] = 0;
					if($m_leads->where(array('leads_id'=>$leads_id))->setField($data)){
						$this->ajaxReturn('','放入线索池成功！',1);
					}else{
						$this->ajaxReturn('','放入线索池失败！',0);
					}
				} else {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			} else {
				$this->ajaxReturn('','参数错误！',0);
			}
		}
	}

	/**
	 * 线索转换
	 * @param 
	 * @author 
	 * @return 
	 */
	public function changeCustomer(){
		if ($this->isPost()) {
			$leads_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$m_leads = M('Leads');
			$where = array();
			$where['leads_id'] = $leads_id;
			$where['is_deleted'] = 0;
			$where['is_transformed'] = 0;

			$leads_info = $m_leads->where($where)->find();
			if (!$leads_info) {
				$this->ajaxReturn('','线索数据不存在或已转换',0);
			}
			//权限判断
			if (!in_array($leads_info['owner_role_id'],getPerByAction('leads','edit'))) {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			$m_customer = M('Customer');
			$m_customer_data = M('CustomerData');
			$m_contacts = M('Contacts');
			$m_r_contacts_customer = M('RContactsCustomer');
			$m_r_leads_log = M('RLeadsLog');
			$m_r_customer_log = M('RCustomerLog');
		
			//联系人信息
			$contacts_data = array();
			$contacts_data['name'] =  $leads_info['contacts_name'];
			$contacts_data['telephone'] =  $leads_info['mobile'];
			$contacts_data['qq_no'] =  $leads_info['position'];
			$contacts_data['email'] =  $leads_info['email'];
			$contacts_data['saltname'] =  $leads_info['saltname'];
			$contacts_data['create_time'] =  time();	
			$contacts_id = $m_contacts->add($contacts_data);

			//客户信息
			$owner_role_id = $_POST['role_id'] ? intval($_POST['role_id']) : session('role_id');
			$customer_data = array();
			$customer_data['owner_role_id'] = $owner_role_id;
			$customer_data['creator_role_id'] =  session('role_id');
			$customer_data['name'] =  $leads_info['name'];
			$customer_data['contacts_id'] = $contacts_id;
			$customer_data['address'] =  $leads_info['state'].'/n'.$leads_info['city'].'/n'.$leads_info['area'].'/n'.$leads_info['street'];
			$customer_data['create_time'] = time();
			$customer_data['update_time'] = time();
			$customer_data['nextstep_time'] = $leads_info['nextstep_time'];
			$customer_data['nextstep'] = $leads_info['nextstep'];
			$customer_data['is_locked'] = 1;
			$customer_data['description'] = $leads_info['description'];

			if ($customer_id = $m_customer->add($customer_data)) {
				$m_customer_data->add(array('customer_id'=>$customer_id));
				//客户联系人关系处理
				$con_cus = array();
				$con_cus['contacts_id'] = $contacts_id;
				$con_cus['customer_id'] = $customer_id;
				$m_r_contacts_customer->add($con_cus);

				//线索沟通日志
				$leads_log_ids = $m_r_leads_log->where('leads_id=%d',$leads_id)->getField('log_id',true);
				foreach ($leads_log_ids as $vv) {
					$customer_log['log_id'] = $vv;
					$customer_log['customer_id'] = $customer_id;
					$customer_logs[] = $customer_log;
				}
				$m_r_customer_log->addAll($customer_logs);

				//线索标记为以转移
				$leads_data = array();
				$leads_data['contacts_id'] = $contacts_id;
				$leads_data['customer_id'] = $customer_id;
				$leads_data['is_transformed'] = 1;
				$leads_data['transform_role_id'] = session('role_id');
				$m_leads->where('leads_id=%d',$leads_id)->save($leads_data);

				//增加客户下操作记录
				$up_message = '将线索 '.$leads_info['name'].' 转化为客户'; 
				$arr['create_time'] = time();
				$arr['create_role_id'] = session('role_id');
				$arr['type'] = '修改';
				$arr['duixiang'] = $up_message;
				$arr['model_name'] = 'customer';
				$arr['action_id'] = $customer_id;
				M('ActionRecord')->add($arr);

				$this->ajaxReturn('','转换成功！',1);
			} else {
				$this->ajaxReturn('','转换失败，请重试！',0);
			}
		}
	}
}