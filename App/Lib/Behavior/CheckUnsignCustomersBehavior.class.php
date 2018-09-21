<?php

class CheckUnsignCustomersBehavior extends Behavior
{


    public function run(&$params)
    {
        //先查出所有已经签过合同并且没有注册的客户
            //
            $subQuery=M()->table('education.students')
                ->select(false);
            $res=M()->table('mxcrm.mx_contract')
                ->field('cu.customer_id,cu.mobile,cu.email,cu.name')
                ->join('c LEFT JOIN education.students as t1 on c.customer_id=t1.customer_id')
                ->join('LEFT JOIN   mxcrm.mx_customer cu on cu.customer_id =c.customer_id')
                ->join("LEFT JOIN mxcrm.mx_receivables mx_rec ON c.customer_id = mx_rec.customer_id") //应收款
                ->join("LEFT JOIN mxcrm.mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
                ->where('t1.id is null')
                ->where(array('c.is_checked'=>array('eq',1),'cu.mobile'=>array('NEQ',''),'mx_rec_o.receivingorder_id'=>array('gt',0)))
                ->group('c.contract_id')
                ->select();
            if(empty($res)){
                return ;
            }
            \Log::write(M()->getLastSql());
            \Log::write(json_encode($res));
            $student_model=new StudentModelEdu();
            $studentprofileModel = new StudentprofileModelEdu();
            foreach ($res as $customer){
                if(!$customer['mobile']){
                    continue;
                }
                $res=$student_model->where(array('customer_id'=>array('eq',$customer['customer_id'])))->find();
                if(!$res){
                    $has_mobile=$student_model->where(array('mobile'=>array('eq',$customer['mobile']),'customer_id'=>array('exp','is null')))->find();
                    \Log::write($student_model->getLastSql());
                    if($has_mobile){
                        $student_model->where(array('mobile'=>array('eq',$customer['mobile'])))->save(array('customer_id'=>$customer['customer_id']));
                    }else{
                        //插入新学员
                        $id =$student_model->add(array(
                            'realname'=>$customer['name'],
                            'mobile'=>$customer['mobile'],
                            'email'=>$customer['email'],
                            'customer_id'=>$customer['customer_id'],
                            'password'=>'xiaoying123456',
                            'remark'=>'初始密码:xiaoying123456'
                        ));
                        \Log::write($student_model->getLastSql());
                        $profile = ['student_id' => $id];
                        $profile['bind_mobile'] = $customer['mobile'];
                        $studentprofileModel->field('student_id')->add($profile);
                    }

                }else{
                    continue;
                }
            }
            \Log::write(M()->getLastSql());

    }
}