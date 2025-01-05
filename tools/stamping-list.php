<?php

class Stamping_List {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10, 2 );
	}

	public static function add_menu() {
		$page_title = '打刻リスト';
		$menu_title = '打刻リスト';
		$capability = 'edit_pages';
		$menu_slug  = 'stamping-list';
		$function   = array( __CLASS__, 'list_page' );
		$icon_url	= 'dashicons-media-document';
		$position	= 5;
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public static function list_page() {

		if(isset($_GET['d1'])) { 
			$day1 = $_GET['d1']; 
		} else {
			//$day1 = date("Y-m-01", strtotime("-3 day"));
			$day1 = date("Y-m-d", strtotime("-7 day"));
		}

		if(isset($_GET['d2'])) { 
			$day2 = $_GET['d2']; 
		} else {
			//$day2 = date("Y-m-t", strtotime("-3 day"));
			$day2 = date("Y-m-d", strtotime("-1 day"));
		}

		echo header_css_html();
		
		$body_html = <<< EOF
		<script>
		function btn1Click(){
			var d1;
			d1 = document.getElementById("day1").value;
			var d2;
			d2 = document.getElementById("day2").value;
			window.open("admin.php?page=stamping-list&d1=" + d1 + "&d2=" + d2 + "","_parent");
		}
		</script>
		<h1>打刻リスト</h1>
		<p>集計開始日<input type="date" name="day1" id="day1" value="{$day1}">&emsp;集計終了日<input type="date" name="day2" id="day2" value="{$day2}">&emsp;
		<input type="button" class="button button-primary ml10" value="集計" onclick="btn1Click();">&emsp;<button type="button" class="button button-primary" id="dl-xlsx">Download XLSX</button></p>
		EOF;
		echo $body_html;

		$table1 = Stamping_List::my_table($day1, $day2);
		echo $table1;

		$wid = '[{ wpx : 50 },{ wpx : 72 },{ wpx : 72 },{ wpx : 144 },{ wpx : 121 },{ wpx : 121 }]';
		$fname = '打刻リスト.xlsx';
		echo footer_xlsx_html( $wid, $fname );

	}

	public static function my_table($b_date1, $b_date2) {
		global $wpdb;
		$body_table ='<div id="T_del" class="bill-list">';
		$body_table .='<table class="bill-list-table table-to-export" data-sheet-name="勤怠打刻">';
		$body_table .= <<< EOF
		<thead>
			<tr>
				<th>ID</th>
				<th>ユーザー</th>
				<th>営業日付</th>
				<th>勤務場所</th>
				<th>打刻時間(リアル)</th>
				<th>IPアドレス</th>
				<th>コメント</th>
			</tr>
		</thead>
		EOF;

		$date1 = str_replace("-", "", $b_date1);
		$date2 = str_replace("-", "", $b_date2);
		for ($i = $date1; $i <= $date2; $i++) {
			$key = 'stamp_' . $i;
			$lists = $wpdb->get_results(
				$wpdb->prepare( 
					"SELECT umeta_id, user_id, meta_key, meta_value FROM $wpdb->usermeta 
					WHERE meta_key = %s ORDER BY %d ", 
					$key, 1 ), 'ARRAY_A' );
			$n = count($lists);
			if($n!=0){
				foreach($lists as $list) {
					$user = get_userdata( $list['user_id'] );
					$value = unserialize( $list['meta_value'] );
					foreach($value as $val) {
						$body_table .= '<tr>';
						$body_table .= '<td>' . $list['umeta_id'] . '</td>';
						$body_table .= '<td>' . $user->display_name . '</td>';
						$body_table .= '<td>' . $i . '</td>';
						$body_table .= '<td>' . get_the_title( $val[0] ) . '</td>';
						$body_table .= '<td>' . $val[1] . '</td>';
						$body_table .= '<td>' . $val[2] . '</td>';
						$body_table .= '<td>' . nl2br($val[3]) . '</td>';
						$body_table .= '</tr>';
					}
				}
			}
		}

		$body_table .='</table></div>';
		return $body_table;
	}

}

Stamping_List::init();
?>