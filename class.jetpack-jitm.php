<?php

/**
 * Jetpack just in time messaging through out the admin
 *
 * @since 3.7.0
 */
class Jetpack_JITM {

	/**
	 * @var Jetpack_JITM
	 **/
	private static $instance = null;

	/**
	 * Get user dismissed messages.
	 *
	 * @var array
	 */
	private static $jetpack_hide_jitm = null;

	static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Jetpack_JITM;
		}

		return self::$instance;
	}

	private function __construct() {
		if ( ! Jetpack::is_active() ) {
			return;
		}
		add_action( 'current_screen', array( $this, 'prepare_jitms' ) );
		add_action( 'load-plugins.php', array( $this, 'previously_activated_plugins' ) );
	}

	/**
	 * Prepare actions according to screen and post type.
	 *
	 * @since 3.8.2
	 *
	 * @param object $screen
	 */
	function prepare_jitms( $screen ) {
		if ( current_user_can( 'jetpack_manage_modules' ) ) {
			global $pagenow;
			// Only show auto update JITM if auto updates are allowed in this installation
			$auto_updates_enabled = ! ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED );
			if ( ! self::is_jitm_dismissed() ) {
				if ( 'media-new.php' == $pagenow && ! Jetpack::is_module_active( 'photon' ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'jitm_enqueue_files' ) );
					add_action( 'post-plupload-upload-ui', array( $this, 'photon_msg' ) );
				}
				elseif ( 'post-new.php' == $pagenow && in_array( $screen->post_type, array( 'post', 'page' ) ) ) {
					add_action( 'admin_enqueue_scripts', array( $this, 'jitm_enqueue_files' ) );
					add_action( 'admin_notices', array( $this, 'editor_msg' ) );
				}
				elseif ( $auto_updates_enabled ) {
					if ( 'update-core.php' == $pagenow && ! Jetpack::is_module_active( 'manage' ) ) {
						add_action( 'admin_enqueue_scripts', array( $this, 'jitm_enqueue_files' ) );
						add_action( 'admin_notices', array( $this, 'manage_msg' ) );
					}
					elseif ( 'plugins.php' == $pagenow && ( isset( $_GET['activate'] ) && 'true' === $_GET['activate'] || isset( $_GET['activate-multi'] ) && 'true' === $_GET['activate-multi'] ) ) {
						add_action( 'admin_enqueue_scripts', array( $this, 'jitm_enqueue_files' ) );
						add_action( 'pre_current_active_plugins', array( $this, 'manage_pi_msg' ) );
					}
				}
			}
		}
	}

	/**
	 * Save plugins that are activated. This is used when one or more plugins are activated to know
	 * what was activated and use it in Jetpack_JITM::manage_pi_msg() before deleting the option.
	 *
	 * @since 3.8.2
	 */
	function previously_activated_plugins() {
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		$action = $wp_list_table->current_action();
		if ( $action && ( 'activate' == $action || 'activate-selected' == $action ) ) {
			update_option( 'jetpack_previously_activated', get_option( 'active_plugins', array() ) );
		}
	}

	/*
	 * Present Manage just in time activation msg on update-core.php
	 *
	 */
	function manage_msg() {
		$normalized_site_url = Jetpack::build_raw_urls( get_home_url() );
		?>
		<div class="jp-jitm">
			<a href="#" data-module="manage" class="dismiss"><span class="genericon genericon-close"></span></a>

			<div class="jp-emblem">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0" y="0" viewBox="0 0 172.9 172.9" enable-background="new 0 0 172.9 172.9" xml:space="preserve">
						<path d="M86.4 0C38.7 0 0 38.7 0 86.4c0 47.7 38.7 86.4 86.4 86.4s86.4-38.7 86.4-86.4C172.9 38.7 134.2 0 86.4 0zM83.1 106.6l-27.1-6.9C49 98 45.7 90.1 49.3 84l33.8-58.5V106.6zM124.9 88.9l-33.8 58.5V66.3l27.1 6.9C125.1 74.9 128.4 82.8 124.9 88.9z" />
					</svg>
			</div>
			<p class="msg">
				<?php _e( 'Reduce security risks with automated plugin updates.', 'jetpack' ); ?>
			</p>

			<p>
				<img class="j-spinner hide" src="<?php echo esc_url( includes_url( 'images/spinner-2x.gif' ) ); ?>" alt="Loading ..." /><a href="#" data-module="manage" class="activate button <?php if ( Jetpack::is_module_active( 'manage' ) ) {
					echo 'hide';
				} ?>"><?php esc_html_e( 'Activate Now', 'jetpack' ); ?></a><a href="<?php echo esc_url( 'https://wordpress.com/plugins/' . $normalized_site_url ); ?>" target="_blank" title="<?php esc_attr_e( 'Go to WordPress.com to try these features', 'jetpack' ); ?>" id="jetpack-wordpressdotcom" class="button button-jetpack <?php if ( ! Jetpack::is_module_active( 'manage' ) ) {
					echo 'hide';
				} ?>"><?php esc_html_e( 'Go to WordPress.com', 'jetpack' ); ?></a>
			</p>
		</div>
		<?php
		//jitm is being viewed, track it
		$jetpack = Jetpack::init();
		$jetpack->stat( 'jitm', 'manage-viewed-' . JETPACK__VERSION );
		$jetpack->do_stats( 'server_side' );
	}

	/*
	 * Present Photon just in time activation msg
	 *
	 */
	function photon_msg() {
		?>
		<div class="jp-jitm">
			<a href="#" data-module="photon" class="dismiss"><span class="genericon genericon-close"></span></a>

			<div class="jp-emblem">
				<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0" y="0" viewBox="0 0 172.9 172.9" enable-background="new 0 0 172.9 172.9" xml:space="preserve">
						<path d="M86.4 0C38.7 0 0 38.7 0 86.4c0 47.7 38.7 86.4 86.4 86.4s86.4-38.7 86.4-86.4C172.9 38.7 134.2 0 86.4 0zM83.1 106.6l-27.1-6.9C49 98 45.7 90.1 49.3 84l33.8-58.5V106.6zM124.9 88.9l-33.8 58.5V66.3l27.1 6.9C125.1 74.9 128.4 82.8 124.9 88.9z" />
					</svg>
			</div>
			<p class="msg">
				<?php _e( 'Speed up your photos and save bandwidth costs by using a free content delivery network.', 'jetpack' ); ?>
			</p>

			<p>
				<img class="j-spinner hide" style="margin-top: 13px;" width="17" height="17" src="<?php echo esc_url( includes_url( 'images/spinner-2x.gif' ) ); ?>" alt="Loading ..." /><a href="#" data-module="photon" class="activate button button-jetpack"><?php esc_html_e( 'Activate Photon', 'jetpack' ); ?></a>
			</p>
		</div>
		<?php
		//jitm is being viewed, track it
		$jetpack = Jetpack::init();
		$jetpack->stat( 'jitm', 'photon-viewed-' . JETPACK__VERSION );
		$jetpack->do_stats( 'server_side' );
	}

	/**
	 * Display message prompting user to enable auto-updates in WordPress.com.
	 *
	 * @since 3.8.2
	 */
	function manage_pi_msg() {
		$normalized_site_url = Jetpack::build_raw_urls( get_home_url() );
		$manage_active       = Jetpack::is_module_active( 'manage' );
		// If it's not an array, it means no JITM was dismissed
		$manage_pi_dismissed = self::is_jitm_dismissed();
		// Check if plugin has auto update already enabled in WordPress.com and don't show JITM in such case.
		$active_before = get_option( 'jetpack_previously_activated', array() );
		delete_option( 'jetpack_previously_activated' );
		$active_now                  = get_option( 'active_plugins', array() );
		$activated                   = array_diff( $active_now, $active_before );
		$auto_update_plugin_list     = Jetpack_Options::get_option( 'autoupdate_plugins', array() );
		$plugin_auto_update_disabled = false;
		foreach ( $activated as $plugin ) {
			if ( ! in_array( $plugin, $auto_update_plugin_list ) ) {
				// Plugin doesn't have auto updates enabled in WordPress.com yet.
				$plugin_auto_update_disabled = true;
				// We don't need to continue checking, it's ok to show JITM for this plugin.
				break;
			}
		}
		// Check if there isn't an auto_update_plugin filter set to false
		$plugin_updates                      = get_site_transient( 'update_plugins' );
		$plugin_updates                      = array_merge( $plugin_updates->response, $plugin_updates->no_update );
		$auto_update_not_disabled_for_plugin = false;
		foreach ( $activated as $plugin ) {
			if ( ! isset( $plugin_updates[$plugin] ) ) {
				continue;
			}
			if ( apply_filters( 'auto_update_plugin', true, $plugin_updates[$plugin] ) ) {
				// There's at least one plugin set cleared for auto updates
				$auto_update_not_disabled_for_plugin = true;
				// We don't need to continue checking, it's ok to show JITM for this round.
				break;
			}
		}

		if ( ( ! $manage_active || ! $manage_pi_dismissed ) && $plugin_auto_update_disabled && $auto_update_not_disabled_for_plugin ) :
			?>
			<div class="jp-jitm">
				<a href="#" data-module="manage-pi" class="dismiss"><span class="genericon genericon-close"></span></a>

				<div class="jp-emblem">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0" y="0" viewBox="0 0 172.9 172.9" enable-background="new 0 0 172.9 172.9" xml:space="preserve">
						<path d="M86.4 0C38.7 0 0 38.7 0 86.4c0 47.7 38.7 86.4 86.4 86.4s86.4-38.7 86.4-86.4C172.9 38.7 134.2 0 86.4 0zM83.1 106.6l-27.1-6.9C49 98 45.7 90.1 49.3 84l33.8-58.5V106.6zM124.9 88.9l-33.8 58.5V66.3l27.1 6.9C125.1 74.9 128.4 82.8 124.9 88.9z" />
					</svg>
				</div>
				<?php if ( ! $manage_active ) : ?>
					<p class="msg">
						<?php _e( 'Save time with automated plugin updates.', 'jetpack' ); ?>
					</p>
					<p>
						<img class="j-spinner hide" src="<?php echo esc_url( includes_url( 'images/spinner-2x.gif' ) ); ?>" alt="<?php echo esc_attr__( 'Loading...', 'jetpack' ); ?>" /><a href="#" data-module="manage" data-module-success="<?php esc_attr_e( 'Success!', 'jetpack' ); ?>" class="activate button"><?php esc_html_e( 'Activate remote management', 'jetpack' ); ?></a>
					</p>
				<?php elseif ( $manage_active ) : ?>
					<p>
						<?php esc_html_e( 'Save time with auto updates on WordPress.com', 'jetpack' ); ?>
					</p>
				<?php endif; // manage inactive
				?>
				<?php if ( ! $manage_pi_dismissed ) : ?>
					<p class="show-after-enable <?php echo $manage_active ? '' : 'hide'; ?>">
						<a href="<?php echo esc_url( 'https://wordpress.com/plugins/' . $normalized_site_url ); ?>" target="_blank" title="<?php esc_attr_e( 'Go to WordPress.com to enable auto-updates for plugins', 'jetpack' ); ?>" data-module="manage-pi" class="button button-jetpack launch show-after-enable"><?php if ( ! $manage_active ) : ?><?php esc_html_e( 'Enable auto-updates on WordPress.com', 'jetpack' ); ?><?php elseif ( $manage_active ) : ?><?php esc_html_e( 'Enable auto-updates', 'jetpack' ); ?><?php endif; // manage inactive ?></a>
					</p>
				<?php endif; // manage-pi inactive
				?>
			</div>
			<?php
			//jitm is being viewed, track it
			$jetpack = Jetpack::init();
			$jetpack->stat( 'jitm', 'manage-pi-viewed-' . JETPACK__VERSION );
			$jetpack->do_stats( 'server_side' );
		endif; // manage inactive
	}

	/**
	 * Display message in editor prompting user to compose entry in WordPress.com.
	 *
	 * @since 3.8.2
	 */
	function editor_msg() {
		global $typenow;
		if ( current_user_can( 'manage_options' ) ) {
			$normalized_site_url = Jetpack::build_raw_urls( get_home_url() );
			$editor_dismissed = isset( self::$jetpack_hide_jitm['editor'] );
			if ( ! $editor_dismissed ) :
			?>
			<div class="jp-jitm">
				<a href="#"  data-module="editor" class="dismiss"><span class="genericon genericon-close"></span></a>
				<div class="jp-emblem">
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0" y="0" viewBox="0 0 172.9 172.9" enable-background="new 0 0 172.9 172.9" xml:space="preserve">
						<path d="M86.4 0C38.7 0 0 38.7 0 86.4c0 47.7 38.7 86.4 86.4 86.4s86.4-38.7 86.4-86.4C172.9 38.7 134.2 0 86.4 0zM83.1 106.6l-27.1-6.9C49 98 45.7 90.1 49.3 84l33.8-58.5V106.6zM124.9 88.9l-33.8 58.5V66.3l27.1 6.9C125.1 74.9 128.4 82.8 124.9 88.9z"/>
					</svg>
				</div>
				<p class="msg">
					<?php esc_html_e( 'Try the brand new editor.', 'jetpack' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( 'https://wordpress.com/' . $typenow . '/' . $normalized_site_url ); ?>" target="_blank" title="<?php esc_attr_e( 'Write on WordPress.com', 'jetpack' ); ?>" data-module="editor" class="button button-jetpack launch show-after-enable"><?php esc_html_e( 'Write on WordPress.com', 'jetpack' ); ?></a>
				</p>
			</div>
			<?php
			//jitm is being viewed, track it
			$jetpack = Jetpack::init();
			$jetpack->stat( 'jitm', 'editor-viewed-' . JETPACK__VERSION );
			$jetpack->do_stats( 'server_side' );
			endif; // manage or editor inactive
		}
	}

	/*
	* Function to enqueue jitm css and js
	*/
	function jitm_enqueue_files( $hook ) {

		$wp_styles = new WP_Styles();
		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_style( 'jetpack-jitm-css', plugins_url( "css/jetpack-admin-jitm{$min}.css", JETPACK__PLUGIN_FILE ), false, JETPACK__VERSION . '-201243242' );
		$wp_styles->add_data( 'jetpack-jitm-css', 'rtl', true );

		//Build stats url for tracking manage button
		$jitm_stats_url = Jetpack::build_stats_url( array( 'x_jetpack-jitm' => 'wordpresstools' ) );

		// Enqueue javascript to handle jitm notice events
		wp_enqueue_script( 'jetpack-jitm-js', plugins_url( '_inc/jetpack-jitm.js', JETPACK__PLUGIN_FILE ),
			array( 'jquery' ), JETPACK__VERSION, true );
		wp_localize_script(
			'jetpack-jitm-js',
			'jitmL10n',
			array(
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'jitm_nonce'  => wp_create_nonce( 'jetpack-jitm-nonce' ),
				'photon_msgs' => array(
					'success' => __( 'Success! Photon is now actively optimizing and serving your images for free.', 'jetpack' ),
					'fail'    => __( 'We are sorry but unfortunately Photon did not activate.', 'jetpack' )
				),
				'manage_msgs' => array(
					'success' => __( 'Success! WordPress.com tools are now active.', 'jetpack' ),
					'fail'    => __( 'We are sorry but unfortunately Manage did not activate.', 'jetpack' )
				),
				'jitm_stats_url' => $jitm_stats_url
			)
		);
	}

	/**
	 * Check if a JITM was dismissed or not. Currently, dismissing one JITM will dismiss all of them.
	 *
	 * @since 3.8.2
	 *
	 * @return bool
	 */
	function is_jitm_dismissed() {
		if ( is_null( self::$jetpack_hide_jitm ) ) {
			// The option returns false when nothing was dismissed
			self::$jetpack_hide_jitm = Jetpack_Options::get_option( 'hide_jitm' );
		}
		// so if it's not an array, it means no JITM was dismissed
		return is_array( self::$jetpack_hide_jitm );
	}
}
/**
 * Filter to turn off all just in time messages
 *
 * @since 3.7.0
 *
 * @param bool true Whether to show just in time messages.
 */
if ( apply_filters( 'jetpack_just_in_time_msgs', false ) ) {
	Jetpack_JITM::init();
}