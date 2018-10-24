<?php
class CourseDocumentAction extends Action{



    protected $db;


    public function _initialize(){

        $this->db=D('CourseDocument');
    }
    public function index(){
        if ($this->isAjax()) {
                $wheredata = $_REQUEST;
                $page = $wheredata['page'] ? $wheredata['page'] : 1;// 请求页码
                $limit = $wheredata['rows'] ? $wheredata['rows'] : 10;// 每页显示条数
                $start = ($page - 1) * $limit;// 查询起始值

                if(isset($_REQUEST['schedule_id']) && is_numeric($_REQUEST['schedule_id'])){
                    $schedule_id=$_GET['schedule_id'];
                    if(isset($_REQUEST['type']) && $_REQUEST['type']=='guanlian'){
                        $type='guanlian';
                    }else{
                        $type='chakan';
                    }
                    if($type=='guanlian'){
                    //如果是要关联,查询关联表中所有
                    $model=$schedule_document=D('ScheduleDocument');
                    $schedule_document=$model->getDataBy('all',array('schedule_id'=>$schedule_id));
                    if($schedule_document){
                        //关联的有课程
                        $document_ids=array();
                        foreach ($schedule_document as $val){
                            $document_ids[]=$val['document_id'];
                        }
                        $condition=array();
                        $condition['id']=array('not in',$document_ids);
                        $condition['cd.creator_id']=session('user_id');
                        $res=$this->db->getDataPage($condition,$start,$limit);
                        $count=$res['count'];
                        $data=$res['data'];
                    }else{
                        $condition=array();
                        $condition['cd.creator_id']=session('user_id');
                        $res=$this->db->getDataPage($condition,$start,$limit);
                        $count=$res['count'];
                        $data=$res['data'];
                    }
                }elseif($type='chakan'){
                    $model=$schedule_document=D('ScheduleDocument');
                    $schedule_document=$model->getDataBy('all',array('schedule_id'=>$schedule_id));
                        if($schedule_document){
                            //关联的有课程
                            $document_ids=array();
                            foreach ($schedule_document as $val){
                                $document_ids[]=$val['document_id'];
                            }
                            $condition=array();
                            $condition['id']=array('in',$document_ids);
                            $condition['cd.creator_id']=session('user_id');
                            $res=$this->db->getDataPage($condition,$start,$limit);
                            $count=$res['count'];
                            $data=$res['data'];
                        }
                }


            }else{
                    $condition=array();
                    $condition['cd.creator_id']=session('user_id');
                    $res=$this->db->getDataPage($condition,$start,$limit);
                    $count=$res['count'];
                    $data=$res['data'];
            }

            $this->ajaxReturn([
                'result' => true,
                'lists' => array_values($data),
                'count'=>$count,
                'total'=>ceil($count / $limit),
                '_sql' => $this->db->getLastSql()
            ]);
        }

        //页面请求
        if(isset($_GET['schedule_id']) && is_numeric($_GET['schedule_id'])){
            $schedule_id=$_GET['schedule_id'];
            if(isset($_GET['type']) && $_GET['type']=='guanlian'){
                $type='guanlian';
            }else{
                $type='chakan';
            }

            $this->assign('type',$type);
            $this->assign('schedule_id',$schedule_id);

        }
        $this->alert=parseAlert();
        $this->display();
    }

    public function add()
    {
        if(IS_POST){
            if($aid=$this->db->addData()){
                alert('success','课件添加成功',U('CourseDocument/index'));
               // $this->redirect(U('CourseDocument/index'));
            }else{
                alert('error',$this->db->getError(),U('CourseDocument/add'));
            }
        }else{
            $this->alert=parseAlert();
            $this->display();
        }
    }

    public function delete()
    {
        $input=$_REQUEST;
        $this->db->startTrans();
        try {
            foreach ($input['id'] as $id)
            {
                if(!$this->db->deleteData($id)){
                    throw new Exception($this->db->getError());
                }
            }
            $this->db->commit();
            $data=[
                'status'=>1,
            ];
        }catch(\Exception $e){
            $data=[
                'status'=>0,
                'info'=>$e->getMessage()
            ];
        }
        if($data['status']){
            $this->ajaxReturn([
                'status'=>1,
            ]);
        }else{
            $this->ajaxReturn([
                'status'=>0,
                'info'=>$this->db->getError()
            ]);
        }
    }


    public function relatedSchedule()
    {
        $data=I('post.');
        //根据schedule_id获取


        $insert=$document_ids=array();
        foreach ($data['id'] as $v){
            $insert[$v]=array(
                'schedule_id'=>$data['schedule_id'],
                'document_id'=>$v
            );
            $document_ids[]=$v;
        }
        $schedule=new ScheduleModelEdu();

        $schedule_info=$schedule->field('serial')
            ->where('id ='.$data['schedule_id'])
            ->find();
        \Log::write(json_encode($document_ids));
        $serial=$schedule_info['serial'];
        $documents=D('CourseDocument')
            ->field('api_res')
            ->where(array('id'=>array('in',$document_ids)))
            ->select();
        \Log::write(D('CourseDocument')->getLastSql());
        $fileArr=array();
        foreach ($documents as $key=> $document){
            $api_res=json_decode($document['api_res'],true);
            $fileArr[]=$api_res['fileid'];
        }

        //请求接口删除数据
        $url = C('TK_ROOM.url').'roombindfile';

        $key= C('TK_ROOM.key');

        $c_data['key']=$key;
        $c_data['serial']=$serial;

        $c_data['fileidarr']=$fileArr;
        \Log::write(json_encode($c_data));
        $res=curlPost($url,'Content-type:application/x-www-form-urlencoded',$c_data);
        $result=json_decode($res['msg'],true);
        \Log::write($res);
        \Log::write(json_encode($result));
        if($result['result']!=0){
            //失败
            $this->ajaxReturn([
                'status'=>0,
                'msg'=>'第三方接口调用失败'
            ]);
        }
        $model=$schedule_document=D('ScheduleDocument');
        $res=$model->where(array('schedule_id'=>$_REQUEST['schedule_id']))
        ->select();
        if($res){
            foreach ($res as $key=>$val){
                if($data[$val['document_id']]){
                    unset($insert[$val['document_id']]);
                }
            }
        }
        $insert=array_values($insert);
        $model->addAll($insert);
        $this->ajaxReturn([
            'status'=>1
        ]);


    }

    public function DelRelatedSchedule()
    {
        $data=I('post.');
        $delete=$document_ids=array();
        foreach ($data['id'] as $v){
            $delete[]=array(
                'schedule_id'=>$data['schedule_id'],
                'document_id'=>$v
            );
            $document_ids[]=$v;
        }
        $schedule=new ScheduleModelEdu();

        $schedule_info=$schedule->field('serial')
            ->where('id ='.$data['schedule_id'])
            ->find();
        $serial=$schedule_info['serial'];
        $documents=D('CourseDocument')
            ->field('api_res')
            ->where(array('id'=>array('in',$document_ids)))
            ->select();
        $fileArr=array();
        foreach ($documents as $key=> $document){
            $api_res=json_decode($document['api_res'],true);
            $fileArr[]=$api_res['fileid'];
        }

        //请求接口删除数据
        $url = C('TK_ROOM.url').'roomdeletefile';

        $key= C('TK_ROOM.key');

        $c_data['key']=$key;
        $c_data['serial']=$serial;

        $c_data['fileidarr']=$fileArr;
        $res=curlPost($url,'Content-type:application/x-www-form-urlencoded',$c_data);
        $result=json_decode($res['msg'],true);
        if($result['result']!=0){
            //失败
            $this->ajaxReturn([
                'status'=>0,
                'msg'=>'第三方接口调用失败'
            ]);
        }
        $model=$schedule_document=D('ScheduleDocument');
        foreach ($delete as $key=>$val){
            $model->where('document_id='.$val['document_id'].' and schedule_id='.$val['schedule_id'])
                ->delete();
        }

        $this->ajaxReturn([
            'status'=>1
        ]);


    }


    public function getRelatedScheduleList()
    {
        $schedule_id=$_GET['schedule_id'];
        $type=$_REQUEST['type'];
        $this->assign('type',$type);
        $this->assign('schedule_id',$schedule_id);
        $this->display('relatedScheduleList');
    }

    public function getChaKanRelatedScheduleList()
    {
        $schedule_id=$_GET['schedule_id'];
        $type=$_REQUEST['type'];
        $this->assign('type',$type);
        $this->assign('schedule_id',$schedule_id);
        $this->display('chaKanRelatedScheduleList');
    }


    public function getDocumentByScheduleId()
    {
        header("Access-Control-Allow-Origin: *");
        $schedule_id= I('schedule_id');
        if($schedule_id=='' || !is_numeric($schedule_id)){
            $this->ajaxReturn([
                'status'=>false,
                'msg'=>'schedule_id参数不合法'
            ]);
        }
        \Log::write($schedule_id);
        $data=(new ScheduleDocumentModel())->getDocumentByScheduleId($schedule_id);
        $this->ajaxReturn([
            'status'=>true,
            'data'=>$data
        ]);
    }

    public function downLoadDocument()
    {
        header("Access-Control-Allow-Origin: *");
        $document_id =I('document_id');
        if($document_id=='' || !is_numeric($document_id)){
            $this->ajaxReturn([
                'status'=>false,
                'msg'=>'document_id参数不合法'
            ]);
        }
        $res =D('CourseDocument')->field('*')->where(array('id'=>array('eq',$document_id)))->find();
        if(empty($res)){
            $this->ajaxReturn([
                'status'=>false,
                'msg'=>'无此文件',
            ]);
        }
        force_download($res['file'],$res['title']);
    }


    public function downLoadDocumentByFilePath()
    {
        header("Access-Control-Allow-Origin: *");
        $document =I('file_path');
        $title=I('file_name');

        if(empty($document)){
            $this->ajaxReturn([
                'status'=>false,
                'msg'=>'请传入正确的文件',
            ]);
        }

        
        force_download($document,$title);
    }
}