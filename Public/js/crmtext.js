$(document).ready(function  () {
	// body...
     $("#tab-btn li").click(prTabSwitch);
     function prTabSwitch () {
		// body...
		 $(this).addClass("cur").siblings().removeClass("cur");
		 var num = $(this).attr('alt');
		 $("#tab-main-"+num).removeClass("hide").siblings().addClass("hide");
     };


  
     //审批切换
      $("#examine_type").change(prTabSwitchapp);
     function prTabSwitchapp () {
		 var num = $(this).children('option:selected').val();
		 $("#tab-approve-main-"+num).removeClass("hide").siblings().addClass("hide");
     };
});