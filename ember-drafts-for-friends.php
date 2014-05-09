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

		add_action('admin_menu', array($this, 'admin_menu'));
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	function admin_menu() {
		add_submenu_page( "edit.php", __( 'Drafts for Ember', 'draftsforfriends'), __('Drafts for Ember', 'draftsforfriends' ), 'publish_posts', __FILE__ , array( $this, 'render_admin_page' ) );
	}

	function admin_scripts( $hook ) {
		if( $hook == 'posts_page_ember-drafts-for-friends/ember-drafts-for-friends' ) {
			wp_enqueue_style( 'draftsforfriends', plugins_url( 'css/drafts-for-friends.css', __FILE__, false, '0.1' ) );

			wp_enqueue_script( 'handlebars', plugins_url( 'js/lib/handlebars-1.1.2.js', __FILE__ ), array( 'jquery' ), '1.1.2', true );
			wp_enqueue_script( 'ember', plugins_url( 'js/lib/ember-1.5.1.js', __FILE__ ), array( 'jquery' ), '1.5.1', true );
			wp_enqueue_script( 'draftsforfriends', plugins_url( 'js/app.js', __FILE__ ), array( 'jquery', 'ember' ), '0.1', true );
		}
	}

	function render_admin_page (){
?>
	<div class="wrap"></div>

	<script type="text/x-handlebars">
		<h2><?php _e( 'Drafts for Friends', 'draftsforfriends' ); ?></h2>
		<div id="message" class="updated below-h2">
			<p>Here goes any messages</p>
		</div>
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

						<table class="wp-list-table widefat fixed movies">
							<thead>
								<tr>
									<th scope="col" id="cb" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></th>
									<th scope="col" id="post" class="manage-column column-post sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=title&amp;order=asc"><span>Post</span><span class="sorting-indicator"></span></a></th>
									<th scope="col" id="created" class="manage-column column-created sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=rating&amp;order=asc"><span>Created</span><span class="sorting-indicator"></span></a></th>
									<th scope="col" id="expires" class="manage-column column-expires sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=director&amp;order=asc"><span>Expires</span><span class="sorting-indicator"></span></a></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th scope="col" class="manage-column column-cb check-column" style=""><label class="screen-reader-text" for="cb-select-all-2">Select All</label><input id="cb-select-all-2" type="checkbox"></th>
									<th scope="col" class="manage-column column-post sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=title&amp;order=asc"><span>Post</span><span class="sorting-indicator"></span></a></th>
									<th scope="col" class="manage-column column-created sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=rating&amp;order=asc"><span>Created</span><span class="sorting-indicator"></span></a></th>
									<th scope="col" class="manage-column column-expires sortable desc" style=""><a href="http://local.wordpress.dev/wp-admin/admin.php?page=tt_list_test&amp;orderby=director&amp;order=asc"><span>Expires</span><span class="sorting-indicator"></span></a></th>
								</tr>
							</tfoot>
							<tbody id="the-list" data-wp-lists="list:movie">
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input type="checkbox" name="movie[]" value="1">
									</th>
									<td class="title column-title">300
										<div class="row-actions">
											<span class="edit"><a href="?page=tt_list_test&amp;action=edit&amp;movie=1">Edit</a> | </span>
											<span class="delete"><a href="?page=tt_list_test&amp;action=delete&amp;movie=1">Delete</a></span>
										</div>
									</td>
									<td class="rating column-rating">R</td>
									<td class="director column-director">Zach Snyder</td>
								</tr>
								<tr>
									<th scope="row" class="check-column">
										<input type="checkbox" name="movie[]" value="2">
									</th>
									<td class="title column-title">Eyes Wide Shut
										<div class="row-actions">
											<span class="edit"><a href="?page=tt_list_test&amp;action=edit&amp;movie=2">Edit</a> | </span>
											<span class="delete"><a href="?page=tt_list_test&amp;action=delete&amp;movie=2">Delete</a></span>
										</div>
									</td>
									<td class="rating column-rating">R</td>
									<td class="director column-director">Stanley Kubrick</td>
								</tr>
								<tr class="alternate">
									<th scope="row" class="check-column">
										<input type="checkbox" name="movie[]" value="3">
									</th>
									<td class="title column-title">Moulin Rouge!
										<div class="row-actions">
											<span class="edit"><a href="?page=tt_list_test&amp;action=edit&amp;movie=3">Edit</a> | </span>
											<span class="delete"><a href="?page=tt_list_test&amp;action=delete&amp;movie=3">Delete</a></span>
										</div>
									</td>
									<td class="rating column-rating">PG-13</td>
									<td class="director column-director">Baz Luhrman</td>
								</tr>
							</tbody>
						</table>

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
								<label for="post_id"><?php _e( 'Choose a draft', 'draftsforfriends' ); ?></label>
								<select id="draftsforfriends-postid" name="post_id">
									<?php foreach ( $drafts as $draft ): ?>
										<optgroup label="<?php echo $draft[0]; ?>">
											<?php foreach ( $draft[2] as $post ): ?>
												<option value="<?php echo $post->ID ?>"><?php echo esc_html( $post->post_title ); ?></option>
											<?php endforeach ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
								<p><?php _e( 'The post you\'ll like to share.', 'draftsforfriends'); ?></p>
							</div>
							<div class="form-required">
								<label><?php _e( 'Share it for', 'draftsforfriends' ); ?></label>
								<input name="expires" type="text" value="2" size="4" class="small-text" />
								<select name="measure">
									<option value="s"><?php _e( 'seconds', 'draftsforfriends' ); ?></option>
									<option value="m"><?php _e( 'minutes', 'draftsforfriends' ); ?></option>
									<option value="h" selected="selected"><?php _e( 'hours', 'draftsforfriends' ); ?></option>
									<option value="d"><?php _e( 'days', 'draftsforfriends' ); ?></option>
								</select>
							</div>
							<p class="submit">
								<input type="submit" class="button button-primary" name="draftsforfriends_submit" value="<?php _e( 'Save', 'draftsforfriends' ); ?>" />
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