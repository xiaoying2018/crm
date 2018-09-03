<?php 
	class ProductwarehouseViewModel extends ViewModel{
		public $viewFields = array(
			'stock_record'=>array('*','_type'=>'LEFT'),
			'product'=>array('name'=>'product_name','_on'=>'product.product_id=stock_record.product_id','_type'=>'LEFT'),
			'warehouse'=>array('name'=>'warehouse_name','_on'=>'stock_record.warehouse_id=warehouse.warehouse_id','_type'=>'LEFT')
		);
	}