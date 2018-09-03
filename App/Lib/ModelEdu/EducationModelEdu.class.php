<?php

class EducationModelEdu extends Model
{
    protected $dbName       =   'education';

    protected $tablePrefix  =   ' ';

    protected function _before_insert(&$data, $options)
    {
        $allowFields        =   $this->allowFields();
        if( in_array( 'create_at', $allowFields ) ){
            $data['create_at']          =   date('Y-m-d H:i:s');
            $creator_id                 =   session('user_id')
                ?   session('user_id')
                :   (array_key_exists('creator_id', $data) ? $data['creator_id'] : 0);
            $data['creator_id']         =   $creator_id;
        }
    }

    protected function _before_update(&$data, $options)
    {
        $allowFields        =   $this->allowFields();
        if( in_array('updator_id', $allowFields) ){
            $data['updator_id']         =   session('user_id');
        }
    }


    protected function allowFields ()
    {
        return array_keys($this->fields['_type']);
    }

}