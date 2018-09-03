<?php

/**
 * 短信宝
 */
class SMSBao
{
    public static function send( $phone, $content )
    {
        $u = 'everelite';
        $p = md5('invY1234');
        // $config = "u={$u}&p={3ed7c1570201866bb2a9f660b17ab368}&m={$phone}&c={$content}";
        // // 初始化一个 cURL 对象
        // $curl = curl_init();
        // // 设置你需要抓取的URL
        // curl_setopt($curl, CURLOPT_URL, 'http://api.smsbao.com/sms?'.$config);
        // // 设置header
        // curl_setopt($curl, CURLOPT_HEADER, 1);
        // // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // // 运行cURL，请求网页
        // $data = curl_exec($curl);
        // // 关闭URL请求
        // curl_close($curl);
        // return $data;

        $url = "http://api.smsbao.com/sms?u={$u}&p={$p}&m={$phone}&c={$content}";
        return file_get_contents($url);
    }
}