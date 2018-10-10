<?php

class StudentModelEdu extends EducationModelEdu
{
    protected $tableName            =   'students';

    /**
     * @ 学生列表
     * @param null $where
     * @return mixed
     */
    public function student_list ($where=null,$page=1,$pagesize=15)
    {
        $_where             =   [];
        if( !is_null($where) && is_array($where) ){
            $_where         =   array_merge( $where, $_where );
        }

        return $this->field('s.id,s.realname,s.code,s.mobile,s.email,s.remark,s.create_at,mx_u1.full_name creator_name,mx_c.name customer_name,s.customer_id')
            ->join("s LEFT JOIN {$this->dbName}.period_student p_s ON s.id = p_s.student_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = p_s.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c_per.course_id = c.id")
            ->join("LEFT JOIN mxcrm.mx_customer mx_c ON s.customer_id = mx_c.customer_id")
            ->join('LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = s.creator_id')
            ->where($_where)
            ->limit(($page-1)*$pagesize,$pagesize)
            ->order('s.create_at desc')
            ->select();
    }
    ##防止上面的方法有其他地方用到，加了这个一样的方法
    public function student_lists ($where=null,$page=1,$pagesize=15)
    {
        $_where             =   [];
        if( !is_null($where) && is_array($where) ){
            $_where         =   array_merge( $where, $_where );
        }

        // 如果不是超管也不是教务,默认只能看到自己的学员 2018-7-27 dragon
        if (!session('?admin') && !in_array(1,session('edu_roles'))) $_where['s.creator_id'] = session('user_id');
        // 2018-7-27 end

        $data =  $this->field('s.id,s.realname,s.code,s.mobile,s.email,s.remark,s.create_at,mx_u1.full_name creator_name,mx_c.name customer_name,s.customer_id')
            ->join("s LEFT JOIN {$this->dbName}.period_student p_s ON s.id = p_s.student_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = p_s.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c_per.course_id = c.id")
            ->join("LEFT JOIN mxcrm.mx_customer mx_c ON s.customer_id = mx_c.customer_id")
            ->join('LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = s.creator_id')
            ->where($_where)
            ->limit(($page-1)*$pagesize,$pagesize)
            ->group('s.code')
            ->order('s.create_at desc')
            ->select();

        $count =  $this->field('s.id,s.realname,s.code,s.mobile,s.email,s.remark,s.create_at,mx_u1.full_name creator_name,mx_c.name customer_name,s.customer_id')
            ->join("s LEFT JOIN {$this->dbName}.period_student p_s ON s.id = p_s.student_id")
            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = p_s.period_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c_per.course_id = c.id")
            ->join("LEFT JOIN mxcrm.mx_customer mx_c ON s.customer_id = mx_c.customer_id")
            ->join('LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = s.creator_id')
            ->where($_where)
            ->group('s.code')
            ->order('s.create_at desc')
            ->select();
        $count=count($count);
        Log::write(json_encode($this->getLastSql()));
//        echo "<pre>";
//        var_dump($this->getLastSql());exit();
//
//        $new_data=array();
//        foreach ($data as $k=>$v){
//            if(!isset($new_data[$v['code']])){
//                $new_data[$v['code']]=$v;
//            }else{
//                continue;
//            }
//        }
//
      //  \Log::write(json_encode($new_data));
        
       // $new_data=array_values($new_data);
//        $count = $this->field('s.id,s.realname,s.code,s.mobile,s.email,s.remark,s.create_at,mx_u1.full_name creator_name,mx_c.name customer_name,s.customer_id')
//            ->join("s LEFT JOIN {$this->dbName}.period_student p_s ON s.id = p_s.student_id")
//            ->join("LEFT JOIN {$this->dbName}.course_period c_per ON c_per.id = p_s.period_id")
//            ->join("LEFT JOIN {$this->dbName}.course c ON c_per.course_id = c.id")
//            ->join("LEFT JOIN mxcrm.mx_customer mx_c ON s.customer_id = mx_c.customer_id")
//            ->join('LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = s.creator_id')
//            ->where($_where)
//            ->order('s.create_at desc')
//            ->select();
        return ['data'=>$data,'count'=>$count];
    }

    /**
     * @ 产品列表
     * @param null $where
     */
    public function course_list ($where=null)
    {
        // TODO student->customer->business->product
        $_where             =   [];
        if( !is_null($where) && is_array($where) ){
            $_where         =   array_merge( $where, $_where );
        }
        return $this->field('mx_p.name product_name,mx_p.code,mx_p.product_id,c_p.course_id,c.name course_name,c.member_type,c.pic,c.status course_status')
            ->join("s LEFT JOIN mxcrm.mx_business mx_bs ON s.customer_id = mx_bs.customer_id")
            ->join("LEFT JOIN mxcrm.mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_bs.business_id")
            ->join("LEFT JOIN mxcrm.mx_product mx_p ON mx_p.product_id = mx_r_bp.product_id")
            ->join("LEFT JOIN {$this->dbName}.course_product c_p ON c_p.product_id = mx_p.product_id")
            ->join("LEFT JOIN {$this->dbName}.course c ON c.id = c_p.course_id")
            ->where($_where)
            ->select();
    }

    /**
     * @ 获取未转化为学员的客户
     * @return mixed
     */
    public function getNotYetCustomer ()
    {
        return $this->field('mx_c.customer_id,mx_c.name,mx_c.mobile')
            ->join('s RIGHT JOIN mxcrm.mx_customer mx_c ON s.customer_id = mx_c.customer_id')
            ->where('s.customer_id is null')
            ->select();
    }

    /**
     * @ 购买课程，还未分班的学员
     */
//    public function a_notYetToPeriodStudentList ($course_id,$period_id)
//    {
//
//        $courseProductModel     =   new CourseProductModelEdu();
//        // 获取购买该课程的所有学员
//        //      课程所属产品
//        $products               =   $courseProductModel->field('id,product_id')
//            ->where(['course_id'=>['eq',$course_id]])
//            ->select();
//        if( !$products )    return [];
//        $productIds             =   array_map( function($p){
//            return $p['product_id'];
//        }, $products );
//        //合法客户信息
//        $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.customer_id,mx_rec_o.receivingorder_id')
//            ->join("mx_cst LEFT JOIN mx_receivables mx_rec ON mx_cst.customer_id = mx_rec.customer_id") //应收款
//            ->join("LEFT JOIN mx_receivingorder mx_rec_o ON mx_rec_o.receivables_id = mx_rec.receivables_id") // 客户收款
//            ->join("LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") // 收款关联的合同
//            ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
//            ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
//            ->where(['mx_r_bp.product_id'=>['in',implode(',',$productIds)], 'mx_rec_o.receivingorder_id'=>['gt',0]])
//            ->select();
//        if( !$legelCustomer )   return [];
//        $legelCustomerIds       =   array_map( function ($c){
//            return $c['customer_id'];
//        }, $legelCustomer );
//        $legelCustomerIds=array_unique($legelCustomerIds);
//        //将合法的客户中，已经选过该课期的学生给过滤掉
//        $periodStudents=new PeriodStudentModelEdu();
//        $students=$periodStudents->field('student_id')->where('period_id ='.$period_id)->select();
//        $students_arr=[];
//        if($students){
//            foreach ($students as $k=>$v){
//                $students_arr[]=$v['student_id'];
//            }
//            $student_model=new StudentModelEdu();
//            $customer_ids=$student_model->field('customer_id')
//                ->where(array('id'=>array('in',$students_arr)))
//                ->select();
//            if($customer_ids){
//                $customer_ids_arr=[];
//                foreach ($customer_ids as $k=>$v){
//                    $customer_ids_arr[]=$v['customer_id'];
//                }
//                if(!empty($students_arr)){
//                    foreach ($legelCustomerIds as $key=> $student){
//                        if(in_array($student,$customer_ids_arr)){
//                            unset($legelCustomerIds[$key]);
//                        }
//                    }
//                }
//            }
//        }
//
//        $data= $this->alias('s')->field(' DISTINCT  s.id,s.realname');
//        $map['s.customer_id']=array('in',$legelCustomerIds);
//        $data=$data->where($map)->select() ?: [];
//
//        return $data;
//
//    }


    /**
     * @ 购买课程，还未分班的学员
     */
    public function notYetToPeriodStudentList ($course_id,$period_id)
    {

        $courseProductModel     =   new CourseProductModelEdu();
        // 获取购买该课程的所有学员
        //      课程所属产品
        $products               =   $courseProductModel->field('id,product_id')
            ->where(['course_id'=>['eq',$course_id]])
            ->select();
        if( !$products )    return [];
        $productIds             =   array_map( function($p){
            return $p['product_id'];
        }, $products );
        //合法客户信息
        $legelCustomer         =   M('Customer')->field('mx_cst.name,mx_cst.customer_id')
            ->join("mx_cst LEFT JOIN mx_contract mx_ctt ON mx_cst.customer_id = mx_ctt.customer_id") //关联的合同
            ->join("LEFT JOIN mx_r_business_contract mx_r_bc ON mx_r_bc.contract_id = mx_ctt.contract_id") // 合同关联的商机
            ->join("LEFT JOIN mx_r_business_product mx_r_bp ON mx_r_bp.business_id = mx_r_bc.business_id") // 商机下的产品
            ->where(['mx_r_bp.product_id'=>['in',implode(',',$productIds)]])
            ->select();
        if( !$legelCustomer )   return [];
        $legelCustomerIds       =   array_map( function ($c){
            return $c['customer_id'];
        }, $legelCustomer );
        $legelCustomerIds=array_unique($legelCustomerIds);
        //将合法的客户中，已经选过该课期的学生给过滤掉
        $periodStudents=new PeriodStudentModelEdu();
        $students=$periodStudents->field('student_id')->where('period_id ='.$period_id)->select();
        $students_arr=[];
        if($students){
            foreach ($students as $k=>$v){
                $students_arr[]=$v['student_id'];
            }
            $student_model=new StudentModelEdu();
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

        $data= $this->alias('s')->field(' DISTINCT  s.id,s.realname');
        $map['s.customer_id']=array('in',$legelCustomerIds);
        $data=$data->where($map)->select() ?: [];

        return $data;

    }


    protected function _before_insert(&$data, $options)
    {
        parent::_before_insert($data, $options); // TODO: Change the autogenerated stub

        $data['code']       =   date('Ymd').str_replace('.','',microtime(true));
        $data['password']   =   password_hash( trim($data['password']), PASSWORD_BCRYPT );
    }

    protected function _before_update(&$data, $options)
    {
        // 特殊处理 修改时间 修改人
        $allowFields        =   $this->allowFields();
        if( in_array('updator_id', $allowFields) ){
            $data['updator_id']         =   session('user_id');
        }
        if( in_array('update_at', $allowFields) ){
            $data['update_at']         =   date('Y-m-d H:i:s');
        }
        if( $password=$data['password'] ){
            $data['password']   =   password_hash( trim($data['password']), PASSWORD_BCRYPT );
        }else{
            unset($data['password']);
        }

    }

    public function getMobileComplementary ($mobile)
    {
        $result         =   '';
        for ($i=0;$i<11;$i++){
            $result .=  9-(int)$mobile{$i};
        }

        return $result;
    }


    public function getSignStudents($student_ids=array(),$schedule_id)
    {
        $subQuery= $this->field('s.id,s.realname,ss.schedule_id,s.code,s.mobile,ss.create_at,ss.update_at,ss.status')
            ->join("s LEFT JOIN {$this->dbName}.schedule_signin ss ON ss.student_id = s.id")
            ->where(array('s.id'=>array('in',$student_ids)))
            ->buildSql();
        $data=$this->table($subQuery.' a')->where('schedule_id='.$schedule_id)->select();
        return $data;


    }

    public function getAllStudents($student_ids=array())
    {
        $data= $this->field('id,realname,code,mobile')
            ->where(array('id'=>array('in',$student_ids)))
            ->select();
        return $data;
    }
}