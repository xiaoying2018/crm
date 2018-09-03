// spacer.gif

 $(document).ready(function(){
 	      $(document.body).prepend("<div id='recordBtn' style='position:fixed;left:0px;top:300px;width:28px; height:27px;cursor: pointer;background:url(Public/img/record.png) left top no-repeat;'></div><div class='recordBox' style='display:none; position:fixed;left:29px;top:300px;width:170px; height:290px; background:#fff; padding:15px; border:1px solid #ccc'><span class='recordClose' style='position:absolute;right:15px; top:15px; display:block;width:9px; height:9px;cursor: pointer;  background:url(Public/img/record.png) -3px -29px;'></span><h2 style='color:#500050!important;font-size:17px;line-height:17px;margin-bottom:10px; font-family: \"Open Sans light\",sans-serif;'>最近访问</h2><ul><li class='business'><a href='#'>商机</a></li><li class='contact'><a href='#'>联系人</a></li></ul></div>");
 
       $('#recordBtn').click(function(){
       	     var state = $('.recordBox').css('display');
       	     if (state == 'none') {
       	     	$('.recordBox').css('display','block');
       	     }else{
       	     	$('.recordBox').css('display','none');
       	     }
       })
// 设置样式
       $('.recordBox ul li').css({'background':'url(Public/img/all-TitleIcons.png) no-repeat','padding-left':'30px','margin-bottom':'5px'});
     
       //商人
       $('.business').css({'background-position':'left -18px'});

       //联系人
       $('.contact').css({'background-position':'left -36px'});
 
      
 //关闭按钮

$('.recordClose').click(function(){
	$('.recordBox').css('display','none');
});
$('.recordClose').mouseenter(function(){
	$(this).css('background-position','-3px -41px');
});
$('.recordClose').mouseleave(function(){
	$(this).css('background-position','-3px -29px');
});


// 阻止冒泡


        // $("*").click(function (event) {
        //     if (!$(this).hasClass("recordBox")&&!$(this).hasClass("recordClose")){
        //         $(".recordBox").css('display','none');
        //     }
        //     // event.stopPropagation();   
        // });



});

	

