<?php
class CoursescheduleViewModel extends ViewModel{
    public $viewFields = array(
        'Schedule'=>array('id'=>'sc_id','serial','start_time','_table'=>"education.course_schedule"),
        'Section'=>array('id'=>'s_id','name'=>'section_name','duration','_table'=>"education.course_section",'_on'=>'Schedule.section_id = Section.id','_type'=>'LEFT'),
        'Period'=>array('id'=>'p_id','name'=>'period_name','_table'=>"education.course_period",'_on'=>'Period.id = Schedule.period_id','_type'=>'LEFT'),
        'Course'=>array('id'=>'c_id','name'=>'course_name','_table'=>"education.course",'_on'=>'Course.id = Period.course_id','_type'=>'LEFT'),


    );
}