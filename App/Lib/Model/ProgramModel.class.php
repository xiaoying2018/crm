<?php

class ProgramModel extends PBaseModel
{
    protected $tableName                =   'program';


    public function ownKeys ($where)
    {
        return ( new parent('KeyRelation') )->field( 'p_id,k_id' )->where( $where )->select();
    }

    public function majorList ($where,$fields=null)
    {
        $defaultFields      =   'p_major.id, p_major.name_cn,p_major.school_name, p_major.volume, p_major.cost, p_major.required_collect, p_major.description, p.rank';
        $fields             =   is_null($fields) ? $defaultFields : $fields;
        return ( new parent('Major') )->field( $fields )
            ->join('LEFT JOIN program.p_major_relation mr ON mr.m_id = p_major.id')
            ->join('LEFT JOIN program.p_program p ON p.id = mr.p_id')
            ->where($where)
            ->order('p.rank ASC')
            ->group( 'id' )
            ->select();
    }

    protected function _after_insert($data, $options)
    {

    }

    public function getTag() {
        $res = $this->getTags();
        return $res;
    }
    



}