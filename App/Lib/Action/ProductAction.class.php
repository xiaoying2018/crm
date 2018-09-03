<?php
class ProductAction extends Action {
	
	public function _initialize(){
		$action = array(
			'permission'=>array('getProductByBusiness'),
			'allow'=>array('adddialog','editdialog', 'allproductdialog','validate','check','delimg','sortimg','mutildialog','changecontent','getmonthlyamount','getmonthlysales','getcurrentstatus','mutildialog_product_contract','mutildialog_product','advance_search','categorylist')
		);
		B('Authenticate', $action);
		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME); 
	}
	
	/**
	*  Ajax检测产品名称
	*
	**/
	public function check(){
		if($_REQUEST['product_id']){
			$where['product_id'] = array('neq',$_REQUEST['product_id']);
		}
		import("@.ORG.SplitWord");
		$sp = new SplitWord();
		$m_product = M('Product');
		if ($this->isAjax()) {
			//$fields_info = M('Fields')->where(array('model'=>'product','field'=>'name'))->field('is_validate,is_unique')->find();
			//if(intval($fields_info['is_validate']) == 1 && intval($fields_info['is_unique']) == 1){
				$split_result = $sp->SplitRMM($_POST['name']);
				if(!is_utf8($split_result)) $split_result = iconv("GB2312//IGNORE", "UTF-8", $split_result) ;
				$result_array = explode(' ',trim($split_result));
				$name_list = $m_product->where($where)->getField('name', true);
				$seach_array = array();
				foreach($name_list as $k=>$v){
					$search = 0;
					foreach($result_array as $k2=>$v2){
						if(strpos($v, $v2) > -1){
							$v = str_replace("$v2","<span style='color:red;'>$v2</span>", $v, $count);
							$search += $count;
						}
					}
					if($search > 0) $seach_array[$k] = array('value'=>$v,'search'=>$search);
				}
				$seach_sort_result = array_sort($seach_array,'search','desc');
				if(empty($seach_sort_result)){
					$this->ajaxReturn(0,'可以添加',0);
				}else{
					$this->ajaxReturn($seach_sort_result,'已创建相近产品',1);
				}
			//}else{
			//	$this->ajaxReturn(0,'可以添加',0);
			//}
		}
	}
	
	/**
	*产品验证
	*
	**/
	public function validate() {
		if($this->isAjax()){
            if(!$this->_request('clientid','trim') || !$this->_request($this->_request('clientid','trim'),'trim')) $this->ajaxReturn("","",3);
            $field = M('Fields')->where('model = "product" and field = "%s"',$this->_request('clientid','trim'))->find();
            $m_product = $field['is_main'] ? D('Product') : D('ProductData');
            $where[$this->_request('clientid','trim')] = array('eq',$this->_request($this->_request('clientid','trim'),'trim'));
            if($this->_request('id','intval',0)){
                $where[$m_product->getpk()] = array('neq',$this->_request('id','intval',0));
            }
            if($this->_request('clientid','trim')) {
				if ($m_product->where($where)->find()) {
					$this->ajaxReturn("","",1);
				} else {
					$this->ajaxReturn("","",0);
				}
			}else{
				$this->ajaxReturn("","",0);
			}
		}
	}
	
	public function index(){
		$product = D('ProductView'); // 实例化User对象
		import('@.ORG.Page');// 导入分页类
		$category = M('product_category');
		$where = array();
		$params = array();
		
		$idArray = Array();
		if($_GET['category_id']){
			$category_list = $category->select();
			$categoryList = getSubCategory($_GET['category_id'], $category_list, '');
			foreach($categoryList as $value){
				$idArray[] = $value['category_id'];
			}
		}
		$idList = empty($idArray) ? $_GET['category_id'] : $_GET['category_id'] . ',' . implode(',', $idArray);
		$p = isset($_GET['p'])?$_GET['p']:1;
		if ($_REQUEST["field"]) {
			if (trim($_REQUEST['field']) == "all") {
				/* $field = is_numeric(trim($_REQUEST['search'])) ? 'product.name|cost_price|sales_price|link|pre_sale_count|stock_count' : 'product.name|link|development_team'; */
			} else {
				$field = trim($_REQUEST['field']);
			}
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			
			$p_where['name'] = array('like','%'.$search.'%');
			$p_where['product_num'] = array('like','%'.$search.'%');
			$p_where['_logic'] = 'OR';
			$product_ids = $product ->where($p_where)->getField('product_id',true);
			if($product_ids){
				$where['product_id'] = array('in',$product_ids);
			}
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		//多选类型字段
		$check_field_arr = M('Fields')->where(array('model'=>'product','form_type'=>'box','setting'=>array('like','%'."'type'=>'checkbox'".'%')))->getField('field',true);
		//高级搜索
		if(!$_GET['field']){
			foreach($_GET as $k=>$v){
				if($k != 'act' && $k != 'content' && $k != 'p' && $k !='condition' && $k != 'listrows' && $k !='daochu' && $k !='this_page' && $k !='current_page' && $k !='export_limit'){
					if(is_array($v)){
						 if ($v['state']){
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
							if($k == 'create_time'){
								$k = 'product.create_time';
							}elseif($k == 'update_time'){
								$k = 'product.update_time';
							}
							//时间段查询
							if ($v['start'] && $v['end']) {
								$where[$k] = array('between',array(strtotime($v['start']),strtotime($v['end'])+86399));
							} elseif ($v['start']) {
								$where[$k] = array('egt',strtotime($v['start']));
							} else {
								$where[$k] = array('elt',strtotime($v['end'])+86399);
							}
						}elseif(($v['value']) != ''){
							if (in_array($k,$check_field_arr)) {
								$where[$k] = field($v['value'],'contains');
							} else {
								$where[$k] = field($v['value'],$v['condition']);
							}
						}
					}else{
						if(!empty($v)){
							$where[$k] = field($v);
						}
				    }
				}
				if(is_array($v)){
					foreach ($v as $key => $value) {
						$params[] = $k.'['.$key.']='.$value;
					}
				}else{
					$params[] = $k.'='.$v;
				}
			}
		}

		//高级搜索字段
		$fields_list_data = M('Fields')->where(array('model'=>array('in',array('','product')),'is_main'=>1))->field('field,form_type')->select();
		foreach($fields_list_data as $k=>$v){
			$fields_data_list[$v['field']] = $v['form_type'];
		}
		$fields_search = array();
		foreach($params as $k=>$v){
			if(strpos($v,'[condition]=') || strpos($v,'[value]=') || strpos($v,'[state]=') || strpos($v,'[city]=') || strpos($v,'[area]=') || strpos($v,'[start]=') || strpos($v,'[end]=')){
				$field = explode('[',$v);

				if(strpos($field[0],'.')){
					$ex_field = explode('.',$field[0]);
					$field[0] = $ex_field[1];
				}
				
				if(strpos($v,'[condition]=')){
					$condition = explode('=',$v);
					$fields_search[$field[0]]['field'] = $field[0];
					$fields_search[$field[0]]['condition'] = $condition[1];
				} elseif (strpos($v,'[state]=')) {
					$state = explode('=',$field[1]);
					$fields_search[$field[0]]['state'] = $state[1];
				} elseif (strpos($v,'[city]=')) {
					$city = explode('=',$field[1]);
					$fields_search[$field[0]]['city'] = $city[1];
				} elseif (strpos($v,'[area]=')) {
					$area = explode('=',$field[1]);
					$fields_search[$field[0]]['area'] = $area[1];
				} elseif (strpos($v,'[start]=')) {
					$start = explode('=',$field[1]);
					$fields_search[$field[0]]['field'] = $field[0];
					$fields_search[$field[0]]['start'] = $start[1];
				} elseif (strpos($v,'[end]=')) {
					$end = explode('=',$field[1]);
					$fields_search[$field[0]]['end'] = $end[1];
				}else{
					$value = explode('=',$v);
					if($fields_search[$field[0]]['field']){
						$fields_search[$field[0]]['value'] = $value[1];
					}else{
						$fields_search[$field[0]]['field'] = $field[0];
						$fields_search[$field[0]]['condition'] = 'eq';
						$fields_search[$field[0]]['value'] = $value[1];
					}
				}
				$fields_search[$field[0]]['form_type'] = $fields_data_list[$field[0]];
			}
		}
		$this->fields_search = $fields_search;
		$by = $this->_get('by','trim');
		if($by == 'deleted'){
			$where['is_deleted'] = 1;
			unset($where['by']);
		}
		if ($by != 'deleted') {
			$where['is_deleted'] = array('neq',1);
			unset($where['by']);
		}

		if($_GET['listrows']){
			$listrows = intval($_GET['listrows']);
			$params[] = "listrows=" . intval($_GET['listrows']);
		}else{
			$listrows = 15;
			$params[] = "listrows=15";
		}
		if ($_GET['category_id']) {
			unset($where['category_id']);
			$where['product.category_id'] = array('in',$idList);
			$dc_id = $_GET['daochu'];
			if($dc_id !=''){
				$where['product_id'] = array('in',$dc_id);
			}
			if(trim($_GET['act']) == 'excel'){
				if(checkPerByAction('product','excelexport')){
					$productList = $product->order('product_id desc')->where($where)->select();
					$this->excelExport($productList);
				}else{
					alert('error',  L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
				}
			}
			$list = $product->order('product_id desc')->where($where)->Page($p.','.$listrows)->select();
			$count = $product->where($where)->count();
			$p_num = ceil($count/$listrows);
			if($p_num<$p){
				$p = $p_num;
			}
		} else {
			if(trim($_GET['act']) == 'excel'){
				$dc_id = $_GET['daochu'];
				if($dc_id !=''){
					$where['product_id'] = array('in',$dc_id);
				}
				$current_page = intval($_GET['current_page']);
				$export_limit = intval($_GET['export_limit']);
				$limit = ($export_limit*($current_page-1)).','.$export_limit;
				if(session('?admin')){
					$productList = $product->order('product_id desc')->where($where)->limit($limit)->select();
					session('export_status', 1);
					$this->excelExport($productList);
				}else{
					if(checkPerByAction('product','excelexport')){
						$productList = $product->order('product_id desc')->where($where)->limit($limit)->select();
						session('export_status', 1);
						$this->excelExport($productList);
					}else{
						alert('error',  L('HAVE NOT PRIVILEGES'),$_SERVER['HTTP_REFERER']);
					}
				}
			}
			$count = $product->where($where)->count() ? $product->where($where)->count() : '0';
			// 查询满足要求的总记录数
			$p_num = ceil($count/$listrows);
			if($p_num<$p){
				$p = $p_num;
			}
			$list = $product->order('product_id desc')->where($where)->Page($p.','.$listrows)->select();
		}	
		$m_product_images = M('productImages');
		foreach ($list as $k => $v) {
			$list[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
			$stock_count = M('stock')->where('product_id = %d', $v['product_id'])->sum('amounts');
			$list[$k]['stock_count'] = empty($stock_count) ? $list[$k]['stock_count'] = 0 : $list[$k]['stock_count'] = $stock_count;
			$product_images_info = '';
			$product_images_info = $m_product_images->where('product_id = %d and is_main = 1', $v['product_id'])->field('path,thumb_path')->find();
			$list[$k]['path'] = $product_images_info['path'];
			$list[$k]['thumb_path'] = $product_images_info['thumb_path'];
		}
		if($_GET['asc_order'] == 'stock_count'){
			$list = array_sorts($list,'stock_count','asc');
		}elseif($_GET['desc_order'] == 'stock_count'){
			$list = array_sorts($list,'stock_count','desc');
		}
		$Page = new Page($count,$listrows);// 实例化分页类 传入总记录数和每页显示的记录数
		if (!empty($_GET['category_id'])) {
			$params['category_id'] = 'category_id='.trim($_GET['category_id']);
		}
		
		$this->parameter = implode('&', $params);
		//by_parameter(特殊处理)
		$this->by_parameter = str_replace('by='.$_GET['by'], '', implode('&', $params));

		if ($_GET['desc_order']) {
			$params[] = "desc_order=" . trim($_GET['desc_order']);
		} elseif($_GET['asc_order']){
			$params[] = "asc_order=" . trim($_GET['asc_order']);
		}
		$Page->parameter = implode('&', $params);
		$show = $Page->show();// 分页显示输出
		//获取下级和自己的岗位列表,搜索用
		$this->listrows = $listrows;
		$this->field_array = getIndexFields('product');
		$this->field_list = getMainFields('product');
		$this->tree_code = getProductTreeCode(0,1); //类别选项
		$this->assign('list',$list);// 赋值数据集
		$this->assign('page',$show);// 赋值分页输出
		$this->assign('count',$count);
		$this->alert=parseAlert();
		$this->display(); // 输出模板
	}
	
	public function edit(){
		$product = D('ProductView')->where('product.product_id = %d',$this->_request('id'))->find();
		if (!$product) {
			$this->error(L('THERE_IS_NO_PRODUCT'));
		}
		$field_list = M('Fields')->where('model = "product"')->order('order_id')->select();
		if($this->isPost()){
			if (!isset($_POST['name']) || $_POST['name'] == '') {
				$this->error( L('PRODUCT_NAME_CANNOT_BE_EMPTY'));
			}
			$m_product = D('Product');
			$m_product_data = D('ProductData');
			$field_list = M('Fields')->where('model = "product"')->order('order_id')->select();
			foreach ($field_list as $v){
				switch($v['form_type']) {
					case 'address':
						$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
					break;
					case 'datetime':
						$_POST[$v['field']] = strtotime($_POST[$v['field']]);
					break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
						}
					break;
				}
			}
			if($m_product->create()){
				if($m_product_data->create()!==false){
					if ($m_product->name == '') {
						$this->error(L('PLEASE_FILL_OUT_THE_PRODUCT_NAME'));
					}
					$m_product->update_time = time();
					$a = $m_product->where('product_id= %d',$product['product_id'])->save();
					$b = $m_product_data->where('product_id=' . $product['product_id'])->save();
					actionLog($product['product_id']);
					if($a && $b!==false) {
						//上传产品主图和副图至服务器
						if (array_sum($_FILES['main_pic']['size']) > 0 || array_sum($_FILES['sec_pic']['size']) > 0) {
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
								$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
							}
							$upload->savePath = $dirname;
							$upload->thumb = true;//生成缩图
							$upload->thumbRemoveOrigin = false;//是否删除原图
							
							if(!$upload->upload()) {// 上传错误提示错误信息
								alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
							}else{
								// 上传成功 获取上传文件信息
								$info =  $upload->getUploadFileInfo();
								if(is_array($info[0]) && !empty($info[0])){
									$upload = $dirname . $info[0]['savename'];
								}else{
									$this->error('图片上传失败，请重试！');
								}
								//写入数据库
								$m_product_images = M('productImages');
								$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);
								$thumb_old = $m_product_images->where('product_id =%d', $product['product_id'])->getField('path');
								foreach($info as $iv){
									$img_data['name'] = $iv['name'];
									$img_data['save_name'] = $iv['savename'];
									$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
									$img_data['path'] = $iv['savepath'].$iv['savename'];
									$img_data['thumb_path'] = $thumb_path;
									$img_data['create_time'] = time();
									if($iv['key'] == 'main_pic'){
										//如果是主图，则修改
										if($m_product_images->where('images_id = %d and is_main = 1', intval($_POST['main_images_id']))->count()){
											//存在则修改
											$m_product_images->where('images_id = %d',intval($_POST['main_images_id']))->save($img_data);
											unlink($thumb_old);
										}else{
											//不存在则添加
											$img_data['is_main'] = 1;
											$img_data['product_id'] = $product['product_id'];
											$img_data['listorder'] = intval($m_product_images->max('listorder'))+1;
											$m_product_images->add($img_data);
										}
										
									}else{
										//如果是副图，则添加
										$img_data['is_main'] = 0;
										$img_data['product_id'] = $product['product_id'];
										$img_data['listorder'] = intval($m_product_images->max('listorder'))+1;
										$m_product_images->add($img_data);
									}
								}
							}
						}
						alert('success', L('PRODUCT_EDIT_SUCCESS'), U('product/index'));
					} else {
						$this->error(L('PRODUCT_EDIT_FAILED'));
					}
				}else{
					$this->error($m_product_data->getError());
				}

			}else{
				$this->error($m_product->getError());
			}
		}else{
			$field_list = field_list_html("edit","product",$product);
			//产品图片
			$m_product_images = M('productImages');
			$product['images']['main'] = $m_product_images->where('product_id = %d and is_main = 1', $product['product_id'])->find();
			$product['images']['secondary'] = $m_product_images->where('product_id = %d and is_main = 0', $product['product_id'])->order('listorder asc')->select();
		}
		$alert = parseAlert();
		$this->alert = $alert;

		$this->product = $product;
		$this->field_list = $field_list;

		$this->jump_url = $_SERVER['HTTP_REFERER'];
		$this->display();
	}
	
	public function add() {
		if($this->isPost()) {
			$m_product = D('Product');
			$m_product_data = D('ProductData');
			if (!isset($_POST['name']) || $_POST['name'] == '') {
				$this->error( L('PRODUCT_NAME_CANNOT_BE_EMPTY'));
			}/* elseif ($m_product->where('name = "%s"', trim($_POST['name']))->find()) {
				$this->error(L('THE_EXISTING_PRODUCT_OPPORTUNITIES'));
			} */
			$field_list = M('Fields')->where('model = "product" and in_add = 1')->order('order_id')->select();
			foreach ($field_list as $v){
				switch($v['form_type']) {
					case 'address':
						$_POST[$v['field']] = implode(chr(10),$_POST[$v['field']]);
					break;
					case 'datetime':
						$_POST[$v['field']] = strtotime($_POST[$v['field']]);
					break;
					case 'box':
						eval('$field_type = '.$v['setting'].';');
						if($field_type['type'] == 'checkbox'){
							$b = array_filter($_POST[$v['field']]);
							$_POST[$v['field']] = !empty($b) ? implode(chr(10),$b) : '';
						}
					break;
				}
			}
			if($m_product->create()){
				if($m_product_data->create()!==false){
					$m_product->creator_role_id = session('role_id');
					$m_product->create_time = time();
					$m_product->update_time = time();
					if ($product_id = $m_product->add()) {
						//产品增加至仓库
						$stock = M('Stock');
						if(is_array($_POST['warehouse_id'])){
							foreach($_POST['warehouse_id'] as $vo){
								$stock_data['product_id'] = $product_id;
								$stock_data['warehouse_id'] = $vo;
								$stock_data['amounts'] = 0;
								$stock_data['last_change_time'] = time();
								$stock ->add($stock_data);
							}
						}
						
						$m_product_data->product_id = $product_id;
						actionLog($product_id);
						if($m_product_data->add()){
							//上传产品主图和副图至服务器
							if (array_sum($_FILES['main_pic']['size'])) {
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
								$upload->thumb = true;//生成缩图
								$upload->thumbRemoveOrigin = false;//是否删除原图
								if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
									$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
								}
								$upload->savePath = $dirname;
								
								if(!$upload->upload()) {// 上传错误提示错误信息
									alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
								}else{// 上传成功 获取上传文件信息
									$m_product_images = M('productImages');
									$info =  $upload->getUploadFileInfo();
									if(is_array($info[0]) && !empty($info[0])){
										$upload = $dirname . $info[0]['savename'];
									}else{
										$this->error('图片上传失败，请重试！');
									}
									$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);
									
									//写入数据库
									foreach($info as $iv){
										if($iv['key'] == 'main_pic'){
											//主图
											$img_data['is_main'] = 1;
										}else{
											//副图
											$img_data['is_main'] = 0;
										}
										$img_data['product_id'] = $product_id;
										$img_data['name'] = $iv['name'];
										$img_data['save_name'] = $iv['savename'];
										$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
										$img_data['path'] = $iv['savepath'].$iv['savename'];
										$img_data['thumb_path'] = $thumb_path; //缩略图
										$img_data['create_time'] = time();
										$img_data['listorder'] = intval($m_product_images->max('listorder'))+1;
										$m_product_images->add($img_data);
									}
								}
							}
							
							if($_POST['submit'] == L('SAVE')) {
								alert('success', L('PRODUCT_ADDED_SUCCESSFULLY'), U('product/index'));
							} else{
								alert('success', L('PRODUCT_ADDED_SUCCESSFULLY'), U('product/index'));
							}
						}else{
							$this->error($m_product_data->getError());
						}
					} else {
						$this->error($m_product->getError());
					}
				}else{
					$this->error($m_product_data->getError());
				}
			}else{
				$this->error($m_product->getError());
			}
		}else{
			$m_warehouse = M('warehouse');
			// $this->house_list = $m_warehouse ->select();
			$field_list = field_list_html("add","product");
			//dump($field_list);die;
			$this->field_list = $field_list;
			$this->alert = parseAlert();
			$this->display();
		}
		
	}
	public function view(){
		$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
		$field_list = M('Fields')->where('model = "product"')->order('order_id')->select();
		foreach($field_list as $k=>$v){
			if(trim($v['input_tips'])){
				$input_tips = ' &nbsp; <span style="color:red">('.L('INFUSE').$v['input_tips'].')</span>';
			}else{
				$input_tips = '';
			}
		}
		if (0 == $product_id) {
			alert('error', L('PARAMETER_ERROR'), U('product/index'));
		} else {
			$product = D('ProductView')->where('product.product_id = %d',$product_id)->find();
			$product['owner'] = D('RoleView')->where('role.role_id = %d', $product['creator_role_id'])->find();
			$log_ids = M('rLogProduct')->where('product_id = %d', $product_id)->getField('log_id', true);
			if(!empty($log_ids)){
				$product['log'] = M('log')->where('log_id in (%s)', implode(',', $log_ids))->select();
			}
			$log_count = 0;
			foreach ($product['log'] as $key=>$value) {
				$product['log'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
				$log_count++;
			}
			$product['log_count'] = $log_count; 
			
			$file_ids = M('rFileProduct')->where('product_id = %d', $product_id)->getField('file_id', true);
			if(!empty($file_ids)){
				$product['file'] = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
				$file_count = 0;
				foreach ($product['file'] as $key=>$value) {
					$product['file'][$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
					$product['file'][$key]['size'] = ceil($value['size']/1024);
					/*判断文件格式 对应其图片*/
					$houzhui = getExtension($value['name']);
					switch ($houzhui) {
						case 'doc':
							$pic = 'doc.png';
							break;
						case 'docx':
							$pic = 'doc.png';
							break;
						case 'pptx':
							$pic = 'ppt.png';
							break;
						case 'ppt':
							$pic = 'ppt.png';
							break;
						case 'xls':
							$pic = 'excel.png';
							break;
						case 'zip':
							$pic = 'zip.png';
							break;
						case 'zip':
							$pic = 'zip.png';
							break;
						case 'pdf':
							$pic = 'pdf.png';
							break;
						case 'png':
							$pic = 'pic.png';
							break;
						case 'jpg':
							$pic = 'pic.png';
							break;
						case 'jpeg':
							$pic = 'pic.png';
							break;
						case 'gif':
							$pic = 'pic.png';
							break;
						default:
							$pic = 'unknown.png';
							break;
					}
					$product['file'][$key]['pic'] = $pic;

					$file_count++;
				}
				$product['file_count'] = $file_count;
			}
			
			//产品图片
			$m_product_images = M('productImages');
			$product['images']['main'] = $m_product_images->where('product_id = %d and is_main = 1', $product_id)->find();
			$product['images']['secondary'] = $m_product_images->where('product_id = %d and is_main = 0', $product_id)->order('listorder asc')->select();
			foreach ($field_list as $k => $v) {
				if($v['field'] == 'category_id'){
					$field_list[$k]['field'] = 'category_name';
				}
				if(stristr('http://',$v['default_value']) && 'http://' != $product[$v['field']] && '' != $product[$v['field']]){
					$product[$v['field']] = '<a href='.$product[$v['field']].' target="_blank">'.$product[$v['field']].'</a>';
				}
			}
			//日程信息 
			$m_event = M('event');
			$m_user = M('user');
			$event_list = $m_event ->where('module ="product" and module_id =%d',$product_id)->select();
			foreach($event_list as $k=>$v){
				$event_list[$k]['create_role_name'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('full_name');
				$event_list[$k]['img'] = $m_user ->where('role_id =%d',$v['creator_role_id'])->getField('img');
			}
			$this->event_list = $event_list;
			$this->product = $product;
			$this->field_list = $field_list;
			$this->alert = parseAlert();
			$this->display();
		}	
	}
	
	
	public function delete(){
		$m_product = M('product');
		$m_product_data = M('product_data');
		$m_product_images = M('productImages');
		$r_module = array('Log'=>'RLogProduct', 'File'=>'RFileProduct','rproductProduct','rContractProduct');
		if($this->isPost()){
			$product_ids = is_array($_POST['product_id']) ? implode(',', $_POST['product_id']) : '';
			if ('' == $product_ids) {
				alert('error', L('YOU_HAVE_NOT_CHOOSE_ANY_CONTENT') ,$_SERVER['HTTP_REFERER']);
			} else {
				$delete_data = array();
				$delete_date['is_deleted'] = 1;
				$delete_date['delete_role_id'] = session('role_id');
				$delete_date['delete_time'] = time();
				$product_delete = $m_product->where('product_id in (%s)', $product_ids)->save($delete_date);
				if($product_delete){
					alert('success','产品已下架！',$_SERVER['HTTP_REFERER']);
				}else{
					alert('error','产品下架失败，请联系管理员！',$_SERVER['HTTP_REFERER']);
				}

				//彻底删除数据（保留，后期可能使用）

				// $product_delete = $m_product->where('product_id in (%s)', $product_ids)->delete();
				// $product_data_delete = $m_product_data->where('product_id in (%s)', $product_ids)->delete();
				// if($product_delete && $product_data_delete){
				// 	foreach ($_POST['product_id'] as $value) {
				// 		actionLog($value);
				// 		foreach ($r_module as $key2=>$value2) {
				// 			$module_ids = M($value2)->where('product_id = %d', $value)->getField($key2 . '_id', true);
				// 			M($value2)->where('product_id = %d', $value) -> delete();
				// 			if(!is_int($key2)){	
				// 				M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
				// 			}
				// 		}
				// 		//删除图片
				// 		$images_files = $m_product_images->where('product_id = %d', $value)->select();
				// 		foreach($images_files as $files){
				// 			@unlink($files['path']);
				// 		}
				// 		$m_product_images->where('product_id = %d', $value)->delete();
				// 		M('Stock')->where('product_id =%d and amounts = 0',$value)->delete();
				// 	}
				// 	alert('success', L('DELETE_THE_SUCCESS') ,$_SERVER['HTTP_REFERER']);
				// } else {
				// 	alert('error', L('DELETE_FAILED_PLEASE_CONTACT_YOUR_ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
				// }
				
			}
		} elseif($_GET['id']) {
			$product_id = intval($_GET['id']);
			$product = $m_product->where('product_id = %d', $product_id)->find();
			if (is_array($product)) {
				//判断库存

				// $stock_count = M('stock')->where('product_id = %d', $product['product_id'])->sum('amounts');
				// if($stock_count > 0){
				// 	alert('error', L('THE_PRODUCT_IS_AVAILABLE_FROM_STOCK_AND_CAN_NOT_BE_DELETED'), $_SERVER['HTTP_REFERER']);
				// }
				$delete_data = array();
				$delete_date['is_deleted'] = 1;
				$delete_date['delete_role_id'] = session('role_id');
				$delete_date['delete_time'] = time();
				$product_delete = $m_product->where('product_id = %d', $product_id)->save($delete_date);
				if($product_delete){
					$this->ajaxReturn('','产品已下架！',1);
				}else{
					$this->ajaxReturn('','产品下架失败，请联系管理员！',0);
				}

				//彻底删除数据（保留，后期可能使用）

				// if($m_product->where('product_id = %d', $product_id)->delete()){
				// 	foreach ($r_module as $key2=>$value2) {
				// 		if(!is_int($key2)){
				// 			$module_ids = M($value2)->where('product_id = %d', $product_id)->getField($key2 . '_id', true);
				// 			M($value2)->where('product_id = %d', $product_id) -> delete();
				// 			M($key2)->where($key2 . '_id in (%s)', implode(',', $module_ids))->delete();
				// 		}
				// 	}
				// 	//删除图片
				// 	$images_files = $m_product_images->where('product_id = %d', $product_id)->select();
				// 	foreach($images_files as $files){
				// 		@unlink($files['path']);
				// 	}
				// 	$m_product_images->where('product_id = %d', $product_id)->delete();
					
				// 	alert('success', L('DELETE_THE_SUCCESS'), $_SERVER['HTTP_REFERER']);
				// }else{
				// 	alert('error', L('DELETE_FAILED_PLEASE_CONTACT_YOUR_ADMINISTRATOR'),$_SERVER['HTTP_REFERER']);
				// }
			} else {
				$this->ajaxReturn('',L('YOU_WANT_TO_DELETE_THE_RECORD_DOES_NOT_EXIST'),0);
			}			
		} else {
			alert('error', L('PLEASE_SELECT_PRODUCT_TO_DELETE'),$_SERVER['HTTP_REFERER']);
		}
	}

	//上架产品（类似回收站还原逻辑）
	public function revert(){
		if($this->isPost()){
			$product_ids = is_array($_POST['product_id']) ? implode(',', $_POST['product_id']) : intval($_POST['product_id']);
			if ('' == $product_ids) {
				alert('error', L('YOU_HAVE_NOT_CHOOSE_ANY_CONTENT') ,$_SERVER['HTTP_REFERER']);
			} else {
				$m_product = M('Product');
				$res_product = $m_product->where('product_id in (%s)', $product_ids)->setField('is_deleted',0);
				if($res_product){
					alert('success','产品已上架！', $_SERVER['HTTP_REFERER']);
				}else{
					alert('error', '产品上架失败，请重试！', $_SERVER['HTTP_REFERER']);
				}
			}
		}elseif($this->isGet()){
			$product_id = intval($_GET['product_id']);
			if($product_id){
				$m_product = M('Product');
				$res_product = $m_product->where('product_id = %d', $product_id)->setField('is_deleted',0);
				if($res_product){
					$this->ajaxReturn('','产品已上架！',1);
				}else{
					$this->ajaxReturn('','产品上架失败或已上架，请重试！',0);
				}
			}else{
				$this->ajaxReturn('','参数错误！',0);
			}
		}
		
	}
	
	public function editDialog(){
		if($this->isPost()){
			$r = trim($_POST['r']);
			$d_r = D($r);
			$d_r->create();
			if($d_r->save()){
				alert('success', L('MODIFY_THE_SUCCESS'),$_SERVER['HTTP_REFERER']);
			}else{
				alert('error', L('MODIFY_THE_FAILURE'),$_SERVER['HTTP_REFERER']);
			}
		}elseif ($_GET['r'] && $_GET['id']) {
			$rbs = M($_GET['r'])->where('id = %d', $_GET['id'])->find();
			$rbs['info'] = M('product')->where('product_id = %d', $rbs['product_id'])->find();
			$this->r = $_GET['r'];
			$this->rbs = $rbs;
			$this->display();
		}else{
			alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	public function listDialog(){
		if($this->isPost()){
			$r = $_POST['r'];
			$model_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
			$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
			$m_r = M($r);
			$m_id = $_POST['module'] . '_id';  //对应模块的id字段
			
			$data[$m_id] = $model_id;
			foreach ($_POST['product_id'] as $value) {
				$data['product_id'] = $value;
				if ($m_r -> add($data) <= 0) {
					alert('error', L('SELECT_A_PRODUCT_FAILURE'),$_SERVER['HTTP_REFERER']);
				}
			}
			alert('success', L('SELECT_A_PRODUCT_SUCCESS') ,$_SERVER['HTTP_REFERER']);
		}elseif ($_GET['r'] && $_GET['module'] && $_GET['id']) {
			$id_array = M($_GET['r']) -> where('%s = %d', $_GET['module'] . '_id', $_GET['id']) -> getField('product_id', true);
			$id_array[] = 0;
			$this -> r = $_GET['r'];
			$this -> module = $_GET['module'];
			$this -> model_id = $_GET['id'];
			$d_product = D('ProductView');
			$a = $d_product->where('product_id not in (%s)', implode(',',$id_array))->select();
			$this->list = $a;
			$this->display();
		}else{
			alert('error', L('PARAMETER_ERROR') ,$_SERVER['HTTP_REFERER']);
		}
	}
	
	public function addDialog(){
		if($this->isPost()){
			$r = $_POST['r'];
			$model_id = isset($_POST['model_id']) ? intval($_POST['model_id']) : 0;
			$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
			$m_r = D($r);
			$m_id = $_POST['module'] . '_id';  //对应模块的id字段
			$m_r->create();
			$m_r->$m_id = $model_id;
			if ($m_r -> add()) {
				alert('success', L('ADD_SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
			} else {
				alert('error', L('ADD_FAILURE'),$_SERVER['HTTP_REFERER']);
			}
			
		}elseif ($_GET['r'] && $_GET['module'] && $_GET['id']) {
			$id_array = M($_GET['r']) -> where('%s = %d', $_GET['module'] . '_id', $_GET['id']) -> getField('product_id', true);
			$id_array[] = 0;
			$this -> r = $_GET['r'];
			$this -> module = $_GET['module'];
			$this -> model_id = $_GET['id'];
			$d_product = D('ProductView');
			$a = $d_product->where('product_id not in (%s)', implode(',',$id_array))->select();
			$this->list = $a;
			$this->display();
		}else{
			alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	//弹出框
	public function allProductDialog(){
		$d_product = D('ProductView');
		$p = isset($_GET['p']) ? intval($_GET['p']) : 1;

		if ($_REQUEST["field"]) {
			$field = trim($_REQUEST['field']);
			
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			if	('development_time' == $field || 'listing_time' == $field) $search = is_numeric($search)?$search:strtotime($search);;
			if (!empty($field) && !empty($search)) {
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
			}
			$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
		}
		import("@.ORG.DialogListPage");

		$list = $d_product->where($where)->Page($p.',10')->select();
		// $m_stock = M('stock');
		// $warehouse = M('warehouse')->select();

		// foreach($list as $k=>$v){
		// 	$product_warehouseStr = '';
		// 	$stock_count = $m_stock->where('product_id = %d', $v['product_id'])->sum('amounts');
		// 	$list[$k]['stock_count'] = empty($stock_count) ? $list[$k]['stock_count'] = 0 : $list[$k]['stock_count'] = $stock_count;
		// 	foreach($warehouse as $item){
		// 		$product_warehouseArr[$item['name']] = $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $item['warehouse_id'])->getField('amounts');
		// 	}
		// 	foreach($product_warehouseArr as $cc=>$gg){
		// 		if(empty($gg)){
		// 			$product_warehouseStr .= $cc.':0 ';
		// 		}else{
		// 			$product_warehouseStr .= $cc.':'.$gg.' ';
		// 		}
		// 	}
		// 	$list[$k]['stock_count_detail'] = $product_warehouseStr;
		// }
		$this->list = $list;
		$count = $d_product->where($where)->count();

		$this->search_field = $_REQUEST;//搜索信息
		$Page = new Page($count,10);
		$Page->parameter = implode('&', $params);
		$this->assign('page',$Page->show());

		$this->display();
	}
	
	public function changeContent(){
		if($this->isAjax()){
			$product = D('ProductView'); // 实例化User对象
			import('@.ORG.Page');// 导入分页类
			$category = M('product_category');
			$where = array();
			$where['is_deleted'] = 0;
			$params = array();

			$p = !$_REQUEST['p']||$_REQUEST['p']<=0 ? 1 : intval($_REQUEST['p']);
			if ($_REQUEST["field"]) {
				$field = trim($_REQUEST['field']);
				
				$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
				$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
				if	('development_time' == $field || 'listing_time' == $field) $search = is_numeric($search)?$search:strtotime($search);;
				if (!empty($field) && !empty($search)) {
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
				}
				$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
			
			if(intval($_REQUEST['cid'])){
				$sub_category = getSubCategory(intval($_REQUEST['cid']), $category->select());
				foreach($sub_category as $v){
					$id_array[] = $v['category_id'];
				}
				$id_array[] = intval($_REQUEST['cid']);
				$where['category_id'] = array('in', $id_array);
			}	
			$count = $product->where($where)->count();// 查询满足要求的总记录数
			$list = $product->order('product_id desc')->where($where)->Page($p.',10')->select();
			// $warehouse = M('warehouse')->select();
			// $m_stock = M('stock');
			foreach($list as $k=>$v){
				// $product_warehouseStr = '';
				// $stock_count = M('stock')->where('product_id = %d', $v['product_id'])->sum('amounts');
				// $list[$k]['stock_count'] = empty($stock_count) ? $list[$k]['stock_count'] = 0 : $list[$k]['stock_count'] = $stock_count;
				// foreach($warehouse as $item){
				// 	$product_warehouseArr[$item['name']] = $m_stock->where('product_id = %d and warehouse_id = %d', $v['product_id'], $item['warehouse_id'])->getField('amounts');
				// }
				// foreach($product_warehouseArr as $cc=>$gg){
				// 	if(empty($gg)){
				// 		$product_warehouseStr .= $cc.':0 ';
				// 	}else{
				// 		$product_warehouseStr .= $cc.':'.$gg.' ';
				// 	}
				// }
				// $list[$k]['stock_count_detail'] = $product_warehouseStr;
				if(empty($v['category_name'])){
					$list[$k]['category_name'] = '';
				}
			}
			
			$data['list'] = $list;
			$data['p'] = $p;
			$data['count'] = $count;
			$data['total'] = $count%10 > 0 ? ceil($count/10) : $count/10;
			$this->ajaxReturn($data,"",1);
		}
	}
	
	public function category(){
		$product_category = M('product_category');			
		$category_list = $product_category->select();	
		$category_list = getSubCategory(0, $category_list, '');
		
		foreach($category_list as $key=>$value){
			$product = M('product');
			$count = $product->where('category_id = %d', $value['category_id'])->count();
			$category_list[$key]['count'] = $count;
			$category_list[$key]['list'] = $product->where('category_id = %d', $value['category_id'])->select();
		}
		$this->alert=parseAlert();
		$this->assign('category_list', $category_list);
		$this->display();
	}
	
	public function category_add(){
		if ($this->isPost()) {
			$category = D('ProductCategory');
			if(!trim($_POST['name'])){
				alert('error','请填写分类名！',$_SERVER['HTTP_REFERER']);
			}
			if ($category->create()) {
				if ($category->add()) {					
					alert('success', L('ADD_SUCCESSFUL'),$_SERVER['HTTP_REFERER']);
				} else {
					alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
				}				
			} else {	
				alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
			}
		}else{
			$category = M('product_category');			
			$category_list = $category->select();			
			$this->assign('category_list', getSubCategory(0, $category_list, ''));
			$this->display();
		}
	}
	
	public function category_delete(){
		$product_category = M('Product_category');
		$product = M('product');
		if($_POST['category_list']){
			foreach($_POST['category_list'] as $value){
				if($product->where('category_id = %d',$value)->select()){
					$name = $product_category->where('category_id = %d',$value)->getField('name');
					$this->ajaxReturn('',L('UNDER_THE_CATEGORY_OF_PRODUCTS',array($name)),0);
				}
				if($product_category->where('parent_id = %d',$value)->select()){
					$name = $product_category->where('category_id = %d',$value)->getField('name');
					$this->ajaxReturn('',L('UNDER_THE_CATEGORY_OF_CHILD_CATEGORIES',array($name)),0);
				}
			}
			if($product_category->where('category_id in (%s)', join($_POST['category_list'],','))->delete()){
				$this->ajaxReturn('',L('CATEGORY_WAS_REMOVED_SUCCESSFULLY'),1);
			}else{
				$this->ajaxReturn('',L('CATEGORY_WAS_REMOVED_FAILED'),0);
			}
		}elseif($_GET['id']){
			if($product->where('category_id = %d',$_GET['id'])->select()){
				$name = $product_category->where('category_id = %d',$value)->getField('name');
				alert('error', L('UNDER_THE_CATEGORY_OF_PRODUCTS',array($name)),$_SERVER['HTTP_REFERER']);
			}
			if($product_category->where('parent_id = %d',$value)->select()){
                $name = $product_category->where('category_id = %d',$value)->getField('name');
                alert('error', L('UNDER_THE_CATEGORY_OF_CHILD_CATEGORIES',array($name)),$_SERVER['HTTP_REFERER']);
            }
            if($product_category->where('category_id = %d',$_GET['id'])->delete()){
				alert('success', L('CATEGORY_WAS_REMOVED_SUCCESSFULLY') ,$_SERVER['HTTP_REFERER']);
			}else{
				alert('error', L('CATEGORY_WAS_REMOVED_FAILED') ,$_SERVER['HTTP_REFERER']);
			}
		}else{
			alert('error',L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}	
	}
	
	//编辑产品分类信息
	public function category_edit(){
		if($_GET['id']){
			$product_category = M('product_category');			
			$category_list = $product_category->select();	
			$this->assign('category_list', getSubCategory(0, $category_list, ''));
			$product_category = M('product_category');
			$categoryList = $product_category->select();	//读取分类列表 加载下拉框
			foreach($categoryList as $key=>$value){
				if($value['category_id'] == $_GET['id']){
					unset($categoryList[$key]);
				}
			}
			
			$this->category_list = $categoryList;
			$this->temp =$product_category->where('category_id = %s', $_GET['id'])->find();
			
			$this->display();
		}elseif($_POST['category_id']){
			$product_category = M('product_category');
			if(!trim($_POST['name'])){
				alert('error','请填写分类名！',$_SERVER['HTTP_REFERER']);
			}	
			$product_category -> create();
			if($product_category->save()){
				alert('success',L('MODIFY_THE_CATEGORY_INFORMATION_SUCCESSFULLY'),$_SERVER['HTTP_REFERER']);
			}else{
				alert('error',L('THERE_IS_NO_DATA_CHANGE_MODIFY_THE_CATEGORY_INFORMATION_FAILURE'),$_SERVER['HTTP_REFERER']);
			}
		}else{
            alert('error',L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
		}
	}
	
	//产品销量统计
	public function analytics(){
		$m_product = M('product');

		if(intval($_GET['role'])){
			$where_role = array('eq', intval($_GET['role']));
		}else{
			if(intval($_GET['department'])){
				$department_id = intval($_GET['department']);
				foreach(getRoleByDepartmentId($department_id, true) as $k=>$v){
					$role_id_array[] = $v['role_id'];
				}
			}else{
				$role_array = getPerByAction(MODULE_NAME,ACTION_NAME,false);
				$role_id_array = $role_array;
			}
			$where_role = array('in', implode(',', $role_id_array));
		}
		if($_GET['category_id']){
			$idArray = Array();
			$categoryList = getSubCategory($_GET['category_id'], $category_list, '');
			foreach($categoryList as $value){
				$idArray[] = $value['category_id'];
			}	
			$idList =empty($idArray)?$_GET['category_id']:$_GET['category_id'] . ',' . implode(',', $idArray);
			$where['category_id'] = array('in',$idList);
		}				
		//时间段搜索
		if($_GET['select_type'] == 1){
			$start_time = strtotime(date('Y-m-01 00:00:00'));
			$end_time = strtotime(date('Y-m-d H:i:s'));
		}elseif($_GET['select_type'] == 2){
			$month=date('m');
			if($month==1 || $month==2 ||$month==3){
				$start_time = strtotime(date('Y-01-01 00:00:00'));
				$end_time = strtotime(date("Y-03-31 23:59:59"));
			}elseif($month==4 || $month==5 ||$month==6){
				$start_time = strtotime(date('Y-04-01 00:00:00'));
				$end_time = strtotime(date("Y-06-30 23:59:59"));
			}elseif($month==7 || $month==8 ||$month==9){
				$start_time = strtotime(date('Y-07-01 00:00:00'));
				$end_time = strtotime(date("Y-09-30 23:59:59"));
			}else{
				$start_time = strtotime(date('Y-10-01 00:00:00'));
				$end_time = strtotime(date("Y-12-31 23:59:59"));
			}
		}elseif($_GET['select_type'] == 3){
			$start_time = strtotime(date('Y-01-01 00:00:00'));
			$end_time = time();
		}elseif($_GET['select_type'] == 4){
			if($_GET['start_time']){
				$start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
			}
			$end_time = $_GET['end_time'] ?  strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time()));
		}elseif($_GET['select_type'] == 5){
			$year = date('Y')-1;
			$start_time = strtotime(date($year.'-01-01 00:00:00'));
			$end_time = strtotime(date('Y-01-01 00:00:00'));
		}else{
			if($_GET['start_time']){
				$start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
			}
			$end_time = $_GET['end_time'] ? strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time()));
		}
		/* if($_GET['start_time']) $start_time = strtotime(date('Y-m-d',strtotime($_GET['start_time'])));
		$end_time = $_GET['end_time'] ?  strtotime(date('Y-m-d 23:59:59',strtotime($_GET['end_time']))) : strtotime(date('Y-m-d 23:59:59',time())); */
		//统计报表
		$products = M('product')->where($where)->select();	
		if($start_time){
			$create_time= array(array('elt',$end_time),array('egt',$start_time), 'and');
		}else{
			$create_time = array('elt',$end_time);
		}
		$product_count = array();
		$d_sales_product = D('SalesProductView');
		foreach($products as $v){
			$sales_list = $d_sales_product->where(array('is_checked'=>1, 'type'=>0, 'product_id'=>$v['product_id'],'sales_time'=>$create_time, 'creator_role_id'=>$where_role))->select();	
			$product = array();
			
			$product['name'] = $v['name']; //产品名称
			$product['standard'] = $v['standard']; //规格
			$product['product_id'] = $v['product_id']; //产品id
			$product['count_sales_price'] = 0; //毛利润
			$product['sales_num'] = 0;
			foreach($sales_list as $val){
				$product['sales_num'] += $val['amount']; //销量
				$product['count_sales_price'] += ($val['amount'] * $val['unit_price'] * ($val['discount_rate']/100));  
			}
			$product['count_cost_price'] = $v['cost_price'] * $product['sales_num']; //成本
			$product_count[] = $product;
			$product_total['sales_sales'] += $product['sales_num'];
			$product_total['sales_price'] += $product['count_sales_price'];
			$product_total['sales_cost'] += $product['count_cost_price'];
		}
	
		//销量TOP20、销售额统计
		$sales_top = $product_count;
		foreach($sales_top as $key=>$row){
			$sales[$key] = $row['sales_num']; 
		}
		array_multisort($sales, SORT_DESC, $sales_top);
		foreach($sales_top as $k=>$v){
			if($k>19){
				unset($sales_top[$k]);
			}
		}
		$this->sales_top = $sales_top;	
		
		$this->product_count = $product_count;
	
		//员工列表
		$idArray = getPerByAction(MODULE_NAME,ACTION_NAME,false);
		$roleList = array();
		foreach($idArray as $roleId){				
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		
		//部门列表
		$url = getCheckUrlByAction(MODULE_NAME,ACTION_NAME);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		//$departmentList = M('roleDepartment')->select();
		$this->assign('departmentList', $departmentList);
	
		$this->product_total = $product_total;
		$this->product_count = $product_count;
		
		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->alert = parseAlert();
		$this->display();
	}
	
	public function getProductByBusiness(){
		$business_id = $_GET['id'];
		if($business_id){
			$r_business_product = M('rBusinessProduct');
			$m_product = M('product');
			$business_product = $r_business_product->where('business_id = %d', $business_id)->select();
			foreach($business_product as $k=>$v){
				$business_product[$k]['product_name'] = $m_product->where('product_id = %d', $v['product_id'])->getField('name');
				$business_product[$k]['standard'] = $m_product->where('product_id = %d', $v['product_id'])->getField('standard');
			}
			$this->ajaxReturn(array('product'=>$business_product,'total_count'=>sizeOf($business_product)),'已获取与商机有关产品！',1);
		}
	}
	
	//删除图片
	public function delImg(){
		$images_id = $_GET['images_id'];
		if($images_id){
			$m_product_images = M('productImages');
			$images_path = $m_product_images->where('images_id = %d', $images_id)->getField('path');
			$result = $m_product_images->where('images_id = %d', $images_id)->delete();
			if($result){
				@unlink($images_path);
				$this->ajaxReturn('','',1);
			}
		}else{
			$this->ajaxReturn('',L('PARAMETER_ERROR'),0);
		}
	}
	
	//图片排序
	public function sortImg(){
		$images_files = $_POST['images_arr'];
		$imagesArr = explode(',', $images_files);
		if($imagesArr){
			$m_product_images = M('productImages');
			//拖动后的listorder
			$original_listorder = $m_product_images->where('images_id in (%s)',$images_files)->getField('listorder',true);
			sort($original_listorder);//按顺序排列
			
			//交换顺序
			foreach($imagesArr as $k=>$v){
				$m_product_images->where('images_id = %d',$v)->setField('listorder',$original_listorder[$k]);
			}
			$this->ajaxReturn('success', '排序成功！', 1);
		}
	}
	
	//导出
	public function excelExport($productList=false){
		C('OUTPUT_ENCODE', false);
		import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");    
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Product");    
		$objProps->setSubject("mxcrm Product Data");    
		$objProps->setDescription("mxcrm Product Data");    
		$objProps->setKeywords("mxcrm Product");    
		$objProps->setCategory("mxcrm");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
        $ascii = 65;
        $cv = '';
        $field_list = M('Fields')->where('model = \'product\'')->order('order_id')->select();
        foreach($field_list as $field){
        	if($field['form_type'] == 'address'){
				for($a=0;$a<=4;$a++){
					$address = array('所在省','所在市','所在县/区','街道信息');
					$objActSheet->setCellValue($cv.chr($ascii).'1', $address[$a]);
					$ascii++;
					if($ascii == 91){
						$ascii = 66;
						$cv .= chr(strlen($cv)+65);
					}
				}
				$ascii--;
			}else{
	            $objActSheet->setCellValue($cv.chr($ascii).'1', $field['name']);
	            $ascii++;
	            if($ascii == 91){
	                $ascii = 65;
	                $cv = chr(strlen($cv)+65);
	            }
	        }
        }
		if(is_array($productList)){
			$list = $productList;
		}else{
			$list = D('ProductView')->select();
		}
		$i = 1;
		foreach ($list as $k => $v) {
            $data = m('ProductData')->where("product_id = $v[product_id]")->find();
            if(!empty($data)){
                $v = $v+$data;
            }
			$i++;
            $ascii = 65;
            $cv = '';
            foreach($field_list as $field){
                if($field['form_type'] == 'datetime'){
					if($v[$field['field']] == 0 || strlen($v[$field['field']]) != 10){
						$objActSheet->setCellValue($cv.chr($ascii).$i, '');
					}else{
						$objActSheet->setCellValue($cv.chr($ascii).$i, date('Y-m-d H:i',$v[$field['field']]));
					} 
                }elseif($field['form_type'] == 'number' || $field['form_type'] == 'floatnumber' || $field['form_type'] == 'phone' || $field['form_type'] == 'mobile' || ($field['form_type'] == 'text' && is_numeric($v[$field['field']]))){
					//防止使用科学计数法，在数据前加空格
					$objActSheet->setCellValue($cv.chr($ascii).$i, ' '.$v[$field['field']]);
				}elseif($field['field'] == 'category_id'){
					$m_category = M('ProductCategory');
					$category = $m_category->where('category_id = %d',$v['category_id'])->find();
					$objActSheet->setCellValue($cv.chr($ascii).$i, $category['name']);
				}elseif($field['form_type'] == 'address'){
					$temp = str_replace('=', '', $v[$field['field']]);
					$address = $temp;
					$arr_address = explode(chr(10),$address);
					for($j=0;$j<4;$j++){
						$objActSheet->setCellValue($cv.chr($ascii).$i, $arr_address[$j]);
						$ascii++;
						if($ascii == 91){
							$ascii = 65;
							$cv .= chr(strlen($cv)+65);
						}
					}
					$ascii--;
				}else{
                    $objActSheet->setCellValue($cv.chr($ascii).$i, $v[$field['field']]);
                }
                $ascii++;
                if($ascii == 91){
                    $ascii = 65;
                    $cv = chr(strlen($cv)+65);
                }
            }
			
		}
		$current_page = intval($_GET['current_page']);
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		//ob_end_clean();
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_product_".date('Y-m-d',mktime())."_".$current_page.".xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output');
		session('export_status', 0);
	}
	public function getCurrentStatus(){
		$this->ajaxReturn(intval(session('export_status')), 'success', 1);
		
	}
	public function excelImport(){
		if($this->isPost()){
			if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null) {
				import('@.ORG.UploadFile');
				$upload = new UploadFile();
				$upload->maxSize = 20000000;
				$upload->allowExts  = array('xls');
				$dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
				if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
					alert('error', L('ATTACHMENTS_TO_UPLOAD_DIRECTORY_CANNOT_WRITE'), $_SERVER['HTTP_REFERER']);
				}
				$upload->savePath = $dirname;
				if(!$upload->upload()) {
					alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
				}else{
					$info =  $upload->getUploadFileInfo();
				}
			}
            
			if(is_array($info[0]) && !empty($info[0])){
				$savepath = $dirname . $info[0]['savename'];
				if($savepath){
					$this->ajaxReturn($savepath,'success',1);
				}else{
					$this->ajaxReturn(0,'error',0);
				}
			}else{
				alert('error', L('UPLOAD_FAILED'), $_SERVER['HTTP_REFERER']);
			}
		}else{
			$this->display();
		}
	}
	public function excelImportact(){
		$m_product = D('product');
		$m_product_data = D('ProductData');
		$savePath = $_GET['path'];
		import("ORG.PHPExcel.PHPExcel");
		$PHPExcel = new PHPExcel();
		$PHPReader = new PHPExcel_Reader_Excel2007();
		if(!$PHPReader->canRead($savePath)){
			$PHPReader = new PHPExcel_Reader_Excel5();
		}
		$PHPExcel = $PHPReader->load($savePath);
		$currentSheet = $PHPExcel->getSheet(0);
		$allRow = $currentSheet->getHighestRow();

		$field_list = M('Fields')->where('model = \'product\'')->order('order_id')->select();
		
		$currentRow = intval($_GET['num']);
		if($currentRow+99 <=$allRow){
			$rows_excal = $currentRow+100;
		}else{
			$rows_excal = $allRow;
		}
		$message = array();
		for($currentRow;$currentRow <= $rows_excal;$currentRow++){

			$data = array();
			$data['creator_role_id'] = session('role_id');
			$data['create_time'] = time();
			$data['update_time'] = time();
			$ascii = 65;
			$cv = '';
			foreach($field_list as $field){
				$info = trim((String)$currentSheet->getCell($cv.chr($ascii).$currentRow)->getValue());
				if ($field['is_main'] == 1){
					if($field['field'] == 'category_id'){
						$m_product_category = M('ProductCategory');
						$product_category = $m_product_category->where('name like "%s"', $info)->find();
						$info = $product_category['category_id'] ? $product_category['category_id'] : 0;
					}
					$data[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(PHPExcel_Shared_Date::ExcelToPHP($info))-8*60*60 : trim($info);
				}else{
					$data_date[$field['field']] = ($field['form_type'] == 'datetime' && $info != null) ? intval(PHPExcel_Shared_Date::ExcelToPHP($info))-8*60*60 : trim($info);
				}
				
				$ascii++;
				if($ascii == 91){
					$ascii = 65;
					$cv = chr(strlen($cv)+65);
				}
			}

			if ($m_product->create($data) && $m_product_data->create($data_date)) {
			
				$product_id = $m_product->add();
				$m_product_data->product_id = $product_id;
				$m_product_data->add();
				//产品增加至仓库
				$stock = M('Stock');
				$warehouse_ids = M('Warehouse')->getField('warehouse_id',true);
				if($warehouse_ids){
					foreach($warehouse_ids as $vo){
						$stock_data['product_id'] = $product_id;
						$stock_data['warehouse_id'] = $vo;
						$stock_data['amounts'] = 0;
						$stock_data['last_change_time'] = time();
						$stock ->add($stock_data);
					}
				}
			}else{
				//$error_message .= L('LINE ERROR',array($currentRow,$m_product->getError().$m_product_data->getError()));	
				$error_message = $m_product->getError().$m_product_data->getError();
			}
			$temp['error_message'] = $error_message;
			$temp['no'] = $currentRow;
			$message[] = $temp;
		}
		$return['allrow'] = $allRow;
		$return['message'] = $message;
		if($return){
			$this->ajaxReturn($return,'success',1);
		}else{
			$this->ajaxReturn('','error',0);
		}  
	}
	
	public function excelImportDownload(){
		C('OUTPUT_ENCODE', false);
        import("ORG.PHPExcel.PHPExcel");
		$objPHPExcel = new PHPExcel();    
		$objProps = $objPHPExcel->getProperties();    
		$objProps->setCreator("mxcrm");
		$objProps->setLastModifiedBy("mxcrm");    
		$objProps->setTitle("mxcrm Product");    
		$objProps->setSubject("mxcrm Product Data");    
		$objProps->setDescription("mxcrm Product Data");    
		$objProps->setKeywords("mxcrm Product Data");    
		$objProps->setCategory("mxcrm");
		$objPHPExcel->setActiveSheetIndex(0);     
		$objActSheet = $objPHPExcel->getActiveSheet(); 
		   
		$objActSheet->setTitle('Sheet1');
        $ascii = 65;
        $cv = '';
        $field_list = M('Fields')->where('model = \'product\' ')->order('order_id')->select();
        foreach($field_list as $field){
            // $objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
            // $ascii++;
            // if($ascii == 91){
            //     $ascii = 65;
            //     $cv = chr(strlen($cv)+65);
            // }

            if($field['form_type'] == 'address'){
				for($i=0;$i<4;$i++){
					$address = array('所在省','所在市','所在县','街道信息');
					$objActSheet->setCellValue($cv.chr($ascii).'2',$address[$i]);
					$ascii++;
					$temp = $cv;
					if($ascii == 91){
						$ascii = 65;
						if($cv){
							$cv = chr(ord($cv)+1);
						}else{
							$cv = 'A';
						}
					}
				}
			}else{
				if($field['form_type'] == 'box' || $field['field'] == 'category_id'){
					if($field['field'] == 'category_id'){
						$category_list = M('ProductCategory')->getField('name',true);
						$select_value = implode(',',$category_list);
					}else{
						eval('$setting='.$field['setting'].';');
						$select_value = implode(',',$setting['data']);
					}
					//数据有效性   start
					$objValidation = $objActSheet->getCell($cv.chr($ascii).'3')->getDataValidation();
					$objValidation -> setType(PHPExcel_Cell_DataValidation::TYPE_LIST)  
			           -> setErrorStyle(PHPExcel_Cell_DataValidation::STYLE_INFORMATION)  
			           -> setAllowBlank(false)  
			           -> setShowInputMessage(true)  
			           -> setShowErrorMessage(true)  
			           -> setShowDropDown(true)  
			           -> setErrorTitle('输入的值有误')  
			           -> setError('您输入的值不在下拉框列表内.')  
			           -> setPromptTitle('--请选择--')  
			           -> setFormula1('"'.$select_value.'"');
			        //数据有效性  end
				}
				$objActSheet->setCellValue($cv.chr($ascii).'2', $field['name']);
				$ascii++;
				$temp = $cv;
				if($ascii == 91){
					$ascii = 65;
					if($cv){
						$cv = chr(ord($cv)+1);
					}else{
						$cv = 'A';
					}
				}
			}
        }
        $objActSheet->mergeCells('A1:'.$cv.chr($ascii).'1');
		$objActSheet->getRowDimension('1')->setRowHeight(80);
		$objActSheet->getStyle('A1')->getFont()->getColor()->setARGB('FFFF0000');
		 $objActSheet->getStyle('A1')->getAlignment()->setWrapText(true);
        $content = L('ADRESS');
        $objActSheet->setCellValue('A1', $content);
        
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
		header("Content-Type: application/vnd.ms-excel;");
        header("Content-Disposition:attachment;filename=mxcrm_product.xls");
        header("Pragma:no-cache");
        header("Expires:0");
        $objWriter->save('php://output'); 
    }
	
	//产品树 弹出框
	public function mutildialog(){
		$where = array();
		if ($_REQUEST["field"]) {
			if (trim($_REQUEST['field']) == "all") {
				/* $field = is_numeric(trim($_REQUEST['search'])) ? 'product.name|cost_price|sales_price|link|pre_sale_count|stock_count' : 'product.name|link|development_team'; */
			} else {
				$field = trim($_REQUEST['field']);
			}
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			
			if ($this->_request('state')){
				$state = $this->_request('state', 'trim');
				$address_where[] = '%'.$state.'%';

				if($this->_request('city')){
					$city = $this->_request('city', 'trim');
					$address_where[] = '%'.$city.'%';

					if($this->_request('area')){
						$area = $this->_request('area', 'trim');
						$address_where[] = '%'.$this->_request('area', 'trim').'%';
					}
				}
				if($search) $address_where[] = '%'.$search.'%';
				//$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));
				if($condition == 'not_contain'){
					$where[$field] = array('notlike', $address_where, 'OR');
				}else{
					$where[$field] = array('like', $address_where, 'AND');
				}
			}elseif(!empty($field)){
				$field_date = M('Fields')->where('(is_main=1 and model="product" and form_type="datetime") or(is_main=1 and model="" and form_type="datetime")')->select();
				foreach($field_date as $v){
					if	($field == $v['field']) $search = is_numeric($search)?$search:strtotime($search);
				}
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
				//$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
		}
		$where['is_deleted'] = 0;
		$product = D('ProductView'); // 实例化对象
		$category = D('ProductCategory'); // 实例化对象
		$list = $product->order('product_id desc')->where($where)->limit(10)->select();
		$count = $product->where($where)->count();
		$category_list = $category->select();
		$this->treecode = getSubCategoryTreeCode(0,1);
		$this->field_list = getMainFields('product');
		$this->categoryList = getSubCategory(0, $category_list, ''); //类别选项
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		$this->assign('list',$list);// 赋值数据集
		$this->display(); // 输出模板
	}
	
	//产品树 弹出框
	public function mutildialog_business(){
		if ($_REQUEST["field"]) {
			if (trim($_REQUEST['field']) == "all") {
				/* $field = is_numeric(trim($_REQUEST['search'])) ? 'product.name|cost_price|sales_price|link|pre_sale_count|stock_count' : 'product.name|link|development_team'; */
			} else {
				$field = trim($_REQUEST['field']);
			}
			$search = empty($_REQUEST['search']) ? '' : trim($_REQUEST['search']);
			$condition = empty($_REQUEST['condition']) ? 'is' : trim($_REQUEST['condition']);
			
			if ($this->_request('state')){
				$state = $this->_request('state', 'trim');
				$address_where[] = '%'.$state.'%';

				if($this->_request('city')){
					$city = $this->_request('city', 'trim');
					$address_where[] = '%'.$city.'%';

					if($this->_request('area')){
						$area = $this->_request('area', 'trim');
						$address_where[] = '%'.$this->_request('area', 'trim').'%';
					}
				}
				if($search) $address_where[] = '%'.$search.'%';
				//$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'state='.$this->_request('state','trim'), 'city='.$this->_request('city','trim'),'area='.$this->_request('area','trim'),'search='.$this->_request('search','trim'));
				if($condition == 'not_contain'){
					$where[$field] = array('notlike', $address_where, 'OR');
				}else{
					$where[$field] = array('like', $address_where, 'AND');
				}
			}elseif(!empty($field)){
				$field_date = M('Fields')->where('(is_main=1 and model="product" and form_type="datetime") or(is_main=1 and model="" and form_type="datetime")')->select();
				foreach($field_date as $v){
					if	($field == $v['field']) $search = is_numeric($search)?$search:strtotime($search);
				}
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
				//$params = array('field='.trim($_REQUEST['field']), 'condition='.$condition, 'search='.$_REQUEST["search"]);
			}
		}
		$customer_id = trim($_GET['customer_id']);
		if(!empty($_GET['business_id'])){
			$product_list = D('BusinessProductView')->where('r_business_product.business_id = %d', $_GET['business_id'])->select();
			$this->product_list = $product_list;
		}
		$product = D('ProductView'); // 实例化对象
		$category = D('ProductCategory'); // 实例化对象
		$where = array();
		$list = $product->order('product_id desc')->where($where)->limit(10)->select();
		$count = $product->where($where)->count();
		$category_list = $category->select();
		$this->treecode = getSubCategoryTreeCode(0,1);
		$this->field_list = getMainFields('product');
		$this->categoryList = getSubCategory(0, $category_list, ''); //类别选项
		$this->total = $count%10 > 0 ? ceil($count/10) : $count/10;
		$this->count_num = $count;
		$this->business_id = trim($_GET['business_id']);
		$this->assign('list',$list);// 赋值数据集
		$this->display(); // 输出模板
	}
	/**
	*第一层产品
	*
	**/
	public function mutildialog_product(){
		$m_product = M('Product');
		if($_GET['business_id']){
			$business_id = intval($_GET['business_id']);
			$business = M('Business')->where('business_id = %d', $business_id)->find();
			$product_list = M('rBusinessProduct')->where('business_id = %d', $business_id)->select();
			foreach ($product_list as $k => $v) {
				$product_list[$k]['product_name'] = $m_product->where('product_id = %d', $v['product_id'])->getField('name');
			}
			$business['product'] = $product_list;
			$this->now_rows = count($product_list);
			//可能性
			$possibility_list = array();
			for ($x=1; $x<=10; $x++) {
				$possibility_list[] = $x*10;
			}
			$this->possibility_list = $possibility_list;
			//商机状态组
			$business['business_type_name'] = M('BusinessType')->where(array('id'=>$business['status_type_id']))->getField('name');
			$business['status_name'] = M('BusinessStatus')->where(array('type_id'=>$business['status_type_id'],'status_id'=>$business['status_id']))->getField('name');

			$this->business = $business;
			$this->display();
		}elseif($order_id = $_GET['order_id']){
			$order_info = M('Order')->where('order_id = %d',$order_id)->find();
			$product_list = M('ROrderProduct')->where('order_id = %d',$order_id)->select();
			foreach ($product_list as $k => $v) {
				$product_list[$k]['product_name'] = $m_product->where('product_id = %d', $v['product_id'])->getField('name');
			}
			$order_info['product'] = $product_list;
			$this->now_rows = count($product_list);
			$this->order_info = $order_info;
			$this->display('mutildialog_product_order');
		}else{
			$customer_id = intval($_GET['customer_id']);
			//可能性
			$possibility_list = array();
			for ($x=1; $x<=10; $x++) {
				$possibility_list[] = $x*10;
			}
			//商机编号
			$business = array();
			$m_config = M('Config');
			$business_custom = $m_config->where('name = "business_custom"')->getField('value');
			// $business_max_id = $m_config->where(array('name'=>'business_code'))->getField('value');
			$business_max_id = M('Business')->max('business_id');
			$business_max_code = str_pad($business_max_id+1,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
			$business['business_custom'] = $business_custom;
			$business['code'] = date('Ymd').'-'.$business_max_code;
			
			//客户名称
			$business['customer_name'] = M('Customer')->where('customer_id = %d',$customer_id)->getField('name');
			//商机状态组
			$this->type_list = M('BusinessType')->select();
			$this->status_list = M('BusinessStatus')->where(array('type_id'=>1))->order('order_id asc')->select();

			$this->business = $business;
			$this->possibility_list = $possibility_list;
			$this->customer_id = $customer_id;
			$this->display();
		}
	}

	public function mutildialog_product_contract(){
		$m_r_contract_sales = M('r_contract_sales');
		$m_sales = M('sales');
		if($contract_id = $_GET['contract_id']){
			$sales_id = $m_r_contract_sales->where('contract_id = %d', $contract_id)->getField('sales_id');
			$sales = $m_sales->where('sales_id = %d', $sales_id)->find();
			$product_list = M('sales_product')->where('sales_id = %d', $sales_id)->select();
			foreach ($product_list as $k => $v) {
				$product_list[$k]['product_name'] = M('Product')->where('product_id = %d', $v['product_id'])->getField('name');
			}
			$sales['product'] = $product_list;

			//dump($business);die;
			$this->sales = $sales;
			$this->sales_id = $sales_id;
			//$this->business_id = $business_id;
		}
		$this->display();
	} 
	/**
	 * 首页获取产品销量和销售额统计
	 * @ level 0:自己的数据  1:自己和下属的数据
	 **/
	public function getmonthlysales(){
		$m_product = M('product');
		$m_sales = M('sales');
		$m_sales_product = M('salesProduct');
		$dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);
		$id = intval($_GET['id']);
		foreach($widget['dashboard'] as $k=>$v){
			if($v['widget'] == 'Productmonthlysales' && $v['id'] == $id){
				if($v['level'] == '1'){
					$where['creator_role_id'] = array('in',getSubRoleId());
				}else{
					$where['creator_role_id'] = array('eq', session('role_id'));
				}
			}
		}
		
		$total_amount = array();
		$total_price = array();
		$year = date('Y');
		$moon = 1;
		$where['type'] = array('eq', 0);//销售
		$where['is_checked'] = array('eq', 1);
		$where['status'] = array(array('eq',97), array('eq',98),'or');//未出库或已出库
		while ($moon <= 12){
			if($moon == 12) {
				$where['sales_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
			} else {
				$where['sales_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
			}
			
			$sales = $m_sales->where($where)->select();//销售数组
			$single_price = 0;//单个商品月销售总额
			$single_amounts = 0;//单个商品月销售量
			foreach($sales as $v){
				//为了防止sales表和sales_product表中的数据对应不上,避免统计数值不准确,所以使用sales_product表中的数据来统计
				$sales_product = $m_sales_product->where('sales_id = %d', $v['sales_id'])->select();
				foreach($sales_product as $val){
					$single_amounts += $val['amount'];
					$all_price = $val['unit_price'] * $val['amount'];//总额
					// $after_discount = $all_price - ($all_price * $val['discount_rate']/100);  //折扣后金额
					// $after_tax = $after_discount + ($after_discount * $val['tax_rate']/100); //税后金额
					$single_price += $all_price;
				}
				// $single_price = $single_price - $v['discount_price'];//减去整单的折扣额
				$single_price = $single_price*(100-$v['final_discount_rate'])/100;
			}
			
			$total_amount[] = $single_amounts;
			$total_price[] = $single_price;
			$moon ++;
		}
		$total_sales = array('amount'=>$total_amount, 'price'=>$total_price);
		$this->ajaxReturn($total_sales,'success',1);
	}
	
	/**
	 * 首页获取最高销量的产品统计
	 * @ level 0:自己的数据  1:自己和下属的数据
	 **/
	public function getmonthlyamount(){
		$m_product = M('product');
		$m_sales = M('sales');
		$m_sales_product = M('salesProduct');
		$dashboard = M('user')->where('user_id = %d', session('user_id'))->getField('dashboard');
		$widget = unserialize($dashboard);
		$id = intval($_GET['id']);
		foreach($widget['dashboard'] as $k=>$v){
			if($v['widget'] == 'Productmonthlyamount' && $v['id'] == $id){
				if($v['level'] == '1'){
					$where['creator_role_id'] = array('in',getSubRoleId());
				}else{
					$where['creator_role_id'] = array('eq', session('role_id'));
				}
			}
		}
		
		$productData = array();
		$year = date('Y');
		$moon = 1;
		$where['type'] = array('eq', 0);//销售
		$where['is_checked'] = array('eq', 1);
		$where['status'] = array(array('eq',97), array('eq',98),'or');//未出库或已出库
		while ($moon <= 12){
			if($moon == 12) {
				$where['sales_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime(($year+1).'-1-1')), 'and');
			} else {
				$where['sales_time'] = array(array('egt', strtotime($year.'-'.$moon.'-1')), array('lt', strtotime($year.'-'.($moon+1).'-1')), 'and');
			}
			
			$salesIdArr = $m_sales->where($where)->getField('sales_id',true);//销售数组
			if(is_array($salesIdArr)){
				$productArr = $m_sales_product->field('sum(amount) as amount,product_id')->where(array('sales_id'=>array('in', $salesIdArr)))->group('product_id')->order('amount desc')->find();
				$product_name = $m_product->where('product_id = %d', $productArr['product_id'])->getField('name');
				$product_amount = empty($productArr['amount']) ? 0 : $productArr['amount'];
			}else{
				$product_name = '无';
				$product_amount = 0;
			}
			
			$productData[] = array($product_name,intval($product_amount));//月度最高销售量产品
			$moon ++;
		}
		$this->ajaxReturn($productData,'success',1);
	}
	//财务统计高级搜索
	public function advance_search(){
		$module_name = trim($_GET['module_name']);
		$action_name = trim($_GET['action_name']);
		$idArray = getPerByAction($module_name,$action_name,false);
		//$idArray = getSubRoleId(true, 1);
		$roleList = array();
		foreach($idArray as $roleId){
			$roleList[$roleId] = getUserByRoleId($roleId);
		}
		$this->roleList = $roleList;
		$url = getCheckUrlByAction($module_name,$action_name);
		$per_type =  M('Permission') -> where('position_id = %d and url = "%s"', session('position_id'), $url)->getField('type');
		if($per_type == 2 || session('?admin')){
			$departmentList = M('roleDepartment')->select();
		}else{
			$departmentList = M('roleDepartment')->where('department_id =%d',session('department_id'))->select();
		}
		$this->assign('departmentList', $departmentList);
		$this->type_id = intval($_GET['type_id']);
		$this->content_id = intval($_GET['content_id']);
		$this->display();
	}

	//高级搜索获取产品类别
	public function categoryList(){
		$category = M('product_category')->select();
		$category_list = getSubCategory(0, $category, '');
		$this->ajaxReturn($category_list,'',1);
	}



	public function changeOnSale()
    {
        $product =D('Product');
        if($product->editData()){
            $data=[
                'status'=>1,
            ];
        }else{
            $data=[
                'status'=>0,
                'info'=>$product->getError()
            ];
        }
        $this->ajaxReturn($data);
    }
}