/*
保理自定义 js
*/

//加载遮层

function loading(){

	$('#layer-loading').show();
	$('#layer-circle').show();
	setTimeout("loadingC()",5000);
}

function loadingC(){

	$('#layer-loading').hide();
	$('#layer-circle').hide();
}

