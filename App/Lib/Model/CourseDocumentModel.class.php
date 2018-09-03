<?php
class CourseDocumentModel extends EducationModelEdu
{
    protected $apiAddError=array(
        '-1'=>'接口调用失败',
        '-2'=>'文件上传失败',
        '-3'=>'文件移动失败',
        '-4'=>'非法文件类型',
        '-5'=>'未指定房间号或key',
        '-6'=>'非公开文件，但未指定房间号',
        '-7'=>'房间不存在或已经删除',
        '-8'=>'该房间不是本企业',
        '-10'=>'上传文件大于100M',
        '3'=>'文件存储失败',
    );

    protected $apiDeleteError=array(
        '4007'=>'没有该房间',
        '4105'=>'没有符合数据'
    );

    // 自动验证
    protected $_validate=array(
        array('file','require','课件文件必须选择'),

    );

    /* 自动完成 */
    protected $_auto = array(
        array('updated_at', 'createTime', self::MODEL_BOTH, 'callback'),
        array('created_at', 'createTime', self::MODEL_INSERT, 'callback'),
        array('creator_id', 'getUserId', self::MODEL_INSERT, 'callback'),
        array('updater_id', 'getUserId', self::MODEL_BOTH, 'callback'),
    );

    /* 创建时间 */
    public function createTime() {
        return date('Y-m-d H:i:s',time());
    }

    public function getUserId()
    {
        return session('role_id');
    }

    public function getDataBy($field='all',$condition=array()){
        if($field=='all'){
            $data=$this->alias('cd')
                ->field('cd.*,u.name')
                ->join('JOIN mx_user u ON u.user_id = cd.creator_id')
                ->where($condition)
                ->select();
            foreach ($data as $key=>$val){
                if($val['size']){
                    $data[$key]['size']=format_size($val['size']);
                }
            }
            return $data;
        }else{
            return $this->getField("cid,$field");
        }
    }

    //添加数据
    public function addData(){
        $data=I('post.');
        //请求接口
        $url = C('TK_ROOM.url').'uploadfile';
        $key= C('TK_ROOM.key');
        $allExts=C('document_type');
        $data['file']=upload($allExts);
        $data['size']=filesize($data['file']);
        $c_data['key']=$key;
        $c_data['conversion']=1;
        $c_data['dynamicppt']=isset($data['property'])?$data['property']:0;
        $c_data['isopen']=1;
        $c_data['filedata']=curl_file_create(realpath($data['file']));
        //上传图片
        $data['title']=$_FILES['file']['name'];
        $res=curlPostWithFile($url,array('Content-type:multipart/form-data'),$c_data);
        if(!$res['status']){
            $this->error=$res['msg'];
            return false;
        }
        $result=json_decode($res['msg'],true);
        if($result['result']!=0){
            $this->error=isset($this->apiAddError[$result['result']])?$this->apiAddError[$result['result']]:'未知错误,请联系管理员';
            return false;
        }
        $data['api_res']=$res['msg'];
        if($this->create($data)){
            return $this->add();
        }
    }

    //传递cid和field获取对应的数据
    public function getDataById($id,$field='all'){
        if($field=='all'){
            return $this->where(array('id'=>$id))->find();
        }else{
            return $this->where(array('id'=>$id))->getField($field);
        }

    }



    // 删除数据
    public function deleteData($id){
        $data=$this->getDataById($id);
        $api_res=json_decode($data['api_res'],true);
        //请求接口删除数据
        $url = C('TK_ROOM.url').'deletefile';

        $key= C('TK_ROOM.key');

        $c_data['key']=$key;
        $file_id=$api_res['fileid'];
        $c_data['fileidarr']=array($file_id);
        $res=curlPost($url,'Content-type:application/x-www-form-urlencoded',$c_data);
        $result=json_decode($res,true);
        if($result['result']!=0){
            $this->error=isset($this->apiDeleteError[$result['result']])?$this->apiDeleteError[$result['result']]:'未知错误,请联系管理员';
        }else{
            if($this->where(array('id'=>$id))->delete()){
                @unlink($data['file']);
                return true;
            }else{
                return false;
            }
        }

    }

}