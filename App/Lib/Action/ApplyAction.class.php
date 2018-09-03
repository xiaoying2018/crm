<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/4/18
 * Time: 9:40
 */

class ApplyAction extends Action
{
    /**
     * 展示评估数据列表
     */
    public function index()
    {
        // 模型实例化
    	$clue = D('StudentClue');
        // 获取评估数据
    	$list = $clue->getDatas();
        // 统计条数
    	$count = $clue->countDatas($list);

    	$this->assign([
    		'list'  =>  $list,
    		'count' =>  $count
    	]);
    	$this->display();
    }

    /**
     * 评估数据请求接口
     */
    public function searchClueData()
    {
        // TODO 如果需要分页...
    }

    /**
     * 展示推荐方案详情页
     */
    public function proplans()
    {
    	if (!$id = I('get.id')) die('缺少关键参数');

        // 获取指定的数据
    	$promote = D('StudentClue')->find($id);

        // 将方案中的推荐数据转换为便于前端展示的格式
    	$promote['promote'] = json_decode($promote['promote'],true);
    	foreach ($promote['promote']['school'] as $k=>$v)
    		$promote['promote']['school'][$k]['advise'] = $v['advise'];

    	$promote['promote'] = json_encode($promote['promote'],true);

    	$this->assign('all',$promote);

    	return $this->display('proplans');
    }

    /**
     * 删除评估数据
     */
    public function dataDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = D('StudentClue')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
    	}

        // 删除成功,返回列表页
    	$this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
    }

    /**
     * 接收评估表单提交数据
     * @return array
     */
    public function getInfo()
    {
    	header("Access-Control-Allow-Origin: *");

        // 1.接收表单数据
        if ( !($data = I('post.')) || !I('post.tel') || !I('post.score') ) return ['code'=>200,'success'=>false,'msg'=>'缺失关键参数'];
    	// if ( !($data = I('post.')) ) return ['code'=>200,'success'=>false,'msg'=>'缺失参数'];

        // 2.读取数据评分并取得相应的范围值 $s
        $score = $data['score'];// 统计后的分数

        // 如果转专业,则专业不计分
        if ( $data['major'] != $data['want_major'] )
        {
        	$data['score'] = $score -= 10;

            // 新增详细判断
        	if ( $data['major'] == '文' && $data['want_major'] == '商' ) $data['score'] = $score += 5;
        	if ( $data['major'] == '商' && $data['want_major'] == '文' ) $data['score'] = $score += 5;
        	if ( $data['major'] == '生' && $data['want_major'] == '医' ) $data['score'] = $score += 5;
        	if ( $data['major'] == '理' && $data['want_major'] == '工' ) $data['score'] = $score += 8;
        	if ( $data['major'] == '理' && ($data['want_major'] == '文' || $data['want_major'] == '商') ) $data['score'] = $score += 5;
        }

        $arr = [
            6 => 0, // 50分以下
            5 => 50, // 50~60分
            4 => 60, // 60~70分
            3 => 70, // 70~80分
            2 => 80, // 80~90分
            1 => 90  // 90分以上
        ];
        // 排除异常数据
        if ( $score>0 )
        {
        	foreach ($arr as $k => $v)
        	{
        		if ($score >= $v) $s = $k;
        	}
        }else{
        	$s = 6;
        }

        // 3.根据分数范围获取数据
        // 5-17新需求,如果目标专业为 美,工,医 则推荐相关专业的学校
        if ( in_array($data['want_major'],['美','工','医']) )
        {
        	$school = $this->getSchool($s,$data['want_major']);
        }else{
            $school = $this->getSchool($s); // 推荐学校
        }

        $plan = $this->getPlan($data['grade_id']); // 推荐方案(时间规划)
        $course = $this->getCourse($data['grade_id'],$data['jn_id'],$data['en_id']); // 推荐课程

        // 3.将接收的数据和推荐的数据 合并 并 写库
        $promote = compact('school','plan','course');
        $data['promote'] = json_encode($promote);
        // $data['promote'] = M('form_school')->getLastSql();
        $data['time'] = time();

        // 5-17 发送邮件给小雷
        // 获取邮件模板
        $email_tpl = $this->createEmailTpl($data);
        // 发送邮件
         $send_res = userSendEmail('lei.xingting@everelite.com','数据定位',$email_tpl);
//        $send_res = userSendEmail('jialongfeicn@gmail.com','数据定位',$email_tpl);

        try{
        	$clue = D('StudentClue');
        	$res = $clue->add($data);
        	$insert_id = $clue->getLastInsID();
        	if (!$res) return ['code'=>200,'success'=>false,'msg'=>'提交失败,请联系在线客服免费获取推荐方案.'];
        }catch (\Exception $exception){
        	die($exception->getMessage());
        }

        // 4.将推荐的方案反馈给用户

        $this->ajaxReturn(['success'=>true,'id'=>$insert_id]);

    }

    public function createEmailTpl($data)
    {
        $source = $data['crm_url']?:'数据定位页面';
        $trstr = '';
        $trstr      .=  "<tr><td class='gray'>姓名：</td><td colspan='5' >{$data['name']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>电话：</td><td colspan='5' >{$data['tel']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>日语水平：</td><td colspan='5' >{$data['jn']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>评估分数：</td><td colspan='5' >{$data['score']}</td></tr>";
        $trstr      .=  "<tr><td class='gray'>来源地址：</td><td colspan='5' >{$source}</td></tr>";
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
           			 来自于数据定位页面,为精准用户,请尽快电话回应.以上内容已录入定位数据,可在数据定位列表中查看详细信息.谢谢.
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
     * 获取推荐学校
     * @param $score
     * @return mixed
     */
    public function getSchool($condition,$major='')
    {
        // 如果是特定的目标专业,则以目标专业为条件,获取相关的学校
    	if ( $major )
    	{
    		$where['cate'] = ['like','%'.$major.'%'];
    		$where['range'] = ['eq',$condition];
    		$schools = M('form_school')->where($where)->order('sort')->limit(0,6)->select();
    	}else{
            // 否则$condition是分值,根据分值读取相应范围的学校
    		$schools = M('form_school')->where('`range`='.$condition)->order('sort')->limit(0,6)->select();
    	}

    	if ( !$schools ) return [];

        // 格式化申请条件
    	foreach ($schools as $k=>$v)
    	{
    		$schools[$k]['advise'] = explode("\r\n",$v['advise']);
    	}

    	return $schools;
    }

    /**
     * 获取推荐方案/时间规划
     * @param $grade_id
     * @return array
     */
    public function getPlan($grade_id)
    {
        // TODO 根据当前年级 $grade_id 读取相应的方案
    	$plans = M('plan')->where('`grade_id`='.$grade_id)->select();
    	if (!$plans) return [];

        // 获取方案详情并整合/处理
    	$plan = [];
    	foreach ($plans as $k=>$v)
    	{
    		$plan[$k]['name'] = $v['name'];
    		$plan[$k]['desc'] = $v['desc'];
    		$plan[$k]['info'] = M('planning')->where('`plan_id`='.$plans[$k]['id'])->field(['name','time'])->order('sort')->select();
    	}

    	return $plan;
    }

    /**
     * @param $grade
     * @param $jn
     * @param $en
     * @return mixed
     * 获取推荐课程
     */
    public function getCourse($grade,$jn,$en)
    {
        // 获取可推荐的课程分类id
    	$cate_id = $this->getCourseCondition($grade,$jn,$en);

    	$course = M('course')->where('cate_id='.$cate_id)->limit(0,3)->select();
    	return $course;
    }

    /**
     * 根据条件返回对应的课程分类ID
     * @param $grade
     * @param $jn
     * @param $en
     * @return int
     */
    public function getCourseCondition($grade,$jn,$en)
    {
        /**
         * 45,46,47,48,49 => 大一,大二,大三,大四,已毕业
         * 6,7 => 英语80-90,英语100以上
         * 1,2 => 日语N1,日语N2
         */
        // 大三大四已毕业 且 英语80分以上
        if( in_array($grade,[47,48,49]) && in_array($en,[6,7]) )
            return 3;// SGU 课程

        // 大三大四已毕业 且 英语能力80分以下 或 大三且日语N2以下
        if( ( in_array($grade,[47,48,49]) && !in_array($en,[6,7]) ) || ( $grade == 47 && !in_array($jn,[1,2]) ) )
            return 2;// 传统路线课程

        // 大一大二 或 大三且日语N2以上
        if( in_array($grade,[45,46]) || ( $grade == 47 && in_array($jn,[1,2]) ) )
            return 1;// 推荐直通车课程

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
    	$list = M('form_field')->select();
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
    			$plan = M('form_field');
    			$res = $plan->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=form');

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
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
    		try {
    			$plan = M('form_field');
    			$res = $plan->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=form');

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
    	$info = M('form_field')->find($id);
        // 分配数据并展示
    	$this->assign('info',$info);
    	$this->display('formEdit');
    }

    /**
     * 表单字段删除
     */
    public function formDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = M('form_field')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
            // 删除子栏目
    		$res = M('form_value')->where('field_id='.$id)->delete();
    		if (!$res) $this->ajaxReturn('','子栏目未删除,删除成功',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
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
    	if (!$id = intval(I('get.fid')) ) die('缺少关键参数');

        // 获取列表
    	$list = M('form_value')->where('field_id='.$id)->select();
        // 统计总数
    	$count = count($list);
        // 分配变量并展示
    	$this->assign('list', $list);
    	$this->assign('count', $count);
    	$this->assign('field_id',$id);
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
    			$res = M('form_value')->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=formContent&fid='.$data['field_id']);

    	}

    	if (!($field_id = I('get.fid'))) die('参数缺失');
    	$this->assign('field_id',$field_id);
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
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
    		try {
    			$res = M('form_value')->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=formContent&fid='.$data['fid']);

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
    	$info = M('form_value')->find($id);

        // 分配数据并展示
    	$this->assign('info',$info);
    	$this->display('formContentEdit');
    }

    /**
     * 字段 value 删除
     */
    public function formContentDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = M('form_value')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
    	}

        // 删除成功,返回列表页
    	$this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }

    // ---------------------------------------------------------------------
    //  方案部分
    // ---------------------------------------------------------------------

    public function plan()
    {
    	$list = M('plan')->select();
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
    			$plan = M('plan');
    			$res = $plan->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}
            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=plan');
    	}

        // 获取列表 TODO 这里 field_id 必须跟添加的年级字段ID保持一致! 目前是6,线上重新添加可能需要重新修改.
    	$grades = M('form_value')->where('field_id=6')->select();

    	if ( !$grades ) die('缺少必要条件,请先在表单管理中心添加年级字段!');

    	$this->assign('grades',$grades);
    	$this->display('planAdd');
    }

    public function planEdit()
    {
    	if ($this->isPost()) {
            // 1.接收表单数据
    		if ( !($data = I('post.')) || !($id = I('post.id')) ) return ['code' => 200, 'status' => false, 'msg' => '缺失参数'];

            // 过滤数据

            // 执行修改操作
    		try {
    			$plan = M('plan');
    			$res = $plan->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认添加数据是否合理 或 联系管理员! ');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 修改成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=plan');

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取详情
    	$info = M('plan')->find($id);

        // 分配数据并展示
    	$this->assign('info',$info);

        // 获取年级列表 TODO 这里 field_id 必须跟添加的年级字段ID保持一致! 目前是6,线上重新添加可能需要重新修改.
    	$grades = M('form_value')->where('field_id=6')->select();

    	if ( !$grades ) die('缺少必要条件,请先在表单管理中心添加年级字段!');

    	$this->assign('grades',$grades);
    	$this->display('planEdit');
    }

    public function planDelete()
    {
        // 接收ID 参数
    	if (!($id = I('post.id'))) die('缺少关键参数');
        // 模型实例化
    	$plan = M('plan');
        // 执行删除操作
    	for ($i = 0; $i < count($id); $i++) {
    		$res = $plan->where('id=' . $id[$i])->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	}
    	$this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }

    public function planning()
    {
        // 获取关键参数
    	if (!$id = intval(I('get.pid')) ) die('缺少关键参数');

        // 获取列表
    	$list = M('planning')->where('plan_id='.$id)->select();
        // 统计总数
    	$count = count($list);
        // 分配变量并展示
    	$this->assign('list', $list);
    	$this->assign('count', $count);
    	$this->assign('plan_id',$id);
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
    			$res = M('planning')->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=planning&pid='.$data['plan_id']);

    	}

    	if (!($plan_id = I('get.pid'))) die('参数缺失');
    	$this->assign('plan_id',$plan_id);
    	$this->display('planningAdd');
    }

    public function planningEdit()
    {
        // 如果是更新操作
    	if ($this->isPost()) {
            // 接收数据
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
    		try {
    			$res = M('planning')->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=planning&pid='.$data['plan_id']);

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
    	$info = M('planning')->find($id);

        // 分配数据并展示
    	$this->assign('info',$info);
    	$this->display('planningEdit');
    }

    public function planningDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = M('planning')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
    	}

        // 删除成功,返回列表页
    	$this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);

    }

    // ---------------------------------------------------------------------
    //  学校部分
    // ---------------------------------------------------------------------

    public function school()
    {
    	$list = M('form_school')->select();
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

            // 处理分类
            // 应5-3新需求,现将form_school表添加cate字段
            // cate只有7中可能,都是单个文字,且form_school表数据不会超过100条
            // 故这里使用拼接方式将cate值组成短字符串,取值使用模糊匹配
    		$data['cate'] = implode('',$data['cate']);

            //上传logo
    		if ( $_FILES['logo']['size'] ) {

                // 如果有文件上传 上传附件
    			import('@.ORG.UploadFile');
                import('@.ORG.Image');//引入缩略图类
                $Img = new Image();//实例化缩略图类
                //导入上传类
                $upload = new UploadFile();
                //设置上传文件大小
                $upload->maxSize = 20000000;
                //设置附件上传目录
                $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                $upload->allowExts  = array('jpg','jpeg','png','gif');// 设置附件上传类型
                $upload->thumb = true;//生成缩图
                $upload->thumbRemoveOrigin = false;//是否删除原图
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                	$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
                }
                $upload->savePath = $dirname;

                if(!$upload->upload()) {// 上传错误提示错误信息
                	alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                }else{// 上传成功 获取上传文件信息
                	$info =  $upload->getUploadFileInfo();
                	if(is_array($info[0]) && !empty($info[0])){
                		$upload = $dirname . $info[0]['savename'];
                	}else{
                		$this->error('图片上传失败，请重试！');
                	}
                	$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);

                    //写入数据库
                	foreach($info as $iv){
                		if($iv['key'] == 'main_pic'){
                            //主图
                			$img_data['is_main'] = 1;
                		}else{
                            //副图
                			$img_data['is_main'] = 0;
                		}
                		$img_data['name'] = $iv['name'];
                		$img_data['save_name'] = $iv['savename'];
                		$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
                		$img_data['path'] = $iv['savepath'].$iv['savename'];
                        $img_data['thumb_path'] = $thumb_path; //缩略图
                    }
                }
            }

            if ( !($data['logo'] = $img_data['path']) ) die('图片上传有误');

            // 处理advise字段 
            $data['advise'] = rtrim($data['advise']);

            // 执行添加操作
            try {
            	$plan = M('form_school');
            	$res = $plan->add($data);
            	if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
            } catch (\Exception $exception) {
            	die($exception->getMessage());
            }

            // 添加成功,返回列表页
            $this->redirect('/index.php?m=apply&a=school');

        }

        // 获取分类数据(专业选项) field_id = 8 代表是目标专业字段的value
        $cate = M('form_value')->where('field_id=8')->select();
        $this->assign('cates',$cate);
        $this->display('schoolAdd');
    }

    public function schoolEdit()
    {
        // 如果是更新操作
    	if ($this->isPost()) {
            // 接收数据
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 处理分类
            // 应5-3新需求,现将form_school表添加cate字段
            // cate只有7中可能,都是单个文字,且form_school表数据不会超过100条
            // 故这里使用拼接方式将cate值组成短字符串,取值使用模糊匹配
    		$data['cate'] = implode('',$data['cate']);

            //上传logo
    		if ( $_FILES['logo']['size'] ) {

                // 如果有文件上传 上传附件
    			import('@.ORG.UploadFile');
                import('@.ORG.Image');//引入缩略图类
                $Img = new Image();//实例化缩略图类
                //导入上传类
                $upload = new UploadFile();
                //设置上传文件大小
                $upload->maxSize = 20000000;
                //设置附件上传目录
                $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                $upload->allowExts  = array('jpg','jpeg','png','gif');// 设置附件上传类型
                $upload->thumb = true;//生成缩图
                $upload->thumbRemoveOrigin = false;//是否删除原图
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                	$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
                }
                $upload->savePath = $dirname;

                if(!$upload->upload()) {// 上传错误提示错误信息
                	alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                }else{// 上传成功 获取上传文件信息
                	$info =  $upload->getUploadFileInfo();
                	if(is_array($info[0]) && !empty($info[0])){
                		$upload = $dirname . $info[0]['savename'];
                	}else{
                		$this->error('图片上传失败，请重试！');
                	}
                	$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);

                    // 准备数据
                	foreach($info as $iv){
                		if($iv['key'] == 'main_pic'){
                            //主图
                			$img_data['is_main'] = 1;
                		}else{
                            //副图
                			$img_data['is_main'] = 0;
                		}
                		$img_data['name'] = $iv['name'];
                		$img_data['save_name'] = $iv['savename'];
                		$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
                		$img_data['path'] = $iv['savepath'].$iv['savename'];
                        $img_data['thumb_path'] = $thumb_path; //缩略图
                    }
                    $data['logo'] = $img_data['path'];
                }

            }

            // 处理advise字段 
            $data['advise'] = rtrim($data['advise']);

            // 执行更新操作
            try {
            	$res = M('form_school')->where('id='.$id)->save($data);
                // if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
            } catch (\Exception $exception) {
            	die($exception->getMessage());
            }

            // 修改成功,返回列表页
            $this->redirect('/index.php?m=apply&a=school');

        }

        // 获取关键参数
        if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
        $info = M('form_school')->find($id);

        // 获取分类数据(专业选项) field_id = 8 代表是目标专业字段的value
        $cate = M('form_value')->where('field_id=8')->select();
        // 分配数据并展示
        $this->assign([
        	'cates'=>$cate,
        	'info'=>$info
        ]);
        $this->display('schoolEdit');
    }

    public function schoolDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = M('form_school')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
    	}

        // 删除成功,返回列表页
    	$this->ajaxReturn('', L('DELETED SUCCESSFULLY'), 1);
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
    	$list = D('CourseCate')->select();
        // 统计总条数
    	$count = count($list);

    	$this->assign([
    		'list'=>$list,
    		'count'=>$count
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
    			$res = D('CourseCate')->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=courseCate');

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
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 执行更新操作
    		try {
    			$res = D('CourseCate')->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=courseCate');

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');
        // 获取详情
    	$info = D('CourseCate')->find($id);

    	$this->assign('info',$info);
    	$this->display('courseCateEdit');
    }

    /**
     * 分类删除
     */
    public function courseCateDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = D('CourseCate')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
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
    	$list = D('Course')->getCourses();
        // 统计总条数
    	$count = count($list);

    	$this->assign([
    		'list'=>$list,
    		'count'=>$count
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

            // 上传logo
    		$img_data = $this->uploadLogo();

            // logo 字段赋值
    		if ( !($data['logo'] = $img_data['path']) ) die('图片上传有误');

            // 执行添加操作
    		try {
    			$res = D('Course')->add($data);
    			if (!$res) die('添加失败,请检确认添加数据是否合理 或 联系管理员! ');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 添加成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=course');

    	}

        // 获取课程分类
    	$cates = D('CourseCate')->select();

    	$this->assign('cates',$cates);
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
    		if (!($data = I('post.')) || !($id = I('post.id')) ) die('缺少参数!');

            // 过滤数据

            // 如果更新logo
    		if ( $_FILES['logo']['size'] )
    		{
    			$img_data = $this->uploadLogo();
    			$data['logo'] = $img_data['path'];
    		}

            // 执行更新操作
    		try {
    			$res = D('Course')->where('id='.$id)->save($data);
    			if (!$res) die('修改失败,请检确认数据是否合理 或 联系管理员!');
    		} catch (\Exception $exception) {
    			die($exception->getMessage());
    		}

            // 修改成功,返回列表页
    		$this->redirect('/index.php?m=apply&a=course');

    	}

        // 获取关键参数
    	if (!($id = I('get.id'))) die('缺少关键参数');

        // 获取课程分类
    	$cates = D('CourseCate')->select();

        // 获取课程详情
    	$info = D('Course')->find($id);

    	$this->assign([
    		'info'  =>  $info,
    		'cates' =>  $cates
    	]);
    	$this->display('courseEdit');
    }

    /**
     * 课程删除
     */
    public function courseDelete()
    {
        // 获取关键参数
    	if (!$ids = I('post.id') ) die('缺少关键参数');

        // 处理id集合
    	$id = implode(',',$ids);

        // 执行删除
    	try {
    		$res = D('Course')->where('id in('.$id.')')->delete();
    		if (!$res) $this->ajaxReturn('','删除失败',0);
    	} catch (\Exception $exception){
    		die('删除失败'.$exception->getMessage());
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
        $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
        $upload->allowExts  = array('jpg','jpeg','png','gif');// 设置附件上传类型
        $upload->thumb = true;//生成缩图
        $upload->thumbRemoveOrigin = false;//是否删除原图
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
        	$this->error(L('ATTACHMENTS TO UPLOAD DIRECTORY CANNOT WRITE'));
        }
        $upload->savePath = $dirname;

        if(!$upload->upload()) {// 上传错误提示错误信息
        	alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
        }else{// 上传成功 获取上传文件信息
        	$info =  $upload->getUploadFileInfo();
        	if(is_array($info[0]) && !empty($info[0])){
        		$upload = $dirname . $info[0]['savename'];
        	}else{
        		$this->error('图片上传失败，请重试！');
        	}
        	$thumb_path = $Img->thumb($upload,$dirname.'thumb_'.$info[0]['savename']);

            //写入数据库
        	foreach($info as $iv){
        		if($iv['key'] == 'main_pic'){
                    //主图
        			$img_data['is_main'] = 1;
        		}else{
                    //副图
        			$img_data['is_main'] = 0;
        		}
        		$img_data['name'] = $iv['name'];
        		$img_data['save_name'] = $iv['savename'];
        		$img_data['size'] = sprintf("%.2f", $iv['size']/1024);
        		$img_data['path'] = $iv['savepath'].$iv['savename'];
                $img_data['thumb_path'] = $thumb_path; //缩略图
            }
            return $img_data;
        }
    }

}