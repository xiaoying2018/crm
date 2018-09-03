<?php
class NewLeadsAction extends ExtensionAction
{
    /**
     *用于判断权限
     *@permission 无限制
     *@allow 登录用户可访问
     *@other 其他根据系统设置
     **/
    public function _initialize()
    {
        $action = [
            'permission'        =>  [],
            'allow'             =>  ['search'],
        ];
        B('Authenticate', $action);
        $this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME, true);
    }


    // 获取指定的数据
    // public function getGivenLeads($c_ondition,$_code)
    // {

    //     $conditions = $this->conditionsHandle($c_ondition);
    //     $conditions['jg_code'] = ['eq',$_code];
    //     unset($conditions['owner_role_id']);

    //     // 获取指定数据
    //     $list = M('Leads')->where($conditions)->order('leads_id desc')->select();

    //     // 获取字段信息
    //     $res = $this->resultsHandle($c_ondition['by'],$list,$c_ondition);

    //     $this->ajaxReturn( [
    //         'params'        =>  $c_ondition,
    //         'conditions'    =>  $c_ondition,
    //         'fields'        =>  $res['fields'],
    //         'count'         =>  count($list),
    //         'lists'         =>  $list,
    //         'status'        =>  true,
    //     ] );
    // }
    private function createorder($orderby){
        switch ($orderby){
            case 1 :return 'DESC';break;
            case 2:return 'ASC';break;
            default: return 'DESC';break;
        }
    }

    public function search ()
    {
        // 参数接收、处理
        $params                     =   $this->paramsHandle( I('get.') );
        $ordercreate = I('get.create_time');
        $orderupdate = I('get.update_time');
        $ordersby = I('get.ordersby');
        session_start();
        cookie('create_time',null);
        cookie('update_time',null);
        cookie('ordersby',null);

        cookie('create_time',$ordercreate,'3600');
        cookie('update_time',$orderupdate,'3600');
        cookie('ordersby',$ordersby,'3600');

//        $this->ajaxReturn($_COOKIE);
         if($orderupdate==1){
            $params['order'] = 'update_time' .' '. $this->createorder($ordersby);
        }else if($ordercreate==1){
        ##创建时间排序
        $params['order'] = 'create_time' .' '. $this->createorder($ordersby);
         }
        if($ordersby==3){
            unset($params['order']);
        }
        $paramss                     =    I('get.super_search') ;
        $new = array_diff($paramss,$params);
        // // 如果是XX合作机构 获取指定的数据
        //       if (session('role_id') == 81) return $this->getGivenLeads($params,'91be12b72fsh');// 李洋

        //       if (session('role_id') == 95) return $this->getGivenLeads($params,'ec74f72eb0sh');// 日本村

        // 下属ids
        $params['subIds']           =   array_map('intval',getPerByAction('leads','index', false));
        // model
        $model                      =   D('LeadsView');
        // config model
        $configModel                =   M('Config');
        // 超时时间
        $params['outDay']           =   (int)$configModel->field('value')
            ->where('name="leads_outdays"')->getField('value');
        // 超时时间戳
        $params['outStamp']         =   $params['outDay'] ? time()-$params['outDay']*24*60*60 : 0;
        // 查询条件,排序
        $orderby                    =   $params['order'];
        $conditions                 =   $this->conditionsHandle($params,$new);
//        $this->ajaxReturn($conditions);
        // 数据量
        $count                      =   $model->where($conditions)->count();
        // 偏移,数据
        $pagesize                   =   30;
        $offset                     =   ($params['page']-1)*$pagesize;

        // 如果是XX合作机构 获取指定的数据
        if (session('role_id') == 81)// 李洋
        {
            unset($conditions['owner_role_id']);
            $conditions['jg_code'] = ['eq','91be12b72fsh'];
        }

        if (session('role_id') == 95)// 日本村
        {
            unset($conditions['owner_role_id']);
            $conditions['jg_code'] = ['eq','ec74f72eb0sh'];
        }

        $lists                      =   $model->where($conditions)->order($orderby)->limit($offset, $pagesize)->select();

        if ($lists)
        {
            foreach ($lists as $k => $v) {
                if ($v['merge']) $lists[$k]['merge_name'] = M('User')->where(['role_id'=>['eq',$v['merge']]])->find()['full_name'];
            }
        }
        $results                    =   $this->resultsHandle($params['by'],$lists,$params);

        // 校区处理
        $blocks                     =   $this->getBlockLists();
        // 下属列表信息
        $subLists                   =   M('User')->field('role_id, full_name')
            ->where(['role_id'=>['in',implode( ',', $params['subIds'] )]])->order('role_id desc')->select();

        QueueEcho( [
            'con'=>$conditions,
            'params'        =>  $params,
            'conditions'    =>  $conditions,
            'count'         =>  $count,
            'blockLists'    =>  $blocks,
            'subLists'      =>  $subLists,
            'fields'        =>  $results['fields'],
            'lists'         =>  $results['lists'],
//            'lists'         =>  $lists,
            'status'        =>  true,
            '_sql'          =>  $model->getLastSql(),
            'block'         =>  session('?admin') ? ( $params['block'] ?: 1 ) : (int)session('block'),
            '_isadmin'      =>  session('?admin') || session('block') == -7,
        ], function(){
            // 线索超时处理--放入线索池
            $this->leadsTimeout();
        } );
    }

    /**
     * @ 参数处理
     */
    protected function paramsHandle ($params)
    {
        // 参数去除前后空格
        $params                 =   array_map( 'trim', $params );
        // 请求类型
        $handled['by']          =   array_key_exists( 'by', $params ) && $params['by']
            ? $params['by'] : 'all';
        // 按照线索负责人查找
        $handled['sub']         =   false;
        if ( ($handled['by'] == 'all' || $handled['by'] == 'sub') && $params['sub'] && in_array( $params['sub'], $this->_permissionRes ) ){
            $handled['sub']         =   (int)$params['sub'];
        }
        // 当前页数
        $handled['page']        =   array_key_exists( 'page', $params )
            ? (int)$params['page'] > 0 ? (int)$params['page'] : 1
            : 1;
        // 校区id
        $handled['block']       =   array_key_exists( 'block', $params ) && $params['block']
            ? (int)$params['block'] : false;
        // 线索分类
        $handled['cluecate']    =   array_key_exists( 'cluecate', $params ) && $params['cluecate']
            ? $params['cluecate'] : false;
        // 模糊搜索
        $handled['search']      =   array_key_exists( 'search', $params ) && $params['search'] ? $params['search'] : false;
        // 排序、
        $handled['order']       =   array_key_exists( 'order', $params ) && $params['order']
            ? str_replace('-',' ', $params['order'])
            : "create_time DESC";

//        $handled['create_time']       =   array_key_exists( 'create_time', $params ) && $params['create_time'] && $params['create_time']==1
//            ? str_replace('-',' ', $params['create_time'])
//            : "create_time ASC";
        return $handled;
    }

    /**
     * @ 线索跟进超时处理
     * @param $model
     */
    public function leadsTimeout ()
    {
        // TODO 线索超时提醒 只提醒当前数据
        $dayZero                    =   strtotime(date('Y-m-d',time()));
        $model                      =   M('Leads');
        $rmodel                     =   M('RLeadsLog');
        // 限制当天 一小时为限
        $where['create_time']       =   ['BETWEEN ',[$dayZero,time()-3600]];
        // 未提醒
        $where['is_notify']         =   ['eq','0'];
//        $subSql                     =   M('RLeadsLog')->alias('rll')->field('count(*)')->where('rll.leads_id=l.leads_id')->buildSql();
        // 查出今天至今为止未提醒数据
        $data                       =   $model->alias('l')->field("leads_id,contacts_name,owner_role_id")
            ->where($where)
            ->select();
        if( !$data )    return ;
        // 查出今天至今为止未提醒数据id集
        $dataIds                    =   array_map(function($v){
            return $v['leads_id'];
        },$data);
        //当前数据的跟进值
        $logs                  =   $rmodel->field('leads_id')->where(['leads_id'=>['in',$dataIds]])->group('leads_id')->select();
        // 获取已经跟进数据的线索id
        $logYetIds             =   array_map( function ($v){
            return $v['leads_id'];
        }, $logs );
        // 未跟进的ids
        $logNotYetIds           =   array_filter( $dataIds,function ($v) use($logYetIds){
            return !in_array($v, $logYetIds);
        });

        // 站内信
        $this->_web_notify($data, $logNotYetIds);
        // 改变状态
        $model->where(['leads_id'=>['in',$dataIds]])->save(['is_notify'=>1]);
    }

    protected function _web_notify ($data,$logNotYetIds)
    {
        $created            =   [];
        $time               =   time();
        foreach ($data as $value){
            if( !in_array($value['leads_id'], $logNotYetIds) )
                continue;
            $item['to_role_id']             =   $value['owner_role_id'];
            $item['from_role_id']           =   62;
            $item['content']                =   '线索超时提醒：'.$value['contacts_name'];
            $item['send_time']              =   $time;
            $created[]      =   $item;
        }

        $model              =   M('Message');
        $r = $model->addAll( $created );
        return $r;
    }

    /**
     * @ 查询条件处理
     * @param $params
     * @return array
     */
    protected function conditionsHandle ($params,$new=[])
    {
        $where          =   [];
        //
        if( $params['by'] != 'public' ){
            $where['owner_role_id']      =   ['gt',0];
        }
        // 请求类型
        switch ($params['by'])
        {
            case 'today' :
                $where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-d', time()))+86400), array('gt',0), 'and');
                $where['_string'] = "`owner_role_id` = ".session('role_id')." OR `merge`=".session('role_id')." ";
//                $where['owner_role_id'] = session('role_id');
                break;
            case 'week' :
                $w = date("w", time()); //获取当前周的第几天 周日是 0 周一 到周六是 1 -6
                $d = $w ? $w - 1 : 6; //如果是周日 -6天
                $start_week = strtotime("".date("Y-m-d")." -".$d." days"); //本周开始时间
                $end_week = strtotime("".date("Y-m-d",$start_week)." +7 days"); //本周结束时间

                $where['nextstep_time'] = array(array('gt',$start_week), array('lt', $end_week),'and');
                break;
            case 'month' :
                $where['nextstep_time'] =  array(array('lt',strtotime(date('Y-m-01', strtotime('+1 month')))), array('gt', strtotime(date('Y-m-01'))),'and');
                break;
            case 'd7' :
                $where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*6);
                break;
            case 'd15' :
                $where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*14);
                break;
            case 'd30' :
                $where['update_time'] =  array('lt',strtotime(date('Y-m-d', time()))-86400*29);
                break;
            case 'add' : $order = 'create_time desc';  break;
            case 'update' : $order = 'update_time desc';  break;
            case 'sub' :
                $where['_string'] = "`owner_role_id` IN (".implode(',', $params['subIds']).") OR `merge`=".session('role_id')." ";
//                $where['owner_role_id'] = array('in',implode(',', $params['subIds']));
                break;
            case 'subcreate' : $where['creator_role_id'] = array('in',implode(',', $params['subIds'])); break;
            case 'public' :
                unset($where['have_time']);
                // $where['_string'] = "leads.owner_role_id=0 or leads.have_time < {$params['outStamp']}";
                $where['_string'] = "leads.owner_role_id=0";
                break;
            case 'deleted': $where['is_deleted'] = 1;unset($where['have_time']); break;
            case 'transformed' :
                $where['is_transformed'] = ['eq',1];
                $allIds     =   array_merge( $params['subIds'], [session('role_id')] );
//                $where['owner_role_id'] = array('in',implode(',', $allIds ));
                $where['_string'] = "`owner_role_id` IN (".implode(',', $allIds ).") OR `merge`=".session('role_id')." ";
                break;
            case 'notransformed' :
                $leads_ids = M('rLeadsLog')->field('leads_id')->group('leads_id')->getField('leads_id',true);
                $where['leads_id'] = ['not in',implode(',',$leads_ids)];// 有跟进日志的线索
                $where['is_transformed'] = ['eq',0];
                $allIds     =   array_merge( $params['subIds'], [session('role_id')] );
//                $where['owner_role_id'] = array('in',implode(',', $allIds ));
                $where['_string'] = "`owner_role_id` IN (".implode(',', $allIds ).") OR `merge`=".session('role_id')." ";
                break;
            case 'intransformed' :
                $leads_ids = M('rLeadsLog')->field('leads_id')->group('leads_id')->getField('leads_id',true);
                $where['leads_id'] = ['in',implode(',',$leads_ids)];// 有跟进日志的线索
                $where['is_transformed'] = ['eq',0];// 同时必须是未转换
                $allIds     =   array_merge( $params['subIds'], [session('role_id')] );
//                $where['owner_role_id'] = array('in',implode(',', $allIds ));
                $where['_string'] = "`owner_role_id` IN (".implode(',', $allIds ).") OR `merge`=".session('role_id')." ";
                break;
            case 'me' :
//                $where['owner_role_id'] = session('role_id');
                $where['_string'] = "`owner_role_id` = ".session('role_id')." OR `merge`=".session('role_id')." ";
                break;
            case 'all' :
                $allIds     =   array_merge( $params['subIds'], [session('role_id')] );
                if( (session('role_id') == 70) || session('?admin') ){

                }else{
                    // 7-02 所有 owner_role_id 的条件都改成如下格式,兼容联合跟进人功能 6-28
                    $where['_string'] = "`owner_role_id` IN (".implode(',', $allIds ).") OR `merge`=".session('role_id')." ";
//                    $where['owner_role_id'] = array('in',implode(',', $allIds ));
                }
                break;
            default :
//                $where['owner_role_id'] = array('in',implode(',', $params['subIds']));
                $where['_string'] = "`owner_role_id` IN (".implode(',', $params['subIds']).") OR `merge`=".session('role_id')." ";
                break;
        }
        // 线索分类
        if( $params['cluecate'] ){
            $where['cluecate']              =   ['eq', $params['cluecate']];
        }
        // 指定线索负责人
        if( $params['sub'] ){
            $where['owner_role_id']     =   ['eq', $params['sub']];
        }

        // search
        if($params['search']!=''){
            $userwhere['full_name'] = ['like', ['%' . $params['search'] . '%']];
            $useridss = M('user')->where($userwhere)->getField('user_id',true);
            if(!empty($useridss)){
                $where['creator_role_id']      =   [ 'in', $useridss ];
            }else{
                if( $search = $params['search'] ){
                    $where['mobile|name|contacts_name|crm_qq|crm_city|crm_url']      =   [ 'like', '%'.$search.'%' ];
                }
            }
        }

        // 条件补充

        if( $params['by'] != 'deleted' ) {
            $where['is_deleted']            =   ['neq',1];
        }elseif( $params['by'] != 'transformed' ){
            $where['is_transformed']        =   ['neq',1];
        }
        // 校区下用户 定向显示
        if( ($params['by'] == 'public') && !session('?admin') ){
            $blockInfo              =   M('Block')->field('name')->find( (int)session('block') );
            $cityname               =   str_replace( '校', '', $blockInfo['name'] );
            $where['crm_city']      =   ['like', "%$cityname%"];
        }
        // 校区搜索 0525 (有歧)
        if( ($params['block'] !== false) && ($params['block'] > 0) && (!array_key_exists( 'cluecate', $where )) ){
            //      获取校区下所有线索分类
            $subCateItems               =   M('LeadCategory')->field('id')
                ->where( ['block_id'=>['eq',(int)$params['block']]] )->select();
            //      校区下所有线索id
            $subCateIds                 =   array_map ( function($v){
                return (int)$v['id'];
            }, $subCateItems );
            $where['cluecate']          =   [ 'in', implode( ',', $subCateIds ) ];
        }

        ##高级搜索
        if(!empty($new)){
            foreach($new as $k=>$v){
                if(count($v)==1){
                    $where[$k] = $v['value'];
                }else if(count($v)==2  && array_key_exists('condition',$v)){
                    $where[$k] = $this->returnCondition($v);
                }else if(count($v)==2  && array_key_exists('start',$v)  &&  array_key_exists('end',$v)){
                    $where[$k] = ['between',[strtotime($v['start']),strtotime($v['end'])]];
                }
            }
        }
        return $where;
    }

    /**
     * @ 查询结果处理
     * @param $by
     * @param $lists
     * @param $params
     * @return array
     */
    protected function resultsHandle ($by,$lists,$params)
    {
        // TODO 超时时间（小时）
        $expire         =   12;
        $lists          =   $lists ?: [];
        // 字段信息
        $fields         =   getIndexFields('leads');
        // 列显示处理
        if( $by == 'public' ){
            $public_only        =   ['nextstep_time','now_university','now_major','expect_major'];
            // 去除列显示
            $fields      =   array_filter( $fields, function($v) use($public_only){
                return (!in_array( $v['field'], $public_only ));
            } );
            // 去除集合值、线索分类映射
            $cateMap        =   [];
            $cluecate       =   M('LeadCategory')->field('id,name')->select();
            foreach ($cluecate as $value){
                $cateMap[$value['id']]  =   $value;
            }

            foreach( $lists as $key => $value ){
                foreach( $public_only as $only ){
                    unset( $lists[$key][$only] );
                }

                if( array_key_exists( (int)$value['cluecate'], $cateMap ) ){
                    $lists[$key]['cluecate']    =   $cateMap[(int)$value['cluecate']]['name'];
                }else{
                    $lists[$key]['cluecate']    =   '无';
                }
            }
        }else{
            $self_only          =   ['cluecate','market_activity'];
            $fields      =   array_filter( $fields, function($v) use($self_only){
                return (!in_array( $v['field'], $self_only ));
            } );
            // 去除集合值
            foreach( $lists as $key => $value ){
                foreach( $self_only as $only ){
                    unset( $lists[$key][$only] );
                }

                // 线索状态
                $crm_status     =   -1;          //  待跟进
                if( $value['is_deleted'] == 1 ){
                    $crm_status     =   -7;     //  已作废
                }elseif( $value['is_transformed'] == 1 ){
                    $crm_status     =   9;       //  已转化
                }elseif( M('rLeadsLog')->where( ['leads_id'=>['eq',$value['leads_id']]] )->count() ){
                    $crm_status     =   6;       // 跟进中
                }elseif( $value['create_time']+($expire*60*60) < time() ){
                    $crm_status     =   4;      // 超时
                }else{
                    $crm_status     =   -1;      // 待跟进
                }

                $lists[$key]['crm_status']    =     $crm_status;
            }
        }
        // 数据关联处理
        if($by == 'deleted') {
            foreach ($lists as $k => $v) {
                $lists[$k]["delete_role"] = getUserByRoleId($v['delete_role_id']);
                $lists[$k]["owner"] = getUserByRoleId($v['owner_role_id']);
                $lists[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
            }
        }elseif($by == 'transformed'){
            $m_business = M('Business');
            $m_contacts = M('Contacts');
            $m_customer = M('Customer');
            foreach ($lists as $k => $v) {
                $lists[$k]["owner"] = getUserByRoleId($v['owner_role_id']);
                $lists[$k]["creator"] = getUserByRoleId($v['creator_role_id']);
                $lists[$k]["transform_role"] = getUserByRoleId($v['transform_role_id']);
                $lists[$k]["business_name"] = $m_business->where('business_id = %d', $v['business_id'])->getField('name');
                $lists[$k]["contacts_name"] = $m_contacts->where('contacts_id = %d', $v['contacts_id'])->getField('name');
                $lists[$k]["customer_name"] = $m_customer->where('customer_id = %d', $v['customer_id'])->getField('name');
            }
        }else{
            $m_remind = M('Remind');
            foreach ($lists as $k => $v) {
                $days = 0;
                //提醒
                $remind_info = array();
                $remind_info = $m_remind->where(array('module'=>'leads','module_id'=>$v['leads_id'],'create_role_id'=>session('role_id'),'is_remind'=>array('neq',1)))->order('remind_id desc')->find();
                $lists[$k]['remind_time'] = !empty($remind_info) ? $remind_info['remind_time'] : '';
                $lists[$k]["owner"] = D('RoleView')->where('role.role_id = %d', $v['owner_role_id'])->find();
                $lists[$k]["creator"] = D('RoleView')->where('role.role_id = %d', $v['creator_role_id'])->find();
                $days = M('leads')->where('leads_id = %d', $v['leads_id'])->getField('have_time');
                $lists[$k]["days"] = $params['outDay']-floor((time()-$days)/86400);
                $lists[$k]["create_time"] = (preg_match('/^1[0-9]{9}$/',$v['create_time']))
                    ?   $v['create_time']
                    :   strtotime( $v['create_time'] );

            }
        }

        return ['fields'=>$fields,'lists'=>$lists];
    }

    /**
     * @校区列表
     * @return array
     */
    protected function getBlockLists  ()
    {
        $results    =  M('Block')->field('id,name')->select();
        $handled    =   [];
        foreach( $results as $key => $value ) {
            $handled[$value['id']]  =   $value;
        }

        return $handled;
    }

    public function returnCondition($v)
    {
//	    $a = I('get.aa');
//        if ($v['type'] == 1) {
        switch ($v['condition']) {
            ##包含
            case 'contains':
                return ['like', ['%' . $v['value'] . '%']];
                break;
            ##不包含
            case 'not_contain':
                return ['notlike', ['%' . $v['value'] . '%']];
                break;
            case 'is':
                return ['eq', $v['value']];
                break;
            case 'isnot':
                return ['neq', $v['value']];
                break;
            case 'start_with':
                return ['like', [$v['value'] . '%']];
                break;
            case 'end_with':
                return ['like', ['%' . $v['value']]];
                break;
            case 'is_empty':
                return ['eq', ''];
                break;
            case 'is_not_empty':
                return ['neq', ''];
                break;
            default:
                return false;
                break;
        }
//        }
//        else if ($v['type'] == 2) {
//            return ['eq', $v['value']];
//        } else if ($v['type'] == 3) {
//            return ['between', [$v['value'][0], $v['value'][1]]];
//        }
    }

}
