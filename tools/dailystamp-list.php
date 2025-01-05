<?php

class DailyStamp_List {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ), 10, 2 );
	}

	public static function add_menu() {
		$page_title = '日別打刻集計';
		$menu_title = '日別打刻集計';
		$capability = 'edit_pages';
		$menu_slug  = 'dailystamp-list-page';
		$function   = array( __CLASS__, 'list_page' );
		$icon_url	= 'dashicons-media-document';
		$position	= 6;
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public static function list_page() {

		if(isset($_GET['d1'])) { 
			$day1 = $_GET['d1']; 
		} else {
			//$day1 = date("Y-m-d", strtotime("-1 day"));
			$day1 = date("Y-m-d");
		}

		if(isset($_GET['p1'])) { 
			$pst1 = $_GET['p1']; 
		} else {
			$pst1 = 0;
		}

		list( $plc_htm, $plc_html ) = stamp_shop_list( $pst1 );
		list( $uid_htm, $uid_html ) = stamp_users_list(0);

		echo header_css_html();

		$body_html = <<< EOF
		<style type="text/css">
		.bill-list { overflow-x: scroll; }
		.bill-list-table {
		width: 100%;
		white-space: nowrap;
		}
		.mr5 {margin-right: 5px!important;}
		[id^="upt-"].button.button-primary{ margin-right: 5px; }
		.l_sticky { position: sticky; left: 0px; }
		</style>
		<script>
		function btn1Click(){
			var d1;
			d1 = document.getElementById("day1").value;
			var p1;
			p1 = document.getElementById("pst1").value;
			window.open("admin.php?page=dailystamp-list-page&d1=" + d1 + "&p1=" + p1 + "","_parent");
		}
		</script>
		<h1>日別打刻集計</h1>
		<p>集計日<input type="date" name="day1" id="day1" value="{$day1}">&emsp;所属<select name="main_place" id="pst1">{$plc_html}</select>&nbsp;
		<input type="button" class="button button-primary ml10" value="集計" onclick="btn1Click();">&emsp;<button type="button" class="button button-primary" id="dl-xlsx">Download XLSX</button></p>
		<p>ユーザー<select id="uid-new">{$uid_htm}</select>&emsp;
		勤務場所<select id="pli-new">{$plc_htm}</select>&emsp;
		打刻時間<input type="time" id="api-new" />&nbsp;
		<input type="button" class="button button-primary ml10" value="追加" onclick="custom_stamping_time();"></p>
		<form id="dailystamp-list-form" method="post" action="">
		EOF;
		echo $body_html;
		
		$table1 = DailyStamp_List::my_table($day1, $pst1);
		echo $table1;
		echo '</form>';

		if(isset($_POST['dailystamp-list-page'])) {
			if( true == $_POST['dailystamp-list-page']){
				echo '<div id="settings_updated" class="updated notice is-dismissible"><p><strong>保存しました。</strong></p></div>';
			} 
		}

		$wid = '[{ wpx : 50 },{ wpx : 72 },{ wpx : 72 }]';
		$fname = '日別打刻集計.xlsx';
		echo footer_xlsx_html( $wid, $fname );

	}

	public static function my_table($b_date1, $b_pst1) {
		global $wpdb;
		$options = get_option( 'attend-setting', Attend_Admin::options_default() );
		$lmt = $options['upper-limit'];
		$date1 = str_replace("-", "", $b_date1);
		$body_table ='<div id="T_del" class="bill-list">';
		wp_nonce_field('dailystamp-nonce-key', 'dailystamp-list-page');
		$body_table .='<table class="bill-list-table table-to-export" data-sheet-name="日別打刻集計">';
		$body_table .= <<< EOF
		<thead>
		<tr>
		<th>ID</th>
		<th class="l_sticky">ユーザー</th>
		<th>営業日付</th>
		EOF;
		for($i = 2; $i <= $lmt; $i = $i + 2){
			$body_table .= '<th colspan="2">勤務場所/打刻時間</th><th colspan="2">勤務場所/打刻時間</th>';
		}
		$body_table .= <<< EOF
		<th>勤務時間計</th>
		<th>操作</th>
		</tr>
		</thead>
		EOF;
		$key = 'stamp_' . $date1;
		$lists = $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT umeta_id, user_id, meta_key, meta_value FROM $wpdb->usermeta 
				WHERE meta_key = %s ORDER BY %d ", 
				$key, 1 ), 'ARRAY_A' );
		$n = count($lists);
		if($n!=0){
			foreach($lists as $list) {
				$vw = 1;
				if($b_pst1!=0) {
					$vw = 0;
					$my_place = get_user_meta( $list['user_id'], 'main_place', false );
					foreach($my_place as $place) {
						foreach($place as $plc) {
							list( $tm, $pl ) = unserialize($plc);
							$d1 = strtotime($b_date1 . '+1 day');
							$d2 = strtotime($tm);
							if($d1 > $d2) {
								if( $b_pst1 == $pl ) {
									$vw = 1;
									break;
								}
							}
						}
					}
				}
				if($vw == 1){
					$user = get_userdata( $list['user_id'] );
					$value = unserialize( $list['meta_value'] );
					$body_table .= '<tr id="row-' . $list['umeta_id'] . '">';
					$body_table .= '<td>' . $list['umeta_id'] . '</td>';
					$body_table .= '<td class="l_sticky">' . $user->display_name . '</td>';
					$body_table .= '<td>' . $date1 . '</td>';
					$a = 0;
					foreach($value as $val) {
						$a++;
						list( $plc_htm, $plc_html ) = stamp_shop_list( $val[0] );
						$body_table .= '<td><span class="pls-' . $list['umeta_id'] . '">' . get_the_title( $val[0] ) . '</span><select class="pli-' . $list['umeta_id'] . '" name="dailystamp-list[pli-' . $list['umeta_id'] . '-' . $a . ']" style="display:none" disabled>' . $plc_htm . '</select></td>';
						$body_table .= '<td><span class="aps-' . $list['umeta_id'] . '">' . date( 'H:i', strtotime($val[1]) ) . '</span><input type="time" class="api-' . $list['umeta_id'] . '" name="dailystamp-list[apt-' . $list['umeta_id'] . '-' . $a . ']" value="' . date( 'H:i', strtotime($val[1]) ) . '" style="display:none" disabled/></td>';
					}
					$m = count($value);
					$v =  $lmt - $m;
					list( $plc_htm, $plc_html ) = stamp_shop_list(0);
					for($j = 1; $j <= $v; $j++){
						$a++;
						$body_table .= '<td><select class="pli-' . $list['umeta_id'] . '" name="dailystamp-list[pli-' . $list['umeta_id'] . '-' . $a . ']" style="display:none" disabled>' . $plc_htm . '</select></td>';
						$body_table .= '<td><input type="time" class="api-' . $list['umeta_id'] . '" name="dailystamp-list[apt-' . $list['umeta_id'] . '-' . $a . ']" value="" style="display:none" disabled/></td>';
					}
					$day1 = working_hours_1d( $list['meta_value'], $list['umeta_id'] );
					$total1[] = $day1;
					$body_table .= '<td>' . $day1 . '</td>';
					$body_table .= '<td><input id="viw-' . $list['umeta_id'] . '" type="button" class="button button-primary" value="表示" onclick="callBtn(this.id);"></td>';
					$body_table .= '</tr>';
				}
			}
		}
		$body_table .= <<< EOF
		<tr>
		<th colspan="3" class="l_sticky">合計&emsp;&emsp;(勤務人数{$n}人)</th>
		EOF;
		for($i = 2; $i <= $lmt; $i = $i + 2){
			$body_table .= '<th colspan="4"></th>';
		}
		if( !empty( $total1 ) ) {
			$t = count($total1);
		} else {
			$t = 0;
		}
		if($t!=0){
			$total = working_hhh_column($total1);
		} else {
			$total = '';
		}
		$body_table .= <<< EOF
		<th>{$total}</th>
		<th></th>
		</tr>
		EOF;
		$body_table .= '</table></div>';
		$body_table .= '<script>';
		$body_table .= <<< EOF
		function callBtn(id_value){
			switch(id_value.substr(0, 3)){
				case 'viw':
					document.getElementById(id_value).value = "キャンセル";
					var idchg = id_value.replace('viw', 'ccl');
					var idupt = id_value.replace('viw', 'upt');
					var iddel = id_value.replace('viw', 'del');
					var plbox = id_value.replace('viw', 'pli');
					var pllbl = id_value.replace('viw', 'pls');
					var apbox = id_value.replace('viw', 'api');
					var aplbl = id_value.replace('viw', 'aps');
					document.getElementById(id_value).id = idchg;
					jQuery('<button id="' + idupt + '" type="submit" class="button button-primary">更新</button>').insertBefore('#' + idchg);
					jQuery('<input id="' + iddel + '" type="button" class="button button-primary mr5" value="削除" onclick="stamping_delete(this.id);">').insertAfter('#' + idupt);
					jQuery('.' + plbox).show();
					jQuery('.' + plbox).prop('disabled', false);
					jQuery('.' + pllbl).hide();
					jQuery('.' + apbox).show();
					jQuery('.' + apbox).prop('disabled', false);
					jQuery('.' + aplbl).hide();
					break;
				case 'ccl':
					var idchg = id_value.replace('ccl', 'viw');
					var delid = id_value.replace('ccl', 'upt');
					var deli2 = id_value.replace('ccl', 'del');
					var plbox = id_value.replace('ccl', 'pli');
					var pllbl = id_value.replace('ccl', 'pls');
					var apbox = id_value.replace('ccl', 'api');
					var aplbl = id_value.replace('ccl', 'aps');
					document.getElementById(id_value).value = "表示";
					document.getElementById(id_value).id = idchg;
					jQuery('#' + delid).remove();
					jQuery('#' + deli2).remove();
					jQuery('.' + plbox).hide();
					jQuery('.' + plbox).prop('disabled', true);
					jQuery('.' + pllbl).show();
					jQuery('.' + apbox).hide();
					jQuery('.' + apbox).prop('disabled', true);
					jQuery('.' + aplbl).show();
					break;
			}
		}
		EOF;
		$body_table .='</script>';
		return $body_table;
	}

	public static function admin_init() {

		if ( isset( $_POST['dailystamp-list-page'] ) && $_POST['dailystamp-list-page'] ) {
			if ( check_admin_referer( 'dailystamp-nonce-key', 'dailystamp-list-page' ) ) {
				// 保存処理
				if ( isset( $_POST['dailystamp-list'] ) && $_POST['dailystamp-list'] ) {
					update_user_stamp_id( $_POST['dailystamp-list'] );
				}
			} 
		} 
	}

}

DailyStamp_List::init();
?>