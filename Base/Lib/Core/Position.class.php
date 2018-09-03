<?php

class Position
{
    public static function ByModile ( $mobile )
    {
        if( !$mobile )  return false;
        $api        =   'http://mobsec-dianhua.baidu.com/dianhua_api/open/location?tel='.$mobile;
        $result     =   json_decode( self::_driver( $api ) ) ;

        if( $result->responseHeader->status == 200 ){
            return $result->response->{$mobile}->detail;
        }else{
            return false;
        }
    }

    public static function ByIp ( $ip )
    {
        if( !$ip ) return false;
        $api        =   'http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip='.$ip;
        $result     =   json_decode( self::_driver($api) )  ;

        if( !is_object($result) && $result == -3 ){
            return false;
        }else{
            return $result;
        }
    }


    public static function _driver ($url,$type=1,$data=[])
    {
        // return  file_get_contents( $url );
        // 1. 初始化
        $ch = curl_init();
        // 2. 设置选项，包括URL
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_HEADER,0);
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);

        return $output;
    }
}