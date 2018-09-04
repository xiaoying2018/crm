<?php

class EducationAction extends Action
{
    public function _initialize()
    {
        // 注入验证类
        $this->_validator = new Validator();

        if (!session('?admin') && !session('edu_roles'))
            $this->ajaxReturn(['result' => false, 'error' => '无权操作']);
    }


    public function index()
    {

    }

    /* >>>>>>>>>>>>>>>>>>> 教师、班主任角色 start <<<<<<<<<<<<<<<<< */
    public function my_period()
    {
        try {
            if (in_array(2, session('edu_roles'))) {
                $headmaster_id = (int)session('user_id');
                // TODO 获取班级信息
                $periodModel = new PeriodModelEdu();
                $periods = $periodModel->period_lists(['c_per.headmaster_id' => ['eq', $headmaster_id]]);
                $result = true;
                $this->ajaxReturn(compact('result', 'periods'));
            }
            $this->_throw('无权操作');
        } catch (Exception $e) {
            $result = ['result' => false, 'error' => $e->getMessage()];
            $this->ajaxReturn($result);
        }
    }

  public function my_schedule()
    {
        try {
            if (in_array(3, session('edu_roles'))) {
                $teacher_id = (int)session('user_id');
                // TODO 获取班级信息
                $scheduleModel = new ScheduleModelEdu();
                $_where = [
                    'c_sch.teacher_id' => ['eq', $teacher_id],
                ];

                if ($_REQUEST['course']!='') {
                    $_where['c.id'] =['eq',$_REQUEST['course']] ;
                }

                if ($_REQUEST['livecontent']!='') {
                    $_where['c_per.id'] =['eq',$_REQUEST['livecontent']];
                }

                // 所有排课信息
                //$scheduleALL = $scheduleModel->teacher_schedule($_where);
                $builder = $scheduleModel->new_teacher_schedule($_where);
                $page = $_REQUEST['page'];
                $pagesize = $_REQUEST['pageSize'] ? $_REQUEST['pageSize'] : 10;// 每页显示条数
                $startPage=($page-1)>0?$page-1:0;
                $start = $startPage * $pagesize;// 查询起始值
                $scheduleALL=$builder->limit($start,$pagesize)->select();
                $_sql = $scheduleModel->getLastSql();
                $count=$scheduleModel->new_teacher_schedule($_where)->count();
                $finished = [];
                $soon = [];
                $schedules = [];
                $now = time();
                foreach ($scheduleALL as $line) {

                    // 8-27 如果当前课程有房间号码,添加进入房间的链接 TODO wait
                    if ($line['serial'])
                    {
                        $_tk_send_url = C('TK_ROOM.url')?:'http://global.talk-cloud.net/WebAPI/';
                        $send_url = $_tk_send_url.'entry';// 接口请求地址
                        $send_url .= '?domain='.C('TK_ROOM.domain');// 公司域名
                        // auth 值为 MD5(key + ts + serial + usertype)
                        $send_url .= '&auth='.md5(C('TK_ROOM.key') . time() . $line['serial'] . 0);// 令牌
                        $send_url .= '&usertype=0';// 用户类型 0=讲师
                        $send_url .= '&ts='.time();// 时间戳
                        $send_url .= '&serial='.$line['serial'];// 房间号码
                        $_tk_teacher_name = session('full_name')?:'主讲老师';// 用户姓名
                        if (strpos($_tk_teacher_name,'@'))
                        {
                            $_tk_teacher_name =substr($_tk_teacher_name,0,strpos($_tk_teacher_name,'@'));
                        }
                        $send_url .= '&username='.$_tk_teacher_name;
                        $ckey = C('TK_ROOM.key');// 企业authkey
                        $chairmansourcepwd = 'xy_laoshi';// 用户密码
                        // 指定的加密方式
                        $ChairmanPWD = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $ckey, $chairmansourcepwd, MCRYPT_MODE_ECB);
                        $send_url .= '&userpassword=' . bin2hex($ChairmanPWD);
                        $line['serial'] = $send_url;
                    }
                    // 8-27 end

                    if ($now > ($line['stamp']+$line['duration']*60)) {
                        // 已结束
                        $line['schedule_status'] = 0;
                        $finished[] = $line;
                    }elseif($line['stamp']<$now && $now <($line['stamp']+$line['duration']*60)){
                        $line['schedule_status'] = 2;
                        $soon[] = $line;
                    } else {
                        $line['schedule_status'] = 1;
                        $soon[] = $line;
                    }
                }

                $schedules = array_merge($soon, $finished);
                $result = true;
                $total=ceil(intval($count)/$pagesize);
                //$_sql = $scheduleModel->getLastSql();
                $this->ajaxReturn(compact('result', 'count','total','schedules', '_sql'));
            }
            $this->_throw('无权操作');
        } catch (Exception $e) {
            $result = ['result' => false, 'error' => $e->getMessage()];
            $this->ajaxReturn($result);
        }
    }
    /* >>>>>>>>>>>>>>>>>>> 教师、班主任角色 start <<<<<<<<<<<<<<<<< */


    /* >>>>>>>>>>>>>>>>>>> 课程模块 start <<<<<<<<<<<<<<<<< */
    public function course_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            $params = I('post.');
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $courseModel = new CourseModelEdu();
                // 写库
                if ($data = $courseModel->create($params, 1)) {
                    try {
                        // 开启
                        $courseModel->startTrans();
                        $fileInfo = $this->upload();
                        $data['pic'] = $fileInfo[0]['savepath'] . $fileInfo[0]['savename'];
                        if ($courseModel->add($data)) {
                            // 提交
                            $courseModel->commit();
                            //$this->ajaxReturn(['result' => true]);
                            //alert('success', '课程添加成功', U('educationview/course_add'));
                            $this->redirect( U('educationview/course_add'));
                        } else {
                            throw new Exception($courseModel->getError(), 414);
                        }
                    } catch (Exception $e) {
                        // 回滚
                        $courseModel->rollback();
                        $this->error($e->getMessage());
                    }
                } else {
                    $this->error($courseModel->getError());
                }
            } else {
                // 验证失败
                $this->error($this->_message(ACTION_NAME, $result['field']));
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function course_del()
    {
        try {
            if ($this->isPost() || $this->isAjax()) {
                $id = I('post.id');
                $params = ['id' => $id];
                $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
                if ($result['result'] === true) {
                    $courseModel = new CourseModelEdu();
                    if ($courseModel->where(['id' => ['eq', (int)$id]])->save(['status' => -7]) !== false) {
                        $this->ajaxReturn(['result' => true]);
                    }
                    $this->_throw($courseModel->getDbError());
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            }
            $this->_throw('非法请求！');
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage()
            ];
            $this->ajaxReturn($result);
        }
    }

    public function course_edit()
    {
        // model
        $courseModel = new CourseModelEdu();
        if ($this->isPost() || $this->isAjax()) {
            $params = I('post.');
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                if ($data = $courseModel->create($params, 2)) {
                    try {
                        // 开启
                        $courseModel->startTrans();
                        if ($_FILES['pic'] && $_FILES['pic']['error'] == 0) {
                            $fileInfo = $this->upload();
                            $data['pic'] = $fileInfo[0]['savepath'] . $fileInfo[0]['savename'];
                        }
                        if ($courseModel->save($data) !== false) {
                            // 提交
                            $courseModel->commit();
                            $this->redirect( U('educationview/course_index'));
                           // alert('success', '课程编辑成功', U('educationview/course_index'));
                        } else {
                            throw new Exception($courseModel->getError(), 414);
                        }
                    } catch (Exception $e) {
                        // 回滚
                        $courseModel->rollback();
                        $this->error($e->getMessage());
                    }
                } else {
                    $this->error($courseModel->getError());
                }
            } else {
                // 验证失败
                $this->error($this->_message(ACTION_NAME, $result['field']));
            }
        } else {
            $this->error('非法操作');
        }
    }

    public function course_index()
    {
        $wheredata = $_REQUEST;
        $courseModel = new CourseModelEdu();
        $lists = $courseModel->course_lists(null, $wheredata['page'], $wheredata['rows'])['data'];
        $count = $courseModel->course_lists(null, $wheredata['page'], $wheredata['rows'])['count'];
//        $this->ajaxReturn($wheredata['rows']);
        $this->ajaxReturn([
            'result' => true,
            'lists' => $lists,
            '_sql' => $courseModel->getLastSql(),
            'count' => $count,
            'total' => ceil($count / $wheredata['rows'])
        ]);
    }

    public function course_init()
    {
        // 分类参数
        $category = [];
        // 成员属性
        $member_type = [
            1 => '一对多',
            2 => '一对一',
        ];
        // 详情
        $info = [];
        // 课时列表
        $sectionList = [];
        //
        $pk = (int)I('get.id');
        if ($pk) {
            // 课程详情
            $courseModel = new CourseModelEdu();
            $info = $courseModel->course_lists(['c.id' => ['eq', $pk]]);
            $info && $info = $info[0];
            if ($info) {
                // 课程下课时列表
                $sectionModel = new SectionModelEdu();
                $sectionList = $sectionModel->sectionByCourseId($pk);
            }
        }
        $this->ajaxReturn(compact('category', 'member_type', 'info', 'sectionList'));

    }

    public function course_detail()
    {
        try {
            // 参数获取
            $id = (int)I('post.id');

            // 盘数判断
            !$id && $this->_throw('主键缺失');
            // 响应数据列表
            $detail = [];
            $sections = [];
            $sections_cate = [];
            $periods = [];
            $products = [];

            // 课程下的课时分类
            $sections_cate = (new SectionCateModelEdu())->where(['course_id'=>['eq',$id]])->select();
            if($sections_cate)
            {
                foreach ($sections_cate as $k => $v)
                {
                    $sections_cate[$k]['create_user'] = M('User')->where(['role_id'=>['eq',$v['create_user']]])->find()['full_name']?:'未知';
                    $sections_cate[$k]['create_time'] = ($v['create_time'])?date('Y-m-d H:i:s',$v['create_time']):' - ';
                }
            }

            // 课程详情
            $courseModel = new CourseModelEdu();
            $courseInfo = $courseModel->course_lists(['c.id' => ['eq', $id]]);
//            $this->ajaxReturn($courseInfo);
            $detail = $courseInfo['data'] ? $courseInfo['data'][0] : false;
            if ($detail) {
                // 课时数据
                $sectionModel = new SectionModelEdu();
                $sections = $sectionModel->sectionByCourseId($id);
                \Log::write($sectionModel->getLastSql());
                \Log::write(json_encode($sections));
                if($sections){
                    if(C('config_qiniu.start')){
                        $sections[0]['_video_path']='http://'.$sections[0]['video_path'];
                    }else{
                        $sections[0]['_video_path']=$sections[0]['video_path'];
                    }
                }

                \Log::write(json_encode($sections));

                // 课期数据
                $periodModel = new PeriodModelEdu();
                $periods = $periodModel->period_lists(['c.id' => ['eq', $id]]);
                // 所属产品
                $productModel = new CourseProductModelEdu();
                $products = $productModel->getCourseBlongsTo(['c_p.course_id' => ['eq', $id]]);
            }
            $result = true;
            $count['section'] = count($sections);
            $count['period'] = count($periods);
            $count['product'] = count($products);

            $_sql = $sectionModel->getDbError();

            foreach ($sections as $k => $v)
            {
                $sections[$k]['cate_name'] = (new SectionCateModelEdu())->find($v['cate'])['name']?:' - ';
            }

            $this->ajaxReturn(compact('result', 'detail', 'sections', 'sections_cate', 'periods', 'products', 'count', '_sql'));
        } catch (Exception $e) {
            $this->ajaxReturn([
                'result' => false,
                'error' => $e->getMessage(),
            ]);
        }

    }
    /* >>>>>>>>>>>>>>>>>>> 课程模块 end <<<<<<<<<<<<<<<<< */

    /* >>>>>>>>>>>>>>>>>>> 课时分类模块 start <<<<<<<<<<<<<<<<<<<< */
    public function section_cate_add()
    {
        // model
        if ($this->isPost() || $this->isAjax()) {
            $sectionModel = new SectionCateModelEdu();
            $params = I('post.');
            if ($params) {
                if ($data = $sectionModel->create($params, 1)) {
                    // TODO 操作者 操作时间
                    $params['create_user'] = session('role_id');
                    $params['create_time'] = time();
                    // 写库
                    if ($sectionModel->add($params)) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                }
            } else {
                // 验证失败
                $this->ajaxReturn([
                    'result' => false,
                    'error' => '参数异常'
                ]);
            }
        }
    }

    public function section_cate_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            $id = I('post.id');
            $params = ['id' => (int)$id];
            if ($params) {
                // model
                $sectionModel = new SectionCateModelEdu();
                try {
                    //      删除主信息
                    $sectionModel->where(['id' => ['eq', $id]])->delete();
                    $this->ajaxReturn(['result' => true]);
                } catch (Exception $e) {
                    $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                }
            } else {
                // 验证失败
                $this->ajaxReturn(['result' => false, 'error' => '']);
            }
        }
    }
    /* >>>>>>>>>>>>>>>>>>> 课时分类模块 end <<<<<<<<<<<<<<<<< */

    /* >>>>>>>>>>>>>>>>>>> 课时模块 start <<<<<<<<<<<<<<<<<<<< */
    public function section_add()
    {
        // model
        if ($this->isPost() || $this->isAjax()) {
            $sectionModel = new SectionModelEdu();
            $params = I('post.');

            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                if ($data = $sectionModel->create($params, 1)) {
                    // 检测课节编号是否存在
                    $yet = $sectionModel->field('id')
                        ->where(['course_id' => ['eq', $data['course_id']], 'node' => ['eq', $data['node']]])->find();
                    if ($yet) $this->ajaxReturn(['result' => false, 'error' => "课节{$data['node']}已存在，请合理调整"]);
                    // 写库
                    if ($sectionModel->add($params)) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                }
            } else {
                // 验证失败
                $this->ajaxReturn([
                    'result' => false,
                    'error' => $this->_message(ACTION_NAME, $result['field'])
                ]);
            }
        }
    }

    public function section_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            $id = I('post.id');
            $params = ['id' => (int)$id];
            // 参数验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $sectionModel = new SectionModelEdu();
                $scheduleModel = new ScheduleModelEdu();
                try {
                    // 开启
                    $sectionModel->startTrans();
                    // 处理
                    //删除课程视频
                    $section_info=$sectionModel->field('video_path')->where(['id' => ['eq', $id]])->find();
                    if(C('config_qiniu.start')){
                        require APP_PATH.'Lib/ORG/qiniu/autoload.php';
                        $accessKey = C('config_qiniu.accessKey');
                        $secretKey = C('config_qiniu.secretKey');
                        $auth = new \Qiniu\Auth($accessKey, $secretKey);
                        $bucket = C('config_qiniu.bucket');
                        $config = new \Qiniu\Config();
                        $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
                        $video_path=explode('/',$section_info['video_path']);
                        $key=$video_path[1];
                        $err = $bucketManager->delete($bucket, $key);
                    }else{
                        @unlink($section_info['video_path']);
                    }

                    //      删除主信息
                    $sectionModel->where(['id' => ['eq', $id]])->delete() === false && $this->_throw($sectionModel->getError());
                    //      删除排课信息
                    $scheduleModel->where(['section_id' => ['eq', $id]])->delete() === false && $this->_throw($scheduleModel->getError());
                    // 提交
                    $sectionModel->commit();
                    $this->ajaxReturn(['result' => true]);
                } catch (Exception $e) {
                    // 回滚
                    $sectionModel->rollback();
                    $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                }
            } else {
                // 验证失败
                $this->ajaxReturn(['result' => false, 'error' => '']);
            }
        }
    }

    public function get_section_cate()
    {
        $par = I();

        $where['course_id'] = $par['id'];

        if ($par['name']) $where['name'] = ['like','%'.$par['name'].'%'];

        $section_cate = (new SectionCateModelEdu())->where($where)->select();

        $this->ajaxReturn(['status'=>true,'data'=>$section_cate]);
    }

    public function section_edit()
    {
        $sectionModel = new SectionModelEdu();
        if ($this->isPost() || $this->isAjax()) {
            $params = I('post.');
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                if ($data = $sectionModel->create($params, 2)) {
                    // 检测课节编号是否存在
                    $info = $sectionModel->field('id,course_id,node')->find($data['id']);
                    $yet = $sectionModel->field('id')
                        ->where(['course_id' => ['eq', $info['course_id']], 'node' => ['eq', $data['node']]])->find();
                    if ($yet && ($data['id'] != $yet['id']))
                        $this->ajaxReturn(['result' => false, 'error' => "课节{$data['node']}已存在，请合理调整"]);
                    if ($sectionModel->save($data) !== false) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $sectionModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }
        }
    }

    public function section_index()
    {
        $course_id = I('course_id');
        if (!$course_id) $this->ajaxReturn(['result' => false, 'error' => '参数缺失']);

        $sectionModel = new SectionModelEdu();
        $sectionLists = $sectionModel->sectionByCourseId((int)$course_id);

        $this->ajaxReturn([
            'result' => true,
            'sectionLists' => $sectionLists,
        ]);
    }

    public function section_init()
    {
        $courseModel = new CourseModelEdu();
        $this->ajaxReturn([
            'course_list' => ''
        ]);
    }
    /* >>>>>>>>>>>>>>>>>>> 课时模块 end <<<<<<<<<<<<<<<<<<<< */


    /* >>>>>>>>>>>>>>>>>>> 课期模块 start <<<<<<<<<<<<<<<<<< */
    public function period_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数获取
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $periodModel = new PeriodModelEdu();
                if ($data = $periodModel->create($params, 1)) {
                    if ($periodModel->add($data)) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $periodModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $periodModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function period_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                // 参数接收
                $id = (int)I('post.id');
                if (!$id) $this->_throw('主键缺失');
                // model
                $periodModel = new PeriodModelEdu();
                $data = [
                    'id' => $id,
                    'status' => -7,
                ];
                // 写库
                if ($periodModel->save($data) !== false)
                    $this->ajaxReturn(['result' => true]);

                $this->_throw($periodModel->getError());

            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }

        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function period_edit()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数获取
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $periodModel = new PeriodModelEdu();
                if ($data = $periodModel->create($params, 1)) {
                    if ($periodModel->save($data) !== false) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $periodModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $periodModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function period_index()
    {
        $periodModel = new PeriodModelEdu();

        $lists = $periodModel->period_lists();

        $this->ajaxReturn([
            'lists' => $lists,
        ]);
    }

    public function period_init()
    {

    }

    public function period_detail()
    {
        if (!$this->isPost() || $this->isAjax()) {
            try {
                $period_id = (int)I('post.id');
                $period_id || $this->_throw('主键缺失');
                // 数据集
                $detail = [];
                $subStudent = [];
                $section = [];
                $scheduleYet = [];
                $count = [];
                $teacher = [];
                $notYetSchedule = [];
                // model
                $periodModel = new PeriodModelEdu();
                $studentModel = new StudentModelEdu();
                $sectionModel = new SectionModelEdu();
                $teacherModel = new TeacherModelEdu();

                // data
                $periods = $periodModel->period_lists(['c_per.id' => ['eq', $period_id]]);
                if ($periods) {
                    // 详情
                    $detail = $periods[0];
                    $course_id = $detail['course_id'];
                    // 班级下所有课程
                    $section = $sectionModel->field('id,name')
                        ->where(['course_id' => ['eq', $course_id]])->select();
                    // 班级下已排课的课程

                    $scheduleYet = $sectionModel->period_schedule(['c_sch.period_id' => ['eq', $period_id]]);

                    $now = time();
                    foreach ($scheduleYet as $key => $value) {
                        // 课时已过期
                        $scheduleYet[$key]['schedule_status'] = ($now < $value['stamp'])
                            ? 1
                            : 0;
                    }
                    // 班级下未排课时
                    $notYetSchedule = $this->notYetSchedule($section, $scheduleYet);
                    // 班级下学员信息
                    $subStudent = $periodModel->student_list(['p_s.period_id' => ['eq', $period_id]]);
                    // 未分班的学员
                    $notYetStudent = $studentModel->notYetToPeriodStudentList($course_id,$period_id);
                    // 讲师列表
                    $teacher = $teacherModel->teacher_lists('')['data'];
                    //数量
                    $count['scheduleYet'] = count($scheduleYet);
                    $count['subStudent'] = count($subStudent);
                    $count['section'] = count($section);
                    $count['notYetStudent'] = count($notYetStudent);

                    $this->ajaxReturn(array_merge(
                        ['result' => true],
                        compact('detail', 'student', 'section', 'teacher', 'scheduleYet', 'notYetSchedule', 'subStudent', 'count', 'notYetStudent')));
                }
                $this->_throw('课期(班级)不存在');
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    public function periodstudent_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                $params = I('post.');
                $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
                if ($result['result'] === true) {
                    // model
                    $PSModel = new PeriodStudentModelEdu();
                    // 写库
                    if ($data = $PSModel->create($params, 1)) {
                        if ($PSModel->add($data)) {
                            $this->ajaxReturn(['result' => true]);
                        }
                        $this->_throw($PSModel->getError(), 414);
                    }
                    $this->_throw($PSModel->getError());
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function periodstudent_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                $id = (int)I('post.id');
                $result = $this->_validator->batch_handle(['id' => $id], $this->_rules()[ACTION_NAME]);
                if ($result['result'] === true) {
                    // model
                    $PSModel = new PeriodStudentModelEdu();
                    // 删除
                    if ($PSModel->where(['id' => ['eq', $id]])->delete() !== false) {
                        $this->ajaxReturn(['result' => true]);
                    }
                    $this->_throw($PSModel->getError());
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }
    /* >>>>>>>>>>>>>>>>>>> 课期模块 end <<<<<<<<<<<<<<<<<<<< */


    /* >>>>>>>>>>>>>>>>>>> 排课模块 start <<<<<<<<<<<<<<<<<< */
    public function schedule_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数接收
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $scheduleModel = new ScheduleModelEdu();
                $periodModel = new PeriodModelEdu();
                $sectionModel = new SectionModelEdu();

                if ($data = $scheduleModel->create($params, 1)) {
                    // 验证数据关联是否合法
                    $periodInfo = $periodModel->field('course_id')
                        ->find($data['period_id']);
                    $sectionInfo = $sectionModel->field('course_id,title,duration')
                        ->find($data['section_id']);
                    if ($periodInfo['course_id'] != $sectionInfo['course_id']) {
                        $this->ajaxReturn(['result' => false, 'error' => '数据关联出错!!!']);
                    }
                    //入库之前进行api预约房间


                    $schedule_res = $scheduleModel->add($data);// 获取新增ID
                    // 入库
                    if ($schedule_res !== false) {

                        // 8-22 Talk-Cloud 对接
                        // 预约房间 start
                        $tk_room_data_type = (new CourseModelEdu())->where(['id'=>['eq',$periodInfo['course_id']]])->find()['member_type'];// 获取房间类型(一对一/一对多)

                        $tk_room_data['key'] = C('TK_ROOM.key');// 秘钥

                        // 准备预约房间接口所需参数
                        if ($tk_room_data_type == 2)
                        {
                            $tk_room_data['roomtype'] = 0;// 房间类型 一对一
                        }else{
                            $tk_room_data['roomtype'] = 3;// 房间类型 一对多
                        }
//                    $tk_room_data['roomtype'] = ($tk_room_data_type == 2) ? 0 : 3 ;
                        $tk_room_data['chairmanpwd'] = 'xy_laoshi';// 老师密码
                        $tk_room_data['assistantpwd'] = 'xy_zhujiao';// 助教密码
                        $tk_room_data['patrolpwd'] = 'xy_xunke';// 巡课密码
                        $tk_room_data['roomname'] = $sectionInfo['title'];// 房间名称
                        $tk_room_data['starttime'] = strtotime($params['start_time']);// 开始时间
                        $tk_room_data['endtime'] = (strtotime($params['start_time']) + (60 * $sectionInfo['duration']));// 结束时间
                        // 如果需要,房间的预约时间默认修改为 +- 10分钟
//                        $tk_room_data['starttime'] = (strtotime($params['start_time']) + 600);// 开始时间
//                        $tk_room_data['endtime'] = (strtotime($params['start_time']) + (60 * $sectionInfo['duration']) + 600);// 结束时间

                        $tk_send_url = C('TK_ROOM.url').'roomcreate';// 接口请求地址

                        $tk_roomcreate_res = curlPost($tk_send_url,'Content-type:application/x-www-form-urlencoded',$tk_room_data);// 发起预约房间的请求

                        if ($tk_roomcreate_res['result']) $this->ajaxReturn(['result' => false,'error'=>'拓课云房间创建失败,请联系管理员!']);// 如果请求失败

                        $tk_room_number = json_decode($tk_roomcreate_res['msg'])->serial;// 接口返回的房间号码

                        // 更新排课信息,插入 Talk-Cloud 房间号码
                        (new ScheduleModelEdu())->where(['id'=>['eq',$schedule_res]])->save(['serial'=>$tk_room_number]);
//                        echo "<pre>";
//                        var_dump($tk_roomcreate_res);// 预约房间的返回值
//                        var_dump($schedule_res);// 排课信息的数据ID
//                        var_dump($tk_room_number);exit();// 预约房间返回的房间号码

                        // 预约房间 end

                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $scheduleModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $scheduleModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }

        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function schedule_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数接收
            $id = (int)I('post.id');
            if (!$id) $this->ajaxReturn(['result' => false, 'error' => '主键缺失']);
            // model
            $scheduleModel = new ScheduleModelEdu();

            // 8-22 获取要删除排课的房间号码 start
            $tk_room_number = $scheduleModel->where(['id'=>['eq',$id]])->find()['serial'];
            // 获取房间号码 end

            // 写库
            if ($scheduleModel->where(['id' => ['eq', $id]])->delete()) {

                // 8-22 删除排课信息时,删除拓课云关联的房间 start
                $tk_send_url = C('TK_ROOM.url').'roomdelete';// 接口请求地址
                $tk_del_data['key'] = C('TK_ROOM.key');// 秘钥
                $tk_del_data['serial'] = $tk_room_number;// 要删除的房间号码
                // 发送请求
                $res = curlPost($tk_send_url,'Content-type:application/x-www-form-urlencoded',$tk_del_data);

//            echo "<pre>";
//            var_dump($tk_room_number);
//
//            echo "<pre>";
//            var_dump($tk_del_data);
//
//            echo "<pre>";
//            var_dump($res);exit();

                // 删除拓课云关联的房间 end

                $this->ajaxReturn(['result' => true]);
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $scheduleModel->getError()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function schedule_edit()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数接收
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $scheduleModel = new ScheduleModelEdu();
                $periodModel = new PeriodModelEdu();
                $sectionModel = new SectionModelEdu();

                if ($data = $scheduleModel->create($params, 2)) {
                    // 验证数据关联是否合法
                    $periodInfo = $periodModel->field('course_id')
                        ->find($data['period_id']);
                    $sectionInfo = $sectionModel->field('course_id')
                        ->find($data['section_id']);
                    if ($periodInfo['course_id'] != $sectionInfo['course_id']) {
                        $this->ajaxReturn(['result' => false, 'error' => '数据关联出错!!!']);
                    }
                    // 入库
                    if ($scheduleModel->save($data) !== false) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $scheduleModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $scheduleModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }

        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function schedule_index()
    {
        try {
            // 请求
            IS_AJAX || $this->_throw('非法操作');
            // 参数
            $schedule_id = (int)I('post.schedule_id');
            $schedule_id || $this->_throw('参数缺失');
            // 获取排课信息
            $scheduleModel = new ScheduleModelEdu();
            $_where = [
                'c_sch.id' => ['eq', $schedule_id],
            ];
            $signins = $scheduleModel->schedule_signin($_where);
            $_sql = $scheduleModel->getLastSql();
            $result = true;
            $opreate = [
                0 => '清除',
                1 => '签到',
                -7 => '请假',
            ];
            $this->ajaxReturn(compact('result', 'signins', 'opreate', '_sql'));

        } catch (Exception $e) {

        }
    }

    public function schedule_upload()
    {
        try {
            (IS_AJAX && IS_POST) || $this->_throw('非法请求');
            $params = I('post.');
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            $result['result'] || $this->_throw($this->_message(ACTION_NAME, $result['field']));
            $homeworkKey = 'homework';
            $videoKey = 'video';
            $oldVideoKey = 'old_video';
            if ($_FILES[$homeworkKey] && $_FILES[$homeworkKey]['error'] == 0) {
                $this->schedule_upload_homework($homeworkKey, $params['id']);

                $this->ajaxReturn(['result' => true]);
            }

            if ($_FILES[$oldVideoKey] && $_FILES[$oldVideoKey]['error'] == 0) {
                $this->schedule_upload_old_video($oldVideoKey, $params['id']);
                $this->ajaxReturn(['result' => true]);
            }
            if ($_FILES[$videoKey] && $_FILES[$videoKey]['error'] == 0) {
                $this->schedule_upload_video($videoKey, $params['id']);

                $this->ajaxReturn(['result' => true]);
            }
            $this->_throw('文件不合法');

        } catch (Exception $e) {

            $this->ajaxReturn([
                'result' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function schedule_after()
    {
        try {
            (IS_POST && IS_AJAX) || $this->_throw('非法请求');
            $date = I('post.date', date('Y-m-d 00:00:00', time()));
            $model = new PeriodModelEdu();
            $scheduleModel = new ScheduleModelEdu();
            $where['c_sch.start_time'] = ['gt', $date];
            $page = $_REQUEST['page'];
            $pagesize = $_REQUEST['rows'];
            $lists = $scheduleModel->schedule_lists($where,$page,$pagesize);
            $split = [];
            $marks = [];

            foreach ($lists['data'] as $value) {
                $split[date('Y-m-d', $value['stamp'])][$value['id']] = $value;
            }
            $dateKeys = array_keys($split);
            foreach ($dateKeys as $value) {
                $marks[$value] = '';
            }

            $this->ajaxReturn([
                'result' => true,
                'lists' => $split,
                'count'=>$lists['count'],
                'total'=>ceil(intval($lists['count'])/$pagesize),
                'marks' => $marks,
                '_sql' => $date,
                'page'=>$page
            ]);
        } catch (Exception $e) {
            $this->ajaxReturn([
                'result' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function schedule_upload_homework($homeworkKey, $schedule_id)
    {
        if (is_array($info = $this->uploadOne($homeworkKey, 'Schedule'))) {
            $path = $info[0]['savepath'] . $info[0]['savename'];
            $mateModel = new MaterialModelEdu();
            $scheduleModel = new ScheduleModelEdu();
            $info = $scheduleModel->schedule_list(['c_sch.id' => ['eq', $schedule_id]])[0];
            $data['path'] = $path;
            if ($info) {
                $data['name'] = "{$info['period_name']}({$info['section_name']})作业";
            }
            // 入材料表
            if (($id = $mateModel->add($data)) !== false) {
                // 更新排课表
                $scheduleModel->where(['id' => ['eq', $schedule_id]])->setField($homeworkKey, $id);
                return;
            }
            $this->_throw($mateModel->getDbError());
        }
        $this->_throw($info);
    }

    protected function schedule_upload_video($videoKey, $schedule_id)
    {
        if (is_array($info = $this->uploadOne($videoKey, 'Schedule'))) {
            $path = $info[0]['savepath'] . $info[0]['savename'];
            $mateModel = new MaterialModelEdu();
            $scheduleModel = new ScheduleModelEdu();
            $info = $scheduleModel->schedule_list(['c_sch.id' => ['eq', $schedule_id]])[0];
            $data['path'] = $path;
            if ($info) {
                $data['name'] = "{$info['period_name']}({$info['section_name']})视频";
            }
            // 入材料表
            if (($id = $mateModel->add($data)) !== false) {
                // 更新排课表
                $scheduleModel->where(['id' => ['eq', $schedule_id]])->setField($videoKey, $id);
                return;
            }
            $this->_throw($mateModel->getDbError());
        }
        $this->_throw($info);
    }

    protected function schedule_upload_old_video($videoKey, $schedule_id)
    {

        if(C('config_qiniu.start')){
            //测试七牛
            require APP_PATH.'Lib/ORG/qiniu/autoload.php';
            $accessKey = C('config_qiniu.accessKey');
            $secretKey = C('config_qiniu.secretKey');
            $auth = new \Qiniu\Auth($accessKey, $secretKey);
            $bucket = C('config_qiniu.bucket');
            // 生成上传Token
            $token = $auth->uploadToken($bucket);
            // 构建 UploadManager 对象
            $uploadMgr = new \Qiniu\Storage\UploadManager();
            if (is_array($info = $this->uploadOne($videoKey, 'temp_qiniu'))) {
                $path = $info[0]['savepath'] . $info[0]['savename'];
            }else{
                $this->_throw($info);
            }

            // 要上传文件的本地路径
            $filePath = $path;
            // 上传到七牛后保存的文件名
            $key = $_FILES[$videoKey]['name'];
            // 初始化 UploadManager 对象并进行文件的上传。
            // 调用 UploadManager 的 putFile 方法进行文件的上传。
            list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
            $path=C('config_qiniu.domain').'/'.$key;
        }else{
            if (is_array($info = $this->uploadOne($videoKey, 'Schedule_old_video'))) {
                $path = $info[0]['savepath'] . $info[0]['savename'];
            }else{
                $this->_throw($info);
            }
        }
        $scheduleModel = new SectionModelEdu();
        $scheduleModel->where(array('id'=>$schedule_id))->save(['video_path'=>$path]);
        return ;


    }



    public function notYetSchedule($sections, $yet)
    {
        $yetSectionId = array_column($yet, 'section_id');
        $notYet = array_filter($sections, function ($v) use ($yetSectionId) {
            return !in_array($v['id'], $yetSectionId);
        });

        return $notYet;
    }
    /* >>>>>>>>>>>>>>>>>>> 排课模块 end <<<<<<<<<<<<<<<<<<<< */


    /* >>>>>>>>>>>>>>>>>>> 学员签到 start <<<<<<<<<<<<<<<<<<<< */
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
                $student_id = (int)$params['student_id'];
                $schedule_id = (int)$params['schedule_id'];
                $status = $params['status'];
                $signinModel = new SigninModelEdu();
                $info = $signinModel->field('id')->where(['student_id' => ['eq', $student_id], 'schedule_id' => ['eq', $schedule_id]])
                    ->find();
                if ($info) {
                    $update['id'] = $info['id'];
                    $update['status'] = $status;
                    if ($signinModel->save($update) === false) {
                        $this->_throw($signinModel->getDbError());
                    }
                } else {
                    $create['student_id'] = $student_id;
                    $create['schedule_id'] = $schedule_id;
                    $create['status'] = $status;
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

    public function signin_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数接收
            $id = (int)I('get.id');
            if (!$id) $this->ajaxReturn(['result' => false, 'error' => '主键缺失']);
            // model
            $signinModel = new SigninModelEdu();
            // 写库
            if ($signinModel->where(['id' => ['eq', $id]])->delete()) {
                $this->ajaxReturn(['result' => true]);
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $signinModel->getError()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }
    /* >>>>>>>>>>>>>>>>>>> 学员签到 end <<<<<<<<<<<<<<<<<<<< */


    /* >>>>>>>>>>>>>>>>>>> 学员模块 start <<<<<<<<<<<<<<<<<< */
    public function student_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                $params = I('post.');
                //是否为空验证
                $addres = $this->checkaddinfo($params);
                if (!$addres['result']) {
                    $this->ajaxReturn($addres);
                }
                $result             =   $this->_validator->batch_handle( $params, $this->_rules()[ACTION_NAME] );
//                $this->ajaxReturn($result);
                if( $result['result'] === true ){
                // model
                $studentModel = new StudentModelEdu();
                $studentprofileModel = new StudentprofileModelEdu();
                // 写库
                if ($data = $studentModel->create($params, 1)) {
                    // 开启
                    $studentModel->startTrans();
                    if ($id = $studentModel->add($data)) {
                        $profile = ['student_id' => $id];
                        $studentprofileModel->field('student_id')->add($profile) === false
                        && $this->_throw($studentprofileModel->getDbError());
                        // 提交
                        $studentModel->commit();
                        $this->ajaxReturn(['result' => true]);
                    }
                    $this->_throw($studentModel->getError(), 414);

                }
                $this->_throw($studentModel->getError());
                }
//                else{
//                    $this->ajaxReturn(11);
//                }
            } catch (Exception $e) {
                if ($e->getCode() == 414)
                    $studentModel->rollback();
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    private function checkaddinfo($params)
    {
        if (empty($params['realname'])) {
            return ['result' => false, 'error' => '姓名不能为空'];
        } else if (empty($params['mobile'])) {
            return ['result' => false, 'error' => '手机不能为空'];
        } else if (empty($params['email'])) {
            return ['result' => false, 'error' => '邮箱不能为空'];
        } else if (empty($params['password'])) {
            return ['result' => false, 'error' => '密码不能为空'];
        }
        return ['result' => true];

    }

    public function student_del()
    {
//        $this->ajaxReturn(I('post.id'));
        try {
            if (IS_AJAX) {
                $id = I('post.id');
//                $this->ajaxReturn($id);
                $result = $this->_validator->batch_handle(['id' => ['in', $id]], $this->_rules()[ACTION_NAME]);

                if ($result['result'] === true) {
                    // model
                    $studentModel = new StudentModelEdu();
                    $studentPeriodModel = new PeriodStudentModelEdu();
                    $signinModel = new SigninModelEdu();
                    // 写库
                    //      开启事务
                    $studentModel->startTrans();
                    //      删除主信息
                    ($studentModel->where(['id' => ['in', $id]])->delete() === false)
                    && $this->_throw($studentModel->getDbError());
                    //      删除学员分班信息
                    ($studentPeriodModel->where(['student_id' => ['in', $id]])->delete() === false)
                    && $this->_throw($studentPeriodModel->getDbError());
                    //      删除学员签到信息
                    ($signinModel->where(['student_id' => ['in', $id]])->delete() === false)
                    && $this->_throw($signinModel->getDbError());

                    $studentModel->commit();
                    $this->ajaxReturn(['result' => true]);
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            }
            $this->_throw('非法操作');
        } catch (Exception $e) {
            $studentModel->rollback();
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            IS_AJAX && $this->ajaxReturn($result);
        }
    }

    public function student_edit()
    {
        try {
            if (IS_AJAX) {
                $params = I('post.');
                $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);

                if ($result['result'] === true) {
                    // model
                    $studentModel = new StudentModelEdu();
                    if ($data = $studentModel->create($params, 2)) {
                        if ($studentModel->save($data) !== false) {
                            $this->ajaxReturn(['result' => true]);
                        }
                        $this->_throw($studentModel->getDbError());
                    }
                    $this->_throw($studentModel->getDbError());
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            }
            $this->_throw('非法请求');
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            IS_AJAX && $this->ajaxReturn($result);
        }
    }

    public function student_index()
    {
//        $this->ajaxReturn(I('post.'));
        $wheredata = I('post.');
        $conditions = $this->student_condition(I('post.'));

        $studentModel = new StudentModelEdu();
        $courseModel = new CourseModelEdu();

        $lists = $studentModel->student_list($conditions, $wheredata['page'], $wheredata['rows']);

        $courses = $courseModel->field('id,name')
            ->where(['status' => ['eq', 1]])
            ->order('id desc')
            ->limit(20)
            ->select() ?: [];
        $periods = [];
        if ($conditions['c.id']) {
            $periodModel = new PeriodModelEdu();
            $periods = $periodModel->field('id,name,course_id')
                ->where(['status' => ['eq', 1], 'course_id' => ['eq', $conditions['c.id'][1]]])
                ->order('id desc')
                ->limit(10)
                ->select();
        }
        $_sql = $studentModel->_sql();
        $data['list'] = $lists;
        $data['count'] = $counts = count($studentModel->student_list($conditions));
        $data['total'] = ceil($counts / $wheredata['rows']);
//        $this->ajaxReturn(['status'=>true,'data'=>$data,'courses'=>$courses]);
        $this->ajaxReturn(compact('lists', 'courses', 'periods', 'conditions', '_sql'));
    }

    public function student_indexs()
    {
//        $this->ajaxReturn(I('post.'));
        $wheredata = I('post.');
        $conditions = $this->student_condition(I('post.'));
        $studentModel = new StudentModelEdu();
        $courseModel = new CourseModelEdu();
        if ($wheredata['course'] != '') {
            $conditions['c.id'] = $wheredata['course'];
        }
        if ($wheredata['lotwhere'] != '') {
            $condition['s.realname'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            if(is_array($studentModel->student_lists($condition, $wheredata['page'], $wheredata['rows'])['data'])){
                $conditions['s.realname'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            }
            $condition1['s.mobile'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            if(is_array($studentModel->student_lists($condition1, $wheredata['page'], $wheredata['rows'])['data'])){
                $conditions['s.mobile'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            }
        }
        if ($wheredata['livecontent'] != '') {
            $conditions['c_per.id'] = $wheredata['livecontent'];
        }

        $lists = $studentModel->student_lists($conditions, $wheredata['page'], $wheredata['rows'])['data'];

        $courses = $courseModel->field('id,name')
            ->where(['status' => ['eq', 1]])
            ->order('id desc')
            ->limit(20)
            ->select() ?: [];
        $periods = [];
        if ($conditions['c.id']) {
            $periodModel = new PeriodModelEdu();
            $periods = $periodModel->field('id,name,course_id')
                ->where(['status' => ['eq', 1], 'course_id' => ['eq', $conditions['c.id'][1]]])
                ->order('id desc')
                ->limit(10)
                ->select();
        }
        $_sql = $studentModel->_sql();
        $data['list'] = $lists;
        $data['count'] = $counts = $studentModel->student_lists($conditions)['count'];
        $data['total'] = ceil($counts / $wheredata['rows']);
        $this->ajaxReturn(['status' => true, 'data' => $data, 'courses' => $courses]);
        $this->ajaxReturn(compact('lists', 'courses', 'periods', 'conditions', '_sql'));
    }

    private function student_condition($params)
    {
        $conditions = [];
        // 课程id
        array_key_exists('course_id', $params)
        && ($course_id = (int)$params['course_id'])
        && $conditions['c.id'] = ['eq', $course_id];
        // 班级id
        array_key_exists('c.id', $conditions) && array_key_exists('period_id', $params)
        && ($period_id = (int)$params['period_id'])
        && $conditions['c_per.id'] = ['eq', $period_id];
        // search
        array_key_exists('search', $params)
        && ($search = $params['search'])
        && $conditions['s.realname|s.code|s.mobile'] = ['like', "%{$search}%"];

        return $conditions;
    }

    public function student_detail()
    {
        try {
            if (IS_AJAX) {
                // 参数接收
                $id = I('post.id');
                $id || $this->_throw('参数缺失');
                // model
                $studentModel = new StudentModelEdu();
                $courseModel = new CourseModelEdu();
                $periodModel = new PeriodModelEdu();
                // return
                $detail = [];
                $product = [];
                $course = [];
                $period = [];
                //
                $where = ['s.id' => ['eq', $id]];
                $detail = $studentModel->student_list($where);
                if ($detail) {
                    // 学生详情
                    $detail = $detail[0];
                    // 购买的课程
                    $course = $studentModel->course_list($where) ?: [];
                    $courseIds = array_map(function ($c) {
                        return $c['course_id'];
                    }, $course);
                    // 所在班级
                    $period = $periodModel->student_list(['s.id' => ['eq', $id]]);
                    // 购买的产品
                    $product = [];
                    foreach ($course as $key => $value) {
                        $product[$value['product_id']]['product_id'] = $value['product_id'];
                        $product[$value['product_id']]['product_name'] = $value['product_name'];
                        if ($value['course_id']) {
                            $product[$value['product_id']]['course'][$value['course_id']] = $value;
                        } elseif (!array_key_exists('course', $product[$value['product_id']])) {
                            $product[$value['product_id']]['course'] = [];
                        }
                    }
                }
                $result = true;
                $_sql = $periodModel->getDbError();
                $this->ajaxReturn(compact('result', 'detail', 'product', 'period', 'course', '_sql'));
            }
            $this->_throw('非法操作');
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            IS_AJAX && $this->ajaxReturn($result);
        }
    }

    /* >>>>>>>>>>>>>>>>>>> 学员模块 end <<<<<<<<<<<<<<<<<<<< */

    /* >>>>>>>>>>>>>>>>>>> 教师模块 start <<<<<<<<<<<<<<<<<< */
    public function teacher_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                $teacherModel = new TeacherModelEdu();
                $params = array_map('intval', I('post.'));
                $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
                if ($result['result'] === true) {
                    // 是否存在
                    $exists = $teacherModel->where(['user_id' => ['eq', $params['user_id']], 'role_id' => ['eq', $params['role_id']]])->find();
                    $exists && $this->_throw('教师信息已存在');
                    if ($data = $teacherModel->create($params, 1)) {
                        // 写库
                        if ($teacherModel->add($params) !== false) {
                            $this->ajaxReturn(['result' => true]);
                        }
                        $this->_throw($teacherModel->getError());
                    }
                    $this->_throw($teacherModel->getError());
                }
                // 验证失败
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    public function teacher_del()
    {
        try {
            if (IS_AJAX) {
                $id = (int)I('post.id');
                $result = $this->_validator->batch_handle(['id' => $id], $this->_rules()[ACTION_NAME]);
                if ($result['result'] === true) {
                    // model
                    $teacherModel = new TeacherModelEdu();
                    // 写库
                    if ($teacherModel->where(['id' => ['eq', $id]])->delete() !== false) {
                        $this->ajaxReturn(['result' => true]);
                    }
                    $this->_throw($teacherModel->getError());
                }
                $this->_throw($this->_message(ACTION_NAME, $result['field']));
            }
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            IS_AJAX && $this->ajaxReturn($result);
        }
    }

    public function teacher_index()
    {
        $wheredata = I('post.');
        $roles = array_filter(I('post.role_id'));
        $where = null;
        if ($roles)
            $where['tu.role_id'] = ['in', implode(',', $roles)];

        $search = I('post.search');
        if ($search) {
            $where['mxu1.full_name'] = ['like', ['%' . $search . '%']];
        }

        $teacherModel = new TeacherModelEdu();
        $lists = $teacherModel->teacher_lists($where,$wheredata['page'],$wheredata['rows'])['data'];
        $counts = $teacherModel->teacher_lists($where)['count'];
        if (!is_null($where) && array_key_exists('tu.role_id', $where) && $lists)
            $lists = array_filter($lists, function ($v) use ($roles) {
                return $v['role_count'] >= count($roles);
            });

        $this->ajaxReturn([
            'result' => true,
            'lists' => $lists,
            'count'=>$counts,
            'total'=>ceil(intval($counts)/$wheredata['rows']),
            '_sql' => $teacherModel->getLastSql()
        ]);
    }

    public function teacher_role()
    {
        $user_id = I('post.user_id');
        $user_id || $this->ajaxReturn(['result' => false, 'error' => '参数缺失']);
        $roleModel = new RoleModelEdu();
        $teacherModel = new TeacherModelEdu();
        $roles = $roleModel->field('id,name')->select();
        $ownRoles = $teacherModel->teacher_roles(['t_u.user_id' => ['eq', $user_id]]) ?: [];
        $ownRoleRal = [];
        foreach ($ownRoles as $value) {
            $ownRoleRal[$value['role_id']] = $value;
        }

        foreach ($roles as $key => $value) {
            if (array_key_exists($value['id'], $ownRoleRal)) {
                $roles[$key]['info'] = $ownRoleRal[$value['id']];
            } else {
                $roles[$key]['info'] = false;
            }
        }

        $this->ajaxReturn(['result' => true, 'roles' => $roles]);
    }

    public function teacher_detail()
    {
        try {
            IS_AJAX || $this->_throw('非法操作');
            $params = I('post.');
            $teacherdetailModel = new TeacherdetailModelEdu();
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                if ($data = $teacherdetailModel->create($params, 2)) {
                    // 开启事务
                    $teacherdetailModel->startTrans();
                    // 是否有文件
                    import('@.ORG.UploadFile');
                    $upload = new UploadFile();// 实例化上传类
                    $upload->maxSize = 3145728000;// 设置附件上传大小
                    $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg', 'mp4');// 设置附件上传类型
                    $upload->savePath = "./Uploads/Teacher/";// 设置附件上传目录

                    if ($_FILES['pic'] && $_FILES['pic']['error'] == 0) {
                        $fileInfo = $upload->uploadOne($_FILES['pic']);
                        $data['fine_pic'] = $fileInfo[0]['savepath'] . $fileInfo[0]['savename'];
                    }
                    if ($_FILES['video'] && $_FILES['video']['error'] == 0) {
                        $fileInfo = $upload->uploadOne($_FILES['video']);
                        $data['video_intro'] = $fileInfo[0]['savepath'] . $fileInfo[0]['savename'];
                    }

                    if ($teacherdetailModel->find($data['user_id'])) {
                        // 修改
                        if ($teacherdetailModel->save($data) !== false) {
                            $teacherdetailModel->commit();
                            $this->ajaxReturn(['result' => true]);
                        }
                        $this->_throw($teacherdetailModel->getDbError());
                    } else {
                        //新增
                        if ($teacherdetailModel->add($data)) {
                            $teacherdetailModel->commit();
                            $this->ajaxReturn(['result' => true]);
                        }
                        $this->_throw($teacherdetailModel->getDbError());
                    }
                }
                $this->_throw($teacherdetailModel->getDbError());
            }
            $this->_throw($this->_message(ACTION_NAME, $result['field']));
        } catch (Exception $e) {
            $teacherdetailModel->rollback();
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            $this->ajaxReturn($result);
        }
    }

    public function teacher_livepics()
    {
        try {
            $user_id = I('post.user_id');
            $user_id || $this->_throw('参数缺失');
            $info = M('User')->field('user_id, full_name')->where("user_id={$user_id} AND status=1")->find();
            $info || $this->_throw('用户不存在');

            $picModel = new TeacherpicModelEdu();
            $pics = $picModel->teacher_pics(['t_pic.user_id' => ['eq', $user_id]]) ?: [];
            $result = true;
            $this->ajaxReturn(compact('info', 'pics', 'result'));
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            $this->ajaxReturn($result);
        }
    }

    public function teacher_addpic()
    {
        try {
            $params = I('post.');
            $picModel = new TeacherpicModelEdu();
            $mateModel = new TeachermateModelEdu();
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                $fileKeys = 'pic';
                if ($_FILES[$fileKeys] && $_FILES[$fileKeys]['error'] == 0) {
                    // 开启事务
                    $picModel->startTrans();
                    //      文件上传
                    $fileInfo = $this->uploadOne($fileKeys, 'Teacher');
                    is_array($fileInfo) || $this->_throw('文件上传出错');
                    $filepath = $fileInfo[0]['savepath'] . $fileInfo[0]['savename'];
                    //      素材数据写入
                    $matedata['name'] = $params['name'];
                    $matedata['path'] = $filepath;
                    ($id = $mateModel->add($matedata)) || $this->_throw($mateModel->getDbError());
                    $picdata['user_id'] = (int)$params['user_id'];
                    $picdata['mate_id'] = $id;
                    ($picModel->add($picdata) !== false) || $this->_throw($picModel->getDbError());

                    //提交
                    $picModel->commit();
                    $this->ajaxReturn(['result' => true, 'data' => $picdata]);
                }
                $this->_throw("文件不合法");
            }
            $this->_throw($this->_message(ACTION_NAME, $result['field']));
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            $picModel->rollback();

            $this->ajaxReturn($result);
        }
    }

    public function teacher_delpic()
    {
        try {
            ($id = (int)I('post.id')) || $this->_throw('参数缺失');
            $picModel = new TeacherpicModelEdu();
            $mateModel = new TeachermateModelEdu();
            $picModel->startTrans();
            // 删除关联
            $picModel->where("mate_id={$id}")->delete() !== false || $this->_throw($picModel->getDbError());
            // 删除材料、磁盘信息
            $mate = $mateModel->field('path')->find($id);
            $mate || $this->_throw('文件不存在');
            $mateModel->delete($id) !== false || $this->_throw($mateModel);
            file_exists($mate['path']) && unlink($mate['path']);
            $picModel->commit();
            $this->ajaxReturn(['result' => true]);
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage(),
            ];
            $picModel->rollback();
            $this->ajaxReturn($result);
        }
    }

    public function teacher_init()
    {
    }

    /* >>>>>>>>>>>>>>>>>>> 教师模块 end <<<<<<<<<<<<<<<<<<<< */

    /* >>>>>>>>>>>>>>>>>>> 产品、课程 start <<<<<<<<<<<<<<<<<<< */
    public function courseproduct_add()
    {
        if ($this->isPost() || $this->isAjax()) {
            // 参数获取
            $params = I('post.');
            // 验证
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            if ($result['result'] === true) {
                // model
                $courseProductModel = new CourseProductModelEdu();
                if ($data = $courseProductModel->create($params, 1)) {
                    // 验证关联是否存在
                    $info = $courseProductModel->where(['product_id' => ['eq', $data['product_id']], 'course_id' => ['eq', $data['course_id']]])
                        ->find();
                    if ($info) {
                        $this->ajaxReturn(['result' => false, 'error' => '该产品已包含该课程']);
                    }
                    if ($courseProductModel->add($data)) {
                        $this->ajaxReturn(['result' => true]);
                    } else {
                        $this->ajaxReturn(['result' => false, 'error' => $courseProductModel->getError()]);
                    }
                } else {
                    $this->ajaxReturn(['result' => false, 'error' => $courseProductModel->getError()]);
                }
            } else {
                $this->ajaxReturn(['result' => false, 'error' => $this->_message(ACTION_NAME, $result['field'])]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function courseproduct_del()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                // 参数接收
                $id = (int)I('post.id');
                $id || $this->_throw('主键缺失');
                // model
                $courseProductModel = new CourseProductModelEdu();
                // 写库
                if ($courseProductModel->where(['id' => ['eq', $id]])->delete())
                    $this->ajaxReturn(['result' => true]);

                $this->_throw($courseProductModel->getError());
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        } else {
            $this->ajaxReturn(['result' => false, 'error' => '非法操作']);
        }
    }

    public function getCP_init()
    {
    }

    /**
     * @ 获取产品下课程信息
     */
    public function productIncludeCourse()
    {
        if ($this->isPost() || $this->isAjax()) {
            try {
                // 获取参数
                $product_id = (int)I('post.product_id');
                $product_id || $this->_throw('参数缺失');
                // model
                $CPModel = new CourseProductModelEdu();
                $where['c_p.product_id'] = ['eq', $product_id];
                $courses = $CPModel->getProductIncludes($where);
                $count = count($courses);

                $this->ajaxReturn(['result' => true, 'courses' => $courses, 'count' => $count, '_sql' => $CPModel->getLastSql()]);
            } catch (Exception $e) {
                $this->ajaxReturn(['result' => false, 'error' => $e->getMessage()]);
            }
        } else {

        }
    }
    /* >>>>>>>>>>>>>>>>>>> 产品、课程 end <<<<<<<<<<<<<<<<<<< */
    /**
     * @param $roleIds
     * @param int $role 1.教师 2.学员
     * @param string $message
     * @param int $type 1.站内信 2.邮件 4.短信
     */
    public function notify()
    {
        // 1.员工（教师、顾问...） 2.学员 3.系统
        try {
            $params = I('post.');
            $result = $this->_validator->batch_handle($params, $this->_rules()[ACTION_NAME]);
            $result['result'] || $this->_throw($this->_message(ACTION_NAME, $result['field']));
            // 发送类型
            $send_type = array_sum(array_map('intval', $params['send_types'])) ?: 1;
            // 接收人
            $roleIds = array_map('intval', $params['roleIds']);
            // 接收人角色
            $role = (int)$params['role'] ?: 1;
            // 标题
            $title = trim($params['title']);
            // 内容
            $message = trim($params['message']);

            $roleInfo = [];
            $mails = [];
            $mobiles = [];
            switch ($role) {
                case 2:
                    $roleModel = new StudentModelEdu();
                    $roleInfo = $roleModel->field('realname,email mail,mobile')
                        ->where(['id' => ['in', $roleIds]])->select();
                    break;
                case 1:
                    $roleModel = M('User');
                    $roleInfo = $roleModel->field('full_name,email mail,telephone mobile')
                        ->where(['user_id' => ['in', $roleIds]])->select();
                    break;
            }
            $mails = array_map(function ($v) {
                return $v['mail'];
            }, $roleInfo);
            $mobiles = array_map(function ($v) {
                return $v['mobile'];
            }, $roleInfo);
//            $this->ajaxReturn($send_type);
            switch ($send_type) {
                case 1:
                    $this->letter_notify($roleIds, $role, $message, $title);
                    break;
                case 2:
                    $this->mail_notify($mails, $title, $message);
                    break;
                case 3:
                    $this->letter_notify($roleIds, $role, $message, $title);
                    $this->mail_notify($mails, $title, $message);
                    break;
                case 4:
//                    foreach ($mobiles as $k=>$v){
//                        $this->sms_notify($v, $message);
//                        sleep(1);
//                    }
                    $this->sms_notify($mobiles, $message);

                    break;
                case 5:
                    $this->letter_notify($roleIds, $role, $message, $title);
                    $this->sms_notify($mobiles, $message);
//                    foreach ($mobiles as $k=>$v){
//                        $this->sms_notify($v, $message);
//                        sleep(1);
//                    }
                    break;
                case 6:
                    $this->mail_notify($mails, $title, $message);
                    $this->sms_notify($mobiles, $message);
//                    foreach ($mobiles as $k=>$v){
//                        $this->sms_notify($v, $message);
//                    }
                    break;
                default:
                    $a = $this->sms_notify($mobiles, $message);
//                    $this->ajaxReturn($a);
                    $this->letter_notify($roleIds, $role, $message, $title);
                    $this->mail_notify($mails, $title, $message);

//                    foreach ($mobiles as $k=>$v){
//                        $a = $this->sms_notify($v, $message);
////                        $this->ajaxReturn($a);
//                    }
                    break;
            }
            $this->ajaxReturn(['result' => true]);
        } catch (Exception $e) {
            $result = [
                'result' => false,
                'error' => $e->getMessage()
            ];

            $this->ajaxReturn($result);
        }
    }

    public function letter_notify($roleIds, $role, $message, $title = '')
    {
        if ($role == 1) {
            $tpl['from_role_id'] = session('role_id');
            $tpl['content'] = $message;
            $tpl['send_time'] = time();

            $all_create = [];
            foreach ($roleIds as $key => $value) {
                $tpl['to_role_id'] = (int)$value;
                $all_create[] = $tpl;
            }
            $model = M('Message');
            $model->addAll($all_create) !== false || $this->_throw($model->getDbError());
            return true;
        } else {
            $tpl['from_user_id'] = session('user_id');
            $tpl['from_type'] = $role;
            $tpl['message'] = $message;
            $tpl['title'] = $title;
            $all_create = [];
            foreach ($roleIds as $key => $value) {
                $tpl['student_id'] = (int)$value;
                $all_create[] = $tpl;
            }
            $model = new LetterModelEdu();
            $model->addAll($all_create) !== false || $this->_throw($model->getDbError());
            return true;
        }
    }



    public function mail_notify($to, $title, $message)
    {
        if (!$to) $this->_throw('请补全接收邮件者的邮件地址');
        import("@.ORG.Mail");
        $mail = new \PHPMailer(true);
        $setting = M('Config')->field('value')->where(['name' => ['eq', 'smtp']])->find();
        $setting = unserialize($setting['value']);
        C($setting, 'smtp');

        $mail->IsSMTP();
        $mail->CharSet = C('MAIL_CHARSET');
        if (is_array($to)) {
            foreach ($to as $mailto) {
                if ($mailto) {
                    $mail->AddAddress($mailto);
                }
            }
        } else {
            $mail->AddAddress($to);
        }
        $mail->Body = $message;
        $mail->From = C('MAIL_ADDRESS');
        $mail->FromName = '小莺出国';
        $mail->Subject = $title;
        $mail->Host = C('MAIL_SMTP');
        $mail->SMTPAuth = C('MAIL_AUTH');
        $mail->Port = C('MAIL_PORT');
        $mail->SMTPSecure = C('MAIL_SECURE');
        $mail->Username = C('MAIL_LOGINNAME');
        $mail->Password = C('MAIL_PASSWORD');
        $mail->IsHTML(true);
        $mail->MsgHTML($message);
        if ($mail->send()) return true;
        $this->_throw($mail->ErrorInfo);
        return false;
    }

    public function sms_notify($mobiles, $message)
    {
        $aa = [];
            foreach($mobiles as $k=>$v){
                $url = "http://api.smsbao.com/sms?u=everelite&p=" . md5('invY1234') . "&m={$v}&c={$message}";
                $send_res = file_get_contents($url);
                $aa[] = $send_res['result'];

//                sleep(2);
            }
//        return $aa;

//            return $send_res['result'];
    }

    protected function upload($path = 'Course')
    {
        import('@.ORG.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->maxSize = 3145728000;// 设置附件上传大小
        $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg', 'mp4');// 设置附件上传类型
        $upload->savePath = "./Uploads/{$path}/";// 设置附件上传目录
        $upload->autoSub = true;
        $upload->subType = 'date';
        $upload->dateFormat = 'Y-m-d';
        if ($upload->upload() === true)
            return $upload->getUploadFileInfo();
        return $upload->getErrorMsg();
    }

    protected function uploadOne($fileKey, $path = 'Course')
    {
        import('@.ORG.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload->maxSize = 3145728000;// 设置附件上传大小
        $upload->allowExts = C('homework_type');// 设置附件上传类型
        $upload->autoSub = true;
        $upload->subType = 'date';
        $upload->dateFormat = 'Y-m-d';
        $upload->savePath = "./Uploads/{$path}/";// 设置附件上传目录
        if (($info = $upload->uploadOne($_FILES[$fileKey])) !== false)
            return $info;
        return $upload->getErrorMsg();
    }

    protected function _rules()
    {
        return [
            /* notify */
            'notify' => [
                'roleIds' => 'required|min:1',
                'title' => 'required',
                'message' => 'required|max:3000',
            ],
            /* course */
            'course_add' => [
                'name' => 'required|max:60',
                'member_type' => 'required|in:1,2',
            ],
            'course_del' => [
                'id' => 'required',
            ],
            'course_edit' => [
                'id' => 'required',
                'name' => 'required|max:60',
                'member_type' => 'required|in:1,2',
            ],
            /* section */
            'section_add' => [
                'course_id' => 'required',
                'name' => 'required|max:60',
                'node' => 'required',
                'title' => 'required|max:300',
                'duration' => 'required|max:5',
            ],
            'section_del' => [
                'id' => 'required',
            ],
            'section_edit' => [
                'id' => 'required',
                'name' => 'required|max:60',
                'node' => 'required',
                'title' => 'required|max:300',
                'duration' => 'required|max:5',
            ],
            'section_index' => [
                'course_id' => 'required',
            ],
            /* period */
            'period_add' => [
                'name' => 'required|max:60',
                'course_id' => 'required',
                'headmaster_id' => 'required',
            ],
            'period_del' => [
                'id' => 'required',
            ],
            'period_edit' => [
                'id' => 'required',
                'name' => 'required|max:60',
                'headmaster_id' => 'required',
            ],
            /* schedule */
            'schedule_add' => [
                'period_id' => 'required',
                'section_id' => 'required',
                'teacher_id' => 'required',
                'start_time' => 'required',
            ],
            'schedule_del' => [
                'id' => 'required',
            ],
            'schedule_upload' => [
                'id' => 'required',
            ],
            'schedule_edit' => [
                'id' => 'required',
                'period_id' => 'required',
                'section_id' => 'required',
                'teacher_id' => 'required',
                'start_time' => 'required',
            ],
            /* signin */
            'signin_in' => [
                'schedule_id' => 'required',
                'student_id' => 'required',
                'status' => 'required|in:0,1,-7',
            ],
            'signin_del' => [
                'id' => 'required',
            ],
            'signin_edit' => [
                'id' => 'required',
                'schedule_id' => 'required',
                'student_id' => 'required',
            ],
            /* courseproduct */
            'courseproduct_add' => [
                'product_id' => 'required',
                'course_id' => 'required',
            ],
            'courseproduct_del' => [
                'id' => 'required',
            ],
            /* teacher */
            'teacher_add' => [
                'user_id' => 'required',
                'role_id' => 'required',
            ],
            'teacher_del' => [
                'id' => 'required',
            ],
            'teacher_livepics' => [
                'user_id' => 'required',
            ],
            'teacher_addpic' => [
                'user_id' => 'required',
                'name' => 'max:100',
            ],
            'teacher_delpic' => [
                'id' => 'required',
            ],
            'teacher_detail' => [
                'user_id' => 'required',
                'realname' => 'required',
                'year' => 'required',
                'birthday' => 'required',
                'university' => 'required',
                'major' => 'required',
                'edu_level' => 'required',
                'special' => 'required',
                'point' => 'required',
                'detail' => 'required',
            ],
            /* student */
            'student_add' => [
                'realname' => 'required',
                'mobile' => 'required',
                'email' => 'required|email',
                'customer_id' => 'required',
                'password' => 'required|max:30',
                'remark' => 'max:3000',
            ],
            'student_edit' => [
                'id' => 'required',
                'realname' => 'required',
                'mobile' => 'required|phone',
                'email' => 'required|email',
                'password' => 'min:6|max:30',
                'remark' => 'max:3000',
            ],
            /* period_student */
            'periodstudent_add' => [
                'period_id' => 'required',
                'student_id' => 'required',
            ],
            'periodstudent_del' => [
                'id' => 'required',
            ],
        ];
    }

    protected function _message($action_name, $field)
    {
        $lists = [
            'course' => [
                'id' => '主键缺失',
                'name' => '名称不合法',
                'member_type' => '所选成员类型不合法',
                'detail' => '详情过长',
            ],
            'section' => [
                'course_id' => '课程主键缺失',
                'name' => '名称不合法',
                'title' => '标题不合法',
                'duration' => '课时时长不合法',
            ],
        ];

        $msgMode = explode('_', $action_name)[0];
        if (array_key_exists($msgMode, $lists) && array_key_exists($field, $lists[$msgMode]))
            return $lists[$msgMode][$field];

        return '操作出错:' . $field;
    }

    protected function _throw($message, $code = 414)
    {
        throw new Exception($message, $code);
    }

    public function getperbycourse()
    {
        $course = I('course');
        $periodModel = new PeriodModelEdu();
        $periods = $periodModel->field('id,name,course_id')
            ->where(['status' => ['eq', 1], 'course_id' => ['eq', $course]])
            ->order('id desc')
//            ->limit(10)
            ->select();
        $this->ajaxReturn($periods);
    }

}