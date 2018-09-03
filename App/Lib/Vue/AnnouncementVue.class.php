<?php
/**
 *公告相关
 **/
class AnnouncementVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array()
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
	 * 公告列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index(){
		if ($this->isPost()) {
			getDateTime('announcement');
			$m_announcement = M('announcement');
			if ($_REQUEST["search"]) {
				$where['title'] = array('like','%'.$_REQUEST["search"].'%');
			}
			$where['role_id'] = array('in',getPerByAction('announcement','index'));
			$where['department'] = array('like', '%('.session('department_id').')%');
			$where['status'] = array('eq', 1);
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;

			$announcement_list = $m_announcement->where($where)->field('title,announcement_id,update_time,role_id,content')->order('order_id')->page($p.',10')->select();
			$m_user = M('User');
			foreach ($announcement_list as $k=>$v) {
				//截取
				$announcement_list[$k]['sub_content'] = msubstr($v['content'],0,50);
				unset($announcement_list[$k]['content']);
				//获取操作权限
				$announcement_list[$k]['permission'] = permissionlist('announcement');
			}
			$count = $m_announcement->where($where)->count();
			$page = ceil($count/10);
			$data['page'] = $page;
			$data['list'] = $announcement_list ? $announcement_list : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}

	/**
	 * 公告详情
	 * @param 
	 * @author 
	 * @return 
	 */
	public function view(){
		if ($this->isPost()) {
			$announcement_id = intval($_POST['id']) ? : '';
			$m_announcement = M('Announcement');
			$m_announcement_data = M('AnnouncementData');
			$announcement_info = $m_announcement->where('announcement_id = %d',$announcement_id)->find();
			if(empty($announcement_info)){
				$this->ajaxReturn('','数据不存在或已删除！',0);
			}
			if(!in_array('('.session('department_id').')', explode(',',$announcement_info['department']))){
				$this->ajaxReturn('','您没有此权利！',-2);
			}
			//公告阅读记录
			if(!$m_announcement_data->where(array('announcement_id'=>$announcement_id,'role_id'=>session('role_id')))->find()){
				$data['announcement_id'] = $announcement_id;
				$data['role_id'] = session('role_id');
				$data['read_time'] = time();
				$m_announcement_data ->add($data);
			}
			$m_announcement->where('announcement_id=%d',$announcement_id)->setInc('hits');
			$announcement_info['user_name'] = M('User')->where('role_id = %d',$announcement_info['role_id'])->getField('full_name');
			$data['data'] = $announcement_info ? $announcement_info : array();
			$data['info'] = 'success';
			$data['status'] = 1;
			$this->ajaxReturn($data,'JSON');
		}
	}
}