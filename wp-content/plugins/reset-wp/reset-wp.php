<?php
/*
Plugin Name: Reset WP
Plugin URI: https://wpreset.com
Description: Reset the WordPress database to the default installation values including all content and customizations. This plugin will soon be removed from the WP repository and replaced with WP Reset.
Version: 1.55
Author: WebFactory Ltd
Author URI: https://www.webfactoryltd.com/
Text Domain: reset-wp
*/

/*

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

if ( ! defined( 'ABSPATH' ) ){
	exit; // Exit if accessed this file directly
}

if ( is_admin() ) {

// todo: rename constant
define( 'REACTIVATE_THE_RESET_WP', true );

class ResetWP {
	static $version = 1.55;

	function __construct() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'plugin_action_links') );
    add_action( 'admin_action_replace_reset_wp', array($this, 'replace_reset_wp'));
    add_action( 'admin_notices', array( $this, 'admin_notices_upgrade' ) );
    add_action( 'admin_footer', array( $this, 'upgrade_footer' ) );
	}

	// Checks reset_wp post value and performs an installation, adding the users previous password also
	function admin_init() {
		global $current_user, $wpdb;

		$reset_wp = ( isset( $_POST['reset_wp'] ) && $_POST['reset_wp'] == 'true' ) ? true : false;
		$reset_wp_confirm = ( isset( $_POST['reset_wp_confirm'] ) && $_POST['reset_wp_confirm'] == 'reset' ) ? true : false;
		$valid_nonce = ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'reset_wp' ) ) ? true : false;

		if ( $reset_wp && $reset_wp_confirm && $valid_nonce ) {
			@require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );

			$blogname = get_option( 'blogname' );
			$admin_email = get_option( 'admin_email' );
			$blog_public = get_option( 'blog_public' );
      $wplang = get_option( 'wplang' );
      $siteurl = get_option ( 'siteurl' );
      $home = get_option ( 'home' );

			if ( $current_user->user_login != 'admin' )
				$user = get_user_by( 'login', 'admin' );

			if ( empty( $user->user_level ) || $user->user_level < 10 )
				$user = $current_user;

			$prefix = str_replace( '_', '\_', $wpdb->prefix );
			$tables = $wpdb->get_col( "SHOW TABLES LIKE '{$prefix}%'" );
			foreach ( $tables as $table ) {
				$wpdb->query( "DROP TABLE $table" );
			}

			$result = wp_install( $blogname, $current_user->user_login, $current_user->user_email, $blog_public, '', '', $wplang);
			$user_id = $result['user_id'];

			$query = $wpdb->prepare( "UPDATE {$wpdb->users} SET user_pass = %s, user_activation_key = '' WHERE ID = %d LIMIT 1", array($current_user->user_pass, $user_id));
			$wpdb->query( $query );

      		update_option('siteurl', $siteurl);
      		update_option('home', $home);

			if ( get_user_meta( $user_id, 'default_password_nag' ) ) {
			  update_user_meta( $user_id, 'default_password_nag', false );
			}
			if ( get_user_meta( $user_id, $wpdb->prefix . 'default_password_nag' ) ) {
			  update_user_meta( $user_id, $wpdb->prefix . 'default_password_nag', false );
			}



			if ( defined( 'REACTIVATE_THE_RESET_WP' ) && REACTIVATE_THE_RESET_WP === true )
				@activate_plugin( plugin_basename( __FILE__ ) );


			wp_clear_auth_cookie();
			wp_set_auth_cookie( $user_id );

			wp_redirect( admin_url()."?reset-wp=reset-wp" );
			exit();
		}

		if ( array_key_exists( 'reset-wp', $_GET ) && stristr( $_SERVER['HTTP_REFERER'], 'reset-wp' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notices_successfully_reset' ) );
		}
	}

	// add settings link to plugins page
	static function plugin_action_links($links) {
		$settings_link = '<a href="' . admin_url('tools.php?page=reset-wp') . '" title="' . __('Reset WordPress', 'reset-wp') . '">' . __('Reset WordPress', 'reset-wp') . '</a>';

		array_unshift($links, $settings_link);

		return $links;
	  } // plugin_action_links


	// test if we're on plugin's page
  function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'tools_page_reset-wp') {
      return true;
    } else {
      return false;
    }
	} // is_plugin_page

	// admin_menu action hook operations & Add the settings page
	function add_page() {
		$hook = add_management_page( 'Reset WP', 'Reset WP', 'administrator', 'reset-wp', array( $this, 'admin_page' ) );
		add_action( "admin_print_scripts-{$hook}", array( $this, 'admin_javascript' ) );
		add_action( "admin_footer-{$hook}", array( $this, 'footer_javascript' ) );
	}

	// Inform the user that WordPress has been successfully reset
	function admin_notices_successfully_reset() {
		global $current_user;

		echo '<div id="message" class="updated"><p><strong>WordPress has been reset successfully.</strong> User "' . $current_user->user_login . '" was recreated with its old password.</p></div>';
		do_action( 'reset_wp_post', $current_user );
  }

  // try to get users to switch to WP Reset
	function admin_notices_upgrade() {
    if ($this->is_plugin_page()) {
      return;
    }

    echo '<div id="message" class="error" style="display: table; margin: 50px auto; padding: 30px; border: 4px solid #dc3232;"><p style="font-size:16px;"><strong style="font-size: 17px;">Important! This is NOT an ad! We\'re not selling anything. You are using an old &amp; unmaintained plugin - Reset WP</strong><br><br>';
    echo 'This is not an advertisement, or a pro upgrade nag. Reset WP is no longer maintained and you have to stop using it.<br>Please switch to <a href="https://wordpress.org/plugins/wp-reset/" target="_blank">WP Reset</a> <span class="dashicons dashicons-external"></span> which is fully compatible with your version of WP. It is a one-click action, nothing else is required; <a href="' . admin_url( 'tools.php?page=reset-wp' ) . '">read more.</a><br><br></p>';
    echo '<p style="font-size:16px;"><a href="#" style="font-size:16px;" id="upgrade-wp-reset" class="button button-primary">Update to a stable &amp; maintained plugin NOW</a> <i>This notice will go away once you stop using Reset WP.</i></p></div>';
  } // admin_notices_upgrade

  function upgrade_footer() {
    if ($this->is_plugin_page()) {
      return;
    }

    $replace_url = add_query_arg(array('action' => 'replace_reset_wp', 'redirect' => urlencode($_SERVER['REQUEST_URI'])), admin_url('admin.php'));
    ?>
		<script>
			jQuery('#upgrade-wp-reset').on('click',function(e){
				jQuery('body').append('<div style="width:400px;height:540px; position:absolute;top:10%;left:50%;margin-left:-200px;background:#FFF;border:1px solid #DDD; border-radius:4px;box-shadow: 0px 0px 0px 4000px rgba(0, 0, 0, 0.85);z-index: 9999999;"><iframe src="<?php echo $replace_url; ?>" style="width:100%;height:100%;border:none;" /></div>');
        jQuery('#wpwrap').css('pointer-events', 'none');

        e.preventDefault();
        return false;
			});
		</script>
  <?php
  } // upgrade_footer

	function admin_javascript() {
		if ( $this->is_plugin_page() ) {
		  wp_enqueue_script( 'jquery' );
		}
	}

	function footer_javascript() {
	?>
	<script type="text/javascript">
		jQuery('#reset_wp_submit').click(function(){
			if ( jQuery('#reset_wp_confirm').val() == 'reset' ) {
				var message = 'Please note - THERE IS NO UNDO!\n\nClicking "OK" will reset your database to the default installation values.\nAll content and customizations will be gone.\nNo files will be modified or deleted.\n\nClick "Cancel" to stop the operation.'
				var reset = confirm(message);
				if ( reset ) {
					jQuery('#reset_wp_form').submit();
				} else {
					return false;
				}
			} else {
				alert('Invalid confirmation. Please type \'reset\' in the confirmation field.');
				return false;
			}
		});
	</script>
	<?php
	}

	// add_option_page callback operations
	function admin_page() {
		global $current_user;

		if ( isset( $_POST['reset_wp_confirm'] ) && $_POST['reset_wp_confirm'] != 'reset-wp' )
			echo '<div class="error fade"><p><strong>Invalid confirmation. Please type \'reset-wp\' in the confirmation field.</strong></p></div>';
		elseif ( isset( $_POST['_wpnonce'] ) )
			echo '<div class="error fade"><p><strong>Invalid wpnonce. Please try again.</strong></p></div>';

	?>
	<div class="wrap">
		<h2>Reset WP</h2>

    <div class="card" id="wp-reset-promo">
    <h2 style="color: #ca4a1f;">This plugin is old &amp; unmaintained! It's been replaced by <a target="_blank" href="https://wordpress.org/plugins/wp-reset/">WP Reset</a></h2>
    <p><b>This is NOT an upsell or an advertisement! WP Reset is 100% FREE. We want you to use a safe, fast &amp; maintained plugin.</b></p>
    <p>Except the name and the slug <b>NOTHING CHANGES</b> for you. WP Reset is simple, fast, free and maintained by the same dedicated developers. You can read more about this change on the <a target="_blank" href="https://wpreset.com/rebranding-reset-wp/?utm_source=reset-wp-free&utm_medium=plugin&utm_content=wp-reset-blog&utm_campaign=reset-wp-free-v<?php echo self::$version; ?>">WP Reset blog</a>. If you have any questions, email us - <a href="mailto:wpreset@webfactoryltd.com?subject=Switching to WP Reset">wpreset@webfactoryltd.com</a>.</p>
    <br><br>
    <style>
    #wpr-upgrade {
      padding: 0;
      margin: 0;
      border-spacing: 0;
      border-collapse: collapse;
      width: 100%;
    }
    #wpr-upgrade th {
      border-bottom: 1px solid black;
      padding: 6px 4px;
    }
    #wpr-upgrade tr td:first-child {
      border-right: 1px solid black;
      text-align: right;
    }
    #wpr-upgrade tr td:last-child, #wpr-upgrade tr th:last-child {
      background: #eeeeee50;
    }
    #wpr-upgrade td {
      text-align: center;
      padding: 6px 4px;
      border-bottom: 1px dotted #222;
    }
    .red {
      color: #ca4a1f;
    }
    .green {
      color: #1fca3c;
    }
    </style>
    <table id="wpr-upgrade">
  <tr>
    <th style="width: 31%;">&nbsp;</th>
    <th>Current plugin</th>
    <th style="width: 35%;"><a href="https://wordpress.org/plugins/wp-reset/" target="_blank" title="WP Reset"><img style="height: 20px; width: auto;" src="<?php echo plugin_dir_url( __FILE__ ); ?>wp-reset-logo.png" alt="WP Reset" title="WP Reset"></a></th>
  </tr>
  <tr>
    <td>updates</td>
    <td><span class="red dashicons dashicons-no"></span><br>no longer updated</td>
    <td><span class="green dashicons dashicons-yes"></span><br>regular, every 3 weeks</td>
  </tr>
  <tr>
    <td>compatible with latest WP</td>
    <td><span class="red dashicons dashicons-no"></span></td>
    <td><span class="green dashicons dashicons-yes"></span></td>
  </tr>
  <tr>
    <td>support</td>
    <td><span class="red dashicons dashicons-no"></span><br>support no longer available</td>
    <td><span class="green dashicons dashicons-yes"></span><br>fast, via email &amp; forums</td>
  </tr>
  <tr>
    <td>license</td>
    <td>free</td>
    <td>free, 100% open source</td>
  </tr>
  <tr>
    <td>PRO version</td>
    <td><span class="dashicons dashicons-no"></span></td>
    <td><span class="dashicons dashicons-no"></span><br>no PRO, no upsells</td>
  </tr>
  <tr>
    <td>number of users</td>
    <td>20,000</td>
    <td>100,000+</td>
  </tr>
  <tr>
    <td>site reset function</td>
    <td><span class="green dashicons dashicons-yes"></span></td>
    <td><span class="green dashicons dashicons-yes"></span></td>
  </tr>
  <tr>
    <td>partial reset function</td>
    <td><span class="red dashicons dashicons-no"></span></td>
    <td><span class="green dashicons dashicons-yes"></span></td>
  </tr>
  <tr>
    <td>additional dev tools</td>
    <td><span class="red dashicons dashicons-no"></span></td>
    <td><span class="green dashicons dashicons-yes"></span></td>
  </tr>
  <tr>
    <td>WP-CLI support</td>
    <td><span class="red dashicons dashicons-no"></span></td>
    <td><span class="green dashicons dashicons-yes"></span></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a style="display: none;" href="" id="continue-reset-wp">Continue using an unmaintained plugin</a></td>
    <td><a style="margin-bottom: 8px;" class="button button-primary" id="upgrade-wp-reset">Switch to WP Reset</a><br>1 click &amp; 5 seconds of waiting</td>
  </tr>
</table>
    <br>
    <?php
    $replace_url = add_query_arg(array('action' => 'replace_reset_wp'), admin_url('admin.php'));
    //echo '<a class="button button-primary" id="upgrade-wp-reset">Replace plugin with the new version - WP Reset</a><br>';
    echo '<p>What happens when I click the "Switch to WP Reset" button? WP Reset will be downloaded from <a target="_blank" href="https://wordpress.org/plugins/wp-reset/">wordpress.org/plugins/wp-reset/</a> and activated. You\'ll be redirected to its admin page and this plugin will then be deactivated. Nothing is deleted or removed!</p>';
    ?>
		<script>
			jQuery('#upgrade-wp-reset').on('click',function(e){
				jQuery('body').append('<div style="width:400px;height:540px; position:absolute;top:10%;left:50%;margin-left:-200px;background:#FFF;border:1px solid #DDD; border-radius:4px;box-shadow: 0px 0px 0px 4000px rgba(0, 0, 0, 0.85);z-index: 9999999;"><iframe src="<?php echo $replace_url; ?>" style="width:100%;height:100%;border:none;" /></div>');
        jQuery('#wpwrap').css('pointer-events', 'none');

        e.preventDefault();
        return false;
			});

      jQuery('#continue-reset-wp').on('click',function(e){
        jQuery('#wp-reset-promo').hide();
        jQuery('#reset-wp-do').show();

        e.preventDefault();
        return false;
			});
		</script>
    </div>
		<div id="reset-wp-do" class="card" style="display: none;">
			<p><strong>After completing the reset operation, you will be automatically logged in and redirected to the dashboard.</strong></p>

			<?php
				echo '<p>Current user "' . $current_user->user_login . '" will be recreated after resetting with its current password and admin privileges. Reset WP <strong>will be automatically reactivated</strong> after the reset operation.</p>';
			?>
			<hr/>

			<p>To confirm the reset operation, type "<strong>reset</strong>" in the confirmation field below and then click the Reset button</p>
			<form id="reset_wp_form" action="" method="post" autocomplete="off">
				<?php wp_nonce_field( 'reset_wp' ); ?>
				<input id="reset_wp" type="hidden" name="reset_wp" value="true">
				<input id="reset_wp_confirm" style="vertical-align: middle;" type="text" name="reset_wp_confirm" placeholder="Type in 'reset'">
				<input id="reset_wp_submit" style="vertical-align: middle;" type="submit" name="Submit" class="button-primary" value="Reset">
			</form>

      <p>Something is not right? Let us know in the <a href="https://wordpress.org/support/plugin/reset-wp" target="_blank">forums</a>.
		</div>
	</div>

	<?php
	}

  function replace_reset_wp() {
    $plugin_slug = 'wp-reset/wp-reset.php';
    $plugin_zip = 'https://downloads.wordpress.org/plugin/wp-reset.latest-stable.zip';

    @include_once ABSPATH . 'wp-admin/includes/plugin.php';
    @include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    @include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    @include_once ABSPATH . 'wp-admin/includes/file.php';
    @include_once ABSPATH . 'wp-admin/includes/misc.php';

    if (!empty($_GET['redirect'])) {
      $redirect = $_GET['redirect'];
    } else {
      $redirect = admin_url('tools.php?page=wp-reset');
    }

		echo '<style>
		body{
			font-family: sans-serif;
			font-size: 14px;
			line-height: 1.5;
			color: #444;
		}

		a{
			color:#0073aa;
		}

		a:hover{
			color:#00a0d2;
			text-decoration:none;
		}
		</style>';

    echo '<div style="margin: 30px;">';
    echo 'If things are not done in a minute <a href="plugins.php" target="_parent">click here to return to Plugins page</a><br><br>';
    echo 'Starting ...<br><br>';

		wp_cache_flush();
    $upgrader = new Plugin_Upgrader();
    echo 'Check if new plugin is already installed - ';
    if ($this->is_plugin_installed($plugin_slug)) {
      echo 'it\'s installed! Making sure it\'s the latest version.';
      $upgrader->upgrade($plugin_slug);
      $installed = true;
    } else {
      echo 'it\'s not installed. Installing.';
      $installed = $upgrader->install($plugin_zip);
    }
    wp_cache_flush();

    if (!is_wp_error($installed) && $installed) {
      echo 'Activating new plugin.';
      $activate = activate_plugin($plugin_slug);

      if (is_null($activate)) {
        echo '<br>Deactivating old plugin.<br>';
        deactivate_plugins(array('reset-wp/reset-wp.php'));

        $options = get_option('wp-reset', array());
        $options['meta']['reset-wp-user'] = true;
        $options['dismissed_notices']['welcome'] = true;
        update_option('wp-reset', $options);

        echo '<script>setTimeout(function() { top.location = "' . $redirect . '"; }, 1000);</script>';
        echo '<br>If you are not redirected to the new plugin in a few seconds - <a href="' . $redirect . '" target="_parent">click here</a>.';
      }
    } else {
      echo 'Could not install WP Reset. You\'ll have to <a target="_parent" href="' . admin_url('plugin-install.php?s=wp+reset&tab=search&type=term') .'">download and install manually</a>.';
    }

    echo '</div>';
  } // replace

  function is_plugin_installed( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( !empty( $all_plugins[$slug] ) ) {
			return true;
		} else {
			return false;
		}
	}
}

$ResetWP = new ResetWP();
}
