<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/5/22
 * Time: 13:18
 */

class CooperateAction extends Action
{

    private $school_field = ['name_cn', 'name_en', 'name_jp', 'desc', 'logo', 'one', 'website', 'address', 'xingzhiid', 'gaozhong', 'daxue', 'major_cate', 'rank'];

    /**
     * 合作机构项目搜索页面
     */
    public function index()
    {
        $this->display('index');
    }

    public function getfields()
    {
        header("Access-Control-Allow-Origin: *");

        // 获取所有字段和对应值
        $fields = M('cooperate_field')->where('status=1')->select();// 获取未禁用的字段列表
        $data = [];
        foreach ($fields as $k => $v) // 根据字段获取相应的值列表
        {
            $data[$fields[$k]['name']]['tag'] = $v['tag'];
            $data[$fields[$k]['name']]['info'] = M('cooperate_value')->where('field_id=' . $v['id'])->select();
        }
        // 获取专业分类信息
        $data['当前专业']['tag'] = 'now_major';
        $data['目标专业']['tag'] = 'want_major';
        $major_format = $this->getTags('type=3');
        foreach ($major_format as $k => $v) {// 获取包含当前专业的学校个数
            $major_format[$k]['school_num'] = M('CooperateSchool')->where(['major_cate'=>['like','%'.$v['id'].'%']])->count();
        }
        $data['当前专业']['info'] = $data['目标专业']['info'] = $major_format;
        $data['地域']['tag'] = 'address';
        $area = $this->getArea();
        // 格式化地域信息
        foreach ($area as $k => $v) {
            if ($v['pid'] == 0) {
                foreach ($area as $key => $val) {
                    if ($val['pid'] == $v['id']) $v['child'][] = $val;
                }
                $area[$k]['child'] = $v['child'];
            } else {
                unset($area[$k]);
            }
        }
        $data['地域']['info'] = $area;

        $this->ajaxReturn($data);
    }

    /**
     * 合作机构数据列表页面
     */
    public function cooperateData()
    {
        // 1.获取当前用户所属的合作机构

        // 2.获取当前用户所属机构的数据
        $list = M('CooperateData')->order('create_time desc')->select();

        // 统计总数
        $count = count($list);
        // 分配变量并展示
        $this->assign([
            'list' => $list,
            'count' => $count
        ]);
        $this->display('cooperateData');
    }

    /**
     * 删除数据
     */
    public function cooperateDataDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // TODO 获取要删除的数据,并判断是否有权限删除.
        // code...

        // 执行删除
        try {
            $res = D('CooperateData')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    // ---------------------------------------------------------------------
    //  表单部分
    // ---------------------------------------------------------------------

    /**
     * 表单字段列表
     */
    public function form()
    {
        // 获取列表
        $list = M('cooperate_field')->select();
        // 统计总数
        $count = count($list);
        // 分配变量并展示
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 表单字段添加
     */
    public function formAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) return ['code' => 200, 'status' => false, 'msg' => '缺失参数'];

            // 过滤数据

            // 执行添加操作
            try {
                $plan = M('cooperate_field');
                $res = $plan->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=form');

        }

        $this->display('formAdd');
    }

    /**
     * 表单字段修改
     */
    public function formEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
            try {
                $plan = M('cooperate_field');
                $res = $plan->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=form');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('cooperate_field')->find($id);
        // 分配数据并展示
        $this->assign('info', $info);
        $this->display('formEdit');
    }

    /**
     * 表单字段删除
     */
    public function formDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('cooperate_field')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
            // 删除子栏目
            $res = M('cooperate_value')->where('field_id=' . $id)->delete();
            if (!$res) $this->ajaxReturn('', '子栏目未删除,删除成功', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    /**
     * 字段 value 列表
     */
    public function formContent()
    {
        // 获取关键参数
        if (!$id = intval(I('get.fid'))) die('缺少关键参数');

        // 获取列表
        $list = M('cooperate_value')->where('field_id=' . $id)->select();
        // 统计总数
        $count = count($list);
        // 分配变量并展示
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('field_id', $id);
        $this->display('formContent');
    }

    /**
     * 字段 value 添加
     */
    public function formContentAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('参数缺失');

            // 过滤数据

            // 执行添加操作
            try {
                $res = M('cooperate_value')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=formContent&fid=' . $data['field_id']);

        }

        if (!($field_id = I('get.fid'))) die('参数缺失');
        $this->assign('field_id', $field_id);
        $this->display('formContentAdd');
    }

    /**
     * 字段 value 修改
     */
    public function formContentEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
            try {
                $res = M('cooperate_value')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=formContent&fid=' . $data['fid']);

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('cooperate_value')->find($id);

        // 分配数据并展示
        $this->assign('info', $info);
        $this->display('formContentEdit');
    }

    /**
     * 字段 value 删除
     */
    public function formContentDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('cooperate_value')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }


    // ---------------------------------------------------------------------
    //  学校部分
    // ---------------------------------------------------------------------

    public function school()
    {
        $list = M('cooperate_school')->select();
        $count = count($list);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->display();
    }

    public function schoolAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('缺少参数');
            if (!($files = $_FILES)) die('学校logo未上传');

            // 过滤数据

            // 处理项目分类
            // 这里使用拼接方式将项目分类组成短字符串
            $data['gaozhong'] = implode(',', $data['gaozhong']);
            $data['daxue'] = implode(',', $data['daxue']);
            $data['major_cate'] = implode(',', $data['major_cate']);

            //上传logo
            if ($_FILES['logo']['size']) {

                // 如果有文件上传 上传附件
                import('@.ORG.UploadFile');
                import('@.ORG.Image');//引入缩略图类
                $Img = new Image();//实例化缩略图类
                //导入上传类
                $upload = new UploadFile();
                //设置上传文件大小
                $upload->maxSize = 20000000;
                //设置附件上传目录
                $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
                $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');// 设置附件上传类型
                $upload->thumb = true;//生成缩图
                $upload->thumbRemoveOrigin = false;//是否删除原图
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                    $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
                }
                $upload->savePath = $dirname;

                if (!$upload->upload()) {// 上传错误提示错误信息
                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                } else {// 上传成功 获取上传文件信息
                    $info = $upload->getUploadFileInfo();
                    if (is_array($info[0]) && !empty($info[0])) {
                        $upload = $dirname . $info[0]['savename'];
                    } else {
                        $this->error('图片上传失败，请重试！');
                    }
                    $thumb_path = $Img->thumb($upload, $dirname . 'thumb_' . $info[0]['savename']);

                    //写入数据库
                    foreach ($info as $iv) {
                        if ($iv['key'] == 'main_pic') {
                            //主图
                            $img_data['is_main'] = 1;
                        } else {
                            //副图
                            $img_data['is_main'] = 0;
                        }
                        $img_data['name'] = $iv['name'];
                        $img_data['save_name'] = $iv['savename'];
                        $img_data['size'] = sprintf("%.2f", $iv['size'] / 1024);
                        $img_data['path'] = $iv['savepath'] . $iv['savename'];
                        $img_data['thumb_path'] = $thumb_path; //缩略图
                    }
                }
            }

            if (!($data['logo'] = $img_data['path'])) die('图片上传有误');

            // 处理advise字段
            $data['advise'] = rtrim($data['advise']);

            // 执行添加操作
            try {
                $plan = M('cooperate_school');
                $res = $plan->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=school');

        }

        // 获取地区信息
        $area = $this->getArea();
        // 获取项目分类信息
        $program = $this->getTags('type=2');
        $program_cate = $this->getTags('type=3');

        $this->assign([
            'area' => $area,
            'programs' => $program,
            'major_cates' => $program_cate,
        ]);
        $this->display('schoolAdd');
    }

    public function schoolEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 处理项目分类
            // 这里使用拼接方式将部分值组成短字符串
            $data['gaozhong'] = implode(',', $data['gaozhong']);
            $data['daxue'] = implode(',', $data['daxue']);
            $data['major_cate'] = implode(',', $data['major_cate']);

            //上传logo
            if ($_FILES['logo']['size']) {

                // 如果有文件上传 上传附件
                import('@.ORG.UploadFile');
                import('@.ORG.Image');//引入缩略图类
                $Img = new Image();//实例化缩略图类
                //导入上传类
                $upload = new UploadFile();
                //设置上传文件大小
                $upload->maxSize = 20000000;
                //设置附件上传目录
                $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
                $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');// 设置附件上传类型
                $upload->thumb = true;//生成缩图
                $upload->thumbRemoveOrigin = false;//是否删除原图
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                    $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
                }
                $upload->savePath = $dirname;

                if (!$upload->upload()) {// 上传错误提示错误信息
                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                } else {// 上传成功 获取上传文件信息
                    $info = $upload->getUploadFileInfo();
                    if (is_array($info[0]) && !empty($info[0])) {
                        $upload = $dirname . $info[0]['savename'];
                    } else {
                        $this->error('图片上传失败，请重试！');
                    }
                    $thumb_path = $Img->thumb($upload, $dirname . 'thumb_' . $info[0]['savename']);

                    // 准备数据
                    foreach ($info as $iv) {
                        if ($iv['key'] == 'main_pic') {
                            //主图
                            $img_data['is_main'] = 1;
                        } else {
                            //副图
                            $img_data['is_main'] = 0;
                        }
                        $img_data['name'] = $iv['name'];
                        $img_data['save_name'] = $iv['savename'];
                        $img_data['size'] = sprintf("%.2f", $iv['size'] / 1024);
                        $img_data['path'] = $iv['savepath'] . $iv['savename'];
                        $img_data['thumb_path'] = $thumb_path; //缩略图
                    }
                    $data['logo'] = $img_data['path'];
                }

            }

            // 处理advise字段
            $data['advise'] = rtrim($data['advise']);

            // 执行更新操作
            try {
                $res = M('cooperate_school')->where('id=' . $id)->save($data);
                // if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=school');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('cooperate_school')->find($id);
        // 处理项目分类
        $info['gaozhong'] = explode(',', $info['gaozhong']);
        $info['daxue'] = explode(',', $info['daxue']);
        $info['major_cate'] = explode(',', $info['major_cate']);
        // 获取地区信息
        $area = $this->getArea();
        // 获取项目分类信息
        $program = $this->getTags('type=2');
        $program_cate = $this->getTags('type=3');
        // 分配数据并展示
        $this->assign([
            'info' => $info,
            'area' => $area,
            'programs' => $program,
            'major_cates' => $program_cate,
        ]);
        $this->display('schoolEdit');
    }

    public function schoolDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('cooperate_school')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    /**
     * @ 地区信息
     * @return mixed
     */
    protected function getArea()
    {
        return (new PBaseModel('Area'))->field('id,name,pid')->select();
    }

    /**
     * @ 项目分类获取
     * @param null $type
     * @return array|mixed
     */
    protected function getTags($where = null)
    {
        if ($where) return (new PBaseModel('Tags'))->field('id,name')->where($where)->select();

        return (new PBaseModel('Tags'))->field('id,name')->select();
    }

    /**
     * 获取项目名称
     */
    protected function getPrograms($cate = null, $want_major = null, $schoo_name = null)
    {
        $program = new PBaseModel('Program');
        $program_field = 'include_major,department';

        return $program
            ->field($program_field)
            ->group('department')
            ->where(['major_category' => ['like', '%' . $want_major . '%'], 'program_category' => ['like', '%' . $cate . '%'], 'school_name' => ['eq', $schoo_name]])
            ->select() ?: [];
    }

    // ---------------------------------------------------------------------
    //  课程分类部分
    // ---------------------------------------------------------------------

    /**
     * 分类列表展示
     */
    public function courseCate()
    {
        // 获取分类列表
        $list = M('CooperateCourseCate')->select();
        // 统计总条数
        $count = count($list);

        $this->assign([
            'list' => $list,
            'count' => $count
        ]);
        $this->display('courseCate');
    }

    /**
     * 分类添加
     */
    public function courseCateAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('数据为空');

            // 过滤数据

            // 执行添加操作
            try {
                $res = M('CooperateCourseCate')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=courseCate');

        }

        $this->display('courseCateAdd');
    }

    /**
     * 分类修改
     */
    public function courseCateEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
            try {
                $res = M('CooperateCourseCate')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=courseCate');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('CooperateCourseCate')->find($id);

        $this->assign('info', $info);
        $this->display('courseCateEdit');
    }

    /**
     * 分类删除
     */
    public function courseCateDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('CooperateCourseCate')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    // ---------------------------------------------------------------------
    //  课程部分
    // ---------------------------------------------------------------------

    /**
     * 课程列表
     */
    public function course()
    {
        // 获取课程列表
        $list = M('CooperateCourse')->select();
        // 统计总条数
        $count = count($list);

        $this->assign([
            'list' => $list,
            'count' => $count
        ]);
        $this->display();
    }

    /**
     * 课程添加
     */
    public function courseAdd()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.'))) die('缺少参数');
            // 是否上传logo
            if (!$_FILES['logo']['size']) die('课程logo未上传');

            // 过滤数据

            // 处理专业分类
            $data['major_cate'] = implode(',', $data['major_cate']);

            // 上传logo
            $img_data = $this->uploadLogo();

            // logo 字段赋值
            if (!($data['logo'] = $img_data['path'])) die('图片上传有误');

            // 执行添加操作
            try {
                $res = M('CooperateCourse')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=course');

        }

        // 获取课程分类
        $cates = M('CooperateCourseCate')->select();
        // 获取专业分类
        $program_cate = $this->getTags('type=3');

        $this->assign([
            'major_cates' => $program_cate,
            'cates' => $cates
        ]);
        $this->display('courseAdd');
    }

    /**
     * 课程修改
     */
    public function courseEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 处理专业分类
            $data['major_cate'] = implode(',', $data['major_cate']);

            // 如果更新logo
            if ($_FILES['logo']['size']) {
                $img_data = $this->uploadLogo();
                $data['logo'] = $img_data['path'];
            }

            // 执行更新操作
            try {
                $res = M('CooperateCourse')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=course');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取课程分类
        $cates = M('CooperateCourseCate')->select();
        // 获取专业分类
        $program_cate = $this->getTags('type=3');
        // 获取课程详情
        $info = M('CooperateCourse')->find($id);
        // 处理专业分类
        $info['major_cate'] = explode(',', $info['major_cate']);

        $this->assign([
            'info' => $info,
            'cates' => $cates,
            'major_cates' => $program_cate,
        ]);
        $this->display('courseEdit');
    }

    /**
     * 课程删除
     */
    public function courseDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('CooperateCourse')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    /**
     * Logo (图片) 上传
     * @return mixed
     */
    public function uploadLogo()
    {
        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        import('@.ORG.Image');//引入缩略图类
        $Img = new Image();//实例化缩略图类
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif');// 设置附件上传类型
        $upload->thumb = true;//生成缩图
        $upload->thumbRemoveOrigin = false;//是否删除原图
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
        }
        $upload->savePath = $dirname;

        if (!$upload->upload()) {// 上传错误提示错误信息
            alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if (is_array($info[0]) && !empty($info[0])) {
                $upload = $dirname . $info[0]['savename'];
            } else {
                $this->error('图片上传失败，请重试！');
            }
            $thumb_path = $Img->thumb($upload, $dirname . 'thumb_' . $info[0]['savename']);

            //写入数据库
            foreach ($info as $iv) {
                if ($iv['key'] == 'main_pic') {
                    //主图
                    $img_data['is_main'] = 1;
                } else {
                    //副图
                    $img_data['is_main'] = 0;
                }
                $img_data['name'] = $iv['name'];
                $img_data['save_name'] = $iv['savename'];
                $img_data['size'] = sprintf("%.2f", $iv['size'] / 1024);
                $img_data['path'] = $iv['savepath'] . $iv['savename'];
                $img_data['thumb_path'] = $thumb_path; //缩略图
            }
            return $img_data;
        }
    }

    // 将分数格式化为特定的分数范围值
    public function scoreFormart($score)
    {
        $arr = [
            6 => 0, // 50分以下
            5 => 50, // 50~60分
            4 => 60, // 60~70分
            3 => 70, // 70~80分
            2 => 80, // 80~90分
            1 => 90  // 90分以上
        ];
        // 排除异常数据
        if ($score > 0) {
            foreach ($arr as $k => $v) {
                if ($score >= $v) $s = $k;
            }
            return $s;
        }

        return 6;
    }

    // ---------------------------------------------------------------------
    //  方案部分
    // ---------------------------------------------------------------------

    public function plan()
    {
        $list = M('cooperate_plan')->select();
        $count = count($list);
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->display();
    }

    public function planAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) return ['code' => 200, 'status' => false, 'msg' => '缺失参数'];

            // 过滤数据

            // 执行添加操作
            try {
                $plan = M('cooperate_plan');
                $res = $plan->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }
            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=plan');
        }

        // 获取列表 TODO 这里 field_id 必须跟添加的年级字段ID保持一致! 目前是6,线上重新添加可能需要重新修改.
        $grades = M('cooperate_value')->where('field_id=6')->select();

        if (!$grades) die('缺少必要条件,请先在表单管理中心添加年级字段!');

        $this->assign('grades', $grades);
        $this->display('planAdd');
    }

    public function planEdit()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.')) || !($id = I('post.id'))) return ['code' => 200, 'status' => false, 'msg' => '缺失参数'];

            // 过滤数据

            // 执行修改操作
            try {
                $plan = M('cooperate_plan');
                $res = $plan->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=plan');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('cooperate_plan')->find($id);

        // 分配数据并展示
        $this->assign('info', $info);

        // 获取年级列表 TODO 这里 field_id 必须跟添加的年级字段ID保持一致! 目前是6,线上重新添加可能需要重新修改.
        $grades = M('cooperate_value')->where('field_id=6')->select();

        if (!$grades) die('缺少必要条件,请先在表单管理中心添加年级字段!');

        $this->assign('grades', $grades);
        $this->display('planEdit');
    }

    public function planDelete()
    {
        // 接收ID 参数
        if (!($id = I('post.id'))) die('缺少关键参数');
        // 模型实例化
        $plan = M('cooperate_plan');
        // 执行删除操作
        for ($i = 0; $i < count($id); $i++) {
            $res = $plan->where('id=' . $id[$i])->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        }
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }

    public function planning()
    {
        // 获取关键参数
        if (!$id = intval(I('get.pid'))) die('缺少关键参数');

        // 获取列表
        $list = M('cooperate_planning')->where('plan_id=' . $id)->select();
        // 统计总数
        $count = count($list);
        // 分配变量并展示
        $this->assign('list', $list);
        $this->assign('count', $count);
        $this->assign('plan_id', $id);
        $this->display();
    }

    public function planningAdd()
    {
        if ($this->isPost()) {
            // 1.接收表单数据
            if (!($data = I('post.'))) die('参数缺失');

            // 过滤数据

            // 执行添加操作
            try {
                $res = M('cooperate_planning')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=planning&pid=' . $data['plan_id']);

        }

        if (!($plan_id = I('get.pid'))) die('参数缺失');
        $this->assign('plan_id', $plan_id);
        $this->display('planningAdd');
    }

    public function planningEdit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
            try {
                $res = M('cooperate_planning')->where('id=' . $id)->save($data);
//                if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=cooperate&a=planning&pid=' . $data['plan_id']);

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('cooperate_planning')->find($id);

        // 分配数据并展示
        $this->assign('info', $info);
        $this->display('planningEdit');
    }

    public function planningDelete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('cooperate_planning')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }

    /**
     * 获取评估结果详情
     */
    public function findPromote()
    {
        // TODO 判断请求是否合法
        header("Access-Control-Allow-Origin: *");
        // 模拟数据

        if (!$id = I('post.id')) $this->ajaxReturn(['success' => false, 'msg' => '缺少关键参数']);

        $info = M('CooperateData')->find($id);

        if (!$info) $this->ajaxReturn(['success' => false, 'msg' => '数据为空,请确认参数是否正确']);

        $promote = json_decode($info['promote']);

        $this->ajaxReturn(['success' => true, 'data' => $promote]);
    }

    /**
     * 提交评估数据 获取评估结果数据ID
     */
    public function createPromote()
    {
        header("Access-Control-Allow-Origin: *");
        // TODO 判断请求是否合法

        // 如果没有评估分数  (分数为必要参数)
        if (!$data = I('post.')) $this->ajaxReturn(['success' => false, 'msg' => '参数丢失']);
        if (!$outer = I('post.outer')) $this->ajaxReturn(['success' => false, 'msg' => '参数丢失']);
        if (!$data['score'] || !$data['want_major'] || !$data['grade']) $this->ajaxReturn(['success' => false, 'msg' => '缺少关键参数']);

        $data['score'] += 20;// 添加专业分数 (专业20分)

        // 如果当前年级是大学,且跨专业申请,则专业分数按规定 $this->major() 调整
        $is_daxue = in_array($data['grade'], ['45', '46', '47', '48', '49']);
        $is_kuazhuanye = $data['major'] != $data['want_major'];
        if ($is_daxue && $is_kuazhuanye) {
            $data['score'] -= (20 - $this->major2score($data['major'], $data['want_major']));// 详细分数增减规则
            $course = $this->getCourse($data['want_major'],2);// 获取大学课程
        }elseif (!$is_daxue){// 5-30新增需求 : 如果是高中统一减10分 满分90分制
            $data['score'] -= 10;
            $course = $this->getCourse($data['want_major'],1);// 获取高中课程
        }

        // 获取学校
        $school = $this->getSchool($data);

        // 获取方案
        $plan = $this->getPlan($data['grade']);

        // 合并结果并返回
        $res_data = compact('school', 'plan', 'course');

        $outer['promote'] = json_encode($res_data);
        $outer['score'] = $data['score'];
        $outer['create_time'] = time();

        // 存储数据
        try {
            $clue = M('CooperateData');
            $res = $clue->add($outer);
            $insert_id = $clue->getLastInsID();
            if (!$res) return ['success' => false, 'msg' => '提交失败,请联系客服免费获取推荐方案.'];
        } catch (\Exception $exception) {
            die($exception->getMessage());
        }

        // 4.将推荐的方案反馈给用户

        $this->ajaxReturn(['success' => true, 'id' => $insert_id]);
    }

    /**
     * 获取推荐方案/时间规划
     * @param $grade_id
     * @return array
     */
    public function getPlan($grade_id)
    {
        // 根据当前年级 获取对应方案
        $plan = M('cooperate_plan')->where('`grade_id`=' . $grade_id)->find();

        if (!$plan) return []; // 如果没有符合条件的方案

        // 获取方案详情并整合/处理
        $plan['info'] = M('cooperate_planning')->where('`plan_id`=' . $plan['id'])->field(['name', 'time', 'sort'])->order('sort')->select();

        return $plan;
    }

    /**
     * 获取推荐课程
     */
    public function getCourse($want_major,$neq)
    {
        // 根据目标专业 获取对应课程
        return M('cooperate_course')->where(['major_cate' => ['LIKE', '%' . $want_major . '%'],'level'=>['NEQ',$neq]])->select();
    }

    /**
     * 获取推荐学校
     */
    public function getSchool($data)
    {
        $school_where = []; // 学校查询条件 [可选: 性质 || 目标专业 || 地区 || 年级(如果当前年级无项目推荐,则当前学校不展示)]    [必选:分数]
        // 学校性质
        $xingzhi_list = ['1' => '国立', '2' => '公立', '3' => '私立']; // 准备学校性质映射规则
        if ($data['xingzhiid']) {
            $school_where['xingzhiid'] = $data['xingzhiid'];
        }
        // 目标专业
        $school_where['major_cate'] = ['like', '%' . $data['want_major'] . '%'];
        // 地区
        if ($data['area_id']) {
            $school_where['area_id'] = ['in', $data['area_id']];
        }
        $school_where['score'] = $this->scoreFormart($data['score']);// 通过分数获取对应的分数范围值 [0-100以内的数字]

        $school = M('CooperateSchool')->field($this->school_field)->where($school_where)->select();// 获取符合条件的所有学校

        // 处理需要转换的数据 并 释放无项目的学校
        switch ($data['grade']) {
            case 45:
            case 46:
            case 47:
            case 48:
            case 49:
                // 大学
                foreach ($school as $k => $v) {
                    if (!in_array($data['want_major'], explode(',', $school[$k]['major_cate']))) {
                        unset($school[$k]);
                        continue;
                    } // 如果当前学校下没有学员希望的目标专业,则忽略当前学校
                    if (!$v['daxue']) {
                        unset($school[$k]);
                        continue;
                    } // 如果当前学校下没有项目分类,则忽略当前学校
                    $school[$k]['project_cate'] = $this->getTags(['id' => ['in', $v['daxue']]]); // 把分类ID 转换成名称
                    // 5-28 修改需求 获取项目名称并处理成指定的格式
                    foreach ($school[$k]['project_cate'] as $key => $val) {
                        $school[$k]['project_cate'][$key]['info'] = $this->getPrograms($val['id'], $data['want_major'], $v['name_cn']); // 通过项目分类获取当前项目分类下的项目
                    }
                    $school[$k]['xingzhiid'] = $xingzhi_list[$v['xingzhiid']]; // 转换学校性质为直观数据
                    $school[$k]['major_cate'] = $this->getTags(['id' => ['in', $v['major_cate']]]); // 获取当前学校的专业分类
                }
                break;
            case 55:
            case 56:
            case 57:
            case 58:
                // 高中
                foreach ($school as $k => $v) {
                    if (!in_array($data['want_major'], explode(',', $school[$k]['major_cate']))) {
                        unset($school[$k]);
                        continue;
                    } // 如果当前学校下没有学员希望的目标专业,则忽略当前学校
                    if (!$v['gaozhong']) {
                        unset($school[$k]);
                        continue;
                    } // 如果当前学校下没有项目分类,则忽略当前学校
                    $school[$k]['project_cate'] = $this->getTags(['id' => ['in', $v['gaozhong']]]); // 把分类ID 转换成名称
                    // 5-28 修改需求 获取项目名称并处理成指定的格式
                    foreach ($school[$k]['project_cate'] as $key => $val) {
                        $school[$k]['project_cate'][$key]['info'] = $this->getPrograms($val['id'], $data['want_major'], $v['name_cn']); // 通过项目分类获取当前项目分类下的项目
                    }
                    $school[$k]['xingzhiid'] = $xingzhi_list[$v['xingzhiid']]; // 转换学校性质为直观数据
                    $school[$k]['major_cate'] = $this->getTags(['id' => ['in', $v['major_cate']]]); // 获取当前学校的专业分类
                }
                break;
            default:
                foreach ($school as $k => $v) {
                    if (!in_array($data['want_major'], explode(',', $school[$k]['major_cate']))) {
                        unset($school[$k]);
                        continue;
                    } // 如果当前学校下没有学员希望的目标专业,则忽略当前学校
                    if ($v['gaozhong'] && $v['daxue']) {// 如果当前学校同时存在高中和大学的项目类别 则合并高中和大学包含的项目分类并去除重复数据
                        $school[$k]['all_project_cate'] = array_unique(array_merge(explode(',', $v['gaozhong']), explode(',', $v['daxue']))); // 把分类ID 转换成名称
                    } elseif ($v['gaozhong']) {// 如果有高中项目分类
                        $school[$k]['all_project_cate'] = explode(',', $v['gaozhong']);
                    } elseif ($v['daxue']) {// 如果有大学项目分类
                        $school[$k]['all_project_cate'] = explode(',', $v['daxue']);
                    } else {// 如果高中和大学项目类别都没有,则直接跳过对major字段的赋值
                        $school[$k]['major_cate'] = $this->getTags(['id' => ['in', $v['major_cate']]]); // 获取当前学校的专业分类
                        break;
                    }
                    $school[$k]['project_cate'] = $this->getTags(['id' => ['in', $school[$k]['all_project_cate']]]); // 把分类ID 转换成名称
                    // 5-28 修改需求 获取项目名称并处理成指定的格式
                    foreach ($school[$k]['project_cate'] as $key => $val) {
                        $school[$k]['project_cate'][$key]['info'] = $this->getPrograms($val['id'], $data['want_major'], $v['name_cn']); // 通过项目分类获取当前项目分类下的项目
                    }
                    $school[$k]['xingzhiid'] = $xingzhi_list[$v['xingzhiid']];// 转换学校性质为直观数据
                    $school[$k]['major_cate'] = $this->getTags(['id' => ['in', $v['major_cate']]]); // 获取当前学校的专业分类
                }
                break;
        }
        
        return $school;
    }

    // 专业转换分数映射列表
    public function major2score($major, $want_major)
    {
        // 经管类                 12
        // 法政国际关系           13
        // 社会学/社会心理/福祉   14
        // 日语/日本文学/日语教育 15
        // 情报理工               16.
        // 机械工程               17.
        // 建筑设计/土木          18.
        // 环境设计/园林          19.
        // 医药齿科保健学         20.
        // 融合学科               21
        // 艺术                   22.
        // 其他                   23
        // 设计(机械/产品/工业)   37.
        // 理学                   38
        // 工学                   39
        // 农学/生命科学          40
        // 新闻传媒/社会情报      41
        // 人工智能AI             42
        // 化学材料学             43
        // 教育/教育心理/幼儿教育 45
        $map = [
            12 => [15 => 10, 45 => 10, 13 => 15, 14 => 15, 41 => 15],// 经管
            13 => [15 => 10, 45 => 10, 12 => 15, 14 => 15, 41 => 15],// 法政
            14 => [41 => 10, 45 => 10, 12 => 15, 13 => 15, 14 => 15, 15 => 15],// 社会
            15 => [12 => 10, 14 => 10, 41 => 10, 13 => 15, 45 => 15],// 日语
            16 => [17 => 5, 21 => 5, 38 => 5, 39 => 10, 12 => 15, 42 => 15],// 情报
            17 => [21 => 5, 38 => 5, 39 => 10, 12 => 10, 42 => 10],// 机械
            18 => [12 => 5, 21 => 5, 37 => 5, 22 => 5, 39 => 10, 38 => 10, 19 => 15],// 建筑
            19 => [12 => 5, 21 => 5, 43 => 5, 37 => 10, 18 => 15],// 环境
            20 => [40 => 10, 43 => 10],// 医药
            22 => [37 => 15, 19 => 10],// 艺术
            37 => [22 => 15, 19 => 10],// 设计
            38 => [12 => 5, 43 => 5, 17 => 15, 16 => 10, 39 => 15],// 理学
            39 => [16 => 10, 18 => 10, 42 => 10, 17 => 15, 38 => 15, 12 => 15],// 工学
            41 => [13 => 10, 15 => 10, 45 => 10, 12 => 15, 14 => 15],// 新闻
            42 => [16 => 15, 21 => 10],// 人工智能
            43 => [38 => 5, 21 => 5, 39 => 10],// 化学
            45 => [12 => 10, 14 => 10, 41 => 10, 13 => 15, 15 => 15],// 教育
        ];

        // 返回跨专业后可以获得的分数
        return $map[$major][$want_major] ?: 0;
    }

//    // 自动获取时间规划方案 TODO 如果确定废弃后,删除本段内容
//    public function autoGetPlan($grade='高一')
//    {
//        // 获取当前年,月份
//        $now_year = intval(date('Y'));
//        $now_mouth = intval(date('m'));
//
//        // 年级映射 可以判断$grade的第一个字   规则  高=高中 大=大学
//
//        return $this->getPlanTpl($now_year);
//
//    }
//
//    // 时间规划方案模板
//    public function getPlanTpl($year)
//    {
//        return [
//            ['time' => $year . '年9月-' . ($year + 2) . '年6月', 'do' => '小莺国内学习'],
//            ['time' => ($year + 1) . '年12月', 'do' => '会考'],
//            ['time' => ($year + 2) . '年6月', 'do' => '高考'],
//            ['time' => ($year + 2) . '年6月', 'do' => '毕业'],
//            ['time' => ($year + 2) . '年6月', 'do' => '第一次留考'],
//            ['time' => ($year + 2) . '年7月', 'do' => '日语 TLPT N1(文科) N2(理科)'],
//            ['time' => ($year + 2) . '年8月-10月', 'do' => '第一次校内考'],
//            ['time' => ($year + 2) . '年11月', 'do' => '第二次留考'],
//            ['time' => ($year + 2) . '年12月~' . ($year + 3) . '年6月', 'do' => '第二次校内考'],
//            ['time' => ($year + 3) . '年4月', 'do' => '入学'],
//        ];
//    }

}