<?php

class EducationviewAction extends Action
{
    public function _initialize (){
        $action = [
            'permission'        =>  [ACTION_NAME],
            'allow'             =>  [],
        ];
        B('Authenticate', $action);
        if( ACTION_NAME == 'my_headmaster' ){
            !in_array( 2, session('edu_roles') ) && alert('error', '您不是班主任!', $_SERVER['HTTP_REFERER']);
        }elseif(ACTION_NAME == 'my_lecturer'){
            !in_array( 3, session('edu_roles') ) && alert('error', '您不是讲师!', $_SERVER['HTTP_REFERER']);
        }
    }

    public function student_banci()
    {
        $this->display();
    }

    public function index ()
    {
        $this->display();
    }

    public function course_add ()
    {
        $category           =   [
            ['id'=>1,'name'=>'修士考试'],
            ['id'=>2,'name'=>'留考校内考'],
            ['id'=>3,'name'=>'热门公开课'],
            ['id'=>4,'name'=>'保录班'],
        ];
        $member_type        =   [
            1   =>  '一对多',
            2   =>  '一对一',
        ];
        $this->assign( compact('category', 'member_type') );
        $this->display();
    }

    public function course_edit ()
    {
        $id             =   (int)I('get.id');
        $courseModel    =   new CourseModelEdu();
        if( !$id )  $this->error('主键缺失');
        // 详情
        $info           =   $courseModel->field('id,name,category,member_type,pic,detail')
            ->where( ['id'=>['eq',$id], 'status'=>['eq',1]] )
            ->find();
        if( !$info )  $this->error('课程不存在');

        $category           =   [
            ['id'=>1,'name'=>'修士考试'],
            ['id'=>2,'name'=>'留考校内考'],
            ['id'=>3,'name'=>'热门公开课'],
            ['id'=>4,'name'=>'保录班'],
        ];
        $member_type        =   [
            1   =>  '一对多',
            2   =>  '一对一',
        ];
        $this->assign( compact('category', 'member_type', 'info') );

        $this->display();
    }

    public function course_index ()
    {
        $this->display();
    }

    public function course_detail ()
    {
        $this->display();
    }

    public function section_add ()
    {
        $this->display();
    }

    public function section_edit ()
    {
        $id             =   (int)I('get.id');
        $sectionModel   =   new SectionModelEdu();
        if( !$id )  $this->ajaxReturn(['result'=>false,'error'=>'主键缺失!']);
        // 详情
        $info           =   $sectionModel->field('id,node,name,title,duration,detail,cate')
            ->where( ['id'=>['eq',$id], 'status'=>['eq',1]] )
            ->find();
        if( !$info )  $this->ajaxReturn( ['result'=>false,'error'=>'课时不存在!','_sql'=>$sectionModel->getDbError()] );

        $info['cate_name'] = (new SectionCateModelEdu())->where(['id'=>['eq',$info['cate']]])->find()['name']?:'';

        $this->assign( compact( 'info') );

        $this->display();
    }

    public function section_index (){}

    public function section_cate_add()
    {
        $this->display();
    }

    public function period_add ()
    {
        $teacherModel       =   new TeacherModelEdu();
        $headmaster         =   $teacherModel->teacher_lists(['tu.role_id'=>['eq',2]])['data'];
        $this->assign( compact('headmaster') );
        $this->display();
    }

    public function period_del () {}

    public function period_edit ()
    {
        $id             =   (int)I('get.id');
        $periodModel    =   new PeriodModelEdu();
        if( !$id )  $this->ajaxReturn(['result'=>false,'error'=>'主键缺失!']);
        // 详情
        $info           =   $periodModel->field('id,name,headmaster_id')
            ->where( ['id'=>['eq',$id], 'status'=>['eq',1]] )
            ->find();
        if( !$info )
            $this->ajaxReturn( ['result'=>false,'error'=>'课时不存在!','_sql'=>$periodModel->getDbError()] );
        $teacherModel       =   new TeacherModelEdu();
        $headmaster         =   $teacherModel->teacher_lists(['tu.role_id'=>['eq',2]])['data'];
        $this->assign( compact('headmaster', 'info') );

        $this->display();
    }

    public function period_index ()
    {

    }

    public function period_detail ()
    {
        $id = I('get.id');
        $this->assign([
            'id'=>$id,
        ]);
        $this->display();
    }

    public function teacher_index ()
    {
        $roleModel          =   new RoleModelEdu();
        $roles              =   $roleModel->field('id,name')->order('id desc')->select();
        $this->assign( compact('roles') );
        $this->display();
    }

    public function teacher_add ()
    {
        // 教务角色数据
        $eduRoleModel           =   new RoleModelEdu();
        $eduRoles               =   $eduRoleModel->field('id,name')->select();
        // 用户表数据
        $users                  =   M('User')->field('user_id,full_name')
            ->where(['status'=>['eq',1]])
            ->select();

        $this->assign( compact('eduRoles', 'users') );

        $this->display();
    }

    public function teacher_edit ()
    {
        $user_id                =   (int)I('get.id');
        if( !$user_id )     $this->ajaxReturn(['result'=>false,'error'=>'主键缺失!']);
        $userinfo               =   M('User')->field('user_id,full_name')
            ->where(['user_id'=>['eq',$user_id],'status'=>['eq',1]])
            ->find();
        if( !$userinfo )
            $this->ajaxReturn( ['result'=>false,'error'=>'员工不存在或没有教务角色'] );
        // 教务角色数据
        $eduRoleModel           =   new RoleModelEdu();
        $eduRoles               =   $eduRoleModel->field('id,name')->select();

        $this->assign( compact('eduRoles', 'userinfo') );

        $this->display();
    }

    public function teacher_livepic ()
    {
        $id     =   I('get.id');
        $id || $this->ajaxReturn( [
            'result'        =>  false,
            'error'         =>  '参数缺失',
        ] );
        $this->assign([
            'id'    => $id,
        ]);
        $this->display();
    }

    public function teacher_detail ()
    {
        $user_id                =   (int)I('get.id');
        if( !$user_id )     $this->ajaxReturn(['result'=>false,'error'=>'主键缺失!']);
        $userinfo               =   M('User')->field('user_id,full_name')
            ->where(['user_id'=>['eq',$user_id],'status'=>['eq',1]])
            ->find();
        if( !$userinfo )
            $this->ajaxReturn( ['result'=>false,'error'=>'员工不存在'] );
        $teacherDetailModel     =   new TeacherdetailModelEdu();
        $teacherpicModel        =   new TeacherpicModelEdu();
        $info            =   $teacherDetailModel->find($user_id) ?: [];
        if( $info ){
            $pics       =   $teacherpicModel->teacher_pics( ['t_pic.user_id'=>['eq',$user_id]] ) ?: [];
            $info['pic'] =   $pics;
        }
        $this->assign( compact('info','userinfo') );

        $this->display();
    }

    public function schedule_add ()
    {

    }

    public function courseproduct_add ()
    {
        $productModel       =   M('Product');
        $products           =   $productModel->field('product_id,name')
            ->where(['is_deleted'=>['neq',1]])
            ->select();

        $this->assign( compact('products') );

        $this->display();
    }

    public function student_index ()
    {
        $courseModel        =   new CourseModelEdu();
        $courses            =   $courseModel->field('id,name')
            ->where(['status'=>['eq',1]])
            ->order('id desc')
            ->limit(20)
            ->select() ?: [];
        $this->assign('courses',$courses);
        $this->display();
    }

    public function student_add ()
    {
        $studentModel           =   new StudentModelEdu();
        $notYetCoustimer        =   $studentModel->getNotYetCustomer();
        $this->assign( compact('notYetCoustimer') );
        $this->display();
    }

    public function student_edit ()
    {
        $id             =   I('get.id');
        !$id && $this->ajaxReturn( ['result'=>false, 'error'=>'参数缺失'] );
        $studentModel   =   new StudentModelEdu();
        $info           =   $studentModel->student_list(['s.id'=>['eq',$id]]);
        !$info && $this->ajaxReturn( ['result'=>false, 'error'=>'学生不存在'] );
        $this->assign( [
            'info'      =>  $info[0],
        ] );
//        var_dump($info);
        $this->display();
    }

    public function student_detail ()
    {
        $id = I('get.id');
        $this->assign([
            'id'=>$id,
        ]);
        $this->display();
    }

    public function notify ()
    {
        $send_type          =   [
            ['type'=>'站内信','key'=>1],
            ['type'=>'邮件','key'=>2],
            ['type'=>'短信','key'=>4],
        ];
        $this->assign(compact('send_type'));
        $this->display();
    }

    public function my_headmaster ()
    {
        $this->display();
    }

    public function headmaster_period ()
    {
        $this->display();
    }

    public function headmaster_studentsignin ()
    {
        $schdule_id             =   (int)I('get.id');
        if( !$schdule_id ){
            $this->ajaxReturn( ['result'=>false, 'error'=>'参数缺失'] );
        }
        $scheduleModel          =   new ScheduleModelEdu();
        $_where                 =   [
            'c_sch.id'     =>  ['eq',$schdule_id],
        ];
        $scheduleInfo           =   $scheduleModel->teacher_schedule($_where);
        if( !$scheduleInfo ){
            $this->ajaxReturn( ['result'=>false, 'error'=>'数据不存在'] );
        }else{
            $scheduleInfo       =   $scheduleInfo[0];
        }

        $this->assign( compact('scheduleInfo') );

        $this->display();
    }

    public function my_lecturer ()
    {
        $courseModel        =   new CourseModelEdu();
        $courses            =   $courseModel->field('id,name')
            ->where(['status'=>['eq',1]])
            ->order('id desc')
            ->limit(20)
            ->select() ?: [];
        $this->assign('courses',$courses);
        $this->display();
    }

}