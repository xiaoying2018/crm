<?php

/**
 * 合同模块
 *
 * */
class CooperationsAction extends Action {

    /**
     * 用于判断权限
     * @permission 无限制
     * @allow 登录用户可访问
     * @other 其他根据系统设置
     * */
    public function _initialize() {
//            $action = array(
//			'permission'=>array(),
//			'allow'=>array('add_examine','revert','getcurrentstatus','travel_business','travel_two','checktype','check_list','getanalycurrentstatus')
//		);
//		B('Authenticate', $action);
//		$this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
    }

    public function index() {
        $getcate = I('get.catid');
        $getcates = I('get.cateid');
//        echo $getcates;
        $types = I('get.types');
//        if ($getcates == 8) {
//            $catedate = M('cooperations_cate')->where(['cate_pid' => $getcates])->select();
//        } else {
            $tcate = M('cooperations_cate')->where(['cooperation_cate_id' => $getcates])->find();
            $catedate = M('cooperations_cate')->select();
//        }
        $indexdata = M('cooperations')->where($wherecate)->select();
        $this->assign('catedate', $catedate);
        $this->assign('datas', $indexdata);
        $this->display();
    }
    public function index1() {
        $getcate = I('get.catid');
        $getcates = I('get.cateid');
        $types = I('get.types');
        if ($getcates == 9) {
            $catedate = M('cooperations_cate')->where(['cate_pid' => $getcates])->select();
        } else {
            $tcate = M('cooperations_cate')->where(['cooperation_cate_id' => $getcates])->find();
            $catedate = M('cooperations_cate')->where(['cate_pid' => $tcate['cate_pid']])->select();
        }
        $indexdata = M('cooperations')->where($wherecate)->select();
        $this->assign('catedate', $catedate);
        $this->assign('datas', $indexdata);
        $this->display();
    }

    public function add() {
        $catedata = M('cooperations_cate')->select();
        $this->assign('coopcate', $catedata);
        $this->display();
    }

    public function adddata() {
//        var_dump($_REQUEST);die;
        if (IS_POST) {
            $rfilecoops = M('rFileCoops');

            $reqdata = I('post.');
            $reqdata['from_p'] = $_SESSION['full_name'];
            $reqdata['add_ts'] = time();
            $reqdata['update_time'] = time();
            $adddata = M('cooperations')->add($reqdata);
            try {
                if ($adddata) {
                    if(!empty($_REQUEST['jihua'])){
                        $fildids = explode(',',trim($_REQUEST['jihua'],','));
                    }
                    foreach($fildids as $k=>$v){
                        $adds['file_id'] = $v;
                        $adds['coop_id'] = $adddata;
                        $adds['type'] = 1;
                        $rfilecoops->add($adds);
                    }
                    alert('success', '新增成功！', U('cooperations/index'));
                    $this->ajaxReturn(['status' => true]);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage());
            }
        }
    }

    public function delete() {
        if (IS_POST) {
            $where['cooperation_id'] = ['in', explode(',', $_REQUEST['id'])];
            $deletedata = M('cooperations')->where($where)->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function edit() {
        $coopdata = M('cooperations')->where(['cooperation_id' => $_REQUEST['id']])->find();
        $catedata = M('cooperations_cate')->select();
        $rfilecoops = M('rFileCoops')->where(['coop_id'=>$_REQUEST['id']])->select();
        $fileids = array_column($rfilecoops,'file_id');
        $filedata = M('file')->where(['file_id'=>['in',$fileids]])->select();
        foreach($filedata as $k=>$v){
            $filedata[$k]['role_id'] = M('user')->where(['user_id'=>$v['role_id']])->find()['full_name'];
            $filedata[$k]['size'] = ceil($v['size']/1024);
            $filedata[$k]['create_date'] = date('Y-m-d',$v['create_date']);
            $filedata[$k]['file_path'] = substr($v['file_path'],1);
             $a = substr($v['file_path'],strrpos($v['file_path'],'.')+1);
            if($a != 'jpg' || $a !='jpeg' || $a !='pdf' || $a!='png' || $a!='gif' || $a!='bmp'){
                $filedata[$k]['ext'] = 1;
            }else{
                $filedata[$k]['ext'] = 2;
            }
        }
//        var_dump($filedata);
        $this->assign('oldfileids',implode(',',array_column($filedata,'file_id')));
        $this->assign('filedata',$filedata);
//        var_dump($filedata);
        $this->assign('coopcate', $catedata);
        $this->assign('cid', $_REQUEST['id']);
        $this->assign('coopdata', $coopdata);
        $this->display();
    }

    public function editdata() {
        if (IS_POST) {
            $id = $_REQUEST['cid'];
//            var_dump(I('post.'));die;
            $oldfileids = explode(',',$_REQUEST['oldfileids']);
            $newfileids = explode(',',trim($_REQUEST['jihua'],','));

            $fileids = array_merge($oldfileids,$newfileids);
            $filedata = M('file')->where(['file_id'=>['in',$fileids]])->select();
            $endfileids = array_column($filedata,'file_id');
//            var_dump($endfileids);die;
            M('rFileCoops')->where(['coop_id'=>$id])->delete();
            if(!empty($endfileids)){
                foreach ($endfileids as $k=>$v){
                    $addrdata['file_id'] = $v;
                    $addrdata['coop_id'] = $id;
                    $addrdata['type'] = 1;
                    M('rFileCoops')->add($addrdata);
                }
            }
            $reqdata = I('post.');
            $reqdata['update_p'] = $_SESSION['full_name'];
            $reqdata['update_time'] = time();
            $adddata = M('cooperations')->where(['cooperation_id' => $id])->save($reqdata);
            try {
                if ($adddata) {
                    alert('success', '修改成功！', U('cooperations/index'));
                    $this->ajaxReturn(['status' => true]);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage());
            }
        }
    }

    public function detail() {
        $id = $_REQUEST['id'];
        if (!empty($id)) {
            $detailData = M('cooperations')->where(['cooperation_id' => $id])->find();
        }
        $detailData['cooperation_cate_name'] = M('cooperations_cate')->where(['cooperation_cate_id'=>$detailData['cooperation_cate_id']])->find()['cooperation_cate_name'];
        if (!empty($detailData)) {
            $this->assign('details', $detailData);
        }
        $filerdata = M('rFileCoops')->where(['coop_id'=>$id])->select();
        if(!empty($filerdata)){
            $fileids = array_column($filerdata,'file_id');
        }
        $filedata = M('file')->where(['file_id'=>['in',$fileids]])->select();
        foreach($filedata as $k1=>$v1){
            $filedata[$k1]['size'] = ceil($v1['size']/1024);
            $filedata[$k1]['create_date'] = date('Y-m-d',$v1['create_date']);
            $filedata[$k1]['role_id'] = M('user')->where(['user_id'=>$v1['role_id']])->find()['full_name'];
            $a = substr($v1['file_path'],strrpos($v1['file_path'],'.')+1);
            if($a == 'jpg' || $a =='jpeg' || $a =='pdf' || $a=='png' || $a=='gif' || $a=='bmp'){
                $filedata[$k1]['ext'] = 1;
            }else{
                $filedata[$k1]['ext'] = 2;
            }
        }
        ##日志
        $logrdata = M('rCoopLog')->where(['coop_id'=>$id])->select();
        $lodids = array_column($logrdata,'log_id');
        $logdata = M('log')->where(['log_id'=>['in',$lodids]])->select();
        $sorts = [];
        foreach($logdata as $k=>$v){
            $sorts[]=$v["create_date"];
            $logdata[$k]['create_date'] = date('Y-m-d H:i:s',$v['create_date']);
            $logdata[$k]['role_id'] = M('user')->where(['user_id'=>$v['role_id']])->find()['full_name'];
            $logdata[$k]['head'] = M('user')->where(['user_id'=>$v['role_id']])->find()['img'];
        }
        array_multisort($sorts,SORT_DESC,$logdata);
        $catedata = M('cooperations_cate')->select();
        ##联系人信息
        $condata = M('coopr')->where(['cid'=>$id])->select();

        $data['id'] = $id;
        $data['detaildata'] = $detailData;
        $data['catedata'] = $catedata;
        $data['filedata'] = $filedata;
        $data['logdata'] = $logdata;
        $data['condata'] = $condata;
        $this->ajaxReturn($data);
        $this->assign('coopcate', $catedata);
//        var_dump($detailData);
        $this->display();
    }

    public function catemanage() {
//        $catedata = M('cooperations_cate')->select();
//        $this->assign('cates',$catedata);
        $this->display();
//        var_dump($catedata);
    }

    public function addcate() {
        $catedata = M('cooperations_cate')->select();

//        $catedata = $this->tree($catedata, 0, 0);
        $this->assign('catedata', $catedata);
        $this->display();
    }

    public function tree($rows, $pid = 0, $level = 0) {
        static $tree = [];
        foreach ($rows as $row) {
            if ($pid == $row['cate_pid']) {
                $row['level'] = $level;
                $tree[] = $row;
                $this->tree($rows, $row['cooperation_cate_id'], $level + 1);
            }
        }
        return $tree;
    }

    public function addcatedata() {
        if (IS_POST) {
//            $catedata['cate_pid'] = I('post.cooperation_cate_id');
            $catedata['cooperation_cate_name'] = I('post.cate_name');
            $catedata['desc'] = I('post.desc');
            $catedata['add_ts'] = time();
            $catedata['update_time'] = time();
//            var_dump($catedata);exit;
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
        $cate = M('cooperations_cate')->select();
        $cate = $this->tree($cate, 0, 0);
        $this->assign('cate', $cate);
        $this->assign('cid', $id);
        $this->assign('catedata', $catedata);
        $this->display();
    }

    public function editcatedata() {
        if (IS_POST) {
            $catedata = I('post.');
            $catedatas['cate_pid'] = $catedata['cooperation_cate_id'];
            $catedatas['cooperation_cate_name'] = $catedata['cooperation_cate_name'];
            $catedatas['desc'] = $catedata['desc'];
            $cates = M('cooperations_cate')->where(['cooperation_cate_id' => $catedata['cid']])->save($catedatas);
            if ($cates !== false) {
                alert('success', '修改成功！', U('cooperations/catemanage'));
            }
        }
    }

    public function deletecate() {
        if (IS_AJAX && IS_POST) {
            $datas = M('cooperations')->where(['cooperation_cate_id' => $_REQUEST['id']])->find();
//            $datas1 = M('cooperations_cate')->where(['cooperation_cate_id' => $_REQUEST['id']])->find();
//            $datas2 = M('cooperations_cate')->where(['cate_pid' => $_REQUEST['id']])->find();
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
        $data = M('cooperations')->order('add_ts desc')->select();
        foreach ($data as $k => $v) {
            
        }
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }

    public function search() {
//        if (IS_POST) {
        $wheredata = $_REQUEST;
        $page = $wheredata['page'] ? $wheredata['page'] : 1;
        $rows = $wheredata['rows'] ? $wheredata['rows'] : 15;
        $sidx = $wheredata['sidx'];
        $sord = $wheredata['sord'];
        $wherecate = $wheredata['type'];
        if(!empty($wherecate)){
            $where['cooperation_cate_id'] = $wherecate;
        }
        
//        $allcatedata = M('cooperations_cate')->where(['cate_pid' => $wherecate])->select();
//        $cateids = array_column($allcatedata, 'cooperation_cate_id');
        //类别
//        if (is_array($cateids)) {
//            $where = ['cooperation_cate_id' => ['in', $cateids]];
//        } else {
//            $where = ['cooperation_cate_id' => $wherecate];
//        }
//            var_dump($where);die;
        //联系人，电话，qq/weixin
        if ($wheredata['lotwhere'] != '') {
            $where['contacts_name'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('cooperations')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('cooperations')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['contacts_name']);
            $where['cooperation_name'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('cooperations')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('cooperations')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
//                var_dump($where);die;
            unset($where['cooperation_name']);
            $where['tel'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('cooperations')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('cooperations')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['tel']);
            $where['onlineNum'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('cooperations')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('cooperations')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            if (!is_array($datas1)) {
                $this->ajaxReturn(['status' => true, 'data' => []]);
            }
            unset($where['onlineNum']);
        }
//            $this->ajaxReturn($wherecate);
        if ($wherecate == '') {
//                $this->ajaxReturn($wherecate);
            $count = M('cooperations')->count();
            $datas = M('cooperations')->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d H:i:s', $v['add_ts']);
            }
            $catedata = M('cooperations_cate')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['cooperation_cate_id'] == $v1['cooperation_cate_id']) {
                        $data['list'][$k]['cate_name'] = $v1['cooperation_cate_name'];
                    }
                }
            }
            $this->ajaxReturn(['status' => true, 'data' => $data]);
        } else {
            
            $count = M('cooperations')->where($where)->count();
            $datas = M('cooperations')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d H:i:s', $v['add_ts']);
            }
            $catedata = M('cooperations_cate')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['cooperation_cate_id'] == $v1['cooperation_cate_id']) {
                        $data['list'][$k]['cate_name'] = $v1['cooperation_cate_name'];
                    }
                }
            }
            $this->ajaxReturn(['status' => true, 'data' => $data]);
        }
//        }
    }

    public function catesearch() {
        if (IS_POST) {
            $catedata = M('cooperations_cate')->order('add_ts desc')->select();
            foreach ($catedata as $k => $v) {
                $v['add_ts'] = date('Y-m-d h:i:s', $v['add_ts']);
            }
            $this->ajaxReturn(['status' => true, 'data' => $catedata]);
        }
    }

    public function getAllCate() {
        if (IS_POST) {
            $catedata = M('cooperations_cate')->select();
            $this->ajaxReturn(['status' => true, 'data' => $catedata]);
        }
    }
    public function delfile() {
        $fileids = $_REQUEST['file_id'];
        $fileids = explode(',', trim($fileids, ','));
        $caseid = $_REQUEST['caseid'];
//        $this->ajaxReturn($caseid);
        if (!empty($fileids)) {
            $where['file_id'] = ['in', $fileids];
            $filedata = M('file')->where($where)->delete();
            if(!empty($caseid)){
                $where['coop_id'] = $caseid;
                $filedata = M('rFileCoops')->where($where)->delete();
            }
            $this->ajaxReturn($filedata);
        }
    }

    public function addcon(){
        $cid = $_REQUEST['ids'];
        if($cid!=''){
            $rmcoop = M('coopr')->where(['cid'=>$cid])->delete();
        }
        $name = $_REQUEST['names'];
        $a = [];
        foreach ($name as $k=>$v){
            $data['cid'] = $cid;
            $data['name'] = $v;
            $data['email'] = $_REQUEST['emails'][$k]?$_REQUEST['emails'][$k]:'';
            $data['tel'] = $_REQUEST['tels'][$k]?$_REQUEST['tels'][$k]:'';
            $data['position'] = $_REQUEST['position'][$k]?$_REQUEST['position'][$k]:'';
//            $this->ajaxReturn($data);
            $a[] = $data;

            $adddata = M('coopr')->add($data);
        }
//        $this->ajaxReturn($a);
        if($adddata || count($name)<1){
            $this->ajaxReturn(1);
        }else{
            $this->ajaxReturn(2);
        }
    }

    public function delcon(){
        $id = $_REQUEST['id'];
            $deldata = M('coopr')->where(['id'=>$id])->delete();
            if($deldata){
                $this->ajaxReturn(1);
            }else{
                $this->ajaxReturn(2);
            }
    }

}
