<?php
/*-------------------------------------------*/
/* ユーザーロールを追加
/*-------------------------------------------*/
function eta_add_role(){
	if ( ! get_role( 'ea_jinji' ) ) {
		$capabilities = array(
			'level_4' => 1, 
			'level_3' => 1, 
			'level_2' => 1, 
			'level_1' => 1, 
			'level_0' => 1, 
			'publish_posts' => 1, 
			'edit_posts' => 1, 
			'edit_others_posts' => 1, 
			'edit_published_posts' => 1, 
			'edit_private_posts' => 1, 
			'delete_posts' => 1, 
			'delete_others_posts' => 1, 
			'delete_published_posts' => 1, 
			'delete_private_posts' => 1, 
			'edit_private_posts' => 1, 
			'read_private_posts' => 1, 
			'manage_categories' => 1, 
			'publish_pages' => 1, 
			'edit_pages' => 1, 
			'edit_others_pages' => 1, 
			'edit_published_pages' => 1, 
			'edit_private_pages' => 1, 
			'delete_pages' => 1, 
			'delete_others_pages' => 1, 
			'delete_published_pages' => 1, 
			'delete_private_pages' => 1, 
			'read' => 1, 
			'read_private_pages' => 1, 
			'create_users' => 1,
			'edit_users' => 1,
			'list_users' => 1,
			'promote_users' => 1,
			'remove_users' => 1,
			'delete_users' => 1
		);
		add_role( 'ea_jinji', '人事・給与担当者', $capabilities );
	}

	if ( ! get_role( 'ea_itaku' ) ) {
		$capabilities = array(
			'read' => 1,
			'read_private_posts' => 1,
			'read_private_pages' => 1
		);
		add_role( 'ea_itaku', 'パート・アルバイト', $capabilities );
	}

	if ( ! get_role( 'ea_user' ) ) {
		$capabilities = array(
			'read' => 1,
			'read_private_posts' => 1,
			'read_private_pages' => 1
		);
		add_role( 'ea_user', '社員', $capabilities );
	}

	if ( ! get_role( 'ea_oldman' ) ) {
		$capabilities = array(
			'read' => 1
		);
		add_role( 'ea_oldman', '退職者', $capabilities );
	}

}
add_action("init", "eta_add_role");

function eta_remove_role(){
	remove_role('ea_jinji');
	remove_role('ea_itaku');
	remove_role('ea_user');
	remove_role('ea_oldman');
}
//add_action("init", "eta_remove_role");

/*-------------------------------------------*/
/* ユーザー情報に所属情報
/*-------------------------------------------*/
if ( ! function_exists( 'place_add_user_form' ) ){
	function place_add_user_form( $profileuser ) {
		$my_place_id = 0;
		$my_place = get_user_meta( $profileuser->ID, 'main_place', false );
		if ( is_array( $my_place ) ) {
			$c = count( $my_place );
			if($c!=0) {
				$my_place_lists = $my_place[ $c - 1 ];
				$c = count( $my_place_lists );
				if($c!=0) {
					$my_place_list = unserialize( $my_place_lists[ $c - 1 ] );
					$my_place_id = $my_place_list[1];
				}
			}
		} 
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => -1,
			'order'          => 'ASC',
			'orderby'        => 'title',
		);
		$place_posts = get_posts( $args );
		if ( $place_posts ) {
			$plc_html = '<option value="">選択してください</option>';
			foreach ( $place_posts as $key => $post ) {
				if( $post->ID == $my_place_id ) {
					$flag = ' selected';
				} else {
					$flag = '';
				}
				$plc_html .= '<option value="' . $post->ID . '"' . $flag . '>' . $post->post_title . '</option>';
			}
		} else {
			$plc_html = '<option value="0">勤務場所が登録されていません</option>';
		}
		?>
		<h3>所属・メインの勤務場所</h3>
		<table class="form-table">
			<tr class="user-main-place">
				<th><label for="main_place">勤務場所</label></th>
				<td><select name="main_place">
					<?php echo $plc_html;?>
				</select></td>
			</tr>
		</table>
		<?php
	}
	add_action( 'show_user_profile', 'place_add_user_form' );
	add_action( 'edit_user_profile', 'place_add_user_form' );
}
if ( ! function_exists( 'place_user_form_save' ) ){
	function place_user_form_save( $user_id ) {
		$meta_keys = array(
			'main_place',
		);
		foreach ( $meta_keys as $key ) {
			if( isset( $_POST[ $key ] ) ) {
				if ( $_POST[ $key ] ) {
					if ( is_numeric( $_POST[ $key ] ) ) {
						$now_timestamp = wp_date( 'Y-m-d H:i' );
						$my_place = get_user_meta( $user_id, $key, true );
						if ( is_array( $my_place ) ) {
							$cnt = count( $my_place );
							if( $cnt >= 30 ) {
								$fruit = array_shift( $my_place );
							}
						} else {
							$my_place = array();
						}
						$my_place[] = serialize( array( $now_timestamp, $_POST[ $key ] ) );
						update_user_meta( $user_id, $key, $my_place );
					}
				} else {
					delete_user_meta( $user_id, $key );
				}
			}
		}
	}
	add_action( 'profile_update', 'place_user_form_save' );
}

?>