<?php
class SystemAction extends Action {

	/**
	*  用于判断权限
	*  @permission 无限制
	*  @allow 登录用户可访问
	*  @other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('scene_add','scene_edit','scene_setting','scenesort','scenedefault','scenelistajax','cycel')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME); 
	}

	/**
	 * 自定义场景（添加）
	 * @param 
	 * @author
	 * @return 
	 */
	public function scene_add(){
		$module_name = $_REQUEST['module'] ? trim($_REQUEST['module']) : '';
		if (!$module_name) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$m_fields = M('Fields');
		$m_scene = M('Scene');

		$field_list = $m_fields->where(array('model'=>$module_name,'is_main'=>1))->select();
		if ($this->isPost()) {
			$data = $_POST['data'];
			if (!$data) {
				$this->ajaxReturn('','筛选条件不能为空！',0);
			}
			//判断重复
			if ($m_scene->where(array('module'=>$module_name,'role_id'=>session('role_id'),'name'=>trim($_POST['scene_name'])))->find()) {
				$this->ajaxReturn('','场景名称已存在！',0);
			}
			if ($m_scene->create()) {
				$m_scene->name = trim($_POST['scene_name']);
				$m_scene->role_id = session('role_id');
				$m_scene->create_time = time();
				$m_scene->update_time = time();
				$m_scene->module = $module_name;
				//处理筛选条件值
				if(!empty($data) && is_array($data)){
					$scene_data = 'array(';
					$s = array();
					$i = 0;
					foreach ($data as $k=>$v) {
						if($v != '' && !in_array($v['field'] ,$s)) {
							$i++;
							if ($i == 1) {
								$scene_data .= "$v[field]=>array(";
							}else {
								$scene_data .= ","."$v[field]=>array(";
							}
							$scene_data .= "field=>'$v[field]',";
							foreach ($_POST[$v['field']] as $k1=>$v1) {
								if ($k1 == 'condition') {
									$scene_data .= "condition=>'$v1',";
								} elseif ($k1 == 'value') {
									$scene_data .= "value=>'$v1',";
								} elseif ($k1 == 'state' || $k1 == 'city' || $k1 == 'area') {
									//处理地址数据
									$scene_data .= "$k1=>'$v1',";
								} elseif ($k1 == 'start') {
									//处理日期数据
									$scene_data .= "start=>'$v1',";
								} elseif ($k1 == 'end') {
									//处理日期数据
									$scene_data .= "end=>'$v1',";
								}
							}
							if ($v['field'] == 'create_time' || $v['field'] == 'update_time') {
								$form_type = 'datetime';
							} elseif ($v['field'] == 'owner_role_id') {
								$form_type = '';
							} else {
								$form_type = $m_fields->where(array('model'=>$module_name,'is_main'=>1,'field'=>$v['field']))->getField('form_type');
							}
							
							$scene_data .= "form_type=>'$form_type'";
							$s[] = $v['field'];
						}
						$scene_data .= ')';
					}
					$scene_data .= ')';
					$m_scene->data = $scene_data;
				}
				if ($scene_id = $m_scene->add()) {
					$scene_info = $m_scene->where(array('id'=>$scene_id))->find();
					$this->ajaxReturn($scene_info,'创建成功！',1);
				} else {
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			}
		}
		$this->module_name = $module_name;
		$this->field_list = $field_list;
		$this->display();
	}

	/**
	 * 自定义场景（编辑）
	 * @param 
	 * @author
	 * @return 
	 */
	public function scene_edit(){
		$module_name = $_REQUEST['module'] ? trim($_REQUEST['module']) : '';
		if (!$module_name) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$id = $_REQUEST['id'] ? intval($_REQUEST['id']) : '';
		if (!$id) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$m_fields = M('Fields');
		$m_scene = M('Scene');
		$scene_info = $m_scene->where(array('id'=>$id,'role_id'=>session('role_id')))->find();
		if ($scene_info['type'] == 1) {
			$this->ajaxReturn('','不能编辑！',0);
		}
		//处理自定义条件
		$data = array();
		eval('$data = '.$scene_info["data"].';');
		$scene_info['data'] = $data;
		$field_list = $m_fields->where(array('model'=>$module_name,'is_main'=>1))->select();
		if ($this->isPost()) {
			$data = $_POST['data'];
			if (!$data) {
				$this->ajaxReturn('','筛选条件不能为空！',0);
			}
			//判断重复
			if ($m_scene->where(array('module'=>$module_name,'role_id'=>session('role_id'),'name'=>trim($_POST['scene_name']),'id'=>array('neq',$id)))->find()) {
				$this->ajaxReturn('','场景名称已存在！',0);
			}
			if ($m_scene->create()) {
				$m_scene->name = trim($_POST['scene_name']);
				$m_scene->update_time = time();
				$m_scene->module = $module_name;
				//处理筛选条件值
				if(!empty($data) && is_array($data)){
					$scene_data = 'array(';
					$s = array();
					$i = 0;
				// println($_POST);
					foreach ($data as $k=>$v) {
						if($v != '' && !in_array($v['field'] ,$s)) {
							$i++;
							if ($i == 1) {
								$scene_data .= "$v[field]=>array(";
							}else {
								$scene_data .= ","."$v[field]=>array(";
							}
							$scene_data .= "field=>'$v[field]',";
							foreach ($_POST[$v['field']] as $k1=>$v1) {
								if ($k1 == 'condition') {
									$scene_data .= "condition=>'$v1',";
								} elseif ($k1 == 'value') {
									$scene_data .= "value=>'$v1',";
								} elseif ($k1 == 'state_scene' || $k1 == 'state') {
									$k1 = 'state';
									//处理地址数据
									$scene_data .= "$k1=>'$v1',";
								} elseif ($k1 == 'city_scene' || $k1 == 'city') {
									$k1 = 'city';
									//处理地址数据
									$scene_data .= "$k1=>'$v1',";
								} elseif ($k1 == 'area_scene' || $k1 == 'area') {
									$k1 = 'area';
									//处理地址数据
									$scene_data .= "$k1=>'$v1',";
								} elseif ($k1 == 'start') {
									//处理日期数据
									$scene_data .= "start=>'$v1',";
								} elseif ($k1 == 'end') {
									//处理日期数据
									$scene_data .= "end=>'$v1',";
								}
							}
							if ($v['field'] == 'create_time' || $v['field'] == 'update_time') {
								$form_type = 'datetime';
							} elseif ($v['field'] == 'owner_role_id') {
								$form_type = '';
							} else {
								$form_type = $m_fields->where(array('model'=>$module_name,'is_main'=>1,'field'=>$v['field']))->getField('form_type');
							}
							
							$scene_data .= "form_type=>'$form_type'";
							$s[] = $v['field'];
						}
						$scene_data .= ')';
					}
					$scene_data .= ')';
					$m_scene->data = $scene_data;
				}
				if ($m_scene->where('id = %d',$id)->save()) {
					$new_scene_info = $m_scene->where('id = %d',$id)->find();
					$this->ajaxReturn($new_scene_info,'创建成功！',1);
				} else {
					$this->ajaxReturn('','创建失败，请重试！',0);
				}
			}
		}
		$this->module_name = $module_name;
		$this->field_list = $field_list;
		$this->scene_info = $scene_info;
		$this->display();
	}
	
	/**
	 * 自定义场景（管理）
	 * @param 
	 * @author
	 * @return 
	 */
	public function scene_setting(){
		$module_name = $_REQUEST['module'] ? trim($_REQUEST['module']) : '';
		if (!$module_name) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$m_scene = M('Scene');
		$m_scene_default = M('SceneDefault');
		if ($this->isPost()) {
			$data = $_POST['data'];
			if ($m_scene->save()) {
				$this->ajaxReturn('','创建成功！',1);
			} else {
				$this->ajaxReturn('','创建失败，请重试！',0);
			}
		}
		$m_scene = M('Scene');
		$scene_id = $_REQUEST['scene_id'] ? intval($_REQUEST['scene_id']) : '';
		$scene_where = array();
		$scene_where['role_id']  = session('role_id');
		$scene_where['type']  = 1;
		$scene_where['_logic'] = 'or';
		$map_scene['_complex'] = $scene_where;
		$map_scene['module'] = $module_name;

		$scene_list = $m_scene->where($map_scene)->order('order_id asc,id asc')->select();
		//默认场景
		$default_scene = $m_scene_default->where(array('module'=>$module_name,'role_id'=>session('role_id')))->getField('scene_id');
		if (!$default_scene) {
			$default_scene = $m_scene->where(array('module'=>$module_name,'type'=>1))->order('id asc')->getField('id');
		}
		$this->default_scene = $default_scene;

		$this->scene_list = $scene_list;
		$this->module_name = $module_name;
		$this->display();
	}

	/**
	 * 自定义场景（排序）
	 * @param 
	 * @author
	 * @return 
	 */
	public function scenesort(){
		//权限判断

		$m_scene = M('Scene');
		if(isset($_GET['postion'])){
			$m_scene = M('Scene');
			foreach(explode(',', $_GET['postion']) as $k=>$v) {
				$data = array('id'=> $v, 'order_id'=>$k);
				$m_scene->save($data);
			}
			$this->ajaxReturn('1', 'success', 1);
		} else {
			$this->ajaxReturn('0', 'error', 1);
		}
	}

	/**
	 * 自定义场景（是否默认、隐藏）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function sceneDefault(){
		$m_scene = M('Scene');
		$scene_id = $this->_request('scene_id','intval',0);
		if ($this->isAjax()) {
			$scene_where = array();
			$scene_where['role_id']  = session('role_id');
			$scene_where['type']  = 1;
			$scene_where['_logic'] = 'or';
			$map_scene['_complex'] = $scene_where;
			$map_scene['id'] = $scene_id;

			$scene_info = $m_scene->where($map_scene)->find();
			if (!$scene_info) {
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			if (trim($_GET['type']) == 'default') {
				//默认场景
				$m_scene_default = M('SceneDefault');
				$res_scene = $m_scene_default->where(array('module'=>$scene_info['module'],'role_id'=>session('role_id')))->find();
				if ($res_scene) {
					$res = $m_scene_default->where(array('module'=>$scene_info['module'],'role_id'=>session('role_id')))->setField('scene_id',$scene_id);
				} else {
					$data = array();
					$data['module'] = $scene_info['module'];
					$data['role_id'] = session('role_id');
					$data['scene_id'] = $scene_id;
					$res = $m_scene_default->add($data);
				}
				if ($res) {
					$this->ajaxReturn('','success',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			}
			if (trim($_GET['type']) == 'hide') {
				if ($scene_info['is_hide']) {
					if ($m_scene->where('id = %d', $scene_id)->setField('is_hide', 0)) {
						$this->ajaxReturn(0,'success',1);
					} else {
						$this->ajaxReturn('','修改失败，请重试！',0);
					}
				} else {
					if ($m_scene->where('id = %d', $scene_id)->setField('is_hide', 1)) {
						$this->ajaxReturn(1,'success',1);
					} else {
						$this->ajaxReturn('','修改失败，请重试！',0);
					}
				}
			}
			if (trim($_GET['type']) == 'del') {
				if ($m_scene->where('id = %d', $scene_id)->delete()) {
					//删除默认场景
					$res = M('SceneDefault')->where('scene_id = %d',$scene_id)->delete();
					$this->ajaxReturn('','success',1);
				} else {
					$this->ajaxReturn('','删除失败，请重试！',0);
				}
			}		
		}
	}

	/**
	 * 自定义场景（列表刷新）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function sceneListAjax(){
		if ($this->isAjax()) {
			$module = $_REQUEST['module'] ? trim($_REQUEST['module']) : '';
			if (!$module) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_scene = M('Scene');
			$scene_where = array();
			$scene_where['role_id']  = session('role_id');
			$scene_where['type']  = 1;
			$scene_where['_logic'] = 'or';
			$map_scene['_complex'] = $scene_where;
			$map_scene['module'] = 'customer';
			$map_scene['is_hide'] = 0;

			$scene_list = $m_scene->where($map_scene)->order('order_id asc,id asc')->select();
			$this->ajaxReturn($scene_list,'',1);
		}
	}

	/**
	 * 自定义周期设置
	 * @param 
	 * @author 
	 * @return 
	 */
	public function cycel(){
		$m_cycel = M('Cycel');
		$module = trim($_REQUEST['module']);
		$module_id = intval($_REQUEST['module_id']);
		if (!$module || !$module_id) {
			$this->ajaxReturn('','参数错误！',0);
		}
		$cycel_info = $m_cycel->where(array('module'=>$module,'module_id'=>$module_id))->find();
		if ($this->isPost()) {
			if ($m_cycel->create()) {
				$m_cycel->update_time = time();
				$m_cycel->end_time = strtotime(trim($_POST['end_time']));
				if ($cycel_info) {
					if ($m_cycel->save()) {
						$this->ajaxReturn('','设置成功',1);
					} else {
						$this->ajaxReturn('','设置失败，请重试！',0);
					}
				} else {
					$m_cycel->create_role_id = session('role_id');
					$m_cycel->start_time = strtotime(date('Y-m-d',time()));
					if ($m_cycel->add()) {
						$this->ajaxReturn('','设置成功',1);
					} else {
						$this->ajaxReturn('','设置失败，请重试！',0);
					}
				}
			} else {
				$this->ajaxReturn('','设置失败，请重试！',0);
			}
		}
		$this->cycel_info = $cycel_info;
		$this->display(); 
	}
}