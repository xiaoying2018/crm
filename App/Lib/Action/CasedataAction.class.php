<?php

/**
 * 合同模块
 *
 * */
class CasedataAction extends Action {

    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     * */
    public function _initialize() {
//            $action = array(
//          'permission'=>array(),
//          'allow'=>array('add_examine','revert','getcurrentstatus','travel_business','travel_two','checktype','check_list','getanalycurrentstatus')
//      );
//      B('Authenticate', $action);
//      $this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
    }

    public function index() {
//        echo 1;
        $programdata = $this->getProgramDatas();
        // echo '<pre>';
        // var_dump($programdata);exit;
        $programcate = [];
        $majorcate = [];
        foreach ($programdata as $k => $v) {
            if ($k == 2) {
                $programcate = $v;
            } else if ($k == 3) {
                $majorcate = $v;
            }
        }
        // echo '<pre>';var_dump($programcate);
        // var_dump($programcate);
        $this->assign('programcate', $programcate);
//        var_dump($programcate);die;
        $this->assign('majorcate', $majorcate);
        $this->display();
    }
    public function getAllProgramcate(){
        $programdata = $this->getProgramDatas();
        $programcate = [];
        foreach ($programdata as $k => $v) {
            if ($k == 2) {
                $programcate = $v;
            }
        }
        $this->ajaxReturn($programcate);
    }

    public function buildInfo($file) {
        $i = 0;
        foreach ($file as $v) {//三维数组转换成2维数组
            if (is_string($v['name'])) { //单文件上传
                $info[$i] = $v;
                $i++;
            } else { // 多文件上传
                foreach ($v['name'] as $key => $val) {//2维数组转换成1维数组
                    //取出一维数组的值，然后形成另一个数组
                    //新的数组的结构为：info=>i=>('name','size'.....)
                    $info[$i]['name'] = $v['name'][$key];
                    $info[$i]['size'] = $v['size'][$key];
                    $info[$i]['type'] = $v['type'][$key];
                    $info[$i]['tmp_name'] = $v['tmp_name'][$key];
                    $info[$i]['error'] = $v['error'][$key];
                    $i++;
                }
            }
        }
        return $info;
    }

    public function add() {
        $productdata = M('product')->where(['is_deleted'=>0])->select();
        $contract = array_column(M('contract')->select(), 'customer_id');
        $customer = M('customer')->field('name,customer_id')->where(['customer_id' => ['in', $contract]])->select();

        $programdata = $this->getProgramDatas();
//        var_dump($programdata);
        $programcate = [];
        $majorcate = [];
        foreach ($programdata as $k => $v) {
            if ($k == 2) {
                $programcate = $v;
            } else if ($k == 3) {
                $majorcate = $v;
            }
        }
        $caseid = $_REQUEST['id'];
        $file_ids = M('rFileCases')->where('cases_id = %d', $caseid)->getField('file_id', true);
        $casefiledata = M('file')->where('file_id in (%s)', implode(',', $file_ids))->select();
//        var_dump($casefiledata);
//        foreach ($casefiledata as $key => $value) {
//            $casefiledata[$key]['owner'] = D('RoleView')->where('role.role_id = %d', $value['role_id'])->find();
//            $casefiledata[$key]['size'] = ceil($value['size'] / 1024);
//            /* 判断文件格式 对应其图片 */
//            $casefiledata[$key]['pic'] = show_picture($value['name']);
//        }
//var_dump($casefiledata);
        $this->assign('programcate', $programcate);
        $this->assign('majorcate', $majorcate);
        $this->assign('customer', $customer);
        $this->assign('productdata', $productdata);
        $this->display();
    }

    public function getfile() {
        $fileids = $_REQUEST['file_id'];
        $fileids = explode(',', $fileids);
        if (!empty($fileids)) {
//            var_dump($filewhere);exit;
            $filedata = M('file')->where(['file_id' => ['in', $fileids]])->select();
            if (!empty($filedata)) {
                foreach ($filedata as $k => $v) {
                    $filedata[$k]['full_name'] = $_SESSION['full_name'];
                    $filedata[$k]['cerate_time'] = date('Y-m-d',$v['create_date']);
                    $filedata[$k]['no_p_file_path'] = substr($v['file_path'],1);
                }
            }
            $this->ajaxReturn($filedata);
        }
    }

    public function delfile() {
        $fileids = $_REQUEST['file_id'];
        $fileids = explode(',', trim($fileids, ','));
        $caseid = $_REQUEST['caseid'];
        if (!empty($fileids)) {
            $where['file_id'] = ['in', $fileids];
            $filedata = M('file')->where($where)->delete();
            if(!empty($caseid)){
                $where['cases_id'] = $caseid;
                $filedata = M('rFileCases')->where($where)->delete();
            }
            $this->ajaxReturn($filedata);
        }
    }

    public function adddata() {
        if (IS_POST) {
            $reqdata = I('post.');
//            $reqdata['add_ts'] = time();
//            $reqdata['update_time'] = time();
            $reqdata['from_p'] = $_SESSION['full_name'];
            $reqdata['createndTime'] = time();
            $m_product_images = M('casedata');
            $rfilecase = M('rFileCases');
            if (array_sum($_FILES['main_pic']['size']) != '') {
//                echo 1;die;
                //如果有文件上传 上传附件
                import('@.ORG.UploadFile');
                //导入上传类
                $upload = new UploadFile();
                //设置上传文件大小
                $upload->maxSize = 20000000;
                //设置附件上传目录
                $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
                $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif'); // 设置附件上传类型
                $upload->saveName = array('uniqid', '', true);
//                $upload->thumb = true; //生成缩图
//                $upload->thumbRemoveOrigin = false; //是否删除原图
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                    $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
                }
                $upload->savePath = $dirname;

                if (!$upload->uploadOne($_FILES['main_pic'], $dirname)) {// 上传错误提示错误信息
//                    echo 2;
//                    echo iconv('UTF-8', 'GB2312', $upload->getErrorMsg());
//                    exit;
                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                } else {// 上传成功 获取上传文件信息
                    $info = $upload->getUploadFileInfo();
//                    var_dump($info);exit;
//                    if (is_array($info) && !empty($info)) {
//                        $upload = $dirname . $info['name'];
//                    } else {
//                        $this->error('附件上传失败2，请重试！');
//                    }
//                    $reqdata['t_img_url'] = $dirname . $info['savename']; //缩略图
                    $reqdata['certs'] = $dirname . $info['savename'];
                }
            }
            $res = $m_product_images->add($reqdata);
//            var_dump($jihuadata);exit;
            if ($res) {
                alert('success', '新增成功！', U('casedata/index'));
            }
        }
    }

    public function delete() {
        if (IS_AJAX && IS_POST) {
            $deletedata = M('casedata')->where(['id' => ['in', explode(',', $_REQUEST['id'])]])->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function edit() {
        $casedata = M('casedata')->where(['id' => $_REQUEST['id']])->find();
        $this->assign('ids',$_REQUEST['id']);
        $this->assign('cid', $_REQUEST['id']);
        $casedata['certs'] = substr($casedata['certs'],1);
        $this->assign('casedata', $casedata);
        $this->display();
    }

    public function editdata() {
//        if (IS_POST) {

        $id = $_REQUEST['cid'];
        $fid = $_REQUEST['fid'];
        $reqdata = I('post.');
        $reqdata['updateTime'] = time();
        if (array_sum($_FILES['main_pic']['size']) != '') {
//                echo 1;die;
            //如果有文件上传 上传附件
            import('@.ORG.UploadFile');
            //导入上传类
            $upload = new UploadFile();
            //设置上传文件大小
            $upload->maxSize = 20000000;
            //设置附件上传目录
            $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
            $upload->allowExts = array('jpg', 'jpeg', 'png', 'gif'); // 设置附件上传类型
            $upload->saveName = array('uniqid', '', true);
//                $upload->thumb = true; //生成缩图
//                $upload->thumbRemoveOrigin = false; //是否删除原图
            if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
            }
            $upload->savePath = $dirname;

            if (!$upload->uploadOne($_FILES['main_pic'], $dirname)) {// 上传错误提示错误信息
//                    echo 2;
//                    echo iconv('UTF-8', 'GB2312', $upload->getErrorMsg());
//                    exit;
                alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
            } else {// 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
//                    var_dump($info);exit;
//                    if (is_array($info) && !empty($info)) {
//                        $upload = $dirname . $info['name'];
//                    } else {
//                        $this->error('附件上传失败2，请重试！');
//                    }
//                    $reqdata['t_img_url'] = $dirname . $info['savename']; //缩略图
                $reqdata['certs'] = $dirname . $info['savename'];
            }
        }

        $adddata = M('casedata')->where(['id' => $id])->save($reqdata);
        try {
            if ($adddata) {
                alert('success', '修改成功！', U('casedata/index'));
                $this->ajaxReturn(['status' => true]);
            }
        } catch (\Exception $e) {
            $this->ajaxReturn($e->getMessage());
        }
//        }
    }
    private function getshenfen($xueli){
        switch ($xueli){
            case 14:return '修士';break;
            case 18:return '本科';break;
            case 15:return '预科';break;
            case 13:return 'SGU';break;
            case 82:return '高中专门';break;
            case 89:return '语言';break;
            default:return '';break;
        }
    }
    public function detail() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('casedata')->where(['id' => $id])->find();
            $detailData['sf'] = $this->getshenfen($detailData['xueli']);
        }
        if(!empty($detailData)){
            $this->ajaxReturn(['status'=>true,'data'=>$detailData]);

        }else{
            $this->ajaxReturn(['status'=>false,'data'=>[]]);
        }
    }


    public function catemanage() {
        $this->display();
    }



    public function setDate($res) {
        foreach ($res as $k => $v) {
            $res[$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
        }
        return $res;
    }
    public function setDates($res,$field,$type){
        foreach ($res as $k => $v) {
            $res[$k][$field] = date($type, $v[$field]);
        }
        return $res;
    }

    public function search() {
//        if (IS_POST) {
        $wheredata = I('post.');
        $page = $wheredata['page'] ? $wheredata['page'] : 1;
        $rows = $wheredata['rows'] ? $wheredata['rows'] : 15;
        $sidx = $wheredata['sidx'];
        $sord = $wheredata['sord'];

        //姓名，日语，英语，学校级别，专业
        if ($wheredata['lotwhere'] != '') {
            $where['name'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('casedata')->where($where)->limit(($page - 1) * $rows, $rows)->order('createndTime desc')->select();
            $datas1 = $this->setDates($datas1,'createndTime','Y-m-d H:i:s');

            if (is_array($datas1)) {
                $count1 = M('casedata')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['name']);


            $where['univ'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('casedata')->where($where)->limit(($page - 1) * $rows, $rows)->order('createndTime desc')->select();
            $datas1 = $this->setDates($datas1,'createndTime','Y-m-d H:i:s');
            if (is_array($datas1)) {
                $count1 = M('casedata')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['univ']);

            if (!is_array($datas1)) {
                $this->ajaxReturn(['status' => false, 'data' => []]);
            }
        }


        if (empty($wheredata['lotwhere'])) {
            ##录取大学
            if(!empty($wheredata['univ'])){
                $where['univ'] = $wheredata['univ'];
            }
            ##录取年份
            if(!empty($wheredata['year'])){
                if(!$wheredata['year']=='2010'){
                    $where['year'] = ['between',[2000,2012]];
                }else{
                    $where['year'] = $wheredata['year'];
                }
            }
            ##录取类型
            if(!empty($wheredata['xueli'])){
                $where['xueli'] = $wheredata['xueli'];
            }
            ##热门标签
            if(!empty($wheredata['hottag'])){
                $where['hottag'] = $wheredata['hottag'];
            }
            ##专业
            if(!empty($wheredata['college'])){
                $where['college'] = $wheredata['college'];
            }

            $count = M('casedata')->where($where)->count();
            $datas = M('casedata')->where($where)->limit(($page - 1) * $rows, $rows)->order('createndTime desc')->select();
            $datas = $this->setDates($datas,'createndTime','Y-m-d H:i:s');

            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
            $this->ajaxReturn(['status' => true, 'data' => $data]);
//            }
        } else {
            $this->ajaxReturn(['status'=>false]);
        }
    }

    public function catesearch() {
        if (IS_POST) {
            $catedata = M('cooperations_cate')->select();
            foreach ($catedata as $k => $v) {
                $v['add_ts'] = date('Y-m-d ', $v['add_ts']);
            }
            $this->ajaxReturn(['status' => true, 'data' => $catedata]);
        }
    }

    public function getProgramDatas($where = []) {
        $defaultFields = '*';
        $fields = is_null($fields) ? $defaultFields : $fields;
        $model = new ProgramModel();
        $res = $model->getTag();
        return $res;
        $limit = $where['limit'];
        unset($where['limit']);
        $count = (int) $model->where($where)->count();
        $data = $model->field($fields)
            ->where($where)->limit($limit[0], $limit[1])->select();

        var_dump($data);
        return ['data' => $data ?: [], 'sql' => $model->getLastSql(), 'count' => $count];
    }

    public function frontsearch() {
//        if (IS_POST) {
        $wheredata = I('post.');
        $page = $wheredata['page'] ? $wheredata['page'] : 1;
        $rows = $wheredata['rows'] ? $wheredata['rows'] : 15;
        $sidx = $wheredata['sidx'];
        $sord = $wheredata['sord'];
        $where = [];
        ##学历
        if(!empty($wheredata['education'])){
            $where['education'] = $wheredata['education'];
        }
        ##服务项目
        if(!empty($wheredata['programcate'])){
            $where['programcate'] = $wheredata['programcate'];
        }
        ##日语水平
        if(!empty($wheredata['japan_language'])){
            $where['japan_language'] = $wheredata['japan_language'];
        }
        ##录取年份
        if(!empty($wheredata['receive_year'])){
            $where['receive_year'] = $wheredata['receive_year'];
        }
        ##名校报考经验
        if(!empty($wheredata['receive_college'])){
            $where['receive_college'] = $wheredata['receive_college'];
        }

        $count = M('cases')->where($where)->count();
        $datas = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
        if(!empty($datas)){
            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
                $fileids[] = M('rFileCases')->where(['type'=>3,'cases_id'=>$v['id']])->getField('file_id');
                $data['list'][$k]['headfile'] = M('file')->where(['file_id'=>['in',$fileids]])->find();
                unset($fileids);
            }
            $catedata = M('product')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['contract_product'] == $v1['product_id']) {
                        $data['list'][$k]['contract_product'] = $v1['name'];
                    }
                }
            }
            $this->ajaxReturn(['status' => true, 'data' => $data]);
        }else{
            $this->ajaxReturn(['status' => false, 'data' => []]);
        }
    }

}
