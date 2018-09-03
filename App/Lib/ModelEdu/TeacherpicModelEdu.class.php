<?php

class TeacherpicModelEdu extends EducationModelEdu
{
    protected $tableName                =   'teacher_pic';


    public function teacher_pics ($where)
    {
        return $this->field('t_pic.user_id,t_pic.mate_id,m.name,m.path,m.create_at')
            ->join("t_pic LEFT JOIN {$this->dbName}.material m ON t_pic.mate_id = m.id")
            ->where($where)
            ->order('m.id DESC')
            ->select();
    }
}