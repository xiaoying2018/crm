<?php
/**
 *产品相关
 **/
class ProductVue extends Action {
	/**
	 *用于判断权限
	 *@permission 无限制
	 *@allow 登录用户可访问
	 *@other 其他根据系统设置
	 **/
	public function _initialize(){
		$action = array(
			'permission'=>array(),
			'allow'=>array('category')
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
	 * 产品列表
	 * @param 
	 * @author 
	 * @return 
	 */
	public function index() {
		if ($this->isPost()) {
			// getDateTime('product');		
			$d_v_product = D('ProductView');
			$m_product_category = M('ProductCategory');
			if (isset($_POST['search'])) {
				$where['name'] = array('like','%'.trim($_POST['search']).'%');
			}
			$p = isset($_POST['p']) ? intval($_POST['p']) : 1 ;
			if ($_POST['category_id']) {
				$idArray = Array();
				$categoryList = getSubCategory($_POST['category_id'],$m_product_category->select(),'');
				foreach ($categoryList as $value) {
					$idArray[] = $value['category_id'];
				}
				$idList = empty($idArray) ? $_POST['category_id'] : $_POST['category_id'].','.implode(',', $idArray);
				$where['category_id'] = array('in',$idList);
			}
			//商机下的产品
			if ($_POST['business_id']) {
				$product_ids =  M('RBusinessProduct')->where('business_id = %d',intval($_POST['business_id']))->getField('product_id', true);
				$where['product_id'] = array('in',$product_ids);
			}
			if ($_POST['contract_id']) {		
				$sales_id = M('rContractSales')->where(array('contract_id'=>intval($_POST['contract_id']),'sales_type'=>0))->getField('sales_id');
				$product_ids = M('salesProduct')->where('sales_id = %d',$sales_id)->getField('product_id',true);
				$where['product_id'] = array('in',$product_ids);
			}
			$list = $d_v_product->where($where)->order('create_time desc')->page($p.',10')->field('name,product_id,standard,suggested_price,create_time')->select();

			$m_product_images = M('product_images');
			foreach ($list as $k=>$v) {
				$product_images_info = $m_product_images->where(array('product_id'=>$v['product_id'],'is_main'=>1))->find();
				if ($product_images_info) {
					$list[$k]['main_path'] = $product_images_info['thumb_path'];
				} else {
					$list[$k]['main_path'] = '';
				}
				//获取操作权限
				$list[$k]['permission'] = getpermission(MODULE_NAME);
			}
			$list = empty($list) ? array() : $list;
			$count = $d_v_product->where($where)->count();
			$page = ceil($count/10);

			$category_arr = $m_product_category->field('category_id,parent_id,name')->select();
			foreach ($category_arr as $k=>$v) {
				$category_arr[$k]['id'] = $v['category_id'];
				unset($category_arr[$k]['category_id']);
			}
			//二维数组递归遍历多维数组实现无限分类
			$category_list = build_tree($category_arr,0);
			$category_list = empty($category_list) ? array() : $category_list ;			
			$data['category_list'] = $category_list;
			$data['list'] = $list;
			$data['page'] = $page;
			$data['info'] = 'success'; 
			$data['status'] = 1; 			
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}

	/**
	 * 产品详情
	 * @param 
	 * @author 
	 * @return
	 */
	public function view() {
		if ($this->isPost()) {
			$product_id =  isset($_POST['id']) ? intval($_POST['id']) : 0;
			if (empty($product_id)) {
				$this->ajaxReturn('','参数错误！',0);
			}
			$product_info = D('ProductView')->where('product.product_id = %d', $product_id)->find();
			//取得字段列表
			$field_list = M('Fields')->where('model = "product"')->order('order_id')->select();
			//查询固定信息
			$product_info['create'] = D('RoleView')->where('role.role_id = %d', $product_info['creator_role_id'])->find();
			$i = 0;
			foreach ($field_list as $k=>$v) {
				$data_list[$i]['field'] = trim($v['field']);
				$data_list[$i]['name'] = trim($v['name']);
				if ($v['setting']) { 
					//将内容为数组的字符串格式转换为数组格式
					eval("\$setting = ".$v['setting'].'; ');
					$data_list[$i]['form_type'] = $setting['type'] == 'checkbox' ? 'checkbox' : 'select';
				} else {
					$data_list[$i]['form_type'] = $v['form_type'];
				}
				$data_a = trim($product_info[$v['field']]);
				if ($v['form_type'] == 'address') {
					$address_array = str_replace(chr(10),' ',$data_a);
					$data_list[$i]['val'] = $address_array;
					$data_list[$i]['type'] = 0;
				} else {
					$data_list[$i]['val'] = $data_a;
					$data_list[$i]['type'] = 0;
				}
				$data_list[$i]['id'] = '';
				$i++;
			}
			//创建人信息
			$creator_role_id = $product_info['creator_role_id'];
			$creator_name = M('User')->where('role_id = %d',$creator_role_id)->getField('name');
			$data_list[$i]['field'] = 'creator_role_id';
			$data_list[$i]['name'] = '创建人';
			$data_list[$i]['val'] = $creator_name;
			$data_list[$i]['id'] = $creator_role_id;
			$data_list[$i]['type'] = 1;
			$data_list[$i]['form_type'] = 'user';

			$data['data'] = $data_list;
			$data['info'] = 'success';
			$data['status'] = 1;
			//获取产品主图
			$path = M('ProductImages')->where(array('product_id'=>$product_id,'is_main'=>1))->getField('path');
			$data['main_path'] = $path ? $path : '';
			$this->ajaxReturn($data,'JSON');
		} else {
			$this->ajaxReturn('','非法请求！',0);
		}
	}
}