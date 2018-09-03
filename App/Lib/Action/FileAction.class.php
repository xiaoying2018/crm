<?php
class FileAction extends Action{
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('editor', 'add', 'delete','filedownload','add_file','addlogfile','getfiles','filedel', 'addcasefile')
		);
		B('Authenticate', $action);
	}
	/**
	 * ajax 查出添加的附件id 并输出
	 * @return $data
	 */
	public function getfiles(){
		$file_id_array = $this->_post('file');	
		$where['file_id'] = array('in',array_filter($file_id_array));	
		$file_list = M('File')->where($where)->select();
		$file_size_count = M('File')->where($where)->sum('size');
		$file_num = 0;
		$file_size = '';
		foreach ($file_list as $key => $value) {
			$file_list[$key]['size'] = ceil($value['size']/1024);
			$file_list[$key]['pic'] = show_picture($value['name']);
		}
		$data['file_list'] = $file_list;		
		$data['file_num'] = sizeof($file_list);
		$data['file_size'] = ceil($file_size_count/1024);
		$this->ajaxReturn($data,'success',1);
	}
	/**
	 * ajax 删除
	 * @return 
	 */
	public function filedel(){
		if($this->isAjax()){
			$file_id = $this->_post('file_id');
			if($file_id){
				//关系表处理
				$r_module = $_POST['module'] ? trim($_POST['module']) : '';
				switch ($r_module) {
					case 'examine' : $r = M('ExamineFile'); break;
					case 'business' : $r = M('RBusinessFile'); break;
					case 'contract' : $r = M('RContractFile'); break;
					case 'customer' : $r = M('RCustomerFile'); break;
					case 'finance' : $r = M('RFileFinance'); break;
					case 'leads' : $r = M('RFileLeads'); break;
					case 'log' : $r = M('RFileLog'); break;
					case 'product' : $r = M('RFileProduct'); break;
					case 'member' : $r = M('RMemberFile'); break;
					case 'task' : $r = M('RTaskFile'); break;
				}
				if ($r) {
					$file_info = M('File')->where('file_id = %d',$file_id)->find();
					$msg = M('File')->where('file_id = %d',$file_id)->delete();
					if($msg){
						@unlink($file_info['file_path']);
						$file_count = $r->where('file_id = %d',$file_id)->count();
						if($file_count){
							$r->where('file_id = %d',$file_id)->delete();
						}
						$this->ajaxReturn('','success',1);
					}else{
						$this->ajaxReturn('','删除失败！',3);
					}
				} else {
					$this->ajaxReturn('','删除失败！',3);
				}
			}
		}else{
			$this->ajaxReturn('','跑神儿了！:-D',2);
		}
	}
	public function editor(){
		header("Content-Type: text/html; charset=utf-8");

		if (isset($_FILES['upfile']['size']) && $_FILES['upfile']['size'] != null) {
			$m_config = M('config');
			//如果有文件上传 上传附件
			import('@.ORG.UploadFile');
			//导入上传类
			$upload = new UploadFile();
			//设置上传文件大小
			$upload->maxSize = 20000000;
			//设置上传文件类型
			$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
		    $value = unserialize($defaultinfo['value']);
			$allow_file_type = str_replace(",php","",$value['allow_file_type']);
			$upload->allowExts  = explode(',', $allow_file_type);// 设置附件上传类型
			//设置附件上传目录
			$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
			if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
				$a['state']='写入文件内容错误';
				//$this->ajaxReturn($a,'JSON');
				echo json_encode($a);
			}
			$upload->savePath = $dirname;
			
			if(!$upload->upload()) {// 上传错误提示错误信息
				//alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
				echo $upload->getErrorMsg(); die();
			}else{// 上传成功 获取上传文件信息
				$info =  $upload->getUploadFileInfo();
			}
		}else{
			$a['state']='上传文件为空';
			
			echo json_encode($a);
		}
		if(is_array($info[0]) && !empty($info[0])){
		
			$a['state']='SUCCESS';
			$a['url'] = $dirname . $info[0]['savename'];
			$a['title'] = $info[0]['name'];
			$a['original'] = $info[0]['name'];
			$a['type'] = substr(strrchr($info[0]['name'], '.'), 1); 
			$a['size'] = $info[0]['size'];
			
			echo json_encode($a);
			
		}else{
			$a['state']='未知错误';
			
			echo json_encode($a);
		};
	}

	public function add(){
		$m_config = M('config');
		if(!empty($_FILES)){
			if (isset($_FILES['file']['size']) && $_FILES['file']['size'] != null) {
				//如果有文件上传 上传附件
				import('@.ORG.UploadFile');
				//导入上传类
				$upload = new UploadFile();
				//设置上传文件大小
				$upload->maxSize = 20000000;
				//设置附件上传目录
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				
				$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
				$value = unserialize($defaultinfo['value']);
				$allow_file_type = str_replace(",php","",$value['allow_file_type']);
				$upload->allowExts  = explode(',', $allow_file_type);// 设置附件上传类型
				
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {// 上传错误提示错误信息
					//alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']."&tar=file");
					$this->ajaxReturn($upload->getErrorMsg(),$upload->getErrorMsg(),0);
					echo $upload->getErrorMsg();
				}else{// 上传成功 获取上传文件信息
					$info = $upload->getUploadFileInfo();
				}
			}
			
			$m_file = M('File');
			$r_file_module = M($_REQUEST['r']);
			$module = $_REQUEST['module'];
			$m_id = $_REQUEST['id'];
		
			if(is_array($info[0]) && !empty($info[0])){
				if(substr($info[0]['savename'], -3)=='jpg' || substr($info[0]['savename'], -3)=='png' || substr($info[0]['savename'], -4)=='jpeg'){
					$data['file_path_thumb'] = $info[0]['savepath'] .'thumb_'. $info[0]['savename'];
				}
				$data['file_path'] = $info[0]['savepath'] . $info[0]['savename'];
				$data['name'] = $info[0]['name'];
				$data['role_id'] = session('role_id');
				$data['size'] = $info[0]['size'];
				$data['create_date'] = time(); 
				if($file_id = $m_file->add($data)){
					$temp['file_id'] = $file_id;
					$temp[$module . '_id'] = $m_id;
					if(0 >= $r_file_module->add($temp)){
						alert('error', L('ADD_FAILURE_PARTS_ACCESSORIES'), $_SERVER['HTTP_REFERER']);
					}else{
						//线索、客户到期时间
						if($r_file_module == 'RFileLeads'){
							M('Leads')->where('leads_id = %d',$m_id)->setField('have_time',time());
						}elseif($r_file_module == 'RCustomerFile'){
							M('Customer')->where('customer_id = %d',$m_id)->setField('update_time',time());
						}
					}
				}else{
					echo '"'.$data['name'].'" 上传失败！';
				}
				echo '"'.$file_id.'" 上传成功！';
			}else{
				echo '"'.$data['name'].'" 上传失败！';
			}
		}elseif($_GET['r'] && $_GET['module'] && $_GET['id']){
			$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
			$value = unserialize($defaultinfo['value']);
			$this->allowExts  = $value['allow_file_type'];// 设置附件上传类型
			$this->r = $_GET['r'];
			$this->module = $_GET['module'];
			$this->id = $_GET['id'];
			$this->display();
		}
	}
	//添加日志附件
	public function addlogfile(){	
		$m_config = M('config');
		if(!empty($_FILES)){			
			if (isset($_FILES['file']['size']) && $_FILES['file']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
				$value = unserialize($defaultinfo['value']);
				$allow_file_type = str_replace(",php","",$value['allow_file_type']);
				$upload->allowExts  = explode(',', $allow_file_type);
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					$this->ajaxReturn($upload->getErrorMsg(),$upload->getErrorMsg(),0);
					echo $upload->getErrorMsg();
				}else{
					$info = $upload->getUploadFileInfo();
				}
			}
			$m_file = M('File');
			if(is_array($info[0]) && !empty($info[0])){
				if(substr($info[0]['savename'], -3)=='jpg' || substr($info[0]['savename'], -3)=='png' || substr($info[0]['savename'], -4)=='jpeg'){
					$data['file_path_thumb'] = $info[0]['savepath'] .'thumb_'. $info[0]['savename'];
				}
				$data['file_path'] = $info[0]['savepath'] . $info[0]['savename'];
				$data['name'] = $info[0]['name'];
				$data['role_id'] = session('role_id');
				$data['size'] = $info[0]['size'];
				$data['create_date'] = time(); 
				if($file_id = $m_file->add($data)){
					if($_GET['r'] && $_GET['module'] && $_GET['id']){
						$r_file_module = M($_REQUEST['r']);
						$module = $_REQUEST['module'];
						$m_id = $_REQUEST['id'];

						$temp = array();
						$temp['file_id'] = $file_id;
						$temp[$module . '_id'] = $m_id;
						if($r_file_module->add($temp)){
							echo $file_id;
						}else{
							echo '"'.$data['name'].'" 上传失败！';
						}
					}else{
						echo $file_id;
					}
				}else{
					echo '"'.$data['name'].'" 上传失败！';
				}
			}else{
				echo '"'.$data['name'].'" 上传失败！';
			}
		}else{
			$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
			$value = unserialize($defaultinfo['value']);
			$this->allowExts  = $value['allow_file_type'];// 设置附件上传类型
			$this->display();
		}
	}
	public function add_file(){
		$m_config = M('config');
		if(!empty($_FILES)){
			if (isset($_FILES['file']['size']) && $_FILES['file']['size'] != null) {
				//如果有文件上传 上传附件
				import('@.ORG.UploadFile');
				//导入上传类
				$upload = new UploadFile();
				//设置上传文件大小
				$upload->maxSize = 20000000;
				//设置附件上传目录
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				
				$defaultinfo = $m_config->where('name = "defaultinfo"')->find();
				$value = unserialize($defaultinfo['value']);
				$allow_file_type = str_replace(",php","",$value['allow_file_type']);
				$upload->allowExts  = explode(',', $allow_file_type);// 设置附件上传类型
				
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					echo "L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE')";
					//$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {// 上传错误提示错误信息
					//alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']."&tar=file");
					echo $upload->getErrorMsg();
				}else{// 上传成功 获取上传文件信息
					$info = $upload->getUploadFileInfo();
				}
			}
			
			$m_file = M('File');
			$r_file_module = M($_REQUEST['r']);
			$module = $_REQUEST['module'];
			$m_id = $_REQUEST['id'];
		
			if(is_array($info[0]) && !empty($info[0])){
				if(substr($info[0]['savename'], -3)=='jpg' || substr($info[0]['savename'], -3)=='png' || substr($info[0]['savename'], -4)=='jpeg'){
					$data['file_path_thumb'] = $info[0]['savepath'] .'thumb_'. $info[0]['savename'];
				}
				$data['file_path'] = $info[0]['savepath'] . $info[0]['savename'];
				$data['name'] = $info[0]['name'];
				$data['role_id'] = session('role_id');
				$data['size'] = $info[0]['size'];
				$data['create_date'] = time(); 
				if($file_id = $m_file->add($data)){
					echo $file_id;
				}else{
					echo '"'.$data['name'].'" 上传失败！';
				}				
			}else{
				echo '"'.$data['name'].'" 上传失败！';
			}
		}
	}
	public function delete(){
		$file_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		if (0 == $file_id){
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}else{
			if (isset($_GET['r']) && isset($_GET['id'])) {
				$m_r = M($_GET['r']);
				$m_file = M('file');
				$file = $m_file->where('file_id = %d', $_GET['id'])->find();
				if (is_array($file) && ($file['role_id'] == session('role_id') || session('?admin'))){
					if ($m_r->where('file_id = %d',$_GET['id'])->delete()) {
						@unlink($file['file_path']);
						if ($m_file->where('file_id = %d',$_GET['id'])->delete()) {
							$this->ajaxReturn('',L('DELETED SUCCESSFULLY'),1);
						}else{
							$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
						}
					}else {
						$this->ajaxReturn('',L('DELETE FAILED CONTACT THE ADMINISTRATOR'),0);
					}
				} else {
					$this->ajaxReturn('',L('YOU_HAVE_NO_RIGHT_TO_DELETE_THE_ATTACHMENT'),0);
				}
			}elseif(empty($_GET['r']) && isset($_GET['id'])){
				$m_file = M('file');
				$file = $m_file->where('file_id = %d', $_GET['id'])->find();
				if (is_array($file) && ($file['role_id'] == session('role_id') || session('?admin'))) {
					if($m_file->where('file_id = %d', $_GET['id'])->delete()){
						@unlink($file['file_path']);
						$this->ajaxReturn('',L('OPERATION_IS_SUCCESSFUL'),1);
					}
				} else {
					$this->ajaxReturn('',L('YOU_HAVE_NO_RIGHT_TO_DELETE_THE_ATTACHMENT'),0);
				}
			}
		}
	}
	//下载通用
	public function filedownload(){
		$path = trim(urldecode($_GET['file_path']));
		$name = trim(urldecode($_GET['file_name']));
        $num = strlen(getExtension($name))+1;
        $result = substr($name,0,-$num);
		if(file_exists($path) && $result && strpos($path, 'Uploads') && !strpos($path, '..')){
		    download($path,$result);
		}else {
		   alert('error','sorry，文件不存在！',$_SERVER['HTTP_REFERER']);
		}
		$this->alert = parseAlert();
	}
	
	public function manager(){
		error_reporting(0);
		ini_set('display_errors',false); 
		import('@.ORG.Services_JSON');
		if($_GET['dir'] == 'file'){
			//根目录路径，可以指定绝对路径				
			if(is_dir(UPLOAD_PATH.'/Common_files/')){ 
				$root_path = UPLOAD_PATH.'/Common_files/';
			}elseif(is_dir(UPLOAD_PATH)){
				$root_path = UPLOAD_PATH;
			}else{
				$root_path = './';
			}
			
			//根目录URL，可以指定绝对路径				
			if(is_dir(UPLOAD_PATH.'/Common_files/')){ 
				$root_url = UPLOAD_PATH.'/Common_files/';
			}elseif(is_dir(UPLOAD_PATH)){
				$root_url = UPLOAD_PATH;
			}else{
				$root_url = './';
			}
			
			//图片扩展名
			$ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp','txt','doc','docx','xsl','ppt','pdf','zip','rar');
		}else{
			//根目录路径，可以指定绝对路径
			if(is_dir(UPLOAD_PATH.'/Common_images/')){ 
				$root_path = UPLOAD_PATH.'/Common_images/';
			}elseif(is_dir(UPLOAD_PATH)){
				$root_path = UPLOAD_PATH;
			}else{
				$root_path = './';
			}
			
			//根目录URL，可以指定绝对路径		
			if(is_dir(UPLOAD_PATH.'/Common_images/')){ 
				$root_url = UPLOAD_PATH.'/Common_images/';
			}elseif(is_dir(UPLOAD_PATH)){
				$root_url = UPLOAD_PATH;
			}else{
				$root_url = './';
			}
			
			//图片扩展名
			$ext_arr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');
		}
		//根据path参数，设置各路径和URL
		if (empty($_GET['path'])) {
			$current_path = realpath($root_path) . '/';
			$current_url = $root_url;
			$current_dir_path = '';
			$moveup_dir_path = '';
		} else {
			$current_path = realpath($root_path) . '/' . $_GET['path'];
			$current_url = $root_url . $_GET['path'];
			$current_dir_path = $_GET['path'];
			$moveup_dir_path = preg_replace('/(.*?)[^\/]+\/$/', '$1', $current_dir_path);
		}
		//排序形式，name or size or type
		$order = empty($_GET['order']) ? 'name' : strtolower($_GET['order']);

		//不允许使用..移动到上一级目录
		if (preg_match('/\.\./', $current_path)) {
			echo 'Access is not allowed.';
			exit;
		}
		//最后一个字符不是/
		if (!preg_match('/\/$/', $current_path)) {
			echo 'Parameter is not valid.';
			exit;
		}
		//目录不存在或不是目录
		if (!file_exists($current_path) || !is_dir($current_path)) {
			echo 'Directory does not exist.';
			exit;
		}

		//遍历目录取得文件信息
		$file_list = array();
		if ($handle = opendir($current_path)) {
			$i = 0;
			while (false !== ($filename = readdir($handle))) {
				if ($filename{0} == '.') continue;
				$file = $current_path . $filename;
				if (is_dir($file)) {
					$file_list[$i]['is_dir'] = true; //是否文件夹
					$file_list[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件
					$file_list[$i]['filesize'] = 0; //文件大小
					$file_list[$i]['is_photo'] = false; //是否图片
					$file_list[$i]['filetype'] = ''; //文件类别，用扩展名判断
				} else {
					$file_list[$i]['is_dir'] = false;
					$file_list[$i]['has_file'] = false;
					$file_list[$i]['filesize'] = filesize($file);
					$file_list[$i]['dir_path'] = '';
					$file_ext = strtolower(array_pop(explode('.', trim($file))));
					$file_list[$i]['is_photo'] = in_array($file_ext, $ext_arr);
					$file_list[$i]['filetype'] = $file_ext;
				}
				$file_list[$i]['filename'] = $filename; //文件名，包含扩展名
				$file_list[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
				$i++;
			}
			closedir($handle);
		}

		
		usort($file_list, 'cmp_func');

		$result = array();
		//相对于根目录的上一级目录
		$result['moveup_dir_path'] = $moveup_dir_path;
		//相对于根目录的当前目录
		$result['current_dir_path'] = $current_dir_path;
		//当前目录的URL
		$result['current_url'] = $current_url;
		//文件数
		$result['total_count'] = count($file_list);
		//文件列表数组
		$result['file_list'] = $file_list;

		//输出JSON字符串
		header('Content-type: application/json; charset=UTF-8');
		$json = new Services_JSON();
		echo $json->encode($result);

	}
}