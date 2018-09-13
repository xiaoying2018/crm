<?php
class ScheduleDocumentModel extends EducationModelEdu
{
    protected $tableName='schedule_Document';

    public function getDataBy($field='all',$condition=array()){
        if($field=='all'){
            $data=$this->where($condition)
                ->select();
            return $data;
        }else{
            return $this->where($condition)->getField($field);
        }
    }

    public function getDocumentByScheduleId($schedule_id)
    {
        $res=$this->field('document_id')->where(array('schedule_id'=>array('eq',$schedule_id)))->select();
        if(empty($res)) return array();
        $document_ids=array();
        foreach ($res as $re){
            $document_ids[]=$re['document_id'];
        }
        $res=(new CourseDocumentModel())->field("*")->where(array('id'=>array('in',$document_ids)))->select();
        return $res;
    }

}