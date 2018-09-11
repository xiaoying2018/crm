<?php
class SignInAction extends Action
{

    public function _initialize()
    {
        // 注入验证类
        $this->_validator = new Validator();

    }

    public function signin_in()
    {
        try {
            // 请求
            IS_AJAX || $this->_throw('非法操作');
            // 参数
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);

            if ($result['result'] === true) {
                $student_id =$params['student_code'];

                $student_model = new StudentModelEdu();
                $student_info=$student_model->field('id')->where('code='.$student_id)->find();

                $schedule_id = (int)$params['schedule_id'];
                // $status = $params['status'];
                $signinModel = new SigninModelEdu();
                $info = $signinModel->field('id')->where(['student_id' => ['eq', $student_info['id']], 'schedule_id' => ['eq', $schedule_id]])
                    ->find();
                if ($info) {
                    $update['id'] = $info['id'];
                    $update['status'] = 1;
                    if ($signinModel->save($update) === false) {
                        $this->_throw($signinModel->getDbError());
                    }
                } else {
                    $create['student_id'] = $student_info['id'];
                    $create['schedule_id'] = $schedule_id;
                    $create['status'] = 1;
                    if (!$signinModel->add($create)) {
                        $this->_throw($signinModel->getDbError());
                    }
                }
                $this->ajaxReturn([
                    'result' => true,
                ]);
            }
            $this->_throw($this->_message(ACTION_NAME, $result['field']));

        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            $this->ajaxReturn($result);
        }
    }


    public function signList()
    {

        if ($this->isAjax()) {
            $schedule_id=I('id');
            if(!$schedule_id) die('非法id');
            //根据排课查询到period_id 进而通过period_id查询到所有的学生id(period_student),可以查到该班期的所有学员
            $schedule_model=new ScheduleModelEdu();
            $student_model = new StudentModelEdu();
            $period_model = new PeriodModelEdu();
            $schedule_info=$schedule_model->field('period_id')->where('id='.$schedule_id)->find();
            $period_info=$period_model->where('id='.$schedule_info['period_id'])->find();
            $period_student_model= new PeriodStudentModelEdu();

            $AllStudents=$period_student_model->field('student_id')->where('period_id='.$schedule_info['period_id'])->select();
            $student_ids=array();
            $all_students=$sign_students=$unSign_students=array();
            if(!empty($AllStudents)){
                foreach ($AllStudents as $student)
                {
                    $student_ids[]=$student['student_id'];
                }
                $all_students=$student_model->getAllStudents($student_ids);

                //根据schedule_id查询签到表(schedule_signin)中该课程的签到情况
                $sign_model =new SigninModelEdu();
                $students=$sign_model->field('student_id')->where('schedule_id='.$schedule_id)->select();
                if($students){
                    $student_ids=array();
                    foreach ($students as $student){
                        $student_ids[]=$student['student_id'];
                    }
                    $sign_students=$student_model->getSignStudents($student_ids,$schedule_id);
                }
                //两者相交，查询到未签到的学员
                $unSign_students=array_diff_assoc($all_students,$sign_students);

            }
            $count=array(
                'all'=>count($all_students),
                'sign'=>count($sign_students),
                'unsign'=>count($unSign_students),
            );

            $this->ajaxReturn([
                'result' => true,
                'detail'=>$period_info,
                'count'=>$count,
                'allStudents' =>    $all_students,
                'signStudents' =>   $sign_students,
                'unSignStudents' => $unSign_students,
            ]);
        }
        $this->display('signList');
    }

    protected function _throw($message, $code = 414)
    {
        throw new Exception($message, $code);
    }

    public function showSignList()
    {
        $this->assign('schedule_id',I('schedule_id'));
        $this->display('signList');
    }

    protected function _rules()
    {
        return [

            /* signin */
            'signin_in' => [
                'schedule_id' => 'required',
                'student_code' => 'required',
                //'status' => 'required|in:0,1,-7',
            ],
        ];
    }


    public function getUserInfo()
    {
        if($this->isPost()){
            $condition = I('post.condition');
            if(empty($condition)){
                $this->ajaxReturn([
                    'status'=>false,
                    'info'=>'请输入查询条件'
                ]);
            }
            $d_user = D('RoleView');
            $where['user.qq']  = array('eq', $condition);
            $where['user.wechat']  = array('eq',$condition);
            $where['user.telephone']  = array('eq',$condition);
            $where['_logic'] = 'or';
            $map['_complex'] = $where;
            $map['status']  = array('eq',1);
            $user = $d_user->where($map)->find();
            if($user){
                $this->ajaxReturn([
                    'status'=>true,
                    'info'=>$user
                ]);
            }else{
                $this->ajaxReturn([
                    'status'=>false,
                    'info'=>'查询不到此用户'
                ]);
            }

        }
    }
}