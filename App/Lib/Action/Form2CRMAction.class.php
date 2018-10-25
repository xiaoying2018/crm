<?php

class Form2CRMAction extends Action
{
    // 映射声明
    protected $_map;
    // 线索生成接口地址
    protected $_api;
    // 邮件发送列表
    protected $_mailto;
    // 自定义字段储存声明
    protected $_custom;
    // 允许请求主域名集
    protected $_allow_main_domain;
    // 分配规则
    protected $_regular;
    // 机器人
    protected $_robot;
    // token标识
    protected $_token;
    // 数据储存仓库
    protected $_data_depot;
    // 手机验证码 标识
    protected $_mobile_verify_sign;
    // 手机验证码有效时间
    protected $_mobile_verify_expr;
    // token类型
    protected $_token_sign  =   false;

    /*****************初始化*******************/
    public function _initialize()
    {
        // 参数初始化
        $this->_init_dispatch();
    }

    protected function _init_dispatch ()
    {
        // 初始化数据仓库
        $this->_init_depot();
        // 初始化配置项
        $this->_init_config();
    }

    protected function _init_depot ()
    {
        // 校区数据,线索分类数据
        $this->_data_depot['cluecates']     =   $this->_getBlockCategory();
        // 数据库字段列表
        $this->_data_depot['DBfields']      =   $this->_getDBFields();
    }

    /**
     * @ 配置文件初始化
     */
    protected function _init_config ()
    {
        // 加载配置文件
        $config                     =   require_once CONF_PATH.'form_lead.php';
        $this->_api                 =   $config['_api'];
        $this->_allow_main_domain   =   $config['_allow_domains'];
        $this->_mailto              =   $config['_mailto'];
        $this->_custom              =   $config['_fields'];
        $this->_map                 =   $this->_mapping();
        $this->_regular             =   $config['_regular'];
        $this->_robot               =   $config['_robot'];
        $this->_token               =   $config['_token_sign'];
        $this->_developer           =   $config['_developer'];
        $this->_source              =   $config['_source'];
        $this->_block               =   $config['_block'];
        $this->_mailtpl             =   $config['_mailtpl'];

        // 验证码获取地址
        $this->_verify              =   $config['_verify'];
        // 手机验证码 标识
        $this->_mobile_verify_sign  =   $config['_mobile_verify_sign'];
        // 有效时间
        $this->_mobile_verify_expr  =   $config['_mobile_verify_expr'];

        // 真实字段映射
        foreach ($this->_map['_aliasMap'] as $key => $value){
            $value['alias']     =   $key;
            $this->_data_depot['_realMap'][$value['field']]     =   $value;
        }
        return ;
    }

    /*******    操作  ********/
    public function fields()
    {
        try{
//            $this->_origin_check();
            // TODO 返回信息：字段映射信息、提交接口地址、token信息
            $info           =   [
                '_api'          =>  $this->_api,
                '_verify'       =>  $this->_verify,
                '_token'        =>  [
                    'sign'  =>  $this->_token,
                    'value' =>  session( $this->_token ) ?: $this->_refresh_token(),
                ],
            ];
            $info           =   array_merge( $info, $this->_map );

            $this->ajaxReturn($info,'JSONP');
        }catch (Exception $e){
            header('HTTP/1.1 '.$e->getCode());
            $this->ajaxReturn( ['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage() ], 'JSONP' );
        }
    }

    public function create()
    {
        // TODO 请求验证、参数处理、去重写库、通知
        // $this->ajaxReturn( session( $this->_mobile_verify_sign ), 'JSONP' );
        try{
            // 请求验证
            $this->_check_dispatch();
            // 数据处理
            $created                =   $this->dataHandle();
            // 短信验证码验证
            $this->_check_verify($created);

//            // 响应
//            QueueEcho([
//                'status' => false,
//                'data' => $created,
//            ], function () use ($created) {
//                // 通知
//                $notifyResult = $this->_notify_dispatch($writeResult['create']);
//                // 记录信息
//            }, 'JSONP');

            $params                 =   I('get.');

            // 临时需求 语言展数据另行处理 8-18后废弃
            if ($params['yuyanzhan'])
            {
                // 录入到指定的数据表中
                $params['create_time'] = date('Y-m-d H:i:s',time());
                M('Yuyanzhan')->add($params);
                
                // 发送短信给指定人员
                import('@.ORG.SMS.SMSBao');

                SMSBao::send('18918481009', '姓名:'.$params['XY_a01'].'. 手机:'.$params['XY_a02'].'. 微信:'.$params['XY_b11'].'. -- 语言展会数据.');

                // 发送邮件给指定人员
                import("@.ORG.Mail");
                $mail               =   new \PHPMailer(true);
                $title              =   "【语言展会表单】 {$params['XY_a01']}：{$params['XY_a02']}";
                $message            =   '姓名:'.$params['XY_a01'].'. 手机:'.$params['XY_a02'].'. 微信:'.$params['XY_b11'].'. -- '.date('Y-m-d H:i:s',time()).' 语言展会数据.';
                $setting            =   M('Config')->field('value')->where(['name'=>['eq','smtp']])->find();
                $setting            =   unserialize( $setting['value'] );
                C($setting,'smtp');

                $mail->IsSMTP();
                $mail->CharSet=C('MAIL_CHARSET');
                if( is_array($this->_mailto) ){
                    foreach ( $this->_mailto as $mailto ){
                        if( $mailto ){
                            $mail->AddAddress($mailto);
                        }
                    }
                    $mail->AddAddress('jialongfeicn@gmail.com');
                    $mail->AddAddress('cui.limin@everelite.com');
                }else{
                    $mail->AddAddress($this->_mailto);
                    $mail->AddAddress('jialongfeicn@gmail.com');
                    $mail->AddAddress('cui.limin@everelite.com');
                }
                $mail->Body=$message;
                $mail->From= C('MAIL_ADDRESS');
                $mail->FromName='语言展会表单';
                $mail->Subject=$title;
                $mail->Host=C('MAIL_SMTP');
                $mail->SMTPAuth=C('MAIL_AUTH');
                $mail->Port=C('MAIL_PORT');
                $mail->SMTPSecure= C('MAIL_SECURE');
                $mail->Username=C('MAIL_LOGINNAME');
                $mail->Password=C('MAIL_PASSWORD');
                $mail->IsHTML(true);
                $mail->MsgHTML($message);
                $mail->Send();

                // stop
                QueueEcho([
                    'status' => true,
                ], function () use ($params) {
                    
                }, 'JSONP');
            }

            if (!$params['XY_b35']) $params['XY_b35'] = '86';// 无此参数 即国内手机号码

            if ($params['XY_b35'] != '86')// 如果是國外手機,添加區號以便區分
            {
                $created['mobile'] = $params['XY_b35'].'-'.$created['mobile'];
            }

            // 1024 新增 判断域名
            if ((strpos($params['XY_b28'],'lang') !== false) || (strpos($params['XY_b28'],'yuyan') !== false)){
                // 如果是 /yuyan /yuyanms /yuyanzl lang.xiaoying.net lang.xiao-ying.net lang.eggelite.com 网站提交的表单,填充咨询项目字段为 语言学校
                $created['crm_project'] = '语言学校';
            }elseif (strpos($params['XY_b28'],'sgu') !== false){
                // 如果是 /sgu 网站提交的表单,填充咨询项目字段为 SGU
                $created['crm_project'] = 'SGU';
            }elseif (strpos($params['XY_b28'],'meishu') !== false){
                // 如果是 /meishu 网站提交的表单,填充咨询项目字段为 艺术
                $created['crm_project'] = '艺术';
            }elseif (strpos($params['XY_b28'],'duyan') !== false){
                // 如果是 /duyan 网站提交的表单,填充咨询项目字段为 读研
                $created['crm_project'] = '读研';
            }
            // 1024 end

            // 去重处理、写库
            $writeResult            =   $this->write($created);
            // token 处理
            session( $this->_token, null );
            session( $this->_mobile_verify_sign, null );

            // 响应
            QueueEcho([
                'status' => true,
                'debug' => $writeResult,
            ], function () use ($writeResult) {
                // 通知
                $notifyResult = $this->_notify_dispatch($writeResult['create']);
                // 记录信息
                $recordsInfo        =   compact( 'created', 'notifyResult', 'writeResult' );
                // 日志记录
                Log::write(serialize($recordsInfo), Log::INFO, 3, RUNTIME_PATH . 'Form/' . date('y_m_d') . '.log');
            }, 'JSONP');

        }catch (\Exception $e){
            header('HTTP/1.1 200');
            // 报警
            QueueEcho( ['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage() ], function() use($e){
                if( !in_array( $e->getCode(), [208] ) ){
                    $message    =   $e->getFile().':line'.$e->getLine().' '.$e->getMessage();
                    $this->_alarm($message);
                }
            }, 'JSONP' );
        }
    }

    /**
     * @ 获取手机验证码
     */
    public function mobileVerify ()
    {
        try{
            // 验证
            $this->_check_dispatch();
            $aliasKey               =   'XY_a02';
            // 参数接收
            $params                 =   I('get.');
            // 参数过滤
            $params                 =   $this->paramHandle($params);

            $mobile                 =   $params[$aliasKey];

            if (!$params['XY_b35']) $params['XY_b35'] = '86';// 无此参数 即国内手机号码

            if ($params['XY_b35'] == '86')// 只驗證國內手機
            {
                if( !array_key_exists( $aliasKey, $params ) || !$params[$aliasKey] )
                {
                    throw new Exception( '手机号不合法', 208 );
                }

                // 手机号验证
                $current                =   [0=>$aliasKey, 1=>$mobile];
                if( !$this->_validator( $current, new Validator() ) ){
                    throw new Exception( '手机号不合法', 208 );
                }

            }

            // 验证码是否存在且合法
            if( $this->checkVerifyExistsYet($mobile) )
            {
                throw new Exception('验证码已发送过', 208);
            }

            $code                   =   getRandStr( 4, 2 );
            
            // 导入短信类
            if ($params['XY_b35'] != '86') {
                // $code                   =   '6666';
                import('@.ORG.aliyun.api_demo.SmsSend');
                $expr = ceil($this->_mobile_verify_expr / 60);
                $intermobile = '00' . $params['XY_b35'] . $mobile;
                $s = SmsSend::sendSms($intermobile, '小莺出国', 'SMS_138079875', $code);

				if ($s->Code != 'OK') { // 发送失败
		            $result = [
		                'status'        =>  false,
		                'message'     	=>  '系统维护,请稍后再试',
		                'outer'         =>  ['Message'=>$s->Message,'Code'=>$s->Code],
		            ];
		            
		            $this->ajaxReturn($result,'JSONP');
				}

            } else {
                import('@.ORG.SMS.SMSBao');
                $expr = ceil($this->_mobile_verify_expr / 60);
                $content = $msg = "【小莺出国】您的四位数字登陆验证码为：{$code} ;有效期为{$expr}分钟";
                if (SMSBao::send($mobile, $content) != 0)
                    throw new Exception('系统维护，请稍后再试', 208);
            }

            // code入session
            $oldCount               =   0;
            if( $oldSession=session( $this->_mobile_verify_sign ) ){
                $oldCount       =   $oldSession['count'] ?: 0;
            }
            $signData               =   [
                'mobile'    =>  $mobile,
                'code'      =>  $code,
                'expr'      =>  time()+$this->_mobile_verify_expr,
                'count'     =>  ++$oldCount,
            ];
            session( $this->_mobile_verify_sign, $signData );
            // 刷新token
            $result = [
                'status'        =>  true,
                '_token'        =>  [
                    'sign'  =>  $this->_token,
                    'value' =>  $this->_refresh_token('_s'),
                ],
            ];
            // 成功返回
            $this->ajaxReturn($result,'JSONP');

        }catch (Exception $e){
            header('HTTP/1.1 200');
            // 报警
            QueueEcho( ['status'=>false, 'code'=>$e->getCode(), 'message'=>$e->getMessage() ], function() use($e){

            }, 'JSONP' );
        }
    }

    /**
     * @ 验证码是否存在
     * @param $mobile
     * @return bool
     */
    protected function checkVerifyExistsYet ($mobile)
    {
        if( ($verifyCode=session($this->_mobile_verify_sign))
            && $verifyCode['mobile'] == $mobile
            && $verifyCode['expr'] > time()
        ){
            if( $verifyCode['count'] >= 7 ){
                throw new Exception( '短信发送过于频繁，请稍后再试', 208 );
            }
            return true;
        }else{
            return false;
        }
    }

    protected function _alarm ($message)
    {
        // 邮件报警
        userSendEmail( $this->_developer, '【表单报警】', $message );
        // 错误记录
        Log::write($message, Log::ERR, 3, RUNTIME_PATH . 'Form/error_' . date('y_m_d') . '.log');

        return ;
    }

    /******* 请求验证 ********/
    /**
     * @ 请求验证
     */
    protected function _check_dispatch ()
    {
        // 来源验证
        $this->_origin_check();
        // token 验证
        $this->_token_check();
        return ;
    }

    /**
     * @请求来源验证
     * @param $http_origin
     * @throws Exception
     */
    protected function _origin_check ()
    {
        // 请求来源
        $http_origin                =   false;
        if( array_key_exists( 'HTTP_ORIGIN', $_SERVER ) && $_SERVER['HTTP_ORIGIN'] ){
            $http_origin        =   $_SERVER['HTTP_ORIGIN'];
        }elseif( array_key_exists( 'HTTP_REFERER', $_SERVER ) && $_SERVER['HTTP_REFERER'] ){
            $http_origin        =   $_SERVER['HTTP_REFERER'];
        }
        // 来源判断
        if( $http_origin === false )    return ;
        // 获取来源主域
        preg_match("/^(?:http|https):\/\/([^\/]+)/", $http_origin, $matches);

        if( !array_key_exists( 1, $matches ) )
            throw new Exception( 'NOT ALLOW 1', 208 );
        $main               =   array_reverse( explode( '.', $matches[1] ) ) ;
        $origin_main                =   $main[1].'.'.$main[0];
        // 主域名合法度验证
        if( !in_array( $origin_main, $this->_allow_main_domain ) )
            throw new Exception( 'NOT ALLOW 2', 208 );
        // 来源控制
        return $this->_originControl( $matches[0] );
    }

    /**
     * @ 来源设置
     * @param $origin
     */
    protected function _originControl ($origin)
    {
        header('Access-Control-Allow-Origin: '.$origin);
        header("Access-Control-Allow-Methods: POST, GET");
        header("Access-Control-Allow-Credentials: true");
        return;
    }

    /**
     * @ token 验证
     * @throws Exception
     */
    protected function _token_check ()
    {
        // 比对token、客户端token
        $sign_token             =   session($this->_token) ?: false;
        $client_token           =   I($this->_token) ?: false;
        // token 无效
        if( $client_token === false || $sign_token === false )
            throw new Exception('TOKEN NONE', 208);
        // token 错误
        if( $sign_token != $client_token ){
            // TODO 暂时关闭token错误自动刷新token设置 原因：`再次请求`的token 刷新后没有记录token类型，无法进一步有效处理<区块连思想>
            // $this->_refresh_token();
            throw new Exception('TOKEN ERROR',208);
        }
        // 记录token类型
        $token_type             =   explode('_', $sign_token);

        if( count($token_type) < 2 ){
            return ;
        }else{
            $this->_token_sign  =   $token_type[1];
        }

        return ;
    }

    protected function _refresh_token ($sign=null)
    {
        $token      =   strtoupper( md5( uniqid(mt_rand(),true) )  );
        $token      =   is_null( $sign ) ? $token : $token.$sign;
        session( $this->_token,$token );
        return session( $this->_token );
    }

    /**
     * @ 短信验证码验证
     */
    protected function _check_verify (&$data)
    {
        $verify_key                 =   'sms';
        // 是否验证
        if( $this->_token_sign == 's'
            || array_key_exists($verify_key, $data)
            || session('?'.$this->_mobile_verify_sign)
        ){
            $origin_code            =   $data[$verify_key];
            $server_code            =   session($this->_mobile_verify_sign);

            // 服务端验证码是否存在
            if( is_null($server_code) )
                throw new Exception( '请先发送验证码', 208 );
            // 当前手机是否是接收验证码手机
            if( $server_code['mobile'] != $data['mobile'] )
            	throw new Exception( '验证码不正确', 208 );

                // throw new Exception( json_encode(['service'=>$server_code['mobile'],'client'=>$data['mobile']]), 208 );

            // 验证码是否过期
            if( $server_code['expr'] < time() ){
                // 销毁旧验证码
                session( $this->_mobile_verify_sign, null );
                throw new Exception( '验证码已过期,请重新获取', 208 );
            }
            // 验证码不正确
            if( $server_code['code'] != $origin_code )
                throw new Exception( '验证码错误', 208 );
            // 销毁数据中的验证码
            unset( $data[$verify_key] );
            return true;
        }
        return null;
    }

    /******* 参数处理 ********/
    /**
     * @ 数据处理
     * @return mixed
     */
    protected function dataHandle ()
    {
        $original = I('get.');
        // 参数接收
        $params = $this->paramHandle($original);
        // 参数验证、转化
        $verified = $this->validHandle($params);
        // 信息填充
        if ($verified['result'] === false) {
            throw new Exception($verified['field'],208);
        }
        return $verified['verified'];
    }

    /**
     * @ 参数接收处理
     * @param $params
     * @return mixed
     */
    protected function paramHandle ($params)
    {
        // 合法假字段列表
        $allows             =   $this->_map['_legal'];

        // 合法集
        $legal              =   [];
        foreach( $params as $key => $value ) {
            if( in_array( $key, $allows ) )
                $legal[$key] = $value;
        }

        $legal      =   array_filter( $legal );

        return $legal;
    }

    /**
     * @参数验证
     * @param $params
     */
    protected function validHandle ($params)
    {
        // 已验证
        $verified           =   [];
        $validator          =   new Validator();

        // 字段验证
        foreach ($params as $key => $value){
            if ($key == 'XY_a02' || $params['XY_b35'] != '86')
            {
                $verified[$this->_map['_aliasMap'][$key]['field']]       =   $value;
                continue;// 如果手機號碼區號不是中國的,則不驗證手機號碼
            }
            if( ( $result = $this->_validator([$key, $value],$validator) ) === false ){
                return [ 'result'=>false, 'field'=>$key ];
            }else{
                $verified[$this->_map['_aliasMap'][$key]['field']]       =   $value;
            }
        }
        // 验证必须字段
        foreach ($this->_map['_must'] as $key => $v){
            if ($key == 'XY_a02' || $params['XY_b35'] != '86')
            {
                continue;// 如果手機號碼區號不是中國的,則不驗證手機號碼
            }
            if( !array_key_exists( $v['field'], $verified ) ){
                return ['result'=>false, 'field'=>$key];
                break;
            }
        }

        return ['result'=>true, 'verified'=>$verified];
    }

    /**
     * @ 单元验证
     */
    protected function _validator ($current, $validator)
    {
        // type 1：文本 2：单选 3：多选 4：文本域 5:电话 6：邮件
        $_map       =   $this->_map['_aliasMap'][$current[0]];
        // 验证规则
        $_rule      =   $_map['_v'];

        return $validator->handle($current[1],$_rule);
    }

    /**
     * @ 数据填充
     * @param $params
     */
    protected function fullInfo ($params, $block_id)
    {
        $focus                      =   $this->getFocus($block_id);

        
        // 新增需求 如果是精英计划来的数据,随机分配给指定的销售人员 2018-7-30 dragon
        if (I('get.target') == 'jyjh')
        {
            // 曹玉霞:7
            // 简依梦:9
            // 汪晓敏:13
            // 陈铭菁:14
            // 徐晓洁:33
            // 赵梓帆:50
            // 赵旭鸣:65
            // 杨文颖:75
            // 李璇:101
            // 欧阳含笑:107
            $target_user = ['7','9','13','14','33','50','65','75','101','107'];// 指定的销售人员role_id
            $rand_target_user = $target_user[array_rand($target_user)];// 从指定人员随机获取负责人
            $params['owner_role_id']    =   $rand_target_user;// 赋值负责人
        }elseif (I('get.target') == 'join'){
            // 新增需求 如果是加盟网站来的数据,分配给指定的销售人员 2018-8-15 dragon
            $params['owner_role_id']    =   '108';// 赋值负责人 108 = 郝璐洋
            // 8-15 end
        }else{
            $params['owner_role_id']    =   (int)$focus['role_id'];// 赋值负责人 : 老代码,除了此行,其他全是7-30新增代码
        }
        // 7-30 end
        

        $params['creator_role_id']  =   62;
        $params['create_time']      =   time();
        $params['creator_name']     =   '机器人';
        $params['have_time']        =   time();
        $this->_mailto[]            =   $focus['email'];
        return $params;
    }

    /**
     * @ 线索分配
     * @param $params
     * @return mixed
     */
    protected function getFocus ($block_id)
    {
        // TODO 计算线索属于哪个部门、取出部门成员、随机分配、数据填充
        //      校区信息-->负责人ID
        $blockInfo          =   M('Block')->field('person_id,department_id')->find( (int)$block_id );
        // TODO 根据分配规则 获取拥有着
        $focus              =   [];
        switch ($this->_regular)
        {
            //      负责人
            case 1:
                $focus      =   D('RoleView')->where(['role_id'=>['eq',(int)$blockInfo['person_id']]])->find();
                break;
            case 2:
                //      销售部信息
                $saleInfo           =   M('RoleDepartment')->field('department_id')
                    ->where(['parent_id'=>['eq',$blockInfo['department_id']], 'name'=>['like','%销售%']])->find();
                //      岗位信息
                $positions          =   M('Position')->field('position_id')
                    ->where(['department_id'=>['eq',(int)$saleInfo['department_id']]])->select();
                $position_ids       =   array_column( $positions, 'position_id' );
                $position_ids       =   implode(',', $position_ids);
                //      成员信息
                $numbers            =   D('RoleView')
                    ->where("( role.position_id IN ({$position_ids}) OR role.role_id = {$blockInfo['person_id']} ) AND ( user.status='1' )")
                    ->order('user_id ASC')->select();

                $numbersMap         =   [];
                foreach ($numbers as $n){
                    $numbersMap[$n['user_id']]      =   $n;
                }
                $focus              =   $numbersMap[array_rand( $numbersMap, 1 )];
                break;
            default:
                $focus      =   D('RoleView')->where(['role_id'=>['eq',$this->_robot]])->find();
                break;
        }

        return $focus;
    }

    /**
     * @ 字段映射库
     * @return array
     */
    protected function _mapping ()
    {
        // 原始字段列表
        $_aliasMap              =   $this->fieldsHandle();
        // 必须字段
        $_must                  =   array_filter( $_aliasMap, function($item){
            return $item['must'];
        } );
        // 合法假字段
        $_legal                 =   array_keys( $_aliasMap );
        // 字段类型
        $_type              =   [ 1=>'文本,电话,邮件',2=>'下拉',3=>'单选',4=>'多选',5=>'文本域' ];
        return compact('_aliasMap','_must', '_legal', '_type');
    }

    /**
     * @ 字段信息处理
     * @return mixed
     */
    protected function fieldsHandle ()
    {
        // TODO 单、多选字段 value赋值；但多选验证 _v 字段替换 （1：文本 2：单选 3：多选 4：文本域 ）
        // 单、多选字段 value赋值
        //      加载原生配置文件
        $_mapping               =    $this->_custom;
        //      DB字段集选项列表
        $DBFields               =  $this->_data_depot['DBfields'];

        //      映射处理，转换为映射数组
        $handleFields       =   [];
        foreach($DBFields as $key => $value){
            $handleFields[$value['field']]  =   $value;
        }
        //      遍历赋值
        foreach ( $_mapping as $key => $value ){
            $fieldName          =   $value['field'];
            // 特殊处理
            if( $value['field'] == 'cluecate' ){
                //      获取线索分类信息
                $_mapping[$key]['value']    =   $this->_data_depot['cluecates'];
                $ids                        =   implode( ',', array_keys( $this->_data_depot['cluecates'] ) );
                $_mapping[$key]['_v']       =   str_replace('@LIST',$ids,$_mapping[$key]['_v'] ) ;
                continue;
            }
            // 是否是可选字段：是
            if( $handleFields[$fieldName]['form_type'] == 'box' ){
                // 当前可选字段的 选项列表
                $currentValue               =   eval("return {$handleFields[$fieldName]['setting']};");
                // 赋值主体 选项值
                $_mapping[$key]['value']    =   $currentValue['data'];
                // 赋值主体 验证值
                $_mapping[$key]['_v']       =   str_replace('@LIST',implode(',',$currentValue['data']),$value['_v'] ) ;
                // 赋值主体 表单类型 验证类型
                switch ($currentValue['type'])
                {
                    case 'select':
                        $_mapping[$key]['type']     =   2;
                        $type           =   'in';
                        break;
                    case 'radio':
                        $_mapping[$key]['type']     =   3;
                        $type           =   'in';
                        break;
                    case 'checkbox':
                        $_mapping[$key]['type']     =   4;
                        $type           =   'multi';
                        break;
                }
                $_mapping[$key]['_v']       =   str_replace('@TYPE',$type,$_mapping[$key]['_v'] ) ;
            }
            // 文本
            if( $handleFields[$fieldName]['form_type'] == 'text' ){
                $_mapping[$key]['type']     =   1;
            }
            if( $handleFields[$fieldName]['form_type'] == 'textarea' ){
                $_mapping[$key]['type']     =   5;
            }
        }

        return $_mapping;
    }

    /******* 写库 ********/
    /**
     * @ 写库
     * @param $create
     */
     protected function write ($create)
    {
        // 主表字段
        $data_fields        =   array_map( function($v){
            return $v['field'];
        }, M('Fields')->field('field')->where(['model'=>['eq','leads'],'is_main'=>['eq',0]])->select() );
        // TODO 特殊处理
        //      TODO { 所在城市 }
        //          所在城市key
        $city_key       =   $this->_map['_aliasMap']['XY_b13']['field'];
        //          手机号key
        $mobile_key     =   $this->_map['_aliasMap']['XY_a02']['field'];

        $create[$city_key]      =   ( array_key_exists($city_key, $create) && $create[$city_key] )
            ?   $create[$city_key]
            :   (Position::ByModile( $create[$mobile_key] )->province ?: '上海');

        //      TODO { 线索等级 }
        $level_key      =   $this->_map['_aliasMap']['XY_b24']['field'];
        $create[$level_key]     =   'S';
        //      TODO { 线索分类 }
        //          分类  key
        $cluecate_key   =   $this->_map['_aliasMap']['XY_a03']['field'];
        //          获取校区 id
        $block_id       =   $this->_getBlockId( $create[$city_key] );
        \Log::write('线索校区id'.$block_id);

        //          goto key
        $goto_key       =   $this->_map['_aliasMap']['XY_c01']['field'];
        $goto           =   explode( '-', array_key_exists( $goto_key, $create ) ? $create[$goto_key] : 'seo' )[0];
        //      TODO 切入注册
        if( $goto === 'register' ){
            $this->toRegister($create, $goto_key, $mobile_key);

            // 926 如果用户手机号码在线索中已经存在,无需再次进入线索 dragon
            $stu_mobile_in_leads_exists = M('leads')->where(['mobile'=>['eq',$create[$mobile_key]]])->find();

            if ($stu_mobile_in_leads_exists) return ;
            // 926 end
        }

        //          所属分类 id
        $create[$cluecate_key]  =   $this->_getCluecateId( $block_id,$goto );
        //          摧毁变量
        unset($create[$goto_key]);

        //      TODO { 来源 }废弃
        // $source_key     =   $this->_map['_aliasMap']['XY_b06']['field'];
        // $source         =   array_key_exists( $source_key, $create ) ? $create[$source_key]: 'seo' ;
        // $create[$source_key]    =   $this->_source[ $source ];

        //      TODO { 数据填充 }
        $create         =   $this->fullInfo($create,$block_id);

        // TODO 主副表字段
        $main_create        =   [];
        $data_create        =   [];

        foreach ($create as $key => $v)
        {
            // 多选字段值处理
            if( $this->_data_depot['_realMap'][$key]['type'] == 4 ){
                $v      =   implode(chr(10),$v);
            }

            if( !in_array( $key, $data_fields ) ){
                $main_create[$key]  =   $v;
            }else{
                $data_create[$key]  =   $v;
            }
        }
        \Log::write('主表写入数据'.json_encode($main_create));
        $model              =   M('Leads');
        // 主表写入
        $mainResult = $model->add( $main_create );
        // 附表写入
        \Log::write('附表表写入数据'.json_encode($data_create));
        if( $mainResult && $data_create ){
            $dataModel      =   M('LeadsData');
            $dataResult     =   $dataModel->add( array_merge(['leads_id'=>$mainResult],$data_create) );
        }
        return compact('mainResult', 'dataResult', 'create');
    }

    /******* 通知 ********/
    /**
     * @ 通知
     */
    protected function _notify_dispatch ($created)
    {
        // 邮件通知
        $mailResult     =   $this->_mail_notify($created);
        // 站内信
        $webResult      =   $this->_web_notify($created);
        return ['mailResult'=>$mailResult, 'webResult'=>$webResult];
    }

    protected function _mail_notify ($created)
    {
        import("@.ORG.Mail");
        $mail               =   new \PHPMailer(true);
        $title              =   "【线索分配】 {$created['contacts_name']}：{$created['mobile']}";
        $message            =   $this->_mail_tpl($created);
        $setting            =   M('Config')->field('value')->where(['name'=>['eq','smtp']])->find();
        $setting            =   unserialize( $setting['value'] );
        C($setting,'smtp');

        $mail->IsSMTP();
        $mail->CharSet=C('MAIL_CHARSET');
        if( is_array($this->_mailto) ){
            foreach ( $this->_mailto as $mailto ){
                if( $mailto ){
                    $mail->AddAddress($mailto);
                }
            }
        }else{
            $mail->AddAddress($this->_mailto);
        }
        $mail->Body=$message;
        $mail->From= C('MAIL_ADDRESS');
        $mail->FromName='CRM机器人';
        $mail->Subject=$title;
        $mail->Host=C('MAIL_SMTP');
        $mail->SMTPAuth=C('MAIL_AUTH');
        $mail->Port=C('MAIL_PORT');
        $mail->SMTPSecure= C('MAIL_SECURE');
        $mail->Username=C('MAIL_LOGINNAME');
        $mail->Password=C('MAIL_PASSWORD');
        $mail->IsHTML(true);
        $mail->MsgHTML($message);
        return($mail->Send());
    }

    protected function _mail_tpl ($created)
    {
        $trueMap            =   [];
        $trstr          =   '';
        foreach( $this->_map['_aliasMap'] as $key => $value ){
            $trueMap[$value['field']]  =   $value;
        }
        foreach( $created as $key => $value ){
            $key_name       =   $trueMap[$key]['name'];
            // 复选框  多值
            if( $trueMap[$key]['type'] == 4 && $value ){
                $value      =   implode('、', $value);
            }
            // 分类映射
            if( $key == 'cluecate' ){
                $value  =   $this->_data_depot['cluecates'][(int)$value]['name'];
            }
            if( $key_name )
                $trstr      .=  "<tr><td class='gray'>{$key_name}：</td><td colspan='5' >{$value}</td></tr>";
        }
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
           			 {$this->_mailtpl}
           		</td>
            </tr>
       </table>
	</body>
</html>
EOF;
        ;
        return $tpl;
    }

    protected function _web_notify ($created)
    {
        $message['to_role_id']              =   $created['owner_role_id'];
        $message['from_role_id']            =   $this->_robot;
        $message['content']                 =   '线索分配提醒：'.$created['contacts_name'];
        $message['send_time']               =   time();

        $model              =   M('Message');
        return $model->add( $message );
    }

    /********辅助数据提取********/
    /**
     * @ 注册切面
     * @param $create
     * @param $goto_key
     * @param $mobile_key
     * @throws Exception
     */
    protected function toRegister ($create, $goto_key, $mobile_key)
    {
        // 密码key
        $passwd_key         =   $this->_map['_aliasMap']['XY_c03']['field'];
        $name_key           =   $this->_map['_aliasMap']['XY_a01']['field'];
        $pre_key            =   $this->_map['_aliasMap']['XY_b35']['field'];
        $goto_value         =   $create[$goto_key];
        // 注册类型
        $goto_type          =   explode('-', $goto_value)[1];
        // TODO i.xiaoying.net 注册
        if( $goto_type === 'student_end' ){
            // 模型实例
            $studentModel           =   new StudentModelEdu();
            $studentprofileModel    =   new StudentprofileModelEdu();

            // 数据拼接
            $register['realname']   =   $create[$name_key];
            $register['mobile']     =   $create[$mobile_key];
            $register['pre']        =   $create[$pre_key];
            // 如果是國外手機,處理一下特殊字符 (區號)
            if (strpos($create[$mobile_key],'-')) $register['mobile']     =   substr($create[$mobile_key],strpos($create[$mobile_key],'-')+1);

            $register['password']   =   $create[$passwd_key];
            $register['customer_id']=   -time();
            $register['remark']     =   '初始化密码：'.$register['password'];
            $register['creator_id'] =   $this->_robot;
            // 判断是否存在
            if($studentModel->where(['mobile'=>['eq',$register['mobile']]])->find())
                throw new Exception('手机号已经注册！', 208);

            // 写库、注册
            //      开启事务
            $studentModel->startTrans();
            //          主表写入
            if( ($student_id=$studentModel->field('mobile,password')->add($register)) !== false ){
                //      附表写入
                $student_profile['student_id']          =   $student_id;
                $student_profile['nickname']            =   $register['realname'];
                $student_profile['bind_mobile']         =   $register['mobile'];
                if($studentprofileModel->add($student_profile)===false){
                    // 附表写入异常
                    $studentModel->rollback();
                    throw new Exception( $studentprofileModel->getDbError() );
                }
                //      写入成功
                $studentModel->commit();
                return ;
            }else{
                // 主表写入异常
                $studentModel->rollback();
                throw new Exception( $studentModel->getDbError(), 414 );
            }
        }
        return ;
    }

    /**
     * @ 获取线索分类
     * @return mixed
     */
    protected function _getBlockCategory()
    {
        // 线索分类信息
        $blockCateModel     =   M('LeadCategory');
        $cates              =   $blockCateModel
            ->field('mx_lead_category.id,mx_lead_category.name,b.name block,b.id block_id')
            ->join('LEFT JOIN mx_block b ON b.id = mx_lead_category.block_id')
            ->select();
        // id 数据集
        // 映射处理
        $realMap            =   [];
        foreach( $cates as $key => $value ){
            $realMap[$value['id']]      =   $value;
        }
        return $realMap;
    }

    /**
     * @ 数据库字段列表
     * @return mixed
     */
    protected function _getDBFields ()
    {
        $fields          =   M('Fields')->field('field,form_type,setting')
            ->where(['model'=>['eq','leads']])->select();

        return $fields;
    }

    /**
     * @ 获取校区 id
     * @param $city
     * @return int|string
     */
    protected function _getBlockId ( $city )
    {
        foreach( $this->_block as $key => $value ) {
            if( in_array( $city, $value ) ){
                return $key;
            }
        }
        return 3;
    }

    /**
     * @ 获取校区id (0605号修改)
     * @param $city
     */
    protected function _getBlockId0605( $city ){
        // 规则定义
        $_block     =   [
            // 上海
            1   =>  [['上海', '浙江'],3],
            // 苏州
            4   =>  [['江苏'], 3],
            // 成都
            3   =>  [['四川','重庆'], 4],
        ];

        // 范围分配
        foreach ( $_block as $key => $value ){
            if( in_array( $city, $value[0] ) ){
                return $key;
            }
        }
        //
        $random     =   rand(0,9);
        if( $random <= 2 )
            return 1;
//            return array_rand([3,4]);
        if( $random >=6 )
            return 3;
        return 4;
    }

    /**
     * @ 获取所属线索分类 id
     */
    protected function _getCluecateId ($block_id, $goto)
    {
        $model          =   M( 'LeadCategory' );
        $cateInfo       =   $model->field('id')->where( ['block_id'=>['eq', (int)$block_id], 'remark'=>['eq', $goto]] )->find();
        if( !$cateInfo )
            $cateInfo   =   $model->field('id')->where( ['block_id'=>['eq', (int)$block_id], 'remark'=>['eq', 'seo']] )->find();

        return (int)$cateInfo['id'];
    }

}
