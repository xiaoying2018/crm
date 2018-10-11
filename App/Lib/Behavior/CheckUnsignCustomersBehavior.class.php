<?php

class CheckUnsignCustomersBehavior extends Behavior
{


    public function _new_run(&$params)
    {
        //先查出所有已经签过合同并且没有注册的客户
            //
            $subQuery=M()->table('education.students')
                ->select(false);
            $res=M()->table('mxcrm.mx_contract')
                ->field('cu.customer_id,cu.mobile,cu.email,cu.name')
                ->join('c LEFT JOIN education.students as t1 on c.customer_id=t1.customer_id')
                ->join('LEFT JOIN mxcrm.mx_customer cu on cu.customer_id =c.customer_id')
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

            $new_students_ids=array();
            foreach ($res as $customer){
                if(!$customer['mobile']){
                    continue;
                }
                $res=$student_model->where(array('customer_id'=>array('eq',$customer['customer_id'])))->find();
                if(!$res){
                    $has_mobile=$student_model->where(array('mobile'=>array('eq',$customer['mobile']),'customer_id'=>array('exp','is null')))->find();
                    \Log::write($student_model->getLastSql());
                    if($has_mobile){
                        $new_students_ids[]=array(
                            'student_id'=>$has_mobile['id'],
                            'customer_id'=>$customer['customer_id'],
                        );
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
                        if($id){
                            $profile = ['student_id' => $id];
                            $profile['bind_mobile'] = $customer['mobile'];
                            $studentprofileModel->field('student_id')->add($profile);
                            $new_students_ids[]=array(
                                'student_id'=>$id,
                                'customer_id'=>$customer['customer_id'],
                            );
                        }

                    }

                }else{
                    continue;
                }
            }
            \Log::write(M()->getLastSql());
            if(empty($new_students_ids)){
                return ;
            }
            \Log::write('新走完流程的学员信息'.json_encode($new_students_ids));
            $period_student=array();
            $period_model=new PeriodStudentModelEdu();
            //根据客户id,查出客户的合同所签署的合同，进而查出客户合同下的产品，进而查出产品所关联的班级，进而查出班级下的班次，将学员加入班级
            foreach($new_students_ids as $key =>$new_student){
                //查询合同下关联的商机
                $products_id        =   M('Customer')->field('mx_r_bp.product_id')
                    ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
                    ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
                    ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
                    ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
                    ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
                    ->where(array('mx_cst.customer_id'=>array('eq',$new_student['customer_id'])))
                    ->group('mx_r_bp.product_id')
                    ->select();
                \Log::write('查询流程走完学员合同下所签署的产品'.M('Customer')->getLastSql());
                if(empty($products_id)){
                    continue;
                }
                foreach($products_id as $k=>$product_id){
                    $period_ids=M()->table('education.course_product')
                        ->field('cp.id')
                        ->join(' c left join education.course_period cp on c.course_id=cp.course_id')
                       // ->where('c.product_id='.$product_id['product_id'])
                        ->where(array('c.product_id'=>array('eq',$product_id['product_id']),'cp.id'=>array('exp','is not null')))
                        ->select();
                    \Log::write('查询流程走完学员合同下所签署的产品下所开的班期'.M()->getLastSql());
                    if(empty($period_ids)){
                        continue;
                    }
                    foreach($period_ids as $period){
                        $new_student=array(
                            'student_id'=>$new_student['student_id'],
                            'period_id'=>$period['id']
                        );
                        \Log::write('新入班级学员'.json_encode($new_student));
                        $period_model->add($new_student);
                        \Log::write('新入班级学员'.$period_model->getLastSql());
                    }

                }

            }


    }

    public function run(&$params)
    {
        //先查出所有已经签过合同并且没有注册的客户
        //
        $subQuery=M()->table('education.students')
            ->select(false);
        $res=M()->table('mxcrm.mx_contract')
            ->field('cu.customer_id,cu.mobile,cu.email,cu.name')
            ->join('c LEFT JOIN education.students as t1 on c.customer_id=t1.customer_id')
            ->join('LEFT JOIN mxcrm.mx_customer cu on cu.customer_id =c.customer_id')
            ->where('t1.id is null')
            ->where(array('cu.mobile'=>array('NEQ','')))
            ->group('c.contract_id')
            ->select();
        if(empty($res)){
            return ;
        }
        \Log::write(M()->getLastSql());
        \Log::write(json_encode($res));
        $student_model=new StudentModelEdu();
        $studentprofileModel = new StudentprofileModelEdu();

        $new_students_ids=array();
        foreach ($res as $customer){
            if(!$customer['mobile']){
                continue;
            }
            $res=$student_model->where(array('customer_id'=>array('eq',$customer['customer_id'])))->find();
            if(!$res){
                $has_mobile=$student_model->where(array('mobile'=>array('eq',$customer['mobile']),'customer_id'=>array('exp','is null')))->find();
                \Log::write($student_model->getLastSql());
                if($has_mobile){
                    $new_students_ids[]=array(
                        'student_id'=>$has_mobile['id'],
                        'customer_id'=>$customer['customer_id'],
                    );
                    $student_model->where(array('mobile'=>array('eq',$customer['mobile'])))->save(array('customer_id'=>$customer['customer_id']));
                }else{
                    //插入新学员
                    $id =$student_model->add(array(
                        'realname'=>$customer['name'],
                        'mobile'=>$customer['mobile'],
                        'email'=>$customer['email'],
                        'customer_id'=>$customer['customer_id'],
                        'creator_id'=>  session('user_id')?session('user_id'):5,
                        'password'=>'xiaoying123456',
                        'remark'=>'初始密码:xiaoying123456'
                    ));
                    \Log::write($student_model->getLastSql());
                    if($id){
                        $profile = ['student_id' => $id];
                        $profile['bind_mobile'] = $customer['mobile'];
                        $studentprofileModel->field('student_id')->add($profile);
                        $new_students_ids[]=array(
                            'student_id'=>$id,
                            'customer_id'=>$customer['customer_id'],
                        );
                    }

                }

            }else{
                continue;
            }
        }

    }
}