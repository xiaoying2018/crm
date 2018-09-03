<?php
	class ContractViewModel extends ViewModel {
	    public $viewFields;
		public function _initialize(){
			$main_must_field = array('*');
	        
			$main_list = array_unique(array_merge(M('Fields')->where(array('model'=>'contract','is_main'=>1))->getField('field', true),$main_must_field));
			$main_list['_type'] = 'LEFT';
			$data_list = M('Fields')->where(array('model'=>'contract','is_main'=>0))->getField('field', true);
			$data_list['_on'] = 'contract.contract_id = contract_data.contract_id';
	        $data_list['_type'] = 'LEFT';
			
			$this->viewFields = array(
				'contract'=>array('*','_type'=>'LEFT'),
				'contract_data'=>$data_list,
				'customer'=>array('name'=>'customer_name', '_on'=>'customer.customer_id=contract.customer_id','_type'=>'LEFT'),
				'business'=>array('name'=>'business_name','prefixion'=>'b_prefixion','code'=>'business_code','contacts_id'=>'contacts_id', '_on'=>'business.business_id=contract.business_id','_type'=>'LEFT'),
				'contacts'=>array('name'=>'contacts_name', '_on'=>'contacts.contacts_id=business.contacts_id','_type'=>'LEFT'),
				'user'=>array('full_name'=>'owner_name', '_on'=>'contract.owner_role_id=user.role_id')
			);
		}
	}