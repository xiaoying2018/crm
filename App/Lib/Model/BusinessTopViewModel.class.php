<?php 
	class BusinessTopViewModel extends ViewModel{
		protected $viewFields;
		public function _initialize(){
			$data_list = array('*');

			$data_list['_type'] = "LEFT";
			//置顶逻辑
			$data_top = array('set_top','top_time');

			$data_top['_on'] = "business.business_id = top.module_id and top.module = 'business' and top.create_role_id = ".session('role_id');

			$this->viewFields = array('business'=>$data_list,'top'=>$data_top);
		}

		// public $viewFields = array(
		// 	'business'=>array('*','_type'=>'LEFT'),
		// 	'top'=>array('top_time','set_top', '_on'=>'business.business_id = top.module_id and top.module = "business" and top.create_role_id = '.session("role_id")),
		// );
	}