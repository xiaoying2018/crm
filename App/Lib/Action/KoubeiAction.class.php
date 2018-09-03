<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/8/7
 * Time: 10:09
 */

class KoubeiAction extends Action
{
    public function index()
    {
        if ($this->isAjax()){
            $wheredata = $_REQUEST;
            $name = $wheredata['name'] ? $wheredata['name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
            $sort_field = $wheredata['sidx'] ?: 0;// 排序规则
            $sort = $wheredata['sord'] ?: 0;// 排序规则
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件

            if ($name) $condition['name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            // 如果不是超级管理员,只能看到自己和下属的数据
            if (!session('?admin'))
            {
                $sub_role_ids = getSubRoleByRole(session('role_id'));// 获取当前用户的下属role_ids
                $condition['create_user'] = ['IN',$sub_role_ids];
            }

            // 如果需要排序
            if ($sort && $sort_field)
            {
                $list = M('Koubei')->where($condition)->limit($start,$limit)->order("{$sort_field} {$sort}")->select();
            }else{
                $list = M('Koubei')->where($condition)->limit($start,$limit)->order('create_time desc')->select();
            }

            $count = M('Koubei')->where($condition)->count();

            if ($list) // 按照前端所需格式拼接下载文件名称
            {
                foreach ($list as $k => $v)
                {
                    $list[$k]['create_user'] = M('User')->where(['role_id'=>['eq',$v['create_user']]])->find()['name']?:'-';
                    $list[$k]['create_time'] = $v['create_time']?date('Y-m-d H:i:s',$v['create_time']):'-';
                    $list[$k]['update_user'] = M('User')->where(['role_id'=>['eq',$v['update_user']]])->find()['name']?:'-';
                    $list[$k]['update_time'] = $v['update_time']?date('Y-m-d H:i:s',$v['update_time']):'-';
                    $list[$k]['status'] = $v['status']?'显示':'隐藏';
                }
            }

            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表

            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }

        $this->display();
    }

    public function add()
    {
        if ($this->isPost()) {

            // 接收表单数据
            if (!($data = I('post.'))) die('缺少参数');
            // 是否上传文件
            if (!$_FILES['file']['size']) die('图片未上传');

            // 过滤数据

            // 上传文件
            $file = $this->uploadKoubei();

            // file 字段赋值
            if (!($data['file'] = $file)) die('图片上传有误');

            $data['mini_img'] = $file;

            $data['create_time'] = time();
            $data['create_user'] = session('role_id');
            $data['update_time'] = time();
            $data['update_user'] = session('role_id');

            // 执行添加操作
            try {
                $res = M('Koubei')->add($data);
                if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=koubei&a=index');

        }

        $this->display();
    }

    public function edit()
    {
        // 如果是更新操作
        if ($this->isPost()) {
            // 接收数据
            if (!($data = I('post.')) || !($id = I('post.id'))) die('缺少参数!');

            // 过滤数据

            // 如果更新文件
            if ($_FILES['file']['size']) {
                $file = $this->uploadKoubei();
                $data['file'] = $file;
                $data['mini_img'] = $file;
            }
            
            $data['update_time'] = time();
            $data['update_user'] = session('role_id');

            // 执行更新操作
            try {
                M('Koubei')->where('id=' . $id)->save($data);
            } catch (\Exception $exception) {
                die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=koubei&a=index');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
        $info = M('Koubei')->find($id);

        $this->assign([
            'info' => $info
        ]);
        $this->display();
    }

    /**
     * 删除
     */
    public function delete()
    {
        // 获取关键参数
        if (!$ids = I('post.id')) die('缺少关键参数');

        // 处理id集合
        $id = implode(',', $ids);

        // 执行删除
        try {
            $res = M('Koubei')->where('id in(' . $id . ')')->delete();
            if (!$res) $this->ajaxReturn('', '删除失败', 0);
        } catch (\Exception $exception) {
            die('删除失败' . $exception->getMessage());
        }

        // 删除成功,返回列表页
        $this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    public function uploadKoubei()
    {
        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
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
}