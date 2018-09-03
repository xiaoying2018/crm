<?php
class SignPersonBehavior extends Behavior
{
    public function run(&$params)
    {
        // TODO: Implement run() method.

        session('user_id') && !session('?admin') && !session('?person') && $this->attemptSign();
    }


    protected function attemptSign ()
    {
        $model          =   M('Block');
        $blockInfo      =   $model->field('id,department_id')
            ->where('person_id='.session('user_id'))->find();

        session( 'person', $blockInfo['id'] );
    }
}