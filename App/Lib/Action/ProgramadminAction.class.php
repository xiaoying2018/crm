<?php
class ProgramadminAction extends Action
{
    public function _initialize(){
        $action = array(
            'permission'=>array(),
            'allow'=>array('index','select_data')
        );
        B('Authenticate', $action);
        $this->_permissionRes = getPerByAction(MODULE_NAME,ACTION_NAME);
    }

    public function index ()
    {
        if ($this->isAjax()){
            $wheredata = $_REQUEST;
            $name = $wheredata['school_name'] ? $wheredata['school_name'] : '';// 查询关键字
            $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
            $limit = $wheredata[' pageSize'] ? $wheredata[' pageSize'] : 10;// 每页显示条数
            $start = ($page - 1) * $limit;// 查询起始值
            $condition = [];// 查询条件
            if ($name) $condition['school_name'] = ['LIKE','%'.$name.'%'];// 如果按关键字查询

            $model              =   new ProgramModel();
            $list = $model->field('id,school_name,name_jp,include_major,school_rank')
                ->where($condition)
                ->order('id DESC')
                ->limit($start,$limit)
                ->select();
            $count = $model->where($condition)->count();
            if ($list) // 按照前端所需格式拼接下载文件名称
            {
                foreach ($list as $k => $v)
                {
                    $list[$k]['school_name'] = $v['school_name'].'.'.trim(strrchr($v['file'], '.'),'.');
                }
            }
            $data['count'] = $count;// 总条数
            $data['total'] = ceil($count / $limit);// 总页数
            $data['list'] = $list;// 数据列表
            $this->ajaxReturn(['status'=>true,'data'=>$data]);
        }
        $this->display();
    }


    /*** program ***/
    public function program_add ()
    {
        $model                  =   new ProgramModel();
        if( IS_POST && IS_AJAX )
        {
            $params         =   I('post.');
            $params['area_id']  =   $params['city_id'] ? $params['city_id'] : $params['area_id'];
            if( $data = $model->create($params, 1) ){
                // 项目写入
                if( ( $pid=$model->add() ) != false ){
                    // term关联写入
                    $terms       =   $params['terms'];
                    if( $terms ){
                        $termModel      =   new PBaseModel('Term');
                        foreach( $terms as $t ){
                            $rl[]       =   ['p_id'=>$pid,'t_id'=>(int)$t];
                        }

                        $result = $termModel->addAll( $rl );
                    }
                    $this->ajaxReturn( ['status'=>true] );
                }
                $this->ajaxReturn( ['status'=>false,'message'=>$model->getError()] );
            }
            $this->ajaxReturn( ['status'=>false, 'message'=>$model->getError()] );
        }
        else
        {
            list( $null , $nature , $program_category, $major_category, $recommend_level, $sign_type, $term ) = $model->getTags();
            $this->assign([
                'is_edit'           =>  false,
                'nature'            =>  $nature,
                'program_category'  =>  $program_category,
                'major_category'    =>  $major_category,
                'recommend_level'   =>  $recommend_level,
                'sign_type'         =>  $sign_type,
                'term'              =>  $term,
                'area'              =>  (new PBaseModel('Area'))->field('id,name')->where('pid=0')->select(),
            ]);
            $this->display();
        }
    }

    public function program_edit ()
    {
        $model                  =   new ProgramModel();

        if( IS_POST && IS_AJAX )
        {
            $params         =   I('post.');
            $params['area_id']  =   $params['city_id'] ? $params['city_id'] : $params['area_id'];
            if( ( $data = $model->create($params, 2) ) && ($model->save()!==false) ){
                // term关联写入
                $terms       =   $params['terms'];
                $pid        =   (int)$params['id'];
                $termModel  =   new PBaseModel('Term');
                // 删除原有关联
                $termModel->where( ['p_id'=>['eq',$pid]] )->delete();
                if( $terms ){
                    foreach( $terms as $k ){
                        $rl[]       =   ['p_id'=>$pid,'t_id'=>(int)$k];
                    }

                    $result = $termModel->addAll( $rl );
                }
                $this->ajaxReturn( ['status'=>true, 'message'=>''] );
            }
            $this->ajaxReturn( ['status'=>false, 'message'=>$model->getError()] );
        }
        else
        {
            $id             =   I('get.id');
            // 关键词
            list( $null , $nature , $program_category, $major_category, $recommend_level, $sign_type, $term ) = $model->getTags();
            $this->assign([
                'nature'            =>  $nature,
                'program_category'  =>  $program_category,
                'major_category'    =>  $major_category,
                'recommend_level'   =>  $recommend_level,
                'sign_type'         =>  $sign_type,
                'term'              =>  $term,
                'area'              =>  (new PBaseModel('Area'))->field('id,name')->where('pid=0')->select(),
            ]);
            $termral        =   ( new PBaseModel('Term') )->field('t_id')->where('p_id='.$id)->select();
            $termIds        =   array_column($termral,'t_id');
            $this->assign( 'is_edit', true );
            $this->assign('info',$model->find((int)$id));
            $this->assign('termIds',$termIds);
            $this->display('program_add');
        }
    }

    public function program_del ()
    {
        if( IS_AJAX && IS_POST ){
            $id         =   I('post.id');
            !$id && $this->ajaxReturn( ['status'=>false, 'message'=>'主键缺失'] );
            $model      =   new ProgramModel();
            $result     =   true;
            // 开启事务
            $model->startTrans();
            try{
                // 删除主表信息
                $model->where(['id'=>['eq',(int)$id]])->delete();
                // 删除关键词关联
                ( new PBaseModel('KeyRelation') )->where( ['p_id'=>['eq',$id]] )->delete();

            }catch (Exception $e){
                $result =   false;
            }

            if( $result ){
                $model->commit();
                $this->ajaxReturn( ['status'=>true, 'message'=>''] );
            }else{
                $model->rollback();
                $this->ajaxReturn( ['status'=>false, 'message'=>$model->getError()] );
            }
        }
        $this->ajaxReturn( ['status'=>false, 'message'=>'非法操作'] );
    }

    public function select_data()
    {
        $model                  =   new ProgramModel();
        // 关键词
        list( $null , $nature , $program_category, $major_category, $recommend_level, $sign_type, $term ) = $model->getTags();
        $this->ajaxReturn([
            'nature'            =>  $nature,
            'program_category'  =>  $program_category,
            'major_category'    =>  $major_category,
            'recommend_level'   =>  $recommend_level,
            'sign_type'         =>  $sign_type,
            'term'              =>  $term,
            'area'              =>  (new PBaseModel('Area'))->field('id,name')->where('pid=0')->select(),
        ]);
    }

    /*** major ***/
    public function major_index ()
    {
        // TODO 数据接收、验证、项目信息提取、专业列表提取
        // 筛选条件
        $where                  =   [];
        // 当前项目信息
        $programInfo            =   [];
        $programModel           =   new ProgramModel();
        if( ($pid=I('get.pid',false)) ){
            $where['p.id']      =   ['eq',(int)$pid];
            $programInfo            =   $programModel->find($pid);
        }
        // 专业列表
        $majorList              =   $programModel->majorList($where);

        $this->assign([
            'programInfo'       =>  $programInfo,
            'dataList'          =>  $majorList,
        ]);
        $this->display();
    }

    public function major_add ()
    {
        $programModel               =   new ProgramModel();
        if( IS_POST && IS_AJAX )
        {
            $params                 =   I( 'post.' );
            $majorModel             =   new PBaseModel('Major');
            $majorRealtionModel     =   new PBaseModel('MajorRelation');
            if( $majorModel->create($params,1) ){
                if( ($mid=$majorModel->add()) != false ){
                    // 专业、项目关联
                    $pids           =   I('post.pid',[]);
                    $relations      =   [];
                    if( $pids ){
                        $pids       =   array_unique( array_filter($pids) );
                        foreach ($pids as $pid){
                            $relations[]        =   ['p_id'=>(int)$pid,'m_id'=>$mid];
                        }
                        $majorRealtionModel->addAll($relations);
                    }
                    $this->ajaxReturn( ['status'=>true,'message'=>''] );
                }
                $this->ajaxReturn( ['status'=>false,'message'=>$majorModel->getError()] );
            }
            $this->ajaxReturn( ['status'=>false,'message'=>$majorModel->getError()] );
        }
        else
        {
            $programs               =   $programModel->field('id,name_cn')->select();
            $this->assign([
                'programs'          =>  $programs,
                'is_edit'           =>  false,
            ]);
            $this->display();
        }
    }

    public function major_edit ()
    {
        $programModel               =   new ProgramModel();
        $majorModel                 =   new PBaseModel('Major');
        $majorRealtionModel         =   new PBaseModel('MajorRelation');
        if ( IS_AJAX && IS_POST )
        {
            $params                 =   I('post.');
            if( $majorModel->create($params,2) )
            {
                if( $majorModel->save() != false )
                {
                    $id             =   I('post.id');
                    // 删除原有关联
                    $majorRealtionModel->where('m_id='.$id)->delete();
                    //
                    $pids           =   array_unique( array_filter(I('post.pid')) );
                    $relations      =   [];
                    if( $pids ){
                        foreach ($pids as $pid){
                            $relations[]        =   ['p_id'=>(int)$pid,'m_id'=>$id];
                        }
                        $majorRealtionModel->addAll($relations);
                    }
                    $this->ajaxReturn(['status'=>true,'message']);
                }
                $this->ajaxReturn( ['status'=>false, 'message'=>$majorModel->getError()] );
            }
            $this->ajaxReturn( ['status'=>false, 'message'=>$majorModel->getError()] );
        }
        else
        {
            $id                     =   I('get.id');
            $programs               =   $programModel->field('id,name_cn')->select();
            $info                   =   $majorModel->find($id);
            $relations              =   $majorRealtionModel->field('p_id')->where('m_id='.$id)->select();
            $relIds                 =   array_map( function($v){ return $v['p_id']; }, $relations );
            $this->assign([
                'programs'          =>  $programs,
                'is_edit'           =>  true,
                'info'              =>  $info,
                'relIds'            =>  $relIds,
            ]);
            $this->display('major_add');
        }
    }

    public function major_del ()
    {
        if( IS_AJAX && IS_POST ){
            $id         =   I('post.id');
            !$id && $this->ajaxReturn( ['status'=>false, 'message'=>'主键缺失'] );
            $model      =   new PBaseModel('Major');
            $result     =   true;
            // 开启事务
            $model->startTrans();
            try{
                // 删除主表信息
                $model->where(['id'=>['eq',(int)$id]])->delete();
                // 删除关键词关联
                ( new PBaseModel('MajorRelation') )->where( ['m_id'=>['eq',$id]] )->delete();

            }catch (Exception $e){
                $result =   false;
            }

            if( $result ){
                $model->commit();
                $this->ajaxReturn( ['status'=>true, 'message'=>''] );
            }else{
                $model->rollback();
                $this->ajaxReturn( ['status'=>false, 'message'=>$model->getError()] );
            }
        }
        $this->ajaxReturn( ['status'=>false, 'message'=>'非法操作'] );

    }


    /*** case ***/
    public function case_index ()
    {
        // TODO 数据接收、验证、项目信息提取、专业列表提取
        // 筛选条件
        $where                  =   [];
        // 当前项目信息
        $majorInfo              =   [];
        $majorModel             =   new PBaseModel('Major');
        $caseModel              =   new PBaseModel('Case');
        if( ($mid=I('get.mid',false)) ){
            $where['mid']       =   ['eq',(int)$mid];
            $majorInfo          =   $majorModel->find($mid);
        }
        $caseList               =   $caseModel->where($where)->select();

        $this->assign([
            'majorInfo'        =>  $majorInfo,
            'dataList'          =>  $caseList,
        ]);
        $this->display();
    }

    public function case_add ()
    {

    }

    public function case_edit (){}

    public function case_del (){}

}
