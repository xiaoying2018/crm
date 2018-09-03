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

}