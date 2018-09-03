<?php 
class MessageAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array('logintips'),
			'allow'=>array('view', 'send','delete', 'ajaxsend', 'index','tips','setread','message_view','message_content','message_view_data','upload','index_data','message_mark')
		);
		B('Authenticate', $action);
	}
	
	public function index(){
		// import("@.ORG.Page");
		$m_r_message = D('MessageReceiveView');
		$m_s_message = D('MessageSendView');
		$m_message = M('Message');
		$m_user = M('User');
		$p1 = 1;
		$p2 = 1;
		$r_where = array();
		$r_where['to_role_id'] = session('role_id');
		$r_where['message.status'] = array('neq', 1);
		$r_where['from_role_id'] = 0;
		$by = $_GET['by'] ? trim($_GET['by']) : 'index';
		
		if($by == 'index'){
			//系统消息
			$receive_list = $m_r_message->where($r_where)->order('send_time desc,read_time asc')->page($p1.',10')->select();
			foreach($receive_list as $k=>$v){
				$pre_content = strip_tags($v['content']);
				// $pre_content = str_replace('&nbsp;','',$v['content']);
				$pre_content_len = mb_strlen($pre_content,'utf-8');
				if($pre_content_len <= 25){
					$receive_list[$k]['pre_content'] = $pre_content;
				}else{
					$pre_content = mb_substr($pre_content,0,25,'utf-8');
					$receive_list[$k]['pre_content'] = $pre_content.' . . .';
				}
			}
			$count1 = $m_r_message->where($r_where)->count();
			$r_where['read_time'] = 0;
			$new_num = $m_r_message->where($r_where)->count();
			//系统消息标记为已读
			$m_message->where(array('to_role_id'=>session('role_id'),'from_role_id'=>0,'read_time'=>0))->setField('read_time',time());
		}else if($by == 'announcement'){
			if (checkPerByAction('announcement','index')) {
				//公告
				$m_announcement = M('Announcement');
				$m_announcement_data = M('AnnouncementData');
				//公告列表权限判断
				$where['department'] = array('like', '%('.session('department_id').')%');
				$where['role_id'] = session('role_id');
				$where['_logic'] = 'or';
				$map['_complex'] = $where;
				$map['status'] = array('eq', 1);
				$announcement_list = $m_announcement->where($map)->order('order_id')->field('announcement_id,role_id,title,create_time,update_time')->select();
				foreach($announcement_list as $k=>$v){
					$user_info = $m_user->where('role_id = %d',$v['role_id'])->field('role_id,name,full_name')->find();
					$message_content = '';
					$message_content = $user_info['full_name'].'发布了一条新公告'.'<a target="_blank" href="'.U('announcement/view','id='.$v['announcement_id']).'">'.'《'.$v['title'].'》'.'</a></br>';
					$announcement_list[$k]['content'] = $message_content;
					$announcement_list[$k]['read'] = $m_announcement_data ->where('announcement_id =%d ',$v['announcement_id'])->find();
				}
				$this->announcement_list = $announcement_list;
			}
		}else if($by == 'remind'){
			$m_remind = M('Remind');
			$m_business = M('Business');
			$m_customer = M('Customer');
			$remind_list = $m_remind->where(array('create_role_id'=>session('role_id'),'remind_time'=>array('lt',time())))->order('remind_id desc')->page($p1,10)->select();
			foreach($remind_list as $k=>$v){
				$customer_id = '';
				if($v['module'] == 'business'){
					$customer_id = $m_business->where(array('business_id'=>$v['module_id']))->getField('customer_id');
				}elseif($v['module'] == 'customer'){
					$customer_id = $v['module_id'];
				}
				$customer_info = $m_customer->where(array('customer_id'=>$customer_id))->field('name,customer_id')->find();
				if($customer_info){
					$message_content = '';
					$message_content = '您有一个新的提醒需要跟进  相关客户'.'<a target="_blank" href="'.U('customer/view','id='.$customer_info['customer_id']).'">'.'【'.$customer_info['name'].'】'.'</a></br>'.'提醒内容：'.$v['content'];

					$remind_list[$k]['content'] = $message_content; 
				}else{
					unset($remind_list[$k]);
				}
			}
			$this->remind_list = $remind_list;
		}
		$this->by = $by;
		
		//获取站内信相关人
		$m_message = M('message');
		$to_role_ids = $m_message ->where('from_role_id =%d and to_role_id !=%d and status = 0',session('role_id'),session('role_id'))->order('message_id desc')->field('to_role_id,send_time')->select();
		$from_role_ids = $m_message ->where('to_role_id =%d and from_role_id >0 and from_role_id !=%d and status = 0',session('role_id'),session('role_id'))->order('message_id desc')->field('from_role_id,send_time')->select();
		if($to_role_ids && $from_role_ids){
			$owner_role_id_array = array_merge($to_role_ids, $from_role_ids);//合并数组
		}else{
			if($to_role_ids){
				$owner_role_id_array = $to_role_ids;
			}elseif($from_role_ids){
				$owner_role_id_array = $from_role_ids;
			}else{
				$owner_role_id_array = array();
			}
		}
		$owner_role_id_array = sort_select($owner_role_id_array,'send_time',1);//排序
		$message_role_id_array = array();
		foreach($owner_role_id_array as $v){
			if($v['from_role_id']){
				$message_role_id_array[] = $v['from_role_id'];
			}
			if($v['to_role_id']){
				$message_role_id_array[] = $v['to_role_id'];
			}
		}
		if($message_role_id_array){
			$message_role_id_array = array_unique($message_role_id_array);//去重
			$message_role_id_array = array_values($message_role_id_array);//重置索引
			// $message_session_role_id[] = session('role_id');
			$user_list = array();
			$m_user =  M('User');
			foreach($message_role_id_array as $k=>$v){
				$user_info = array();
				$user_info = $m_user->where('role_id = %d',$v)->field('name,thumb_path,role_id,full_name')->find();
				if($user_info){
					$user_list[$k]['user_info'] = $user_info;
					$to_role_id = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',session('role_id'),$v)->max('message_id');
					$from_role_id = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',$v,session('role_id'))->max('message_id');
					if($to_role_id >$from_role_id){
						$message_info = $m_message ->where('message_id =%d',$to_role_id)->find();
					}else{
						$message_info = $m_message ->where('message_id =%d',$from_role_id)->find();
					}
					if($message_info['file_id']){
						$message_info['content'] = '文件已上传，请查收。';
					}else{
						$message_info['content'] = $message_info['content'] ? cutString($message_info['content'],15) : '';
					}
					$user_list[$k]['message'] = $message_info;
					//未读消息数
					$n_where['read_time'] = 0;
					$n_where['to_role_id'] = session('role_id');
					$n_where['from_role_id'] = $v;
					$n_where['status'] = array('neq', 2);
					$user_list[$k]['noread_count'] = $m_message->where($n_where)->count();
				}
			}
		}
		//获取公告未读数量
		$m_announcement = M('Announcement');
		$m_announcement_data = M('AnnouncementData');
		$where = array();
		$where['department'] = array('like', '%('.session('department_id').')%');
		$where['status'] = array('eq', 1);
		//公告列表权限判断
		$where['role_id'] = array('in', getPerByAction('announcement','index'));
		$announcement_list = $m_announcement->where($where)->order('order_id')->field('announcement_id,role_id,title,create_time,update_time')->select();
		$no_counts = 0;
		foreach($announcement_list as $k=>$v){
			$announcement_list[$k]['read'] = $m_announcement_data ->where('announcement_id =%d ',$v['announcement_id'])->find();
			if(!$announcement_list[$k]['read']){
				$no_counts +=1;
			}
		}
		$this->no_counts = $no_counts;
			
		$this->assign('receive_list',$receive_list);
		$this->assign('receive_list_num',$count1);
		$this->assign('new_num',$new_num);
		$this->assign('send_list',$user_list);
		$this->assign('send_list_num',$count2);
		$this->alert = parseAlert();
		$this->listrows = $listrows;
		$this->this_page = $p;
		$this->display();
	}
	//动态加载
	public function index_data(){
		if($this->isAjax()){
			$m_r_message = D('MessageReceiveView');
			$m_message = M('Message');
			$m_user = M('User');
			$p = $_GET['p'] ? intval($_GET['p']) : 1;
			$r_where = array();
			$r_where['to_role_id'] = session('role_id');
			$r_where['message.status'] = array('neq', 1);
			$r_where['from_role_id'] = 0;
			$by = $_GET['by'] ? trim($_GET['by']) : 'index';
			
			if($by == 'index'){
				//系统消息
				$receive_list = $m_r_message->where($r_where)->order('send_time desc,read_time asc')->page($p.',10')->select();
				foreach($receive_list as $k=>$v){
					$receive_list[$k]['message_type'] = 'index';
					$receive_list[$k]['message_time'] = date('m/d H:i',$v['send_time']);
					$pre_content = strip_tags($v['content']);
					// $pre_content = str_replace('&nbsp;','',$v['content']);
					$pre_content_len = mb_strlen($pre_content,'utf-8');
					if($pre_content_len <= 25){
						$receive_list[$k]['pre_content'] = $pre_content;
					}else{
						$pre_content = mb_substr($pre_content,0,25,'utf-8');
						$receive_list[$k]['pre_content'] = $pre_content.' . . .';
					}
				}
				//系统消息标记为已读
				$m_message->where(array('to_role_id'=>session('role_id'),'from_role_id'=>0,'read_time'=>0))->setField('read_time',time());
			}else if($by == 'announcement'){
				//公告
				$m_announcement = M('Announcement');
				$where['department'] = array('like', '%('.session('department_id').')%');
				$where['status'] = array('eq', 1);
				//公告列表权限判断
				$where['role_id'] = array('in', getPerByAction('announcement','index'));
				$announcement_list = $m_announcement->where($where)->order('order_id')->page($p.',10')->field('announcement_id,role_id,title,create_time,update_time')->select();
				foreach($announcement_list as $k=>$v){
					$announcement_list[$k]['message_type'] = 'announcement';
					$announcement_list[$k]['message_time'] = date('m/d H:i',$v['create_time']);
					$user_info = $m_user->where('role_id = %d',$v['role_id'])->field('role_id,name')->find();
					$message_content = '';
					$message_content = $user_info['name'].'发布了一条新公告'.'<a target="_blank" href="'.U('announcement/view','id='.$v['announcement_id']).'">'.'《'.$v['title'].'》'.'</a></br>';
					$announcement_list[$k]['content'] = $message_content;

				}
				$receive_list = $announcement_list;
			}else if($by == 'remind'){
				$m_remind = M('Remind');
				$m_business = M('Business');
				$m_customer = M('Customer');
				$remind_list = $m_remind->where(array('create_role_id'=>session('role_id'),'remind_time'=>array('lt',time())))->order('remind_id desc')->page($p,10)->select();
				foreach($remind_list as $k=>$v){
					$remid_list[$k]['message_type'] = 'remind';
					$remid_list[$k]['message_time'] = date('m/d H:i',$v['remind_time']);
					$customer_id = '';
					$customer_id = $m_business->where(array('business_id'=>$v['module_id']))->getField('customer_id');
					$customer_info = $m_customer->where(array('customer_id'=>$customer_id))->field('name,customer_id')->find();
					if($customer_info){
						$message_content = '';
						$message_content = '您有一个新的提醒需要跟进  相关客户'.'<a target="_blank" href="'.U('customer/view','id='.$customer_info['customer_id']).'">'.'【'.$customer_info['name'].'】'.'</a></br>'.'提醒内容：'.$v['content'];

						$remind_list[$k]['content'] = $message_content; 
					}else{
						unset($remind_list[$k]);
					}
				}
				$receive_list = $remind_list;
			}
			if($receive_list){
				$this->ajaxReturn($receive_list,'',1);
			}else{
				$this->ajaxReturn('没有更多数据啦！','没有更多数据啦！',0);
			}
		}
	}
	
	public function view(){
		$m_message = D('MessageReceiveView');
		$id = intval($_GET['id']);
		$where['message_id'] = $id;
		$where['_complex'] = array('to_role_id'=>session('role_id'),'from_role_id'=>session('role_id'),'_logic'=>'or');
		$info = $m_message->where($where)->find();
		if($info['read_time'] == 0 && $info['to_role_id'] == session('role_id')){
			$m_message->where(array('message_id'=>$id,'to_role_id'=>session('role_id')))->save(array('read_time'=>time()));
		}
		$this->assign('info',$info);
		$this->display();
	}
	
	public function send(){
		if($this->isPost()){
			$to_role = $_POST['to_role_id'];
			$m_user = M('User');
			$m_token = M('Userudid');
			if(is_array($to_role)){
				foreach($to_role as $k=>$v){
					sendMessage($v, $_POST['content']);
				}
				alert('success',L('SEND_SUCCESS'),U('Message/index'));
			}else{
				if(sendMessage($_POST['to_role_id'],$_POST['content'])){
					$user_info = $m_user->where('role_id = %d',$_POST['to_role_id'])->find();
					if($user_info['model'] == 1){
						$token_ios = $m_token->where('role_id = %d',$_POST['to_role_id'])->getField('token');
					}elseif($user_info['model'] == 2){
						$token_and = $m_token->where('role_id = %d',$_POST['to_role_id'])->getField('token');
					}
					if($_POST['personal_id'] == 1){
						alert('success',L('SEND_SUCCESS'),U('Message/message_view','to_role_id='.$_POST['to_role_id']));
					}else{
						alert('success',L('SEND_SUCCESS'),U('Message/index'));
					}
				}else{
					alert('error',L('SEND_FAILY'),U('Message/index'));
				}
			}
			
		}elseif(intval($_GET['from_role_id'])){
			$user_info = M('User')->where(array('role_id'=>intval($_GET['from_role_id'])))->find();
			$this->assign('user_info',$user_info);
		}
		$d_role = D('RoleView');
		
		$departments_list = M('roleDepartment')->select();	
		foreach($departments_list as $k=>$v){
			$roleList = $d_role->where('position.department_id = %d', $v['department_id'])->select();
			$departments_list[$k]['user'] = $roleList;
		}
		$this->departments_list = $departments_list;
		$this->display();
	}
	
	public function ajaxSend(){
		if ($this->isAjax()){
			$send_type = $_POST['send_type'] ? intval($_POST['send_type']) : 1;
			if(sendMessage($_POST['to_role_id'],$_POST['content'],0,$send_type)){
				$data = M('User')->where('role_id = %d',session('role_id'))->field('name,img,thumb_path,full_name')->find();
				$data['content'] = trim($_POST['content']);
				$data['file_path'] = '';
				$data['send_type'] = 1;
				if($send_type == 2){
					$data['file_path'] = M('File')->where('file_id = %d',$_POST['content'])->getField('file_path');
					$data['send_type'] = 2;
				}
				$data['send_time'] = date('Y/m/d H:i',time());
				$this->ajaxReturn($data,L('SEND_SUCCESSS'),1);
			}else{
				$this->ajaxReturn("",L('SEND_FAILYS'),0);
			}
		}else{
			alert('error', L('ILLEGAL_ACCESS'), $_SERVER['HTTP_REFERER']);
		}
	}
	
	public function delete(){
		$m_message = M('message');
		if($this->isPost()){
			$message_id = is_array($_POST['message_id']) ? $_POST['message_id'] : '';
			if ('' == $message_id) { 
				alert('error', L('NOT_CHOOSE_ANY_CONTENT'), U('Message/index'));
			} else {
				if($_GET['model'] == 'receive'){
					foreach($message_id as $k => $v){
						$message = $m_message->where('message_id = %d and to_role_id= %d', $v, session('role_id')) -> find();
						if($message['status'] == 2 || $message['from_role_id'] == 0){
							$m_message->where('message_id = %d', $v)->delete();
						}else{
							$m_message->where('message_id = %d', $v)->setField('status', 1);
						}
					}
					alert('success',L('DELETE_SUCCESS'),U('Message/index'));
				}elseif($_GET['model'] == 'send'){
					foreach($message_id as $k => $v){
						$message = $m_message->where('message_id = %d and from_role_id= %d', $v, session('role_id')) -> find();
						if($message['status'] == 1 || $message['from_role_id'] == 0){
							$m_message->where('message_id = %d', $v)->delete();
						}else{
							$m_message->where('message_id = %d', $v)->setField('status', 2);
						}
					}
					alert('success',L('DELETE_SUCCESS'),U('Message/index'));
				}else{
					alert('error',L('PARAMETER_ERROR_DELETE_FALILY'),$_SERVER['HTTP_REFERER']);
				}
			}
		}else{
			$id = intval($_GET['id']);
			$message = $m_message->where('message_id = %d', $id) -> find();
			if($id){
				if($message['from_role_id'] == 0){
					if($m_message->where('message_id = %d', $id)->delete()){
						$this->ajaxReturn('删除成功！','',1);
						// alert('success',L('DELETE_SUCCESS'),U('Message/index'));
					}else{
						$this->ajaxReturn('删除失败！','',0);
						// alert('error',L('DELETE_FAILY'), $_SERVER['HTTP_REFERER']);
					}
				}else{
					if($message['status'] == 0){
						$status = $message['to_role_id'] == session('role_id') ?  1 : 2 ;
						if($m_message->where('message_id = %d', $id)->setField('status', 1)){
							$this->ajaxReturn('删除成功！','',1);
							// alert('success',L('DELETE_SUCCESS'), U('Message/index'));
						}else{
							$this->ajaxReturn('删除失败！','',0);
							// alert('error',L('DELETE_FAILY'), $_SERVER['HTTP_REFERER']);
						}
					}elseif($message['status'] == 1 || $message['status'] == 2){
						if($m_message->where('message_id = %d', $id)->delete()){
							$this->ajaxReturn('删除成功！','',1);
							// alert('success',L('DELETE_SUCCESS'), U('Message/index'));
						}else{
							$this->ajaxReturn('删除成功！','',0);
							// alert('error',L('DELETE_FAILY'), $_SERVER['HTTP_REFERER']);
						}
					}
				}
			}else{
				$this->ajaxReturn('参数错误！','',0);
				// alert('error',L('PARAMETER_ERROR_CONTCART_ADMINISTRATOR'), $_SERVER['HTTP_REFERER']);
			}
		}
	}
	public function logintips(){
		//登录超时
		$is_login = 0;
		if(session('role_id') || session('login_show')){
			$is_login = $_SESSION['role_id'] ? $_SESSION['role_id'] : $_SESSION['login_show'];
		}
		// if(session('role_id')){
		// 	$is_login = $_SESSION['role_id'];
		// }
		$new_num['is_login'] = $is_login;
		$this->ajaxReturn($new_num,"",1);
	}
	
	/**
	 * xxx_count格式的数据为卡片提醒数据，否则为导航提醒数据
	 */
	public function tips(){
		//导航顶部数字提示
		$m_message = M('message');
		$m_user = M('user');
		$message_num = 0;//系统站内信数量
		//桌面提醒
		$data_list = array();
		$data_list_message = $m_message->where(array('to_role_id' => session('role_id'),'read_time' => 0,'status'=>array('neq', 1)))->select();

		$defaultinfo_info = M('Config')->where('name = "defaultinfo"')->find();
		$defaultinfo = unserialize($defaultinfo_info['value']);
		$thumb_path = './public/img/logo2.png';
		if ($defaultinfo['logo_min_thumb_path']) {
			$thumb_path = $defaultinfo['logo_min_thumb_path'];
		}
		$m = 0;
		foreach($data_list_message as $k=>$v){
			if(empty($v['is_notifi'])){
				$data_list[$m]['type'] = 1;
				if ($v['from_role_id']) {
					$data_list[$m]['role_info'] = $m_user->where('role_id = %d',$v['from_role_id'])->field('full_name,thumb_path')->find();
					//判断是否含有html标签
					if($v['content'] != strip_tags($v['content'])){
						$data_list[$m]['content'] = '您有新的消息';
						$data_list[$m]['content_msubstr'] = '您有新的消息';
					}else{
						$data_list[$m]['content'] = $v['content'];
						$data_list[$m]['content_msubstr'] = msubstr($v['content'],0,30);
					}
					$data_list[$m]['url_link'] = U('message/message_view','to_role_id='.$v['from_role_id']);
				} else {
					$data_list[$m]['role_info'] = array('full_name'=>'系统消息','thumb_path'=>$thumb_path);
					$data_list[$m]['content'] = '您有新的消息';
					$data_list[$m]['content_msubstr'] = '您有新的消息';
					$data_list[$m]['url_link'] = U('message/index');
				}
				//标记为已提醒
				$m_message->where('message_id = %d',$v['message_id'])->setField('is_notifi',1);
				$m++;
			}
			if(empty($v['from_role_id'])){
				$message_num +=1;
			}
			$message_time = $v['send_time'];
		}
		$new_num['message_time'] = getTimeBySec(time()-$message_time) ? getTimeBySec(time()-$message_time) : '0分钟';
		$new_num['message'] = count($data_list_message);
		//站内信数量
		$new_num['message_num'] = $message_num;
		$new_num['data_list'] = $data_list;
		$new_num['data_list_count'] = count($data_list);

		//员工站内信
		$m_user = M('User');
		$from_role_ids = $m_message->where(array('to_role_id' => session('role_id'),'read_time' => 0,'status'=>array('neq', 1),'from_role_id'=>array('neq','')))->group('from_role_id')->order('message_id desc')->getField('from_role_id',true);

		foreach($from_role_ids as $k=>$v){
			$user_info = $m_user->where('role_id = %d',$v)->field('full_name,role_id,thumb_path')->find();
			$message_info = $m_message->where('from_role_id = %d',$v)->order('message_id desc')->find();
			$role_message_list[$k]['full_name'] = $user_info['full_name'];
			$role_message_list[$k]['thumb_path'] = $user_info['thumb_path'];
			$role_message_list[$k]['role_id'] = $user_info['role_id'];
			$role_message_list[$k]['send_time'] = date('Y年m月d H:i:s',$message_info['send_time']);
			$role_message_list[$k]['content'] = $message_info['content'];
			if($message_info['file_id']){
				$role_message_list[$k]['content'] = '附件信息';
			}
			//查询未读数
			$role_message_list[$k]['unread_num'] = $m_message->where(array('from_role_id'=>$v,'to_role_id'=>session('role_id'),'read_time'=>array('eq','')))->count();
		}
		$new_num['role_message_list'] = $role_message_list ? $role_message_list : array();

		//获取公告未读数量
		$m_announcement = M('Announcement');
		$m_announcement_data = M('AnnouncementData');
		$where['department'] = array('like', '%('.session('department_id').')%');
		$where['status'] = array('eq', 1);
		//公告列表权限判断
		$where['role_id'] = array('in', getPerByAction('announcement','index'));
		$announcement_list = $m_announcement->where($where)->order('order_id')->field('announcement_id,role_id,title,create_time,update_time')->select();
		$no_counts = 0;

		$message_announcement_count = 0;
		foreach($announcement_list as $k=>$v){
			$announcement_list[$k]['read'] = $m_announcement_data->where('announcement_id =%d ',$v['announcement_id'])->find();
			if(!$announcement_list[$k]['read']){
				$new_num['message'] +=1;
				$message_announcement_count +=1;
			}
			$announcement_time = $v['update_time'];
		}
		$new_num['announcement_time'] = getTimeBySec(time()-$announcement_time);
		$new_num['message_announcement_count'] = $message_announcement_count;
		
		$current_start_time = strtotime(date('Y-m-d', time()))-1;
		$current_end_time = strtotime(date('Y-m-d', time()))+86400;
		// $m_task = M('Task');
		// $new_num[task] = $m_task->where('owner_role_id like "%s" and isclose = 0 and status <> "'.L('COMPLETE').'" and is_deleted <> 1 and due_date > %d', '%'.session('role_id').'%',time())->count();
		
		//今日日程
		$m_event = M('Event');
		$m_contract = M('Contract');
		$m_remind = M('Remind');
		$m_customer = M('Customer');
	 	$m_receivables = M('Receivables');

		$current_time = time();
		$event_list = $m_event->where("owner_role_id = %d and isclose = 0 and is_deleted <> 1 and start_date > $current_start_time and end_date < $current_end_time", session('role_id'))->order('start_date asc')->select();

		//追加周期性提醒
		$cycel_event_list = cycel_event($current_start_time,$current_end_time);
		if ($cycel_event_list && $event_list) {
			$event_list = array_merge($event_list,$cycel_event_list);
		} else {
			if ($cycel_event_list) {
				$event_list = $cycel_event_list;
			}
		}

		foreach($event_list as $k=>$v){
			$between_date = date('Y年m月d H:i',$v['start_date']);
			switch ($v['module']) {
				case 'contract' : 
					$contract_name = $m_contract->where(array('contract_id'=>$v['module_id']))->getField('contract_name');
					$subject_info = '【 合同 】'.$contract_name;
					break;
				case 'remind' : 
					$remind_info = $m_remind->where(array('remind_id'=>$v['module_id']))->find();
					$field_name = 'name';
					if ($remind_info['module'] == 'contract') {
						$field_name = 'contract_name';
					}
					$module_name = M($remind_info['module'])->where(array($remind_info['module'].'_id'=>$remind_info['module_id']))->getField($field_name);
					$subject_info = '【 提醒 】'.$module_name;
					break;
				case 'customer' :
					$customer_name = $m_customer->where(array('customer_id'=>$v['module_id']))->getField('name');
					$subject_info = '【 '.$customer_name.' 】';
					break;
				case 'receivables' :
					$receivables_name = $m_receivables->where(array('receivables_id'=>$v['module_id']))->getField('name');
					$subject_info = '【 应收款提醒 】'.$receivables_name;
					break;
				default : 
					$subject_info = $v['subject'];
					$between_date = date('Y年m月d H:i',$v['start_date']).' ~ '.date('Y年m月d H:i',$v['end_date']);
					break;
			}
			$event_list[$k]['subject'] = $subject_info;
			$event_list[$k]['between_date'] = $between_date;
		}
		$event_num = count($event_list);
		$new_num['event_num'] = $event_num;
		$new_num['event_list'] = $event_list ? $event_list : array();
		
		$m_contract = M('Contract');
		$days = C('defaultinfo.contract_alert_time') ? intval(C('defaultinfo.contract_alert_time')) : 30;
		$temp_time = $current_time+$days*86400;
		$contract_num = $m_contract->where("owner_role_id = %d and is_checked = 1 and contract_status = 0 and is_deleted <> 1 and $temp_time >= end_date", session('role_id'))->count();
	
		//导航下面卡片提示
		//从用户表中读取“最后阅读时间”，如果创建时间大于最后阅读时间，则提醒，否则不提醒
		
		$last_read_time_js = $m_user->where('role_id = %d', session('role_id'))->getField('last_read_time');
		if(!empty($last_read_time_js)){
			$last_read_time = json_decode($last_read_time_js, true);
		}

		//提醒发送站内信
		$m_remind = M('Remind');
		$m_customer = M('Customer');
		$m_business = M('Business');
		$remind_list = $m_remind->where(array('create_role_id'=>session('role_id'),'is_remind'=>0,'remind_time'=>array('lt',time())))->select();
		if($remind_list){
			foreach($remind_list as $k=>$v){
				$customer_id = '';
				if($v['module'] == 'business'){
					$customer_id = $m_business->where('business_id = %d',$v['module_id'])->getField('customer_id');
				}elseif($v['module'] == 'customer'){
					$customer_id = $v['module_id'];
				}
				$customer_info = $m_customer->where('customer_id = %d',$customer_id)->field('name,customer_id')->find();
				$message_content = '';
				$message_content = '您有一条自动提醒，内容如下：<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;'.$v['content'].'。<br/> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;相关客户：<a href="'.U('customer/view','id='.$customer_info['customer_id']).'">'.$customer_info['name'].'</a>';
				$res = sendMessage($v['create_role_id'],$message_content,1);
				if($res){
					//标记为已提醒
					$m_remind->where('remind_id = %d',$v['remind_id'])->setField('is_remind',1);
				}
			}
		}

		//待办事项
		//待审批的合同（数量以合同列表权限范围为准）
		$check_contract_num = 0;
		$contract_examine_role_ids = M('ContractExamine')->getField('role_id',true);
		$contract_examine = M('Config')->where(array('name'=>'contract_examine'))->getField('value');
		if (checkPerByAction('contract','check') || in_array(session('role_id'),$contract_examine_role_ids)) {
			$where_check_contract = array();
			// $contract_below_ids = getPerByAction('contract','index');
			// $session_role_id = session('role_id');
			$where_check_contract['owner_role_id'] = array('in',getPerByAction('contract','index'));
			$where_check_contract['is_checked'] = array('in',array('0','3'));
			$check_contract_num = M('Contract')->where($where_check_contract)->count();
		}
		//应收款提醒
		$receivables_num = 0;
		if (checkPerByAction('finance','index_receivables')) {
			$m_receivables = M('receivables');
			$receivables_time = M('config')->where('name="receivables_time"')->getField('value');
			$f_outdate = empty($receivables_time) ? 0 : time()-86400*$receivables_time;
			$r_where['pay_time'] = array('elt',time()+$f_outdate);
			$r_where['status'] = array('lt',2);
			$r_where['owner_role_id'] = session('role_id');
			$receivables_num = $m_receivables ->where($r_where)->count();
		}
		
		//待审批的单据（数量以审批列表权限范围为准）
		$examine_num = 0;
		if (checkPerByAction('examine','index')) {
			$where_examine = array();
			$where_examine['is_deleted'] = 0;
			$where_examine['examine_role_id'] = session('role_id');
			$where_examine['examine_status'] = array('lt',2);
			$examine_num = M('Examine')->where($where_examine)->count();
		}
		
		//待确认的回款
		$receivingorder_num = 0;
		if (checkPerByAction('finance','check')) {
			$where_check_receivingorder = array();
			$where_check_receivingorder['owner_role_id'] = array('in',getPerByAction('finance','index_receivingorder'));
			$where_check_receivingorder['status'] = 0;
			$receivingorder_num = M('Receivingorder')->where($where_check_receivingorder)->count();
		}

		//今日需拜访客户
		$today_customer = 0;
		if (checkPerByAction('customer','index')) {
			$today_where = array();
			// $today_where['owner_role_id'] = array('in',getPerByAction('customer','index'));
			$today_where['owner_role_id'] = session('role_id');
			//过滤客户池条件
			$m_config = M('Config');
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
			$today_customer = $m_customer->where($today_where)->count();
		}

		$new_num['receivables_num'] = $receivables_num ? $receivables_num : '';
		$new_num['check_contract_num'] = $check_contract_num ? $check_contract_num : '';
		$new_num['contract_num'] = $contract_num ? $contract_num : '';
		$new_num['examine_num'] = $examine_num ? $examine_num : '';
		$new_num['receivingorder_num'] = $receivingorder_num ? $receivingorder_num : '';
		$new_num['today_customer'] = $today_customer ? $today_customer : '';
		$new_num['todo_num'] = $check_contract_num+$examine_num+$receivingorder_num+$today_customer+$contract_num+$receivables_num;

		//锁屏页面
		$is_lock = 0;
		if(session('is_lock')){
			$is_lock = $_SESSION['is_lock'];
		}
		$new_num['is_lock'] = $is_lock;
		$this->ajaxReturn($new_num,"",1);
	}

	//站内信详情消息自动加载
	public function message_content(){
		$m_message = M('Message');
		$m_user = M('User');
		$m_file = M('File');
		$request_time = strtotime($_POST['request_time']);
		$to_role_id = intval($_POST['to_role_id']);
		$where = array();
		$where['from_role_id'] = $to_role_id;
		$where['to_role_id'] = session('role_id');
		$where['send_time'] = array('egt',$request_time);
		$where['read_time'] = 0;
		$message_content = $m_message->where($where)->select();
		$to_role_info = $m_user->where('role_id = %d',$to_role_id)->field('name,img,thumb_path,full_name')->find();
		//$from_role_info = $m_user->where('role_id = %d',session('role_id'))->field('name.img')->find();
		
		if($message_content){
			foreach($message_content as $k=>$v){
				$message_content[$k]['to_role_info'] = $to_role_info;
				//消息标记为已读
				$m_message->where('message_id = %d',$v['message_id'])->setField('read_time',time());
				$message_content[$k]['send_type'] = 1;
				// 附件格式
				if($v['file_id']){
					$message_content[$k]['send_type'] = 2;
					$message_content[$k]['file_info'] = $m_file->where('file_id = %d',$v['file_id'])->find();
				}
				$message_content[$k]['send_time'] = date('Y/m/d H:i',$v['send_time']);
			}
			$this->ajaxReturn($message_content,'',1);
		}else{
			$this->ajaxReturn('','',0);
		}
	}

	//设置站内信已读
	public function setRead(){
		$message_idArr = $_POST['message_id'];
		if(empty($message_idArr)){
			alert('error',L('PLEASE_CHOOSE_MESSAGE_TO_SET_READ'),$_SERVER['HTTP_REFERER']);
		}
		$m_message = M('message');
		$where['message_id'] = array('in', $message_idArr);
		$where['read_time'] = array('eq', 0);
		$result = $m_message->where($where)->setField('read_time',time());

		if($result){
			alert('success',L('SUCCESS_TO_SET_MESSAGE_READ'), $_SERVER['HTTP_REFERER']);
		}else{
			alert('error',L('FAILD_TO_SET_MESSAGE_READ'), $_SERVER['HTTP_REFERER']);
		}
	}
	//站内信详情
	public function message_view(){
		$m_file = M('File');
		$by = isset($_GET['by']) ? trim($_GET['by']) : '';
		if ($_REQUEST["field"]) {
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);			
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
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
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		//import("@.ORG.Page");
		$m_message = M('message');
		if(intval($_GET['to_role_id'])){
			$to_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',session('role_id'),intval($_GET['to_role_id']))->getField('message_id',true);
			$from_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',intval($_GET['to_role_id']),session('role_id'))->getField('message_id',true);
			if(!$to_role_ids){
				$all_role_ids = $from_role_ids;
			}elseif(!$from_role_ids){
				$all_role_ids = $to_role_ids;
			}else{
				$all_role_ids = array_merge($to_role_ids, $from_role_ids);  
			}
			$list_where['message_id'] = array('in',$all_role_ids);
			$all_message_list = $m_message ->where($list_where)->page('1'.',10')->order('message_id desc')->select();
			foreach($all_message_list as $k=>$v){
				$all_message_list[$k]['user'] = M('user')->where('role_id =%d',$v['from_role_id'])->field('name,img,thumb_path,full_name')->find();
				if($v['from_role_id'] == session('role_id')){
					$all_message_list[$k]['is_me'] = 1;
				}else{
					$all_message_list[$k]['is_me'] = 0;
				}
				$all_message_list[$k]['send_type'] = 1;
				// 附件格式
				if($v['file_id']){
					$all_message_list[$k]['send_type'] = 2;
					$all_message_list[$k]['file_info'] = $m_file->where('file_id = %d',$v['file_id'])->find();
				}
			}
			//标记为已读状态
			$read_list = array();
			$read_list['read_time'] = 0;
			$read_list['to_role_id'] = session('role_id');
			$read_list['form_role_id'] = intval($_GET['to_role_id']);
			$m_message->where($read_list)->setField('read_time',time());

			$to_role_info = M('User')->where('role_id = %d',$_GET['to_role_id'])->field('role_id,img,name,thumb_path,full_name')->find();
			$this->to_role_info = $to_role_info;
		}
		//获取站内信相关人
		$to_role_ids = $m_message ->where('from_role_id =%d and to_role_id !=%d and status = 0',session('role_id'),session('role_id'))->order('message_id desc')->field('to_role_id,send_time')->select();
		$from_role_ids = $m_message ->where('to_role_id =%d and from_role_id >0 and from_role_id !=%d and status = 0',session('role_id'),session('role_id'))->order('message_id desc')->field('from_role_id,send_time')->select();
		$owner_role_id_array = array_merge($to_role_ids, $from_role_ids);//合并数组
		$owner_role_id_array = sort_select($owner_role_id_array,'send_time',1);//排序
		$message_role_id_array = array();
		foreach($owner_role_id_array as $v){
			if($v['from_role_id']){
				$message_role_id_array[] = $v['from_role_id'];
			}
			if($v['to_role_id']){
				$message_role_id_array[] = $v['to_role_id'];
			}
		}
		if($message_role_id_array){
			$message_role_id_array = array_unique($message_role_id_array);//去重
			$message_role_id_array = array_values($message_role_id_array);//重置索引
			$user_list = array();
			$m_user =  M('User');
			foreach($message_role_id_array as $k=>$v){
				$user_info = array();
				$user_info = $m_user->where('role_id = %d',$v)->field('name,img,role_id,thumb_path,full_name')->find();
				if($user_info){
					$user_list[$k]['user_info'] = $user_info;
					$to_role_id = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',session('role_id'),$v)->max('message_id');
					$from_role_id = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',$v,session('role_id'))->max('message_id');
					if($to_role_id >$from_role_id){
						$message_info = $m_message ->where('message_id =%d',$to_role_id)->find();
					}else{
						$message_info = $m_message ->where('message_id =%d',$from_role_id)->find();
					}
					$user_list[$k]['message'] = $message_info;
					if($message_info['file_id']){
						$user_list[$k]['message']['content'] = '文件已上传，请查收。';
					}else{
						$user_list[$k]['message']['content'] = cutString($user_list[$k]['message']['content'],15);
					}
					//未读消息数
					$n_where['read_time'] = 0;
					$n_where['to_role_id'] = session('role_id');
					$n_where['from_role_id'] = $v;
					$n_where['status'] = array('neq', 2);
					$user_list[$k]['noread_count'] = $m_message->where($n_where)->count();
				}
			}
		}

		//获取公告未读数量
		$m_announcement = M('Announcement');
		$m_announcement_data = M('AnnouncementData');
		$where = array();
		$where['department'] = array('like', '%('.session('department_id').')%');
		$where['status'] = array('eq', 1);
		//公告列表权限判断
		$where['role_id'] = array('in', getPerByAction('announcement','index'));
		$announcement_list = $m_announcement->where($where)->order('order_id')->field('announcement_id,role_id,title,create_time,update_time')->select();
		$no_counts = 0;
		foreach($announcement_list as $k=>$v){
			$announcement_list[$k]['read'] = $m_announcement_data ->where('announcement_id =%d ',$v['announcement_id'])->find();
			if(!$announcement_list[$k]['read']){
				$no_counts +=1;
			}
		}
		$this->no_counts = $no_counts;

		$this->assign('send_list',$user_list);
		$this->assign('p',$p);
		$this->parameter = implode('&', $params);
		$this->assign('request_time',date('Y-m-d H:i:s',time()));
		$this->alert = parseAlert();
		$this->to_role_id = $_GET['to_role_id'];
		$this->all_message_list = array_reverse($all_message_list);
		$this->display();
	}

	//站内信详情ajax翻页加载
	public function message_view_data(){
		if($this->isAjax()){
			$m_message = M('Message');
			$m_file = M('File');
			$to_role_id = intval($_GET['to_role_id']);
			if($to_role_id){
				$to_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',session('role_id'),$to_role_id)->getField('message_id',true);
				$from_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d and status = 0',$to_role_id,session('role_id'))->getField('message_id',true);
				if(!$to_role_ids){
					$all_role_ids = $from_role_ids;
				}elseif(!$from_role_ids){
					$all_role_ids = $to_role_ids;
				}else{
					$all_role_ids = array_merge($to_role_ids, $from_role_ids);  
				}
				$list_where['message_id'] = array('in',$all_role_ids);
				$p = isset($_GET['p']) ? intval($_GET['p']) : 2 ;
				$all_message_list = $m_message ->where($list_where)->page($p.',10')->order('message_id desc')->select();
				if($all_message_list){
					$all_message_list = array_reverse($all_message_list);
					foreach($all_message_list as $k=>$v){
						$all_message_list[$k]['user'] = M('user')->where('role_id =%d',$v['from_role_id'])->field('name,img,thumb_path')->find();
						if($v['from_role_id'] == session('role_id')){
							$all_message_list[$k]['is_me'] = 1;
						}else{
							$all_message_list[$k]['is_me'] = 0;
						}
						$all_message_list[$k]['send_type'] = 1;
						if($v['file_id']){
							$all_message_list[$k]['send_type'] = 2;
							$all_message_list[$k]['file_info'] = $m_file->where('file_id = %d',$v['file_id'])->find();
						}
						$all_message_list[$k]['send_time'] = date('Y/m/d H:i',$v['send_time']);
						$all_message_list[$k]['message_time'] = date('Y年m月d号 H:m',$v['send_time']);
					}
					$this->ajaxReturn($all_message_list,'',1);
				}else{
					$this->ajaxReturn('','',0);
				}				
			}
		}
	}
	//ajax图片上传(消息)
    public function upload(){
    	if(!empty($_FILES)){
			if (isset($_FILES['img']['size']) && $_FILES['img']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
		        $upload->maxSize = 10485760;// 设置附件上传大小

		        $upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
				$dirname = UPLOAD_PATH.'/' . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					$ajaxRet['status'] = 2;
					$ajaxRet['msg'] = '创建文件夹失败！';
					$this->ajaxReturn($ajaxRet);
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					$ajaxRet['status'] = 3;
					$ajaxRet['msg'] = $upload->getErrorMsg();
					$this->ajaxReturn($ajaxRet);
				}else{
					$info =  $upload->getUploadFileInfo();
				}
				if(is_array($info[0]) && !empty($info[0])){
					$upload = $dirname . $info[0]['savename'];
				}else{
					$ajaxRet['status'] = 4;
					$ajaxRet['msg'] = '发送失败！';
					$this->ajaxReturn($ajaxRet);
				}
		        if (!$info) {
		            // 上传错误 提示错误信息
		            $info['msg'] = $upload->getError();
		            $info['status'] = 'error';
		            $this->ajaxReturn($info);
		        }else {
		        	$data['file_path'] = $info[0]['savepath'] . $info[0]['savename'];
					$data['name'] = $info[0]['name'];
					$data['role_id'] = session('role_id');
					$data['size'] = $info[0]['size'];
					$data['create_date'] = time();
					if($file_id = M('File')->add($data)){
						$list['id'] = $file_id;
			            $list['status'] = 'success';
			            $this->ajaxReturn($list);
					}else{
						$ajaxRet['status'] = 4;
						$ajaxRet['msg'] = '发送失败！';
						$this->ajaxReturn($ajaxRet);
					} 
		        }
			}
		}else{
			$ajaxRet['status'] = 4;
			$ajaxRet['msg'] = '发送失败！';
			$this->ajaxReturn($ajaxRet);
		}
    }
	
	public function message_delete(){
		$m_message = M('message');
		if($this->isPost()){
			$role_id = is_array($_POST['role_id']) ? $_POST['role_id'] : '';
			if('' == $role_id){ 
				alert('error', L('NOT_CHOOSE_ANY_CONTENT'), U('Message/index'));
			}else{
				foreach($role_id as $k => $v){
					$to_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d',session('role_id'),$v)->save(array('status'=>1));
					$from_role_ids = $m_message ->where('from_role_id =%d and to_role_id =%d',$v,session('role_id'))->save(array('status'=>1));
					if($message['status'] == 2 || $message['from_role_id'] == 0){
						$m_message->where('message_id = %d', $v)->delete();
					}else{
						$m_message->where('message_id = %d', $v)->setField('status', 1);
					}
				}
				alert('success',L('DELETE_SUCCESS'),U('Message/index'));
			}
		}
	}

	/**
	*站内信标记
	*
	**/
	public function message_mark(){
		if($this->isAjax()){
			$m_message = M('Message');
			$message_id = $_GET['message_id'];
			$message_info = $m_message->where('message_id = %d',$message_id)->find();
			//判断权限
			if($message_info['to_role_id'] != session('role_id')){
				$this->ajaxReturn('','您没有此权限！',0);
			}
			$is_mark = 1;
			if($message_info['is_mark'] == 1){
				$is_mark = 0;
			}
			if($m_message->where('message_id = %d',$message_id)->setField('is_mark',$is_mark)){
				$this->ajaxReturn('','标记成功！',1);
			}else{
				$this->ajaxReturn('','标记失败！',0);
			}
		}
	}
}