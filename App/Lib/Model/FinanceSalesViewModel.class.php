<?php 
	class FinanceSalesViewModel extends ViewModel{
		public $viewFields = array(
			'sales'=>array('sales_id'=>'sales_id', '_type'=>'LEFT'),
			'receivables'=>array('receivables_id'=>'receivables_id','_on'=>'receivables.sales_id = sales.sales_id','_type'=>'LEFT')
		);
	}