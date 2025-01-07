<?php
/*-------------------------------------------*/
/*  更新通知を管理者のみ表示
/*-------------------------------------------*/
function update_nag_admin_only() {
	if ( ! current_user_can( 'administrator' ) ) {
		remove_action( 'admin_notices', 'update_nag', 3 );
	}
}
add_action( 'admin_init', 'update_nag_admin_only' );

/*-------------------------------------------*/
/*  JS・CSSファイルを追加
/*-------------------------------------------*/
if ( ! function_exists( 'attend_styles' ) ){
	function attend_styles() {
		wp_enqueue_style( 'main', get_template_directory_uri() . '/css/main.css', array(), '1.0.0');
		wp_enqueue_script( 'appf', get_template_directory_uri() . '/js/appf.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'ajjs', get_template_directory_uri() . '/js/ajjs.php', array( 'jquery' ), '1.0.0', true );
	}
	function attend_adm_styles() {
		$_custom_files = '';
		$_custom_files = attend_adm_ss( $_custom_files, 'ajjs.php' );
		echo $_custom_files;
	}
	function attend_adm_ss($_custom_files, $_file_name){
		$_current_theme_dir = get_template_directory_uri();
		$_custom_files .= '<script type="text/javascript" src="' .$_current_theme_dir . '/js/' . $_file_name . '?ver=1.0.0' . '"></script>';
		return $_custom_files."\n";
	}
	add_action( 'wp_enqueue_scripts', 'attend_styles');
	add_action( 'admin_footer', 'attend_adm_styles' );
}

/*-------------------------------------------*/
/* No login redirect
/*-------------------------------------------*/
if ( ! function_exists( 'eta_no_login_redirect' ) ){
	function eta_no_login_redirect( $content ) {
		global $pagenow;
		$s_url = $_SERVER['REQUEST_URI'];
		if( !is_user_logged_in() && !is_admin() && ( $pagenow != 'wp-login.php' ) && php_sapi_name() !== 'cli' && strstr($s_url,'wp-cron.php')==false ){
			//auth_redirect();
			$url = wp_login_url( $s_url );
			header( "Location: {$url}" );
			exit;
		}
	}
	add_action( 'init', 'eta_no_login_redirect' );
}

/*-------------------------------------------*/
/* メニュー名称を変更
/*-------------------------------------------*/
if ( ! function_exists( 'change_post_menu_label' ) ){
	function change_post_menu_label() {
		global $menu;
		global $submenu;
		$menu[5][0] = '勤務場所';
		$submenu['edit.php'][5][0] = '場所一覧';
		$submenu['edit.php'][10][0] = '新規追加';
	}
	add_action('admin_menu', 'change_post_menu_label');
}

/*-------------------------------------------*/
/* Load Module
/*-------------------------------------------*/
require_once( dirname( __FILE__ ) . '/inc/user-role.php' );
require_once( dirname( __FILE__ ) . '/inc/setting-page.php' );
require_once( dirname( __FILE__ ) . '/inc/func.php' );
require_once( dirname( __FILE__ ) . '/tools/stamping-list.php' );
require_once( dirname( __FILE__ ) . '/tools/dailystamp-list.php' );
require_once( dirname( __FILE__ ) . '/tools/stamplog-list.php' );

require 'inc/plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/etbsjp/Attend/',
	__FILE__,
	'Attend'
);
$myUpdateChecker->setBranch( 'dist' );

?>