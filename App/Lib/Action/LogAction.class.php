<?php
/**
*日志模块
*
**/
class LogAction extends Action{
	/**
	*用于判断权限
	*@permission 无限制
	*@allow 登录用户可访问
	*@other 其他根据系统设置
	**/
	public function _initialize(){
		$action = array(
			'permission'=>array('wxadd'),
			'allow'=>array('add', 'delete', 'anly','notepad','getnotepad','mycommont','commentshow','myreply','replyalldel','replydel','viewajax','commun_list')
		);
		B('Authenticate', $action);
	}
	/**
	 * 最初的评论(回复)
	 * @return [type] [description]
	 */
	public function myCommont(){
		if($this->isAjax()){
			$log_id = $this->_post('log_id','intval');
			// $send_role_id = $this->_post('send_role_id','intval');
			$send_role_id = session('role_id');
			$content = $this->_post('content');
			if(!$log_id) $this->ajaxReturn('','当前日志发生跑路现象，暂不支持回复！',3);
			if(!$send_role_id) $this->ajaxReturn('','回复者处于隐身状态哦！别闹！',4);
			if(!$content) $this->ajaxReturn('','回复内容必填哦！',5);
			$m_log = M('Log');//日志表
			$log_info = $m_log->where('log_id = %d',$log_id)->find();
			$receive_role_id = $log_info['role_id'];//接收者role_id
			if(!$receive_role_id){
				$this->ajaxReturn('','该日志不存在或已删除！',6);
			}
			$data['log_id'] = $log_id;
			$data['send_role_id'] = $send_role_id;
			$data['receive_role_id'] = $receive_role_id;//接收者role_id
			$data['content'] = $content;
			$data['create_time'] = time();
			$m_log_talk = M('LogTalk');//日志评论回复表
			$talk_id = $m_log_talk->add($data);
			if($talk_id){
				M('Log')->where('log_id = %d',$log_id)->setField('status',2);
				$sendor = getUserByRoleId($send_role_id);
				$url = U('log/mylog_view','id='.$log_id);
				$message_content = '<a target="_blank" href="'.$url.'">'.$sendor['user_name'].' 评论了你的日志《'.$log_info["subject"].'》</a>';
				sendMessage($receive_role_id,$message_content,1);
				$g_mark = 'wk_'.$talk_id;
				$m_log_talk->where('talk_id = %d',$talk_id)->save(array('g_mark'=>$g_mark));
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','发表失败，程序员正在火速检修！',6);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	//(评论/回复)显示
	public function commentShow(){
		$log_id = $this->_request('log_id','intval');
		$m_log_talk = M('LogTalk');//日志评论回复表
		$m_user = M('User');
		$log_info = M('Log')->where('log_id = %d',$log_id)->find();
		$comment_list = $m_log_talk->where(array('log_id'=>$log_id,'parent_id'=>0))->order('create_time asc')->select();
		foreach ($comment_list as $key => $value) {
			$creator_info = array();
			$creator_info = $m_user->where('role_id = %d',$value['send_role_id'])->field('thumb_path,role_id,name,full_name')->find();
			$comment_list[$key]['img'] = $creator_info['thumb_path'];
			$comment_list[$key]['creator'] = $creator_info;
			$comment_list[$key]['content'] = htmlspecialchars_decode($value['content']);
			
			//是否有删除回复权限
			$comment_list[$key]['delete'] = 0;
			if(session('?admin') || $value['send_role_id'] == session('role_id') || $log_info['role_id'] == session('role_id')){
				$comment_list[$key]['delete'] = 1;
			}
			$comment_list_child = $m_log_talk->where('parent_id =%d and g_mark = "%s"',$value['talk_id'],$value['g_mark'])->select();
			foreach ($comment_list_child as $childkey => $childvalue) {
				$creator_child_info = array();
				$creator_child_info = $m_user->where('role_id = %d',$childvalue['send_role_id'])->field('thumb_path,role_id,name,full_name')->find();
				$comment_list_child[$childkey]['childimg'] = $creator_child_info['thumb_path'];
				$comment_list_child[$childkey]['creator_child'] = $creator_child_info;
				$comment_list_child[$childkey]['content'] = htmlspecialchars_decode($childvalue['content']);
				//是否有删除回复权限
				$comment_list_child[$childkey]['delete'] = 0;
				if(session('?admin') || $childvalue['send_role_id'] == session('role_id') || $log_info['role_id'] == session('role_id')){
					$comment_list_child[$childkey]['delete'] = 1;
				}
			}
			$comment_list[$key]['comment_list_child'] = $comment_list_child;
		}
		$this->current_img = $current_img = M('User')->where('role_id = %d',session('role_id'))->getField('thumb_path');//当前头像	
		$this->assign('comment_list',$comment_list);
		$this->assign('log_id',$log_id);
		$this->display();
	}	
	//添加回复
	public function myReply(){
		if($this->isAjax()){
			$talk_id = $this->_post('talk_id','intval');
			$receive_role_id = $this->_post('receiveid','intval');
			$content = $this->_post('content');			
			if(!$talk_id) $this->ajaxReturn('','当前回复发生跑路现象，暂不支持回复！',3);
			if(!$receive_role_id) $this->ajaxReturn('','当前回复对象发生跑路现象，暂不支持回复！',6);
			if(!$content) $this->ajaxReturn('','回复内容必填哦！',4);
			$m_log_talk = M('LogTalk');//日志评论回复表
			$talk_info = $m_log_talk->where('talk_id = %d',$talk_id)->find();
			$rep_data['parent_id'] = $talk_info['parent_id'] ? $talk_info['parent_id'] : $talk_id;
			$rep_data['log_id'] = $talk_info['log_id'];
			$rep_data['send_role_id'] = session('role_id');
			$rep_data['receive_role_id'] = $receive_role_id;
			$rep_data['content'] = $content;
			$rep_data['create_time'] = time();
			$rep_data['g_mark'] = $talk_info['g_mark'];
			$talk_id = $m_log_talk->add($rep_data);
			if($talk_id){
				$sendor = getUserByRoleId(session('role_id'));
				$url = U('log/mylog_view','id='.$talk_info['log_id']);
				$message_content = '<a target="_blank" href="'.$url.'">'.$sendor['user_name'].' 回复了你的评论</a>';
				sendMessage($receive_role_id,$message_content,1);
				$this->ajaxReturn('','success',1);
			}else{
				$this->ajaxReturn('','发表失败，程序员正在火速检修！',5);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	/**
	 * 评论(回复) 删除
	 * @return [type] true or false
	 * 1全部删除 包括回复的所有 replyAllDel() 2删除单一的回复 replyDel()
	 * 便于后续的其他操作 分开写方法
	 * replyAllDel()   replyDel()
	 */
	public function replyAllDel(){
		if($this->isAjax()){
			$talk_id = $this->_post('id');
			if(!$talk_id) $this->ajaxReturn('','当前被删除项发生跑路现象，暂不支持此操作！',3);
			$m_log_talk = M('LogTalk');//日志评论回复表
			$talkinfo = $m_log_talk->where('talk_id = %d',$talk_id)->find();
			$role_id = M('Log')->where('log_id = %d',$talkinfo['log_id'])->getField('role_id');
			if($talkinfo){
				if($talkinfo['send_role_id'] != session('role_id') && $role_id != session('role_id')){
					$this->ajaxReturn('','sorry,您没有权限删除！',6);
				}else{
					$msg = $m_log_talk->where('g_mark = "%s"',$talkinfo['g_mark'])->delete();
					if($msg){
						$this->ajaxReturn('','success',1);
					}else{
						$this->ajaxReturn('','删除失败，程序员正在火速检修！',5);
					}	
				}							
			}else{
				$this->ajaxReturn('','数据查询失败！',4);
			}
		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}
	public function replyDel(){
		if($this->isAjax()){			
			$talk_id = $this->_post('id');
			if(!$talk_id) $this->ajaxReturn('','当前被删除项发生跑路现象，暂不支持此操作！',3);
			$m_log_talk = M('LogTalk');//日志评论回复表
			$send_role_id = $m_log_talk->where('talk_id = %d',$talk_id)->getField('send_role_id');
			$log_id = $m_log_talk->where('talk_id = %d',$talk_id)->getField('log_id');
			$role_id = M('Log')->where('log_id = %d',$log_id)->getField('role_id');
			if($send_role_id != session('role_id') && $role_id != session('role_id')){
				$this->ajaxReturn('','sorry,您没有权限删除！',5);		
			}else{
				$msg = $m_log_talk->where('talk_id = %d',$talk_id)->delete();
				if($msg){
					$this->ajaxReturn('','success',1);
				}else{
					$this->ajaxReturn('','删除失败，程序员正在火速检修！',4);
				}
			}

		}else{
			$this->ajaxReturn('','跑神了-D',2);
		}
	}

	//沟通日志添加
	public function add(){
		if($this->isPost()){
			if($_POST['r']){
				$r = trim($_POST['r']);
				$module = trim($_POST['module']);
				$model_id = intval($_POST['id']);
				//列表快捷添加沟通记录
				if ($module == 'customer' && $_POST['business_id']) {
					$r = 'rBusinessLog';
					$module = 'business';
					$model_id = intval($_POST['business_id']);
				}
				$m_r = M($r);
				$m_log = M('Log');
				$m_log->create();
				$m_log->category_id = 1;
				$m_log->create_date = time();
				$m_log->update_date = time();
				$m_log->role_id = session('role_id');
				$m_log->status_id = intval($_POST['status_id']);
				if ($_POST['nextstep_time']) {
					$m_log->nextstep_time = strtotime($_POST['nextstep_time']);
				}
				$ajax_data = array();
				if($log_id = $m_log->add()){
					if ($_POST['nextstep_time']) {
						//关联日程
						$event_res = dataEvent(trim($_POST['content']),strtotime($_POST['nextstep_time']),$module,$model_id);
					}
					//保存为沟通模板
					if (intval($_POST['save_reply'] == 1)) {
						$m_log_reply = M('LogReply');
						$m_log_reply->type = 2;
						$m_log_reply->status_id = intval($_POST['status_id']);
						$m_log_reply->content = trim($_POST['content']);
						$m_log_reply->role_id = session('role_id');
						$m_log_reply->create_time = time();
						$m_log_reply->update_time = time();
						$reply_id = $m_log_reply->add();
					}

					$m_id = $module . '_id';
					$data['log_id'] = $log_id;
					$data[$m_id] = $model_id;
					if($m_r->add($data)){
						//相关模块更新时间
						if($module == 'customer' || $module == 'business' || $module == 'leads'){
							if($module == 'business'){
								if (strtotime($_POST['nextstep_time'])) {
									$business_res = M('Business')->where(array('business_id'=>$model_id))->setField('nextstep_time',strtotime($_POST['nextstep_time']));
								}
								$module = 'customer';
								$model_id = M('Business')->where(array('business_id'=>$model_id))->getField('customer_id');
							}
							$pk_id = M($module)->getPk();
							if($module == 'leads'){
								$m_leads = M('Leads');
								$leads_data = array();
								$leads_data['nextstep'] = trim($_POST['content']);
								if (strtotime($_POST['nextstep_time'])) {
									$leads_data['nextstep_time'] = strtotime($_POST['nextstep_time']);
								}
								$leads_data['update_time'] = time();
								$leads_data['have_time'] = time();
								$first_time = $m_leads->where('leads_id = %d',$model_id)->getField('first_time');
								if(!$first_time){
									$leads_data['first_time'] = time();
								}
								$res = M('Leads')->where('leads_id = %d',$model_id)->save($leads_data);
							}elseif($module == 'customer'){
								$customer_data = array();
								//下次联系时间
								if (strtotime($_POST['nextstep_time'])) {
									$customer_data['nextstep_time'] = strtotime($_POST['nextstep_time']);
								}
								$customer_data['update_time'] = time();
								$res = M('Customer')->where('customer_id = %d',$model_id)->save($customer_data);
							}else{
								$res = M($module)->where("$pk_id = %d", $model_id)->setField('update_time',time());
							}
						}
						$m_user = M('User');
						$ajax_data['owner'] = $m_user->where('role_id = %d', session('role_id'))->field('full_name,role_id,thumb_path')->find();
						if (!$ajax_data['owner']['thumb_path']) {
							$ajax_data['owner']['thumb_path'] = '__PUBLIC__/img/avatar_default.png';
						}
						$ajax_data['date'] = date('Y-m-d H:i', time());
						$ajax_data['log_id'] = $log_id;
						$this->ajaxReturn($ajax_data,'',1);
					}else{
						$this->ajaxReturn('','参数错误！',0);
					}
				}else{
					$this->ajaxReturn('','参数错误！',0);
				}
			}else{
				$this->ajaxReturn('','参数错误！',0);
			}
		} elseif ($_GET['r'] && $_GET['module'] && $_GET['id']) {
			$this->r = $_GET['r'];
			$this->module = trim($_GET['module']);
			$this->module_name = $_GET['module'].'_id';
			$this->model_id = intval($_GET['id']);

			//自定义快捷回复
			$this->status_list = M('LogStatus')->select();
			$where_reply = array();
			$where_reply['type']  = 1;
			$where_reply['role_id']  = session('role_id');
			$where_reply['_logic'] = 'or';
			$map['_complex'] = $where_reply;
			$reply_list = M('LogReply')->where($map)->select();
			foreach ($reply_list as $k=>$v) {
				$reply_list[$k]['str_content'] = cutString($v['content'],'12');
			}
			$this->reply_list = $reply_list;
			if ($_GET['module'] == 'customer') {
				//相关商机
				$this->business_list = M('Business')->where(array('customer_id'=>intval($_GET['id']),'name'=>array('neq','..'),'code'=>array('neq','')))->field('name,business_id,code')->select();
				//历史沟通日志（最新1条）
				$log_id = 0;
				$log_count = 0; //沟通日志总数
				$customer_log_count = 0; //客户下沟通日志总数
				$business_log_count = 0; //商机下沟通日志总数

				$customer_log_id = M('rCustomerLog')->where('customer_id = %d',intval($_GET['id']))->max('log_id');
				$customer_log_count = M('rCustomerLog')->where('customer_id = %d',intval($_GET['id']))->count();
				//商机下沟通日志
				$customer_business_ids = M('Business')->where('customer_id = %d', intval($_GET['id']))->getField('business_id', true);
				if ($customer_business_ids) {
					$business_log_id = M('rBusinessLog')->where('business_id in (%s)', implode(',', $customer_business_ids))->max('log_id');
					$business_log_count = M('rBusinessLog')->where('business_id in (%s)', implode(',', $customer_business_ids))->count();
				}
				$log_count = $customer_log_count+$business_log_count;
				//比较取最大值
				if ($customer_log_id && $business_log_id) {
					if ($customer_log_id > $business_log_id) {
						$log_id = $customer_log_id;
					} else {
						$log_id = $business_log_id;
					}
				} elseif ($customer_log_id || $business_log_id) {
					if ($customer_log_id) {
						$log_id = $customer_log_id;
					} else {
						$log_id = $business_log_id;
					}
				}

				if ($log_id) {
					$log_info = M('Log')->where(array('log_id'=>$log_id))->find();
					$log_info['user'] = M('User')->where('role_id = %d',$log_info['role_id'])->field('full_name,thumb_path,role_id')->find();
					if (!$log_info['user']['thumb_path']) {
						$log_info['user']['thumb_path'] = '__PUBLIC__/img/avatar_default.png';
					}
					//沟通类型
					$log_info['status_name'] = M('LogStatus')->where('id = %d',$log_info['status_id'])->getField('name');
				}
				$this->log_info = $log_info ? $log_info : array();
				$this->log_count = $log_count ? $log_count : 0;
			}
			$this->display();
		} else {
			alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}

	//删除沟通日志
	public function delete(){
		$log_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if (0 == $log_id){
			$this->ajaxReturn(0);
		} else {
			if (isset($_GET['r']) && isset($log_id)) {
				$m_r = M($_GET['r']);
				$m_log = M('log');
				$msg = $m_r->where('log_id = %d',$log_id)->delete();
				$customer_id = $m_r->where('log_id = %d',$log_id)->getField('customer_id');
				if ($msg) {
					if ($m_log->where('log_id = %d',$log_id)->delete()) {
						if($customer_id){
							M('customer')->where('customer_id =%d',$customer_id)->setField('update_time',time());
						}
						$this->ajaxReturn(1);
					} else {
						$this->ajaxReturn(0);
					}
				} else {
					$this->ajaxReturn(0);
				}
			} elseif (empty($_GET['r']) && isset($log_id)){
				$m_log = M('Log');
				if ($m_log->where('log_id = %d',$log_id)->delete()){
					$this->ajaxReturn('','删除成功',1);
				} else {
					$this->ajaxReturn(0);
				}
			}
		}
	}

	/**
	*日志列表页（默认页面）
	*
	**/
	public function index(){
		$m_log = M('Log');
		$m_comment = M('Comment');
		$m_r_file_log = M('RFileLog');
		$where = array();
		$params = array();

		$order = "create_date desc";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		$below_ids = getPerByAction('log','index');
		$module = isset($_GET['module']) ? trim($_GET['module']) : '';
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		switch ($by) {
			case 'today' : $where['create_date'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
			case 'week' : $where['create_date'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
			case 'month' : $where['create_date'] = array('gt',strtotime(date('Y-m-01', time()))); break;
			case 'add' : $order = 'create_date desc';  break;
			case 'update' : $order = 'update_date desc';  break;
			case 'sub' : $where['role_id'] = array('in',implode(',', $below_ids)); break;
			case 'me' : $where['role_id'] = session('role_id'); break;
			default :  $where['role_id'] = array('in',implode(',', $below_ids)); break;
		}

		if ($_GET['r'] && $_GET['module']){
			$m_r = M($_GET['r']);
			$log_ids = $m_r->getField('log_id', true);
			$where['log_id'] = array('in', implode(',', $log_ids));
		}
		if($_GET['role_id']){
			if(in_array(intval($_GET['role_id']),$below_ids)){
				$where['role_id'] = intval($_GET['role_id']);
			}else{
				$where['role_id'] = session('role_id');
			}
		}
		if (!isset($where['role_id'])) {
			$where['role_id'] = array('in',implode(',', $below_ids));
		}
		if(intval($_GET['type'])){
			$where['category_id'] = intval($_GET['type']);
		}else{
			$where['category_id'] = array('neq',1);
		}
		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);

			$condition = empty($_REQUEST['condition']) ? 'eq' : trim($_REQUEST['condition']);
			if	('create_date' == $field || 'update_date' == $field) {
				$search = strtotime($search);
			} elseif ('role_id' == $field) {
				$condtion = "is";
			}
			$field = trim($_REQUEST['field']) == 'all' ? 'subject|content' : $_REQUEST['field'];
			switch ($_REQUEST['condition']) {
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
			$params = array('field='.$_REQUEST['field'], 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
			//过滤不在权限范围内的role_id
			if(trim($_REQUEST['field']) == 'role_id'){
				if(!in_array(trim($search),$below_ids)){
					$where['role_id'] = array('in',$below_ids);
				}
			} 
		}
		if($_GET['listrows']){
			$listrows = $_GET['listrows'];
			$params[] = "listrows=" . trim($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=15";
		}
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		$count = $m_log->where($where)->count();
		$p_num = ceil($count/$listrows);
		if($p_num<$p){
			$p = $p_num;
		}
		$list = $m_log->where($where)->page($p.','.$listrows)->order($order)->select();
		import("@.ORG.Page");
		$Page = new Page($count,$listrows);
		if (!empty($_REQUEST['by'])){
			$params['by'] = 'by=' . trim($_REQUEST['by']);
		}
		if (!empty($_REQUEST['r']) && !empty($_REQUEST['module'])) {
			$params['r'] = 'r=' . trim($_REQUEST['r']);
			$params['module'] = 'module=' . trim($_REQUEST['module']);
		}
		if (!empty($_REQUEST['type'])) {
			$params['type'] = 'type=' . trim($_REQUEST['type']);
		}

		$this->parameter = implode('&', $params);
		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}

		$Page->parameter = implode('&', $params);
		$show = $Page->show();
		$this->assign('page',$show);
		$m_log_category = M('LogCategory');
		foreach($list as $key=>$value){
			$list[$key]['creator'] = getUserByRoleId($value['role_id']);
			if($m_comment->where("module='log' and module_id=%d", $value['log_id'])->select()){
				$list[$key]['is_comment'] = 1;
			}
			$list[$key]['category_name'] = $m_log_category->where('category_id = %d',$value['category_id'])->getField('name');
			$list[$key]['content'] = strip_tags($value['content']);
			//附件
			$file_count = 0;
			$file_count = $m_r_file_log->where('log_id = %d',$value['log_id'])->count();
			$list[$key]['file_count'] = $file_count ? $file_count : 0;
		}

		$this->category_list = M('LogCategory')->order('order_id')->select();
		//获取下级和自己的岗位列表,搜索用
		$d_role_view = D('RoleView');
		$this->role_list = $d_role_view->where('role.role_id in (%s)', implode(',', $below_ids))->select();
		$this->assign("listrows",$listrows);
		$this->assign('list',$list);
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*查看日志详情
	*
	**/
	public function mylog_view(){
		if($_GET['id']){
			$log_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
			$m_log = M('Log');
			$log = $m_log->where('log_id = %d', $log_id)->find();
			if(!$log){
				alert('error','日志不存在或已删除！', $_SERVER['HTTP_REFERER']);
			}
			if (!in_array($log['role_id'],getPerByAction('log',ACTION_NAME))){
				alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);
			}
			//日志状态
			if($log['role_id'] != session('role_id') && $log['status'] < 1){
				$m_log->where('log_id = %d', $log_id)->setField('status',1);
			}
			//查询今日数据
			if($log['category_id'] == 4){
				$start_time = strtotime(date('Y-m-d',$log['create_date']));
				$end_time = strtotime(date('Y-m-d',$log['create_date']))+86400;
				$create_time = array('between',array($start_time,$end_time));
				$customer_count = M('Customer')->where(array('creator_role_id'=>$log['role_id'],'is_deleted'=>0,'create_time'=>$create_time))->count();
				$contacts_count = M('Contacts')->where(array('creator_role_id'=>$log['role_id'],'is_deleted'=>0,'create_time'=>$create_time))->count();
				$business_count = M('Business')->where(array('creator_role_id'=>$log['role_id'],'is_deleted'=>0,'create_time'=>$create_time))->count();
				$log_count = M('Log')->where(array('role_id'=>$log['role_id'],'category_id'=>1,'create_date'=>$create_time))->count();
				$contract_ids = M('Contract')->where(array('owner_role_id'=>$log['role_id'],'is_checked'=>1,'create_time'=>$create_time))->getField('contract_id',true);
				$contract_count = count($contract_ids);
				//回款金额（合同签约人的收款单金额）
				$receivables_ids = M('Receivables')->where(array('contract_id'=>array('in',$contract_ids)))->getField('receivables_id',true);
				$receivingorder_sum = M('Receivingorder')->where(array('receivables_id'=>array('in',$receivables_ids),'is_deleted'=>0,'status'=>1))->sum('money');
				$receivingorder_sum = $receivingorder_sum ? $receivingorder_sum : 0;

				$anly_count = array('customer_count'=>$customer_count,'contacts_count'=>$contacts_count,'business_count'=>$business_count,'log_count'=>$log_count,'contract_count'=>$contract_count,'receivingorder_sum'=>$receivingorder_sum);
				$this->anly_count = $anly_count;
			}
			//end
			$m_customer = M('Customer');
			$m_r_customer_log = M('RCustomerLog');
			$m_contacts = M('Contacts');
			$m_r_contacts_log = M('RContactsLog');
			$m_business = M('Business');
			$m_r_business_log = M('RBusinessLog');
			$m_leads = M('Leads');
			$m_r_leads_log = M('RLeadsLog');
			$m_product = M('Product');
			$m_r_product_log = M('RLogProduct');

			$file_ids = M('rFileLog')->where('log_id = %d', $log_id)->getField('file_id', true);
			$log['file'] = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
			if(!empty($file_ids)){
				foreach ($log['file'] as $key=>$value) {
					// $log['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
					$log['file'][$key]['size'] = ceil($value['size']/1024);
					/*判断文件格式 对应其图片*/
					$log['file'][$key]['pic'] = show_picture($value['name']);
					$log['file'][$key]['format'] = getExtension($value['name']);
				}
			}
			$log['file_count'] = count($file_ids);
			$log['creator'] = getUserByRoleId($log['role_id']);
			$log['category_name'] = M('LogCategory')->where('category_id = %d',$log['category_id'])->getField('name');
			
			//Log related module
			$r_customer_log = $m_r_customer_log->where('log_id = %d',$log_id)->find();
			if(!empty($r_customer_log)){
				$customer = $m_customer->where('customer_id = %d',$r_customer_log['customer_id'])->find();
				$log['customer_id'] = $customer['customer_id'];
				$log['customer_name'] = $customer['name'];
			}
			$r_contacts_log = $m_r_contacts_log->where('log_id = %d',$log_id)->find();
			if(!empty($r_contacts_log)){
				$contacts = $m_contacts->where('contacts_id = %d',$r_contacts_log['contacts_id'])->find();
				$log['contacts_id'] = $contacts['contacts_id'];
				$log['contacts_name'] = $contacts['name'];
			}
			$r_business_log = $m_r_business_log->where('log_id = %d',$log_id)->find();
			if(!empty($r_business_log)){
				$business = $m_business->where('business_id = %d',$r_business_log['business_id'])->find();
				$log['business_id'] = $business['business_id'];
				$log['business_name'] = $business['name'];
			}
			$r_leads_log = $m_r_leads_log->where('log_id = %d',$log_id)->find();
			if(!empty($r_leads_log)){
				$leads = $m_leads->where('leads_id = %d',$r_leads_log['leads_id'])->find();
				$log['leads_id'] = $leads['leads_id'];
				$log['leads_name'] = $leads['name'];
			}
			$r_product_log = $m_r_product_log->where('log_id = %d',$log_id)->find();
			if(!empty($r_product_log)){
				$product = $m_product->where('product_id = %d',$r_product_log['product_id'])->find();
				$log['product_id'] = $product['product_id'];
				$log['product_name'] = $product['name'];
			}
			if (in_array($log['role_id'], getSubRoleId(false))) {
				if(!($log['comment_role_id'] > 0)){
					$this->comment_role_id = session('role_id');
				}
			}
			if(intval($_GET['type'])){
				$condition['category_id'] = intval($_GET['type']);
			}else{
				$log['category_id'] == '1' ? $condition['category_id'] = array('eq',1) : $condition['category_id'] = array('neq',1);
			}
			$below_ids = getSubRoleId(false);
			$by = isset($_GET['by']) ? trim($_GET['by']) : '';
			switch ($by) {
				case 'today' : $condition['create_date'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
				case 'week' : $condition['create_date'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
				case 'month' : $condition['create_date'] = array('gt',strtotime(date('Y-m-01', time()))); break;
				case 'add' : $order = 'create_date desc';  break;
				case 'update' : $order = 'update_date desc';  break;
				case 'sub' : $condition['role_id'] = array('in',implode(',', $below_ids)); break;
				case 'me' : $condition['role_id'] = session('role_id'); break;
			}
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$terms = empty($_REQUEST['condition']) ? 'eq' : trim($_REQUEST['condition']);
				if	('create_date' == $field || 'update_date' == $field) {
					$search = strtotime($search);
				}
				switch ($terms) {
					case "is" : $condition[$field] = array('eq',$search);break;
					case "isnot" :  $condition[$field] = array('neq',$search);break;
					case "contains" :  $condition[$field] = array('like','%'.$search.'%');break;
					case "not_contain" :  $condition[$field] = array('notlike','%'.$search.'%');break;
					case "start_with" :  $condition[$field] = array('like',$search.'%');break;
					case "end_with" :  $condition[$field] = array('like','%'.$search);break;
					case "is_empty" :  $condition[$field] = array('eq','');break;
					case "is_not_empty" :  $condition[$field] = array('neq','');break;
					case "gt" :  $condition[$field] = array('gt',$search);break;
					case "egt" :  $condition[$field] = array('egt',$search);break;
					case "lt" :  $condition[$field] = array('lt',$search);break;
					case "elt" :  $condition[$field] = array('elt',$search);break;
					case "eq" : $condition[$field] = array('eq',$search);break;
					case "neq" : $condition[$field] = array('neq',$search);break;
					case "between" : $condition[$field] = array('between',array($search-1,$search+86400));break;
					case "nbetween" : $condition[$field] = array('not between',array($search,$search+86399));break;
					case "tgt" :  $condition[$field] = array('gt',$search+86400);break;
					default : $condition[$field] = array('eq',$search);
				}
			}
			//上一篇
			$condition['role_id'] = array('in',implode(',', getSubRoleId()));
			$p_condition = $condition;
			$p_condition['log_id'] = array('gt',$log_id);
			$pre = M('log')->where($p_condition)->order('create_date asc')->limit(1)->find();
			if($pre){
				$this->pre_href = U('log/mylog_view', 'id='.$pre['log_id'].'&type='.$_GET['type'].'&by='.$by.'&field='.$field.'&condition='.$terms.'&search='.$search);
			}
			//下一篇
			$n_condition = $condition;
			$n_condition['log_id'] = array('lt',$log_id);

			$next = M('Log')->where($n_condition)->order('create_date desc')->limit(1)->find();
			if($next){
				$this->next_href = U('log/mylog_view', 'id='.$next['log_id'].'&type='.$_GET['type'].'&by='.$by.'&field='.$field.'&condition='.$terms.'&search='.$search);
			}
			$this->log = $log;
			// $this->comment_list = D('CommentView')->where('module = "log" and module_id = %d', $log['log_id'])->order('comment.create_time desc')->select();
			$this->alert = parseAlert();
			$this->display();
		}else{
			alert('error', L('PARAMETER_ERROR'), $_SERVER['HTTP_REFERER']);
		}
	}

	/**
	 * 日志详情加载（列表）
	 * @param 
	 * @author
	 * @return 
	 */
	public function viewAjax(){
		$log_id = intval($_GET['log_id']);
		$m_log = M('Log');
		$log = $m_log->where('log_id = %d', $log_id)->find();
		$error_message = '';
		if(!$log){
			$error_message = '日志不存在或已删除！';
		}
		if (!in_array($log['role_id'], getPerByAction('log','mylog_view'))){
			$error_message = L('HAVE NOT PRIVILEGES');
		} 
		//日志附件
		$file_ids = M('rFileLog')->where('log_id = %d', $log_id)->getField('file_id', true);
		$log['file'] = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
		if(!empty($file_ids)){
			foreach ($log['file'] as $key=>$value) {
				$log['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
				$log['file'][$key]['size'] = ceil($value['size']/1024);
				/*判断文件格式 对应其图片*/
				$log['file'][$key]['pic'] = show_picture($value['name']);
			}
		}
		$log['file_count'] = count($file_ids);
		$log['category_name'] = M('LogCategory')->where('category_id = %d',$log['category_id'])->getField('name');
		//计算星期
		$log['week_name'] = getTimeWeek($log['create_date']);
		$this->log = $log;
		$this->error_message = $error_message;
		$this->display();
	}

	/**
	*修改日志
	*
	**/
	public function mylog_edit(){
		if($_GET['id']){
			//start
			$log_id = $_GET['id'];
			$m_log = M('Log');
			$log = $m_log->where('log_id = %d', $_GET['id'])->find();
			if($log){
				$file_id_array = M('RFileLog')->where('log_id = %d',$log_id)->getField('file_id',true);
				$log['file_list'] = M('File')->where('file_id in (%s)',implode(',',$file_id_array))->select();
				foreach ($log['file_list'] as $key => $value) {
					$log['file_list'][$key]['size'] = ceil($value['size']/1024);
					$log['file_list'][$key]['pic'] = show_picture($value['name']);
				}
			}else{
				alert('error','查看数据为空！',$_SERVER['HTTP_REFERER']);
			}	

			//end
			if (!in_array($log['role_id'], getSubRoleId())) alert('error', L('HAVE NOT PRIVILEGES'), $_SERVER['HTTP_REFERER']);		
			$this->log =  $log;
			//查询今日数据
			$start_time = strtotime(date('Y-m-d',$log['create_date']));
			$end_time = strtotime(date('Y-m-d',$log['create_date']))+86400;
			$create_time = array('between',array($start_time,$end_time));
			$customer_count = M('Customer')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$contacts_count = M('Contacts')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$business_count = M('Business')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$log_count = M('Log')->where(array('role_id'=>session('role_id'),'category_id'=>1,'create_date'=>$create_time))->count();
			$contract_ids = M('Contract')->where(array('owner_role_id'=>session('role_id'),'is_checked'=>1,'create_time'=>$create_time))->getField('contract_id',true);
			$contract_count = count($contract_ids);
			//回款金额（合同签约人的收款单金额）
			$receivables_ids = M('Receivables')->where(array('contract_id'=>array('in',$contract_ids)))->getField('receivables_id',true);
			$receivingorder_sum = M('Receivingorder')->where(array('receivables_id'=>array('in',$receivables_ids),'is_deleted'=>0,'status'=>1))->sum('money');
			$receivingorder_sum = $receivingorder_sum ? $receivingorder_sum : 0;

			$anly_count = array('customer_count'=>$customer_count,'contacts_count'=>$contacts_count,'business_count'=>$business_count,'log_count'=>$log_count,'contract_count'=>$contract_count,'receivingorder_sum'=>$receivingorder_sum);
			$this->anly_count = $anly_count;
			//end
			$this->alert = parseAlert();
			$this->jump_url = $_SERVER['HTTP_REFERER'];
			$this->display();
		} elseif ($_POST['submit']){
			$log_id = $this->_post('log_id');
			if(!$log_id) alert('error', '参数错误！', $_SERVER['HTTP_REFERER']);
			$log = M('Log');
			$log -> create();
			$log ->update_date = time();
			if($log->save()){
				if($_POST['file']){
					$m_file_log = M('RFileLog');
					foreach($_POST['file'] as $v){
						$file_info = $m_file_log->where('file_id = %d',$v)->find();
						if(!$file_info){
							$file_data = array();
							$file_data['log_id'] = $log_id;
							$file_data['file_id'] = $v;
							$m_file_log->add($file_data);
						}
					}
				}
				alert('success', L('EDIT_LOG_SUCCESS'), $_POST['jump_url']);
			}else{
				alert('error', L('EDIT_LOG_FAILED'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error', '程序跑路了，啊哈！', $_SERVER['HTTP_REFERER']);
		}
	}

	
	/**
	*添加日志
	*
	**/
	public function mylog_add(){
		if($this->isPost()){
			$category_id = $_POST['category_id'];
			if($category_id == 1){
				if(!trim($_POST['subjectbylog'])) alert('error','标题不能为空',$_SERVER['HTTP_REFERER']);
				if(!trim($_POST['contentbylog'])) alert('error','内容不能为空',$_SERVER['HTTP_REFERER']);
			}else{
				if(!trim($_POST['subject'])) alert('error',L('NEED_LOG_TITLE'),$_SERVER['HTTP_REFERER']);
				if(!trim($_POST['content'])) alert('error',L('NEED_LOG_CONTENT'),$_SERVER['HTTP_REFERER']);
			}
			if($category_id == 1){
				$m_log = M('Log');
				$m_log->create();
				if(intval($_POST['business_id']) || intval($_POST['task_id']) || intval($_POST['product_id']) || intval($_POST['customer_id'])){
					$m_log->category_id = 1;
					$m_log->subject = $_POST['subjectbylog'];
					$m_log->content = $_POST['contentbylog'];
					$m_log->create_date = time();
					$m_log->update_date = time();
					$m_log->role_id = session('role_id');
					$m_log->log_type = 1; //1为文本加附件形式
					if($log_id = $m_log->add()){
						actionLog($log_id);
						$data['business_id'] = intval($_POST['business_id']);
						$data['task_id'] = intval($_POST['task_id']);
						$data['product_id'] = intval($_POST['product_id']);
						$data['customer_id'] = intval($_POST['customer_id']);
						$data['log_id'] = $log_id;
						if ($data['business_id']) {
							M('RBusinessLog')->add($data);
						}
						if ($data['task_id']) {
							M('RLogTask')->add($data);
						}
						if ($data['product_id']) {
							M('RLogProduct')->add($data);
						}
						if ($data['customer_id']) {
							M('RCustomerLog')->add($data);
						}
						if($_POST['submit'] == L('SAVE')){
							$url = intval($_POST['category_id']) == 1 ? U('log/anly') : U('log/index');
							alert('success',L('ADD SUCCESS', array(L('LOG'))),$url);
						}else{
							alert('success',L('ADD SUCCESS', array(L('LOG'))),$_SERVER['HTTP_REFERER']);
						}
					}else{
						alert('error',L('ADD_LOG_FAILED'),$_SERVER['HTTP_REFERER']);
					}
				}else{
					alert('error','请选择沟通日志相关模块',$_SERVER['HTTP_REFERER']);
				}
			}else{
				$log = M('Log');
				$log->create();
				$log->create_date = time();
				$log->update_date = time();
				$log->role_id = session('role_id');
				if($log_id = $log->add()){
					//相关附件
					if($_POST['file']){
						$m_file_log = M('RFileLog');
						foreach($_POST['file'] as $v){
							$file_data = array();
							$file_data['log_id'] = $log_id;
							$file_data['file_id'] = $v;
							$m_file_log->add($file_data);
						}
					}
					actionLog($log_id);
					if($_POST['submit'] == L('SAVE')){
						$url = intval($_POST['category_id']) == 1 ? U('log/anly') : U('log/index');
						alert('success',L('ADD SUCCESS', array(L('LOG'))),$url);
					}else{
						alert('success',L('ADD SUCCESS', array(L('LOG'))),$_SERVER['HTTP_REFERER']);
					}
				}else{
					alert('error',L('ADD_LOG_FAILED'),$_SERVER['HTTP_REFERER']);
				}
			}
		}else{
			//查询今日数据
			$start_time = strtotime(date('Y-m-d'));
			$end_time = strtotime(date('Y-m-d'))+86400;
			$create_time = array('between',array($start_time,$end_time));
			$customer_count = M('Customer')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$contacts_count = M('Contacts')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$business_count = M('Business')->where(array('creator_role_id'=>session('role_id'),'is_deleted'=>0,'create_time'=>$create_time))->count();
			$log_count = M('Log')->where(array('role_id'=>session('role_id'),'category_id'=>1,'create_date'=>$create_time))->count();
			$contract_ids = M('Contract')->where(array('owner_role_id'=>session('role_id'),'is_checked'=>1,'create_time'=>$create_time))->getField('contract_id',true);
			$contract_count = count($contract_ids);
			//回款金额（合同签约人的收款单金额）
			$receivables_ids = M('Receivables')->where(array('contract_id'=>array('in',$contract_ids)))->getField('receivables_id',true);
			$receivingorder_sum = M('Receivingorder')->where(array('receivables_id'=>array('in',$receivables_ids),'is_deleted'=>0,'status'=>1))->sum('money');
			$receivingorder_sum = $receivingorder_sum ? $receivingorder_sum : 0;

			$anly_count = array('customer_count'=>$customer_count,'contacts_count'=>$contacts_count,'business_count'=>$business_count,'log_count'=>$log_count,'contract_count'=>$contract_count,'receivingorder_sum'=>$receivingorder_sum);
			$this->anly_count = $anly_count;
			$this->current_time = time();
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	*删除商机、产品、客户、线索、日程、任务、联系人沟通日志
	*
	**/
	public function log_delete() {
		$m_log = M('Log');
		if ($_GET['id']){
			$log_id = intval($_GET['id']);
			$log_info = $m_log->where('log_id = %d',$log_id)->find();
			if (!in_array($log_info['role_id'], getSubRoleId())){
				alert('error', L('HAVE NOT PRIVILEGES'), U('log/index'));
			}
			if($m_log->where('log_id = %d',$log_id)->delete()){
				actionLog($log_id);
				alert('success', L('DELETED SUCCESSFULLY'), U('log/index'));
			}
		} elseif (is_array($_POST['log_id'])) {
			$log_ids = implode(',', $_POST['log_id']);
			if($m_log->where('log_id in (%s)', $log_ids)->delete()){
				$this->ajaxReturn('',L('DELETE_RELATED_LOG_SUCCESS'),1);
			} else {
				$this->ajaxReturn('',L('DELETE_RELATED_LOG_FAILED'),0);
			}
		}

	}

	/**
	*自定义字段，日志类型列表
	*
	**/
	public function category(){
		$m_category = M('LogCategory');
		$this->category_list = $m_category->order('order_id')->select();
		$this->alert=parseAlert();
		$this->display();
	}

	/**
	*增加日志类别
	*
	**/
	public function categoryAdd(){
		if ($this->isPost()) {
			$m_category = M('LogCategory');
			if($m_category->create()){
				if ($m_category->add()) {
					alert('success', L('ADD SUCCESS', array(L('LOG'))), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('ADD_FAILED_CONTACT_ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error', L('ADD_FAILED_CONTACT_ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	*修改日志类别
	*
	**/
	public function categoryEdit(){
		$m_category = M('LogCategory');
		if ($this->isGet()) {
			$category_id = intval(trim($_GET['id']));
			$this->log_category = $m_category->where('category_id = %d', $category_id)->find();
			$this->display();
		} else {
			if ($m_category->create()) {
				if ($m_category->save()) {
					alert('success', L('EDIT_LOG_SUCCESS'), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('DATA_NO_MODIFIED'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error', L('MODIFY_FAILED_CONTACT_ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
			}
		}
	}

	/**
	*删除日志类别
	*
	**/
	public function categoryDelete(){
		if ($_POST['category_id']) {
			$id_array = $_POST['category_id'];
			if (M('Log')->where('category_id <> 1 and category_id in (%s)', implode(',', $id_array))->select()) {
				alert('error', L('DELETE_FAILED_PLEASE_DELETE_ONE_BY_ONE'), $_SERVER['HTTP_REFERER']);
			} else {
				if (M('LogCategory')->where('category_id in (%s)', implode(',', $id_array))->delete()) {
					alert('success', L('DELETED SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('DELETE_RELATED_LOG_FAILED'), $_SERVER['HTTP_REFERER']);
				}
			}
		} elseif($_POST['old_id']){
			$old_id = intval($_POST['old_id']);
			$new_id = intval($_POST['new_id']);
			if($old_id && $new_id){
				if (M('LogCategory')->where('category_id <> 1 category_id = %d', $old_id)->delete()) {
					M('Log')->where('category_id = %d', $old_id)->setField('category_id', $new_id);
					M('LogCategory')->where('category_id = %d', $old_id)->setField('category_id', $new_id);
					alert('success', L('DELETED SUCCESSFULLY'), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('MODULE_LOG_IS_SYSTEM_FIELDS_CAN_NOT_BE_DELETED'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('DELETE_FAILED_FOR_INVALIDATE_PARAMETER'), $_SERVER['HTTP_REFERER']);
			}
		} else {
			$old_id = intval(trim($_GET['id']));
			$this->old_id = $old_id;
			$this->statusList = M('LogCategory')->where('category_id <> %d', $old_id)->select();
			$this->display();
		}
	}

	/**
	*日志类别排序
	*
	**/
	public function categorySort(){
		if ($this->isGet()) {
			$status = M('LogCategory');
			$a = 0;
			foreach (explode(',', $_GET['postion']) as $v) {
				$a++;
				$status->where('category_id = %d', $v)->setField('order_id',$a);
			}
			$this->ajaxReturn('1', L('SAVE_SUCCESSFUL'), 1);
		} else {
			$this->ajaxReturn('0', L('SAVE_FAILED'), 1);
		}
	}

	/**
	 * 获取便笺
	 **/
	public function getNotepad(){
		$m_note = M('note');
		$note = $m_note->where('role_id = %d', session('role_id'))->order('note_id asc')->getField('content');
		$this->ajaxReturn($note,'success',1);
	}

	/**
	 * 写入便笺
	 **/
	public function notepad(){
		$content = empty($_POST['content']) ? '' : $_POST['content'];
		$m_note = M('note');
		$note = $m_note->where('role_id = %d', session('role_id'))->find();
		if($note){
			$result = $m_note->where('role_id = %d', session('role_id'))->save(array('content'=>$content, 'update_time'=>time()));
		}else{
			$result = $m_note->add(array('role_id'=>session('role_id'),'content'=>$content, 'update_time'=>time()));
		}
		if($result){
			$this->ajaxReturn('','success',1);
		}else{
			$this->ajaxReturn('','error',0);
		}
	}

	/**
	 * 日志统计
	 **/
	public function analytics(){
		$content_id = $_GET['content_id'] ? intval($_GET['content_id']) : 1;
		//权限范围
		$below_ids = getPerByAction(MODULE_NAME,ACTION_NAME);
		$m_log = M('Log');
		$m_user = M('User');
		$m_examine = M('Examine');
		$role_id_array = array();
		if (intval($_GET['role'])) {
			$role_id_array = array(intval($_GET['role']));
			$params[] = "role=" . intval($_GET['role']);
		} else {
			if (intval($_GET['department'])) {
				$department_id = intval($_GET['department']);
				$params[] = "department=" . intval($_GET['department']);
				foreach (getRoleByDepartmentId($department_id, true) as $k=>$v) {
					$role_id_array[] = $v['role_id'];
				}
			}
		}
		//过滤权限范围内的role_id
		if ($role_id_array) {
			//数组交集
			$idArray = array_intersect($role_id_array,$below_ids);
		} else {
			$idArray = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		}
		$p = $_GET['p'] ? intval($_GET['p']) : 1;
		import("@.ORG.Page");
		
		//分页功能
		$role_list = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->page($p.',15')->field('role_id,full_name,thumb_path')->order('user_id')->select();
		$count = $m_user->where(array('role_id'=>array('in', $idArray), 'status'=>1))->count();
		$Page = new Page($count,15);
		$this->count = $count;
		$this->assign('count',$count);
		$Page->parameter = implode('&', $params);
		$this->assign('page', $Page->show());
		//时间段搜索
		$search_time_year = $_GET['search_year'] ? intval($_GET['search_year']) : date('Y',time());
		$params[] = "search_year=" . intval($_GET['search_year']);
		$search_time_month = $_GET['search_month'] ? intval($_GET['search_month']) : date('m',time());
		$params[] = "search_month=" . intval($_GET['search_month']);
		$search_time = $search_time_year.'-'.$search_time_month;
		//查询使用年份、月份数组
		$min_time = M('Log')->min('create_date');
		$min_year = $min_time ? date('Y',$min_time) : date('Y');
		$max_year = date('Y');
		$year_array = array();
		for ($i=$min_year; $i <= $max_year; $i++) { 
			$year_array[] = $i;
		}
		$month_array = array('1','2','3','4','5','6','7','8','9','10','11','12');
		$this->year_array = $year_array;
		$this->month_array = $month_array;
		$this->search_time_year = $search_time_year;
		$this->search_time_month = $search_time_month;

		//当前时间
		$date = $search_time;
		$this->date_now = $date;
		//根据月份计算天数
		$days = getmonthdays(strtotime($date));
		$this->days = $days;

		//部门岗位
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission')->where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('RoleDepartment')->select();
		}else{
			$departmentList = M('RoleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);
		
		//本月时间戳范围
		$month_start_time = strtotime(date($search_time_year.'-'.$search_time_month.'-01')); 
		$month_end_time = strtotime($search_time_year."-".$search_time_month."-".date("t",strtotime($search_time)))+86400;
		$month_time = array('between',array($month_start_time,$month_end_time));
		//本年时间戳范围
		$year_start_time = strtotime(date($search_time_year."-01-01"));
		$year_end_time = strtotime(date($search_time_year."-12-31"));
		$year_time = array('between',array($year_start_time,$year_end_time));

		//获取时间范围内的每日时间戳数组(当月)
		$start = strtotime($date.'-'.'01');
		$end = strtotime($date.'-'.$days);
		$day_list = dateList($start,$end);

		//自定义时间
		$m_workrule = M('Workrule');
		//计算年休息的天数
		$year_no = $m_workrule->where(array('year'=>$search_time_year,'type'=>1))->count;
		//年总天数
		$year_count_total = round(($year_end_time-$year_start_time)/86400);
		//计算月休息天数
		$month_no_array = $m_workrule->where(array('sdate'=>$month_time,'type'=>1))->getField('sdate',true);
		$month_no = count($month_no_array);
		//月总天数
		$month_count_total = $days;

		$week_array = array(); //星期六、星期日的日期数组
		foreach($day_list as $k=>$v){
			$no_work = 1;
			$week = '';
			$week = getTimeWeek($v['sdate']);
			if(!in_array($v['sdate'],$month_no_array)){
				$no_work = 0;
			}
			$day_list[$k]['no_work'] = $no_work;

			//判断星期六、日
			if($week == '星期六' || $week == '星期日'){
				$week_array[] = $k+1;
			}
		}
		$this->week_array = $week_array;
		$now = time();
		$m_log_talk = M('LogTalk');
		if($content_id == 1){
			foreach($role_list as $k=>$v){
				//本月日志数
				$month_count = 0;
				$month_count = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>$month_time,'category_id'=>array('neq',1)))->count();
				$role_list[$k]['month_count'] = $month_count;
				//计算月完成率
				$month_rate = 0.00;
				$month_rate = round($month_count/($month_count_total-$month_no),2)*100;
				$role_list[$k]['month_rate'] = $month_rate;

				//本年日志数
				$year_count = 0;
				$year_count = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>$year_time,'category_id'=>array('neq',1)))->count();
				$role_list[$k]['year_count'] = $year_count;
				//计算年完成率
				$year_rate = 0.00;
				$year_rate = round($year_count/($year_count_total-$year_no),2)*100;
				$role_list[$k]['year_rate'] = $year_rate;

				//判断是否请假、出差
				$examine_list = array();
				$examine_list = $m_examine->where(array('owner_role_id'=>$v['role_id'],'create_time'=>$month_time,'type'=>array('in',array('2','5')),'examine_status'=>2))->select();

				//每日数据
				foreach($day_list as $key=>$val){
					if (time() > $val['sdate']) {
						$log_ids = array();
						$is_comment = 0;
						
						if($now > $val['sdate'] && empty($val['no_work'])){
							// $log_ids = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>array('between',array($val['sdate'],$val['edate'])),'category_id'=>array('neq',1)))->getField('log_id',true);
							$log_type = 2; //未写日志
							
						}else{
							if($val['no_work'] == 1){
								$log_type = 3; //休
							}else{
								$log_type = 0; //未到日期
							}
						}
						$log_ids = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>array('between',array($val['sdate'],$val['edate'])),'category_id'=>array('neq',1)))->getField('log_id',true);
						if($log_ids){
							$log_type = 1;//已写日志
							//是否点评
							if($m_log_talk->where(array('log_id'=>$log_ids[0],'send_role_id'=>session('role_id')))->find()){
								$is_comment = 1;
							}
						}
						//判断是否请假、出差
						if ($examine_list) {
							foreach ($examine_list as $key1=>$val1) {
								$dateList = array();
								$dateList = dateList($val1['start_time'],$val1['end_time']);
								$new_dateList = array();
								foreach ($dateList as $key2=>$val2) {
									$new_dateList[] = $val2['sdate'];
								}
								if (in_array($val['sdate'],$new_dateList) && $val1['type'] == 2) {
									$log_type = 4; //假
									$module_url = U('examine/view','id='.$val1['examine_id'].'&type=2');
								}
								if (in_array($val['sdate'],$new_dateList) && $val1['type'] == 5) {
									$log_type = 5; //差
									$module_url = U('examine/view','id='.$val1['examine_id'].'&type=5');
								}
							}
						}

						$role_list[$k]['log_type'][$key+1]['is_comment'] = $is_comment;
						$role_list[$k]['log_type'][$key+1]['type'] = $log_type;
						$role_list[$k]['log_type'][$key+1]['log_ids'] = $log_ids[0];
						$role_list[$k]['log_type'][$key+1]['url'] = $module_url ? : '';
					} else {
						$role_list[$k]['log_type'][$key+1][] = array();
					}
				}
			}
		}elseif($content_id == 2){
			//沟通日志
			foreach($role_list as $k=>$v){
				//本月日志数
				$month_count = 0;
				$month_count = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>$month_time,'category_id'=>1))->count();
				$role_list[$k]['month_count'] = $month_count;

				//本年日志数
				$year_count = 0;
				$year_count = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>$year_time,'category_id'=>1))->count();
				$role_list[$k]['year_count'] = $year_count;

				//每日数据
				foreach($day_list as $key=>$val){
					if (time() > $val['sdate']) {
						$log_count = 0;
						$log_count = $m_log->where(array('role_id'=>$v['role_id'],'create_date'=>array('between',array($val['sdate'],$val['edate'])),'category_id'=>1))->count();
						$role_list[$k]['log_count'][$key+1]['count'] = $log_count;
						$role_list[$k]['log_count'][$key+1]['sdate'] = $val['sdate'];
						$role_list[$k]['log_count'][$key+1]['lt_time'] = 1;
					} else {
						$role_list[$k]['log_count'][$key+1]['lt_time'] = 0;
					}
				}
			}
		}

		$this->role_list = $role_list;
		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 沟通日志列表(弹出框)
	 **/
	public function commun_list(){
		$role_id = $_GET['role_id'] ? intval($_GET['role_id']) : 0;
		$search_time = $_GET['search_time'] ? intval($_GET['search_time']) : strtotime(date('Y-m-d'));
		$search_date = array('between',array($search_time,$search_time+86400));
		$m_log = M('Log');
		$m_log_status = M('LogStatus');

		//相关模块沟通历史
		$module = $_GET['module'] ? trim($_GET['module']) : '';
		$module_id = $_GET['module_id'] ? intval($_GET['module_id']) : 0;
		if (!empty($module) && !empty($module_id)) {
			switch ($module) {
				case 'leads' : 
					$m_r_log = M('RLeadsLog'); 
					//判断权限
					$outdays = M('Config')->where('name="leads_outdays"')->getField('value');
					$outdate = empty($outdays) ? 0 : time()-86400*$outdays;	
					$where['have_time'] = array('egt',$outdate);
					$where['owner_role_id'] = array('neq',0);
					$where['leads_id'] = $module_id;

					if ($leads_info = D('Leads')->where($where)->find()) {
						if (in_array($leads_info['owner_role_id'],getPerByAction('leads','view'))) {
							$is_permission = 1;
						}
					}
					break;
				case 'customer' : 
					$m_r_log = M('RCustomerLog'); 

					//判断权限
					$customer_info = M('Customer')->where('customer_id = %d', $module_id)->find();
					$m_config = M('Config');
					$outdays = $m_config->where('name="customer_outdays"')->getField('value');
					$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

					$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
					$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
					$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
					$openrecycle = $m_config->where('name="openrecycle"')->getField('value');

					if ($openrecycle == 2) {
						if($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)){
							if (in_array($customer_info['owner_role_id'], getPerByAction('customer','view')) || session('?admin')) {
								$is_permission = 1;
							}
						}else{
							$is_permission = 1;
						}
					}else{
						if (in_array($customer_info['owner_role_id'], getPerByAction('customer','view')) || session('?admin')) {
							$is_permission = 1;
						}
					}
					//共享客户也有查看权限
					$share_role_ids = M('CustomerShare')->where(array('customer_id'=>$module_id))->getField('by_sharing_id',true);
					if (in_array(session('role_id'), $share_role_ids)) {
						$is_permission = 1;
					}
					break;
				case 'business' : 
					$m_r_log = M('RBusinessLog'); 
					//判断权限
					$customer_id = M('Business')->where('business_id = %d',$module_id)->getField('customer_id');
					
					$customer_info = M('Customer')->where('customer_id = %d', $customer_id)->find();
					$m_config = M('Config');
					$outdays = $m_config->where('name="customer_outdays"')->getField('value');
					$outdate = empty($outdays) ? 0 : time()-86400*$outdays;

					$c_outdays = $m_config->where('name="contract_outdays"')->getField('value');
					$c_outdays = empty($c_outdays) ? 0 : $c_outdays;
					$contract_outdays = empty($c_outdays) ? 0 : time()-86400*$c_outdays;
					$openrecycle = $m_config->where('name="openrecycle"')->getField('value');

					if ($openrecycle == 2) {
						if($customer_info['owner_role_id'] != 0 && (($customer_info['update_time'] > $outdate && $customer_info['get_time'] > $contract_outdays) || $customer_info['is_locked'] == 1)){
							if (in_array($customer_info['owner_role_id'], getPerByAction('business','view')) || session('?admin')) {
								$is_permission = 1;
							}
						}else{
							$is_permission = 1;
						}
					}else{
						if (in_array($customer_info['owner_role_id'], getPerByAction('business','view')) || session('?admin')) {
							$is_permission = 1;
						}
					}
					//共享客户也有查看权限
					$share_role_ids = M('CustomerShare')->where(array('customer_id'=>$customer_id))->getField('by_sharing_id',true);
					if (in_array(session('role_id'), $share_role_ids)) {
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
				if ($module == 'customer') {
					//合并客户、商机沟通记录
					$customer_business_ids = M('business')->where('customer_id = %d and is_deleted=0', $module_id)->getField('business_id', true);
					$customer_log_ids = M('rCustomerLog')->where('customer_id = %d', $module_id)->getField('log_id', true);
					$customer_log_ids = $customer_log_ids ? $customer_log_ids : array();
					$business_log_ids = M('rBusinessLog')->where('business_id in (%s)', implode(',', $customer_business_ids))->getField('log_id', true);
					$business_log_ids = $business_log_ids ? $business_log_ids : array();
					$m_log = M('log');
					$log_list = $m_log->where('log_id in (%s)', implode(',', array_merge($customer_log_ids,$business_log_ids)))->order('create_date desc')->select();
				} else {
					$log_ids = $m_r_log->where(array($module_id_name=>$module_id))->getField('log_id', true);
					$log_list = $m_log->where('log_id in (%s)', implode(',', $log_ids))->order('create_date desc')->select();
				}

				// $d_role = D('RoleView');
				$m_user = M('User');
				$m_sign = M('Sign');
				$m_customer = M('Customer');
				$m_sign_img = M('SignImg');
				
				foreach ($log_list as $key=>$value) {
					$role_info = array();
					$role_info = $m_user->where('role_id = %d', $value['role_id'])->field('full_name,role_id,thumb_path')->find();
					if (!$role_info['thumb_path']) {
						$role_info['thumb_path'] = '__PUBLIC__/img/avatar_default.png';
					}
					$log_list[$key]['user'] = $role_info;
					
					if($value['sign'] == 1){
						//客户签到
						$sign_info = $m_sign->where('log_id = %d',$value['log_id'])->find();
						if($sign_info){
							$sign_info['content'] = '进行了签到';
							$sign_info['type'] = 9;
							$sign_info['sign_customer_id'] = $sign_info['customer_id'];
							$sign_customer_name = $m_customer->where('customer_id = %d',$sign_info['customer_id'])->getField('name');
							$sign_info['sign_customer_name'] = !empty($sign_customer_name) ? $sign_customer_name : '';
							$sign_info['sign_img'] = $m_sign_img ->where('sign_id = "%d"',$sign_info['sign_id'])->select();
							$log_list[$key]['sign_info'] = $sign_info;
						}else{
							$log_list[$key]['sign_info'] = array();
						}
						$log_list[$key]['type'] = 2;//签到
					}else{
						$log_list[$key]['type'] = 1;//沟通日志
					}
					//沟通类型
					$log_list[$key]['status_name'] = $m_log_status->where('id = %d',$value['status_id'])->getField('name');
				}
			} else {
				echo '<div class="alert alert-error">您没有此权利！</div>';die();
			}
		} else {
			//相关人沟通历史
			if(!$role_id){
				echo '<div class="alert alert-error">参数错误！</div>';die();
			}
			//判断权限(自己和下属)
			$below_ids = getSubRoleId();
			if(!in_array($role_id,$below_ids) && !session('?admin')){
				echo '<div class="alert alert-error">您没有此权利！</div>';die();
			}
			$m_leads = M('Leads');
			$m_r_leads_log = M('rLeadsLog');
			$m_customer = M('Customer');
			$m_r_customer_log = M('rCustomerLog');
			$m_business = M('Business');
			$m_r_business_log = M('rBusinessLog');
			// $role_info = getUserByRoleId($role_id);
			$role_info = M('User')->where('role_id = %d',$role_id)->field('full_name,role_id,thumb_path')->find();
			if (!$role_info['thumb_path']) {
				$role_info['thumb_path'] = '__PUBLIC__/img/avatar_default.png';
			}
			$log_list = $m_log->where(array('role_id'=>$role_id,'create_date'=>$search_date,'category_id'=>1))->order('log_id desc')->select();
			$log_count = count($log_list);
			//相关线索数
			$leads_count = 0;
			//相关客户数
			$customer_count = 0;
			//相关商机数
			$business_count = 0;
			foreach($log_list as $k=>$v){
				$module_type = '';
				$module_id = '';
				$module_name = '';
				$leads_id = $m_r_leads_log->where('log_id = %d',$v['log_id'])->getField('leads_id');
				if($leads_id){
					$leads_count += 1;
					$module_info = $m_leads->where('leads_id = %d',$leads_id)->field('name,leads_id')->find();
					$module_type = 'leads';
					$module_id = $module_info['leads_id'];
					$module_name = '线索';
				}else{
					$customer_id = $m_r_customer_log->where('log_id = %d',$v['log_id'])->getField('customer_id');
					if($customer_id){
						$customer_count += 1;
						$module_info = $m_customer->where('customer_id = %d',$customer_id)->field('name,customer_id')->find();
						$module_type = 'customer';
						$module_id = $module_info['customer_id'];
						$module_name = '客户';
					}else{
						$business_id = $m_r_business_log->where('log_id = %d',$v['log_id'])->getField('business_id');
						if($business_id){
							$business_count += 1;
							$module_info = $m_business->where('business_id = %d',$business_id)->field('name,business_id')->find();
							$module_type = 'business';
							$module_id = $module_info['business_id'];
							$module_name = '商机';
						}
					}
				}
				$log_list[$k]['module_type'] = $module_type;
				$module_info = $module_info ? $module_info : array('name'=>'查看详情');
				$log_list[$k]['user'] = $role_info;
				$log_list[$k]['module_name'] = $module_name;
				if(in_array($module_type,array('leads','customer','business'))){
					$url = "<a href=./index.php?m=".$module_type."&a=view&id=".$module_id.">".$module_info['name']."</a>";
				}else{
					$url = '无';
				}
				$log_list[$k]['url'] = $url;
				//沟通类型
				$log_list[$k]['status_name'] = $m_log_status->where('id = %d',$v['status_id'])->getField('name');
			}
			$this->total_array = array('log_count'=>$log_count,'leads_count'=>$leads_count,'customer_count'=>$customer_count,'business_count'=>$business_count);
		}
		
		$this->log_list = $log_list;
		$this->display();
	}

}
