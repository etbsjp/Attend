<?php

class Attend_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 10, 2 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ), 10, 2 );
	}

	public static function add_admin_menu() {
		$page_title = '勤怠打刻設定';
		$menu_title = '勤怠打刻設定';
		$capability = 'edit_pages';
		$menu_slug  = 'attend-setting-page';
		$function   = array( __CLASS__, 'setting_page' );
		// $icon_url	= '';
		// $position	= '';
		add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function );
	}

	public static function setting_page() {
		?>
		<h1>勤怠打刻設定</h1>
		<div class="wrap">
		<form id="attend-setting-form" method="post" action="">
		<?php wp_nonce_field( 'attend-nonce-key', 'attend-setting-page' ); ?>
		<?php
			$options = get_option( 'attend-setting', Attend_Admin::options_default() );
			$stt_html = '';
			for($i = 0; $i < 24; $i++){
				if( $options['start-time'] == substr('0' . $i, -2) . ':00' ){
					$stt_html .= '<option value="' . substr('0' . $i, -2) . ':00" selected>' . substr('0' . $i, -2) . ':00</option>';
				} else {
					$stt_html .= '<option value="' . substr('0' . $i, -2) . ':00">' . substr('0' . $i, -2) . ':00</option>';
				}
			}
			$clt_html = '';
			for($i = 0; $i < 24; $i++){
				if( $options['closing-time'] == substr('0' . $i, -2) . ':00' ){
					$clt_html .= '<option value="' . substr('0' . $i, -2) . ':00" selected>' . substr('0' . $i, -2) . ':00</option>';
				} else {
					$clt_html .= '<option value="' . substr('0' . $i, -2) . ':00">' . substr('0' . $i, -2) . ':00</option>';
				}
			}
			$rot_html = '';
			$st1 = $st2 = $st3 = '';
			switch( $options['rounding-of-time'] ){
				case 0:
					$st1 = ' selected';
					break;
				case 1:
					$st2 = ' selected';
					break;
				case 2:
					$st3 = ' selected';
					break;
			}
			$rot_html .= '<option value="0"' . $st1 . '>実数</option>';
			$rot_html .= '<option value="1"' . $st2 . '>10分</option>';
			$rot_html .= '<option value="2"' . $st3 . '>15分</option>';
			$ult_html = '';
			for($i = 2; $i <= 8; $i = $i + 2){
				if( $options['upper-limit'] == $i ){
					$ult_html .= '<option value="' . $i . '" selected>' . $i  . '</option>';
				} else {
					$ult_html .= '<option value="' . $i . '">' . $i . '</option>';
				}
			}
		?>
		<table class="form-table">
			<tr>
				<th>営業日付開始時間</th>
				<td><select name="attend-setting[start-time]">
					<?php echo $stt_html;?>
				</select></td>
			</tr>
			<tr>
				<th>営業日付終了時間</th>
				<td><select name="attend-setting[closing-time]">
					<?php echo $clt_html;?>
				</select></td>
			</tr>
			<tr>
				<th>時間の丸め</th>
				<td><select name="attend-setting[rounding-of-time]">
					<?php echo $rot_html;?>
				</select></td>
			</tr>
			<tr>
				<th>1日の打刻上限</th>
				<td><select name="attend-setting[upper-limit]">
					<?php echo $ult_html;?>
				</select></td>
			</tr>
		</table>
		<p><input type="submit" value="設定を保存" class="button button-primary button-large"></p>
		</form>
		</div>
		<?php 
			if(isset($_POST['attend-setting-page'])) {
				if( true == $_POST['attend-setting-page']){ 
        			echo '<div id="settings_updated" class="updated notice is-dismissible"><p><strong>設定を保存しました。</strong></p></div>';
				}
			}
	}

	public static function options_default() {
		$default = array(
			'start-time'       => '07:00',
			'closing-time'     => '09:00',
			'rounding-of-time' => '1',
			'upper-limit'      => '6',
		);
		return $default;
	}

	public static function admin_init() {

		if ( isset( $_POST['attend-setting-page'] ) && $_POST['attend-setting-page'] ) {

			if ( check_admin_referer( 'attend-nonce-key', 'attend-setting-page' ) ) {
				// 保存処理
				if ( isset( $_POST['attend-setting'] ) && $_POST['attend-setting'] ) {
					update_option( 'attend-setting', $_POST['attend-setting'] );
				} else {
					update_option( 'attend-setting', '' );
				}
				$user = wp_get_current_user();
				$log = array( wp_date( 'Y-m-d H:i' ), '設定変更', $user->ID, '', '', '' );
				stamping_log( serialize( $log ) );
				// wp_safe_redirect( menu_page_url( 'attend-setting-page', false ) );
			}
		}
	}

}

Attend_Admin::init();
?>