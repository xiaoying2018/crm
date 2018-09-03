<?php
class RoleModelEdu extends EducationModelEdu
{
    protected $tableName                =   'edu_role';

    /**
     * @ 角色操作列表
     * @param $user_id
     * @return mixed
     */
    public function role_list ($user_id)
    {
        return $this->field('e_r.name,t_u.id,mx_u2.full_name creator_name,t_u.create_at')
            ->join("e_r LEFT JOIN {$this->dbName}.teacher_user t_u ON e_r.id = t_u.role_id")
            ->join("LEFT JOIN mxcrm.mx_user mx_u1 ON mx_u1.user_id = t_u.user_id")
            ->join("LEFT JOIN mxcrm.mx_user mx_u2 ON mx_u2.user_id = t_u.creator_id")
            ->where("t_u.user_id = {$user_id} OR t_u.id IS NULL")
            ->select();
    }


}