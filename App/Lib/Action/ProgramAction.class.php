<?php

class ProgramAction extends Action
{
    protected $rank             =   array( 1=>'1-10', 2=>'11-20', 3=>'21-30' );

    /**
     * @列表页显示
     */
    public function index ()
    {
        //
        $tags               =   $this->getTags();
        //
        list( $null, $nature, $program_category, $major_category, $level, $mail, $terms  )   =   $tags;
        // data
        $conditions         =   [];
        if( $params = I('get.') ){
            $conditions =   $this->parseParams($params);
        }
        $data       =   $this->getProgramData($conditions);

        $this->assign( [
            'nature'                =>  $nature,
            'program_category'      =>  $program_category,
            'major_category'        =>  $major_category,
            'rank'                  =>  $this->rank,
            'area'                  =>  $this->getArea(),
            'level'                 =>  $level,
            'mail'                  =>  $mail,
            'term'                 =>  $terms,
        ] );

        $this->display();
    }

    public function search ()
    {
        if( IS_AJAX && IS_POST )
        {
            // 参数接收
            $params             =   I('post.');
            // 查询条件解析
            $conditions         =   $this->parseParams( $params );
            // 数据查询
            $conditions['limit'][] = ($params['page']-1)*$params['rows'];
            $conditions['limit'][] = $params['rows'];
            $collect            =   $this->getProgramData( $conditions );

            $this->ajaxReturn( [
                'status'            =>  true,
                'params'            =>  $params,
                'conditions'        =>  $conditions,
                'data'              =>  $collect['data'],
                'count'             =>  $collect['count'],
                'sql'               =>  $collect['sql'],
                'total'             =>  ceil(intval($collect['count'])/$params['rows']),
                'page'              =>  $params['page']
            ] );
        }
        $this->ajaxReturn( ['status'=>false, '非法操作'] );
    }

    public function showItem ()
    {
        // 参数接收
        $id         =   I('get.id');
        $model      =   new ProgramModel();
        $info       =   $model->find($id);

        // 新增推荐等级字段查询   6-12
        $level = ( new PBaseModel('Level') )->field('l_id')->where('p_id='.$info['id'])->find()['l_id'];
        if ($level){
            $info['level'] = ( new PBaseModel('Tags') )->find($level)['name'];
        }else{
            $info['level'] = '';
        }

        $terms      =   ( new PBaseModel('Term') )->where('p_id='.$info['id'])->select();
        //
        $allTags    =   $model->getTags();
        list( $null , $nature , $program_category, $major_category, $recommend_level, $sign_type, $term ) = $allTags;
        // 性质转换
        $info['nature_id']          =   $nature[$info['nature_id']]['name'];
        // 项目划分转换
        $info['program_category']   =   $program_category[$info['program_category']]['name'];
        // 专业分类转换
        $major_c_ids                =   explode( ',', $info['major_category'] );
        $major_c_names              =   [];
        if( is_array($major_c_ids) ){
            $major_c_names              =   array_map( function($id) use($major_category){
                return $major_category[$id]['name'];
            }, $major_c_ids );
        }
        $info['major_category']     =   implode(',', $major_c_names);
        // $info['major_category']     =   $major_category[$info['major_category']]['name'];
        // 推荐等级
        $info['recommend_level']    =   $recommend_level[$info['recommend_level']]['name'];
        // 报名方式转换
        $info['sign_type']          =   $sign_type[$info['sign_type']]['name'];
        // 是否转换
        $info['specialty_enable']   =   $info['specialty_enable'] ? '是' : '否';
        $info['whether_abroad']     =   $info['whether_abroad'] ? '是' : '否';
        $info['is_aoo_check']       =   $info['is_aoo_check'] ? '是' : '否';
        
        // 学期信息转换
        $termed                     =   array_map( function($v) use($term){
             return $term[$v['t_id']]['name'];
        }, $terms );
        $info['term']               =   implode('/', $termed).'期';

        $this->ajaxReturn(['status'=>true, 'info'=>$info ,'termData'=>array_map(function($v){ return $v['t_id']; }, $terms) ]);
    }

    public function excelimport()
    {
        if($this->isPost())
        {
            if (isset($_FILES['excel']['size']) && $_FILES['excel']['size'] != null)
            {
                import('@.ORG.UploadFile');
                $upload = new UploadFile();
                $upload->maxSize = 20000000;
                $upload->allowExts  = array('xls','xlsx');
                $dirname = UPLOAD_PATH . date('Ym', time()).'/'.date('d', time()).'/';
                if (!is_dir($dirname) && !mkdir($dirname, 0777, true)) {
                    alert('error', L('ATTACHMENTS_TO_UPLOAD_DIRECTORY_CANNOT_WRITE'), $_SERVER['HTTP_REFERER']);
                }
                $upload->savePath = $dirname;
                if(!$upload->upload()) {
                    alert('error', $upload->getErrorMsg(), $_SERVER['HTTP_REFERER']);
                }else{
                    $info =  $upload->getUploadFileInfo();
                }
            }
            if(is_array($info[0]) && !empty($info[0])){
                $savepath = $dirname . $info[0]['savename'];
                if($savepath){
                    $this->ajaxReturn($savepath,'success',1);
                }else{
                    $this->ajaxReturn(0,'error',0);
                }
            }else{
                alert('error', L('UPLOAD_FAILED'), $_SERVER['HTTP_REFERER']);
            }
        }else{
            $this->display();
        }
    }

    /**
     * @ 获取项目数据
     * @param $where
     * @param null $fields
     * @return array
     */
    protected function getProgramData ($where, $fields=null)
    {
        $defaultFields      =   '*';
        $fields             =   is_null($fields) ? $defaultFields : $fields;
        $model              =   new ProgramModel();
        $limit              =   $where['limit'];
        unset( $where['limit'] );
        $count              =   (int)$model->where( $where )->count();
        $data               =   $model->field($fields)
            ->where($where)->limit($limit[0],$limit[1])->select();


        return [ 'data'=>$data ?: [], 'sql'=>$model->getLastSql(), 'count'=>$count ];
    }

    /**
     * @参数解析
     * @param $params
     * @return array
     */
    protected function parseParams ($params)
    {
        $where   =   [];    $where['id']  =   [];
        // nature
        if( array_key_exists( 'nature_id', $params ) && !empty($params['nature_id']) )
            $where['nature_id']=[ 'eq',(int)$params['nature_id'] ];

        // program_category
        if( array_key_exists( 'program_category', $params ) && !empty($params['program_category']) )
            $where['program_category']=[ 'eq',(int)$params['program_category'] ];

        // taoci
        if( array_key_exists( 'taoci', $params ) && $params['taoci'] != 'all' )
            $where['taoci']=[ 'eq',(int)$params['taoci'] ];

        // major_category
        if( array_key_exists( 'major_category', $params ) && !empty($params['major_category']) )
            $where['major_category']=[ 'eq',(int)$params['major_category'] ];

        // terms
        if( array_key_exists( 'term', $params ) && !empty($params['term']) ){
            $termIds            =   array_unique( explode( ',', $params['term'] ) );
            $termModel          =   new PBaseModel('Term');
            $pinfos             =   $termModel->field('p_id,t_id,count(*)')
                ->where( ['t_id'=>['in', $termIds]] )
                ->group('p_id')
                ->having('count(*) >= '.count($termIds) )
                ->select();
            $pids               =   array_unique( array_column($pinfos,'p_id') );
            $pids               =   array_map( function($v){
                return (int)$v;
            }, $pids );

            $where['id']        =   [ 'in',  $where['id'] ? array_intersect($where['id'], $pids) : $pids  ];
        }

        // level TODO 多选改为单选 0614
        /*
        if( array_key_exists( 'level', $params ) && !empty($params['level']) ){
            $levelIds           =   array_unique( explode( ',', $params['level'] ) );
            $levelModel         =   new PBaseModel('Level');
            $linfos             =   $levelModel->field('p_id,t_id,count(*)')
                ->where(['l_id'=>['in',$levelIds]])
                ->group('p_id')`
                ->having('count(*) >= '.count($levelIds))
                ->select();
            $pids               =   array_unique( array_column( $linfos, 'p_id' ) );
            $pids               =   array_map( function($v){
                return (int)$v;
            }, $pids );
            $where['id']        =   [ 'in',  $where['id'] ? array_intersect($where['id'], $pids) : $pids  ];
        }
        */
        if( array_key_exists( 'level', $params ) && !empty($params['level']) ){
            $where['recommend_level']       =   ['eq',(int)$params['level']];
        }

        // area
        if( array_key_exists( 'area_id', $params ) || array_key_exists( 'city_id', $params ) ){
            if( array_key_exists( 'city_id', $params ) && !empty($params['city_id']) ){
                $where['area_id']               =   ['eq',(int)$params['city_id']];
            }elseif(!empty($area_id=$params['area_id'])){
                $subItems           =   ( new PBaseModel('Area') )->field('id')->where("pid={$area_id}")->select();
                $ids                =   array_merge( [$area_id], array_map( function($v){ return $v['id']; }, $subItems ) );
                $where['area_id']   =   ['in',$ids];
            }
        }

        // rank
        if( array_key_exists( 'rank', $params ) && !empty($params['rank']) )
            $where['school_rank']=[ 'between', str_replace('-',',',$this->rank[(int)$params['rank']]) ];

        // name
        if( array_key_exists( 'name', $params ) && !empty($params['name']) )
            $where['school_name|include_major']=[ 'LIKE', "%{$params['name']}%" ];

        // page
        $page       =   1;
        if( array_key_exists( 'page', $params ) && !empty($params['page']) ){
            $page           =   (int)$params['page'];
        }

        if( !$where['id'] ) unset( $where['id'] );

        $pagesize       =   20;
        $offset         =   ($page-1)*$pagesize;
        $where['limit'] =   [$offset,$pagesize];

        return $where;
    }

    /**
     * @ 地区信息
     * @return mixed
     */
    protected function getArea()
    {
        return ( new PBaseModel('Area') )->field('id,name,pid')->where('pid=0')->select();
    }

    /**
     * @ 标签库获取
     * @param null $type
     * @return array|mixed
     */
    protected function getTags ($type=null)
    {
        $data       =   ( new PBaseModel('Tags') )->field('id,name,type')->select();
        $result     =   array();
        array_map( function ($v) use(&$result){ $result[$v['type']][$v['id']] = $v; }, $data );
        return is_null( $type ) ? $result : $result[$type];
    }
}
