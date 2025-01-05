<?php

class StampLog_List {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ), 10, 2 );
	}

	public static function add_menu() {
		$page_title = '修正操作ログ';
		$menu_title = '修正操作ログ';
		$capability = 'edit_pages';
		$menu_slug  = 'stamplog-list';
		$function   = array( __CLASS__, 'list_page' );
		$icon_url	= 'dashicons-media-document';
		$position	= 9;
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	}

	public static function list_page() {
		echo header_css_html();

		$body_html = <<< EOF
		<h1>修正操作ログ</h1>
		<p><button type="button" class="button button-primary" id="dl-xlsx">Download XLSX</button>
		&emsp;<span style="font-size:10px">※保存されているのは直近20件前後です。</span></p>
		EOF;
		echo $body_html;

		$table1 = StampLog_List::my_table();
		echo $table1;

		$wid = '[{ wpx : 121 },{ wpx : 72 },{ wpx : 72 },{ wpx : 72 },{ wpx : 121 },{ wpx : 121 }]';
		$fname = '操作ログ.xlsx';
		echo footer_xlsx_html( $wid, $fname );

	}

	public static function my_table() {
		$options = get_option( 'stamping-log' );
		if ( is_array($options) ) {
			$body_table ='<div id="T_del" class="bill-list">';
			$body_table .='<table class="bill-list-table table-to-export" data-sheet-name="勤怠打刻">';
			$body_table .= <<< EOF
			<thead>
				<tr>
					<th>操作日時</th>
					<th>操作内容</th>
					<th>操作ユーザー</th>
					<th>umeta_id</th>
					<th>meta_key</th>
					<th>修正前データ</th>
				</tr>
			</thead>
			EOF;
			foreach( $options as $value ){
				$vals = unserialize( $value );
				$body_table .='<tr>';
				$c = 0;
				$sosa = "none";
				foreach( $vals as $val ) {
					$c++;
					$body_table .= '<td>';
					if( $c == 6 && ( $sosa == '手動更新' || $sosa == '手動削除' ) ){
						$lists = unserialize( $val );
						$body_table .= '<ol>';
						foreach( $lists as $list ) {
							$body_table .= '<li><ul>';
							foreach( $list as $lst ) {
								$body_table .= '<li>' . nl2br($lst) . '</li>';
							}
							$body_table .= '</ul></li>';
						}
						$body_table .= '</ol>';
					} elseif( $c == 2 ) {
						$sosa = $val;
						$body_table .= $val;
					} else {
						$body_table .= $val;
					}
					$body_table .= '</td>';
				}
				$body_table .='</tr>';
			}
			$body_table .='</table></div>';
		} else {
			$body_table = '<p>ログデータはまだありません。</p>';
		}
		return $body_table;
	}

}

StampLog_List::init();
?>