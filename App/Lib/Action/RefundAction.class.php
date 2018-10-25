<?php
/**
 *退款模块
 * author :jasper
 * date:18/10/24
 **/
class RefundAction extends Action{
    /**
     *用于判断权限
     *@permission 无限制
     *@allow 登录用户可访问
     *@other 其他根据系统设置
     **/
    public function _initialize(){
        $action = array(
            'permission'=>array(),
            'allow'=>array('listdialog', 'revert', 'adddialog', 'analytics', 'checkout','getmonthlyreceive','getyearreceivecomparison','getreceivablesmoney','getpayablesmoney','receivablesplan','advance_search','getcurrentstatus')
        );
        $this->type = $_REQUEST['t'] ? trim($_REQUEST['t']) : 'receivables';
        if(!in_array($this->type,array('receivables','payables','receivingorder','paymentorder','receivablesplan'))){
            alert('error',L('PARAMETER_ERROR'),U('index/index'));
        }
        B('Authenticate', $action);
        $a = ACTION_NAME.'_'.$this->type;
        $this->_permissionRes = getPerByAction(MODULE_NAME,$a);
    }



    public function index()
    {
        //回款单
        $d_refund = D('RefundView');

        $m_refund = M('Refund');
        $m_customer = M('Customer');
        $m_contract = M('Contract');
        $m_user = M('User');
        $where=$order=[];
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1 ;
        //高级搜索
//        if(!$_GET['field']){
//            $fields_search = array();
//            foreach($_GET as $kd => $vd){
//                $no_field_array = array('act','content','p','condition','listrows','daochu','this_page','current_page','export_limit','desc_order','asc_order','selectexcelxport','by','t','order_field','type','daochu','od');
//                if(!in_array($kd,$no_field_array)){
//                    if(in_array($kd,array('create_time','update_time','pay_time'))){
//                        $where[$kd] = field($vd['value'], $vd['condition']);
//                        $fields_search[$kd]['field'] = $kd;
//                        $fields_search[$kd]['start'] = $vd['start'];
//                        $fields_search[$kd]['end'] = $vd['end'];
//                        $fields_search[$kd]['form_type'] = 'datetime';
//
//                        //时间段查询
//                        if ($vd['start'] && $vd['end']) {
//                            $where[$kd] = array('between',array(strtotime($vd['start']),strtotime($vd['end'])+86399));
//                        } elseif ($vd['start']) {
//                            $where[$kd] = array('egt',strtotime($vd['start']));
//                        } else {
//                            $where[$kd] = array('elt',strtotime($vd['end'])+86399);
//                        }
//                    }elseif($kd =='customer_name'){
//                        if(!empty($vd['value'])){
//                            $c_where['name'] = array('like','%'.$vd['value'].'%');
//                            $customer_ids = M('customer')->where($c_where)->getField('customer_id',true);
//                            if($customer_ids){
//                                $where['customer_id'] = array('in',$customer_ids);
//                            }else{
//                                $where['customer_id'] = -1;
//                            }
//                            $fields_search[$kd]['field'] = $kd;
//                            $fields_search[$kd]['condition'] = $vd['condition'];
//                            $fields_search[$kd]['value'] = $vd['value'];
//                        }
//                    }elseif(in_array($kd,array('status','owner_role_id','creator_role_id'))){
//                        if(!empty($vd)){
//                            $where[$this->type .'.'.$kd] = $vd['value'];
//                            $fields_search[$kd]['field'] = $kd;
//                            $fields_search[$kd]['value'] = $vd['value'];
//                        }
//                    }elseif($kd =='code'){
//                        if(!empty($vd['value'])){
//                            $b_where['code'] = array('like','%'.$vd['value'].'%');
//                            $business_ids = M('business')->where($b_where)->getField('business_id',true);
//                            if($business_ids){
//                                $where['business_id'] = array('in',$business_ids);
//                            }else{
//                                $where['business_id'] = -1;
//                            }
//                            $fields_search[$kd]['field'] = $kd;
//                            $fields_search[$kd]['value'] = $vd['value'];
//                        }
//                    }else{
//                        if(is_array($vd)) {
//                            if($kd =='money'){
//                                $fields_search[$kd]['form_type'] = 'number';
//                            }
//                            if(!empty($vd['value'])){
//                                $where[$kd] = field($vd['value'], $vd['condition']);
//                                $fields_search[$kd]['field'] = $kd;
//                                $fields_search[$kd]['condition'] = $vd['condition'];
//                                $fields_search[$kd]['value'] = $vd['value'];
//                            }
//                        }else{
//                            if(!empty($vd)){
//                                $where[$kd] = field($vd);
//                                $fields_search[$kd]['field'] = $kd;
//                                $fields_search[$kd]['value'] = $vd;
//                            }
//                        }
//                    }
//                }
//                if($kd != 'search'){
//                    if(is_array($vd)){
//                        foreach ($vd as $key => $value) {
//                            $params[] = $kd . '[' . $key . ']=' . $value;
//                        }
//                    }else{
//                        $params[] = $kd . '=' . $vd;
//                    }
//                }
//            }
//        }

        //权限
//        if (!isset($where[$this->type . '.owner_role_id']) && $by != 'deleted') {
//            $where[$this->type . '.owner_role_id'] = array('in', $this->_permissionRes);
//        }

        // 过滤不在权限范围内的role_id
//        if(isset($where[$this->type . '.owner_role_id'])){
//            if(!empty($where[$this->type . '.owner_role_id']) && !in_array(intval($where[$this->type . '.owner_role_id']),$below_ids)){
//                $where[$this->type . '.owner_role_id'] = array('in',$this->_permissionRes);
//            }
//        }

        // 校区筛选 获取组织架构中的校区
        $jiagou = M('RoleDepartment')->where(['department_id'=>['IN','200,300,400,500']])->select();
        $this->jiagou = $jiagou;

//        unset($where['xq']);
//        if ($xq = $_GET['xq'])
//        {
//            $this->xq = $xq;
//            // 获取校区负责人的position_id
//            $position = M('Position')->where(['department_id'=>['eq',$xq],'parent_id'=>['eq',1]])->find()['position_id'];
//            // 通过负责人的position_id 获取role_id
//            $role = M('Role')->where(['position_id'=>['eq',$position]])->find()['role_id'];
//            // 通过负责人的 role_id 获取下属 role_ids
//            $sub_ids = getAppointSubRoleId($role);
//            $where[$this->type . '.owner_role_id'] = array('in',implode(',', $sub_ids));
//
//            if ($by == 'me') $where[$this->type . '.owner_role_id'] = array('eq',session('role_id'));
//
//        }
        // 校区筛选 END

//        $this->fields_search = $fields_search;
        if($_GET['listrows']){
            $listrows = intval($_GET['listrows']);
            $params[] = "listrows=" . intval($_GET['listrows']);
        }else{
            $listrows = 15;
            $params[] = "listrows=15";
        }
        $count = $d_refund->where($where)->count();

        $p_num = ceil($count/$listrows);
        if($p_num<$p){
            $p = $p_num;
        }
        //导出
        if(trim($_GET['act']) == 'excel'){
            if(!checkPerByAction('finance','export_receivingorder')){
                alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
            }else{
                $dc_id = explode(',',trim($_GET['daochu']));
                if(!empty($_GET['daochu'])){
                    $where['receivingorder.receivingorder_id'] = array('in',$dc_id);
                }
                $current_page = intval($_GET['current_page']);
                $export_limit = intval($_GET['export_limit']);
                $limit = ($export_limit*($current_page-1)).','.$export_limit;
                $list = $d_receivingorder->where($where)->limit($limit)->select();
            }
        }else{
//            if(!empty($_GET['od'])){
////                if($_GET['od']==2){
////                    $order =  $this->type . '.refund_time desc';
////                }else if($_GET['od']==3){
////                    $order =  $this->type . '.refund_time asc';
////                }
////            }
///
            $list = $d_refund->where($where)->order($order)->page($p.','.$listrows)->select();
        }


        //总计金额
        $sum_money = $d_refund->where($where)->sum('money');
        $m_r_contract_sales = M('RContractSales');
        $m_sales_product = M('SalesProduct');
        $m_product = M('Product');
        foreach($list as $k=>$v){

            //审核状态
            $status_name = '待审';
            if ($v['status'] == 1) {
                $status_name = '通过';
            }elseif($v['status'] == 2){
                $status_name = '拒绝';
            }
            $list[$k]['status_name'] = $status_name;
        }
        $sum_money = number_format($sum_money,2);
        $money_arr = array('sum_money'=>$sum_money);

        //导出
        if(trim($_GET['act']) == 'excel'){
            session('export_status', 1);
            $this->excelExport($list,'receivingorder');
        }

        import("@.ORG.Page");
        $Page = new Page($count,$listrows);
        $params[] = 'by=' . trim($_GET['by']);
        $params[] = 't=' . $this->type;
        $params[] = 'type=' . trim($_GET['type']);
        if ($_GET['desc_order']) {
            $params[] = "desc_order=" . trim($_GET['desc_order']);
        } elseif($_GET['asc_order']){
            $params[] = "asc_order=" . trim($_GET['asc_order']);
        }

        $this->parameter = implode('&', $params);
        $Page->parameter = implode('&', $params);
        $show = $Page->show();

        $this->listrows = $listrows;
        $this->alert = parseAlert();

        $this->assign('page',$show);
        $this->assign('list',$list);
        $this->assign('count',$count);
        $this->assign('money_arr',$money_arr);
        $this->alert = parseAlert();
        $this->display();

    }


    public function showAddRefund()
    {
        $this->display('showAddRefund');
    }


    public function addRefund()
    {
        $m_refund = M('Refund');
        $m_contract=M('Contract');
        $m_receivingorder = M('receivingorder');
        $m_receivables = M('Receivables');
        if ($m_refund->create()) {
            if(empty($customer_id=$_POST['customer_id'])){
                $this->error('请选择客户');
            }

            if(empty($contract_id=$_POST['contract_id'])){
                $this->error('请选择关联的合同');
            }
            // 退凭证 如果没有上传文件直接阻止提交
            if (!$_FILES['file']['size']) $this->error('请上传回款凭证.');

            $pingzheng = $this->uploadFile();// 文件上传

            if (!$pingzheng) $this->error("文件类型错误..(允许类型: 'word','docx','gif','jpg','jpeg','bmp','png','swf','pdf')");
            $refund=[];
            $m_refund->file = $pingzheng['savename'];// 赋值
            $m_refund->filename = $pingzheng['name'];// 赋值
            $m_refund->type = '线下付款';
            $m_refund->price = round($_POST['price'], 2);
            $receivables_custom='TK';
            $receivables_max_id = $m_refund->max('receivables_id');
            $receivables_max_id = $receivables_max_id+1;
            $receivables_max_code = str_pad($receivables_max_id,4,0,STR_PAD_LEFT);//填充字符串的左侧（将字符串填充为新的长度）
            $m_refund->name = $receivables_custom.date('Ymd').'-'.$receivables_max_code;
            $m_refund->prefixion = $receivables_custom;
            $m_refund->contract_id=$contract_id;
            $m_refund->customer_id=$customer_id;
            $m_refund->refund_time = $_POST['pay_time'] ? $_POST['pay_time'] : date('Y-m-d H:i:s',time());
            $m_refund->creator_role_id = session('role_id');
            $m_refund->owner_role_id = $_POST['owner_role_id'] ? intval($_POST['owner_role_id']) : session('role_id');
            $m_refund->create_time = date('Y-m-d H:i:s',time());
            $m_refund->update_time = date('Y-m-d H:i:s',time());
            $m_refund->status = 0;
            $m_refund->money =$refund['money']= $_POST['price'];

            $this->checkMoney($m_contract,$refund,$m_receivables, $m_receivingorder);
            if($id = $m_refund->add()){
                //创建应收款同时创建收款单
                if(!empty($id)){
                    //记录操作日志
                    actionLog($id);

                    //发送站内信给审核人
                    $check_position_ids =  M('Permission') -> where('url = "%s"','finance/check')->getField('position_id',true);
                   // $check_position_ids =  M('Permission') -> where('url = "%s"','refund/showAddRefund')->getField('position_id',true);
                    if($check_position_ids){
                        $receivables_check_role_ids = D('RoleView')->where(array('role.position_id'=>array('in',$check_position_ids),'user.status'=>array('neq',2)))->getField('role_id');
                    }
                    if($receivables_check_role_ids){
                        $receivables_check_role_ids = $receivables_check_role_ids;
                    }else{
                        //管理员
                        $receivables_check_role_ids = M('User')->where(array('category_id'=>1,'status'=>1))->getField('role_id',true);
                    }
                    $url = U('finance/view','t=receivables&id='.$id);
                    $form_role_info = M('User')->where('role_id = %d',session('role_id'))->field('role_id,full_name')->find();
                    foreach($receivables_check_role_ids as $k=>$v){
                        sendMessage($v,$_SESSION['name'].'&nbsp;&nbsp;创建了新的退款单《<a href="'.$url.'">'.$_POST['name'].'</a>》<font style="color:green;">需要进行审核</font>！',1);
                    }
                    alert('success',L('ADD SUCCESS',array('')),U('refund/index'));
                }
            }else{
                $this->error('添加回款失败,请联系系统管理员');
            }
        } else {
            $this->error('添加回款失败,请联系系统管理员');
        }
    }

    public function uploadFile()
    {
        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');
        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array('word','docx','gif','jpg','jpeg','bmp','png','swf','pdf');// 设置附件上传类型
        $upload->thumbRemoveOrigin = false;//是否删除原文件
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
        }
        $upload->savePath = $dirname;

        if (!$upload->upload()) {// 上传错误提示错误信息
            $this->error($upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if (is_array($info[0]) && !empty($info[0])) {
                $res['savename'] = $dirname . $info[0]['savename'];
                $res['name'] = $info[0]['name'];
            } else {
                $this->error('file is not uploaded.');
            }
            // 返回文件路径
            return $res ?: [];
        }
    }



    public function view()
    {
        $id = I('id');
        if(empty($id)){
            $this->error('请选择你要查看的退款单');
        }
        $d_refund = D('RefundView');
        $m_user =M('User');
        $where=[];
        $where['id']=$id;
        $refund_info=$d_refund->where($where)->find();

        if(empty($refund_info)){
            $this->error(L('RECORD NOT EXIST'));
        }elseif(!in_array($refund_info['owner_role_id'], $this->_permissionRes)){
            alert('error',L('DO NOT HAVE PRIVILEGES'),$_SERVER['HTTP_REFERER']);
        }
        $refund_info['owner'] = $m_user->where(array('role_id'=>$refund_info['creator_role_id']))->field('full_name,thumb_path')->find();
        $refund_info['examine'] = $m_user->where(array('role_id'=>$refund_info['examine_role_id']))->field('full_name,thumb_path')->find();
        $this->alert = parseAlert();
        $this->assign('info',$refund_info);
        $this->display();
    }


    public function check()
    {

        $submit = $this->_post('submit1','trim');
        $description = $this->_post('description','trim');
        $refund_id = $this->_post('refund_id','intval');
        $m_refund = M('Refund');
        $m_contract=M('Contract');
        $m_receivingorder = M('receivingorder');
        $m_receivables = M('Receivables');
        if(!$refund_id){
            alert('error', L('PARAMETER_ERROR'),$_SERVER['HTTP_REFERER']);
        }
        if(!$refund = $m_refund->where('is_deleted = 1 and id = %d', $refund_id)->find()){
            alert('error', '退款单已经被删除或者不存在',$_SERVER['HTTP_REFERER']);
        }

        if($refund['status'] == 0){
            if($submit == 'agree'){
                $data['status'] = 1;
                //查询退款金额 是否大于合同金额  以及收款金额
               $contract=$this->checkMoney($m_contract, $refund, $m_receivables, $m_receivingorder);
            }elseif($submit == 'deny'){
                $data['status'] = 2;
            }else{
                alert('error', '请求错误!', $_SERVER['HTTP_REFERER']);
            }
            $data['examine_role_id'] = session('role_id');
            $data['check_des'] = $description;
            $data['update_time'] =date('Y-m-d H:i:s',time()) ;
            $data['check_time'] = date('Y-m-d H:i:s',time()) ;
            $result = $m_refund->where('id = %d', $refund_id)->save($data);
            if($result){
                if($data['status']==1){
                    $m_contract->is_refund=1;
                    $m_contract->refund_money=$contract['refund_money']+$refund['money'];
                    $m_contract->where('contract_id =%d',$refund['contract_id'])->save();
                }

                //发送站内信
                $url = U('refund/view','id='.$refund['id']);
                sendMessage($refund['creator_role_id'],'您创建的退款单《<a href="'.$url.'">'.$refund['name'].'</a>》<font style="color:green;">已审核</font>！',1);
                alert('success', L('CHECK_SUCCESS'), $_SERVER['HTTP_REFERER']);
            }else{
                alert('error', L('CHECK_FAILED'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            alert('error', '审核失败，该单已审核过了', $_SERVER['HTTP_REFERER']);
        }
    }

    /**
     * @param $m_contract
     * @param $refund
     * @param $m_receivables
     * @param $m_receivingorder
     * @return mixed
     */
    protected function checkMoney($m_contract, $refund, $m_receivables, $m_receivingorder)
    {
        $contract = $m_contract->where('contract_id =%d', $refund['contract_id'])->find();
        if ($refund['money'] > $contract['price']) {
            alert('error', '退款金额大于合同金额', $_SERVER['HTTP_REFERER']);
        }
        $receivables = $m_receivables->where('contract_id =%d', $contract['contract_id'])->select();
        if (empty($receivables)) {
            alert('error', '该合同没有任何收款,不能退款操作', $_SERVER['HTTP_REFERER']);
        }
        $receivables_money = 0;
        foreach ($receivables as $receivable) {
            $receivables_money += $m_receivingorder->where('is_deleted <> 1 and status = 1 and receivables_id =%d', $receivable['receivables_id'])->sum('money');
        }
        if ($refund['money'] > $receivables_money) {
            alert('error', '退款金额大于收款金额', $_SERVER['HTTP_REFERER']);
        }
        return $contract;
    }


}