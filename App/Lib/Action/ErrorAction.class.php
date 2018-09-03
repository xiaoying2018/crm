<?php
/**
*错误提示模块
*
**/
class ErrorAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array('index'),
			'allow'=>array('')
		);
		B('Authenticate', $action);
	}
	
	public function index(){
		$type = $_GET['error_type'] ? intval($_GET['error_type']) : 1;
		if($type){
			$data['title'] = '非常抱歉，让您看到这个页面！';
			$data['content'] = '如有疑问和建议，欢迎联系我们';
			if($type == 1){
				$data['title'] = '尊敬的用户，您的授权信息出错！';
				$data['content'] = '请联系我们';
			} elseif ($type == 2) {
				$authorize_setting = C('AUTHORIZE_SETTING');
				$end_time = date('Y年m月d日',strtotime($authorize_setting['ENDTIME']));
				$data['title'] = '尊敬的用户，您的服务已于'.$end_time.'到期！';
				$data['content'] = '我们将为您保存30天的数据，请及时联系客服续费或申请延期，以免数据丢失给您造成损失！';
			}
			$this->assign('data',$data);
			$this->display();
		}else{
			alert('success','',$_SERVEER['HTTP_REFERER']);
		}
	}
}
