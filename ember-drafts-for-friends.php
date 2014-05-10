<?php
/*
Plugin Name: Ember Drafts for Friends
Plugin URI: http://automattic.com/
Description: Now you can add expiration date to posts and pages easily! Now powered by Ember!
Author: Rodrigo Iloro
Version: 0.1
Author URI: http://rodrigo.iloro.net/
*/

class EmberDraftsForFriends {
	function __construct(){
		add_action('init', array($this, 'init'));
	}

	function init(){
		global $wpdb;

		$wpdb->ember_drafts_for_friends = $wpdb->prefix . 'ember_drafts_for_friends';
		$wpdb->drafts_for_friends = $wpdb->prefix . 'drafts_for_friends';

		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_ember_drafts_for_friends', array( $this, 'admin_rest_actions' ) );
	}

	function admin_menu() {
		add_submenu_page( "edit.php", __( 'Drafts for Ember', 'draftsforfriends'), __('Drafts for Ember', 'draftsforfriends' ), 'publish_posts', __FILE__ , array( $this, 'render_admin_page' ) );
	}

	function admin_scripts( $hook ) {
		if( $hook == 'posts_page_ember-drafts-for-friends/ember-drafts-for-friends' ) {
			wp_enqueue_style( 'draftsforfriends', plugins_url( 'css/drafts-for-friends.css', __FILE__, false, '0.1' ) );

			wp_enqueue_script( 'ZeroClipboard', plugins_url( 'js/lib/ZeroClipboard.js', __FILE__ ), array( 'jquery' ), '0.1', true );
			wp_enqueue_script( 'moment', plugins_url( 'js/lib/moment.min.js', __FILE__ ), array( 'jquery' ), '1.1.2', true );
			wp_enqueue_script( 'handlebars', plugins_url( 'js/lib/handlebars-1.1.2.js', __FILE__ ), array( 'jquery' ), '1.1.2', true );
			wp_enqueue_script( 'ember', plugins_url( 'js/lib/ember-1.5.1.js', __FILE__ ), array( 'jquery' ), '1.5.1', true );
			wp_enqueue_script( 'ember_data', plugins_url( 'js/lib/ember-data.js', __FILE__ ), array( 'ember' ), '1.5.1', true );
			wp_enqueue_script( 'draftsforfriends', plugins_url( 'js/app.js', __FILE__ ), array( 'jquery', 'ember', 'ember_data' ), '0.1', true );

			wp_localize_script( 'draftsforfriends', 'draftsForFriends', array(
				'ajax_endpoint' => admin_url('admin-ajax.php'),
				'blog_url' => get_bloginfo( 'url' )
			));

		}
	}

	function get_shared() {
		global $wpdb, $current_user;
		return $wpdb->get_results( $wpdb->prepare( "SELECT d.*, p.post_title AS post_title FROM $wpdb->drafts_for_friends d INNER JOIN $wpdb->posts p ON d.post_id = p.id WHERE user_id = %d", intval( $current_user->ID ) ) );
	}

	function get_drafts() {
		$drafts    = get_posts( array( 'post_status' => 'draft' ) );
		$pending   = get_posts( array( 'post_status' => 'pending' ) );
		$scheduled = get_posts( array( 'post_status' => 'future' ) );

		foreach ($drafts as &$draft) {
			$draft->post_status_category = __( 'Your Drafts:', 'draftsforfriends' );
		}

		foreach ($pending as &$draft) {
			$draft->post_status_category = __( 'Your Scheduled Posts:', 'draftsforfriends' );
		}

		foreach ($scheduled as &$draft) {
			$draft->post_status_category = __( 'Pending Review:', 'draftsforfriends' );
		}

		return array_merge( $drafts, $pending, $scheduled ); 
	}

	function create_shared_draft( $post_id, $expiration, $measure ) {
		global $wpdb, $current_user;
		$post = get_post( intval( $post_id ) );

		$expires = intval( $expiration );
		if( $expires == 0 ) {
			return array( 'error' => __( 'Invalid Expiration.', 'draftsforfriends' ) );
		}

		$possible_measures_values = array( 's', 'm', 'd', 'h' );
		if ( !in_array( $measure, $possible_measures_values ) ) {
			return array( 'error' => __( 'Invalid Measurement Unit.', 'draftsforfriends' ) );
		}

		$expiration_date = time() + $this->calc( $expires, $measure );

		$wpdb->insert(
			$wpdb->drafts_for_friends,
			array(
				'post_id'         => $post->ID,
				'user_id'         => $current_user->ID,
				'hash'            => wp_generate_password( 32, false, false ),
				'created_date'    => current_time( 'mysql', 1 ),
				'expiration_date' => date( 'Y-m-d H:i:s', $expiration_date )
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s'
			)
		);

		if( $wpdb->insert_id ) {
			return array(
				'success' => sprintf( __( 'Shared draft for \'%s\' created.', 'draftsforfriends' ), $post->post_title )
			);
		}
	}

	function calc( $expires, $measure ) {
		$exp = 60;
		$multiply = 60;
		if ( isset( $expires ) && ( $e = intval( $expires ) ) ) {
			$exp = $e;
		}

		$mults = array(
			's' => 1,
			'm' => 60,
			'h' => 3600,
			'd' => 24 * 3600
		);
		
		if ( isset( $expires ) && $mults[ $measure ] ) {
			$multiply = $mults[ $measure ];
		}
		return $exp * $multiply;
	}

	function delete_shared_draft( $share_id ) {
		global $wpdb;

		if( $share_id == 0 ) {
			return array( 'error' => __( 'There are no shared post to delete.', 'draftsforfriends' ) );
		}

		$result = $wpdb->delete( $wpdb->drafts_for_friends, array( 'id' => $share_id ) );

		if( $result ) {
			return array( 'success' => __( 'Shared draft deleted.', 'draftsforfriends' ) );
		}
	}

	function admin_rest_actions() {
		$request_method = $_SERVER['REQUEST_METHOD'];

		if ( $request_method == "GET") {

			if( isset( $_GET['action'] ) && $_GET['action'] == 'ember_drafts_for_friends' ) {

				if ( isset( $_GET['type'] ) ) {
					$type = $_GET['type'];
					switch ($type) {
						case 'drafts':
							$result = array( 'drafts' => $this->get_shared() );
							break;
						case 'posts':
							$result = array( 'posts' => $this->get_drafts() );
							break;
					}
					wp_send_json( $result );
				}

			}

		} elseif ( $request_method == "POST" ) {

		} elseif ( $request_method == "DELETE" && isset( $_GET['id'] )) {
			$share_id = $_GET['id'];
			wp_send_json( $this->delete_shared_draft( $share_id ) );
		}


	}

	function render_admin_page() {
?>
	<div class="wrap"></div>

	<script type="text/x-handlebars" id="components/message">
		<div id="message" class="updated below-h2">
			<p>Here goes any messages</p>
		</div>
	</script>

	<script type="text/x-handlebars" id="components/table-header">
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
			<th scope="col" id="post" class="manage-column column-post sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=title&amp;order=asc"><span>Post</span><span class="sorting-indicator"></span></a></th>
			<th scope="col" id="created" class="manage-column column-created sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=rating&amp;order=asc"><span>Created</span><span class="sorting-indicator"></span></a></th>
			<th scope="col" id="expires" class="manage-column column-expires sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=director&amp;order=asc"><span>Expires</span><span class="sorting-indicator"></span></a></th>
		</tr>
	</script>

	<script type="text/x-handlebars" id="components/table-navigation">
		<div class="alignleft actions bulkactions">
			<select name="action">
				<option value="-1" selected="selected">Bulk Actions</option>
				<option value="delete">Delete</option>
			</select>
			<input type="submit" name="" id="doaction" class="button action" value="Apply">
		</div>

		<div class="tablenav-pages">
			<span class="displaying-num">7 items</span>
			<span class="pagination-links"><a class="first-page disabled" title="Go to the first page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test">«</a><a class="prev-page disabled" title="Go to the previous page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=1">‹</a>
			<span class="paging-input"><input class="current-page" title="Current page" type="text" name="paged" value="1" size="1"> of <span class="total-pages">2</span></span>
			<a class="next-page" title="Go to the next page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=2">›</a>
			<a class="last-page" title="Go to the last page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=2">»</a></span></div>
			<br class="clear">
		</div>
	</script>

	<script type="text/x-handlebars" id="components/table-navigation-footer">
		<div class="tablenav bottom">

			<div class="alignleft actions bulkactions">
				<select name="action2">
					<option value="-1" selected="selected">Bulk Actions</option>
					<option value="delete">Delete</option>
				</select>
				<input type="submit" name="" id="doaction2" class="button action" value="Apply">
			</div>

			<div class="tablenav-pages">
				<span class="displaying-num">7 items</span>
				<span class="pagination-links">
					<a class="first-page disabled" title="Go to the first page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test">«</a>
					<a class="prev-page disabled" title="Go to the previous page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=1">‹</a>
					<span class="paging-input">1 of <span class="total-pages">2</span></span>
					<a class="next-page" title="Go to the next page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=2">›</a>
					<a class="last-page" title="Go to the last page" href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;paged=2">»</a>
				</span>
			</div>

			<br class="clear">
		</div>
	</script>

	<script type="text/x-handlebars">
		<h2><?php _e( 'Drafts for Friends', 'draftsforfriends' ); ?></h2>
		{{outlet}}
	</script>

	<script type="text/x-handlebars" id="index">
		<div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					<form id="movies-filter" method="get">
						<!-- For plugins, we also need to ensure that the form posts back to our current page -->
						<input type="hidden" name="page" value="tt_list_test">
						<!-- Now we can render the completed list table -->
						<input type="hidden" id="_wpnonce" name="_wpnonce" value="1a9370cd7d"><input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=tt_list_test">	<div class="tablenav top">

						{{table-navigation}}

						<table class="wp-list-table widefat fixed movies">
							<thead>
								{{table-header}}
							</thead>
							<tfoot>
								{{table-header}}
							</tfoot>
							<tbody id="the-list" data-wp-lists="list:movie">
								{{#each drafts itemController="post"}}
									<tr class="alternate">
										<th scope="row" class="check-column">
											<input type="checkbox" name="draft[]" value="{{id}}">
										</th>
										<td class="title column-title"><a target="_blank" {{bind-attr href="share_url"}}>{{post_title}}</a>
											<div class="row-actions">
												<span class="copy"><a class="copy-to-clipboard" {{bind-attr data-clipboard-text="share_url"}}><?php _e( 'Copy to Clipboard', 'draftsforfriends' ) ;?></a> | </span>
												<span class="edit"><a {{action "extendLimit"}}><?php _e( 'Extend Limit', 'draftsforfriends' ) ;?></a> | </span>
												<span class="delete"><a {{action "delete"}}><?php _e( 'Delete', 'draftsforfriends' ) ;?></a></span>
											</div>
										</td>
										<td class="rating column-rating">{{created_date}}</td>
										<td class="director column-director">{{expiration_date}}</td>
									</tr>
								{{else}}
									<tr class="no-items"><td class="colspanchange" colspan="3">No shared drafts!</td></tr>
								{{/each}}
							</tbody>
						</table>

						{{table-navigation-footer}}
					</form>
				</div>
			</div>
			<div id="col-left">
				<div class="col-wrap">



					<div class="form-wrap">
						<h3><?php _e( 'Share a Draft', 'draftsforfriends' ); ?></h3>
						<form id="draftsforfriends-add" action="" method="post" class="validate">
							<?php wp_nonce_field( 'draftsforfriends-add', 'draftsforfriends-add-nonce' ); ?>
							<div class="form-field form-required">
								<label for="posts"><?php _e( 'Choose a draft', 'draftsforfriends' ); ?></label>
								{{view Ember.Select
									name="posts"
									classNames="posts"
									valueBinding="model.post_id"
									content=posts
									optionGroupPath="post_status_category"
									optionValuePath="content.id"
									optionLabelPath="content.post_title"}}
								<p><?php _e( 'The post you\'ll like to share.', 'draftsforfriends'); ?></p>
							</div>
							<div class="form-required">
								<label><?php _e( 'Share it for', 'draftsforfriends' ); ?></label>
								{{input valueBinding="model.expiration" classNames="small-text expiration" placeholder="expiration"}}
								{{view Ember.Select
									name="units"
									valueBinding="model.unit"
									content=units
									optionValuePath="content.value"
									optionLabelPath="content.title"
									value="h"}}
							</div>
							<p class="submit">
								<button {{action createDraft}} class="button button-primary"><?php _e( 'Save', 'draftsforfriends' ); ?></button>
							</p>
						</form>
					</div>


				</div>
			</div>
		</div>
	</script>
<?php
	}

}
new EmberDraftsForFriends();
?>
