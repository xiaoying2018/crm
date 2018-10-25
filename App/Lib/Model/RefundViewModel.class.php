<?php
class RefundViewModel extends ViewModel{
    public $viewFields = array(
        'refund'=>array('*', '_type'=>'LEFT'),
        'contract'=>array('number'=>'contract_number','price'=>'contract_price', '_on'=>'refund.contract_id=contract.contract_id','_type'=>'LEFT'),
        'role'=>array('_on'=>'refund.creator_role_id=role.role_id', '_type'=>'LEFT'),
        'role2'=>array('_table'=>'mx_role','_as'=>'role2','_on'=>'refund.owner_role_id=role2.role_id', '_type'=>'LEFT'),
        'customer'=>array('name'=>'customer_name','_on'=>'refund.customer_id=customer.customer_id', '_type'=>'LEFT'),
        'user'=>array('full_name'=>'creator_name', '_on'=>'role.user_id = user.user_id'),
        'user2'=>array('full_name'=>'owner_name','_table'=>'mx_user','_as'=>'user2', '_on'=>'role2.user_id = user2.user_id'),
    );
}