<?php 
	class invoiceViewModel extends ViewModel {
	   public $viewFields = array(
		'invoice'=>array('*','_type'=>'LEFT'),
		'contract'=>array('number'=>'contract_num', '_on'=>'contract.contract_id=invoice.contract_id','_type'=>'LEFT'),
		'customer'=>array('name'=>'customer_name', '_on'=>'customer.customer_id=contract.customer_id','_type'=>'LEFT'),
		'user'=>array('full_name','_on'=>'user.role_id=invoice.create_role_id','_type'=>'LEFT')
	   );
	} 