<?php
/**
 *日志相关
 **/
class LogVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('logstatus','praise','comment_add','replydel','add','delete')
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
	 * 工作日志列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			$d_log = D('LogView');
			$m_log_talk = M('LogTalk');
			$m_r_file_log = M('RFileLog');
			$m_file = M('File');
			$m_user = M('User');

			$by = isset($_REQUEST['by']) ? trim($_REQUEST['by']) : '';
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			$where = array();
			$order = 'log_id desc';
			$below_ids = getPerByAction('log','index');
			$sub_ids = getSubRoleId(false);
			$role_id = session('role_id');

			if (isset($_POST['search'])) {
				$where['subject'] = array('like','%'.trim($_POST['search']).'%');
			}
			//排序
			if ($_POST['order_field'] && $_POST['order_type']) {
				$order = 'log.'.trim($_POST['order_field']).' '.trim($_POST['order_type']).',log.log_id asc';
			}

			switch ($by) {
				case 'today' : $where['create_date'] =  array('gt',strtotime(date('Y-m-d', time()))); break;
				case 'week' : $where['create_date'] =  array('gt',(strtotime(date('Y-m-d', time())) - (date('N', time()) - 1) * 86400)); break;
				case 'month' : $where['create_date'] = array('gt',strtotime(date('Y-m-01', time()))); break;
				case 'add' : $order = 'create_date desc';  break;
				case 'update' : $order = 'update_date desc';  break;
				case 'sub' : 
							if (checkPerByAction('log','index') == 3) {
								$where['role_id'] = array('eq','-1');
							} else {
								$where['role_id'] = array('in',implode(',', $sub_ids)); 
							}
							break;
				case 'me' : $where['role_id'] = $role_id; break;
				default :  $where['role_id'] = array('in',implode(',', $below_ids)); break;
			}

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
								if($k == 'create_date'){
									$k = 'log.create_date';
								}elseif($k == 'update_date'){
									$k = 'log.update_date';
								}
								//时间段查询
								if ($v['start'] && $v['end']) {
									$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
								} elseif ($v['start']) {
									$where[$k] = array('egt',strtotime($v['start']));
								} else {
									$where[$k] = array('elt',strtotime($v['end'])+86399);
								}
							} elseif ($k == 'talk_status'){
								//日志状态（0全部1已点评2未点评）
								switch ($v['value']) {
									case 1 : $where['log_talk.talk_id'] = array('gt',0); break;
									case 2 : $where['log_talk.talk_id'] = array('EXP','IS NULL'); break;
									default :  break;
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
	            }
			}
			if (!isset($where['category_id'])) {
				$where['category_id'] = array('neq',1);
			}
			if (!isset($where['role_id'])) {
				$where['role_id'] = array('in',implode(',', $below_ids));
			}

			$list = $d_log->where($where)->field('log_id,role_id,subject,create_date,content,talk_id')->order($order)->page($p.',10')->select();
			$count = $d_log->where($where)->count();
			$page = ceil($count/10);

			$m_praise = M('Praise');
			foreach ($list as $k=>$v) {
				//过滤html代码
				$content_info = strip_tags($v['content']);
				
				if (empty($v['subject'])) {
					$list[$k]['subject'] = msubstr($content_info,0,15);
				}
				$content_text = msubstr($content_info,0,50);
				$list[$k]['content'] = $content_text;
				//评论数
				$comment_cont = $m_log_talk->where(array('log_id'=>$v['log_id']))->count();
				$list[$k]['comment_count'] = $comment_cont;
				//点赞数
				$list[$k]['praise_count'] = $m_praise->where('log_id = %d',$v['log_id'])->count();
				//是否点赞
				if ($m_praise->where('log_id = %d and role_id = %d',$v['log_id'],$role_id)->find()) {
					$list[$k]['is_praised'] = 1;
				} else {
					$list[$k]['is_praised'] = 0;
				}
				//附件
				$file_ids = $m_r_file_log->where('log_id = %d',$v['log_id'])->getField('file_id',true);

				$file_list = $m_file->where(array('file_id'=>array('in',$file_ids)))->select();
				foreach ($file_list as $key=>$val) {
					$file_type = '';
					$file_type = end(explode('.',$val['name']));
					$file_list[$key]['file_type'] = $file_type;
					$file_list[$key]['size'] = round($val['size']/1024,2).'Kb';
					if (intval($val['size']) > 1024*1024) {
						$file_list[$key]['size'] = round($val['size']/(1024*1024),2).'Mb';
					}
				}
				$list[$k]['file_list'] = $file_list ? $file_list : array();
				//作者
				$list[$k]['role_info'] = $m_user->where('role_id = %d',$v['role_id'])->field('full_name,role_id,thumb_path')->find();
			}
			//获取查询条件信息
			if($p == 1 && $_POST['search'] == ''){
				$fields_list = array(
					'0'=>array('field'=>'subject','form_type'=>'text','input_tips'=>'','name'=>'日志标题','setting'=>''),
					'1'=>array('field'=>'role_id','form_type'=>'user','input_tips'=>'','name'=>'日志负责人','setting'=>''),
					'2'=>array('field'=>'talk_status','form_type'=>'select','input_tips'=>'','name'=>'日志状态','setting'=>array(array('key'=>'0','value'=>'全部'),array('key'=>'1','value'=>'已点评'),array('key'=>'2','value'=>'未点评'))),
					'3'=>array('field'=>'category_id','form_type'=>'select','input_tips'=>'','name'=>'日志类型','setting'=>array(array('key'=>'4','value'=>'日报'),array('key'=>'3','value'=>'周报'),array('key'=>'2','value'=>'月报'))),
					'4'=>array('field'=>'create_date','form_type'=>'datetime','input_tips'=>'','name'=>'创建时间','setting'=>''),
					'5'=>array('field'=>'update_date','form_type'=>'datetime','input_tips'=>'','name'=>'修改时间','setting'=>'')
				);
			}
			if ($p == 1 && $_POST['search'] == '') {
				$data['fields_list'] = $fields_list ? $fields_list : array();
			} else {
				$data['fields_list'] = array();
			}

			//过滤查询下属日志时，role_id = 0的调试错误数据，防止APP报错崩溃
			if(!$sub_ids && $by == 'sub'){
				$list = array();
			}else{
				$list = empty($list) ? array() : $list;
			}
			$data['data'] = $list;
			$data['page'] = $page;
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 工作日志添加
	 * @param 
	 * @author 
	 * @return 
	 */
	public function mylog_add(){
		if ($this->isPost()) {
			if (!trim($_POST['subject'])) {
				$this->ajaxReturn('','请填写日志标题！',0);
			}
			if (!trim($_POST['content'])) {
				$this->ajaxReturn('','请填写日志内容！',0);
			}
			$m_log = M('Log');
			$role_id = session('role_id');

			$data = array();
			$data['subject'] = trim($_POST['subject']);
			$data['content'] = trim($_POST['content']);
			$data['create_date'] = time();
			$data['update_date'] = time();
			$data['role_id'] = $role_id;
			$data['category_id'] = empty($_POST['category_id']) ? 4 : intval($_POST['category_id']);

			if ($log_id = $m_log->add($data)) {
				actionLog($log_id);
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
				$this->ajaxReturn('','添加成功！',1);
			} else {
				$this->ajaxReturn('','添加失败！',0);
			}
		}
	}

	/**
	 * 工作日志编辑
	 * @param 
	 * @author 
	 * @return 
	 */
	public function mylog_edit() {
		if ($this->isPost()) {
			$log_id = $_POST['id'] ? intval($_POST['id']) : 0;
			$m_log = M('Log');
			$log_info = $m_log->where(array('role_id'=>session('role_id'),'log_id'=>$log_id))->find();
			if ($log_info) {
				if ($log_info['role_id'] != session('role_id')) {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
				$long = time()-$log_info['create_date'];
				if ($long > 86400) {
					$this->ajaxReturn('','日志创建时间超过1天，不能编辑！',0);
				}
			} else {
				$this->ajaxReturn('','您没有此权利！',-2);
			}

			if ($m_log->create()) {
				$m_log->update_date = time();
				if ($m_log->where(array('log_id'=>$log_id))->save()) {
					$this->ajaxReturn('','修改成功！',1);
				} else {
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','修改失败，请重试！',0);
			}
		}
	}

	/**
	 * 工作日志删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function log_delete() {
		if ($this->isPost()) {
			$log_id = $_REQUEST['id'] ? intval($_REQUEST['id']) : 0;
			$m_log = M('Log');
			$log_info = $m_log->where(array('log_id'=>$log_id,'role_id'=>session('role_id')))->find();
			if (!$log_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if ($m_log->where('log_id = %d',$log_id)->delete()) {
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}
	}

	/**
	 * 工作日志详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function mylog_view() {
		if ($this->isPost()) {
			$m_log = D('LogView');
			$m_comment = M('Comment');
			$m_user = M('User');
			$log_id = $_POST['id'] ? intval($_POST['id']) : '';
			$log_info = $m_log->where(array('log_id'=>$log_id))->find();
			if (!$log_info) {
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			$role_id = session('role_id');
			//权限判断
			if (!in_array($log_info['role_id'], getPerByAction('log','mylog_view'))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//附件
			$file_ids = M('RFileLog')->where('log_id = %d', $log_id)->getField('file_id', true);
			$file_list = M('File')->where(array('file_id'=>array('in',$file_ids)))->select();
			foreach ($file_list as $key=>$value) {
				$file_list[$key]['owner_name'] = $m_user->where('role_id = %d', $value['role_id'])->getField('full_name');
				$file_type = '';
				$file_type = end(explode('.',$value['name']));
				$file_list[$key]['file_type'] = $file_type;
				$file_list[$key]['size'] = round($value['size']/1024,2).'Kb';
				if (intval($value['size']) > 1024*1024) {
					$file_list[$key]['size'] = round($value['size']/(1024*1024),2).'Mb';
				}
			}
			$log_info['file'] = $file_list ? $file_list : array();
			//处理内容换行
			// $log_info['content'] = '<p>'.$log_info['content'].'</p>';

			//查询评论
			$m_log_talk = M('LogTalk');//日志评论回复表
			$comment_list = $m_log_talk->where(array('log_id'=>$log_id,'parent_id'=>0))->order('g_mark asc,create_time asc')->field('send_role_id,talk_id,content,create_time,g_mark')->select();
			foreach ($comment_list as $k=>$v) {
				$user_info = array();
				$user_info = $m_user->where('role_id = %d',$v['send_role_id'])->field('thumb_path,role_id,full_name')->find();
				$comment_list[$k]['user_name'] = $user_info['full_name'];
				$comment_list[$k]['img'] = $user_info['thumb_path'];
				$comment_list[$k]['content'] = str_replace('src="', 'src="'.'http://'.$_SERVER['HTTP_HOST'], htmlspecialchars_decode($v['content']));

				//子回复
				$comment_list_child = $m_log_talk->where('parent_id =%d and g_mark = "%s"',$v['talk_id'],$v['g_mark'])->field('send_role_id,talk_id,content,create_time,g_mark')->select();
				foreach ($comment_list_child as $key=>$val) {
					$creator_child_info = array();
					$creator_child_info = $m_user->where('role_id = %d',$val['send_role_id'])->field('thumb_path,role_id,full_name')->find();
					$comment_list_child[$key]['childimg'] = $creator_child_info['thumb_path'];
					$comment_list_child[$key]['creator_child'] = $creator_child_info;
					//处理评论中表情路径
					$content = str_replace('src="', 'src="'.'http://'.$_SERVER['HTTP_HOST'], $val['content']);
					$comment_list_child[$key]['content'] = htmlspecialchars_decode($val['content']);
					//是否有删除回复权限
					$comment_list_child[$key]['delete'] = 0;
					if(session('?admin') || $val['send_role_id'] == session('role_id') || $log_info['role_id'] == session('role_id')){
						$comment_list_child[$key]['delete'] = 1;
					}
				}
				$comment_list[$k]['comment_list_child'] = $comment_list_child ? $comment_list_child : array();
			}
			$log_info['comment_list'] = $comment_list ? $comment_list : array();
			$comment_cont = $m_log_talk->where(array('log_id'=>$log_id))->count();
			$log_info['comment_count'] = $comment_cont ? $comment_cont : '0';

			//点赞
			$m_praise = M('Praise');
			$praise_list = $m_praise->where('log_id = %d',$log_id)->select();
			$m_user = M('User');
			foreach ($praise_list as $k=>$v) {
				$praise_list[$k]['user_info'] = $m_user->where(array('role_id'=>$v['role_id']))->field('full_name,thumb_path')->find();
			}
			$log_info['praise_list'] = $praise_list ? $praise_list : array();
			$log_info['praise_count'] = $praise_list ? count($praise_list) : '0';
			if ($m_praise->where('log_id = %d and role_id = %d',$log_id,$role_id)->find()) {
				$log_info['is_praised'] = 1;
			} else {
				$log_info['is_praised'] = 0;
			}
			if (in_array($log_info['role_id'], getSubRoleId(true))) {
				$log_info['is_comment'] = 1;
			} else {
				$log_info['is_comment'] = 0;
			}
			//权限返回
			$data['permission'] = permissionlist('log',$log_info['role_id']);
			$data['data'] = $log_info ? $log_info : array();
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data,"JSON");
		}
	}

	/**
	 * 工作日志评论
	 * @param 
	 * @author 
	 * @return 
	 */
	public function comment_add(){
		if ($this->isPost()) {
			$talk_id = $_POST['talk_id'] ? intval($_POST['talk_id']) : 0;
			if ($talk_id) {
				//子回复
				$receive_role_id = $this->_post('receiveid','intval');
				$content = $this->_post('content','trim');			
				if (!$talk_id) {
					$this->ajaxReturn('','当前回复发生错误，暂不支持回复！',0);
				}
				if (!$receive_role_id) {
					$this->ajaxReturn('','当前回复对象发生错误，暂不支持回复！',0);
				}
				if (!$content) {
					$this->ajaxReturn('','回复内容必填！',0);
				}
				$m_log_talk = M('LogTalk');//日志评论回复表
				
				$talk_info = $m_log_talk->where('talk_id = %d',$talk_id)->find();
				//只展示两层评论
				$rep_data['parent_id'] = $talk_info['parent_id'] ? $talk_info['parent_id'] : $talk_id;
				$rep_data['log_id'] = $talk_info['log_id'];
				$rep_data['send_role_id'] = session('role_id');
				$rep_data['receive_role_id'] = $receive_role_id;
				$rep_data['content'] = $content;
				$rep_data['create_time'] = time();
				$rep_data['g_mark'] = $talk_info['g_mark'];
				$talk_id = $m_log_talk->add($rep_data);
				if ($talk_id) {
					$user_name = M('User')->where(array('role_id'=>session('role_id')))->getField('full_name');
					$url = U('log/mylog_view','id='.$talk_info['log_id']);
					$message_content = '<a target="_blank" href="'.$url.'">'.$user_name.' 回复了你的评论</a>';
					sendMessage($receive_role_id,$message_content,1);
					$this->ajaxReturn('','回复成功！',1);
				}else{
					$this->ajaxReturn('','回复失败！',0);
				}
			} else {
				//主评论
				$log_id = $this->_post('log_id','intval');
				$send_role_id = session('role_id');
				$content = $this->_post('content','trim');
				if (!$log_id) $this->ajaxReturn('','当前评论发生错误，暂不支持评论！',0);
				if (!$send_role_id) $this->ajaxReturn('','当前评论对象发生错误，暂不支持评论！',0);
				if (!$content) $this->ajaxReturn('','评论内容必填！',0);

				$m_log = M('Log');
				$receive_role_id = $m_log->where('log_id = %d',$log_id)->getField('role_id');//接收者role_id
				if (!$receive_role_id) {
					$this->ajaxReturn('','该日志不存在或已删除！',0);
				}
				$data['log_id'] = $log_id;
				$data['send_role_id'] = $send_role_id;
				$data['receive_role_id'] = $receive_role_id;//接收者role_id
				$data['content'] = $content;
				$data['create_time'] = time();

				$m_log_talk = M('LogTalk');//日志评论回复表
				$log_talk_id = $m_log_talk->add($data);
				if ($log_talk_id) {
					$m_log->where('log_id = %d',$log_id)->setField('status',2);
					$user_name = M('User')->where(array('role_id'=>$send_role_id))->getField('full_name');
					$url = U('log/mylog_view','id='.$log_id);
					$message_content = '<a target="_blank" href="'.$url.'">'.$user_name.' 评论了你的日志</a>';
					sendMessage($receive_role_id,$message_content,1);
					$g_mark = 'wk_'.$log_talk_id;
					$m_log_talk->where('talk_id = %d',$log_talk_id)->save(array('g_mark'=>$g_mark));

					$data = array();
					$data['data'] = '';
					$data['status'] = 1;
					$data['info'] = '评论成功';
					$this->ajaxReturn($data,"JSON");
				}else{
					$this->ajaxReturn('','评论失败！',0);
				}
			}
		}
	}

	/**
	 * 评论删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function replyDel() {
		if ($this->isPost()) {
			$talk_id = $_POST['talk_id'] ? intval($_POST['talk_id']) : 0;
			if (!$talk_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			//日志评论回复表
			$m_log_talk = M('LogTalk');
			$talkinfo = $m_log_talk->where('talk_id = %d',$talk_id)->find();
			$role_id = M('Log')->where('log_id = %d',$talkinfo['log_id'])->getField('role_id');
			if ($talkinfo) {
				if ($talkinfo['send_role_id'] != session('role_id') && $role_id != session('role_id')) {
					$this->ajaxReturn('','您没有权限删除！',-2);
				} else {
					if ($talkinfo['parent_id' == 0]) {
						$msg = $m_log_talk->where(array('g_mark'=>$talkinfo['g_mark'],'parent_id'=>$talk_id))->delete();
					} else {
						$msg = $m_log_talk->where(array('g_mark'=>$talkinfo['g_mark']))->delete();
					}
					if ($msg) {
						$this->ajaxReturn('','删除成功！',1);
					}else{
						$this->ajaxReturn('','删除失败！',0);
					}	
				}							
			} else {
				$this->ajaxReturn('','数据查询失败！',0);
			}
		}
	}

	/**
	 * 工作日志点赞
	 * @param 
	 * @author 
	 * @return 
	 */
	public function praise(){
		if ($this->isPost()) {
			$log_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if ($log_id) {
				$m_praise = M('Praise');
				$praise_info = $m_praise->where(array('log_id'=>$log_id,'role_id'=>session('role_id')))->find();
				if ($praise_info) {
					//取消赞
					if ($m_praise->where(array('log_id'=>$log_id,'role_id'=>session('role_id')))->delete()) {
						$number = $m_praise->where('log_id = %d',$log_id)->count();
						$info['is_praise'] = 0;
						$info['number'] = $number;

						$data['data'] = $info;
						$data['status'] = 1;
						$data['info'] = '成功';
						$this->ajaxReturn($data,'JSON');
					} else {
						$this->ajaxReturn('','取消失败',0);
					}
				} else {
					$m_praise->role_id = session('role_id');
					$m_praise->log_id = $log_id;
					if ($m_praise->add()) {
						$number = $m_praise->where('log_id = %d',$log_id)->count();
						$info['is_praise'] = 1;
						$info['number'] = $number;

						$data['data'] = $info;
						$data['status'] = 1;
						$data['info'] = '成功';
						$this->ajaxReturn($data,'JSON');
					} else {
						$this->ajaxReturn('','点赞失败！',0);
					}
				}
			} else {
				$this->ajaxReturn('','参数错误！',0);
			}
		}
	}

	/**
	 * 沟通日志创建
	 * @param module = customer 需添加沟通日志的模块
	 * @author 
	 * @return 
	 */
	public function add(){
		if ($this->isPost()) {
			$params = $_POST;
			if (!is_array($params)) {
				$this->ajaxReturn('','非法的数据格式!',0);
			}
			$module = $params['module'] ? trim($params['module']) : '';
			$model_id = isset($params['id']) ? intval($params['id']) : 0;
			if (!$model_id || !$module) {
				$this->ajaxReturn('','参数错误！',0);
			}
			if ($module == 'customer') {
				$m_r = M('RCustomerLog');
			} elseif ($module == 'business') {
				$m_r = M('RBusinessLog');
			} elseif ($module == 'leads') {
				$m_r = M('RLeadsLog');
			}
			$m_log = M('Log');

			$m_log->create($params);
			$m_log->role_id = session('role_id');
			$m_log->category_id = 1;
			$m_log->create_date = time();
			$m_log->update_date = time();
			if ($_POST['nextstep_time']) {
				$m_log->nextstep_time = $_POST['nextstep_time'];
			}

			if ($log_id = $m_log->add()) {
				if ($_POST['nextstep_time']) {
					//关联日程
					$event_res = dataEvent(trim($_POST['content']),$_POST['nextstep_time'],$module,$model_id);
				}
				$m_id = $module.'_id';
				$data['log_id'] = $log_id;
				$data[$m_id] = $model_id;
				if ($m_r->add($data)) {
					//相关模块更新时间
					if ($module == 'customer' || $module == 'business' || $module == 'leads') {
						if($module == 'business'){
							$module = 'customer';
							$model_id = M('Business')->where(array('business_id'=>$model_id))->getField('customer_id');
						}
						$pk_id = M($module)->getPk();
						if ($module == 'leads') {
							$m_leads = M('Leads');
							$leads_data = array();
							$leads_data['nextstep'] = trim($_POST['content']);
							if ($_POST['nextstep_time']) {
								$leads_data['nextstep_time'] = $_POST['nextstep_time'];
							}
							$leads_data['update_time'] = time();
							$leads_data['have_time'] = time();
							$first_time = $m_leads->where('leads_id = %d',$model_id)->getField('first_time');
							if (!$first_time) {
								$leads_data['first_time'] = time();
							}
							$res = M('Leads')->where('leads_id = %d',$model_id)->save($leads_data);
						} elseif ($module == 'customer') {
							$customer_data = array();
							//下次联系时间
							if ($_POST['nextstep_time']) {
								$customer_data['nextstep_time'] = $_POST['nextstep_time'];
							}
							$customer_data['update_time'] = time();
							$res = M('Customer')->where('customer_id = %d',$model_id)->save($customer_data);
						} elseif ($module == 'business') {
							$customer_id = M('Business')->where('business_id = %d',$model_id)->getField('customer_id');
							M('Customer')->where('customer_id = %d',$customer_id)->setField('update_time',time());
						} else {
							$res = M($module)->where("$pk_id = %d", $model_id)->setField('update_time',time());
						}
					}
					$this->ajaxReturn('','添加成功！',1);
				} else {
					$this->ajaxReturn('','添加失败！',0);
				}
			} else {
				$this->ajaxReturn('','添加失败！',0);
			}
		}
	}

	/**
	 * 沟通日志删除
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete(){
		if ($this->isPost()) {
			$log_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if (!$log_id) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$m_log = M('Log');

			$r_module = $_POST['module'] ? trim($_POST['module']) : '';
			switch ($r_module) {
				case 'business' : $r = M('RBusinessLog'); break;
				case 'customer' : $r = M('RCustomerLog'); break;
				case 'finance' : $r = M('RFinanceLog'); break;
				case 'leads' : $r = M('RLogLeads'); break;
				case 'product' : $r = M('RLogProduct'); break;
				case 'member' : $r = M('RMemberLog'); break;
				case 'order' : $r = M('ROrderLog'); break;
				case 'sales' : $r = M('RSalesLog'); break;
			}
			if ($r) {
				$log_info = $m_log->where('log_id = %d',$log_id)->find();
				if ($log_info['role_id'] != session('role_id') && !session('?admin')) {
					$this->ajaxReturn('','您没有此权利！',-2);
				}
				$msg = $m_log->where('log_id = %d',$log_id)->delete();
				if ($msg) {
					if($r->where('log_id = %d',$log_id)->find()){
						$r->where('log_id = %d',$log_id)->delete();
					}
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败，请重试！',0);
				}
			} else {
				$this->ajaxReturn('','参数错误！',0);
			}
		}
	}

	/**
	 * 沟通日志类型
	 * @param 
	 * @author 
	 * @return 
	 */
	public function logStatus(){
		if ($this->isPost()) {
			$list = M('LogStatus')->field('id,name')->select();
			$new_arr = array();
			foreach ($list as $k=>$v) {
				$new_arr[$k]['key'] = $v['id'];
				$new_arr[$k]['value'] = $v['name'];
			}
			$data['list'] = $new_arr ? $new_arr : array();
			$data['status'] = 1;
			$data['info'] = '成功';
			$this->ajaxReturn($data, "JSON");
		}
	}

}