<?php

class MaterialsAction extends Action
{

    /**
     * 权限控制 暂未开通
     * @permission 无限制
     * @allow 登录用户可访问的内容
     * @other 其他根据系统权限设置
     **/
//    public function _initialize(){
//        $action = array(
//            'permission'=>array(),
//            'allow'=>array('getcurrentstatus')
//        );
//        B('Authenticate', $action);
//        $this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
//    }


    /**
     * 材料管理 (标签/材料名)
     * ===========================================================================
     */
    public function index()
    {
        if ($this->isAjax()){
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件
            if ($name) $condition['name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            $list = M('Materials')->where($condition)->limit($start,$limit)->select();
            $count = M('Materials')->where($condition)->count();

            if ($list) // 按照前端所需格式拼接下载文件名称
            {
                foreach ($list as $k => $v)
                {
                    $list[$k]['name'] = $v['name'].'.'.trim(strrchr($v['file'], '.'),'.');
                }
            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('materials');
    }

    /**
     * 详情
     */
    public function materialsDetail()
    {
        // 获取关键参数
        if (!($id = I('get.id'))) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);

        // 获取详情
        $info = M('Materials')->find($id);

        if (!$info) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);

        $info['apply_name'] = M('MaterialsApply')->field('project_name')->where('id='.$info['program_id'])->find()['project_name'];
        $info['create_time'] = date('Y-m-d H:i:s',$info['create_time']);

        $this->ajaxReturn(['status'=>true,'data'=>$info]);
    }

    /**
     * 添加
     */
    public function materialsAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.')) || !($id = I('post.program_id'))) die('缺少参数');

            // 是否上传文件
            if (!$_FILES['file']['size']) die('文件未上传');

            // 过滤数据

            // 上传文件
            $file = $this->uploadMaterials();

            // file 字段赋值
            if (!($data['file'] = $file)) die('文件上传有误');

            $data['create_time'] = time();

            // 执行添加操作
            try {
                $res = M('Materials')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=applyinfo&id='.$id);

        }

        // 获取材料分类
        $cates = M('MaterialsSampleCate')->select();
        // 获取项目
        $programs = $this->getPrograms();

        $this->assign([
            'cates' => $cates,
            'programs' => $programs
        ]);
        $this->display('materialsAdd');
    }

    /**
     * 修改
     */
    public function materialsEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 读取数据
            $project_id = M('Materials')->find($id)['program_id'];

            if (!$project_id) die('非法操作');// 数据不存在

            // 如果更新文件
            if ($_FILES['file']['size']) {
                $file = $this->uploadMaterials();
                $data['file'] = $file;
            }

            // 执行更新操作
            try {
                M('Materials')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=applyinfo&id='.$project_id);

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取分类
        $cates = M('MaterialsCate')->select();
        // 获取详情
        $info = M('Materials')->find($id);

        // 如果是从项目中进入编辑页面
        if ($project_id = I('get.project'))
        {
            $this->project_id = $project_id;
        }

        $this->assign([
            'info' => $info,
            'cates' => $cates,
        ]);

        $this->display('materialsEdit');
    }

    /**
     * 删除
     */
    public function materialsDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('Materials')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    /**
     * TODO 待修改 材料样本API
     * ===========================================================================
     */
    public function materialsSample()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件
            if ($name) $condition['name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            if ($cate = I('post.c')) $condition['cate_id'] = $cate;// 如果按分类查询

            // 查询结果
            $list = M('MaterialsSample')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsSample')->where($condition)->count();

            if ($list) // 按照前端所需格式拼接下载文件名称
            {
                foreach ($list as $k => $v)
                {
                    $list[$k]['name'] = $v['name'].'.'.trim(strrchr($v['file'], '.'),'.');
                }
            }

            $data['cates'] = M('MaterialsSampleCate')->select();
            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('materialsSample');
    }

    /**
     * 详情
     */
    public function materialsSampleDetail()
    {
        // 获取关键参数
        if (!($id = I('get.id'))) $this->ajaxReturn(['status'=>false, 'data'=>'缺少关键参数']);

        // 获取详情
        $info = M('MaterialsSample')->find($id);

        if (!$info) $this->ajaxReturn(['status'=>false, 'data'=>'数据为空']);

        $info['create_time'] = date('Y-m-d H:i:s',$info['create_time']);
        $info['cate_name'] = M('MaterialsSampleCate')->field('name')->where('id='.$info['cate_id'])->find()['name'];

        $this->ajaxReturn(['status'=>true, 'data'=>$info]);
    }

    /**
     * 添加
     */
    public function materialsSampleAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.'))) die('缺少参数');
            // 是否上传文件
            if (!$_FILES['file']['size']) die('样本文件未上传');

            // 过滤数据

            // 上传文件
            $file = $this->uploadMaterials();

            // file 字段赋值
            if (!($data['file'] = $file)) die('文件上传有误');

            $data['create_time'] = time();

            // 执行添加操作
            try {
                $res = M('MaterialsSample')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=materialsSample');

        }

        // 获取样本分类
        $cates = M('MaterialsSampleCate')->select();

        $this->assign([
            'cates' => $cates
        ]);
        $this->display('materialsSampleAdd');
    }

    /**
     * 修改
     */
    public function materialsSampleEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 如果更新文件
            if ($_FILES['file']['size']) {
                $file = $this->uploadMaterials();
                $data['file'] = $file;
            }

            // 执行更新操作
            try {
                M('MaterialsSample')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=materialsSample');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取课程分类
        $cates = M('MaterialsSampleCate')->select();
        // 获取课程详情
        $info = M('MaterialsSample')->find($id);

        $this->assign([
            'info' => $info,
            'cates' => $cates
        ]);
        $this->display('materialsSampleEdit');
    }

    /**
     * 删除
     */
    public function materialsSampleDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 要删除的数据是否存在
        $materials = M('MaterialsSample')->field('file')->where('id in(' . $id . ')')->select();

        // 执行删除
        try {
            $res = M('MaterialsSample')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
            for ($i = 0; $i < count($materials); $i++) {
                unlink($materials[$i]['file']);
            }
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    
    /**
     * 申请管理API
     * ===========================================================================
     */
    public function apply()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $cate = $wheredata['c'] ? $wheredata['c'] : 1;// 申请类别标签 1=普通申请 2=COE申请 3=签证申请
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $sort_field = $wheredata['sidx'] ?: 0;// 排序规则
            $sort = $wheredata['sord'] ?: 0;// 排序规则
            $start = ($page - 1) * $limit;// 查询起始值
            $condition['tag'] = ['eq',$cate];// 查询条件

            // 如果按申请年份搜索
            if ($sort_field == 'apply_date') $sort_field = 'join_year';

            
            if ($name)// 如果按关键字查询
            {
                // 如果需要排序
                if ($sort && $sort_field)
                {
                    // 查询结果
                    $list = M('MaterialsApply')->where($condition)->where("`sname` LIKE '%".$name."%' OR `project_name` LIKE '%".$name."%' ")->order("{$sort_field} {$sort}")->limit($start,$limit)->select();
                }else{
                    // 查询结果
                    $list = M('MaterialsApply')->where($condition)->where("`sname` LIKE '%".$name."%' OR `project_name` LIKE '%".$name."%' ")->order('create_time desc')->limit($start,$limit)->select();
                }
                $count = M('MaterialsApply')->where($condition)->where("`sname` LIKE '%".$name."%' OR `project_name` LIKE '%".$name."%' ")->count();

            }else{// 普通查询
                // 如果需要排序
                if ($sort && $sort_field)
                {
                    // 查询结果
                    $list = M('MaterialsApply')->where($condition)->order("{$sort_field} {$sort}")->limit($start,$limit)->select();
                }else{
                    // 查询结果
                    $list = M('MaterialsApply')->where($condition)->order('create_time desc')->limit($start,$limit)->select();
                }
                $count = M('MaterialsApply')->where($condition)->count();
            }

            if ($list)
            {
                foreach ($list as $k => $v)
                {
                    $list[$k]['apply_date'] = $v['join_year'];
                    if ( strlen($v['join_mouth']) > 1 )
                    {
                        $list[$k]['apply_date'] .= $v['join_mouth'];
                    }else{
                        $list[$k]['apply_date'] .= '0'.$v['join_mouth'];
                    }
                }
            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('apply');

    }

    /**
     * 详情
     */
    public function applyinfo()
    {
        if (!$id = I('get.id')) die('缺少关键参数'); // 接收参数

        $info = M('MaterialsApply')->find($id); // 申请详情

        if (!$info) die('数据不存在'); // 数据为空

        $ids = implode(',',json_decode($info['materials'])); // 需要提交的材料的 ids

        $materials = M('MaterialsSample')->field('name')->where(['id'=> ['IN',$ids]])->select(); // 需要提交的材料 ID 转 名称

        // 获取当前申请已提交的材料
        $list = D('Materials')->getMaterials('program_id='.$id);

        if ($list) // 按照前端所需格式拼接下载文件名称
        {
            foreach ($list as $k => $v)
            {
                $list[$k]['name'] = $v['name'].'.'.trim(strrchr($v['file'], '.'),'.');
            }
        }

        $count = count($list);

        // 分配并返回视图文件
        $this->assign([
            'list'      => $list,
            'count'     => $count,
            'materials' =>  $materials,
            'info'      =>  $info
        ]);
        $this->display('applyinfo');

    }

    /**
     * 添加
     */
    public function applyAdd()
    {
        if ($this->isPost()) {
            
            // 接收表单数据
            if (!($data = I('post.')) || !I('post.student_id') || !I('post.tag')) die('请填写必选项!');

            if ($data['tag'] == 1) // 如果是普通申请,则入学年月份为必选字段
            {
                if (!$data['join_year'] || !$data['join_mouth']) $this->error('请填写必选字段! 入学年份/入学月份');
            }

            // 处理数据
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['first_end_time'] = strtotime($data['first_end_time']) ?: '';
            $data['end_time'] = strtotime($data['end_time']) ?: '';
            $data['submit_time'] = strtotime($data['submit_time']) ?: '';
            $data['ms_time'] = strtotime($data['ms_time']) ?: '';
            $data['offer_time'] = strtotime($data['offer_time']) ?: '';
            $data['exam_time'] = strtotime($data['exam_time']) ?: '';
            $data['materials'] = json_encode($data['materials']) ?: '';

            // 执行添加操作
            try {
                $res = M('MaterialsApply')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=apply&c='.$data['tag']);

        }

        if (!$c = I('get.c')) die('缺少关键参数');

        // 获取指定申请类别的材料分类
        $cates = M('MaterialsSampleCate')->where(['apply_tag'=>['LIKE','%'.$c.'%']])->select();

        $this->assign([
            'cates' => $cates,
            'c' =>  $c
        ]);

        $this->display('applyAdd');
    }

    /**
     * 修改
     */
    public function applyEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收表单数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数,请选择关联学员');

            // 过滤数据

            // 处理数据
            $data['update_time'] = time();
            $data['first_end_time'] = strtotime($data['first_end_time']) ?: '';
            $data['end_time'] = strtotime($data['end_time']) ?: '';
            $data['submit_time'] = strtotime($data['submit_time']) ?: '';
            $data['ms_time'] = strtotime($data['ms_time']) ?: '';
            $data['offer_time'] = strtotime($data['offer_time']) ?: '';
            $data['exam_time'] = strtotime($data['exam_time']) ?: '';
            $data['materials'] = json_encode($data['materials']) ?: '';

            // 执行更新操作
            try {
                M('MaterialsApply')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=apply&c='.$data['tag']);

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('MaterialsApply')->find($id);

        // 获取指定申请类别的材料分类
        $cates = M('MaterialsSampleCate')->where(['apply_tag'=>['LIKE','%'.$info['tag'].'%']])->select();

        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];
        $info['adviser_name'] = M('User')->field('full_name')->where('user_id=' . $info['adviser'])->find()['full_name'];
        $info['materials'] = json_decode($info['materials']);
        $all_materials = M('MaterialsSample')->field('id,name')->where('cate_id=' . $info['cate_id'])->select() ?: [];

        $info['create_time'] = $info['create_time']?date('Y-m-d H:i',$info['create_time']):'';
        $info['update_time'] = $info['update_time']?date('Y-m-d H:i',$info['update_time']):'';
        $info['first_end_time'] = $info['first_end_time']?date('Y-m-d H:i',$info['first_end_time']):'';
        $info['end_time'] = $info['end_time']?date('Y-m-d H:i',$info['end_time']):'';
        $info['submit_time'] = $info['submit_time']?date('Y-m-d H:i',$info['submit_time']):'';
        $info['ms_time'] = $info['ms_time']?date('Y-m-d H:i',$info['ms_time']):'';
        $info['offer_time'] = $info['offer_time']?date('Y-m-d H:i',$info['offer_time']):'';
        $info['exam_time'] = $info['exam_time']?date('Y-m-d H:i',$info['exam_time']):'';
        
        $this->assign([
            'info' => $info,
            'cates' => $cates,
            'all_materials' => $all_materials
        ]);
        $this->display('applyEdit');
    }

    /**
     * 修改申请状态
     */
    public function changeStatus()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收表单数据
            if (!($data['status'] = I('post.status')) || !($id = I('post.id'))) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);

            // 过滤数据

            // 执行更新操作
            try {
                M('MaterialsApply')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            $this->ajaxReturn(['status'=>true]);

        }

        $this->ajaxReturn(['status'=>false,'msg'=>'非法请求']);

    }

    /**
     * 删除
     */
    public function applyDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // TODO 如果需要的话,在删除之前,将待删除的申请中的所有已提交材料删掉.

        // 执行删除
        try {
            $res = M('MaterialsApply')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    /**
     * 保险服务API
     * ===========================================================================
     */
    public function insurance()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($name) $condition['insurance_name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 查询结果
            $list = M('MaterialsInsurance')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsInsurance')->where($condition)->count();

            // 获取相应的学员名称
            foreach ($list as $k => $v)
            {
                $list[$k]['sname'] = D('Students')->findStudents($v['student_id'])['realname'];
            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('insurance');

    }

    /**
     * 详情
     */
    public function insuranceDetail()
    {
        // 获取关键参数
        if (!($id = I('get.id'))) $this->ajaxReturn(['status'=>false, 'msg'=>'缺少关键参数']);

        // 获取详情
        $info = M('MaterialsInsurance')->find($id);

        if (!$info) $this->ajaxReturn(['status'=>false, 'msg'=>'数据为空']);

        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];
        $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
        $info['end_time'] = date('Y-m-d H:i',$info['end_time']);

        $this->ajaxReturn(['status'=>true, 'data'=>$info]);
    }

    /**
     * 添加
     */
    public function insuranceAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.')) || !I('post.student_id')) die('缺少参数,请选择关联学员');

            // 过滤数据

            // 处理数据
            $data['start_time'] = strtotime($data['start_time']) ?: '';
            $data['end_time'] = strtotime($data['end_time']) ?: '';
            $data['create_user'] = session('full_name') ?: '';

            // 执行添加操作
            try {
                $res = M('MaterialsInsurance')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=insurance');

        }

        $this->display('insuranceAdd');
    }

    /**
     * 修改
     */
    public function insuranceEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收表单数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数,请选择关联学员');

            // 过滤数据

            // 处理数据
            $data['start_time'] = strtotime($data['start_time']) ?: '';
            $data['end_time'] = strtotime($data['end_time']) ?: '';

            // 执行更新操作
            try {
                M('MaterialsInsurance')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=insurance');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('MaterialsInsurance')->find($id);
        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];


        $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
        $info['end_time'] = date('Y-m-d H:i',$info['end_time']);

        $this->assign([
            'info' => $info,
        ]);
        $this->display('insuranceEdit');
    }

    /**
     * 删除
     */
    public function insuranceDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('MaterialsInsurance')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    /**
     * 境外服务分类API
     * ===========================================================================
     */
    public function abroadCate()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($name) $condition['cate_name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 查询结果
            $list = M('MaterialsAbroadCate')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsAbroadCate')->where($condition)->count();

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('abroadCate');

    }

    /**
     * 分类添加
     */
    public function abroadCateAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('数据为空');

            // 过滤数据

            // 执行添加操作
            try {
                $res = M('MaterialsAbroadCate')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=abroadCate');

        }

        $this->display('abroadCateAdd');
    }

    /**
     * 分类修改
     */
    public function abroadCateEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
            try {
                $res = M('MaterialsAbroadCate')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=abroadCate');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('MaterialsAbroadCate')->find($id);

        $this->assign('info', $info);
        $this->display('abroadCateEdit');
    }

    /**
     * 分类删除
     */
    public function abroadCateDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('MaterialsAbroadCate')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    /**
     * 境外服务API
     * ===========================================================================
     */
    public function abroad()
    {

        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $cate = $wheredata['cate'] ? $wheredata['cate'] : '';// 服务分类
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($cate) $condition['cate_id'] = $cate;

            if ($name) $condition['sname'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 查询结果
            $list = M('MaterialsAbroad')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsAbroad')->where($condition)->count();

            // 获取相应的学员名称
            foreach ($list as $k => $v)
            {
                $list[$k]['cate_name'] = M('MaterialsAbroadCate')->find($v['cate_id'])['cate_name'];
            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display();

    }

    /**
     * 添加
     */
    public function abroadAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.'))) die('缺少参数');

            // 过滤数据

            // 处理数据
            $data['create_time'] = strtotime($data['create_time']) ?: '';
            $data['notice_time'] = strtotime($data['notice_time']) ?: '';
            $data['create_user'] = session('full_name')?:' - ';

            // 执行添加操作
            try {
                $res = M('MaterialsAbroad')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=abroad');

        }
        
        // 获取分类
        $cates = M('MaterialsAbroadCate')->select();

        $this->assign([
            'cates' => $cates
        ]);
        $this->display('abroadAdd');
    }

    /**
     * 修改
     */
    public function abroadEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 处理数据
            $data['create_time'] = strtotime($data['create_time']) ?: '';
            $data['notice_time'] = strtotime($data['notice_time']) ?: '';

            // 执行更新操作
            try {
                $res = M('MaterialsAbroad')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=abroad');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取课程分类
        $cates = M('MaterialsAbroadCate')->select();
        // 获取课程详情
        $info = D('MaterialsAbroad')->findAbroad($id);
        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];

        $info['create_time'] = date('Y-m-d H:i',$info['create_time']);
        $info['notice_time'] = date('Y-m-d H:i',$info['notice_time']);

        $this->assign([
            'info' => $info,
            'cates' => $cates,
        ]);
        $this->display('abroadEdit');
    }

    /**
     * 删除
     */
    public function abroadDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('MaterialsAbroad')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    /**
     * 样本分类API
     * ===========================================================================
     */
    public function materialsSampleCate()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($name) $condition['name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 查询结果
            $list = M('MaterialsSampleCate')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsSampleCate')->where($condition)->count();

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('materialsSampleCate');

    }

    /**
     * 添加
     */
    public function materialsSampleCateAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('数据为空');

            if (!$data['apply_tag']) die('所属申请类别不能为空');

            $data['apply_tag'] = implode(',',$data['apply_tag']);

            // 过滤数据

            // 执行添加操作
            try {
                $res = M('MaterialsSampleCate')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=materialsSampleCate');

        }

        $this->display('materialsSampleCateAdd');
    }

    /**
     * 修改
     */
    public function materialsSampleCateEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            if (!$data['apply_tag']) die('所属申请类别不能为空');

            $data['apply_tag'] = implode(',',$data['apply_tag']);

            // 过滤数据

            // 执行更新操作
            try {
                M('MaterialsSampleCate')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=materialsSampleCate');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('MaterialsSampleCate')->find($id);
        
        $info['apply_tag'] = explode(',',$info['apply_tag']);

        $this->assign('info', $info);
        $this->display('materialsSampleCateEdit');
    }

    /**
     * 删除
     */
    public function materialsCateDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('MaterialsSampleCate')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    /**
     * 附加服务API
     */
    public function additional()
    {
        if ($this->isAjax())
        {
            $wheredata = $_REQUEST;
            $cate = $wheredata['c'] ? $wheredata['c'] : '';// 服务分类
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($cate) $condition['server_cate'] = $cate;

            if ($name) $condition['sname'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 查询结果
            $list = M('MaterialsAdditional')->where($condition)->limit($start,$limit)->select();
            $count = M('MaterialsAdditional')->where($condition)->count();

            // 获取相应的学员名称
//            foreach ($list as $k => $v)
//            {
//                if (!$list[$k]['sname']) $list[$k]['sname'] = D('Students')->findStudents($v['student_id'])['realname'];
//            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display('additional');

    }

    /**
     * 详情
     */
    public function additionalDetail()
    {
        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('MaterialsAdditional')->find($id);
        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];

        $info['create_time'] = date('Y-m-d H:i',$info['create_time']);
        $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
        $info['arrive_time'] = date('Y-m-d H:i',$info['arrive_time']);
        $info['come_time'] = date('Y-m-d H:i',$info['come_time']);
        $info['notice_time'] = date('Y-m-d H:i',$info['notice_time']);

        $this->ajaxReturn(['status'=>true,'data'=>$info]);
    }

    /**
     * 添加
     */
    public function additionalAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.')) || !I('post.student_id')) die('缺少参数,请选择关联学员');

            // 过滤数据
            
            // 处理数据
            $data['create_time'] = time();
            $data['start_time'] = strtotime($data['start_time']) ?: '';
            $data['arrive_time'] = strtotime($data['arrive_time']) ?: '';
            $data['come_time'] = strtotime($data['come_time']) ?: '';
            $data['notice_time'] = strtotime($data['notice_time']) ?: '';

            // 执行添加操作
            try {
                $res = M('MaterialsAdditional')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=materials&a=additional');

        }

        $this->display('additionalAdd');
    }

    /**
     * 修改
     */
    public function additionalEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收表单数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少关键参数');

            // 过滤数据

            // 处理数据
            $data['start_time'] = strtotime($data['start_time']) ?: '';
            $data['arrive_time'] = strtotime($data['arrive_time']) ?: '';
            $data['come_time'] = strtotime($data['come_time']) ?: '';
            $data['notice_time'] = strtotime($data['notice_time']) ?: '';

            // 执行更新操作
            try {
                M('MaterialsAdditional')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=materials&a=additional');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('MaterialsAdditional')->find($id);
        $info['sname'] = D('Students')->findStudents($info['student_id'])['realname'];

        $info['create_time'] = date('Y-m-d H:i',$info['create_time']);
        $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
        $info['arrive_time'] = date('Y-m-d H:i',$info['arrive_time']);
        $info['come_time'] = date('Y-m-d H:i',$info['come_time']);
        $info['notice_time'] = date('Y-m-d H:i',$info['notice_time']);

        $this->assign([
            'info' => $info
        ]);
        $this->display('additionalEdit');
    }

    /**
     * 删除
     */
    public function additionalDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('MaterialsAdditional')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }




    // ============================= 公用方法

    /**
     * 获取材料API
     */
    public function getmaterials()
    {
        if (!$id = I('get.cate_id')) $this->ajaxReturn(['status' => false, 'msg' => '缺少关键参数']);

        // 获取指定分类下的材料列表
        $res = M('MaterialsSample')->where('cate_id=' . $id)->select() ?: [];

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $res]);
    }

    /**
     * 获取学员API
     */
    public function getstudents()
    {
        if (!$name = I('get.name')) $name = '';

        // 获取符合条件的学员
        $stu = D('Students')->getStudents('`realname` LIKE "%'.$name.'%"');

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $stu]);
    }

    /**
     * 获取顾问API
     */
    public function getguwen()
    {
        if (!$sid = I('get.id')) $this->ajaxReturn(['status' => false, 'msg' => '缺少关键参数']);;

        // 获取符合条件的学员
        $stu = D('Students')->findStudents($sid)['customer_id'];
        $_guwen = M('Customer')->field('owner_role_id')->where('customer_id='.$stu)->find()['owner_role_id'];
        $guwen = M('User')->where('role_id='.$_guwen)->find();

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $guwen]);
    }

    /**
     * 获取老师API
     */
    public function getteachers()
    {
        if (!$name = I('get.name')) $name = '';

        // 获取符合条件的老师
        $res = M('User')->where(['full_name' => ['like', '%' . $name . '%'],'status' => ['eq',1]])->select() ?: [];

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $res]);
    }

    /**
     * 上传材料
     */
    public function uploadMaterials()
    {
        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array();// 设置附件上传类型
        $upload->thumbRemoveOrigin = false;//是否删除原文件
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            die(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
        }
        $upload->savePath = $dirname;

        if (!$upload->upload()) {// 上传错误提示错误信息
            die($upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if (is_array($info[0]) && !empty($info[0])) {
                $upload = $dirname . $info[0]['savename'];
            } else {
                die('文件上传失败，请重试！');
            }
            // 返回文件路径
            return $upload ?: '';
        }
    }

    /**
     * 获取项目名称
     */
    protected function getPrograms()
    {
        $program = new PBaseModel('Program');
        $program_field = 'id,include_major,department';

        return $program->field($program_field)->select() ?: [];
    }

    /**
     * 获取申请项目名称
     */
    public function getprojectname()
    {
        if (!$name = I('get.name')) $name = '';

        $program = new PBaseModel('Tags');

        $data = $program->field('id,name')->where(['type'=>['eq',2],'name'=>['LIKE','%'.$name.'%']])->select() ?: [];

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $data]);
    }

    /**
     * 获取申请学校名称
     */
    public function getschoolname()
    {
        if (!$name = I('get.name')) $name = '';

        // 806新增 根据选择项目,筛选学校
        if (!$cate = I('get.cate')) $cate = '';

        $program = new PBaseModel('Program');

        // 806修改 根据选择项目,筛选学校
        $data = $program->field('id,school_name')->group('school_name')->where(['school_name'=>['LIKE','%'.$name.'%'],'program_category'=>['LIKE','%'.$cate.'%']])->select() ?: [];

        // 返回数据
        $this->ajaxReturn(['status' => true, 'data' => $data]);
    }

}