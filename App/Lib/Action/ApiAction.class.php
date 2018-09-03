<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/17
 * Time: 16:18
 */

class ApiAction extends Action
{
    /**
     * 获取表单字段信息
     */
    public function searchFormField()
    {

    	header("Access-Control-Allow-Origin: *");

        // 获取未禁用的字段列表
    	$fields = M('form_field')->where('status=1')->select();

    	$data = [];
        // 根据字段获取相应的值列表
    	foreach ($fields as $k=>$v)
    	{
    		$data[$fields[$k]['name']]['tag'] = $v['tag'];
    		$data[$fields[$k]['name']]['info'] = M('form_value')->where('field_id='.$v['id'])->select();
    	}

        // 返回数据
    	$this->ajaxReturn($data);
    }

    /**
     * 获取推荐数据
     */
    public function getPromote()
    {
    	header("Access-Control-Allow-Origin: *");

    	if ( !$id = I('post.id') ) $this->ajaxReturn(['msg'=>'缺少关键参数','status'=>false]);

    	$res = D('StudentClue')->find($id);

    	$this->ajaxReturn(['status'=>true,'data'=>$res]);

    }

    /**
     * 口碑数据接口
     */
    public function koubeiApi()
    {

        header("Access-Control-Allow-Origin: *");

        $par = I();// 获取参数

        $page = $par['page']?:1;// 当前页
        $limit = $par['limit']?:15;// 每页显示条数
        $start = $limit * ($page-1);// 当前查询起始条数
        $sort = $par['sort']?'create_time asc':'create_time desc';// 如果按创建时间排序 (默认时间倒叙)

        $condition = [];// 准备查询条件
        $condition['status'] = ['eq',1];// 准备查询条件

        $kb_model = M('Koubei');// 实例化模型

        $kbs = $kb_model->field('id,name,one,content,from,file,mini_img,create_time')->where($condition)->order($sort)->limit($start,$limit)->select();// 获取数据列表

        $count = $kb_model->where($condition)->count();// 统计数据总数

        $this->ajaxReturn([
            'status'=>true,
            'msg'=>'',// 错误提示
            'count'=>$count,// 总条数
            'data'=>$kbs// 数据列表
        ]);
    }

    /**
     * 口碑点赞
     */
    public function koubeiUpstar()
    {

        header("Access-Control-Allow-Origin: *");

        $par = I();// 获取参数

        if (!$par['target']) $this->ajaxReturn(['status'=>false, 'msg'=>'缺少参数']);

        $koubei = new KoubeiModel();

        $save_data['id'] = $par['target'];// 目标数据ID

        $info = $koubei->find($save_data['id']);

        if (!$info) $this->ajaxReturn(['status'=>false, 'msg'=>'非法请求,未知数值']);// 要修改的数据不存在

        $save_data['start_num'] = $info['start_num'] + 1;// 点赞数

        $koubei->save($save_data);// 更新口碑点赞数量

        $this->ajaxReturn(['status'=>true, 'msg'=>'感谢你的小心心']);// 点赞成功

    }

    /**
     * 5-3新增,线索修改改为异步请求
     */
    public function editLeads()
    {
        // 过滤非法请求
//        if ( !$this->isAjax() ) $this->ajaxReturn(['status'=>false,'msg'=>'非法请求']);
        // 读取当前用户权限
        $this->_permissionRes = array_merge( getPerByAction(MODULE_NAME,ACTION_NAME, true), [session('user_id')] );
        // 获取关键参数
        if ( !$id = I('post.id') ) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);
        // 要修改的数据不存在
        if ( !$d_v_leads = D('LeadsView')->where('leads.leads_id = %d',$id)->find() ) $this->ajaxReturn(['status'=>false,'msg'=>'数据不存在']);
        // 没有操作权限
        // if ( $this->_permissionRes && !in_array($d_v_leads['owner_role_id'], $this->_permissionRes) )
        // $this->ajaxReturn(['status'=>false,'msg'=>'无操作权限']);

        if ( !session( '?admin' ) && ( $this->_permissionRes && !in_array($d_v_leads['owner_role_id'], $this->_permissionRes) ) && !in_array(session('role_id'), [70,81,95,109]) )
        {
            $this->ajaxReturn(['status'=>false,'msg'=>'无操作权限', 'is_admin'=>session( '?admin' ) ]);
        }

        // 2018-08-01 新增 保存线索所有操作记录 dragon
        $old_leads = M('Leads')->where('leads_id= %d',$id)->find();// 修改前数据
        $old_leads_data = M('LeadsData')->where('leads_id= %d',$id)->find();// 修改前数据

        // 获取附表字段
        $slave_fields       =   M('Fields')->field('field')->where(['model'=>['eq','leads'],'is_main'=>['eq',0]])->select();
        $slave_fields       =   array_map( function($v){
            return $v['field'];
        }, $slave_fields );
        // 修改单个字段
        $params     =   I('post.');

        if ($params['merge']) $params['merge_time'] = time();// 如果修改联合跟进人,则自动更新修改时间

        if ( array_key_exists( 'field', $params ) && array_key_exists( 'value', $params ) && count($params) == 3 )
        {
            $field      =   $params['field'];
            $value      =   $params['value'];
            // 获取提交数据
            $data = [$field=>$value];
            // 执行更新操作
            try {
                $result = false;
                if( in_array( $field, $slave_fields ) ){
                    $model      =   M( 'LeadsData' );
                    if( $model->where(['leads_id'=>['eq',$id]])->find() ){
                        $result =  $model->where(['leads_id'=>['eq',$id]])->save( $data );
                    }else{
                        $data['leads_id']   =   $id;
                        $result =  $model->where(['leads_id'=>['eq',$id]])->add( $data );
                    }
                }else{
                    $result =  M('Leads')->where(['leads_id'=>['eq',$id]])->save($data);
                }
            } catch (\Exception $exception) {
                $this->ajaxReturn(['status'=>false,'msg'=>$exception->getMessage()]);
            }

            $new_leads = M('Leads')->where('leads_id= %d',$id)->find();// 修改后数据
            $new_leads_data = M('LeadsData')->where('leads_id= %d',$id)->find();// 修改后数据
            $m_fields = M('fields');
            $m_action_record = M('action_record');
            $field_info = $m_fields ->where('model="leads" and field="%s"',$field)->field('form_type,name')->find();
            $field_name = $field_info['name'];
            if( in_array( $field, $slave_fields ) ){
                if ($old_leads_data[$field] === $new_leads_data[$field])
                {
                    // 返回执行结果
                    $this->ajaxReturn(['status'=>true, 'result'=>$result]);
                }
                $up_message = '将 '.$field_name.' 由 "'.$old_leads_data[$field].'" 修改为 "'.$new_leads_data[$field].'"'."<br/>";
            }else{
                if ($old_leads[$field] === $new_leads[$field])
                {
                    // 返回执行结果
                    $this->ajaxReturn(['status'=>true, 'result'=>$result]);
                }
                // 0802 新增.如果修改线索真实姓名,映射客户到名称
                if ( ($field == 'name'))
                {
                    M('Customer')->where(['leads_id'=>['eq',$id]])->save(['name'=>$new_leads[$field]]);
                }
                // 0802 end
                $up_message = '将 '.$field_name.' 由 "'.$old_leads[$field].'" 修改为 "'.$new_leads[$field].'"'."<br/>";
            }
            $arr['create_time'] = time();
            $arr['create_role_id'] = session('role_id');
            $arr['type'] = '修改';
            $arr['duixiang'] = $up_message;
            $arr['model_name'] = 'leads';
            $arr['action_id'] = $id;
            $m_action_record ->add($arr);

            // 返回执行结果
            $this->ajaxReturn(['status'=>true, 'result'=>$result]);
        }

        // 修改所有字段 =====================================================================================

        unset($params['id']);// 过滤请求中的ID参数
        
        // 执行更新操作
        try {
            $main_create        =   [];
            $slave_create       =   [];
            foreach ( $params as $key=> $value ){
                if( in_array( $key, $slave_fields ) ){
                    $slave_create[ $key ]   =   $value;
                }else{
                    $main_create[ $key ]    =   $value;
                }
            }
            M('Leads')->where( ['leads_id'=>['eq',$id]] )->save( $main_create );

            if( M('LeadsData')->where(['leads_id'=>['eq',$id]])->find() ){
                $result =  M('LeadsData')->where(['leads_id'=>['eq',$id]])->save( $slave_create );
            }else{
                $slave_create['leads_id']   =   $id;
                $result =  M('LeadsData')->where(['leads_id'=>['eq',$id]])->add( $slave_create );
            }
            
        } catch (\Exception $exception) {
            $this->ajaxReturn(['status'=>false,'msg'=>$exception->getMessage()]);
        }

        $new_leads = M('Leads')->where('leads_id= %d',$id)->find();// 修改后数据
        $new_leads_data = M('LeadsData')->where('leads_id= %d',$id)->find();// 修改后数据
        $update_ago_leads = array_diff_assoc($new_leads,$old_leads); // 已修改的字段
        
        $update_ago_leads_data = array_diff_assoc($new_leads_data,$old_leads_data); // 已修改的字段
        $m_fields = M('fields');
        $m_action_record = M('action_record');
        $up_message = '';
        foreach($update_ago_leads as $k => $v){
            if($k != 'update_time'){
                $field_info = $m_fields ->where('model="leads" and field="%s"',$k)->field('form_type,name')->find();
                $field_name = $field_info['name'];
                if($field_info['form_type'] == 'datetime'){
                    $old_value = date('Y-m-d',$old_leads[$k]);
                    $new_value = date('Y-m-d',$v);
                }else{
                    $old_value = $old_leads[$k];
                    $new_value = $v;
                }

                if ($old_value === $new_value)
                {
                    continue;
                }

                // 0802 新增.如果修改线索真实姓名,映射客户到名称
                if ( $k == 'name' )
                {
                    M('Customer')->where(['leads_id'=>['eq',$id]])->save(['name'=>$new_value]);
                }
                // 0802 end

                $up_message .= '将 '.$field_name.' 由 "'.$old_value.'" 修改为 "'.$new_value.'"'."<br/>";
            }
        }
        foreach($update_ago_leads_data as $kk => $vv){
            if($kk != 'update_time'){
                $field_infos = $m_fields ->where('model="leads" and field="%s"',$k)->field('form_type,name')->find();
                $field_names = $field_infos['name'];
                if($field_infos['form_type'] == 'datetime'){
                    $old_values = date('Y-m-d',$old_leads_data[$k]);
                    $new_values = date('Y-m-d',$v);
                }else{
                    $old_values = $old_leads_data[$k];
                    $new_values = $v;
                }

                if ($old_value === $new_value)
                {
                    continue;
                }

                $up_message .= '将 '.$field_names.' 由 "'.$old_values.'" 修改为 "'.$new_values.'"'."<br/>";
            }
        }
        
        $arr['create_time'] = time();
        $arr['create_role_id'] = session('role_id');
        $arr['type'] = '修改';
        $arr['duixiang'] = $up_message;
        $arr['model_name'] = 'leads';
        $arr['action_id'] = $id;
        $m_action_record ->add($arr);

        // 返回执行结果
        $this->ajaxReturn(['status'=>true]);

    }


    // 608新增合作机构数据录入接口
    public function createJigouLeads()
    {
        header("Access-Control-Allow-Origin: *");

        // 判断请求
        
        // 判断参数
        if (!$data = I('post.')) $this->ajaxReturn(['status'=>false,'msg'=>'参数缺失']);
        if ( !$code = $this->getClueName($data['jg_code'])['cate'] ) $this->ajaxReturn(['status'=>false,'msg'=>'机构号码异常']);

        // 数据验证
//        if ( !is_phone($data['mobile']) ) $this->ajaxReturn(['status'=>false,'msg'=>'非法的手机号码']);

         // 数据拼接
        $data['create_time'] = time();
        $data['cluecate'] = $code;
        $data['contacts_name'] = $data['name'];
        $_have_time = M('Config')->where(['name'=>['eq'=>'leads_outdays']])->find()['value']?:10;
        $data['have_time'] = time() + (intval($_have_time) * 24 * 60 * 60) - (10 * 24 * 60 * 60);// TODO 莫名多十天,抽时间查看是否未配置失去,或列表显示的地方是否有异样
        // 获取机构名称
        $jigou_data = $this->getClueName($data['jg_code']);
        $data['owner_role_id'] = $jigou_data['person_id'];
        $data['creator_role_id'] = $jigou_data['creator_role_id']; // 创建者岗位ID
        $data['creator_name'] = $jigou_data['creator_name']; // 创建者姓名

        // 数据写入
        try {
            $insert_id = M('Leads')->add($data);
            if ($insert_id && $data['description']) {
                $outer['leads_id'] = $insert_id;
                $outer['description'] = $data['description'];
                M('LeadsData')->add($outer);
            }
        } catch (\Exception $exception){
            $this->ajaxReturn(['status'=>false,'msg'=>$exception->getMessage()]);
        }

        $this->ajaxReturn(['status'=>true,'msg'=>'录入成功']);
    }

    /**
     * 获取当前机构名称/线索分类/负责人
     */
    public function getClueName($code)
    {

    	$num = rand(1,10);

        switch (intval($num))
        {
            case 1:
            case 2:
            case 3:
            case 4:
                // 成都
                $_map = [
                    '91be12b72fsh'  =>  ['name'=>'李洋-合作机构', 'cate'=>'60', 'person_id'=>'70','creator_role_id'=>'81','creator_name'=>'李洋'],
                    'ec74f72eb0sh'  =>  ['name'=>'日本村-合作机构', 'cate'=>'65', 'person_id'=>'9','creator_role_id'=>'95','creator_name'=>'日本村'],
                ];
                break;
            case 5:
            case 6:
            case 7:
            case 8:
                // 江苏
                $_map = [
                    '91be12b72fsh'  =>  ['name'=>'李洋-合作机构', 'cate'=>'59', 'person_id'=>'70','creator_role_id'=>'81','creator_name'=>'李洋'],
                    'ec74f72eb0sh'  =>  ['name'=>'日本村-合作机构', 'cate'=>'66', 'person_id'=>'50','creator_role_id'=>'95','creator_name'=>'日本村'],
                ];
                break;
            default:
                // 上海
                $_map = [
                    '91be12b72fsh'  =>  ['name'=>'李洋-合作机构', 'cate'=>'58', 'person_id'=>'70','creator_role_id'=>'81','creator_name'=>'李洋'],
                    'ec74f72eb0sh'  =>  ['name'=>'日本村-合作机构', 'cate'=>'64', 'person_id'=>'28','creator_role_id'=>'95','creator_name'=>'日本村'],
                ];
                break;
        }
        
        return $_map[$code];
    }

    public function stuupload()
    {
        header("Access-Control-Allow-Origin: *");

        // 如果有文件上传 上传附件
        import('@.ORG.UploadFile');

        //导入上传类
        $upload = new UploadFile();
        //设置上传文件大小
        $upload->maxSize = 20000000;
        //设置附件上传目录
        $dirname = UPLOAD_PATH . date('Ym', time()) . '/' . date('d', time()) . '/';
        $upload->allowExts = array();// 设置上传文件后缀
        $upload->allowTypes = array();// 设置上传文件类型
        $upload->thumbRemoveOrigin = false;//是否删除原文件
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
            $this->ajaxReturn(['status'=>false,'msg'=>L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE')]);
        }
        $upload->savePath = $dirname;

        if (!$upload->upload()) {// 上传错误提示错误信息
            $this->ajaxReturn(['status'=>false,'msg'=>$upload->getErrorMsg()]);
        } else {// 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if (is_array($info[0]) && !empty($info[0])) {
                $upload = $dirname . $info[0]['savename'];
                $file_name = $info[0]['name'];// 新增,多文件上传保留文件名称
            } else {
                $this->ajaxReturn(['status'=>false,'msg'=>'文件上传失败，请重试！']);
            }
            // 返回文件路径
            $this->ajaxReturn(['status'=>true,'data'=>['path'=>$upload,'name'=>$file_name]]);
        }

    }


    public function crontabNoticeAdviser()
    {
        $current = strtotime(date('Y-m-d H:i:s'));// 当前时间

        $offer_time = D('MaterialsApply')->getApply(['id'=>['>',0]]);// 申请列表

        foreach ($offer_time as $k => $v)
        {
            // 考试时间
            $notice_start = $offer_time[$k]['exam_time'] - (14 * 24 * 60 * 60);// 提醒开始时间 = offer时间 - 14天
            $notice_end = $notice_start + (24 * 60 * 60);// 提醒结束时间 = 提醒开始时间 + 1天

            echo "<pre>";
            var_dump(date('Y-m-d H:i:s',$notice_start));
            var_dump(date('Y-m-d H:i:s',$current));
            var_dump(date('Y-m-d H:i:s',$notice_end));

            if ($notice_start < $current && $current < $notice_end)
            {
                $v['sname'] = D('Students')->findStudents($v['student_id'])['realname'];
                
                // 获取邮件模板
               $email_tpl = $this->createEmailTpl($v);
                // 发送邮件
               $send_res = userSendEmail('jialongfeicn@gmail.com','考试时间提醒',$email_tpl);
                // $notice_str = $v['sname']." 的 ".$v['project_name']." 申请距离考试还有两周,考试时间: ".date('Y年m月d日 H时i分',$offer_time[$k]['exam_time']);
                // 发送站内信
                // sendMessage($v['adviser_role_id'],$notice_str,1);
            }

            // 提交材料截止时间
            $notice_start = $offer_time[$k]['end_time'] - (14 * 24 * 60 * 60);// 提醒开始时间 = offer时间 - 14天
            $notice_end = $notice_start + (24 * 60 * 60);// 提醒结束时间 = 提醒开始时间 + 1天
            if ($notice_start < $current && $current < $notice_end)
            {
                $v['sname'] = D('Students')->findStudents($v['student_id'])['realname'];

                // 获取邮件模板
               $email_tpl = $this->createEmailTpl($v);
                // 发送邮件
               $send_res = userSendEmail('jialongfeicn@gmail.com','提交材料时间提醒',$email_tpl);
                // $notice_str = $v['sname']." 的 ".$v['project_name']." 申请距离材料提交截止时间还有两周,截止时间: ".date('Y年m月d日 H时i分',$offer_time[$k]['end_time']);
                // 发送站内信
                // sendMessage($v['adviser_role_id'],$notice_str,1);
            }
        }
    }

    public function createEmailTpl($data)
    {
        $trstr = '';
        $trstr      .=  "<tr><td class='gray'>申请人：</td><td colspan='5' >{$data['sname']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>申请项目：</td><td colspan='5' >{$data['project_name']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>考试时间：</td><td colspan='5' >".date('Y年m月d日 H时i分',$data['exam_time'])."</td></tr>";
        $trstr      .=  "<tr><td class='gray'>提交材料截止时间：</td><td colspan='5' >".date('Y年m月d日 H时i分',$data['end_time'])."</td></tr>";
        $tpl            =   <<<EOF
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" name="viewport">
		<title></title>
	</head>
	<style type="text/css">
		._email tr{  height: 20px;  width: 100%;  }
		._email tr td{ padding: 10px;word-wrap:break-word;word-break:break-all; }
		.gray{width: 36%;}
	</style>
	<body>
		<table border="1" bordercolor="#f0f0f0" cellspacing="1" width="100%"  class="_email">   
            {$trstr}
            <tr>
           		<td colspan="6">
           			 请提醒学员密切关注已申请项目的 提交材料截止时间 和 考试时间 . 谢谢.
           		</td>
            </tr>
       </table>
	</body>
</html>
EOF;
        ;
        return $tpl;
    }

    /**
     * 任务分配邮件提醒
     */
    public function sendtask()
    {
        if (!$to = I('post.to')) return false;// 接收者
        $content = I('post.content');// 邮件内容
        $user = session('full_name');// 发送者
        $title = "来自 ".$user." 的新任务";// 标题
        $send_res = userSendEmail($to,$title,$content);
        return $send_res;
    }

    /**
     * 添加/关联客户的目标申请
     * 816 dragon
     */
    public function addtargetapply()
    {
        $par = I('post.');

        if (!$par) $this->ajaxReturn(['status'=>false,'msg'=>'缺失参数']);

        if (!$data['customer_id'] = $par['_target_customer_id']) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);

        if (!$data['school_name'] = $par['_target_school_name']) $this->ajaxReturn(['status'=>false,'msg'=>'申请学校名称不能为空']);

        if (!$data['project_name'] = $par['_target_project_name']) $this->ajaxReturn(['status'=>false,'msg'=>'申请项目名称不能为空']);

        if ($par['_target_join_date'])
        {
            $data['join_date'] = date('Ym',strtotime($par['_target_join_date']));
        }else{
            $data['join_date'] = '';
        }

        // 填充
        $data['create_time'] = time();
        $data['create_user'] = session('role_id');
        $data['update_time'] = time();
        $data['update_user'] = session('role_id');

        $res = M('customer_target_apply')->add($data);

        if (!$res) $this->ajaxReturn(['status'=>false,'msg'=>'系统维护,请稍后再试 或 联系管理员']);

        $data['id'] = $res;

        $this->ajaxReturn(['status'=>true,'data'=>$data]);
    }

    /**
     * 删除客户的目标申请
     * 816 dragon
     */
    public function deltargetapply()
    {
        $par = I('post.');

        if (!$par) $this->ajaxReturn(['status'=>false,'msg'=>'缺失参数']);

        if (!$id = $par['apply']) $this->ajaxReturn(['status'=>false,'msg'=>'缺少关键参数']);

        $res = M('customer_target_apply')->where(['id'=>['eq',$id]])->save(['is_delete'=>1,'update_time'=>time(),'update_user'=>session('role_id')]);

        if (!$res) $this->ajaxReturn(['status'=>false,'msg'=>'系统维护,请稍后再试 或 联系管理员']);

        $this->ajaxReturn(['status'=>true]);
    }
    

}