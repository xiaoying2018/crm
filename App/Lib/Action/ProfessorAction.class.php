<?php

/**
 * 合同模块
 *
 * */
class ProfessorAction extends Action {

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
//        echo "<pre>";
//        var_dump($programdata);exit();
        $majorcate = [];
        foreach ($programdata[3] as $k => $v) {
            if ($v['type'] == 3) {
                $majorcate[] = $v;
            }
        }
//        var_dump($majorcate);
        $this->assign('majorcate', $majorcate);
        $this->display();
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
        $productdata = M('product')->where(['is_deleted' => 0])->select();
        $contract = array_column(M('contract')->select(), 'customer_id');
        $customer = M('customer')->field('name,customer_id')->where(['customer_id' => ['in', $contract]])->select();

        $programdata = $this->getProgramDatas();
//        var_dump($programdata);
        $programcate = [];
        $majorcate = [];
        foreach ($programdata[3] as $k => $v) {
            if ($v['type'] == 2) {
                $programcate[] = $v;
            } else if ($v['type'] == 3) {
                $majorcate[] = $v;
            }
        }
        $caseid = $_REQUEST['id'];
        $file_ids = M('rFileCases')->where('professor_id = %d', $caseid)->getField('file_id', true);
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



    public function adddata() {
        if (IS_POST) {

            $reqdata = I('post.');
            $reqdata['add_ts'] = time();
            $reqdata['update_time'] = time();
            $reqdata['from_p'] = $_SESSION['full_name'];
            $m_product_images = M('professor');


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
                    $reqdata['t_photo'] = $dirname . $info['savename']; //缩略图
                    $reqdata['photo'] = $dirname . $info['name'];
                }
            }
            if ($m_product_images->add($reqdata)) {
                alert('success', '新增成功！', U('professor/index'));
            }
        }
    }

    public function delete() {
        if (IS_AJAX && IS_POST) {
            $deletedata = M('professor')->where(['id' => ['in', explode(',', $_REQUEST['id'])]])->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function edit() {
        $casedata = M('professor')->where(['id' => $_REQUEST['id']])->find();
//        var_dump($casedata);
        $casedata['photo'] = substr($casedata['photo'], 1);
        $casedata['t_photo'] = substr($casedata['t_photo'], 1);
        $this->assign('professor', $casedata);
        $contract = array_column(M('contract')->select(), 'customer_id');
        $customer = M('customer')->field('name,customer_id')->where(['customer_id' => ['in', $contract]])->select();
        $programdata = $this->getProgramDatas();
//        var_dump($programdata);
        $programcate = [];
        $majorcate = [];
        foreach ($programdata[3] as $k => $v) {
            if ($v['type'] == 2) {
                $programcate[] = $v;
            } else if ($v['type'] == 3) {
                $majorcate[] = $v;
            }
        }
//        var_dump($majorcate);
        $rfile = M('rFileCases')->where(['professor_id' => $_REQUEST['id']])->select();
//        var_dump($rfile);exit;
        $newfilearr = [];
        foreach ($rfile as $k => $v) {
            $rfile[$k]['filedata'] = $res = M('file')->where(['file_id' => $v['file_id']])->find();
            $rfile[$k]['filedata']['create_date'] = date('Y-m-d H:i_s', $res['create_date']);
            if ($v['type'] == 1) {
                $newfilearr[1][] = $res;
            } else if ($v['type'] == 2) {
                $newfilearr[2][] = $res;
            } else if ($v['type'] == 3) {
                $newfilearr[3][] = $res;
            } else if ($v['type'] == 4) {
                $newfilearr[4][] = $res;
            }
        }
        $casedata['files'] = $newfilearr;
//        echo '<pre>';
//        var_dump($programcate);exit;
        $this->assign('ids', $_REQUEST['id']);
        $this->assign('programcate', $programcate);
        $this->assign('majorcate', $majorcate);
//        var_dump($customer);
        $this->assign('customer', $customer);

        $casedata['pic'] = substr($casedata['pic'], 1);
        $wordpos = strpos(substr($casedata['words'], 1), '.');
        $wordext = substr(substr($casedata['words'], 1), $wordpos + 1);

        $pr = strrpos($casedata['tpic'], '/');
        $pname = substr($casedata['tpic'], $pr + 1);
        $casedata['pname'] = $pname;
        $wr = strrpos($casedata['twords'], '/');
        $wname = substr($casedata['twords'], $pr + 1);
        $casedata['wname'] = $wname;
        $this->assign('wordext', $wordext);
        $productdata = M('product')->where(['is_deleted' => 0])->select();
        $this->assign('productdata', $productdata);
//        var_dump($productdata);
        $this->assign('cid', $_REQUEST['id']);
        $this->assign('casedata', $casedata);
        $this->display();
    }

    public function editdata() {
        $id = $_REQUEST['ids'];
        $fid = $_REQUEST['fid'];
        $reqdata = I('post.');
        $reqdata['update_time'] = time();
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
                $reqdata['t_photo'] = $dirname . $info['savename']; //缩略图
                $reqdata['photo'] = $dirname . $info['name'];
            }
        }


        $adddata = M('professor')->where(['id' => $id])->save($reqdata);
        if ($adddata !== false) {
            alert('success', '修改成功！', U('professor/index'));
            $this->ajaxReturn(['status' => true]);
        } else {
            alert('error', '修改失败', U('professor/index'));
        }
    }

    public function detail() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('professor')->where(['id' => $id])->find();
        }
        $last = strrpos($detailData['photo'], '/');
        $pname = substr($detailData['photo'], $last+1);
        $detailData['pname'] = $pname;
        if($detailData['nature_id']==1){
            $detailData['nature_id'] = '国立';
        }else if($detailData['nature_id']==2){
            $detailData['nature_id'] = '公立';
        }else if($detailData['nature_id']==3){
            $detailData['nature_id'] = '私立';
        }
//        $programdata = $this->getProgramDatas();
//        $majorcate = [];
//        foreach ($programdata as $k => $v) {
//            if ($v['type'] == 3 && $v['name']==$detailData['department']) {
//                $detailData['']
//            }
//        }
        
        $this->ajaxReturn($detailData);
    }

    public function detail_1() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('professor')->where(['id' => $id])->find();
        }
//        
        $product = M('product')->select();
        $rfile = M('rFileCases')->where(['professor_id' => $id])->select();
//        var_dump($rfile);exit;
        $newfilearr = [];
        foreach ($rfile as $k => $v) {
            $rfile[$k]['filedata'] = $res = M('file')->where(['file_id' => $v['file_id']])->find();
            $rfile[$k]['filedata']['create_date'] = date('Y-m-d H:i_s', $res['create_date']);
            if ($v['type'] == 1) {
                $newfilearr[1][] = $res;
            } else if ($v['type'] == 2) {
                $newfilearr[2][] = $res;
            } else if ($v['type'] == 3) {
                $newfilearr[3][] = $res;
            } else if ($v['type'] == 4) {
                $newfilearr[4][] = $res;
            }
        }
        if (!empty($detailData)) {
            $detailData['pic'] = substr($detailData['pic'], 1);
            $detailData['words'] = substr($detailData['words'], 1);
            $detailData['files'] = $newfilearr;
            $this->assign('details', $detailData);
        }
//        
        $this->assign('newf', $newfilearr);

        $wordpos = strpos(substr($detailData['words'], 1), '.');
        $wordext = substr(substr($detailData['words'], 1), $wordpos + 1);
        $detailData['wordext'] = $wordext;
//        echo '<pre>';
//        var_dump($detailData);exit;
        foreach ($product as $k => $v) {
            if ($detailData['contract_product'] == $v['product_id']) {
                $detailData['contract_product'] = $v['name'];
            }
        }
        $this->assign('wordext', $wordext);
        $this->assign('product', $product);
        $this->display();
    }

    public function catemanage() {
        $this->display();
    }

    public function addcatedata() {
        if (IS_POST) {
            $catedata = I('post.');
            $catedata['add_ts'] = time();
            $adddata = M('cooperations_cate')->add($catedata);
            try {
                if ($adddata) {
                    alert('success', '新增成功！', U('cooperations/catemanage'));
                    $this->ajaxReturn(['status' => true]);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage());
            }
        }
    }

    public function editcate() {
        $id = $_REQUEST['id'];
        $catedata = M('cooperations_cate')->where(['cooperation_cate_id' => $id])->find();
        $this->assign('cid', $id);
        $this->assign('catedata', $catedata);
        $this->display();
    }

    public function editcatedata() {
        if (IS_POST) {
            $catedata = I('post.');
            $cates = M('case')->where(['id' => $catedata['cid']])->save($catedata);
            if ($cates !== false) {
                alert('success', '修改成功！', U('cooperations/catemanage'));
            }
        }
    }

    public function deletecate() {
        if (IS_AJAX && IS_POST) {
            $datas = M('cooperations')->where(['cooperation_cate_id' => $_REQUEST['id']])->find();
            if (is_array($datas)) {
                $this->ajaxReturn(['status' => 3]);
            }
            $deletedata = M('cooperations_cate')->where(['cooperation_cate_id' => $_REQUEST['id']])->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function aa() {
        $a = ',a,ds,d';
        $a = trim($a, ',');
        echo $a;
    }

    public function searchss() {
        if (IS_POST) {
            $where = I('post.');
            if (empty($where)) {
                $data = M('professor')->order('add_ts desc')->select();
                foreach ($data as $k => $v) {
                    $data[$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
                }
                $catedata = M('product')->select();
                foreach ($data as $k => $v) {
                    foreach ($catedata as $k1 => $v1) {
                        if ($v['contract_product'] == $v1['product_id']) {
                            $data[$k]['contract_product'] = $v1['name'];
//                        $v['cooperation_cate_id'] = $v1['cooperation_cate_name'];
                        }
                    }
                }
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            } else {
                $data = M('cooperations')->where(['cooperation_cate_id' => $where['cooperation_cate_id']])->order('add_ts desc')->select();
                foreach ($data as $k => $v) {
                    $data[$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
                }
                $catedata = M('product')->select();
                foreach ($data as $k => $v) {
                    foreach ($catedata as $k1 => $v1) {
                        if ($v['contract_product'] == $v1['product_id']) {
                            $data[$k]['contract_product'] = $v1['name'];
//                        $v['cooperation_cate_id'] = $v1['cooperation_cate_name'];
                        }
                    }
                }
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
        }
    }

    public function setDate($res) {
        foreach ($res as $k => $v) {
            $res[$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
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
        $mcate = $_REQUEST['major_category'];
//            
        //姓名，日语，英语，学校级别，专业
        if ($wheredata['lotwhere'] != '' || $mcate != '') {

            if (!empty($mcate)) {
                $where['department'] = $mcate;
            }
//            $this->ajaxReturn($where);
            $where['name'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('professor')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('professor')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['name']);
            $where['research_keywords'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('professor')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('professor')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['department']);
            if (is_array($datas1)) {
                $count1 = M('professor')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }

            if (!is_array($datas1)) {
                $this->ajaxReturn(['status' => false, 'data' => []]);
            }
        }
        if (empty($wheredata['lotwhere']) || empty($mcate)) {
            $count = M('professor')->count();
            $datas = M('professor')->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
//            foreach ($datas as $k=>$v){
//                if($v['nature_id']==1){
//                    $datas[$k]['nature_id'] = '国立';
//                }else if($v['nature_id']==2){
//                    $datas[$k]['nature_id'] = '公立';
//                }else if($v['nature_id']==3){
//                    $datas[$k]['nature_id'] = '私立';
//                }
//            }

            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
            }


            $this->ajaxReturn(['status' => true, 'data' => $data]);
//            }
        } else {
            
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
        return ['data' => $data ?: [], 'sql' => $model->getLastSql(), 'count' => $count];
    }

}
