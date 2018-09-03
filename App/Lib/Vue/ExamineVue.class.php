<?php
/**
 *审批相关
 **/
class ExamineVue extends Action{
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('examinestatus','check_list','add_examine','examinestatus','checkPer')
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
	 * 审批动态
	 * @param 
	 * @author 
	 * @return 
	 */
	public function dynamic() {
		if ($this->isPost()) {
			$m_examine = M('Examine');
			$where = array();
			$where['is_deleted'] = 0;
			$where['examine_status'] = array('lt',2);

			//我发起的
			$map1['_complex'] = $where;
			$map1['creator_role_id']  = session('role_id');
			$create_count = $m_examine->where($map1)->count();
			$data['create_count'] = $create_count ? $create_count : '0';

			//我的审批
			$map2['_complex'] = $where;
			$map2['examine_role_id']  = session('role_id');
			$examine_count = $m_examine->where($map2)->count();
			$data['examine_count'] = $examine_count ? $examine_count : '0';

			//审批模块是否启用
			$type_list = M('ExamineStatus')->where(array('type'=>0))->getField('status',true);
			$data['type_list'] = $type_list ? : array();
			//权限查询
			$permission_list = apppermission('examine','add');
			if($permission_list){
				$data['permission_list'] = $permission_list;
			}else{
				$data['permission_list'] = array();
			}
			$this->ajaxReturn($data,'success',1);
		}else{
			$this->ajaxReturn('','非法请求',0);
		}
	}

	/**
	 * 审批列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			$m_examine = M('Examine');
			$where = array();
			$where['is_deleted'] = 0;
			$order = "examine_status asc,update_time desc";
			$below_ids = getPerByAction('examine','index');
			$by = $_POST['by'] ? trim($_POST['by']) : 'create';
			switch ($by) {
				case 'today' : $where['create_time'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
				case 'week' : $where['create_time'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
				case 'month' : $where['create_time'] = array('gt',strtotime(date('Y-m-01', time()))); break;
				case 'add' : $order = 'create_time desc,examine_id asc';  break;
				case 'update' : $order = 'update_time desc,examine_id asc';  break;
				case 'deleted' : $where['is_deleted'] = 1; break;
				case 'subcreate' : $where['creator_role_id'] = array('in',implode(',', $below_ids)); break;
				case 'not_examine' : $where['examine_status'] = 0; break;
				case 'examining' : $where['examine_status'] = array('in',array(0,1)); break;
				case 'me_examine' : $where['examine_role_id'] = session('role_id');
									$where['examine_status'] = array('in',array(0,1));
									break;//待我审批
				case 'create' : $where['creator_role_id'] = session('role_id'); 
								$order = "update_time desc";
								break;//我发起的
			}
			//我已审批的（包含我参与过的审批）
			$m_examine_check = M('ExamineCheck');
			if ($by == 'examined') {
				$map['role_id'] = session('role_id');
				$examine_ids = $m_examine_check->where($map)->getField('examine_id',true);
				$where['examine_id'] = array('in',implode(',',$examine_ids));
			}
			//审批状态
			if (intval($_POST['examine_status'])) {
				//审批中
				if (intval($_POST['examine_status']) == 1) {
					$where['examine_status'] = array('elt',1);
				}
				//审批完成
				if (intval($_POST['examine_status']) == 2) {
					$where['examine_status'] = array('eq',2);
				}
				//审批完成
				if (intval($_POST['examine_status']) == 3) {
					$where['examine_status'] = array('eq',3);
				}
			}
			//审批类型
			if (intval($_POST['type'])) {
				$where['type'] = intval($_POST['type']);
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;

			$examine_list = $m_examine->where($where)->page($p.',10')->order($order)->field('examine_id,create_time,type,examine_status,creator_role_id,owner_role_id,examine_role_id,content,start_time,end_time,money,budget,end_address,duration,description')->select();

			$m_user = M('User');
			$m_examine_check = M('ExamineCheck');
			foreach ($examine_list as $k=>$v) {
				$user_info = $m_user->where('role_id = %d',$v['creator_role_id'])->field('full_name,thumb_path,role_id')->find();
				$examine_list[$k]['user_info'] = $user_info ? : array();
				//审批权限
				$view = 1;
				$edit = 1;
				$delete = 1;
				
				//详情权限
				$examine_check = array();
				$examine_check = $m_examine_check->where(array('role_id'=>session('role_id'),'examine_id'=>$v['examine_id']))->find();
				if (!in_array($v['creator_role_id'],getPerByAction('examine','view')) && !$examine_check && $v['examine_role_id'] != session('role_id')) {
					$view = 0;
				}
				//编辑、删除权限
				if ($v['examine_status'] != 0) {
					$edit = 0;
					$delete = 0;
				} else {
					if (!in_array($v['creator_role_id'],getPerByAction('examine','edit'))) {
						$edit = 0;
					}
					if (!in_array($v['creator_role_id'],getPerByAction('examine','delete'))) {
						$delete = 0;
					}
				}				
				$examine_list[$k]['permission']['view'] = $view;
				$examine_list[$k]['permission']['edit'] = $edit;
				$examine_list[$k]['permission']['delete'] = $delete;
			}
			if ($by == 'examined') {
				foreach ($examine_list as $k=>$v) {
					//审批意见
					$examine_check_info = $m_examine_check->where('examine_id = %d',$v['examine_id'])->find();
					//审批时间
					$examine_list[$k]['examine_time'] = $examine_check_info['check_time'];
					//审批结果
					$examine_list[$k]['is_agree'] = $examine_check_info['is_checked'];
				}
			}
			$count = $m_examine->where($where)->count();
			$page = ceil($count/10);

			$data['list'] = $examine_list ? $examine_list : array();
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 审批添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$m_examine = M('Examine');
			$params = $_POST;
			if (!is_array($params)) {
				$this->ajaxReturn('','非法的数据格式！',0);
			}
			$type = $_POST['type'] ? intval($_POST['type']) : '';
			if (!$type) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$examine_role_id = intval($params['examine_role_id']);
			if (!$examine_role_id) {
				$this->ajaxReturn('','请选择下一审批人！',0);
			}
			switch ($type) {
				case 1 : $type_name = '审批';break;
				case 2 : $type_name = '请假';break;
				case 3 : $type_name = '报销';break;
				case 4 : $type_name = '差旅';break;
				case 5 : $type_name = '出差';break;
				case 6 : $type_name = '借款';break;
			}
			if (!$params['content']) {
				$this->ajaxReturn('','请填写"'.$type_name.'"内容！',0);
			}
			if ($m_examine->create($params)) {
				$m_examine->creator_role_id = session('role_id');
				$m_examine->owner_role_id = session('role_id');
				$m_examine->create_time = time();
				$m_examine->update_time = time();
				$m_examine->type = $type;

				if ($examine_id = $m_examine->add()) {
					if($_POST['file']){
						$m_examine_file = M('ExamineFile');
						foreach($_POST['file'] as $v){
							$file_data = array();
							$file_data['examine_id'] = $examine_id;
							$file_data['file_id'] = $v;
							$m_examine_file->add($file_data);
						}
					}
					if($_POST['travel']){
						$m_examine_travel = M('ExamineTravel');
						foreach($_POST['travel'] as $v){
							$file_travel = array();
							$file_travel['examine_id'] = $examine_id;
							$file_travel['start_address'] = $v['start_address'];
							if ($_POST['type'] =='4') {
								$file_travel['start_time'] = $v['start_time'];
								$file_travel['end_address'] = $v['end_address'];
								$file_travel['end_time'] = $v['end_time'];
								$file_travel['vehicle'] = $v['vehicle'];
								$file_travel['duration'] = $v['duration'];
							}
							$file_travel['money'] = $v['money'];
							$file_travel['description'] = $v['description'];
							$m_examine_travel->add($file_travel);
						}
					}
					actionLog($examine_id);

					$creator = getUserByRoleId(session('role_id'));
					$message_content = $creator['user_name'].'于'.date('Y-m-d',time()).'创建的'.$type_name.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type_name.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$_POST['content'].'</a>';
					sendMessage($_POST['examine_role_id'],$message_content,1);

					$this->ajaxReturn('','添加成功！',1);
				}else{
					$this->ajaxReturn('','添加失败！',0);
				}
			}else{
				$this->ajaxReturn('','添加失败！',0);
			}
		}
	}

	/**
	 * 审批编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function edit(){
		if ($this->isPost()) {
			$params = $_POST;
			if(!is_array($params)){
				$this->ajaxReturn('','非法的数据格式',0);
			}
			$examine_id = $params['id'] ? intval($params['id']) : 0;
			if (!$examine_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_examine = M('Examine');
			$m_examine_travel = M('ExamineTravel');
			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if (!$examine_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			} else {
				if (in_array($examine_info['examine_status'],array('1','2'))) {
					$this->ajaxReturn('','当前状态不允许编辑！',0);
				} elseif (!in_array($examine_info['creator_role_id'],$this->_permissionRes)) {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}
			
			$type = $examine_info['type'];
			$examine_role_id = intval($params['examine_role_id']);
			if (!$examine_role_id) {
				$this->ajaxReturn('','请选择下一审批人！',0);
			}
			switch ($type) {
				case 1:$type_name = '审批';break;
				case 2:$type_name = '请假';break;
				case 3:$type_name = '报销';break;
				case 4:$type_name = '差旅';break;
				case 5:$type_name = '出差';break;
				case 6:$type_name = '借款';break;
			}
			if (!$params['content']) {
				$this->ajaxReturn('','请填写"'.$type_name.'"内容!',0);
			}
			if ($m_examine->create($params)) {
				$m_examine->update_time = time();
				$m_examine->examine_status = 0;
				$m_examine->order_id = 0;
				if ($m_examine->where('examine_id = %d',$examine_id)->save()) {
					$operation_flag = true;
					foreach($_POST['travel'] as $v){
						if(!empty($v['money'])){
							$file_travel = array();
							$file_travel['examine_id'] = $examine_id;
							$file_travel['start_address'] = $v['start_address'];
							if($_POST['type'] =='4'){
								$file_travel['start_time'] = $v['start_time'];
								$file_travel['end_address'] = $v['end_address'];
								$file_travel['end_time'] = $v['end_time'];
								$file_travel['vehicle'] = $v['vehicle'];
								$file_travel['duration'] = $v['duration'];
							}
							$file_travel['money'] = $v['money'];
							$file_travel['description'] = $v['description'];
							//在编辑时，如果又添加商品，根据是否存在sales_product_id来进行编辑或添加
							if(empty($v['id'])){
								//添加
								$result_examine = $m_examine_travel->add($file_travel);
								if(empty($result_examine)){
									$operation_flag = false;
									break;
								}
							}else{
								//编辑
								$result_examine = $m_examine_travel->where('id = %d', $v['id'])->save($file_travel);
								if($result_examine === false){
									$operation_flag = false;
									break;
								}
							}
						}
						//在编辑时，如果从原来的数据中去除一条信息，则删除
						if($v['id'] && empty($v['money'])){
							$result_examine = $m_examine_travel->where('id = %d', $v['id'])->delete();
							if($result_examine == 0 || $result_examine === false){
								$operation_flag = false;
							}
						}
					}
					switch ($type) {
						case 1 : $type = '普通审批';break;
						case 2 : $type = '请假单';break;
						case 3 : $type = '报销单';break;
						case 4 : $type = '差旅单';break;
						case 5 : $type = '出差申请';break;
						case 6 : $type = '借款单';break;
					}
					$creator = getUserByRoleId(session('role_id'));
					$message_content = $creator['user_name'].'于'.date('Y-m-d',time()).'编辑了'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$_POST['id']).'">'.$params['content'].'</a>';
					sendMessage($params['examine_role_id'],$message_content,1);
					actionLog($examine_id);
					$this->ajaxReturn('','修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败！',0);
			}
		}
	}

	/**
	 * 审批详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$examine_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$m_examine = M('Examine');
			$m_user = M('User');

			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if (!$examine_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}

			if(!$this->checkPer($examine_id)){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//申请人
			$examine_info['create_name'] = $m_user->where('role_id = %d',$examine_info['creator_role_id'])->getField('full_name');
			//审批人
			$examine_info['examine_name'] = $m_user->where(array('role_id'=>$examine_info['examine_role_id']))->getField('full_name');

			if ($examine_info['type'] == 3 || $examine_info['type'] == 4) {
				$travel_list = M('ExamineTravel')->where('examine_id = %d',$examine_id)->select();
				$examine_info['travel'] = $travel_list ? $travel_list : array();
			}
			//附件
			$file_id_array = M('ExamineFile')->where('examine_id = %d',$examine_id)->getField('file_id',true);
			$file_list = M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select();
			$examine['file_count'] = $file_list ? count($file_list) : 0;
			foreach ($file_list as $key => $value) {
				$file_list[$key]['size'] = ceil($value['size']/1024);
				$file_list[$key]['pic'] = show_picture($value['name']);
			}
			$examine_info['file_list'] = $file_list ? $file_list : array();
			//审批意见
			$m_examine_check = M('ExamineCheck');
			$opinion_list = $m_examine_check->where('examine_id = %d',$examine_id)->field('check_time,is_checked,content,role_id')->select();
			foreach ($opinion_list as $k=>$v) {
				$opinion_list[$k]['role_info'] = $m_user->where('role_id = %d',$v['role_id'])->field('full_name,thumb_path,role_id')->find();
			}
			$examine_info['opinion_list'] = $opinion_list ? : array();
			//是否有审批权限
			if (session('?admin') || $examine_info['examine_role_id'] == session('role_id')) {
				if ($examine_info['examine_status'] == 0 || $examine_info['examine_status'] == 1) {
					$add_examine = 1;
				}
			}
			//返回编辑、删除权限
			if ($examine_info['examine_status'] == 0 || $examine_info['examine_status'] == 3) {
				$data['permission'] = array('edit'=>1,'delete'=>1);
			} else {
				$data['permission'] = array('edit'=>0,'delete'=>0);
			}
			$examine_info['add_examine'] = $add_examine ? $add_examine : 0;			
			$data['data'] = $examine_info;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 审批删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		if ($this->isPost()) {
			$examine_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$examine_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_examine = M('Examine');
			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if (!$examine_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			} else {
				if ($examine_info['examine_status'] != 0) {
					$this->ajaxReturn('','当前状态不允许删除',0);
				} elseif (!in_array($examine_info['creator_role_id'],$this->_permissionRes)) {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
			}
			$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
			$res = $m_examine->where('examine_id = %d',$examine_id)->setField($data);
			if ($res) {
				actionLog($examine_id);
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}
	}

	/**
	 * 添加审批意见
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add_examine() {
		if ($this->isPost()) {
			$examine_id = intval($_POST['id']) ? : '';
			$m_examine = M('Examine');
			$m_examine_step = M('examine_step');
			$examine_info = $m_examine->where(array('examine_id'=>$examine_id))->find();
			if (!$examine_info) {
				$this->ajaxReturn('','审批单不存在或已删除！',0);
			}
			$option = M('examine_status')->where('status=%d',intval($_POST['type']))->getField('option');
			if ($m_examine->create()) {
				$m_examine->update_time = time();
				$is_end = 0; //是否结束审批（发送站内信判断）
				if ($_POST['is_agree'] == 1) {
					if ($_POST['examine_status'] != 2 && $_POST['examine_role_id'] == null) {
						$this->ajaxReturn('','请选择下一审批人！',0);
					}
					$m_examine->examine_role_id = intval($_POST['examine_role_id']);
					if ($_POST['examine_status'] == 2) {
						$m_examine->order_id = intval($_POST['order_id']);
						$m_examine->examine_status = 2;
					} elseif ($option == 1) {
						//自定义流程
						//查询审批流程排序最大值，如果order_id和最大值相等，则审批结束
						$max_order_id = $m_examine_step->where('process_id = %d',$examine_info['type'])->max('order_id');
						if ($examine_info['order_id'] == $max_order_id) {
							$m_examine->examine_status = 2;//审批结束
							$is_end = 1;
						} else {
							$m_examine->order_id = $order_id;
							$m_examine->examine_status = 1;	//审批中
						}
					} else {
						$m_examine->examine_status = 1;	//审批中
					}
				} else {
					//结束审批
					$is_end = 1;
					//如果是自定义流程,驳回至最初状态					
					if($option == 1){
						$step_role_id = $m_examine_step->where(array('process_id'=>intval($_POST['type'])))->order('order_id asc')->getField('role_id');
					}
					$m_examine->examine_role_id = $step_role_id ? : 0;
					$m_examine->order_id = 0;
					$m_examine->examine_status = 3;
				}
				if ($m_examine->where(array('examine_id'=>$examine_id))->save()) {
					$c_data = array();
					$c_data['role_id'] = session('role_id');
					$c_data['is_checked'] = intval($_POST['is_agree']);
					$c_data['examine_id'] = $examine_id;
					$c_data['content'] = $_POST['opinion'];
					$c_data['check_time'] = time();
					M('examine_check')->add($c_data);
					
					switch ($examine_info['type']) {
						case 1 : $type = '普通审批';break;
						case 2 : $type = '请假审批';break;
						case 3 : $type = '普通报销';break;
						case 4 : $type = '差旅报销';break;
						case 5 : $type = '出差申请';break;
						case 6 : $type = '借款申请';break;
					}
					$creator = getUserByRoleId($examine_info['creator_role_id']);
					if ($_POST['examine_status'] == 2 || $is_end == 1) {
						$message_content = '您申请的'.$type.'已被审批！<a href="'.U('examine/view','id='.$examine_id).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$examine_info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间：'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$examine_info['content'].'</a>';
						sendMessage($examine_info['creator_role_id'],$message_content,1);
					} else {
						$message_content = '您有一个'.$type.'审批待处理！<a href="'.U('examine/view','id='.$examine_id).'">点击查看</a><br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.$examine_info['creator_role_id'].'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型：'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容：<a href="'.U('examine/view','id='.$examine_id).'">'.$examine_info['content'].'</a>';
						sendMessage($_POST['examine_role_id'],$message_content,1);
					}
					$this->ajaxReturn('','审核成功！',1);
				} else {
					$this->ajaxReturn('','审核失败！',0);
				}
			}
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 审批权限判断
	 * @param 
	 * @author 
	 * @return 
	 */
	public function checkPer($examine_id) {
		$m_examine = M('Examine');
		//非管理员权限限制
		if(!session('?admin')){
			//已审核的人
			$examine_check_info = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$examine_id))->find();
			//审核人或自己
			$c_where['creator_role_id'] = session('role_id'); 
			$c_where['examine_role_id'] = session('role_id');
			$c_where['_logic'] = 'or';
			$where['_complex'] = $c_where;
		}
		$where['examine_id'] = $examine_id;
		$examine_info = $m_examine->where($where)->find();
		$creator_role_id = $m_examine->where('examine_id = %d',$examine_id)->getField('creator_role_id');
		//授权判断
		$below_ids = getPerByAction('examine','view');

		if($examine_check_info || $examine_info || in_array($creator_role_id, $below_ids)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * 审批记录
	 * @param 
	 * @author 
	 * @return 
	 */
	public function check_list(){
		$m_examine_check = M('ExamineCheck');
		$m_user = M('User');
		$examine_id = $_POST['id'] ? intval($_POST['id']) : 0;
		//判断权限
		if (!$this->checkPer($examine_id)) {
			$this->ajaxReturn('','您没有此权利！',-2);
		}
		if ($examine_id) {
			$examine_info = M('Examine')->where(array('examine_id'=>$examine_id))->find();
			//是否有审批权限
			if (session('?admin') || $examine_info['examine_role_id'] == session('role_id')) {
				if ($examine_info['examine_status'] == 0 || $examine_info['examine_status'] == 1) {
					$add_examine = 1;
				}
			}
			$check_list = $m_examine_check->where('examine_id = %d',$examine_id)->order('check_id asc')->select();
			foreach($check_list as $k=>$v){
				$check_list[$k]['user'] = $m_user->where('role_id =%d',$v['role_id'])->field('role_id,full_name,thumb_path')->find();
			}
			$data['add_examine'] = $add_examine ? $add_examine : 0;
			$data['list'] = $check_list ? $check_list : array();
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','参数错误！',0);
		}
	}

	/**
	 * 审批人流程
	 * @param 
	 * @author 
	 * @return 
	 */
	public function examineStatus() {
		if ($this->isPost()) {
			$status = $_POST['type'] ? intval($_POST['type']) : 0;
			$examine_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$status) {
				$this->ajaxReturn('','参数错误！',0);
			}
			if ($examine_id) {
				$examine_info = M('Examine')->where(array('examine_id'=>$examine_id,'is_deleted'=>0))->field('order_id')->find();
				if (!$examine_info) {
					$this->ajaxReturn('','数据不存在或已删除！',0);
				}
				$next_order = $examine_info['order_id']+1;
			} else {
				$next_order = 0;
			}
			
			$option = M('ExamineStatus')->where(array('status'=>$status))->getField('option');
			if ($option == 1) {
				$role_id = M('ExamineStep')->where(array('process_id'=>$status,'order_id'=>$next_order))->getField('role_id');
				$examine_role['role_id'] = $role_id ? : '';
				$full_name = M('User')->where('role_id =%d',$examine_role['role_id'])->getField('full_name');
				$examine_role['full_name'] = $full_name ? : '';
			} else {
				$examine_role['role_id'] = '';
				$examine_role['full_name'] = '';
			}
			$data['option'] = $option  ? : '0'; //0自选 1流程
			$data['examine_role'] = $examine_role ? : array();
			$data['status'] = 1;
			$data['info'] = 'success';
			$this->ajaxReturn($data,'JSON');
		}
	}
}