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

		register_activation_hook( __FILE__, array( $this, 'plugin_install' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_uninstall' ) );
	}

	function init(){
		global $wpdb;

		$wpdb->ember_drafts_for_friends = $wpdb->prefix . 'ember_drafts_for_friends';
		$wpdb->drafts_for_friends = $wpdb->prefix . 'drafts_for_friends';

		add_action( 'admin_menu', array($this, 'admin_menu') );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_ajax_ember_drafts_for_friends', array( $this, 'admin_rest_actions' ) );

		add_filter( 'the_posts', array( $this, 'the_posts_intercept') );
		add_filter( 'posts_results', array( $this, 'posts_results_intercept') );
	}

	function plugin_install() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ember_drafts_for_friends';
		$create_sql = "CREATE TABLE $table_name (".
			'id bigint(20) unsigned NOT NULL AUTO_INCREMENT,'.
			'post_id bigint(20) unsigned NOT NULL,'.
			'user_id bigint(20) unsigned NOT NULL,'.
			'hash varchar(32) NOT NULL DEFAULT \'\','.
			'created_date datetime NOT NULL,'.
			'expiration_date datetime NOT NULL,'.
			'PRIMARY KEY (id),'.
			'KEY post_id (post_id),'.
			'KEY user_id (user_id),'.
			'KEY postid_hash_expired (post_id, hash, expiration_date));';

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $create_sql );

		add_option( 'draft_for_friends_db_version', '0.1' );
	}

	function plugin_uninstall() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'ember_drafts_for_friends';
		$drop_sql = "DROP TABLE IF EXISTS $table_name";

		$wpdb->query( $drop_sql );
		delete_option( 'draft_for_friends_db_version' );
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

	function can_view( $post_id ) {
		global $wpdb;
		if( isset( $_GET['emberdraftsforfriends'] ) ){
			$hash = sanitize_text_field( $_GET['emberdraftsforfriends'] );
			$has_shares = intval( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $wpdb->ember_drafts_for_friends WHERE post_id = %d and hash = %s AND expiration_date >= %s", intval( $post_id ), $hash, current_time( 'mysql', 1 ) ) ) );
			return ( $has_shares === 1 );
		}
		return false;
	}

	function posts_results_intercept( $posts ) {
		if ( 1 != count( $posts ) ) {
			return $posts;
		}

		$post = $posts[0];
		$status = get_post_status( $post );

		if ( 'publish' != $status && $this->can_view( $post->ID ) ) {
			$post->comment_status = 'closed';
			$this->shared_post = $post;
		}
		return $posts;
	}

	function the_posts_intercept( $posts ) {
		if ( empty( $posts ) && !is_null( $this->shared_post ) ) {
			return array( $this->shared_post );
		} else {
			$this->shared_post = null;
			return $posts;
		}
	}

	function get_shared() {
		global $wpdb, $current_user;
		return $wpdb->get_results( $wpdb->prepare( "SELECT d.id, d.post_id, d.hash, CONCAT(d.created_date, ' UTC') as created_date, CONCAT(d.expiration_date, ' UTC') as expiration_date, p.post_title AS post_title FROM $wpdb->ember_drafts_for_friends d INNER JOIN $wpdb->posts p ON d.post_id = p.id WHERE user_id = %d", intval( $current_user->ID ) ) );
	}

	function get_share_by_id ( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( "SELECT d.id, d.post_id, d.hash, CONCAT(d.created_date, ' UTC') as created_date, CONCAT(d.expiration_date, ' UTC') as expiration_date, p.post_title AS post_title FROM $wpdb->ember_drafts_for_friends d INNER JOIN $wpdb->posts p ON d.post_id = p.id WHERE d.id = %d", $id ) );
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

		$result = $wpdb->insert(
			$wpdb->ember_drafts_for_friends,
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
			return $this->get_share_by_id( $wpdb->insert_id );
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

		return $wpdb->delete( $wpdb->ember_drafts_for_friends, array( 'id' => $share_id ) );
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
			$data = json_decode(file_get_contents("php://input"));
			$draft = $data->draft;

			$result = $this->create_shared_draft( $draft->post_id, $draft->expiration, $draft->expiration_unit );

			wp_send_json( array( 'draft' => $result ) );
		} elseif ( $request_method == "DELETE" && isset( $_GET['id'] )) {
			$share_id = $_GET['id'];
			$result = $this->delete_shared_draft( $share_id );
			wp_exit();
		}
	}

	function render_admin_page() {
?>
	<div class="wrap"></div>

	<script type="text/x-handlebars" data-template-name="application">
		<h2><?php _e( 'Drafts for Friends', 'draftsforfriends' ); ?></h2>
    {{view EmberDrafts.NotificationView notificationBinding="notification"}}
		{{outlet}}
	</script>

	<script type="text/x-handlebars" data-template-name="index">
		<div id="col-container">
			<div id="col-right">
				<div class="col-wrap">
					{{partial "shares"}}
				</div>
			</div>
			<div id="col-left">
				<div class="col-wrap">
					{{render "create" this}}
				</div>
			</div>
		</div>
	</script>

	<script type="text/x-handlebars" data-template-name="_shares">

			{{table-navigation}}

			<table class="wp-list-table widefat fixed movies">
				<thead>
					{{table-header}}
				</thead>
				<tfoot>
					{{table-header}}
				</tfoot>
				<tbody id="the-list">
					{{#each drafts itemController="post"}}
						<tr class="alternate">
							<th scope="row" class="check-column">
								{{input type="checkbox" name=id }}
							</th>
							<td class="title column-title"><a target="_blank" {{bind-attr href=share_url}}>{{post_title}}</a><span class="copied">Copied!</span>
								<div class="row-actions">
									<span class="copy">{{copy-to-clipboard data-clipboard-textBinding="share_url"}} | </span>
									<span class="edit"><a {{action "extendLimit"}}><?php _e( 'Extend Limit', 'draftsforfriends' ) ;?></a> | </span>
									<span class="delete"><a {{action "delete"}}><?php _e( 'Delete', 'draftsforfriends' ) ;?></a></span>
								</div>
							</td>
							<td class="rating column-rating">{{humanize created_date}}</td>
							<td class="director column-director">{{humanize expiration_date}}</td>
						</tr>
					{{else}}
						<tr class="no-items"><td class="colspanchange" colspan="3">No shared drafts!</td></tr>
					{{/each}}
				</tbody>
			</table>

			{{table-navigation}}
	</script>

	<script type="text/x-handlebars" data-template-name="create">
		<div class="form-wrap">
			<h3><?php _e( 'Share a Draft', 'draftsforfriends' ); ?></h3>
			<div class="form-field form-required">
				<label for="posts"><?php _e( 'Choose a draft', 'draftsforfriends' ); ?></label>
				{{view Ember.Select
					name="posts"
					classNames="posts"
					content=posts
					valueBinding="form.post_id"
					optionGroupPath="post_status_category"
					optionValuePath="content.id"
					optionLabelPath="content.post_title"}}
				<p><?php _e( 'The post you\'ll like to share.', 'draftsforfriends'); ?></p>
			</div>
			<div class="form-required">
				<label><?php _e( 'Share it for', 'draftsforfriends' ); ?></label>
				{{input valueBinding="form.expiration" classNames="small-text expiration" placeholder="expiration"}}
				{{view Ember.Select
					name="units"
					valueBinding="form.expiration_unit"
					content=units
					optionValuePath="content.value"
					optionLabelPath="content.title"
					value="h"}}
			</div>
			<p class="submit">
				<button {{action create}} class="button button-primary"><?php _e( 'Save', 'draftsforfriends' ); ?></button>
			</p>
		</div>
	</script>

	<script type="text/x-handlebars" data-template-name="components/copy-to-clipboard">
		<?php _e( 'Copy to Clipboard', 'draftsforfriends' ) ;?>
	</script>

	<script type="text/x-handlebars" data-template-name="notification">
    {{#if notification}}
      <div class="updated below-h2">
        <p>{{notification.message}}</p>
      </div>
    {{/if}}
	</script>

	<script type="text/x-handlebars" data-template-name="components/table-header">
		<tr>
			<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
			<th scope="col" id="post" class="manage-column column-post sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=title&amp;order=asc"><span>Post</span><span class="sorting-indicator"></span></a></th>
			<th scope="col" id="created" class="manage-column column-created sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=rating&amp;order=asc"><span>Created</span><span class="sorting-indicator"></span></a></th>
			<th scope="col" id="expires" class="manage-column column-expires sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=director&amp;order=asc"><span>Expires</span><span class="sorting-indicator"></span></a></th>
		</tr>
	</script>

	<script type="text/x-handlebars" data-template-name="components/table-navigation">
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

<?php
	}

}
new EmberDraftsForFriends();
?>
