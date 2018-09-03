<?php

/**
 * 合同模块
 *
 * */
class PublicclassAction extends Action {

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
    	$livecate = M('livecate')->select();
    	$livecontent = M('livecontent')->select();
    	$this->assign('livecate', $livecate);
    	$this->assign('livecontent', $livecontent);
    	$this->display();
    }

    public function livecate() {
    	$livecate = M('livecate')->select();
    	$this->assign('livecate', $livecate);
    	$this->display();
    }

    public function addlivecatedata() {
    	$reqdata = I('post.');
    	$reqdata['from_p'] = $_SESSION['full_name'];
    	$reqdata['add_ts'] = time();
    	$adddata = M('livecate')->add($reqdata);
    	if ($adddata) {
    		if ($adddata) {
    			alert('success', '新增成功！', U('publicclass/livecate'));
    		}
    	}
    }

    public function livecatesearch() {
    	if (IS_POST) {
    		$catedata = M('livecate')->select();
    		foreach ($catedata as $k => $v) {
    			$v['add_ts'] = date('Y-m-d h:i:s', $v['add_ts']);
    		}
    		$this->ajaxReturn(['status' => true, 'data' => $catedata]);
    	}
    }

    public function editlivecate() {
    	$id = $_REQUEST['id'];
    	$this->assign('ids', $id);
    	$livecatedata = M('livecate')->where(['id' => $id])->find();
    	$this->assign('livecatedata', $livecatedata);
    	$this->display();
    }

    public function editlivecatedata() {
    	$id = $_REQUEST['ids'];
    	$editdata = I('post.');
    	$editdata['update_time'] = time();
    	$edit = M('livecate')->where(['id' => $id])->save($editdata);
    	if ($edit !== false) {
    		alert('success', '修改成功！', U('publicclass/livecate'));
    	}
    }

    public function deletelivecate() {
    	if (IS_AJAX && IS_POST) {
//            echo $_REQUEST['id'];exit;
    		$datas = M('publicclass')->where(['livecate' => $_REQUEST['id']])->find();
//            $datas1 = M('cooperations_cate')->where(['cooperation_cate_id' => $_REQUEST['id']])->find();
//            $datas2 = M('cooperations_cate')->where(['cate_pid' => $_REQUEST['id']])->find();
    		if (is_array($datas)) {
    			$this->ajaxReturn(['status' => 3]);
    		}
    		$deletedata = M('livecate')->where(['id' => $_REQUEST['id']])->delete();
    		if ($deletedata) {
    			$this->ajaxReturn(['status' => true]);
    		} else {
    			$this->ajaxReturn(['status' => false]);
    		}
    	}
    }

    public function livecontent() {
    	$livecate = M('livecate')->select();
    	$this->assign('livecate', $livecate);
    	$this->display();
    }

    public function addlivecontentcatedata() {
    	$reqdata = I('post.');
    	$reqdata['from_p'] = $_SESSION['full_name'];
    	$reqdata['add_ts'] = time();
    	$adddata = M('livecontent')->add($reqdata);
    	if ($adddata) {
    		if ($adddata) {
    			alert('success', '新增成功！', U('publicclass/livecontent'));
    		}
    	} else {
    		alert('error', '新增失败！', U('publicclass/livecontent'));
    	}
    }

    public function livecontentcatesearch() {
    	if (IS_POST) {
    		$catedata = M('livecontent')->select();
    		foreach ($catedata as $k => $v) {
    			$v['add_ts'] = date('Y-m-d h:i:s', $v['add_ts']);
    		}
    		$this->ajaxReturn(['status' => true, 'data' => $catedata]);
    	}
    }

    public function editlivecontentcate() {
    	$id = $_REQUEST['id'];
    	$this->assign('ids', $id);
    	$livecatedata = M('livecontent')->where(['id' => $id])->find();
    	$this->assign('livecatedata', $livecatedata);
    	$this->display();
    }

    public function editlivecontentcatedata() {
    	$id = $_REQUEST['ids'];
    	$editdata = I('post.');
    	$editdata['update_time'] = time();
    	$edit = M('livecontent')->where(['id' => $id])->save($editdata);
    	if ($edit !== false) {
    		alert('success', '修改成功！', U('publicclass/livecontent'));
    	} else {
    		alert('error', '修改失败！', U('publicclass/livecontent'));
    	}
    }

    public function deletelivecontentcate() {
    	if (IS_AJAX && IS_POST) {
    		$datas = M('publicclass')->where(['livecontent' => ['like', ['%' . $_REQUEST['id'] . '%']]])->find();
//            $datas1 = M('cooperations_cate')->where(['cooperation_cate_id' => $_REQUEST['id']])->find();
//            $datas2 = M('cooperations_cate')->where(['cate_pid' => $_REQUEST['id']])->find();
    		if (is_array($datas)) {
    			$this->ajaxReturn(['status' => 3]);
    		}
    		$deletedata = M('livecontent')->where(['id' => $_REQUEST['id']])->delete();
    		if ($deletedata) {
    			$this->ajaxReturn(['status' => true]);
    		} else {
    			$this->ajaxReturn(['status' => false]);
    		}
    	}
    }

    public function add() {
    	$livecate = M('livecate')->select();
    	$livecontent = M('livecontent')->select();
    	$this->assign('livecate', $livecate);
    	$this->assign('livecontent', $livecontent);
    	$this->display();
    }

    public function adddata() {
    	if (IS_POST) {
    		$reqdata = I('post.');
    		$reqdata['add_ts'] = time();
    		$reqdata['update_time'] = time();

    		$reqdata['from_p'] = $_SESSION['full_name'];
    		$reqdata['livecontent'] = implode(',', $reqdata['livecontent']);
//            var_dump($reqdata);exit;

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
                    $reqdata['t_img_url'] = $dirname . $info['savename']; //缩略图
                    $reqdata['img_url'] = $dirname . $info['name'];
                }
            }
            $res = M('publicclass')->add($reqdata);
//            echo $res;exit;
            $contentids = $reqdata['livecontent'];
            if (!empty($contentids)) {
            	foreach (explode(',', $contentids) as $k => $v) {
            		$a['p_id'] = $res;
            		$a['c_id'] = $v;
            		M('rpc')->add($a);
            	}
            }
            if ($res) {
            	alert('success', '新增成功！', U('publicclass/index'));
            } else {
            	alert('error', '新增失败！', U('publicclass/index'));
            }
        }
    }

    public function delete() {
    	if (IS_AJAX && IS_POST) {
    		$deletedata = M('publicclass')->where(['id' => ['in', explode(',', $_REQUEST['id'])]])->delete();
    		if ($deletedata) {
    			$this->ajaxReturn(['status' => true]);
    		} else {
    			$this->ajaxReturn(['status' => false]);
    		}
    	}
    }

    public function edit() {
    	$id = $_REQUEST['id'];
    	$id = addslashes($id);
    	$editdata = M('publicclass')->where(['id' => $id])->find();
    	$editdata['t_img_url'] = substr($editdata['t_img_url'], 1);
    	$livecate = M('livecate')->select();
    	$livecontent = M('livecontent')->select();
    	$this->assign('ids', $id);
    	$this->assign('editdata', $editdata);
    	$this->assign('livecate', $livecate);
    	$this->assign('livecontent', $livecontent);
    	$this->display();
    }

    public function editdata() {

    	$reqdata = I('post.');
    	$reqdata['update_time'] = time();
    	$reqdata['livecontent'] = implode(',', $reqdata['livecontent']);
//            var_dump($reqdata);exit;

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
                $reqdata['t_img_url'] = $dirname . $info['savename']; //缩略图
                $reqdata['img_url'] = $dirname . $info['name'];
            }
        }
        $adddata = M('publicclass')->where(['id' => $reqdata['ids']])->save($reqdata);
        M('rpc')->where(['p_id' => $reqdata['ids']])->delete();
        if (!empty($reqdata['livecontent'])) {
        	foreach (explode(',', $reqdata['livecontent']) as $k => $v) {
        		$a['p_id'] = $reqdata['ids'];
        		$a['c_id'] = $v;
        		M('rpc')->add($a);
        	}
        }

        if ($adddata !== false) {
        	alert('success', '修改成功！', U('publicclass/index'));
        	$this->ajaxReturn(['status' => true]);
        } else {
        	alert('error', '修改失败！', U('publicclass/index'));
        }
    }

    public function detail() {
    	header("Access-Control-Allow-Origin: *");
    	$id = $_REQUEST['id'];
    	$livecate = M('livecate')->select();
    	$livecate = M('livecontent')->select();
    	if (!empty($id)) {
    		$detailData = M('publicclass')->where(['id' => $id])->find();
    	}
    	if (!empty($res = M('livecate')->where(['id' => $detailData['livecate']])->find())) {
    		$detailData['livecate'] = $res['cate_name'];
    	}
    	foreach (explode(',', $detailData['livecontent']) as $k3 => $v3) {
    		$r = M('livecontent')->where(['id' => $v3])->find();
    		if ($r) {
    			$news[] = $r['cate_name'];
    		}
    	}
    	$detailData['livecontent'] = implode(',', $news);
    	$pos = strrpos($detailData['img_url'], '/');
    	$detailData['img_url'] = substr($detailData['img_url'], $pos + 1);
    	$detailData['add_ts'] = date('Y-m-d H:i:s', $detailData['add_ts']);
//        $detailData['t_img_url'] = substr($detailData['t_img_url'], 1);
    	$this->ajaxReturn(['status'=>true,'data'=>$detailData]);
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
    		$res[$k]['update_time'] = date('Y-m-d ', $v['update_time']);
    	}
    	return $res;
    }

    public function search() {
    	header("Access-Control-Allow-Origin: *");
    	if (IS_POST) {
    		$wheredata = I('post.');
    		$page = $wheredata['page'] ? $wheredata['page'] : 1;
    		$rows = $wheredata['rows'] ? $wheredata['rows'] : 12;
    		$sidx = $wheredata['sidx'];
    		$sord = $wheredata['sord'];
    		$pcate = $wheredata['livecate'];
    		$mcate = $wheredata['livecontent'];
//            
            //姓名，日语，英语，学校级别，专业
    		if ($pcate != '' || $mcate != ''  ||$wheredata['lotwhere']!='') {

    			if (!empty($pcate)) {
    				$where['livecate'] = $pcate;
    			}
    			if (!empty($mcate)) {

                    $contentids = explode(',', $mcate);
                        $res = M('rpc')->where(['c_id' => ['in',$contentids]])->select();
                        foreach($res as $k=>$v){
                            $new1[] = $v['p_id'];
                        }
                        foreach(array_count_values($new1) as $k1=>$v1){
                            if($v1 == count($contentids)){
                                $new2[] = $k1;
                            }
                        }
                    $where['id'] = ['in', $new2];
                }
                if ($wheredata['lotwhere'] != '') {
                    $where['names'] = ['like', ['%' . $wheredata['lotwhere'] . '%']];
                }
    			$datas1 = M('publicclass')->where($where)->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
    			$datas1 = $this->setDate($datas1);
    			if (is_array($datas1)) {
    				$count1 = M('publicclass')->where($where)->count();
    				$data['list'] = $datas1;
    				$data['count'] = $count1;
    				$data['total'] = ceil($count1 / $rows);
    				$catedata = M('livecate')->select();
    				$contentdata = M('livecontent')->select();
    				foreach ($data['list'] as $k => $v) {
    					foreach ($catedata as $k1 => $v1) {
    						if ($v['livecate'] == $v1['id']) {
    							$data['list'][$k]['livecate'] = $v1['cate_name'];
    						}
    					}
    					foreach (explode(',', $v['livecontent']) as $k3 => $v3) {
    						$r = M('livecontent')->where(['id' => $v3])->find();
    						if ($r) {
    							$news[] = $r['cate_name'];
    						}
    					}
    					$data['list'][$k]['livecontent'] = implode(',', $news);
    					$news = [];
    				}
    				$data['page'] = $page;
    				$this->ajaxReturn(['status' => true, 'data' => $data]);
    			}

    			if (!is_array($datas1)) {
    				$this->ajaxReturn(['status' => false, 'data' => []]);
    			}
    		}
    		if (empty($pcate) || empty($mcate)) {
    			$count = M('publicclass')->count();
    			$datas = M('publicclass')->order('add_ts desc')->limit(($page - 1) * $rows, $rows)->select();
    			$data['count'] = $count;
    			$data['list'] = $datas;
    			$data['total'] = ceil($count / $rows);
    			foreach ($data['list'] as $k => $v) {
    				$data['list'][$k]['add_ts'] = date('Y-m-d ', $v['add_ts']);
    				$data['list'][$k]['update_time'] = date('Y-m-d ', $v['update_time']);
    			}
    			$catedata = M('livecate')->select();
    			$contentdata = M('livecontent')->select();
    			foreach ($data['list'] as $k => $v) {
    				foreach ($catedata as $k1 => $v1) {
    					if ($v['livecate'] == $v1['id']) {
    						$data['list'][$k]['livecate'] = $v1['cate_name'];
    					}
    				}
    				foreach (explode(',', $v['livecontent']) as $k3 => $v3) {
    					$r = M('livecontent')->where(['id' => $v3])->find();
    					if ($r) {
    						$news[] = $r['cate_name'];
    					}
    				}
    				$data['list'][$k]['livecontent'] = implode(',', $news);
    				$news = [];
    			}
    			$data['page'] = $page;
    			$this->ajaxReturn(['status' => true, 'data' => $data]);
    		}
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

    public function getAllCata(){
    	header("Access-Control-Allow-Origin: *");
    	if(IS_POST){
    		$livecate = M('livecate')->select();
    		$livecontent = M('livecontent')->select();
    		$allCate[0] = $livecate;
    		$allCate[1] = $livecontent;
    		$this->ajaxReturn(['status'=>true,'data'=>$allCate]);
    	}
    }

}
