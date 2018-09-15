<?php

class PeriodStudentModelEdu extends EducationModelEdu
{
    protected $tableName                =   'period_student';


    public function  addStudents($course_id,$period_id)
    {
        //先获取此班级关联产品,根据course_id 获取到product_id
        //一般情况下，一个班级之关联一个产品，当然也许未来可能会关联多个产品，因此获取的产品为一个数组
        $course_product_model = new CourseProductModelEdu();
        $where=array(
            'course_id'=>array(
                'eq',$course_id
            ),
        );
        //获取此班级下的关联的所有product_id
        $products=$course_product_model->where($where)->field('product_id')->select();
        //如果没有关联产品，返回
        if(empty($products)){
            return ;
        }
        //如果有关联产品，将所有产品的product_id组成一个数组
        $products_ids_arr=array();
        foreach ($products as $product){
            $products_ids_arr[]=$product['product_id'];
        }

        //获取此产品下的所有学生
        $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.mobile,mx_cst.email,mx_cst.customer_id,mx_rec_o.receivingorder_id')
            ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
            ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
            ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
            ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
            ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
            ->where(['mx_r_bp.product_id'=>['in',$products_ids_arr], 'mx_rec_o.receivingorder_id'=>['gt',0]])
            ->select();
        if(empty($legelCustomer)){
            return ;
        }

        $legelCustomerIds       =   array_map( function ($c){
            return $c['customer_id'];
        }, $legelCustomer );
        $legelCustomerIds=array_unique($legelCustomerIds);
        //将合法客户在学员表中进行查询，如果没有就创建
        $student_model=new StudentModelEdu();
        $studentprofileModel = new StudentprofileModelEdu();
        foreach ($legelCustomer as $customer){
            $res=$student_model->where(array('customer_id'=>array('eq',$customer['customer_id'])))->find();
            if(!$res){
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
            }else{
                continue;
            }
        }

        //将合法的客户中，已经选过该课期的学生给过滤掉
        $periodStudents=new PeriodStudentModelEdu();
        $students=$periodStudents->field('student_id')->where('period_id ='.$period_id)->select();
        $students_arr=[];
        if($students){
            foreach ($students as $k=>$v){
                $students_arr[]=$v['student_id'];
            }

            $customer_ids=$student_model->field('customer_id')
                ->where(array('id'=>array('in',$students_arr)))
                ->select();
            if($customer_ids){
                $customer_ids_arr=[];
                foreach ($customer_ids as $k=>$v){
                    $customer_ids_arr[]=$v['customer_id'];
                }
                if(!empty($students_arr)){
                    foreach ($legelCustomerIds as $key=> $student){
                        if(in_array($student,$customer_ids_arr)){
                            unset($legelCustomerIds[$key]);
                        }
                    }
                }
            }
        }

        $data= $student_model->alias('s')->field(' DISTINCT  s.id,s.realname');
        $map['s.customer_id']=array('in',$legelCustomerIds);
        $data=$data->where($map)->select() ?: [];

        if(empty($data)){
            return ;
        }
        foreach ($data as $da){
            $this->add(array(
                'period_id'=>$period_id,
                'student_id'=>$da['id'],
            ));
        }
    }


    public function addStudentsToAllPeriods($course_id)
    {
        //先获取此班级关联产品,根据course_id 获取到product_id
        //一般情况下，一个班级之关联一个产品，当然也许未来可能会关联多个产品，因此获取的产品为一个数组
        $course_product_model = new CourseProductModelEdu();
        $where=array(
            'course_id'=>array(
                'eq',$course_id
            ),
        );
        //获取此班级下的关联的所有product_id
        $products=$course_product_model->where($where)->field('product_id')->select();
        //如果没有关联产品，返回
        if(empty($products)){
            return ;
        }
        //如果有关联产品，将所有产品的product_id组成一个数组
        $products_ids_arr=array();
        foreach ($products as $product){
            $products_ids_arr[]=$product['product_id'];
        }

        //获取此产品下的所有学生
        $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.mobile,mx_cst.email,mx_cst.customer_id,mx_rec_o.receivingorder_id')
            ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
            ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
            ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
            ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
            ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
            ->where(['mx_r_bp.product_id'=>['in',$products_ids_arr], 'mx_rec_o.receivingorder_id'=>['gt',0]])
            ->select();
        if(empty($legelCustomer)){
            return ;
        }

        $legelCustomerIds       =   array_map( function ($c){
            return $c['customer_id'];
        }, $legelCustomer );
        $legelCustomerIds=array_unique($legelCustomerIds);
        //将合法客户在学员表中进行查询，如果没有就创建
        $student_model=new StudentModelEdu();
        $studentprofileModel = new StudentprofileModelEdu();
        \Log::write(json_encode($legelCustomer));
        foreach ($legelCustomer as $customer){
            $res=$student_model->where(array('customer_id'=>array('eq',$customer['customer_id'])))->find();

            if(!$res){
                //插入新学员
                $id =$student_model->add(array(
                    'realname'=>$customer['name'],
                    'mobile'=>$customer['mobile'],
                    'email'=>$customer['email'],
                    'customer_id'=>$customer['customer_id'],
                    'password'=>'xiaoying123456',
                    'remark'=>'初始密码:xiaoying123456'
                ));
                Log::write($student_model->getLastSql());
                $profile = ['student_id' => $id];
                $profile['bind_mobile'] = $customer['mobile'];
                $studentprofileModel->field('student_id')->add($profile);
                Log::write($studentprofileModel->getLastSql());
            }else{
                continue;
            }
        }

        //查询改班级下所有开的班期
        $couser_periods_model =new PeriodModelEdu();
        $periods_ids=$couser_periods_model->field('id')->where(array('course_id'=>array('eq',$course_id)))->select();
        if(empty($periods_ids)){
            return ;
        }
        //将合法的客户中，已经选过该课期的学生给过滤掉
        $periodStudents=new PeriodStudentModelEdu();
        foreach ($periods_ids as $period){

            $students=$periodStudents->field('student_id')->where('period_id ='.$period['id'])->select();
            $students_arr=[];
            if($students){
                foreach ($students as $k=>$v){
                    $students_arr[]=$v['student_id'];
                }
                $customer_ids=$student_model->field('customer_id')
                    ->where(array('id'=>array('in',$students_arr)))
                    ->select();
                if($customer_ids){
                    $customer_ids_arr=[];
                    foreach ($customer_ids as $k=>$v){
                        $customer_ids_arr[]=$v['customer_id'];
                    }
                    if(!empty($students_arr)){
                        foreach ($legelCustomerIds as $key=> $student){
                            if(in_array($student,$customer_ids_arr)){
                                unset($legelCustomerIds[$key]);
                            }
                        }
                    }
                }
            }

            $data= $student_model->alias('s')->field(' DISTINCT  s.id,s.realname');
            $map['s.customer_id']=array('in',$legelCustomerIds);
            $data=$data->where($map)->select() ?: [];

            if(empty($data)){
                return ;
            }
            foreach ($data as $da){
                $this->add(array(
                    'period_id'=>$period['id'],
                    'student_id'=>$da['id'],
                ));
            }
        }
    }


    public function removeStudentsFromPeriodsStudent($course_id,$product_id)
    {
        \Log::write('课程id'.$course_id.'产品id'.$product_id);
        //获取此产品下的所有学生
        $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.customer_id,mx_rec_o.receivingorder_id')
            ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
            ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
            ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
            ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
            ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
            ->where(['mx_r_bp.product_id'=>['eq',$product_id], 'mx_rec_o.receivingorder_id'=>['gt',0]])
            ->select();
        if(empty($legelCustomer)) return ;
        $customer_ids=array();
        foreach ($legelCustomer as $customer){
            $customer_ids[]=$customer['customer_id'];
        }

        //获取所有的学生student_id
        $student_model =new StudentModelEdu();

        $student_ids=$student_model->field('id')->where(array('customer_id'=>array('in',$customer_ids)))->select();
        \Log::write('要踢出班级的学生id'.json_encode($student_ids));
        if(empty($student_ids)) return ;

        //获取课程下所有关联的产品
        $course_product_model =new CourseProductModelEdu();
        $product_ids=$course_product_model->field('product_id')->where(array('course_id'=>array('eq',$course_id)))->select();
        \Log::write('课程关联的其他产品'.json_encode($product_ids));
        //根据剩余关联的产品product_id查出学生student_id
        //将要踢出的学生与查出的学生对比，如果有相同的就将此学生从要踢出的学生数组中排除(此学生买了其他的产品)
        if(!empty($product_ids)){
            $product_ids_arr=array();
            foreach ($product_ids as $product_id){
                $product_ids_arr[]=$product_id['product_id'];
            }

            $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.mobile,mx_cst.email,mx_cst.customer_id,mx_rec_o.receivingorder_id')
                ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
                ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
                ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
                ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
                ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
                ->where(['mx_r_bp.product_id'=>['in',$product_ids_arr], 'mx_rec_o.receivingorder_id'=>['gt',0]])
                ->select();
            if(!empty($legelCustomer)){
                $legelCustomerIds       =   array_map( function ($c){
                    return $c['customer_id'];
                }, $legelCustomer );
                $legelCustomerIds=array_unique($legelCustomerIds);
                $other_student_ids=$student_model->field('id')->where(array('customer_id'=>array('in',$legelCustomerIds)))->select();

                foreach ($student_ids as $key=> $student_id){
                    if(in_array($student_id,$other_student_ids)){
                        unset($student_ids[$key]);
                    }
                }

            }
        }

        //获取课程下所有的课期
        $periods_model = new PeriodModelEdu();

        $periods_ids=$periods_model->field('id')->where(array('course_id'=>array('eq',$course_id)))->select();

        if(empty($periods_ids)) return ;

        $periods_ids_arr=array();
        foreach ($periods_ids as $periods_id)
        {
            $periods_ids_arr[]=$periods_id['id'];
        }

        foreach ($periods_ids_arr as $period){
            foreach ($student_ids as $student_id){
                $delete_arr[]=array(
                    'period_id'=>$period,
                    'student_id'=>$student_id
                );
            }
        }
        \Log::write(json_encode($delete_arr));
        foreach ($delete_arr as $delete){
            $this->delete($delete);
        }
    }
}