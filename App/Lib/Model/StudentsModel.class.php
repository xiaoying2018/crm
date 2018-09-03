<?php

class StudentsModel extends EBaseModel
{
    protected $tableName                =   'students';

    public function getStudents($condition='')
    {
        if ($condition)
        {
            $data = $this->db(1,"DB_CONFIG_EDU")->query('select * from `students` WHERE '.$condition);
        }else{
            $data = $this->db(1,"DB_CONFIG_EDU")->query('select * from `students`');
        }

        return $data;
    }

    public function findStudents($id=1)
    {
        $data = $this->db(1,"DB_CONFIG_EDU")->query('select `realname`,`customer_id` from `students` WHERE  `id`='.$id.' limit 1');
        return $data?$data[0]:[];
    }

    public function search($like)
    {
        $data = $this->db(1,"DB_CONFIG_EDU")->query('select `id` from `students` WHERE `realname` LIKE "%'.$like.'%"');
        return $data;
    }

    public function cstm2stdt($cid)
    {
        $data = $this->db(1,"DB_CONFIG_EDU")->query('select `id` from `students` WHERE `customer_id`='.$cid.' limit 1');

        return $data?$data[0]['id']:'';
    }

}