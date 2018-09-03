<?php 
	class ContactsViewModel extends ViewModel {
		public function _initialize(){
			$main_must_field = array('contacts_id','owner_role_id','is_locked','creator_role_id','delete_role_id','create_time','delete_time','update_time','is_deleted');
			$main_list = array_unique(array_merge(M('Fields')->where(array('model'=>'contacts','is_main'=>1))->getField('field', true),$main_must_field));
			$data_list = M('Fields')->where(array('model'=>'contacts','is_main'=>0))->getField('field', true);
			$data_list['_on'] = 'contacts.contacts_id = contacts_data.contacts_id';
			
			$this->viewFields = array(  
				'contacts'=>array('*','_type'=>'LEFT'),
				'contacts_data'=>$data_list,
				//'contacts_data'=>array('*', '_on'=>'contacts.contacts_id = contacts_data.contacts_id','_type'=>'LEFT'),
				'RContactsCustomer'=>array('customer_id','_on'=>'RContactsCustomer.contacts_id=contacts.contacts_id','_type'=>'LEFT'),
				'customer'=>array('name'=>'customer_name','owner_role_id', '_on'=>'RContactsCustomer.customer_id=customer.customer_id')
			);
		}
	} 