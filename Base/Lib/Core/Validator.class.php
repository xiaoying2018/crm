<?php
class Validator
{
    protected $optional     =   ['in','multi'];

    public function handle ($value,$rules)
    {
        $_parsed        =   $this->_parseItem($rules);

        foreach ( $_parsed as $group ){
            if( !method_exists($this,$group['operate'] ) )    return false;
            if( $this->{$group['operate']}($value,$group['params']) === false ){
                return false;
            }
        }

        return true;
    }

    public function batch_handle ($data, $_rules)
    {
        foreach ($_rules as $field => $_r){
            if( !array_key_exists( $field, $data ) || $this->handle( $data[$field], $_r ) === false ){
                return [ 'result'=>false, 'field'=>$field ];
            }
        }
        return [ 'result'=>true ];
    }

    protected function required($value,$params)
    {
        return  ( $value == '' ) ? false : true;
    }

    protected function min ($value,$params)
    {
        // 为空不验证
        if( $this->required($value,$params) === false ) return true;

        if( is_string($value) && (strlen($value) >= $params) )    return true;

        if( is_array($value) && count($value) >= $params ) return true;

        return false;
    }

    protected function max ($value,$params)
    {
        // 为空不验证
        if( $this->required($value,$params) === false ) return true;

        if( is_string($value) && strlen($value) <= $params )    return true;

        if( is_array($value) && count($value) <= $params ) return true;

        return false;
    }

    protected function in ($value,$params)
    {
        return in_array( $value, $params ) ? true : false;
    }

    protected function multi ($value,$params)
    {
        // 为空不验证
        if( $this->required($value,$params) === false ) return true;

        foreach ($value as $v){
            if( $this->in($v, $params) === false ){
                return false;
            }
        }
        return true;
    }

    protected function number ($value,$params) {}

    protected function phone ($value,$params)
    {
        // 为空不验证
        if( $this->required($value,$params) === false ) return true;

        return preg_match('/^1[345678][0-9]{9}$/',$value ) ? true : false;
    }

    protected function email ($value,$params)
    {
        // 为空不验证
        if( $this->required($value,$params) === false ) return true;

        return preg_match('/([a-z0-9]*[-_.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[.][a-z]{2,3}([.][a-z]{2})?/i', $value)
            ? true
            : false;
    }

    // 规则库
    protected function _rules($rule) {}

    // 解析单元验证规则
    protected function _parseItem ($rule)
    {
        // 储存声明
        $_rule          =   [];
        // 解压单元
        $item           =   explode( '|',$rule );
        //
        $_rule          =   array_map( function($v){
            list( $operate, $params )      =   explode( ':', $v );
            if( in_array( $operate, $this->optional ) ){
                $params     =   explode( ',', $params );
            }
            return compact( 'operate', 'params' );
        }, $item );

        return  $_rule;
    }
    //
}