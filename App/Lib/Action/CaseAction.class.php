<?php

/**
 * 合同模块
 *
 * */
class CaseAction extends Action {

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
//            var_dump($_REQUEST);
//            exit;
//            echo '<pre>';
//            var_dump($_FILES);
            $pppp = $this->buildInfo($_FILES);
            $pcount = count($_FILES['main_pic']['name']);
            $pres = array_splice($pppp, 0, $pcount);
            $jihuafileid = explode(',', trim($_REQUEST['jihua'], ','));
            $offerfileid = explode(',', trim($_REQUEST['offer'], ','));
            $zpfileid = explode(',', trim($_REQUEST['zp'], ','));
            $otherfileid = explode(',', trim($_REQUEST['other'], ','));

            $filemodel = M('file');
            $jihuadata = $filemodel->where(['file_id' => ['in', $jihuafileid]])->select();
            $offerdata = $filemodel->where(['file_id' => ['in', $offerfileid]])->select();
            $zpdata = $filemodel->where(['file_id' => ['in', $zpfileid]])->select();
            $otherdata = $filemodel->where(['file_id' => ['in', $otherfileid]])->select();



//            echo '<pre>';
//            var_dump($pres);
//            exit;
//            var_dump($_FILES['main_pic']);exit;
            $reqdata = I('post.');
            if (!empty($jihuadata)) {
                foreach ($jihuadata as $k1 => $v1) {
                    $reqdata['plains'] .= $v1['name'] . ',';
                }
            }
            $reqdata['add_ts'] = time();
            $reqdata['update_time'] = time();
            $reqdata['from_p'] = $_SESSION['full_name'];
            $m_product_images = M('cases');
            $rfilecase = M('rFileCases');
            $res = $m_product_images->add($reqdata);
//            var_dump($jihuadata);exit;
            if (!empty($jihuadata)) {
                foreach ($jihuadata as $k => $v) {
                    $jihuareq['file_id'] = $v['file_id'];
                    $jihuareq['cases_id'] = $res;
                    $jihuareq['type'] = 1;
                    $rfilecase->add($jihuareq);
                }
            }
            if (!empty($offerdata)) {
                foreach ($offerdata as $k => $v) {
                    $offerreq['file_id'] = $v['file_id'];
                    $offerreq['cases_id'] = $res;
                    $offerreq['type'] = 2;
                    $rfilecase->add($offerreq);
                }
            }
            if (!empty($zpdata)) {
                foreach ($zpdata as $k => $v) {
                    $zpreq['file_id'] = $v['file_id'];
                    $zpreq['cases_id'] = $res;
                    $zpreq['type'] = 3;
                    $rfilecase->add($zpreq);
                }
            }
            if (!empty($otherdata)) {
                foreach ($otherdata as $k => $v) {
                    $otherreq['file_id'] = $v['file_id'];
                    $otherreq['cases_id'] = $res;
                    $otherreq['type'] = 4;
                    $rfilecase->add($otherreq);
                }
            }
            if ($res) {
                alert('success', '新增成功！', U('case/index'));
            }

//            if (array_sum($_FILES['main_word']['size']) != '') {
//                //如果有文件上传 上传附件
//                import('@.ORG.UploadFile');
//                //导入上传类
//                $upload = new UploadFile();
//                //设置上传文件大小
//                $upload->maxSize = 20000000;
//                //设置附件上传目录
//                $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
//                $upload->allowExts = array('pdf', 'doc', 'txt', 'docx', 'xls', 'xlsx', 'ppt', 'jpg', 'jpeg', 'png', 'gif'); // 设置附件上传类型
//                $upload->saveName = array('uniqid', '', true);
////                $upload->thumb = true; //生成缩图
////                $upload->thumbRemoveOrigin = false; //是否删除原图
//                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
//                    $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
//                }
//                $upload->savePath = $dirname;
//
//                if (!$upload->uploadOne($_FILES['main_word'], $dirname)) {// 上传错误提示错误信息
//                    echo 2;
//                    echo iconv('UTF-8', 'GB2312', $upload->getErrorMsg());
//                    exit;
//                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
//                } else {// 上传成功 获取上传文件信息
//                    $info = $upload->getUploadFileInfo();
////                    var_dump($info);exit;
//                    if (is_array($info) && !empty($info)) {
//                        $upload = $dirname . $info['savename'];
//                    } else {
//                        $this->error('附件上传失败2，请重试！');
//                    }
//                    $reqdata['words'] = $dirname . $info['savename']; //缩略图
//                    $reqdata['twords'] = $dirname . $info['name'];
//                }
//            }
//            if ($m_product_images->add($reqdata)) {
//                alert('success', '新增成功！', U('case/index'));
//            }
        }
    }

    public function delete() {
        if (IS_AJAX && IS_POST) {
            $deletedata = M('cases')->where(['id' => ['in', explode(',', $_REQUEST['id'])]])->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function edit() {
        $casedata = M('cases')->where(['id' => $_REQUEST['id']])->find();

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
        $rfile = M('rFileCases')->where(['cases_id' => $_REQUEST['id']])->select();
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
        $this->assign('ids',$_REQUEST['id']);
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
        $productdata = M('product')->where(['is_deleted'=>0])->select();
        $this->assign('productdata', $productdata);
//        var_dump($productdata);
        $this->assign('cid', $_REQUEST['id']);
        $this->assign('casedata', $casedata);
        $this->display();
    }

    public function editdata() {
//        if (IS_POST) {
        
        $id = $_REQUEST['cid'];
        $fid = $_REQUEST['fid'];
        $reqdata = I('post.');
        $reqdata['update_time'] = time();
        $reqdata['from_p'] = $_SESSION['full_name'];
        
        
        $jihuafileid = explode(',', trim($_REQUEST['jihua'], ','));
        $offerfileid = explode(',', trim($_REQUEST['offer'], ','));
        $zpfileid = explode(',', trim($_REQUEST['zp'], ','));
        $otherfileid = explode(',', trim($_REQUEST['other'], ','));

        $filemodel = M('file');
        $jihuadata = $filemodel->where(['file_id' => ['in', $jihuafileid]])->select();
        $offerdata = $filemodel->where(['file_id' => ['in', $offerfileid]])->select();
        $zpdata = $filemodel->where(['file_id' => ['in', $zpfileid]])->select();
        $otherdata = $filemodel->where(['file_id' => ['in', $otherfileid]])->select();
        
        $rfilecase = M('rFileCases');
        $casedatas = M('cases')->where(['id'=>$id])->find();
//            var_dump($jihuadata);exit;
        if (!empty($jihuadata)) {
            foreach ($jihuadata as $k => $v) {
                $editplains .= $v['name'] . ',';
                $jihuareq['file_id'] = $v['file_id'];
                $jihuareq['cases_id'] = $id;
                $jihuareq['type'] = 1;
                $rfilecase->add($jihuareq);
            }
            $reqdata['plains'] .= $casedatas['plains'] . $editplains;
        }
        if (!empty($offerdata)) {
            foreach ($offerdata as $k => $v) {
                $offerreq['file_id'] = $v['file_id'];
                $offerreq['cases_id'] = $id;
                $offerreq['type'] = 2;
                $rfilecase->add($offerreq);
            }
        }
        if (!empty($zpdata)) {
            foreach ($zpdata as $k => $v) {
                $zpreq['file_id'] = $v['file_id'];
                $zpreq['cases_id'] = $id;
                $zpreq['type'] = 3;
                $rfilecase->add($zpreq);
            }
        }
        if (!empty($otherdata)) {
            foreach ($otherdata as $k => $v) {
                $otherreq['file_id'] = $v['file_id'];
                $otherreq['cases_id'] = $id;
                $otherreq['type'] = 4;
                $rfilecase->add($otherreq);
            }
        }
        $adddata = M('cases')->where(['id' => $id])->save($reqdata);
        try {
            if ($adddata) {
                alert('success', '修改成功！', U('case/index'));
                $this->ajaxReturn(['status' => true]);
            }
        } catch (\Exception $e) {
            $this->ajaxReturn($e->getMessage());
        }
//        }
    }

    public function detail() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('cases')->where(['id' => $id])->find();
        }

        $rfile = M('rFileCases')->where(['cases_id' => $id])->select();
        $usermodel = M('user');
        $newfilearr = [];
        foreach ($rfile as $k => $v) {
            $rfile[$k]['filedata'] = $res = M('file')->where(['file_id' => $v['file_id']])->find();
            $userdata = $usermodel->where(['role_id'=>$res['role_id']])->find();
//            $res['file_path'] = substr($res['file_path'],1);
            $rfile[$k]['filedata']['create_date'] = date('Y-m-d H:i_s', $res['create_date']);
//            $rfile[$k]['filedata']['file_path'] = substr($res['file_path'],1);
            if ($v['type'] == 1) {

                static $i = 0;
                $newfilearr[1][] = $res;
                $newfilearr[1][$i]['no_p_file_path'] = substr($res['file_path'],1);
                $newfilearr[1][$i]['create_time'] = date('Y-m-d ',$res['create_date']);
                $newfilearr[1][$i]['from_pp'] = $userdata['full_name'];
                ++$i;
            } else if ($v['type'] == 2) {
                static $ii = 0;
                $newfilearr[2][] = $res;
                $newfilearr[2][$ii]['no_p_file_path'] = substr($res['file_path'],1);
                $newfilearr[2][$ii]['create_time'] = date('Y-m-d ',$res['create_date']);
                $newfilearr[2][$ii]['from_pp'] = $userdata['full_name'];
                ++$ii;
            } else if ($v['type'] == 3) {
                static $iii = 0;
                $newfilearr[3][] = $res;
                $newfilearr[3][$iii]['no_p_file_path'] = substr($res['file_path'],1);
                $newfilearr[3][$iii]['create_time'] = date('Y-m-d ',$res['create_date']);
                $newfilearr[3][$iii]['from_pp'] = $userdata['full_name'];
                ++$iii;
            } else if ($v['type'] == 4) {
                static $iiii=0;
                $newfilearr[4][] = $res;
                $newfilearr[4][$iiii]['no_p_file_path'] = substr($res['file_path'],1);
                $newfilearr[4][$iiii]['create_time'] = date('Y-m-d ',$res['create_date']);
                $newfilearr[4][$iiii]['from_pp'] = $userdata['full_name'];
                ++$iiii;
            }
        }
//        var_dump($newfilearr);exit;
        $detailData['files'] = $newfilearr;
//      echo '<pre>';
//      var_dump($detailData);exit;
        $this->ajaxReturn($detailData);

        $product = M('product')->select();
        if (!empty($detailData)) {
            $detailData['pic'] = substr($detailData['pic'], 1);
            $detailData['words'] = substr($detailData['words'], 1);
            $this->assign('details', $detailData);
        }
//        
        $wordpos = strpos(substr($detailData['words'], 1), '.');
        $wordext = substr(substr($detailData['words'], 1), $wordpos + 1);
        $detailData['wordext'] = $wordext;
//        var_dump($detailData);exit;
        foreach ($product as $k => $v) {
            if ($detailData['contract_product'] == $v['product_id']) {
                $detailData['contract_product'] = $v['name'];
            }
        }
        $pr = strrpos($detailData['tpic'], '/');
        $pname = substr($detailData['tpic'], $pr + 1);
        $detailData['pname'] = $pname;
        $wr = strrpos($detailData['twords'], '/');
        $wname = substr($detailData['twords'], $pr + 1);
        $detailData['wname'] = $wname;
        echo '<pre>';
        print_r($detailData);
        exit;
        $this->ajaxReturn($detailData);
        $this->assign('wordext', $wordext);
        $this->assign('product', $product);
//        var_dump($detailData);
        $this->display();
    }

    public function detail_1() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('cases')->where(['id' => $id])->find();
        }
//        
        $product = M('product')->select();
        $rfile = M('rFileCases')->where(['cases_id' => $id])->select();
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
        $this->assign('newf',$newfilearr);
        
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
                $data = M('cases')->order('add_ts desc')->select();
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

    	// 允许来自 i.xiaoying.net 的请求
//        header("Access-Control-Allow-Origin: http://i.xiaoying.net");

        // 允许所有来源的请求
        header("Access-Control-Allow-Origin: *");

//        if (IS_POST) {
        $wheredata = I('post.');
        $page = $wheredata['page'] ? $wheredata['page'] : 1;
        $rows = $wheredata['rows'] ? $wheredata['rows'] : 15;
        $sidx = $wheredata['sidx'];
        $sord = $wheredata['sord'];
        $pcate = $wheredata['program_category'];
        $mcate = $wheredata['major_category'];
//            
        //姓名，日语，英语，学校级别，专业
        if ($wheredata['lotwhere'] != '' || $pcate != '' || $mcate != '') {

            if (!empty($pcate)) {
                $where['programcate'] = $pcate;
            }
            if (!empty($mcate)) {
                $where['majorcate'] = $mcate;
            }
            $where['names'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['names']);

            // 814 change lqs
            $where['education'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['education']);
            // 814 end

            $where['japan_language'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['japan_language']);
            $where['eng_language'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['eng_language']);
            $where['college_lev'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['college_lev']);
//echo 1;die;
            $where['undergraduate_major'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['undergraduate_major']);
            $where['plains'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
            $datas1 = M('cases')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $datas1 = $this->setDate($datas1);
            if (is_array($datas1)) {
                $count1 = M('cases')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count1;
                $data['total'] = ceil($count1 / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }

            if (!is_array($datas1)) {
                $this->ajaxReturn(['status' => false, 'data' => []]);
            }
        }
        if (empty($wheredata['lotwhere']) || empty($pcate) || empty($mcate)) {
            $count = M('cases')->count();
            $datas = M('cases')->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();


            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
            }
            $catedata = M('product')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['contract_product'] == $v1['product_id']) {
                        $data['list'][$k]['contract_product'] = $v1['name'];
//                        $v['cooperation_cate_id'] = $v1['cooperation_cate_name'];
                    }
                }
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

        var_dump($data);
        return ['data' => $data ?: [], 'sql' => $model->getLastSql(), 'count' => $count];
    }

}
