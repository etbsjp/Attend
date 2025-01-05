<?php

/*-------------------------------------------*/
/* ブロック
/*-------------------------------------------*/
function add_block_eta() {
	wp_enqueue_script(
		'eta_block-script', 
		get_template_directory_uri() . '/js/eta_block.js', 
		array( 'wp-blocks', 'wp-element') 
	);
}
add_action( 'enqueue_block_editor_assets', 'add_block_eta' );

/*-------------------------------------------*/
/* 打刻履歴
/*-------------------------------------------*/
function add_block_eta_view() {
	wp_enqueue_script(
		'eta_view_block-script', 
		get_template_directory_uri() . '/js/eta_view_block.js', 
		array( 'wp-blocks', 'wp-element') 
	);
}
add_action( 'enqueue_block_editor_assets', 'add_block_eta_view' );

/*-------------------------------------------*/
/* 打刻処理
/*-------------------------------------------*/
if ( ! function_exists( 'stamping_time' ) ){
	function stamping_time() {
		$user	 = wp_get_current_user();
		list( $mes, $txt )= explode( '||', $_POST['mes'] );
		$post_id = url_to_postid( $mes );
		$title   = get_the_title( $post_id );
		$now_timestamp = wp_date( 'Y-m-d H:i' );
		list( $ymd, $array ) = business_dates( $user->ID, $post_id, $txt );
		update_user_meta( $user->ID , 'stamp_' . $ymd , $array );
		if( $txt != '' ){
			$commentdata = array(
				'comment_author'       => $user->display_name, 
				'comment_author_email' => $user->user_email,
				'comment_content'      => $txt,
				'comment_date'         => $now_timestamp,
				'comment_type'         => 'comment',
				'comment_post_ID'      => $post_id,
				'user_id'              => $user->ID
			);
			wp_new_comment( $commentdata );
		}
		$msg	 = $now_timestamp . " に " . $title . ' で打刻しました。';
		echo $msg;
		die();
	}
	add_action( 'wp_ajax_stamping_time', 'stamping_time' );
	//add_action( 'wp_ajax_nopriv_stamping_time', 'stamping_time' );
}

/*-------------------------------------------*/
/* 直前の打刻表示
/*-------------------------------------------*/
if ( ! function_exists( 'stamplist_view' ) ){
	function stamplist_view() {
		global $wpdb;
		$user = wp_get_current_user();
		$lists = $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT meta_key, meta_value FROM $wpdb->usermeta 
				WHERE user_id = %s AND meta_key LIKE %s ORDER BY umeta_id DESC LIMIT 1 OFFSET 0", 
				$user->ID, 'stamp%' ), 'ARRAY_A' );
		$table = '<table>';
		foreach($lists as $list) {
			$ans = unserialize( $list['meta_value'] );
			foreach($ans as $an) {
				$tbl0 = '<tr>';
				$tbl0 .= '<td class="m19">' . get_the_title( $an[0] ) . '</td>';
				$tbl0 .= '<td>' . $an[1] . '</td>';
				$tbl0 .= '</tr>';
			}
		}
		$table .= $tbl0;
		$table .= '</table>';
		echo $table;
		die();
	}
	add_action( 'wp_ajax_stamplist_view', 'stamplist_view' );
}

/*-------------------------------------------*/
/* 手動打刻追加
/*-------------------------------------------*/
if ( ! function_exists( 'custom_stamping_time' ) ){
	function custom_stamping_time() {
		$mes     = $_POST['mes'];
		list( $uid, $pid, $day, $time ) = explode( '||', $mes );
		$ymd     = str_replace( '-', '', $day );
		$array[] = array( $pid, $day . ' ' . $time . ':00', $_SERVER['REMOTE_ADDR'], '手動追加' );
		update_user_meta( $uid , 'stamp_' . $ymd , $array );
		$user = wp_get_current_user();
		$log = array( wp_date( 'Y-m-d H:i' ), '手動追加', $user->ID, 'NEW', 'stamp_' . $ymd, '' );
		stamping_log( serialize( $log ) );
		die();
	}
	add_action( 'wp_ajax_custom_stamping_time', 'custom_stamping_time' );
}

/*-------------------------------------------*/
/* 手動打刻削除
/*-------------------------------------------*/
if ( ! function_exists( 'stamping_delete' ) ){
	function stamping_delete() {
		$mes = $_POST['mes'];
		$pos = strpos( $mes, 'del-' );
		if( $pos !== false ) {
			$umid = str_replace( 'del-', '', $mes );
		}
		delete_user_stampid( $umid );
		die();
	}
	add_action( 'wp_ajax_stamping_delete', 'stamping_delete' );
}

if ( ! function_exists( 'delete_user_stampid' ) ){
	function delete_user_stampid($umetaid) {
		global $wpdb;
		$lists = $wpdb->get_results(
					$wpdb->prepare( 
						"SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE umeta_id = %s", 
						$umetaid ), 'ARRAY_A' );
		$user = wp_get_current_user();
		$log = array( wp_date( 'Y-m-d H:i' ), '手動削除', $user->ID, $umetaid, $lists[0]['meta_key'], $lists[0]['meta_value'] );
		stamping_log( serialize( $log ) );
		$lists = $wpdb->get_results(
					$wpdb->prepare( 
						"DELETE FROM $wpdb->usermeta WHERE umeta_id = %s", 
						$umetaid ), 'ARRAY_A' );
	}
}

/*-------------------------------------------*/
/* 営業日付の判定
/*-------------------------------------------*/
if ( ! function_exists( 'business_dates' ) ){
	function business_dates( $user_id, $post_id, $txt ) {
		$options = get_option( 'attend-setting', Attend_Admin::options_default() );
		$lmt = $options['upper-limit'];
		$now = wp_date( 'Y-m-d H:i:s' );
		$stt = wp_date( 'Ymd' ) . ' ' . $options['start-time'] . ':00';
		$cls = wp_date( 'Ymd' ) . ' ' . $options['closing-time'] . ':00';
		if( strtotime($now) < strtotime($cls) ) {
			if( strtotime($now) > strtotime($stt) ) {	
				$ymd   = wp_date( 'Ymd', strtotime("-1 day") );
				$stamp = get_user_meta( $user_id , 'stamp_' . $ymd , true );
				$c = count($stamp);
				if($c!=0){
					if( $c % 2 == 0 ) {
						$ymd = wp_date( 'Ymd' );
					} else {
						$ymd = wp_date( 'Ymd', strtotime("-1 day") );
					}
				} else {
					$ymd = wp_date( 'Ymd' );
				}
			} else {
				$ymd = wp_date( 'Ymd', strtotime("-1 day") );
			}
		} else {
			$ymd = wp_date( 'Ymd' );
		}
		$array = business_date_stamp( $user_id, $ymd, $now, $lmt, $post_id, $txt );
		return array( $ymd, $array );
	}
}
if ( ! function_exists( 'business_date_stamp' ) ){
	function business_date_stamp( $user_id, $ymd, $now, $lmt, $post_id, $txt ) {
		$stamp = get_user_meta( $user_id , 'stamp_' . $ymd , true );
		if( $stamp ) {
			$cnt = count($stamp);
			if( $cnt == $lmt ) {
				$t = $lmt - 1;
				$stamp[$t] = array( $post_id, $now, $_SERVER['REMOTE_ADDR'], $txt );
			} else {
				$stamp[] = array( $post_id, $now, $_SERVER['REMOTE_ADDR'], $txt );
			}
			$array = $stamp;
		} else {
			$array[] = array( $post_id, $now, $_SERVER['REMOTE_ADDR'], $txt );
		}
		return $array;
	}
}

/*-------------------------------------------*/
/* 1日の勤務時間計算
/*-------------------------------------------*/
if ( ! function_exists( 'working_hours_1d' ) ){
	function working_hours_1d( $tmrry, $umetaid ){
		$value = unserialize( $tmrry );
		$c = count($value);
		if( $c % 2 == 0 ) {
			$options = get_option( 'attend-setting', Attend_Admin::options_default() );
			foreach($value as $val) {
				$timey[] = $val[1];
			}
			if( isset($timey) ) {
				$t = count($timey);
			} else {
				$t = 0;
			}
			$ans = '';
			switch( $options['rounding-of-time'] ){
				case 0:
					for($i = 0; $i < $t; $i = $i + 2) {
						$time1 = strtotime( $timey[$i] );
						$time2 = strtotime( $timey[$i + 1] );
						$diff = $time2 - $time1;
						$chks[] = intval($diff / 60 / 60) . ':' . substr('0' . ($diff / 60 % 60), -2);
					}
					if( isset($chks) ) {
						$ans = working_hhh_row( $chks, $umetaid );
					}
					break;
				case 1:
					for($i = 0; $i < $t; $i = $i + 2) {
						$x = substr(date('Y-m-d H:i', strtotime( $timey[$i].'+10 min' )), -1);
						if($x!=0) {
							$time1 = strtotime( substr(date('Y-m-d H:i', strtotime( $timey[$i].'+10 min' )), 0, 15) . '0:00' );
						} else {
							$time1 = strtotime( substr(date('Y-m-d H:i', strtotime( $timey[$i] )), 0, 15) . '0:00' );
						}
						$time2 = strtotime( substr(date('Y-m-d H:i', strtotime( $timey[$i + 1] )), 0, 15) . '0:00' );
						$diff = $time2 - $time1;
						$chks[] = intval($diff / 60 / 60) . ':' . substr('0' . ($diff / 60 % 60), -2);
					}
					if( isset($chks) ) {
						$ans = working_hhh_row( $chks, $umetaid );
					}
					break;
				case 2:
					for($i = 0; $i < $t; $i = $i + 2) {
						$S = intval(date( 'i', strtotime( $timey[$i] ) ));
						switch( $S ) {
							case $S > 0 && $S <= 14:
								$time1 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i] )), 0, 13) . ':15:00' );
								break;
							case $S >= 15 && $S <= 29:
								$time1 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i] )), 0, 13) . ':30:00' );
								break;
							case $S >= 30 && $S <= 44:
								$time1 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i] )), 0, 13) . ':45:00' );
								break;
							case $S >= 45 && $S <= 59:
							case $S == 0:
								$time1 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i].'+60 min' )), 0, 13) . ':00:00' );
								break;
						}
						$E = intval(date( 'i', strtotime( $timey[$i + 1] ) ));
						switch( $E ) {
							case $E == 0:
							case $E > 0 && $E <= 14:
								$time2 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i + 1] )), 0, 13) . ':00:00' );
								break;
							case $E >= 15 && $E <= 29:
								$time2 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i + 1] )), 0, 13) . ':15:00' );
								break;
							case $E >= 30 && $E <= 44:
								$time2 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i + 1] )), 0, 13) . ':30:00' );
								break;
							case $E >= 45 && $E <= 59:
								$time2 = strtotime( substr(date('Y-m-d H', strtotime( $timey[$i + 1] )), 0, 13) . ':45:00' );
								break;
						}
						$diff = $time2 - $time1;
						$chks[] = intval($diff / 60 / 60) . ':' . substr('0' . ($diff / 60 % 60), -2);
					}
					if( isset($chks) ) {
						$ans = working_hhh_row( $chks, $umetaid );
					}
					break;
			}
			return $ans;
		} else {
			return '要打刻修正';
		}
	}
}

/*-------------------------------------------*/
/* 時間の合計(行)
/*-------------------------------------------*/
if ( ! function_exists( 'working_hhh_row' ) ){
	function working_hhh_row( $chks, $umetaid ){
		$s = count($chks);
		if($s!=1) {
			$hh = 0;
			$mm = 0;
			foreach($chks as $chk) {
				$hh += intval(substr($chk, 0, 2));
				$mm += intval(substr($chk, -2));
			}
			if($mm >= 60) {
				$mh = floor($mm / 60);
				$hh = $hh + $mh;
				$mm = $mm % 60;
			}
			$ans = $hh . ':' . substr('0' . $mm, -2);
		} else {
			$ans = $chks[0];
		}
		return $ans;
	}
}

/*-------------------------------------------*/
/* 時間の合計(列)
/*-------------------------------------------*/
if ( ! function_exists( 'working_hhh_column' ) ){
	function working_hhh_column( $chks ){
		$s = count($chks);
		if($s!=1) {
			$hh = 0;
			$mm = 0;
			foreach($chks as $chk) {
				$hh += intval(substr($chk, 0, 2));
				$mm += intval(substr($chk, -2));
			}
			if($mm >= 60) {
				$mh = floor($mm / 60);
				$hh = $hh + $mh;
				$mm = $mm % 60;
			}
			$ans = $hh . ':' . substr('0' . $mm, -2);
		} else {
			$ans = $chks[0];
		}
		return $ans;
	}
}

/*-------------------------------------------*/
/* umeta_id ベースで更新
/*-------------------------------------------*/
if ( ! function_exists( 'update_user_stamp_id' ) ){
	function update_user_stamp_id( $array ) {
		global $wpdb;
		$options = get_option( 'attend-setting', Attend_Admin::options_default() );
		$a = 1;
		$ans = array();
		foreach( $array as $key => $val ){
			$moto = explode("-", $key);
			if($a % 2 == 0) {
				if($place != 0) {
					//$time1 = strtotime( $old );
					$time2 = strtotime( $day . ' ' . $val );
					$day2 = date( 'Y-m-d', strtotime( $day . '+1 day' ) );
					$time3 = strtotime( $day . ' ' . $options['closing-time']);
					//$diff  = $time2 - $time1;
					$diff  = $time2 - $time3;
					if($diff >= 0) {
						$ans[] = array( $place, $day . ' ' . $val . ':00', $_SERVER['REMOTE_ADDR'], '手動更新' );
						//$old = $day . ' ' . $val;
						$sort[] = strtotime( $day . ' ' . $val );
					} else {
						$day2 = date( 'Y-m-d', strtotime( $day . '+1 day' ) );
						$ans[] = array( $place, $day2 . ' ' . $val . ':00', $_SERVER['REMOTE_ADDR'], '手動更新' );
						//$old = $day2 . ' ' . $val;
						$sort[] = strtotime( $day2 . ' ' . $val );
					}
				}
			} else {
				if($a == 1) {
					$umetaid = $moto[1];
					$lists = $wpdb->get_results(
						$wpdb->prepare( 
							"SELECT umeta_id, user_id, meta_key, meta_value FROM $wpdb->usermeta 
							WHERE umeta_id = %s ORDER BY %d ", 
							$umetaid, 1 ), 'ARRAY_A' );
					$day = str_replace("stamp_", "", $lists[0]['meta_key'] );
					$day = substr( $day, 0, 4 ) . '-' . substr( $day, 4, 2 ) . '-' . substr( $day, 6, 2 );
					$userid = $lists[0]['user_id'];
					$stampymd = $lists[0]['meta_key'];
					$user = wp_get_current_user();
					$log = array( wp_date( 'Y-m-d H:i' ), '手動更新', $user->ID, $umetaid, $stampymd, $lists[0]['meta_value'] );
					$place = $val;
					//$old = $day . ' ' . $options['start-time'] . ':00';
				} else {
					$place = $val;
				}
			}
			$a++;
		}
		array_multisort($sort, $ans);
		update_user_meta( $userid , $stampymd , $ans );
		stamping_log( serialize( $log ) );
	}
}

/*-------------------------------------------*/
/* umeta_id から user_id を取得
/*-------------------------------------------*/
if ( ! function_exists( 'get_stamp_user_id' ) ){
	function get_stamp_user_id( $umetaid ) {
		global $wpdb;
		$udt = $wpdb->get_results(
			$wpdb->prepare( 
				"SELECT user_id FROM $wpdb->usermeta 
				WHERE umeta_id = %s ORDER BY %d ", 
				$umetaid, 1 ), 'ARRAY_A' );
		$uid = $udt[0]['user_id'];
		return $uid;
	}
}

/*-------------------------------------------*/
/* 今現在の所属を取得
/*-------------------------------------------*/
if ( ! function_exists( 'get_now_main_place' ) ){
	function get_now_main_place( $uid, $day ) {
		$my_place = get_user_meta( $uid, 'main_place', false );
		if ( is_array( $my_place ) ) {
			$c = count( $my_place );
			$chk = 0;
			if($c!=0) {
				foreach( $my_place as $place ) {
					foreach($place as $plc) {
						$val = unserialize( $plc );
						$diff = strtotime($day) - strtotime($val[0]);
						if($diff >= 0 || $chk == 0) {
							$my_place_id = $val[1];
							$chk++;
						}
					}
				}
			}
		} else {
			$my_place_id = 0;
		}
		$syozoku = get_the_title( $my_place_id );
		return $syozoku;
	}
}

/*-------------------------------------------*/
/* 勤務場所の選択肢の中身
/*-------------------------------------------*/
if ( ! function_exists( 'stamp_shop_list' ) ){
	function stamp_shop_list( $pid ) {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'title',
		);
		$place_posts = get_posts( $args );
		$plc_htm = '';
		if ( $place_posts ) {
			foreach ( $place_posts as $key => $post ) {
				if( $post->ID == $pid ) {
					$flag = ' selected';
				} else {
					$flag = '';
				}
				$plc_htm .= '<option value="' . $post->ID . '"' . $flag . '>' . $post->post_title . '</option>';
			}
			$plc_htm1 = '<option value="0">--</option>' . $plc_htm;
			$plc_htm2 = '<option value="0">全体</option>' . $plc_htm;
		} else {
			$plc_htm1 = '<option value="0">登録なし</option>';
			$plc_htm2 = '<option value="0">勤務場所が登録されていません</option>';
		}
		return array( $plc_htm1, $plc_htm2 );
	}
}

/*-------------------------------------------*/
/* ユーザーリストの選択肢の中身
/*-------------------------------------------*/
if ( ! function_exists( 'stamp_users_list' ) ){
	function stamp_users_list( $uid ) {
		$args = array(
			'role__in' => array( 'administrator', 'ea_jinji', 'ea_itaku', 'ea_user' ), 
			'orderby'  => 'ID',
			'order'    => 'ASC', 
		);
		$stamp_users = get_users( $args );
		$uid_htm = '';
		$uid_html = '';
		if ( $stamp_users ) {
			foreach ( $stamp_users as $key => $usr ) {
				if( $usr->ID == $uid ) {
					$flag = ' selected';
				} else {
					$flag = '';
				}
				$uid_htm .= '<option value="' . $usr->ID . '"' . $flag . '>' . $usr->display_name . '</option>';
			}
			$uid_htm  = '<option value="0">--</option>' . $uid_htm;
		} else {
			$uid_htm = '<option value="0">登録なし</option>';
			$uid_html = '<option value="0">ユーザーが登録されていません</option>';
		}
		return array( $uid_htm, $uid_html );
	}
}

/*-------------------------------------------*/
/* ヘッダーCSS出力
/*-------------------------------------------*/
if ( ! function_exists( 'header_css_html' ) ){
	function header_css_html() {
		$header_html = <<< EOF
		<style type="text/css">
		.rt { text-align: right; }
		.bill-list-table{
		border-collapse: separate;
		border-spacing: 0px;
		border-top: 1px solid #ccc;
		border-left: 1px solid #ccc;
		}
		.bill-list-table th{
		padding: 4px;
		text-align: left;
		vertical-align: top;
		color: #444;
		background-color: #ccc;
		border-top: 1px solid #fff;
		border-left: 1px solid #fff;
		border-right: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		}
		.bill-list-table td{
		padding: 4px;
		background-color: #fafafa;
		border-right: 1px solid #ccc;
		border-bottom: 1px solid #ccc;
		}
		.bill-list-table tr:nth-child(even) td{
		background-color: #f0f0f3;
		}
		.ml10 {margin-left: 10px!important;}
		</style>
		EOF;
		return $header_html;
	}
}

/*-------------------------------------------*/
/* エクセル出力
/*-------------------------------------------*/
if ( ! function_exists( 'footer_xlsx_html' ) ){
	function footer_xlsx_html( $wid, $fname ) {
		$footer_html = '<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.9.10/xlsx.full.min.js"></script>';
		$footer_html .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.3/FileSaver.min.js"></script>';
		$footer_html .= <<< EOF
		<script>
		document.getElementById("dl-xlsx").addEventListener("click", function () {
		var wopts = {
			bookType: "xlsx",
			bookSST: false,
			type: "binary"
		};

		var workbook = {SheetNames: [], Sheets: {}};

		document.querySelectorAll("table.table-to-export").forEach(function (currentValue, index) {
			// sheet_to_workbook()の実装を参考に記述
			var n = currentValue.getAttribute("data-sheet-name");
			if (!n) {
			n = "Sheet" + index;
			}
			workbook.SheetNames.push(n);
			workbook.Sheets[n] = XLSX.utils.table_to_sheet(currentValue, wopts);
			workbook["Sheets"][n]["!cols"] = {$wid};
		});

		var wbout = XLSX.write(workbook, wopts);

		function s2ab(s) {
			var buf = new ArrayBuffer(s.length);
			var view = new Uint8Array(buf);
			for (var i = 0; i != s.length; ++i) {
			view[i] = s.charCodeAt(i) & 0xFF;
			}
			return buf;
		}

		saveAs(new Blob([s2ab(wbout)], {type: "application/octet-stream"}), "{$fname}");
		}, false);
		</script>
		EOF;
		return $footer_html;
	}
}

/*-------------------------------------------*/
/* ログ登録
/*-------------------------------------------*/
if ( ! function_exists( 'stamping_log' ) ){
	function stamping_log( $log ) {
		$options = get_option( 'stamping-log' );
		if ( is_array($options) ) {
			$cnt = count( $options );
			if( $cnt >= 20 ) {
				$fruit = array_shift( $options );
			}
		} else {
			$options = array();
		}
		$options[] = $log;
		update_option( 'stamping-log', $options );
	}
}

?>