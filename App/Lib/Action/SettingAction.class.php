<?php
class SettingAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array('clearcache'),
			'allow'=>array('getbusinessstatuslist','boxfield','mapdialog','customeshow','lockscreen','getreplybystatus','replylist','logreplyadd','logreplyedit','logreplydel','workrule','workruleedit','workruledel')
		);
		B('Authenticate',$action);
	}

	public function index(){
		$this->redirect('setting/defaultInfo');
	}
	/**
	*  打开调试模式
	*
	**/
	public function openDebug(){
		$file_path =  CONF_PATH.'app_debug.php';
		$result = file_put_contents($file_path, "<?php \n\r define ('APP_DEBUG',true);");
		if($result){
			$this->ajaxReturn(1,'',1);
		}else{
			$this->ajaxReturn(1,'',2);
		}
	}
	
	/**
	*  关闭调试模式
	*
	**/
	public function closeDebug(){
		$file_path = CONF_PATH.'app_debug.php';
		$result = file_put_contents($file_path, "<?php \n\r define ('APP_DEBUG',false);");
		if($result){
			$this->ajaxReturn(1,'',1);
		}else{
			$this->ajaxReturn(1,'',2);
		}
	}
	
	/**
	*  清空缓存文件
	*
	**/
	public function clearCache(){
		if($this->clear_Cache()){
			$this->ajaxReturn(1,'',1);
		}else{
			$this->ajaxReturn(1,'',0);
		}
		
	}
	
	/**
	*  删除缓存文件
	*
	**/
	protected function clear_Cache(){
		deldir(RUNTIME_PATH);
		return true;
	}

	/**
	*  smtp设置
	*
	**/
	public function smtp(){
		if ($this->isAjax()) {			
			if (ereg('^([a-zA-Z0-9]+[-|_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[-|_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['address'])){
				if (ereg('^([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+@([a-zA-Z0-9]+[_|_|.]?)*[a-zA-Z0-9]+.[a-zA-Z]{2,3}$',$_POST['test_email'])){
					$smtp = array('MAIL_ADDRESS'=>$_POST['address'],'MAIL_SMTP'=>$_POST['smtp'],'MAIL_LOGINNAME'=>$_POST['loginName'],'MAIL_PASSWORD'=>$_POST['password'],'MAIL_PORT'=>$_POST['port'],'MAIL_SECURE'=>$_POST['secure'],'MAIL_CHARSET'=>'UTF-8','MAIL_AUTH'=>true,'MAIL_HTML'=>true);
					C($smtp,'smtp');
					//C('','smtp');
					import('@.ORG.Mail');
					$content = L('EMALI_CONTENT');
					$message = SendMail($_POST['test_email'],L('EMALI_TITLE'),$content,L('EMALI_AUTOGRAPH'));
					if($message === true){
						$message = L('SENT SUCCESSFULLY');
					} else {
						$message = $message ? $message : L('SENT FAILED');
					}
				} else {
					$message = L('TEST YOUR INBOX MALFORMED');
				}
			} else {
				$message = L('EMAIL FORMAT ERROR');
			}
			$this->ajaxReturn("", $message, 1);			
		} elseif($this->isPost()) {
			$edit = false;
			$m_config = M('Config');
			if(empty($_POST['address'])){				
				$this->error(L('NEED_ADDRESS'));
			}
			if(empty($_POST['smtp'])){				
				$this->error(L('NEED_SMTP'));
			}
			if(empty($_POST['port'])){				
				$this->error( L('NEED_PORT'));
			}
			if(empty($_POST['loginName'])){				
				$this->error(L('NEED_LOGINNAME'));
			}
			if(empty($_POST['password'])){				
				$this->error(L('NEED_PASSWORD'));
			}		
			if(is_email($_POST['address'])){
				$demosmtp = array('MAIL_ADDRESS'=>$_POST['address'],'MAIL_SMTP'=>$_POST['smtp'],'MAIL_PORT'=>$_POST['port'],'MAIL_LOGINNAME'=>$_POST['loginName'],'MAIL_PASSWORD'=>$_POST['password'],'MAIL_SECURE'=>$_POST['secure'],'MAIL_CHARSET'=>'UTF-8','MAIL_AUTH'=>true,'MAIL_HTML'=>true);
				$smtp['name'] = 'smtp';
				$smtp['value'] =serialize($demosmtp);
				if($m_config->where('name = "smtp"')->find()){
					if($m_config->where('name = "smtp"')->save($smtp)){
						F('smtp',$demosmtp);
						$edit = true;
					}
				} else {
					if($m_config->add($smtp)){
						F('smtp',$demosmtp);
						$edit = true;
					}else{
						$this->error(L('ADD FAILED'));
					}
				}
			}else{			
				$this->error(L('EMAIL FORMAT ERROR'));
			}
			if($edit){
				alert('success',L('SUCCESSFULLY SET AND SAVED'),U('setting/smtp'));
			}else{				
				$this->error(L('DATA UNCHANGED'));
			}
		} else {
			$smtp = M('Config')->where('name = "smtp"')->getField('value');		
			$this->smtp = unserialize($smtp);			
			$this->alert = parseAlert();
			$this->display();			
		}
	}
	
	public function sms(){
		if($this->isAjax()){
			if($_POST['uid'] && $_POST['passwd'] && $_POST['phone']){
				$result = sendtestSMS(trim($_POST['uid']), trim($_POST['passwd']), $_POST['phone']);
				if($result < 1){
					$message = L('ACCOUNT INFORMATION ERROR');
				}elseif($result == 1){
					$message = L('SENT SUCCESSFULLY SMS');
				}
			}else{
				$message = L('SENT FAILED SMS');
			}
			$this->ajaxReturn("", $message, 1);
		}elseif($this->isPost()){	
			$m_config = M('Config');
			if(strstr(trim($_POST['uid']), 'BST') === false)	$message = L('ACCOUNT NAME IS MALFORMED');
			$sms = array('uid'=>trim($_POST['uid']),'passwd'=>trim($_POST['passwd']),'sign_name'=>trim($_POST['sign_name']),'sign_sysname'=>trim($_POST['sign_sysname']));
			$sms['name'] = 'sms';
			$sms['value'] =serialize($sms);			
			if($m_config->where('name = "sms"')->find()){
				if($m_config->where('name = "sms"')->save($sms)){
					F('sms',$sms);
					$edit = true;
				} 
			} else {
				if($m_config->add($sms)){
					F('sms',$sms);
					$edit = true;
				}else{				
					$this->error(L('EMAIL FORMAT ERROR'));
				}
			}			
			if($edit){
				alert('success',L('SUCCESSFULLY SET AND SAVED'),U('setting/sms'));
			}else{				
				$this->error(L('DATA UNCHANGED'));
			}
		}else{		
			$sms = M('Config')->where('name = "sms"')->getField('value');
			$this->sms = unserialize($sms);
			$this->alert = parseAlert();
			$this->display();		
		}
	}

	/**
	*  银行账户设置
	*
	**/
	public function category(){
		//判断权限

		$m_bank_account = M('BankAccount');
		$bank_list = $m_bank_account->select();

		$m_receivingorder = M('Receivingorder');
		$m_paymentorder = M('Paymentorder');
		$m_account_money = M('AccountMoney');

		foreach($bank_list as $k=>$v){
			//计算账户余额
			//查询最新的初始化余额(审核通过，并且时间最新的)
			$account_money = '0.00';
			$account_info = $m_account_money->where(array('account_id'=>$v['account_id']))->order('create_time desc')->find();
			$where = array();
			if($account_info){
				$where['update_time'] = array('gt',$account_info['create_time']);
			}
			$where['bank_account_id'] = $v['account_id'];
			$where['status'] = 1;

			//收
			$receivingorder_money = '0.00';
			$receivingorder_money = $m_receivingorder->where($where)->sum('money');
			//支
			$paymentorder_money = '0.00';
			$paymentorder_money = $m_paymentorder->where($where)->sum('money');

			$balance = '0.00';
			if($account_info){
				$balance = $account_info['money']+$receivingorder_money-$paymentorder_money;
			}else{
				$balance = $receivingorder_money-$paymentorder_money;
			}
			$bank_list[$k]['balance'] = $balance;
		}
		$this->bank_list = $bank_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	*  银行账户(添加)
	*
	**/
	public function category_add(){
		//判断权限

		if ($this->isPost()) {
			$m_bank_account = M('bank_account');
			if(!trim($_POST['open_bank'])){
				alert('error','请填写开户行！',$_SERVER['HTTP_REFERER']);
			}
			if(!trim($_POST['bank_account'])){
				alert('error','请填写银行账户！',$_SERVER['HTTP_REFERER']);
			}
			if(!trim($_POST['company'])){
				alert('error','请填写收款单位！',$_SERVER['HTTP_REFERER']);
			}
			if($m_bank_account->create()){
				if ($m_bank_account->add()) {
					alert('success', '银行账户添加成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '银行账户添加失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '银行账户添加失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	*  银行账户(编辑)
	*
	**/
	public function category_edit(){
		//判断权限

		$m_bank_account = M('bank_account');
		if ($this->isGet()) {
			$account_id = intval(trim($_GET['id']));
			$this->bank_info = $m_bank_account = M('bank_account')->where('account_id = %d', $account_id)->find();
			$this->display();
		} else {
			if(!trim($_POST['open_bank'])){
				alert('error','请填写开户行！',$_SERVER['HTTP_REFERER']);
			}
			if(!trim($_POST['bank_account'])){
				alert('error','请填写银行账户！',$_SERVER['HTTP_REFERER']);
			}
			if(!trim($_POST['company'])){
				alert('error','请填写收款单位！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_bank_account->create()) {
				if ($m_bank_account->save()) {
					alert('success', '银行账户编辑成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '银行账户编辑失败！', $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error','银行账户编辑失败！', $_SERVER['HTTP_REFERER']);
			}
		}
	}

	/**
	*  银行账户(删除)
	*
	**/
	public function category_delete(){
		//判断权限

		$m_bank_account = M('bank_account');
		if ($_POST['account_id']) {
			$id_array = $_POST['account_id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['account_id'];
			}
			if ($m_bank_account->where('account_id in (%s)', implode(',', $id_array))->delete()){
				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}
	}

	/**
	*  商机状态设置
	*
	**/
	public function businessStatus(){
		$type_id = $_GET['type_id'] ? intval($_GET['type_id']) : 0;
		$type_info = M('BusinessType')->where(array('id'=>$type_id))->find();
		if (!$type_info) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$this->statusList = M('BusinessStatus')->where(array('type_id'=>$type_id))->order('order_id')->select();
		$this->type_info = $type_info;
		$this->alert=parseAlert();
		$this->display();
	}
	
	/**
	*  添加商机状态
	*
	**/
	public function businessStatusAdd(){
		if ($this->isPost()) {
			$type_id = intval($_POST['type_id']);
			$m_status = M('BusinessStatus');
			if(!trim($_POST['name'])){
				alert('error','请填写状态名！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_status->where(array('type_id'=>$type_id,'name'=>trim($_POST['name'])))->find()) {
				alert('error','该状态名已存在！',$_SERVER['HTTP_REFERER']);
			}
			if($m_status->create()){
				$order_id = $m_status->where(array('type_id'=>intval($_POST['type_id']),'order_id'=>array('not in',array('99','100'))))->max('order_id');
				$m_status->order_id = $order_id ? $order_id+1 : 1;
				if ($m_status->add()) {
					alert('success', L('SUCCESSFULLY ADDED'), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('THE STATE NAME ALREADY EXISTS'), $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', L('ADD FAILED'), $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}
	
	/**
	*  修改商机状态
	*
	**/
	public function businessStatusEdit(){
		$m_status = M('BusinessStatus');
		if ($this->isGet()) {
			$status_id = intval($_GET['id']);
			$this->status = $m_status->where('status_id = %d', $status_id)->find();
			$this->display();
		} else {
			if(!trim($_POST['name'])){
				alert('error','请填写状态名！',$_SERVER['HTTP_REFERER']);
			}
			$status_id = intval($_POST['status_id']);
			$type_id = $m_status->where(array('status_id'=>$status_id))->getField('type_id');
			if ($m_status->where(array('type_id'=>$type_id,'name'=>trim($_POST['name']),'status_id'=>array('neq',$status_id)))->find()) {
				alert('error','该状态名已存在！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_status->create()) {
				if ($m_status->save()) {
					alert('success', L('SUCCESSFULLY EDIT'), $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('DATA UNCHANGED'), $_SERVER['HTTP_REFERER']);
				}
			} else {
				alert('error', L('EDIT FAILED'), $_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	/**
	*  删除商机状态
	*
	**/
	public function businessStatusDelete(){
		if ($_POST['status_id']) {
			$id_array = $_POST['status_id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['status_id'];
			}
			if (M('Business')->where('status_id in (%s)', implode(',', $id_array))->select() || M('RBusinessStatus')->where('status_id in (%s)', implode(',', $id_array))->select()) {
				$this->ajaxReturn('','有商机正在使用该阶段，无法删除！',0);
			} else {
				if (M('BusinessStatus')->where('status_id in (%s)', implode(',', $id_array))->delete()){
					$this->ajaxReturn('','删除成功！',1);
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			}
		}
	}
	
	/**
	*  商机状态排序
	*
	**/
	public function businessStatusSort(){
		if ($this->isGet()) {
			$status = M('BusinessStatus');
			$a = 0;
			foreach (explode(',', $_GET['postion']) as $v) {
				$a++;
				$status->where('status_id = %d', $v)->setField('order_id',$a);
			}
			$this->ajaxReturn('1', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('0', L('EDIT FAILED'), 1);
		}
	}

	/**
	*  ajax获取商机状态列表
	*
	**/
	public function getBusinessStatusList(){
		$statusList = M('BusinessStatus')->order('order_id')->select();
		$this->ajaxReturn($statusList, '', 1);
	}
	
	/**
	*  系统默认设置
	*
	**/
	public function defaultinfo(){
		if($this->isGet()){
			$m_config = M('Config');
			$defaultinfo = $m_config->where('name = "defaultinfo"')->getField('value');
			$this->defaultinfo = unserialize($defaultinfo);
			$this->alert = parseAlert();
			$this->display();
		}elseif($this->isAjax()){
			if(!session('?admin')){
				$ajaxRet['status'] = 4;
				$ajaxRet['msg'] = '您没有此权限！';
				$this->ajaxReturn($ajaxRet);
			}
			$m_config = M('Config');
			if($_FILES['blob']['error'] != 4){
				$ajaxRet = array();
				// dump($_FILES['img']);die;
				import('@.ORG.UploadFile');
				import('@.ORG.Image');//引入缩略图类
				$Img = new Image();//实例化缩略图类
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('jpg', 'gif', 'png', 'jpeg');
				$dirname = UPLOAD_PATH.'/' . date('Ym', time()).'/'.date('d', time()).'/';
				$upload->thumb = true;//生成缩图
				$upload->thumbRemoveOrigin = false;//是否删除原图
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
					$ajaxRet['msg'] = 'Logo提交失败！';
					$this->ajaxReturn($ajaxRet);
				}
				$data['logo'] = $upload;
				$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);
				$data['logo_thumb_path'] = $thumb_path;
			}
			//由于logo和其他参数是存在一起的，所以保存logo时其他信息也要读取处理一下
			$defaultinfo_info = $m_config->where('name = "defaultinfo"')->find();
			$defaultinfo = unserialize($defaultinfo_info['value']);
			// $data['name'] = $_POST['name'];
			// if ($data['name'] == "") {
			// 	$ajaxRet['status'] = 6;
			// 	$ajaxRet['msg'] = L('THE SYSTEM NAME CAN NOT BE EMPTY');
			// 	$this->ajaxReturn($ajaxRet);
			// }
			$data['name'] = $defaultinfo['name'];
			$data['logo_min'] = $defaultinfo['logo_min'];
			$data['logo_min_thumb_path'] = $defaultinfo['logo_min_thumb_path'];
			$data['description'] = $defaultinfo['description'];
			$data['state'] = $defaultinfo['state'];
			$data['city'] = $defaultinfo['city'];
			$allow_file_type = explode(',',$defaultinfo['allow_file_type']);
			if(!empty($defaultinfo['allow_file_type'])){
				$allow_list = array();
				foreach($allow_file_type as $k=>$v){
					if(trim($v) != 'php'){
						$allow_list[] = $v;
					}
				}
				$allow_file_list = implode(',',$allow_list);
			}else{
				$allow_file_list = '';
			}
			$data['allow_file_type'] = !empty($allow_file_list) ? trim($allow_file_list) : 'pdf,doc,jpg,jpeg,png,gif,txt,doc,xls,zip,docx,rar';
			$data['contract_alert_time'] = intval(trim($defaultinfo['contract_alert_time']));
			$data['task_model'] = trim($defaultinfo['task_model']);
			$data['is_invoice'] = trim($defaultinfo['is_invoice']);	
			if($defaultinfo_info){
				$default = unserialize($defaultinfo_info['value']);					
				if (!isset($data['logo']) || $data['logo'] == "") {
					$data['logo'] = $default['logo'];
				}
				if($m_config->where('name = "defaultinfo"')->save(array('value'=>serialize($data)))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				} else {
					$result_defaultinfo = false;
				}
			} else {
				if($m_config->add(array('value'=>serialize($data), 'name'=>'defaultinfo'))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				}else{
					$result_defaultinfo = false;
				}
			}
			if($result_defaultinfo){
				$ajaxRet['status'] = 1;
				$ajaxRet['msg'] = '保存成功！';
				$this->ajaxReturn($ajaxRet);
			}else{
				$ajaxRet['status'] = 5;
				$ajaxRet['msg'] = '保存失败！';
				$this->ajaxReturn($ajaxRet);
			}
		}elseif($this->isPost()){
			if(!session('?admin')){
				alert('error','您没有此权限！',$_SERVER['HTTP_REFERER']);
			}
			$sys_name = $this->_post('name');
			if (!$sys_name) alert('error',L('THE SYSTEM NAME CAN NOT BE EMPTY'),$_SERVER['HTTP_REFERER']);	
			$m_config = M('Config');
			//系统logo
			if ($_FILES['logo_min']['size'] > 0) {
				//如果有文件上传 上传附件
				import('@.ORG.UploadFile');
				import('@.ORG.Image');//引入缩略图类
				$Img = new Image();//实例化缩略图类
				//导入上传类
				$upload = new UploadFile();
				//设置上传文件大小
				$upload->maxSize = 20000000;
				//设置附件上传目录
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				$upload->allowExts  = array('jpg','jpeg','png','gif');// 设置附件上传类型
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error','创建文件夹失败！',$_SERVER['HTTP_REFERER']);
				}
				$upload->savePath = $dirname;
				$upload->thumb = true;//生成缩图
				$upload->thumbRemoveOrigin = false;//是否删除原图
				if(!$upload->upload()) {// 上传错误提示错误信息
					alert('error',$upload->getErrorMsg(),$_SERVER['HTTP_REFERER']);
				}else{// 上传成功 获取上传文件信息
					$info =  $upload->getUploadFileInfo();
					$upload = $dirname . $info[0]['savename'];
					$data['logo_min'] = $upload;
					$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);					
					$data['logo_min_thumb_path'] = $thumb_path;
				}
			}
			//由于logo和其他参数是存在一起的，所以保存logo时其他信息也要读取处理一下
			$defaultinfo_info = $m_config->where('name = "defaultinfo"')->find();
			$defaultinfo = unserialize($defaultinfo_info['value']);
			$data['name'] = $sys_name;
			
			$data['description'] = $_POST['description'];
			$data['logo'] = $defaultinfo['logo'];
			$data['logo_thumb_path'] = $defaultinfo['logo_thumb_path'];
			$data['state'] = $defaultinfo['state'];
			$data['city'] = $defaultinfo['city'];
			$allow_file_type = explode(',',$defaultinfo['allow_file_type']);
			if(!empty($defaultinfo['allow_file_type'])){
				$allow_list = array();
				foreach($allow_file_type as $k=>$v){
					if(trim($v) != 'php'){
						$allow_list[] = $v;
					}
				}
				$allow_file_list = implode(',',$allow_list);
			}else{
				$allow_file_list = '';
			}
			$data['allow_file_type'] = !empty($allow_file_list) ? trim($allow_file_list) : 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,txt,zip';
			$data['contract_alert_time'] = intval(trim($defaultinfo['contract_alert_time']));
			$data['task_model'] = trim($defaultinfo['task_model']);
			$data['is_invoice'] = trim($defaultinfo['is_invoice']);
			
			if($defaultinfo_info){
				$default = unserialize($defaultinfo_info['value']);		
				if (!isset($data['logo_min']) || $data['logo_min'] == "") {
					$data['logo_min'] = $default['logo_min'];
					$data['logo_min_thumb_path'] = $default['logo_min_thumb_path'];
				}
				if($m_config->where('name = "defaultinfo"')->save(array('value'=>serialize($data)))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				} else {
					$result_defaultinfo = false;
				}
			} else {
				if($m_config->add(array('value'=>serialize($data), 'name'=>'defaultinfo'))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				}else{
					$result_defaultinfo = false;
				}
			}
			if($result_defaultinfo){
				alert('success','保存成功！',$_SERVER['HTTP_REFERER']);
			}else{
				alert('error','保存失败！',$_SERVER['HTTP_REFERER']);
			}
		}
	}
	
	/**
	*  业务参数设置
	*
	**/
	public function setup(){
		$m_config = M('Config');
		$defaultinfo = $m_config->where('name = "defaultinfo"')->getField('value');
		$default_info = unserialize($defaultinfo);
		if($this->isGet()){
			$this->defaultinfo = $default_info;
		
			$config_array = array();
			$config_list = $m_config->select();
			foreach($config_list as $k=>$v){
				$config_array[$v['name']] = $v['value'];
			}
			$this->assign('config_array',$config_array);
			//DEBUG模式是否打开
			if(APP_DEBUG == 1){
				$this->app_debug = 1;
			}else{
				$this->app_debug = 0;
			}
			$this->alert = parseAlert();
			$this->display();
		}elseif($this->isPost()){
			$data['logo'] = $default_info['logo'];
			$data['logo_min'] = $default_info['logo_min'];
			$data['name'] = trim($default_info['name']);
			if ($data['name'] == "") {
				alert('error',L('THE SYSTEM NAME CAN NOT BE EMPTY'),U('setting/setup'));
			}
			$data['description'] = trim($default_info['description']);
			$data['state'] = trim($default_info['state']);
			$data['city'] = trim($default_info['city']);
			$allow_file_type = explode(',',$_POST['allow_file_type']);
			if(!empty($_POST['allow_file_type'])){
				$allow_list = array();
				foreach($allow_file_type as $k=>$v){
					if($v != 'php'){
						$allow_list[] = $v;
					}
				}
				$allow_file_list = implode(',',$allow_list);
			}else{
				$allow_file_list = '';
			}
			$data['allow_file_type'] = !empty($allow_file_list) ? trim($allow_file_list) : 'pdf,doc,ppt,txt,xls,zip,docx,rar,pptx,xlsx,jpg,jpeg,png,gif';
			$data['contract_alert_time'] = intval(trim($_POST['contract_alert_time']));
			$data['task_model'] = trim($_POST['task_model']);
			$data['is_invoice'] = trim($_POST['is_invoice']);
			$data['state'] = $_POST['state'];
			$data['city'] = $_POST['city'];
			if($defaultinfo){
				$default = unserialize($defaultinfo['value']);					
				if (!isset($data['logo']) || $data['logo'] == "") {
					$data['logo'] = $default['logo'];
				}				
				if($m_config->where('name = "defaultinfo"')->save(array('value'=>serialize($data)))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				} else {
					$result_defaultinfo = false;
				}
			} else {
				if($m_config->add(array('value'=>serialize($data), 'name'=>'defaultinfo'))){
					F('defaultinfo',$data);
					$result_defaultinfo = true;
				}else{
					$result_defaultinfo = false;
				}
			}
			//是否替换原来的
			$bc_check = $m_config -> where('name="bc_check"') -> setField('value',$_POST['bc_check']);
			$bc_info = $m_config-> where('name="business_custom"')->find();
			$m_business = M('Business');
			if($bc_info['value'] != $_POST['business_custom'] && $_POST['bc_check'] == 1){
				$bus_list = $m_business->where('business_id >0')->select();
				foreach($bus_list as $kk=>$vv){
					$new_num = str_replace($vv['prefixion'],$_POST['business_custom'],$vv['code']);
					$m_business->where('business_id =%d',$vv['business_id'])->setField('code',$new_num);
				}
				$m_business->where('business_id >0')->save(array('prefixion'=>$_POST['business_custom']));
			}

			$cc_check = $m_config -> where('name="cc_check"') -> setField('value',$_POST['cc_check']);
			$cc_info = $m_config-> where('name="contract_custom"')->find();
			$m_contract = M('Contract');
			if($cc_info['value'] != $_POST['contract_custom'] && $_POST['cc_check'] == 1){
				$contract_list = $m_contract->where('contract_id >0')->select();
				foreach($contract_list as $kk=>$vv){
					$new_num = str_replace($vv['prefixion'],$_POST['contract_custom'],$vv['number']);
					$m_contract->where('contract_id =%d',$vv['contract_id'])->setField('number',$new_num);
				}
				$m_contract->where('contract_id >0')->save(array('prefixion'=>$_POST['contract_custom']));
			}

			$fc_check = $m_config -> where('name="fc_check"') -> setField('value',$_POST['fc_check']);
			$fc_info = $m_config-> where('name="receivables_custom"')->find();
			$m_receivables = M('Receivables');
			if($fc_info['value'] != $_POST['receivables_custom'] && $_POST['fc_check'] == 1){
				$receivables_list = $m_receivables->where('receivables_id >0')->select();
				foreach($receivables_list as $kk=>$vv){
					$new_num = str_replace($vv['prefixion'],$_POST['receivables_custom'],$vv['name']);
					$m_receivables->where('receivables_id =%d',$vv['receivables_id'])->setField('name',$new_num);
				}
				$m_receivables->where('receivables_id >0')->save(array('prefixion'=>$_POST['receivables_custom']));
			}

			$uc_check = $m_config -> where('name="uc_check"') -> setField('value',$_POST['uc_check']);
			$uc_info = $m_config-> where('name="user_custom"')->find();
			$m_user = M('User');
			if($uc_info['value'] != $_POST['user_custom'] && $_POST['uc_check'] == 1){
				$role_list = $m_user->where('user_id >0')->select();
				foreach($role_list as $kk=>$vv){
					$new_num = str_replace($vv['prefixion'],$_POST['user_custom'],$vv['number']);
					$m_user->where('user_id =%d',$vv['user_id'])->setField('number',$new_num);
				}
				$m_user->where('user_id >0')->save(array('prefixion'=>$_POST['user_custom']));
			}
			//改变合同前缀名
			if(!$m_config-> where('name="contract_custom"')->find()){
				$contract_custom['name'] = 'contract_custom';
				$contract_custom['value'] = $_POST['contract_custom'];
				$contract_custom = $m_config -> add($contract_custom);    
			}else {
				$contract_custom = $m_config -> where('name="contract_custom"') -> setField('value',$_POST['contract_custom']);
			}
			//应收款编号前缀
			if(!$m_config-> where('name="receivables_custom"')->find()){
				$receivables_custom['name'] = 'receivables_custom';
				$receivables_custom['value'] = $_POST['receivables_custom'];
				$receivables_custom = $m_config -> add($receivables_custom);
			}else {
				$receivables_custom = $m_config -> where('name="receivables_custom"') -> setField('value',$_POST['receivables_custom']);
			}
			//商机编号前缀
			if(!$m_config-> where('name="business_custom"')->find()){
				$business_custom['name'] = 'business_custom';
				$business_custom['value'] = $_POST['business_custom'];
				$business_custom = $m_config -> add($business_custom);
			}else {
				$business_custom = $m_config -> where('name="business_custom"') -> setField('value',$_POST['business_custom']);
			}
			//员工编号前缀
			if(!$m_config-> where('name="user_custom"')->find()){
				$user_custom['name'] = 'user_custom';
				$user_custom['value'] = $_POST['user_custom'];
				$user_custom = $m_config -> add($user_custom);
			}else {
				$user_custom = $m_config -> where('name="user_custom"') -> setField('value',$_POST['user_custom']);
			}
			//客户数量限制
			if(!$m_config-> where('name="opennum"')->find()){
				$cus_num['name'] = 'opennum';
				$cus_num['value'] = $_POST['opennum'];
				$customer_num = $m_config -> add($cus_num);
			}else {
				$customer_num = $m_config -> where('name="opennum"') -> setField('value',$_POST['opennum']);
			}
			//回款到期提醒
			if(!$m_config-> where('name="receivables_time"')->find()){
				$rec_num['name'] = 'receivables_time';
				$rec_num['value'] = $_POST['receivables_time'];
				$rec_date = $m_config -> add($rec_num);
			}else {
				$rec_date = $m_config -> where('name="receivables_time"') -> setField('value',$_POST['receivables_time']);
			}

			$leads_outdays = $m_config -> where('name="leads_outdays"') -> setField('value',$_POST['leads_outdays']);
			$result_customer_outdays = $m_config->where('name = "customer_outdays"')->setField('value', $_POST['customer_outdays']);
			$result_contract_outdays = $m_config->where('name = "contract_outdays"')->setField('value', $_POST['contract_outdays']);
			//判断如果重新启用则更改客户回收时间
			$old_openrecycle = $m_config ->where('name = "openrecycle"')->getField('value');
			if($old_openrecycle == 1){
				if($_POST['openrecycle'] == 2){
					$c_data['get_time'] = time();
					$c_data['update_time'] = time();
					M('customer')->where('is_deleted = 0')->save($c_data);
				}
			}
			$m_config->where('name = "openrecycle"')->setField('value', $_POST['openrecycle']);
			$result_openrecycle = $m_config->where('name = "openrecycle"')->setField('value', $_POST['openrecycle']);
			$result_customer_limit_condition = $m_config->where('name = "customer_limit_condition"')->setField('value', $_POST['customer_limit_condition']);
			$result_customer_limit_counts = $m_config->where('name = "customer_limit_counts"')->setField('value', $_POST['customer_limit_counts']);
			$is_invoice = $m_config->where('name = "is_invoice"')->setField('value', $_POST['is_invoice']);
			if($result_defaultinfo || $result_contract_outdays || $contract_custom  || $leads_outdays || $result_customer_outdays || $result_customer_limit_condition || $result_customer_limit_counts || $is_invoice || $result_openrecycle || $bc_check || $cc_check || $fc_check || $business_custom || $receivables_custom || $user_custom || $customer_num ||$rec_date){
				alert('success',L('SUCCESSFULLY SET AND SAVED'),U('setting/setup'));
			} else {
				alert('error',L('DATA UNCHANGED'),U('setting/setup'));
			}
		}
	}
	
	/**
	*  自定义字段列表
	*
	**/
	public function fields(){
		$model = $this->_get('model','trim','customer');
		$model_array = array('leads','customer','contacts','product','business','contract');
		if(!in_array(trim($_GET['model']),$model_array)){
			$model = 'customer';
		}
		$fields = M('fields')->where(array('model'=>$model))->order('order_id ASC')->select();
		foreach($fields as $k=>$v){
			if($v['is_validate'] == 1 && $v['is_null'] == 1){
				$fields[$k]['is_null'] = '是';
			}else{
				$fields[$k]['is_null'] = '否';
			}
		}
		$this->assign('model',$model);
		$this->assign('fields',$fields);
		$this->alert=parseAlert();
		$this->display();
	}
	
	/**
	*  自定义字段是否列表显示
	*
	**/
	public function indexShow(){
		$field = M('fields');
		$field_id = $this->_request('field_id','intval',0);
		if($this->isAjax()){
			if($field_id == 0){
				$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
			}
			$field_info = $field->where(array('field_id'=>$field_id))->find();
			if($field_info['in_index']) {
				if($field ->where('field_id = %d', $field_id)->setField('in_index', 0)){
					$this->ajaxReturn('','success',1);
				}else{
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			}else{
				if($field ->where('field_id = %d', $field_id)->setField('in_index', 1)){
					$this->ajaxReturn('','success',1);
				}else{
					$this->ajaxReturn('','修改失败，请重试！',0);
				}
			}
		}
		
	}
	
	/**
	*  添加自定义字段
	*
	**/
	public function fieldAdd(){
		$field = M('fields');
		if($this->isPost()){
			$field_model = D('Field');

			$field_str = 'crm_';
			for ($i = 1; $i <= 6; $i++) {
				$field_str .= chr(rand(97, 122));
			}

			$data['model']         = $this->_post('model'); //模块名称
			$data['field']         = $field_str; //字段名称
			$data['name']         = $this->_post('name'); //标识名称
			$data['form_type']     = $this->_post('form_type'); //字段类型
			$data['default_value'] = $this->_post('default_value');  //默认值
			$data['max_length']    = $this->_post('max_length');
			$data['is_main']       = $this->_post('is_main');
			if($field->where(array('field'=>$field_str,'model'=>array(array('eq',$data['model']),array('eq',''),'OR')))->find()){
				alert('error',L('THE FIELD NAME ALREADY EXISTS'),$_SERVER['HTTP_REFERER']);
			}
			if($field->where(array('name'=>$data['name'],'model'=>array(array('eq',$data['model']),array('eq',''),'OR')))->find()){
				alert('error','该标识名已存在',$_SERVER['HTTP_REFERER']);
			}

			if($field_model->add($data) !== false){
				$field->create();
				$field->field = $field_str; //字段名称
				if($this->_post('form_type') == 'box'){
					$setting = $this->_post('setting');
					if(!empty($setting['options'])){
						$field->setting = 'array(';
						$field->setting .= "'type'=>'$setting[boxtype]','data'=>array(";
						$i = 0;
						$options = explode(chr(10),$setting['options']);
						$s = array();
						foreach($options as $v){
							$v = trim(str_replace(chr(13),'',$v));
							if($v != '' && !in_array($v ,$s)){
								$i++;
								$field->setting .= "$i=>'$v',";
								$s[] = $v;
							}
						}
						$field->setting = substr($field->setting,0,strlen($field->setting) -1 ) .'))';
					}else{
						$field->setting = "array('type'=>'$setting[boxtype]')";
					}
				}
				$field->add();
				$this->clear_Cache();
				alert('success',L('ADD CUSTOM FIELD SUCCESS'),$_SERVER['HTTP_REFERER']);
			}else{
				if($error = $field_model->getError()){
					alert('error',$error,$_SERVER['HTTP_REFERER']);
				}else{
					alert('error',L('ADDING CUSTOM FIELDS TO FAIL'),$_SERVER['HTTP_REFERER']);
				}
			}
		}else{
			$this->assign('model',$this->_get('model','trim','customer'));
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*  修改自定义字段
	*
	**/
	public function fieldEdit(){
		$field = M('fields');
		$field_id = $this->_request('field_id','intval',0);
		if($field_id == 0) alert('error',L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		$field_info = $field->where(array('field_id'=>$field_id))->find();
		if($field_info['operating'] == 2)  alert('error',L('SYSTEM FIXED FIELD PROHIBIT MODIFICATION'),$_SERVER['HTTP_REFERER']);;
		if($this->isPost()){
			$field_model = D('Field');
			$data['model']         = $field_info['model']; //模块名称
			$data['field']         = $field_info['operating'] == 0 ? $this->_post('field') : $field_info['field']; //字段名称
			$data['field_old']     = $field_info['field']; //字段名称
			$data['form_type']     = $field_info['form_type']; //字段类型
			$data['default_value'] = $this->_post('default_value');  //默认值
			$data['max_length']    = $this->_post('max_length');
			$data['is_main']       = $field_info['is_main'];
			$data['name']         = $this->_post('name'); //标识名称
			
			if($field->where(array('field'=>$data['field'],'model'=>array(array('eq',$data['model']),array('eq',''),'OR'),'field_id'=>array('neq',$field_id)))->find()){
				alert('error',L('THE FIELD NAME ALREADY EXISTS'),$_SERVER['HTTP_REFERER']);
			}
			if($field_model->save($data) !== false){
				$field->create();
				if($field_info['form_type'] == 'box'){
					eval('$field_info["setting"] = '.$field_info["setting"].';');
					$boxtype = $field_info['setting']['type'];
					$setting = $this->_post('setting');
					if(!empty($setting['options'])){
						$field->setting = 'array(';
						$field->setting .= "'type'=>'$setting[boxtype]','data'=>array(";
						$i = 0;
						$options = explode(chr(10),$setting['options']);
						$s = array();
						foreach($options as $v){
							$v = trim(str_replace(chr(13),'',$v));
							if($v != '' && !in_array($v ,$s)){
								$i++;
								$field->setting .= "$i=>'$v',";
								$s[] = $v;
							}
						}
						$field->setting = substr($field->setting,0,strlen($field->setting) -1 ) .'))';
					}else{
						$field->setting = "array('type'=>'$setting[boxtype]')";
					}
				}
				$field->save();
				$this->clear_Cache();
				alert('success',L('MODIFY CUSTOM FIELD SUCCESS'), $_SERVER['HTTP_REFERER']);
			}else{
				if($error = $field_model->getError()){
					alert('error',$error,$_SERVER['HTTP_REFERER']);
				}else{
					alert('error',L('FAILED TO MODIFY CUSTOM FIELDS'),$_SERVER['HTTP_REFERER']);
				}
			}
		}else{

			if($field_info['form_type'] == 'box'){
				eval('$field_info["setting"] = '.$field_info["setting"].';');
				$field_info['form_type_name'] = L('OPTIONS');
				$field_info["setting"]['options'] = implode(chr(10),$field_info["setting"]['data']);
			}else if($field_info['form_type'] == 'editor'){
				$field_info['form_type_name'] = L('EDITOR');
			}else if($field_info['form_type'] == 'text'){
				$field_info['form_type_name'] = L('TEXT');
			}else if($field_info['form_type'] == 'textarea'){
				$field_info['form_type_name'] = L('TEXTAREA');
			}else if($field_info['form_type'] == 'datetime'){
				$field_info['form_type_name'] = L('DATETIME');
			}else if($field_info['form_type'] == 'number'){
				$field_info['form_type_name'] = L('NUMBER');
			}else if($field_info['form_type'] == 'floatnumber'){
				$field_info['form_type_name'] = L('FLOATNUMBER');
			}else if($field_info['form_type'] == 'address'){
				$field_info['form_type_name'] = L('ADDRESS');
			}else if($field_info['form_type'] == 'phone'){
				$field_info['form_type_name'] = L('PHONE');
			}else if($field_info['form_type'] == 'mobile'){
				$field_info['form_type_name'] = L('MOBILE');
			}else if($field_info['form_type'] == 'email'){
				$field_info['form_type_name'] = L('EMAIL');
			}
			$this->assign('fields',$field_info);
			$this->assign('models',array('customer'=>L('CUSTOMER'),'business'=>L('BUSINESS'),'contacts'=>L('CONTACTS')));
			$this->alert = parseAlert();
			$this->display();
		}
	}
	
	/**
	*  删除自定义字段
	*
	**/
	public function fieldDelete(){
		$field = M('fields');
		if($this->isPost()){
			$field_id = is_array($_POST['field_id']) ? implode(',', $_POST['field_id']) : '';
			if ('' == $field_id) {
				alert('error', L('NOT CHOOSE ANY'), $_SERVER['HTTP_REFERER']);
				die;
			} else {
				$where['field_id'] = array('in',$field_id);
				$where['operating'] = array('not in', array(3,0));
				
				$field_info = $field->where($where)->select();
				if($field_info){
					alert('error', L('SYSTEM FIXED FIELDS DELETE PROHIBITED'), $_SERVER['HTTP_REFERER']);
				}else{
					$field_infos = $field->where(array('field_id'=>array('in',$field_id)))->select();
					foreach($field_infos as $field_info){
						$field_model = D('Field');
						$data['model']         = $field_info['model']; //模块名称
						$data['field']         = $field_info['field']; //字段名称
						$data['is_main']       = $field_info['is_main'];
						$field_model->delete($data);
						$field->where(array('field_id'=>$field_info['field_id']))->delete();
					}
					$this->clear_Cache();
					alert('success',L('DELETE CUSTOM FIELD SUCCESS'),$_SERVER['HTTP_REFERER']);
				}
			}
		}else{
			$field_id = $this->_get('field_id','intval',0);
			if($field_id == 0) alert('error',L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
			$field_info = $field->where(array('field_id'=>$field_id))->find();
			if($field_info['operating'] != 0) alert('error',L('SYSTEM FIXED FIELDS DELETE PROHIBITED'),$_SERVER['HTTP_REFERER']);
			$field_model = D('Field');
			$data['model'] = $field_info['model']; //模块名称
			$data['field'] = $field_info['field']; //字段名称
			$data['is_main'] = $field_info['is_main'];
			if($field_model->delete($data) !== false){
				$field->where(array('field_id'=>$field_id))->delete();
				$this->clear_Cache();
				$this->ajaxReturn($data['model'],L('DELETE CUSTOM FIELD SUCCESS'),1);
				//alert('success',L('DELETE CUSTOM FIELD SUCCESS'),$_SERVER['HTTP_REFERER']);
			}else{
				//alert('error',L('FAILED TO DELETE CUSTOM FIELDS'),$_SERVER['HTTP_REFERER']);
				$this->ajaxReturn($data['model'],L('FAILED TO DELETE CUSTOM FIELDS'),0);
			}
		}
		
	}
	
	/**
	*  自定义字段排序
	*
	**/
	public function fieldsort(){	
		if(isset($_GET['postion'])){
			$fields = M('fields');
			foreach(explode(',', $_GET['postion']) AS $k=>$v) {
				$data = array('field_id'=> $v, 'order_id'=>$k);
				$fields->save($data);
			}
			$this->ajaxReturn('1', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('0', L('EDIT FAILED'), 1);
		}
	}
	
	/**
	*  获得自定义字段选项
	*
	**/
	public function boxField(){
		$field_list = M('Fields')->where(array('model'=>$this->_get('model'),'field'=>$this->_get('field')))->getField('setting');
		eval('$field_list = '.$field_list .';');
		$this->ajaxReturn($field_list['data'], $field_list['type'], 1);
	}
	
	/**
	*  营销发送短信
	*
	**/
	public function sendSms(){
		if($this->isPost()){
			$phoneNum = trim($_POST['phoneNum']);
			$message = trim($_POST['smsContent']);
			if($_POST['settime']){
				$send_time = strtotime(trim($_POST['sendtime']));
				if($send_time > time()){
					$sendtime = date('YmdHis',$send_time);
				}
			}
			$current_sms_num = getSmsNum();
			if(!F('sms')) alert('success',L('SEND_SMS_FAILED'),$_SERVER['HTTP_REFERER']);
			$phoneNum = str_replace(" ","",$phoneNum);
			$phone_array = explode(chr(10),$phoneNum);
			if(sizeof($phone_array) > 0){
				//if(sizeof($phone_array) > $current_sms_num) alert('error','短信余额不足，请联系管理员，及时充值!',$_SERVER['HTTP_REFERER']);
			}
			$fail_array = array();
			$success_array = array();
			if($phoneNum && $message){		
				if(strpos($message,'{$name}',0) === false){
					foreach($phone_array as $k=>$v){
						if($v){
							$phone = substr($v,0,11);
							if(is_phone($phone)){
								$success_array[] = $phone;
							}else{
								$fail_array[] = $v;
							}
						}
					}
					if(!empty($fail_array)){
						$fail_message = L('PART_OF_NUMBER_SEND_FAILED').implode(',', $fail_array);
					}
					//echo '发送成功!';die();
					$result = sendGroupSMS(implode(',', $success_array),$message,'sign_name', $sendtime);
					if($result == 1){
						
					    $m_sms_record=M('smsRecord');
						$data['role_id']=session("role_id");
						$data['telephone']=implode(',', $success_array);
						$data['phone_counts'] = count($success_array);
						$data['content']=$message;
						$data['sendtime']=time();
						$m_sms_record->add($data);
						alert('success', L('SEND_SUCCESS_MAY_DELAY_BY_BAD_NETWORK').$fail_message,$_SERVER['HTTP_REFERER']);
					}else{
						alert('error',L('SMS_NOTIFICATION_FAILS_CODE', array($result)),$_SERVER['HTTP_REFERER']);
					}
				}else{
					foreach($phone_array as $k=>$v){
						$real_message = $message;
						$name = ''; 
						if($v){
							$no = str_replace(" ","",$v);
							$phone = substr($no,0,11);
							if(is_phone($phone)){
								if(strpos($v,',',0) === false){
									$info_array = explode('，', $v);
								}else{
									$info_array = explode(',', $v);
								}
								$real_message = str_replace('{$name}',$info_array[1],$real_message);
								$result = sendSMS($phone, $real_message, 'sign_name', $sendtime);
								$m_sms_record=M('smsRecord');
								$data['role_id'] = session("role_id");
								$data['telephone'] = $phone;
								$data['phone_counts'] = 1;
								$data['content'] = $real_message;
								$data['sendtime'] = time();
								$m_sms_record ->add($data);
								if($result<0 && $k==0){
									alert('error', L('SMS_NOTIFICATION_FAILS_CODE', array($result)),$_SERVER['HTTP_REFERER']); 
								}
							}else{
								$fail_array[] = $v;
							}
						}
					}
					
					if(!empty($fail_array)){
						$fail_message = L('PART_OF_NUMBER_SEND_FAILED').implode(',', $fail_array);
					}
					alert('success',L('SEND_SUCCESS_MAY_DELAY_BY_BAD_NETWORK').$fail_message,U('setting/sendsms'));
					
				}
			}else{
				alert('error',L('INCOMPLETE_INFORMATION'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			$current_sms_num = getSmsNum();
			
			$model = trim($_GET['model']);
			if($model == 'customer'){
				$customer_ids = trim($_GET['customer_ids']);
				if($customer_ids){
					$contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', $customer_ids)->getField('contacts_id', true);
					$contacts_ids = implode(',', $contacts_ids);
					$contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
					$this->contacts = $contacts;
				}else{
					alert('error',L('SELECT_CUSTOMER_TO_SEND'),$_SERVER['HTTP_REFERER']);
				}
			}elseif($model == 'contacts'){
				$contacts_ids = trim($_GET['contacts_ids']);
				if(!$contacts_ids) alert('error',L('SELECT_CONTACTS_TO_SEND'),$_SERVER['HTTP_REFERER']);
				$contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
				$this->contacts = $contacts;
			}elseif($model == 'leads'){
				$d_v_leads = D('LeadsView');
				$leads_ids = trim($_GET['leads_ids']);
				$where['leads_id'] = array('in',$leads_ids);
				$customer_list = $d_v_leads->where($where)->select();
				$contacts = array();
				foreach ($customer_list as $k => $v) {
					$contacts[] = array('name'=>$v['contacts_name'], 'customer_name'=>$v['name'], 'telephone'=>trim($v['mobile']));
				}
				$this->contacts = $contacts;
			}
			$this->templateList = M('SmsTemplate')->order('order_id')->select();
			$this->alert = parseAlert();
			$this->current_sms_num = $current_sms_num;
			$this->display();
		}
	}
	public function smsRecord(){	
	    $m_sms_record=M('smsRecord');
		$where = array();
		$params = array();
		$order = "sendtime desc";
		if($_GET['desc_order']){
			$order = trim($_GET['desc_order']).' desc';
		}elseif($_GET['asc_order']){
			$order = trim($_GET['asc_order']).' asc';
		}
		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']) == 'all' ? 'title|content' : $_REQUEST['field'];
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('sendtime' == $field) $search = is_numeric($search)?$search:strtotime($search);
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
			$params = array('field='.$field, 'condition='.$condition, 'search='.trim($_REQUEST["search"]));
		}
		
	    $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
		if(!session('?admin')){
			$where['role_id'] = session('role_id');
		}
		$list = $m_sms_record->where($where)->order($order)->page($p.',10')->select();
		foreach($list as $k=>$v){
			//查询发送人
			$list[$k]['role_name'] = M('user')->where('role_id=%d',$v['role_id'])->getField('name');
			//截取手机号
			if(strstr($v['telephone'],',')){
				$list[$k]['subtelephone'] = substr($v['telephone'],0,strpos($v['telephone'],',')).'...';
			}else{
				$list[$k]['subtelephone'] = $v['telephone'];
			}
			//截取内容
			if(mb_strlen($v['content'],'utf-8') >= 30){
				$list[$k]['subcontent'] = mb_substr($v['content'],0,30,'utf8').'...';
			}else{
				$list[$k]['subcontent'] = $v['content'];
			}
		}
		$count =$m_sms_record->where($where)->count();
		import("@.ORG.Page");
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());
		$this->assign('data',$list);
		$this->alert=parseAlert();
		$this->display();
	}
	/**
	  * 删除短息列表信息
	**/
	public function smsdelete(){
		$m_sms_record = M('smsRecord');
		if($this->isPost()){
			$record_ids = is_array($_POST['record_id']) ? implode(',', $_POST['record_id']) : '';
			if ('' == $record_ids) {
				$this->ajaxReturn('',L('NOT CHOOSE ANY'),0);
			} else {
				if($m_sms_record->where('sms_record_id in (%s)', $record_ids)->delete()){
					$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
				} else {
					$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
				}
			}
		} 
	}
	/**
	*  营销发送邮件
	*
	**/
	public function sendemail(){
		if($this->isPost()){
			$smtp = M('UserSmtp')->where('smtp_id = %d', intval($_POST['smtp']))->find();
			if(empty($smtp) && $_POST['smtp'] != '-1'){
				//alert('error', L('NEED_SET_SMTP'),$_SERVER['HTTP_REFERER']);
				$this->error(L('NEED_SET_SMTP'));
			}		
			import('@.ORG.Mail');
			$emails = trim($_POST['emails']);
			$title = trim($_POST['title']);
			$content = trim($_POST['content']);
			$url = $this->_server('HTTP_HOST');
			preg_match_all('/<a(.*?)href="(\/Uploads.+?)">(.*?)<\/a>/i',$content,$str_array);
			foreach($str_array as $v){
				$content = str_replace($str_array[0],'',$content);
			}
			$fail_array = array();
			$success_array = array();
			$emails = str_replace(" ","",$emails);
			$emails_array = explode(chr(10),$emails);
			if($emails && $content && $title){
				foreach($emails_array as $k=>$v){
					$email='';
					$str_content='';
					$email_array = array();
					if($v){
						if(strpos($v,',') !== false || strpos($v,'，')!==false){
							$email_array = strpos($v,',') ? explode(',',$v) : explode('，',$v);
							$email = trim($email_array[0]);
							$str_content = str_replace('{name}',$email_array[1],$content);
						}else{
							$email = trim($v);
							$str_content = $content;
						}
						$str_content =(strpos($content,'{name}') !== false) ? str_replace('{name}',$email_array[1],$content) :$content;
						if(is_email($email)){
							$old_array[$email] = $v;
							$success_array[]=array('email'=>$email,'content'=>$str_content);
						}else{
							$fail_array[] = $v;
						}
					}
				}
				if(!empty($fail_array)){
					$fail_message = L('INVALIDATE_EMAIL').implode(',', $fail_array);
				}
				$i=0;
				foreach($success_array as $value){
					$result = bsendemail($value['email'],$title,$value['content'],$str_array[3],true,intval($_POST['smtp']));
					if($result){
						$i++;
					}else{
						$fail_result .= L('SEND_FAILED_UNKNOWN_REASON', array($old_array[$value['email']]));
					}
				}
				if($i>0){
					alert('success',L('SEND_EAMIL_SUCCESS_MAY_DELAY_BY_BAD_NETWORK').$fail_message.'<br>'.$fail_result,$_SERVER['HTTP_REFERER']);
				}else{
					$this->error(L('SEND_FAILED_CONTACTS_ADMIN').$fail_message.'<br>'.$fail_result);
				}
			}else{
				$this->error(L('INCOMPLETE_INFO'));
			}
		}else{
			$model = trim($_GET['model']);
			if($model == 'customer'){
				$customer_ids = trim($_GET['customer_ids']);
				if($customer_ids){
					if($customer_ids == 'all'){
						$all_ids = getSubRoleId();
						$where['is_deleted'] = array('neq',1);
						$where['owner_role_id'] = array('in', $all_ids);
						$customer_ids = D('CustomerView')->where($where)->getField('customer_id', true);
						$contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', implode(',', $customer_ids))->getField('contacts_id', true);
						$contacts_ids = implode(',', $contacts_ids);
						$contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
					}else{
						$contacts_ids = M('RContactsCustomer')->where('customer_id in (%s)', $customer_ids)->getField('contacts_id', true);
						$contacts_ids = implode(',', $contacts_ids);
						$contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
					}
					$this->contacts = $contacts;
				}else{
					alert('error',L('SELECT_CUSTOMER_TO_SEND_EMAIL'),$_SERVER['HTTP_REFERER']);
				}
			}elseif($model == 'contacts'){
				$contacts_ids = trim($_GET['contacts_ids']);
				if(!$contacts_ids) alert('error',L('SELECT_CONTACTS_TO_SEND_EMAIL'),$_SERVER['HTTP_REFERER']);
				$contacts = D('ContactsView')->where('contacts.contacts_id in (%s)', $contacts_ids)->select();
				$this->contacts = $contacts;
			}elseif($model == 'leads'){
				$d_v_leads = D('LeadsView');
				$leads_ids = trim($_GET['leads_ids']);
				if('all' != $leads_ids){$where['leads_id'] = array('in',$leads_ids);}
				$customer_list = $d_v_leads->where($where)->select();
				$contacts = array();
				foreach ($customer_list as $k => $v) {
					$contacts[] = array('name'=>$v['contacts_name'], 'customer_name'=>$v['name'], 'email'=>trim($v['email']));
				}
				$this->contacts = $contacts;
			}
			$this->templateList = M('EmailTemplate')->order('order_id')->select();
			$smtpList = M('UserSmtp')->where('user_id = %d', session('user_id'))->select();
			foreach($smtpList as $k=>$v){
				$smtpList[$k]['settinginfo'] = unserialize($v['settinginfo']);
			}
			$this->smtpList = $smtpList;
			$moren_smtp = M('config')->where('name="smtp"')->getField('value');
			$this->moren_address = unserialize($moren_smtp);
			$this->alert = parseAlert();
			$this->display();
		}
	}

	public function appsetting(){
		$m_config = M('Config');
		$config = $m_config->where('name = "app_push_message"')->find();
		if($this->isPost()){
			if($config){
				$m_config->where('name = "app_push_message"')->setField('value', intval($_POST['push_message']));
			}else{
				$data['value'] = intval($_POST['push_message']);
				$data['name'] = "app_push_message";
				$m_config->add($data);
			}
			alert('success', '保存成功!', $_SERVER['HTTP_REFERER']);
		}else{
			$this->app_config = $config;
			$this->alert = parseAlert();
			$this->display();
		}
	}
	public function customeshow(){
		$m_fields = M('fields');
		$field_id = intval($_GET['field_id']);
		$id = intval($_GET['id']);
		if($field_id){
			if($id == 1){
				$is_show = $id;
			}else{
				$is_show = 0;
			}
			$result = $m_fields ->where('field_id =%d',$field_id)->setField('is_show',$is_show);
			if($result){
				if($id == 1){
					$data = '客户列表页显示开启！';
				}else{
					$data = '客户列表页显示关闭！';
				}
				alert('success',$data,$_SERVER['HTTP_REFERER']);
			}else{
				alert('error','操作失败!',$_SERVER['HTTP_REFERER']);
			}
		}
	}

	//锁屏页面
	// session('is_lock') 1锁屏2正常
	public function lockscreen(){
		$user_info = M('User')->where('role_id = %d',session('role_id'))->field('role_id,name,full_name,img,password,salt')->find();
		if($this->isPost()){
			if ($user_info['password'] == md5(trim($_POST['password']) . $user_info['salt'])) {
				unset($_SESSION['is_lock']);
				$this->ajaxReturn('正在登录','',1);
			}else{
				$this->ajaxReturn('','密码错误，请重新输入！',0);
			}
		}
		session('is_lock',1);
		$this->user_info = $user_info;
		$this->display();
	}

	/**
	 * 审批设置列表
	 **/
	public function examine(){
		$m_examine_status = M('examine_status');
		$examine_status_list = $m_examine_status ->select();
		$this->status_list = $examine_status_list;
		$this->display();
	}

	/**
	 * 审批流程设置
	 **/
	public function examinetype(){
		$m_examine_status = M('examine_status');
		$m_examine_step = M('ExamineStep');
		$m_position = M('Position');
		$d_role = D('RoleView');
		
		$id = intval($_GET['id']);
		$status_info = $m_examine_status ->where('id=%d',$id)->find();
		$step_list = $m_examine_step->where('process_id=%d',$id)->order('order_id')->select();
		foreach($step_list as $k=>$v){
			$step_list[$k]['position_name'] = $m_position->where('position_id=%d',$v['position_id'])->getField('name');
			$step_list[$k]['user'] = $d_role->where('role.role_id=%d',$v['role_id'])->find();
		}
		$this->assign('step_list',$step_list);
		$this->status_info = $status_info;
		$this->status_id = $id;
		$this->display();
	}

	/**
	 * 审批流程保存
	 **/
	public function ajaxexamine(){
		$m_examine_status = M('examine_status');
		$type = intval($_POST['openrecycle']);
		$status_id = intval($_POST['status_id']);
		if($type== 1){
			$option_id = 0;
		}else{
			$option_id = 1;
		}
		$result = $m_examine_status ->where('id=%d',$status_id)->setField('option',$option_id);
		if($result){
			$this->ajaxReturn('success','编辑成功！',1);
		}else{
			$this->ajaxReturn('error','编辑失败！',0);
		}
	}

	/**
	 * 编辑审批流程
	 **/
	public function edit_process(){
		$m_examine_status = M('ExamineStatus');
		$d_role = D('RoleView');
		if($this->isPost()){
			$m_examine_process->create();
			$m_examine_process->save();
			redirects('编辑成功！',$_SERVER['HTTP_REFERER']);
		}else{
			$m_examine_step = M('ExamineStep');
			$m_position = M('Position');
			$m_user = M('User');
			$process_id = intval($_GET['process_id']);
			//流程信息
			$data = $m_examine_status->where('id=%d',$process_id)->find();
			//该流程下的步骤列表
			$step_list = $m_examine_step->where('process_id=%d',$process_id)->order('order_id')->select();
			foreach($step_list as $k=>$v){
				$step_list[$k]['user_info'] = $d_role->where(array('role.role_id'=>$v['role_id']))->field('user_name,role_name,department_name')->find();
			}
			$this->assign('step_list',$step_list);
			$this->assign('data',$data);
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	 * 清空审批流程数据
	 **/
	public function cleartype(){
		$status_id = intval($_POST['status_id']);
		if($status_id){
			$m_examine_step = M('ExamineStep');
			$result = $m_examine_step ->where('process_id=%d',$status_id)->delete();
			if($result){
				$this->ajaxReturn('success','删除成功！',1);
			}else{
				$this->ajaxReturn('error','删除失败！',0);
			}
		}else{
			$this->ajaxReturn('error','获取参数失败！',0);
		}
	}

	/**
	 * 审批流程排序
	 **/
	public function examinesort(){	
		if(isset($_GET['postion'])){
			$examine_step = M('examine_step');
			foreach(explode(',', $_GET['postion']) AS $k=>$v) {
				$data = array('step_id'=> $v, 'order_id'=>$k);
				$examine_step->save($data);
			}
			$this->ajaxReturn('1', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('0', L('EDIT FAILED'), 1);
		}
	}

	/**
	 * 停用和启用数据
	 **/
	public function enable(){	
		if(isset($_POST['type'])){
			$type = intval($_POST['type']);
			$id = intval($_POST['id']);
			$m_examine_status = M('ExamineStatus');
			if($type == 1){
				$type_id = 0;
			}else{
				$type_id = 1;
			}
			$result = $m_examine_status ->where('id= %d',$id)->setField('type',$type_id);
			$this->ajaxReturn('1', L('SUCCESSFULLY EDIT'), 1);
		} else {
			$this->ajaxReturn('0', L('EDIT FAILED'), 0);
		}
	}

	/**
	 * 工作日配置
	 **/
	public function workrule(){
		//判断权限(根据考勤规则权限判断)
		if (!session('?admin') && !checkPerByAction('kaoqin','setting')) {
			alert('error','您没有此权利！',U('index/index'));
		}
		$m_workrule = M('Workrule');
		$m_workrule_config = M('WorkruleConfig');
		if($this->isPost()){
			if($_POST['year']){
				$data = array();
				$data['value'] = ','.implode(',',$_POST['rule_id']).',';
				$data['year'] = intval($_POST['year']);
				$data['update_time'] = time();
				$workrule_config_info = $m_workrule_config->where(array('year'=>intval($_POST['year'])))->find();
				if($workrule_config_info){
					$res = $m_workrule_config->where(array('id'=>$workrule_config_info['id']))->save($data);
					$workrule_config_id = $workrule_config_info['id'];
				}else{
					$res = $m_workrule_config->add($data);
					$workrule_config_id = $res;
				}
				//批量生成数据
				// $res_del = $m_workrule->where(array('year'=>intval($_POST['year']),'status'=>array('neq',1)))->delete();
				//判断星期
				$workrule = $m_workrule_config->where(array('id'=>$workrule_config_id))->getField('value');
				$workrule_array = array_filter(explode(',',$workrule));
				$workrule_list = array();
				foreach($workrule_array as $k=>$v){
					switch($v){
						case 1 : $workrule_list[] = '星期一';break;
						case 2 : $workrule_list[] = '星期二';break;
						case 3 : $workrule_list[] = '星期三';break;
						case 4 : $workrule_list[] = '星期四';break;
						case 5 : $workrule_list[] = '星期五';break;
						case 6 : $workrule_list[] = '星期六';break;
						case 7 : $workrule_list[] = '星期日';break;
					}
				}
				//获取年日期时间戳数组
				$year_start_time = strtotime(date($data['year']."-01-01"));
				$year_end_time = strtotime(date($data['year']."-12-31"));
				$day_list = dateList($year_start_time,$year_end_time);
				//自定义时间
				$status_workrule_array = $m_workrule->where(array('year'=>intval($_POST['year']),'status'=>1))->getField('sdata',true);
				foreach($day_list as $k=>$v){
					$day_data = array();
					$week_name = '';
					$week_name = getTimeWeek($v['sdate']);
					$day_data['year'] = intval($_POST['year']);
					$day_data['sdate'] = $v['sdate'];
					if(in_array($week_name,$workrule_list)){
						$day_data['type'] = 2;
					}else{
						$day_data['type'] = 1;
					}
					$workrule_info = array();
					$workrule_info = $m_workrule->where(array('year'=>intval($_POST['year']),'sdate'=>$v['sdate']))->find();
					if(!$workrule_info && (empty($status_workrule_array) || !in_array($v['sdata'],$status_workrule_array))){
						$m_workrule->add($day_data);
					}else{
						$m_workrule->where(array('sdate'=>$v['sdate']))->save($day_data);
					}
				}

				if($res !== false){
					$this->ajaxReturn('','设置成功！',1);
				}else{
					$this->ajaxReturn('','设置失败，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','设置失败，请重试！',0);
			}
		}
		$now_year = date('Y',time());

		$where_year = $_GET['year'] ? intval($_GET['year']) : $now_year;
		$this->where_year = $where_year;

		$year_list = $m_workrule->group('year')->getField('year',true);
		$max_year = $m_workrule->group('year')->max('year');
		$min_year = $m_workrule->group('year')->min('year');
		$start_year = $min_year ? $min_year : $now_year-1;
		$sub_year = $max_year-$min_year;
		$max_i = 11+$sub_year;
		for ($i=1; $i < $max_i; $i++) { 
			$year_list[] = $start_year+$i;
		}
		$this->year_list = $year_list;
		
		$work_config = $m_workrule_config->where(array('year'=>$where_year))->getField('value');
		$work_config_array = array_filter(explode(',',$work_config));
		$work_config_list = array('1'=>'周一','2'=>'周二','3'=>'周三','4'=>'周四','5'=>'周五','6'=>'周六','7'=>'周日');
		$this->work_config_array = $work_config_array;
		$this->work_config_list = $work_config_list;

		$workrule_list = $m_workrule->where(array('status'=>1,'year'=>$where_year))->order('sdate desc')->select();
		$this->workrule_list = $workrule_list;
		$this->display();
	}

	/**
	 * 工作日配置(修改)
	 **/
	public function workruleEdit(){
		//判断权限(根据考勤规则权限判断)
		if (!session('?admin') && !checkPerByAction('kaoqin','setting')) {
			alert('error','您没有此权利！',U('index/index'));
		}
		
		$m_workrule = M('Workrule');
		if($this->isPost()){
			if($_POST['sdate']){
				$sdate = strtotime(trim($_POST['sdate']));
				$edate = strtotime(trim($_POST['sdate']));
				$workrule_id = intval($_POST['id']);
			}elseif($_POST['between_date']){
				$between_date = explode(' - ',trim($_POST['between_date']));
			}
			if($between_date[0]){
				$sdate = strtotime($between_date[0]);
				$edate = strtotime($between_date[1]);
			}
			//计算天数
			$days = ($edate-$sdate+86400)/86400;
			//查询是否有重复值
			$where = array();
			$where['sdate']  = array('between', array($sdate,$edate));
			$where['status'] = 1;
			$rule_info = $m_workrule->where($where)->find();
			if($rule_info && !$workrule_id){
				$this->ajaxReturn('','日期设置重复或交叉，请重新设置！',2);
			}
			for ($i=$sdate; $i <= $edate; $i += 86400) {
				$year = '';
				$year = date('Y',$i);
				$workrule_data = array();
				$workrule_data['type'] = intval($_POST['type']);
				$workrule_data['status'] = 1;
				$workrule_data['year'] = $year;
				$workrule_data['sdate'] = $i;

				$work_info = array();
				$work_info = $m_workrule->where(array('year'=>$year,'sdate'=>$i))->find();
				if($work_info || $workrule_id){
					if($workrule_id){
						$m_workrule->where(array('id'=>$workrule_id))->save($workrule_data);
					}else{
						$m_workrule->where(array('year'=>$year,'sdate'=>$i))->save($workrule_data);
					}
				}else{
					$m_workrule->add($workrule_data);
				}
			}
			$this->ajaxReturn('','设置成功！',1);
		}
		if($_GET['rule_id']){
			$workrule_info = $m_workrule->where('id = %d',intval($_GET['rule_id']))->find();
			$workrule_info['sdate'] = date('Y-m-d',$workrule_info['sdate']);
			$workrule_info['edate'] = date('Y-m-d',$workrule_info['edate']);
			$this->workrule_info = $workrule_info;
		}
		$this->display();
	}

	/**
	 * 工作日配置(删除)
	 **/
	public function workruleDel(){
		//判断权限(根据考勤规则权限判断)
		if (!session('?admin') && !checkPerByAction('kaoqin','setting')) {
			alert('error','您没有此权利！',U('index/index'));
		}
		
		$m_workrule = M('Workrule');
		$m_workrule_config = M('WorkruleConfig');
		if ($_POST['rule_id']) {
			$id_array = $_POST['rule_id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['rule_id'];
			}
			if ($m_workrule->where('id in (%s)', implode(',', $id_array))->setField('status',0)){
				//工作日数据修改
				$now_year = date('Y',time());
				$where_year = $_GET['year'] ? intval($_GET['year']) : $now_year;

				//判断星期
				$workrule = $m_workrule_config->where(array('year'=>$where_year))->getField('value');
				$workrule_array = array_filter(explode(',',$workrule));
				$workrule_list = array();
				foreach($workrule_array as $k=>$v){
					switch($v){
						case 1 : $workrule_list[] = '星期一';break;
						case 2 : $workrule_list[] = '星期二';break;
						case 3 : $workrule_list[] = '星期三';break;
						case 4 : $workrule_list[] = '星期四';break;
						case 5 : $workrule_list[] = '星期五';break;
						case 6 : $workrule_list[] = '星期六';break;
						case 7 : $workrule_list[] = '星期日';break;
					}
				}

				foreach($id_array as $k=>$v){
					$workrule_info = $m_workrule->where('id = %d',$v)->find();
					$week_name = '';
					$week_name = getTimeWeek($workrule_info['sdate']);
					$type = 1;
					if(in_array($week_name,$workrule_list)){
						$type = 2;
					}else{
						$type = 1;
					}
					$res = $m_workrule->where('id = %d',$v)->setField('type',$type);
				}

				$this->ajaxReturn('','删除成功！',1);
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
		}
	}

	/**
	 * 沟通类型
	 **/
	public function logStatus() {
		//判断权限

		$m_log_status = M('LogStatus');
		$status_list = $m_log_status->select();

		$this->status_list = $status_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 沟通类型(添加)
	 **/
	public function logStatusAdd() {
		if ($this->isPost()) {
			$m_log_status = M('LogStatus');
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_log_status->where(array('name'=>trim($_POST['name'])))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_log_status->create()){
				$m_log_status->create_role_id = session('role_id');
				$m_log_status->create_time = time();
				$m_log_status->update_time = time();
				if ($m_log_status->add()) {
					alert('success', '沟通类型添加成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '沟通类型添加失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '沟通类型添加失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	 * 沟通类型(编辑)
	 **/
	public function logStatusEdit() {
		$id = $_REQUEST['id'] ? intval($_REQUEST['id']) : '';
		if (!$id) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_log_status = M('LogStatus');
		$status_info = $m_log_status->where('id = %d',$id)->find();
		if ($this->isPost()) {
			
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_log_status->where(array('name'=>trim($_POST['name']),'id'=>array('neq',$id)))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_log_status->create()){
				$m_log_status->update_time = time();
				if ($m_log_status->where('id = %d',$id)->save()) {
					alert('success', '沟通类型修改成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '沟通类型修改失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '沟通类型修改失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->status_info = $status_info;
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	 * 沟通类型(删除)
	 **/
	public function logStatusDel() {
		$m_log_status = M('LogStatus');
		$m_log = M('Log');
		if ($_POST['id']) {
			$id_array = $_POST['id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['id'];
			}
			$del_arr = array();
			//判断是否被使用
			foreach ($id_array as $k=>$v) {
				if (!$m_log->where(array('status_id'=>$v,'category_id'=>1))->find()) {
					$del_arr[] = $v;
				}
			}
			if ($del_arr) {
				if ($m_log_status->where('id in (%s)', implode(',', $del_arr))->delete()){
					if (count($id_array) == count($del_arr)) {
						$this->ajaxReturn('','删除成功！',1);
					} else {
						$this->ajaxReturn('','部分类型已被使用，删除失败！',1);
					}
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			} else {
				$this->ajaxReturn('','所选类型已被使用，不能删除！',0);
			}
		}
	}

	/**
	 * 沟通日志快捷回复
	 **/
	public function logReply() {
		//判断权限

		$status_id = $_GET['status_id'] ? intval($_GET['status_id']) : '';
		if (!$status_id) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_log_reply = M('LogReply');
		$reply_list = $m_log_reply->where(array('type'=>1,'status_id'=>$status_id))->select();
		foreach ($reply_list as $k=>$v) {
			$reply_list[$k]['str_content'] = cutString($v['content'],30);
		}

		$this->reply_list = $reply_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 沟通日志快捷回复(添加)
	 **/
	public function logReplyAdd() {
		$status_id = $_REQUEST['status_id'] ? intval($_REQUEST['status_id']) : '';
		//type 1系统2个人
		$type = $_REQUEST['type'] ? intval($_REQUEST['type']) : '2';
		if (!$status_id || !in_array($type,array('1','2'))) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_log_status = M('LogStatus');
		$status_name = $m_log_status->where('id = %d',$status_id)->getField('name');
		if ($this->isPost()) {
			$m_log_reply = M('LogReply');
			if(!trim($_POST['content'])){
				alert('error','请填写内容！',$_SERVER['HTTP_REFERER']);
			}
			if($m_log_reply->create()){
				$m_log_reply->type = $type;
				$m_log_reply->role_id = session('role_id');
				$m_log_reply->create_time = time();
				$m_log_reply->update_time = time();
				if ($reply_id = $m_log_reply->add()) {
					if ($type == 2) {
						$reply_info = $m_log_reply->where('id = %d',$reply_id)->find();
						$reply_info['status_name'] = $m_log_status->where('id = %d',$reply_info['status_id'])->getField('name');
						$this->ajaxReturn($reply_info,'快捷回复添加成功！',1);
					} else {
						alert('success', '快捷回复添加成功！', $_SERVER['HTTP_REFERER']);
					}
				} else {
					if ($type == 2) {
						$this->ajaxReturn('','快捷回复添加失败！',0);
					} else {
						alert('error', '快捷回复添加失败！', $_SERVER['HTTP_REFERER']);
					}
				}
			}else{
				if ($type == 2) {
					$this->ajaxReturn('','快捷回复添加失败！',0);
				} else {
					alert('error', '快捷回复添加失败！', $_SERVER['HTTP_REFERER']);
				}
			}
		} else {
			$this->status_name = $status_name;
			$this->display();
		}
	}

	/**
	 * 沟通日志快捷回复(编辑)
	 **/
	public function logReplyEdit() {
		$id = $_REQUEST['id'] ? intval($_REQUEST['id']) : '';
		$m_log_reply = M('LogReply');
		$reply_info = $m_log_reply->where('id = %d',$id)->find();
		if (!$reply_info) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		if (!session('?admin') && $reply_info['type'] == 1) {
			alert('error','您没有此权利！',$_SERVER['HTTP_REFERER']);
		}
		$m_log_status = M('LogStatus');
		$status_name = $m_log_status->where('id = %d',$reply_info['status_id'])->getField('name');
		if ($this->isPost()) {
			$type = intval($_POST['type']);
			if(!trim($_POST['content'])){
				if ($type == 2) {
					$this->ajaxReturn('','请填写内容！',0);
				} else {
					alert('error','请填写内容！',$_SERVER['HTTP_REFERER']);
				}
			}
			if (!session('?admin') && $_POST['type'] == 1) {
				if ($type == 2) {
					$this->ajaxReturn('','参数错误！',0);
				} else {
					alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
				}
			}
			if($m_log_reply->create()){
				$m_log_reply->update_time = time();
				if ($m_log_reply->where('id = %d',$id)->save()) {
					if ($type == 2) {
						$reply_info_new = $m_log_reply->where('id = %d',$id)->find();
						$reply_info_new['status_name'] = $m_log_status->where('id = %d',$reply_info_new['status_id'])->getField('name');
						$this->ajaxReturn($reply_info_new,'快捷回复修改成功！',1);
					} else {
						alert('success', '快捷回复修改成功！', $_SERVER['HTTP_REFERER']);
					}
				} else {
					if ($type == 2) {
						$this->ajaxReturn('','快捷回复修改失败！',0);
					} else {
						alert('error', '快捷回复修改失败！', $_SERVER['HTTP_REFERER']);
					}
				}
			}else{
				if ($type == 2) {
					$this->ajaxReturn('','快捷回复修改失败！',0);
				} else {
					alert('error', '快捷回复修改失败！', $_SERVER['HTTP_REFERER']);
				}
			}
		} else {
			$reply_info['status_name'] = $status_name;
			$this->reply_info = $reply_info;
			$this->display();
		}
	}

	/**
	 * 沟通日志快捷回复(删除)
	 **/
	public function logReplyDel() {
		$m_log_reply = M('LogReply');
		if ($_POST['id']) {
			$id_array = $_POST['id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['id'];
			}
			$del_arr = array();
			//判断是否被使用
			foreach ($id_array as $k=>$v) {
				if (!$m_log_reply->where(array('id'=>$v,'role_id'=>array('neq',session('role_id'))))->find()) {
					$del_arr[] = $v;
				}
			}
			if ($del_arr) {
				if ($m_log_reply->where('id in (%s)', implode(',', $del_arr))->delete()){
					if (count($id_array) == count($del_arr)) {
						$this->ajaxReturn('','删除成功！',1);
					} else {
						$this->ajaxReturn('','部分删除失败！',1);
					}
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			} else {
				$this->ajaxReturn('','删除失败！',0);
			}
			
		}
	}

	/**
	 * ajax获得快捷回复
	 **/
	public function getReplyByStatus() {
		if ($this->isAjax()) {
			$stauts_id = $_REQUEST['status_id'] ? intval($_REQUEST['status_id']) : '';
			$reply_list = array();
			$where = array();
			$where['type']  = 1;
			$where['role_id']  = session('role_id');
			$where['_logic'] = 'or';
			$map['_complex'] = $where;
			if ($stauts_id) {
				$map['status_id'] = $stauts_id;
			}
			$reply_list = M('LogReply')->where($map)->select();
			foreach ($reply_list as $k=>$v) {
				$reply_list[$k]['str_content'] = cutString($v['content'],'12');
			}
			$this->ajaxReturn($reply_list,'','0');
		}
	}

	/**
	 * dialog管理快捷沟通
	 **/
	public function replyList() {
		$m_log_reply = M('LogReply');
		$m_log_status = M('LogStatus');
		$where = array();
		$where['type']  = 1;
		$where['role_id']  = session('role_id');
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		$reply_list = M('LogReply')->where($map)->select();
		foreach ($reply_list as $k=>$v) {
			$reply_list[$k]['str_content'] = cutString($v['content'],'12');
			$reply_list[$k]['status_name'] = $m_log_status->where(array('id'=>$v['status_id']))->getField('name');
		}
		$this->status_list = $m_log_status->select();
		$this->reply_list = $reply_list;
		$this->display();
	}

	/**
	 * 商机状态组（列表）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function businessType() {
		//判断权限

		$m_business_type = M('BusinessType');
		$type_list = $m_business_type->select();

		$this->type_list = $type_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 商机状态组（添加）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function businessTypeAdd() {
		if ($this->isPost()) {
			$m_business_type = M('BusinessType');
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_business_type->where(array('name'=>trim($_POST['name'])))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_business_type->create()){
				$m_business_type->create_role_id = session('role_id');
				$m_business_type->create_time = time();
				$m_business_type->update_time = time();
				if ($type_id = $m_business_type->add()) {
					//默认追加项目成功、项目失败
					$data = array();
					$data[0]['name'] = '项目失败';
					$data[0]['order_id'] = '99';
					$data[0]['is_end'] = '2';
					$data[0]['description'] = '项目失败';
					$data[0]['type_id'] = $type_id;

					$data[1]['name'] = '项目成功';
					$data[1]['order_id'] = '100';
					$data[1]['is_end'] = '3';
					$data[1]['description'] = '项目成功';
					$data[1]['type_id'] = $type_id;
					$m_business_status = M('BusinessStatus');
					foreach ($data as $k=>$v) {
						$res = $m_business_status->add($v);
					}
					alert('success', '商机状态组添加成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '商机状态组添加失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '商机状态组添加失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	 * 商机状态组（编辑）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function businessTypeEdit() {
		$id = $_REQUEST['id'] ? intval($_REQUEST['id']) : '';
		if (!$id) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_business_type = M('BusinessType');
		$type_info = $m_business_type->where('id = %d',$id)->find();
		if ($this->isPost()) {
			
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_business_type->where(array('name'=>trim($_POST['name']),'id'=>array('neq',$id)))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_business_type->create()){
				$m_business_type->update_time = time();
				if ($m_business_type->where('id = %d',$id)->save()) {
					alert('success', '商机状态组修改成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '商机状态组修改失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '商机状态组修改失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->type_info = $type_info;
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	 * 商机状态组（删除）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function businessTypeDel() {
		$m_business_type = M('BusinessType');
		$m_business = M('Business');
		if ($_POST['id']) {
			$id_array = $_POST['id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['id'];
			}
			$del_arr = array();
			//判断是否被使用
			foreach ($id_array as $k=>$v) {
				if (!$m_business->where(array('status_type_id'=>$v))->find() && $v !== 1) {
					$del_arr[] = $v;
				}
			}
			if ($del_arr) {
				if ($m_business_type->where('id in (%s)', implode(',', $del_arr))->delete()){
					if (count($id_array) == count($del_arr)) {
						$this->ajaxReturn('','删除成功！',1);
					} else {
						$this->ajaxReturn('','部分组已被使用，删除失败！',1);
					}
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			} else {
				$this->ajaxReturn('','所选组已被使用，不能删除！',0);
			}
		}
	}

	/**
	 * 财务相关类型（列表）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function finance() {
		//判断权限
		$field = $_GET['field'] ? trim($_GET['field']) : 'payables';
		switch ($field) {
			case 'payables' : $field_name = '应付款';
				break;
			default : $field_name = '应付款';
				break;
		}
		$m_finance_type = M('FinanceType');
		$type_list = $m_finance_type->where(array('field'=>$field))->select();
		$this->field_name = $field_name;
		$this->type_list = $type_list;
		$this->alert = parseAlert();
		$this->display();
	}

	/**
	 * 财务类型（添加）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function financeAdd() {
		if ($this->isPost()) {
			$m_finance_type = M('FinanceType');
			if (!trim($_POST['field'])) {
				alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
			}
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_finance_type->where(array('name'=>trim($_POST['name'])))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_finance_type->create()){
				$m_finance_type->create_role_id = session('role_id');
				$m_finance_type->create_time = time();
				$m_finance_type->update_time = time();
				if ($m_finance_type->add()) {
					alert('success', '类型添加成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '类型添加失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '类型添加失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->alert=parseAlert();
			$this->display();
		}
	}

	/**
	 * 财务类型（编辑）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function financeEdit() {
		$id = $_REQUEST['id'] ? intval($_REQUEST['id']) : '';
		if (!$id) {
			alert('error','参数错误！',$_SERVER['HTTP_REFERER']);
		}
		$m_finance_type = M('FinanceType');
		$type_info = $m_finance_type->where('id = %d',$id)->find();
		if ($this->isPost()) {
			
			if(!trim($_POST['name'])){
				alert('error','请填写名称！',$_SERVER['HTTP_REFERER']);
			}
			if ($m_finance_type->where(array('name'=>trim($_POST['name']),'id'=>array('neq',$id)))->find()) {
				alert('error','该名称已存在，请修改后重试！',$_SERVER['HTTP_REFERER']);
			}
			if($m_finance_type->create()){
				$m_finance_type->update_time = time();
				if ($m_finance_type->where('id = %d',$id)->save()) {
					alert('success', '类型修改成功！', $_SERVER['HTTP_REFERER']);
				} else {
					alert('error', '类型修改失败！', $_SERVER['HTTP_REFERER']);
				}
			}else{
				alert('error', '类型修改失败！', $_SERVER['HTTP_REFERER']);
			}
		} else {
			$this->type_info = $type_info;
			$this->alert = parseAlert();
			$this->display();
		}
	}

	/**
	 * 财务类型（删除）
	 * @param 
	 * @author 
	 * @return 
	 */
	public function financeDel() {
		$m_finance_type = M('FinanceType');
		$m_payables = M('Payables');
		if ($_POST['id']) {
			$id_array = $_POST['id'];
			if(!is_array($id_array)){
				$id_array = array();
				$id_array[0] = $_POST['id'];
			}
			$del_arr = array();
			//判断是否被使用
			foreach ($id_array as $k=>$v) {
				if (!$m_payables->where(array('type_id'=>$v))->find()) {
					$del_arr[] = $v;
				}
			}
			if ($del_arr) {
				if ($m_finance_type->where('id in (%s)', implode(',', $del_arr))->delete()){
					if (count($id_array) == count($del_arr)) {
						$this->ajaxReturn('','删除成功！',1);
					} else {
						$this->ajaxReturn('','部分类型已被使用，删除失败！',1);
					}
				} else {
					$this->ajaxReturn('','删除失败！',0);
				}
			} else {
				$this->ajaxReturn('','所选类型已被使用，不能删除！',0);
			}
		}
	}
	
}