<?php 
	class FinanceSalesViewModel extends ViewModel{
		public $viewFields = array(
			'sales'=>array('*', '_type'=>'LEFT'),
			'receivables'=>array('*','_on'=>'receivables.sales_id = sales.sales_id','_type'=>'LEFT')
		);
	}