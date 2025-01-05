<?php
header('Content-Type: application/x-javascript; charset=utf-8');
require_once( dirname( __FILE__ , 5) . '/wp-load.php' );
$ajaxurl = admin_url( 'admin-ajax.php');
$script = <<< EOF
function stamping_time(){
	var mes1 = location.href;
	var mes2 = document.getElementById("stamping-note").value;
	var mes = mes1 + "||" + mes2;
	jQuery.ajax({
		type: "POST",
		url: "{$ajaxurl}",
		data: {
			"action": "stamping_time",
			"mes"   : mes,
		},
		success: function( response ){
			document.getElementById("attendMsg").innerText = response;
			document.getElementById("stamping-note").value = '';
		}
	});
	return false;
	alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
}
function stamplist_view(){
	jQuery.ajax({
		type: "POST",
		url: "{$ajaxurl}",
		data: {
			"action": "stamplist_view",
		},
		success: function( response ){
			document.getElementById("AttendView").innerHTML = response;
		}
	});
	return false;
	alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
}
function showAttend(){
	stamplist_view();
}
showAttend();
function custom_stamping_time(){
	var mes1 = document.getElementById("uid-new").value;
	var mes2 = document.getElementById("pli-new").value;
	if( document.getElementById("day3") != null ) {
		var mes3 = document.getElementById("day3").value;
	} else {
		var mes3 = document.getElementById("day1").value;
	}
	var mes4 = document.getElementById("api-new").value;
	var mes = mes1 + "||" + mes2 + "||" + mes3 + "||" + mes4;
	jQuery.ajax({
		type: "POST",
		url: "{$ajaxurl}",
		data: {
			"action": "custom_stamping_time",
			"mes"   : mes,
		},
		success: function( response ){
			location.reload();
		}
	});
	return false;
	alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
}
function stamping_delete(mes){
	jQuery.ajax({
		type: "POST",
		url: "{$ajaxurl}",
		data: {
			"action": "stamping_delete",
			"mes"   : mes,
		},
		success: function( response ){
			location.reload();
		}
	});
	return false;
	alert( "予期しないエラーしました。何度も発生する場合は管理者に問い合わせて下さい。" );
}
EOF;
echo $script;