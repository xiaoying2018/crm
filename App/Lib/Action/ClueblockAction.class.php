<?php

class ClueblockAction extends Action
{
    /*** genesis ***/
    public function index ()
    {
        $this->assign( [
            'blockList'     =>  ['name'=>'luke'],
        ] );
        $this->display();
    }

    /**
     * 获取线索区列表
     */
    public function search ()
    {
        $data       =   M('Block')->field('id,name,person_id,person_name')->select();

        $this->ajaxReturn( ['status'=>true, 'data'=>$data] );
    }

    /**
     *  获取线索区下分类列表
     */
    public function search_cate ()
    {
        $block          =   I('get.block');
        if( !$block )
            $this->ajaxReturn(['status'=>false]);
        $model          =   M('LeadCategory');
        $data           =   $model->field('id,name,parent_id,remark,block_id')
            ->where("block_id={$block}")
            ->select();
        $this->ajaxReturn( [
            'status'    =>  true,
            'data'      =>  $data,
        ] );
    }

    public function add ()
    {
        // 获取负责人列表
        $persons        =   $this->blockPersons();
        // 校区列表
        $branchs        =   $this->getBranch();
        if( IS_AJAX && IS_POST )
        {
            $model          =   M('Block');

            if( $model->create( I('post.'), 1 ) ){
                if( $model->add() != false ){
                    $this->ajaxReturn( ['status'=>true,] );
                }else{
                    $this->ajaxReturn( ['status'=>false,$model->getError()] );
                }
            }
            $this->ajaxReturn( ['status'=>false,$model->getError()] );
        }
        else
        {
            $this->assign( [
                'persons'       =>  $persons,
                'branchs'       =>  $branchs,
            ] );
            $this->display();
        }
    }

    public function edit ()
    {
        // 获取负责人列表
        $persons        =   $this->blockPersons();
        // 校区列表
        $branchs        =   $this->getBranch();
        $model          =   M('Block');
        if(IS_AJAX && IS_POST)
        {
            if( $model->create( I('post.'), 2 ) ){
                if( $model->save() != false ){
                    $this->ajaxReturn( ['status'=>true] );
                }
                $this->ajaxReturn( ['status'=>false,$model->getError()] );
            }
            $this->ajaxReturn( ['status'=>false,$model->getError()] );
        }
        else
        {
            $id     =   I('get.id');
            $info       =   $model->field('id,name,person_id,person_name,department_id')->find($id);
            $this->assign( [
                'persons'       =>  $persons,
                'info'          =>  $info,
                'is_edit'       =>  true,
                'branchs'       =>  $branchs,
            ] );
            $this->display('add');
        }
    }

    /*** person ***/
    public function cate_index ()
    {
        if( !session('?person') ) alert('error',  '无权访问', $_SERVER['HTTP_REFERER']);
        // 获取当前去信息
        $blockInfo          =   M('Block')->find(session('person'));

        $this->assign([
            'blockInfo'     =>  $blockInfo,
        ]);

        $this->display();
    }

    public function cate_search ()
    {
        if( session('?block') && session('block') > 0 ){
            $where['block_id']     =   [ 'eq', (int)session('block') ];
        }else{
            $where              =   [];
        }

        $data       =   M('LeadCategory')->where( $where )->select();

        $this->ajaxReturn([
            'status'=>true,
            'data'=>$data ?: [],
            '_sql'=>M('LeadCategory')->getLastSql(),
            'block'=>   session('block')
        ]);
    }

    public function cate_add ()
    {
        $model                  =   M('LeadCategory');
        if( IS_AJAX && IS_POST )
        {
            $params                 =   I('post.');
            $params['block_id']     =   session('person');
            if( $model->create( $params, 1 ) ){
                if( $model->add() != false ){
                    $this->ajaxReturn( ['status'=>true,] );
                }else{
                    $this->ajaxReturn( ['status'=>false,$model->getError()] );
                }
            }
            $this->ajaxReturn( ['status'=>false,$model->getError()] );
        }
        else
        {
            if( !session('?person') ) alert('error',  '无权访问', $_SERVER['HTTP_REFERER']);
            // 获取当前去信息
            $blockInfo          =   M('Block')->find(session('person'));
            $this->assign( [
                'blockInfo'       =>  $blockInfo,
            ] );
            $this->display();
        }
    }

    public function cate_edit ()
    {
        $model                  =   M('LeadCategory');
        if( IS_AJAX && IS_POST )
        {
            $params                 =   I('post.');
            $params['block_id']     =   session('person');
            if( $model->create( $params, 2 ) ){
                if( $model->save() != false ){
                    $this->ajaxReturn( ['status'=>true,] );
                }else{
                    $this->ajaxReturn( ['status'=>false,$model->getError()] );
                }
            }
            $this->ajaxReturn( ['status'=>false,$model->getError()] );
        }
        else
        {
            if( !session('?person') ) alert('error',  '无权访问', $_SERVER['HTTP_REFERER']);
            // 获取当前去信息
            $blockInfo          =   M('Block')->find(session('person'));
            $info               =   $model->find(I('get.id'));
            $this->assign( [
                'blockInfo'         =>  $blockInfo,
                'info'              =>  $info,
                'is_edit'           =>  true
            ] );
            $this->display('cate_add');
        }
    }

    public function cate_del ()
    {
        if( !session('?person') )    $this->ajaxReturn( ['status'=>false] );

        $id         =   I('post.id');
        M('LeadCategory')->delete((int)$id);

        $this->ajaxReturn(['status'=>true]);
    }

    protected function blockPersons ()
    {
//        // 获取负责人岗位信息
//        $position           =   M('Position')->field('position_id,department_id')
//                        ->where(['parent_id'=>['eq',1],'department_id'=>['neq',1]])->select();
//        // 岗位ids集
//        $positionIds        =   array_map( function($v){
//            return $v['position_id'];
//        }, $position );
//        // 获取用户Ids
//        $user               =   M('Role')->field('user_id')->where(['position_id'=>['in',$positionIds]])->select();
//        $userIds            =   array_map( function ($v){
//            return $v['user_id'];
//        }, $user );
//        // 获取用户信息
//        $userinfo           =   M('User')->field('user_id,full_name')->where(['user_id'=>['in',$userIds]])->select();

        // TODO 0606修改
        $userinfo           =   M('User')->field('user_id,full_name')->where([])->select();
        return $userinfo;
    }

    protected function getBranch ()
    {
        $genesisId      =   1;
        // 实例化model
        $model          =   M('RoleDepartment');
        $branchs        =   $model->field('department_id,name')
            ->where(['parent_id'=>['eq',$genesisId]])->select();

        return $branchs;
    }
}