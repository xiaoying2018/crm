<?php

class OAWidget extends Widget
{
    public function render($data)
    {
        // TODO: Implement render() method.
        $items       =   [
            [   'name'=>'工作日志','url'=>U('log/index'),],
            [   'name'=>'审批','url'=>U('examine/index'),],
            [   'name'=>'知识','url'=>U('knowledge/index'),],
            [   'name'=>'公告','url'=>U('announcement/index'),],
            [   'name'=>'定位签到','url'=>U('sign/index'),],
            [   'name'=>'日程','url'=>U('event/index'),],
            [   'name'=>'任务','url'=>U('task/index'),],
        ];
        return $this->renderFile("index", [
            'title'         =>  'OA办公地址栏',
            'items'         =>  $items,
        ]);
    }
}