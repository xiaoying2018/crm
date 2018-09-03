<?php

/**
 * 合同模块
 *
 * */
class CompetitiveAction extends Action {

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
        $types = I('get.types');
//        if ($getcates == 8) {
//            $catedate = M('competitive_cate')->where(['cate_pid' => $getcates])->select();
//        } else {
//            $tcate = M('competitive_cate')->where(['competitive_cate_id' => $getcates])->find();
        $catedate = M('competitive_cate')->select();
//        }
        $indexdata = M('competitive')->where($wherecate)->select();
        $this->assign('catedate', $catedate);
        $this->assign('datas', $indexdata);
        $this->display();
    }

    public function index1() {
        $getcate = I('get.catid');
        $getcates = I('get.cateid');
        $types = I('get.types');
        if ($getcates == 9) {
            $catedate = M('competitive_cate')->where(['cate_pid' => $getcates])->select();
        } else {
            $tcate = M('competitive_cate')->where(['competitive_cate_id' => $getcates])->find();
            $catedate = M('competitive_cate')->where(['cate_pid' => $tcate['cate_pid']])->select();
        }
        $indexdata = M('competitive')->where($wherecate)->select();
        $this->assign('catedate', $catedate);
        $this->assign('datas', $indexdata);
        $this->display();
    }

    public function add() {
        $catedata = M('competitive_cate')->select();
        $this->assign('coopcate', $catedata);
        $this->display();
    }

    public function adddata() {
        if (IS_POST) {
            $reqdata = I('post.');
            $reqdata['from_p'] = $_SESSION['full_name'];
            $reqdata['add_ts'] = time();
            $reqdata['update_time'] = time();
            $adddata = M('competitive')->add($reqdata);
            try {
                if ($adddata) {
                    alert('success', '新增成功！', U('competitive/index'));
                    $this->ajaxReturn(['status' => true]);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage());
            }
        }
    }

    public function delete() {
        if (IS_POST) {
            $where['competitive_id'] = ['in', explode(',', $_REQUEST['id'])];
            $deletedata = M('competitive')->where($where)->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function edit() {
        $coopdata = M('competitive')->where(['competitive_id' => $_REQUEST['id']])->find();
        $catedata = M('competitive_cate')->select();
//        foreach ($catedata as $k => $v) {
//            if ($v['cate_pid'] == 0) {
//                unset($catedata[$k]);
//            }
//        }
//        var_dump($coopdata);
        $this->assign('coopcate', $catedata);
        $this->assign('cid', $_REQUEST['id']);
        $this->assign('coopdata', $coopdata);
        $this->display();
    }

    public function editdata() {
        if (IS_POST) {
            $id = $_REQUEST['cid'];
//            var_dump(I('post.'));die;
            $reqdata = I('post.');
            $reqdata['update_p'] = $_SESSION['full_name'];
            $reqdata['update_time'] = time();
            $adddata = M('competitive')->where(['competitive_id' => $id])->save($reqdata);
            try {
                if ($adddata) {
                    alert('success', '修改成功！', U('competitive/index'));
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
            $detailData = M('competitive')->where(['competitive_id' => $id])->find();
        }
        if (!empty($detailData)) {
            $this->assign('details', $detailData);
        }
        $catedata = M('competitive_cate')->select();
        $data['detaildata'] = $detailData;
        $data['catedata'] = $catedata;
        $this->ajaxReturn($data);
        $this->assign('coopcate', $catedata);
//        var_dump($detailData);
        $this->display();
    }

    public function catemanage() {
//        $catedata = M('competitive_cate')->select();
//        $this->assign('cates',$catedata);
        $this->display();
//        var_dump($catedata);
    }

    public function addcate() {
        $catedata = M('competitive_cate')->select();

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
                $this->tree($rows, $row['competitive_cate_id'], $level + 1);
            }
        }
        return $tree;
    }

    public function addcatedata() {
        if (IS_POST) {
//            $catedata['cate_pid'] = I('post.competitive_cate_id');
            $catedata['competitive_cate_name'] = I('post.cate_name');
            $catedata['desc'] = I('post.desc');
            $catedata['add_ts'] = time();
            $catedata['update_time'] = time();
//            var_dump($catedata);exit;
            $adddata = M('competitive_cate')->add($catedata);
            try {
                if ($adddata) {
                    alert('success', '新增成功！', U('competitive/catemanage'));
                    $this->ajaxReturn(['status' => true]);
                }
            } catch (\Exception $e) {
                $this->ajaxReturn($e->getMessage());
            }
        }
    }

    public function editcate() {
        $id = $_REQUEST['id'];
        $catedata = M('competitive_cate')->where(['competitive_cate_id' => $id])->find();
        $cate = M('competitive_cate')->select();
//        $cate = $this->tree($cate, 0, 0);
        $this->assign('cate', $cate);
        $this->assign('cid', $id);
        $this->assign('catedata', $catedata);
        $this->display();
    }

    public function editcatedata() {
        if (IS_POST) {
            $catedata = I('post.');
            $cates = M('competitive_cate')->where(['competitive_cate_id' => $catedata['cid']])->save($catedata);
            if ($cates !== false) {
                alert('success', '修改成功！', U('competitive/catemanage'));
            }
        }
    }

    public function deletecate() {
        if (IS_AJAX && IS_POST) {
            $datas = M('competitive')->where(['competitive_cate_id' => $_REQUEST['id']])->find();
            if (is_array($datas)) {
                $this->ajaxReturn(['status' => 3]);
            }
            $deletedata = M('competitive_cate')->where(['competitive_cate_id' => $_REQUEST['id']])->delete();
            if ($deletedata) {
                $this->ajaxReturn(['status' => true]);
            } else {
                $this->ajaxReturn(['status' => false]);
            }
        }
    }

    public function aa() {
        $data = M('competitive')->order('add_ts desc')->select();
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
//        $allcatedata = M('competitive_cate')->where(['cate_pid' => $wherecate])->select();
//        $cateids = array_column($allcatedata, 'competitive_cate_id');
        //类别
        if (!empty($wherecate)) {
            $where = ['competitive_cate_id' => $wherecate];
        }
//            var_dump($where);die;
        //联系人，电话，qq/weixin
        if ($wheredata['lotwhere'] != '') {
            $where['contacts_name'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('competitive')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('competitive')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['contacts_name']);
            $where['competitive_name'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('competitive')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('competitive')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
//                var_dump($where);die;
            unset($where['competitive_name']);
            $where['tel'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('competitive')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('competitive')->where($where)->count();
                $data['list'] = $datas1;
                $data['count'] = $count;
                $data['total'] = ceil($count / $rows);
                $this->ajaxReturn(['status' => true, 'data' => $data]);
            }
            unset($where['tel']);
            $where['onlineNum'] = ['like', '%' . $wheredata['lotwhere'] . '%'];
            $datas1 = M('competitive')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            if (is_array($datas1)) {
                $count1 = M('competitive')->where($where)->count();
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
            $count = M('competitive')->count();
            $datas = M('competitive')->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d H:i:s', $v['add_ts']);
            }
            $catedata = M('competitive_cate')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['competitive_cate_id'] == $v1['competitive_cate_id']) {
                        $data['list'][$k]['cate_name'] = $v1['competitive_cate_name'];
                    }
                }
            }
            $this->ajaxReturn(['status' => true, 'data' => $data]);
        } else {
//            var_dump($where);
            $count = M('competitive')->where($where)->count();
            $datas = M('competitive')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
            $data['count'] = $count;
            $data['list'] = $datas;
            $data['total'] = ceil($count / $rows);
//                var_dump($data);
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['add_ts'] = date('Y-m-d H:i:s', $v['add_ts']);
            }
            $catedata = M('competitive_cate')->select();
            foreach ($data['list'] as $k => $v) {
                foreach ($catedata as $k1 => $v1) {
                    if ($v['competitive_cate_id'] == $v1['competitive_cate_id']) {
                        $data['list'][$k]['cate_name'] = $v1['competitive_cate_name'];
                    }
                }
            }
            $this->ajaxReturn(['status' => true, 'data' => $data]);
        }
//        }
    }

    public function catesearch() {
        if (IS_POST) {
            $catedata = M('competitive_cate')->select();
            foreach ($catedata as $k => $v) {
                $v['add_ts'] = date('Y-m-d h:i:s', $v['add_ts']);
            }
            $this->ajaxReturn(['status' => true, 'data' => $catedata]);
        }
    }

    public function getAllCate() {
        if (IS_POST) {
            $catedata = M('competitive_cate')->select();
            $this->ajaxReturn(['status' => true, 'data' => $catedata]);
        }
    }

}
