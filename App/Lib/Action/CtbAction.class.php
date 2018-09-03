<?php
/**
 * Created by PhpStorm.
 * User: dragon
 * Date: 2018/7/10
 * Time: 10:27
 */

class CtbAction extends Action
{

    // 定时任务测试
    public function timeoutLeadsManage()
    {
        echo json_encode(['send_message_num'=>666, 'first_message_id'=>6]);
    }

}