<?php

class PBaseModel extends Model
{
    protected $dbName                   =   'program';

    protected $tablePrefix              =   'p_';






    /**
     * @ 标签库获取
     * @param null $type
     * @return array|mixed
     */
    public function getTags ($type=null)
    {
        $data       =   ( new self('Tags') )->field('id,name,type,remark')->select();
        $result     =   [];
        foreach ($data as $value){
            $result[$value['type']][$value['id']]   =   $value;
        }
        return is_null( $type ) ? $result : $result[$type];
    }

}