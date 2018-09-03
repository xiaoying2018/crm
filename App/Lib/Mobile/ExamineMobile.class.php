<?php

class ExamineMobile extends Action{
	/**
	 *	permission 未登录可访问
	 * 	allow 登录访问
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
		);
		B('AppAuthenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
		Global $roles;
		$this->roles = $roles;
	}

	//审批动态
	public function dynamic(){
		if($this->isPost()){
			$m_examine = M('Examine');
			$where = array();
			$where['is_deleted'] = 0;
			$where['examine_status'] = array('lt',2);
			//我发起的
			$map1['_complex'] = $where;
			$map1['creator_role_id']  = session('role_id');
			$create_count = $m_examine->where($map1)->count();
			$examine_info['create_count'] = $create_count ? $create_count : 0;
			//我的审批
			$map2['_complex'] = $where;
			$map2['examine_role_id']  = session('role_id');
			$examine_count = $m_examine->where($map2)->count();
			$examine_info['examine_count'] = $examine_count ? $examine_count : 0;
			$permission_list = apppermission('examine','add');
			if($permission_list){
				$data['permission_list'] = $permission_list;
			}else{
				$data['permission_list'] = array();
			}
			$this->ajaxReturn($examine_info,'success',1);
		}else{
			$this->ajaxReturn('非法请求','非法请求',2);
		}
	}

	//审批列表
	public function index(){
		if($this->roles == 2){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$m_examine = M('Examine');
			$where = array();
			$where['is_deleted'] = 0;
			//$where['examine_status'] = array('lt',2);
			$order = "examine_status asc,update_time desc";
			//$below_ids = getSubRoleId(false);
			$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
			$all_ids = getSubRoleId();
			$by = trim($_GET['by']);
			if(!$by){
				$this->ajaxReturn('参数错误','参数错误',2);
			}
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
				//case 'examined' : $where['examine_status'] = array('in',array(2,3)); break;
				//待我审批
				case 'me_examine' : $where['examine_role_id'] = session('role_id');$where['examine_status'] = array('elt',1); break;
				//我发起的
				case 'create' : $where['creator_role_id'] = session('role_id'); break;
			}
			//我已审批的（包含我参与过的审批）
			$m_examine_check = M('ExamineCheck');
			if($by == 'examined'){
				$map['role_id'] = session('role_id');
				$examine_ids = $m_examine_check->where($map)->getField('examine_id',true);
				$where['examine_id'] = array('in',implode(',',$examine_ids));
			}
			//审批状态
			if(intval($_REQUEST['examine_status'])){
				//审批中
				if(intval($_REQUEST['examine_status']) == 1){
					$where['examine_status'] = array('elt',1);
				}
				//审批完成
				if(intval($_REQUEST['examine_status']) == 2){
					$where['examine_status'] = array('gt',1);
				}
			}
			//审批类型
			if(intval($_REQUEST['type'])){
				$where['type'] = intval($_REQUEST['type']);
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$list = $m_examine->where($where)->page($p.',10')->order($order)->field('examine_id,create_time,type,examine_status,creator_role_id,owner_role_id,examine_role_id')->select();
			$m_user = M('User');
			foreach($list as $k=>$v){
				$list[$k]['user_name'] = $m_user->where('role_id = %d',$v['creator_role_id'])->getField('full_name');
				//审批权限
				$view = 1;
				$edit = 1;
				$delete = 1;
				
				//详情权限
				$examine_check = array();
				$examine_check = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$v['examine_id']))->find();
				if($v['type'] == 4){
					$owner_role_id = implode(',',$v['owner_role_id']);//出差人
					if(!in_array($v['creator_role_id'],getPerByAction('examine','view')) && !$examine_check && $v['examine_role_id'] != session('role_id') && !in_array(session('role_id'),$owner_role_id)){
						$view = 0;
					}
				}else{
					if(!in_array($v['creator_role_id'],getPerByAction('examine','view')) && !$examine_check && $v['examine_role_id'] != session('role_id')){
						$view = 0;
					}
				}
				//编辑权限
				if($v['examine_status'] != 0){
					$edit = 0;
				}else{
					if(!in_array($v['creator_role_id'],getPerByAction('examine','edit'))){
						$edit = 0;
					}
				}
				//删除权限
				if($v['examine_status'] != 0){
					$delete = 0;
				}else{
					if(!in_array($v['creator_role_id'],getPerByAction('examine','delete'))){
						$delete = 0;
					}
				}
				
				
				$list[$k]['permission']['view'] = $view;
				$list[$k]['permission']['edit'] = $edit;
				$list[$k]['permission']['delete'] = $delete;
			}
			if($by == 'examined'){
				foreach($list as $k=>$v){
					//审批意见
					$examine_check_info = $m_examine_check->where('examine_id = %d',$v['examine_id'])->find();
					//审批时间
					$list[$k]['examine_time'] = $examine_check_info['check_time'];
					//审批结果
					$list[$k]['is_agree'] = $examine_check_info['is_checked'];
				}
			}
			
			$list = empty($list) ? array() : $list;
			$count = $m_examine->where($where)->count();
			$page = ceil($count/10);
			$data['list'] = $list;
			$data['page'] = $page;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	//新建审批
	public function add(){
		if(!in_array(session('role_id'),getPerByAction('examine','add'))){
			$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
		}
		if($this->isPost()){
			$m_examine = M('Examine');
			$params = json_decode($_POST['params'],true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式!',2);
			}
			$type = intval($_GET['type']) ? intval($_GET['type']) : 1;
			if(!$type){
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
			$examine_role_id = intval($params['examine_role_id']);
			if(!$examine_role_id){
				$this->ajaxReturn('请选择下一审批人!','请选择下一审批人!',2);
			}
			switch($type){
				case 1:$type_name = '审批';break;
				case 2:$type_name = '请假';break;
				case 3:$type_name = '报销';break;
				case 4:$type_name = '差旅';break;
				case 5:$type_name = '借款';break;
			}
			if(!$params['content']){
				$this->ajaxReturn('请填写"'.$type_name.'"内容!','请填写"'.$type_name.'"内容!',2);
			}
			if($m_examine->create($params)){
				$m_examine->creator_role_id = session('role_id');
				$m_examine->owner_role_id = session('role_id');
				$m_examine->create_time = time();
				$m_examine->update_time = time();
				$m_examine->type = $type;
				
				if($type == 2){
					if(!$params['start_time'] || !$params['end_time']){
						$this->ajaxReturn('请填写请假时间!','请填写请假时间!',2);
					}
					if(!$params['duration']){
						$this->ajaxReturn('请填写请假时长!','请填写请假时长!',2);
					}
					$m_examine->duration = intval($params['duration']);
				}elseif($type == 3){
					if(!$params['cate_list']){
						$this->ajaxReturn('请填写报销事项!','请填写报销事项!',2);
					}
				}elseif($type == 4){
					if(!$params['start_address']){
						$this->ajaxReturn('请填写出发地!','请填写出发地!',2);
					}
					if(!$params['end_address']){
						$this->ajaxReturn('请填写目的地!','请填写目的地!',2);
					}
					if(!$params['start_time']){
						$this->ajaxReturn('请填写出发时间!','请填写出发时间!',2);
					}
					if(!$params['end_time']){
						$this->ajaxReturn('请填写结束时间!','请填写结束时间!',2);
					}
					$m_examine->owner_role_id = $params['owner_role_list'];
				}elseif($type == 5){
					if(!$params['start_time']){
						$this->ajaxReturn('请填写借款时间!','请填写借款时间!',2);
					}
					if(!$params['money']){
						$this->ajaxReturn('请填写借款金额!','请填写借款金额!',2);
					}
				}
	// $examine_id = $m_examine->add();
	// $this->ajaxReturn($m_examine->getLastSql(),'',1);
				if($examine_id = $m_examine->add()){
					if($type == 3){
						$m_examine_travel = M('ExamineTravel');
						$cate_list = $params['cate_list'];
						foreach($cate_list as $k=>$v){
							$data['examine_id'] = $examine_id;
							$data['start_address'] = $v['cate'];
							$data['money'] = $v['money'];
							$data['start_time'] = $v['expense_time'];
							$m_examine_travel->add($data);
						}
					}
					actionLog($examine_id);
					$this->ajaxReturn('添加成功','success',1);
				}else{
					$this->ajaxReturn('添加失败','添加失败',2);
				}
			}else{
				$this->ajaxReturn('添加失败','添加失败',2);
			}
		}
	}
	//编辑审批
	public function edit(){
		if($this->isPost()){
			$examine_id = $_POST['id'];
			if(!$examine_id){
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
			$m_examine = M('Examine');
			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if(!$examine_info){
				$this->ajaxReturn('数据不存在或已删除！','数据不存在或已删除！',2);
			}else{
				if($examine_info['examine_status'] != 0){
					$this->ajaxReturn('当前状态不允许编辑','当前状态不允许编辑',2);
				}elseif(!in_array($examine_info['creator_role_id'],$this->_permissionRes)){
					$this->ajaxReturn('您没有此权利!','您没有此权利',-2);
				}
			}
			$params = json_decode($_POST['params'],true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式',2);
			}
			$type = $examine_info['type'];
			//$type = trim($_GET['type']) ? trim($_GET['type']) : 1;
			$examine_role_id = intval($params['examine_role_id']);
			if(!$examine_role_id){
				$this->ajaxReturn('请选择下一审批人!','请选择下一审批人',2);
			}
			switch($type){
				case 1:$type_name = '审批';break;
				case 2:$type_name = '请假';break;
				case 3:$type_name = '报销';break;
				case 4:$type_name = '差旅';break;
				case 5:$type_name = '借款';break;
			}
			if(!$params['content']){
				$this->ajaxReturn('请填写"'.$type_name.'"内容!','请填写"'.$type_name.'"内容!',2);
			}
			if($m_examine->create($params)){
				$m_examine->update_time = time();
				
				if($type == 2){
					if(!$params['start_time'] || !$params['end_time']){
						$this->ajaxReturn('请填写请假时间!','请填写请假时间!',2);
					}
					if(!$params['duration']){
						$this->ajaxReturn('请填写请假时长!','请填写请假时长!',2);
					}
					$m_examine->duration = intval($params['duration']);
				}elseif($type == 3){
					if(!$params['cate_list']){
						$this->ajaxReturn('请填写报销事项!','请填写报销事项!',2);
					}
				}elseif($type == 4){
					if(!$params['start_address']){
						$this->ajaxReturn('请填写出发地!','请填写出发地!',2);
					}
					if(!$params['end_address']){
						$this->ajaxReturn('请填写目的地!','请填写目的地!',2);
					}
					if(!$params['start_time']){
						$this->ajaxReturn('请填写出发时间!','请填写出发时间!',2);
					}
					if(!$params['end_time']){
						$this->ajaxReturn('请填写结束时间!','请填写结束时间!',2);
					}
					if(!$params['budget']){
						$this->ajaxReturn('请填写预算金额!','请填写预算金额!',2);
					}
					if(!$params['advance']){
						$this->ajaxReturn('请填写预支金额!','请填写预支金额!',2);
					}
				}elseif($type == 5){
					if(!$params['start_time']){
						$this->ajaxReturn('请填写借款时间!','请填写借款时间!',2);
					}
					if(!$params['money']){
						$this->ajaxReturn('请填写借款金额!','请填写借款金额!',2);
					}
				}
				if($m_examine->where('examine_id = %d',$examine_id)->save()){
					if($type == 3){
						$m_examine_expense = M('ExamineExpense');
						$cate_list = $params['cate_list'];
						//查询原有报销事项id
						$cateids = $m_examine_expense->where('examine_id = %d',$examine_id)->getField('id',true);
						$cate_id = array();
						foreach($cate_list as $k=>$v){
							$data['cate'] = $v['cate'];
							$data['money'] = $v['money'];
							$data['expense_time'] = $v['expense_time'];
							if($v['id']){
								$m_examine_expense->where('id = %d',$v['id'])->save($data);
								$ids[] = $v['id'];
							}else{
								$data['examine_id'] = $examine_id;
								$cate_id[] = $m_examine_expense->add($data);
							}
							$cate_ids = array_merge($ids,$cate_id);
						}
						$count_cate_ids = count($cate_ids);
						$count_cateids = count($cateids);
						if($count_cateids > $count_cate_ids){
							//获取两个数组差集
							$del_ids = array_diff($cateids,$cate_ids);
							foreach($del_ids as $k=>$v){
								$m_examine_expense->where('id = %d',$v)->delete();
							}
						}
					}
					switch($type){
						case 1:$type = '普通审批';break;
						case 2:$type = '请假单';break;
						case 3:$type = '报销单';break;
						case 4:$type = '差旅单';break;
						case 5:$type = '借款单';break;
					}
					$creator = getUserByRoleId(session('role_id'));
					$message_content = $creator['user_name'].'于'.date('Y-m-d',time()).'编辑了'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$_POST['id']).'">'.$params['content'].'</a>';
					$email_content = $creator['user_name'].'于'.date('Y-m-d',time()).'编辑了'.$type.'等待您的批复！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$_POST['id'],'','',true).'">'.$params['content'].'</a>';
					sendMessage($params['examine_role_id'],$message_content,1);
					actionLog($examine_id);
					$this->ajaxReturn('修改成功','修改成功',1);
				}else{
					$this->ajaxReturn('修改失败','修改失败',2);
				}
			}else{
				$this->ajaxReturn('修改失败','修改失败',2);
			}
		}
	}
	//审批详情
	public function view(){
		if($this->isPost()){
			$examine_id = $_POST['id'];
			if(!$examine_id){
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
			$m_examine = M('Examine');
			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if(!$examine_info){
				$this->ajaxReturn('数据不存在或已删除！','数据不存在或已删除！',2);
			}
			//检查权限
			// if(!$this->checkPer($examine_id)){
			// 	$this->ajaxReturn('您没有此权利！','您没有此权利！',2);
			// } 
			$examine_check = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$examine_id))->find(); //审批人是否包括自己
			if($examine_info['type'] == 4){
				$owner_role_id = implode(',',$examine_info['owner_role_id']);//出差人
				if(!in_array($examine_info['creator_role_id'],$this->_permissionRes) && !$examine_check && $examine_info['examine_role_id'] != session('role_id') && !in_array(session('role_id'),$owner_role_id)){
					$this->ajaxReturn('您没有此权利！','您没有此权利！',2);
				}
			}else{
				if(!in_array($examine_info['creator_role_id'],$this->_permissionRes) && !$examine_check && $examine_info['examine_role_id'] != session('role_id')){
					$this->ajaxReturn('您没有此权利！','您没有此权利！',2);
				}
			}
			
			$d_role = D('RoleView');
			//申请人
			$examine_info['create_role_info'] = $d_role->where('role.role_id = %d',$examine_info['creator_role_id'])->field('department_name,user_name,img,role_id')->find();
			//
			//报销单
			if($examine_info['type'] == 3){
				//报销事项
				$m_examine_travel = M('ExamineTravel');
				$examine_travel_list = $m_examine_travel->where('examine_id = %d',$examine_id)->select();
				$examine_info['expense_list'] = $examine_travel_list ? $examine_travel_list : array();
				//报销总计
				$examine_info['money_sum'] = $m_examine_travel->where('examine_id = %d',$examine_id)->sum('money');
			}
			//审批意见
			$m_examine_check = M('ExamineCheck');
			$opinion_list = $m_examine_check->where('examine_id = %d',$examine_id)->field('check_time,is_checked,content,role_id')->select();
			$i = 0;
			foreach($opinion_list as $k=>$v){
				$opinion_list[$i]['role_info'] = $d_role->where('role.role_id = %d',$v['role_id'])->field('department_name,user_name,img,role_id')->find();
				$opinion_list[$i]['create_time'] = $v['check_time'];
				$opinion_list[$i]['opinion'] = $v['content'];
				$opinion_list[$i]['is_agree'] = $v['is_checked'];
				$i++;
			}
			//下一审批人信息
			if($opinion_list && $examine_info['examine_role_id']){
				$opinion_list[$i]['create_time'] = '';
				$opinion_list[$i]['opinion'] = '';
				$opinion_list[$i]['is_agree'] = '3'; //审批中
				$opinion_list[$i]['role_info'] = $d_role->where('role.role_id = %d',$examine_info['examine_role_id'])->field('department_name,user_name,img,role_id')->find();
			}
			//如果没有审批意见，默认为下一审批人信息
			$examine_role_info[0]['create_time'] = $examine_info['create_time'];
			$examine_role_info[0]['is_agree'] = '3';//审批中
			$examine_role_info[0]['opinion'] = '';
			$examine_role_info[0]['role_info'] = $d_role->where('role.role_id = %d',$examine_info['examine_role_id'])->field('department_name,user_name,img,role_id')->find();
			$examine_info['opinion_list'] = $opinion_list ? $opinion_list : $examine_role_info;
			//出差人
			if($examine_info['owner_role_id']){
				if(substr($examine_info['owner_role_id'],-1) == ','){
					$examine_info['owner_role_id'] = substr($examine_info['owner_role_id'],0,-1);
				}
				$owner_role_ids = explode(',',$examine_info['owner_role_id']);
				foreach($owner_role_ids as $k=>$v){
					$res = $d_role->where('role.role_id = %d',$v)->field('role_id,user_name')->find();
					if($res) $owner_list[] = $res;
				}
			}
			if($examine_info['type'] == 4){
				$examine_info['owner_role_list'] = $owner_list ? $owner_list : array();
			}
			//是否有审批权限
			if(session('?admin') || $examine_info['examine_role_id'] == session('role_id')){
				if($examine_info['examine_status'] == 0 || $examine_info['examine_status'] == 1){
					$add_examine = 1;
				}
			}
			$examine_info['add_examine'] = $add_examine ? $add_examine : 0;
			$data['data'] = $examine_info;
			//返回编辑、删除权限
			if($examine_info['examine_status'] == 0){
				$data['permission'] = array('edit'=>1,'delete'=>1);
			}else{
				$data['permission'] = array('edit'=>0,'delete'=>0);
			}
			$data['data'] = $examine_info;
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
	//审批意见(审批进度)
	public function add_examine() {
		if(!in_array(session('role_id'),getPerByAction('examine','add_examine'))){
			$this->ajaxReturn('您没有此权利!','您没有此权利',-2);
		}
		if($this->isPost()){
			$m_examine = M('Examine');
			$m_examine_opinion = M('ExamineCheck');
			$examine_id = $_POST['id'];
			$params = json_decode($_POST['params'],true);
			if(!is_array($params)){
				$this->ajaxReturn('非法的数据格式!','非法的数据格式!',2);
			}
			if(!$examine_id){
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
			$examine = $m_examine->where('examine_id = %d',$examine_id)->find();
			if($examine['examine_status'] == 2 || $examine['examine_status'] == 3){
				$this->ajaxReturn('该审批已经结束！','该审批已经结束！',2);
			}
			if(!session('?admin') && $examine['examine_role_id'] != session('role_id')){
				$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
			}
			
			$where['examine_id'] = $examine_id;
			$m_examine_opinion->create();
			$m_examine_opinion->check_time = time();
			$m_examine_opinion->role_id = session('role_id');
			$m_examine_opinion->examine_id = $examine_id;
			$m_examine_opinion->is_checked = $params['is_agree'];
			$m_examine_opinion->content = $params['opinion'];
			//如果有下一审批人，则审批进行
			if($params['examine_role_id']){
				$m_examine->create();
				$m_examine->update_time = time();
				$m_examine->examine_role_id = $params['examine_role_id'];
				$examine_status = 1;
				$m_examine->examine_status = $examine_status;	//审批状态  审批中
			}else{
				//如果没有下一审批人，则审批结束
				$m_examine->create();
				$m_examine->update_time = time();
				$m_examine->examine_role_id = 0;
				if($params['is_agree'] == 1){
					$examine_status = 2;
					$m_examine->examine_status = $examine_status;	//选择审批结束时   审批状态  通过
				}elseif($params['is_agree'] == 2){
					$examine_status = 3;
					$m_examine->examine_status = $examine_status;	//选择审批结束时   审批状态  未通过
				}
			}
			if($m_examine->where($where)->save()){
				$m_examine_opinion->examine_status = $examine_status;
				if($m_examine_opinion->add()){
					switch($params['type']){
						case 1:$type = '普通审批';break;
						case 2:$type = '请假单';break;
						case 3:$type = '报销单';break;
						case 4:$type = '差旅单';break;
						case 5:$type = '借款单';break;
					}
					$create_role_id = $m_examine ->where($where)->getField('creator_role_id');
					$creator = getUserByRoleId($create_role_id);
					if($examine_status == 2 || $examine_status == 3){
						$message_content = '您申请的内容已被审批！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$params['content'].'</a>';
						$email_content = '您申请的内容已被审批！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id,'','',true).'">'.$params['content'].'</a>';
						//默认发送站内信
						sendMessage($create_role_id,$message_content,1);
					}else{
						$message_content = '您有一个审批待处理！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id).'">'.$params['content'].'</a>';
						$email_content = '您有一个审批待处理！<br/> &nbsp; &nbsp; &nbsp; 内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 申请人：<a class="role_info" rel="'.session('role_id').'" href="javascript:void(0)">'.$creator['user_name'].'</a> ['.$creator['department_name'].' - '.$creator['role_name'].']<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 创建时间:'.date('Y-m-d',time()).'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批类型:'.$type.'<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; 审批内容:<a href="'.U('examine/view','id='.$examine_id,'','',true).'">'.$params['content'].'</a>';
						sendMessage($params['examine_role_id'],$message_content,1);
					}
					$this->ajaxReturn('审核成功！','审核成功！',1);
				}else{
					$this->ajaxReturn('审核失败！','审核失败！',2);
				}
			}else{
				$this->ajaxReturn('审核失败！','审核失败！',2);
			}
		}else{
			$this->ajaxReturn('非法请求','非法请求',2);
		}
	}
	//检查是否有权限
	public function checkPer($examine_id){
		$m_examine = M('Examine');
		if(!session('?admin')){	//非管理员权限限制

			//已审核的人
			$examine_check_info = M('ExamineCheck')->where(array('role_id'=>session('role_id'),'examine_id'=>$examine_id))->find();
			//审核人或自己
			$c_where['creator_role_id'] = session('role_id'); 
			$c_where['examine_role_id'] = session('role_id');
			$c_where['_logic'] = 'or';
			$where['_complex'] = $c_where;
		}
		$where['examine_id'] = $examine_id;
		$info = $m_examine->where($where)->find();
		$creator_role_id = $m_examine->where('examine_id = %d',$examine_id)->getField('creator_role_id');
		//授权判断
		$below_ids = getPerByAction('examine','view');

		if($examine_check_info || $info || in_array($creator_role_id, $below_ids)){
			return true;
		}else{
			return false;
		}
	}
	//审批删除
	public function delete(){
		if($this->isPost()){
			$examine_id = $_REQUEST['id'];
			if(!$examine_id){
				$this->ajaxReturn('参数错误！','参数错误！',2);
			}
			$m_examine = M('Examine');
			$examine_info = $m_examine->where('examine_id = %d',$examine_id)->find();
			if(!$examine_info){
				$this->ajaxReturn('数据不存在或已删除！','数据不存在或已删除！',2);
			}else{
				if($examine_info['examine_status'] != 0){
					$this->ajaxReturn('当前状态不允许删除','当前状态不允许删除',2);
				}elseif(!in_array($examine_info['creator_role_id'],$this->_permissionRes)){
					$this->ajaxReturn('您没有此权利!','您没有此权利!',-2);
				}
			}
			$data = array('is_deleted'=>1, 'delete_role_id'=>session('role_id'), 'delete_time'=>time());
			$m = $m_examine->where('examine_id = %d',$examine_id)->setField($data);
			if($m){
				actionLog($examine_id);
				$this->ajaxReturn('删除成功！','删除成功！',1);
			}else{
				$this->ajaxReturn('删除失败！','删除失败！',2);
			}
		}
	}
}