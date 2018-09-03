<?php
/**
 *附件相关
 **/
class FileVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('add','delete')
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
	 * 添加附件
	 * @param 
	 * @author 
	 * @return 
	 */
	public function add() {
		$m_config = M('Config');
		if (!empty($_FILES)) {
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
					$this->ajaxReturn('',L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'),0);
				}
				$upload->savePath = $dirname;
				if (!$upload->upload()) {
					$this->ajaxReturn('',$upload->getErrorMsg().$upload->getErrorMsg(),0);
				} else {
					$info = $upload->getUploadFileInfo();
				}
			}
			$m_file = M('File');
			if (is_array($info[0]) && !empty($info[0])) {
				if (substr($info[0]['savename'], -3)=='jpg' || substr($info[0]['savename'], -3)=='png' || substr($info[0]['savename'], -4)=='jpeg') {
					$data['file_path_thumb'] = $info[0]['savepath'] .'thumb_'. $info[0]['savename'];
				}
				$data['file_path'] = $info[0]['savepath'] . $info[0]['savename'];
				$data['name'] = $info[0]['name'];
				$data['role_id'] = session('role_id');
				$data['size'] = $info[0]['size'];
				$data['create_date'] = time(); 
				if ($file_id = $m_file->add($data)) {
					//返回数据
					$res_data = array();
					$res_data['img_data'] = $data;
					$res_data['img_data']['file_type'] = end(explode('.',$data['name']));
					$res_data['img_data']['size'] = round($data['size']/1024,2).'Kb';
					if (intval($data['size']) > 1024*1024) {
						$res_data['size'] = round($data['size']/(1024*1024),2).'Mb';
					}

					if ($_POST['r'] && $_POST['module'] && $_POST['id']) {
						$r_file_module = M(trim($_POST['r']));
						$module = trim($_POST['module']);
						$m_id = intval($_POST['id']);

						$temp = array();
						$temp['file_id'] = $file_id;
						$temp[$module . '_id'] = $m_id;
						if ($r_file_module->add($temp)) {
							$res_data['data'] = $file_id;
							$res_data['info'] = '附件上传成功！';
							$res_data['status'] = 1;
							$this->ajaxReturn($res_data,'JSON');
						} else {
							$this->ajaxReturn('',$data['name'].'上传失败！',0);
						}
					} else {
						$res_data['data'] = $file_id;
						$res_data['info'] = '附件上传成功！';
						$res_data['status'] = 1;
						$this->ajaxReturn($res_data,'JSON');
					}
				} else {
					$this->ajaxReturn('',$data['name'].'上传失败！',0);
				}
			} else {
				$this->ajaxReturn('',$data['name'].'上传失败！',0);
			}
		}
	}

	/**
	 * 删除附件
	 * @param 
	 * @author 
	 * @return 
	 */
	public function delete() {
		if ($this->isPost()) {
			$file_id = $_POST['id'] ? intval($_POST['id']) : 0;
			if ($file_id) {
				$m_file = M('File');
				$r_module = $_POST['module'] ? trim($_POST['module']) : '';
				if (!$r_module) {
					$this->ajaxReturn('','参数错误！',0);
				}
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
					$file_info = $m_file->where('file_id = %d',$file_id)->find();
					$msg = $m_file->where('file_id = %d',$file_id)->delete();
					if ($msg) {
						@unlink($file_info['file_path']);
						if ($r->where('file_id = %d',$file_id)->find()) {
							$r->where('file_id = %d',$file_id)->delete();
						}
						$this->ajaxReturn('','删除成功！',1);
					} else {
						$this->ajaxReturn('','删除失败！',0);
					}
				} else {
					$this->ajaxReturn('','参数错误！',0);
				}
			}
		} else {
			$this->ajaxReturn('','跑神儿了！:-D',0);
		}
	}
}