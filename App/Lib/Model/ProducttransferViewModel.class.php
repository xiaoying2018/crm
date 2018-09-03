<?php 
	class ProducttransferViewModel extends ViewModel{
		public $viewFields = array(
			'transfer_record'=>array('*','_type'=>'LEFT'),
			'product'=>array('name'=>'product_name','_on'=>'product.product_id=transfer_record.product_id','_type'=>'LEFT'),
		);
	}