<?php 
	class ContactsTempViewModel extends ViewModel {
	   public $viewFields = array(
		'contacts'=>array('*','_type'=>'LEFT'),
		'RContactsCustomer'=>array('_on'=>'contacts.contacts_id=RContactsCustomer.contacts_id','_type'=>'LEFT'),
		'customer'=>array('customer_id','owner_role_id','name'=>'customer_name','_on'=>'customer.customer_id=RContactsCustomer.customer_id')
	   );
	} 