<?php 
	class SalefaqViewModel extends ViewModel{
		public $viewFields = array(
			'salefaq'=>array('salefaq_id','category_id','role_id','title','content','create_time','update_time','hits' ,'_type'=>'LEFT'),
			'salefaq_cate'=>array('name'=>'name', '_on'=>'salefaq.category_id=salefaq_cate.category_id'),
		);

	}