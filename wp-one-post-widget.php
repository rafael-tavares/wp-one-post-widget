<?php
/**
 * Plugin Name:       WP One Post Widget
 * Plugin URI:        https://wordpress.org/plugins/wp-one-post-widget/
 * Description:       Select one specific published post and display it in a widget area.
 * Version:           3.0.1
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Rafael Tavares
 * Author URI:
 * Text Domain:       wponepostwidget
 * Domain Path:       /language
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_ONE_POST_WIDGET_VERSION', '3.0.1' );
define( 'WP_ONE_POST_WIDGET_FILE', __FILE__ );
define( 'WP_ONE_POST_WIDGET_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_ONE_POST_WIDGET_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main widget class.
 */
class WP_One_Post_Widget extends WP_Widget {

	const ID_BASE = 'wp-one-post-widget';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			self::ID_BASE,
			__( 'WP One Post Widget', 'wponepostwidget' ),
			array(
				'classname'                   => 'wp_one_post_widget',
				'description'                 => __( 'Select one specific post and display it in a widget area.', 'wponepostwidget' ),
				'customize_selective_refresh' => true,
			)
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Instance settings.
	 */
	public function widget( $args, $instance ) {
		$instance = $this->normalize_instance( $instance );
		$post     = $this->get_selected_post( $instance );

		if ( ! $post ) {
			return;
		}

		$post_id      = (int) $post->ID;
		$custom_title = isset( $instance['custom_title'] ) ? (string) $instance['custom_title'] : '';
		$readmore     = isset( $instance['readmore'] ) ? (string) $instance['readmore'] : '';
		$use_thumb    = isset( $instance['use_thumbnail'] ) ? (string) $instance['use_thumbnail'] : 'no';
		$thumb_pos    = isset( $instance['thumbnail_position'] ) ? (string) $instance['thumbnail_position'] : 'left';

		$widget_title = '' !== $custom_title ? $custom_title : get_the_title( $post_id );
		$link         = get_permalink( $post_id );
		$excerpt      = $this->get_post_excerpt_for_display( $post );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-provided wrappers.
		echo $args['before_widget'];

		if ( '' !== $widget_title ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-provided wrappers.
			echo $args['before_title'] . esc_html( $widget_title ) . $args['after_title'];
		}

		$text = '<p>' . $excerpt;
		if ( '' !== $readmore && $link ) {
			$text .= ' <a href="' . esc_url( $link ) . '">' . esc_html( $readmore ) . '</a>';
		}
		$text .= '</p>';

		$thumb_html = '';
		if ( 'yes' === $use_thumb && $post_id > 0 && has_post_thumbnail( $post_id ) ) {
			$thumb_pos_safe = in_array( $thumb_pos, array( 'left', 'right', 'top' ), true ) ? $thumb_pos : 'left';
			$thumbnail_id  = (int) get_post_thumbnail_id( $post_id );

			if ( $thumbnail_id > 0 ) {
				$thumb = wp_get_attachment_image(
					$thumbnail_id,
					'thumbnail',
					false,
					array(
						'class' => 'wp-one-post-widget__thumbnail wp-one-post-widget__thumbnail--' . $thumb_pos_safe,
					)
				);
				if ( is_string( $thumb ) && '' !== $thumb ) {
					$thumb_html = $thumb;
				}
			}
		}

		// Wrapper is required to contain floats without theme-specific styling.
		echo '<div class="wp-one-post-widget__content">' . $thumb_html . $text . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme-provided wrappers.
		echo $args['after_widget'];
	}

	/**
	 * Admin form.
	 *
	 * @param array $instance Instance settings.
	 */
	public function form( $instance ) {
		$instance = $this->normalize_instance( $instance );
		$post     = $this->get_selected_post( $instance );

		$post_id          = $post ? (int) $post->ID : (int) $instance['post_id'];
		$post_title_field = $post ? get_the_title( $post ) : (string) $instance['title'];
		$custom_title     = (string) $instance['custom_title'];
		$readmore         = (string) $instance['readmore'];
		$use_thumbnail    = (string) $instance['use_thumbnail'];
		$thumb_pos        = (string) $instance['thumbnail_position'];

		$search_id   = $this->get_field_id( 'post_search' );
		$search_desc = $this->get_field_id( 'post_search_desc' );
		?>
		<div class="wp-one-post-widget-form">
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'custom_title' ) ); ?>">
					<?php esc_html_e( 'Custom Title', 'wponepostwidget' ); ?>
				</label>
				<input
					class="widefat"
					type="text"
					id="<?php echo esc_attr( $this->get_field_id( 'custom_title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'custom_title' ) ); ?>"
					value="<?php echo esc_attr( $custom_title ); ?>"
				/>
			</p>

			<p>
				<label for="<?php echo esc_attr( $search_id ); ?>">
					<?php esc_html_e( 'Search the content for the keyword and select', 'wponepostwidget' ); ?>
				</label>
				<input
					class="widefat wp-one-post-widget-search"
					type="text"
					id="<?php echo esc_attr( $search_id ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'post_search' ) ); ?>"
					value="<?php echo esc_attr( $post_title_field ); ?>"
					placeholder="<?php echo esc_attr__( 'keyword...', 'wponepostwidget' ); ?>"
					autocomplete="off"
					aria-autocomplete="list"
					aria-describedby="<?php echo esc_attr( $search_desc ); ?>"
				/>
				<span id="<?php echo esc_attr( $search_desc ); ?>" class="screen-reader-text">
					<?php esc_html_e( 'Type to search published posts, then choose a result with the arrow keys and Enter.', 'wponepostwidget' ); ?>
				</span>
				<input
					class="wp-one-post-widget-post-id"
					type="hidden"
					id="<?php echo esc_attr( $this->get_field_id( 'post_id' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'post_id' ) ); ?>"
					value="<?php echo esc_attr( (string) $post_id ); ?>"
				/>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'readmore' ) ); ?>">
					<?php esc_html_e( 'Label Read More', 'wponepostwidget' ); ?>
				</label>
				<input
					class="widefat"
					type="text"
					id="<?php echo esc_attr( $this->get_field_id( 'readmore' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'readmore' ) ); ?>"
					value="<?php echo esc_attr( $readmore ); ?>"
				/>
			</p>

			<fieldset class="wp-one-post-widget-options">
				<legend><?php esc_html_e( 'Use Thumbnail?', 'wponepostwidget' ); ?></legend>
				<label>
					<input
						type="radio"
						name="<?php echo esc_attr( $this->get_field_name( 'use_thumbnail' ) ); ?>"
						value="yes"
						<?php checked( $use_thumbnail, 'yes' ); ?>
					/>
					<?php esc_html_e( 'Yes', 'wponepostwidget' ); ?>
				</label>
				<label>
					<input
						type="radio"
						name="<?php echo esc_attr( $this->get_field_name( 'use_thumbnail' ) ); ?>"
						value="no"
						<?php checked( $use_thumbnail, 'no' ); ?>
					/>
					<?php esc_html_e( 'No', 'wponepostwidget' ); ?>
				</label>
			</fieldset>

			<fieldset class="wp-one-post-widget-options">
				<legend><?php esc_html_e( 'Thumbnail Position', 'wponepostwidget' ); ?></legend>
				<label>
					<input
						type="radio"
						name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_position' ) ); ?>"
						value="left"
						<?php checked( $thumb_pos, 'left' ); ?>
					/>
					<?php esc_html_e( 'Left', 'wponepostwidget' ); ?>
				</label>
				<label>
					<input
						type="radio"
						name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_position' ) ); ?>"
						value="right"
						<?php checked( $thumb_pos, 'right' ); ?>
					/>
					<?php esc_html_e( 'Right', 'wponepostwidget' ); ?>
				</label>
				<label>
					<input
						type="radio"
						name="<?php echo esc_attr( $this->get_field_name( 'thumbnail_position' ) ); ?>"
						value="top"
						<?php checked( $thumb_pos, 'top' ); ?>
					/>
					<?php esc_html_e( 'Top', 'wponepostwidget' ); ?>
				</label>
			</fieldset>
		</div>
		<?php
	}

	/**
	 * Sanitize instance on save.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $this->normalize_instance( is_array( $old_instance ) ? $old_instance : array() );

		$instance['custom_title'] = isset( $new_instance['custom_title'] )
			? sanitize_text_field( $new_instance['custom_title'] )
			: '';
		$instance['readmore'] = isset( $new_instance['readmore'] )
			? sanitize_text_field( $new_instance['readmore'] )
			: '';

		$use_thumbnail = isset( $new_instance['use_thumbnail'] ) ? sanitize_key( (string) $new_instance['use_thumbnail'] ) : 'no';
		$instance['use_thumbnail'] = in_array( $use_thumbnail, array( 'yes', 'no' ), true ) ? $use_thumbnail : 'no';

		$thumb_pos = isset( $new_instance['thumbnail_position'] ) ? sanitize_key( (string) $new_instance['thumbnail_position'] ) : 'left';
		$instance['thumbnail_position'] = in_array( $thumb_pos, array( 'left', 'right', 'top' ), true ) ? $thumb_pos : 'left';

		$post_id = isset( $new_instance['post_id'] ) ? absint( $new_instance['post_id'] ) : 0;
		$search  = isset( $new_instance['post_search'] ) ? sanitize_text_field( $new_instance['post_search'] ) : '';

		if ( $post_id > 0 && $this->is_valid_published_post( $post_id ) ) {
			$instance['post_id'] = $post_id;
			$instance['title']   = get_the_title( $post_id );
		} elseif ( '' !== $search ) {
			$resolved = $this->find_published_post_id_by_title( $search );
			if ( $resolved > 0 ) {
				$instance['post_id'] = $resolved;
				$instance['title']   = get_the_title( $resolved );
			} else {
				$instance['title'] = $search;
			}
		}

		return $instance;
	}

	/**
	 * Normalize instance defaults and migrate title → post_id when possible.
	 *
	 * @param array $instance Raw instance.
	 * @return array
	 */
	public function normalize_instance( $instance ) {
		$instance = is_array( $instance ) ? $instance : array();

		$defaults = array(
			'post_id'            => 0,
			'title'              => '',
			'custom_title'       => '',
			'readmore'           => '',
			'use_thumbnail'      => 'no',
			'thumbnail_position' => 'left',
			'post_search'        => '',
		);
		$instance = array_merge( $defaults, $instance );

		$instance['post_id']      = absint( $instance['post_id'] );
		$instance['title']        = sanitize_text_field( (string) $instance['title'] );
		$instance['custom_title'] = sanitize_text_field( (string) $instance['custom_title'] );
		$instance['readmore']     = sanitize_text_field( (string) $instance['readmore'] );

		if ( ! in_array( $instance['use_thumbnail'], array( 'yes', 'no' ), true ) ) {
			$instance['use_thumbnail'] = 'no';
		}
		if ( ! in_array( $instance['thumbnail_position'], array( 'left', 'right', 'top' ), true ) ) {
			$instance['thumbnail_position'] = 'left';
		}

		if ( $instance['post_id'] <= 0 && '' !== $instance['title'] ) {
			$resolved = $this->find_published_post_id_by_title( $instance['title'] );
			if ( $resolved > 0 ) {
				$instance['post_id'] = $resolved;
			}
		}

		return $instance;
	}

	/**
	 * Resolve selected post object.
	 *
	 * @param array $instance Normalized instance.
	 * @return WP_Post|null
	 */
	private function get_selected_post( $instance ) {
		$post_id = isset( $instance['post_id'] ) ? absint( $instance['post_id'] ) : 0;

		if ( $post_id <= 0 && ! empty( $instance['title'] ) ) {
			$post_id = $this->find_published_post_id_by_title( (string) $instance['title'] );
		}

		if ( $post_id <= 0 || ! $this->is_valid_published_post( $post_id ) ) {
			return null;
		}

		$post = get_post( $post_id );
		return ( $post instanceof WP_Post ) ? $post : null;
	}

	/**
	 * Whether ID is a published post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	private function is_valid_published_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return false;
		}
		return ( 'post' === $post->post_type && 'publish' === $post->post_status );
	}

	/**
	 * Find a published post by exact title (legacy compatibility).
	 *
	 * @param string $title Post title.
	 * @return int
	 */
	private function find_published_post_id_by_title( $title ) {
		$title = trim( (string) $title );
		if ( '' === $title ) {
			return 0;
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'post',
				'post_status'            => 'publish',
				'title'                  => $title,
				'posts_per_page'         => 1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( empty( $query->posts ) || ! ( $query->posts[0] instanceof WP_Post ) ) {
			return 0;
		}

		return (int) $query->posts[0]->ID;
	}

	/**
	 * Build excerpt for frontend using WordPress APIs (no raw substr on HTML).
	 *
	 * @param WP_Post $post Post object.
	 * @return string Escaped/safe HTML string for output inside a paragraph.
	 */
	private function get_post_excerpt_for_display( $post ) {
		$post_id = (int) $post->ID;

		if ( $post_id > 0 && has_excerpt( $post_id ) ) {
			return wp_kses_post( (string) $post->post_excerpt );
		}

		$content = (string) $post->post_content;
		$content = strip_shortcodes( $content );
		if ( function_exists( 'excerpt_remove_blocks' ) ) {
			$content = excerpt_remove_blocks( $content );
		}
		$content = wp_strip_all_tags( $content );
		$content = trim( preg_replace( '/\s+/u', ' ', $content ) );

		if ( '' === $content ) {
			return '';
		}

		// Preserve approximate legacy length (~100 chars) with multibyte-safe cutting.
		return esc_html( wp_html_excerpt( $content, 100, '&hellip;' ) );
	}
}

/**
 * Bootstrap plugin.
 */
function wp_one_post_widget_bootstrap() {
	load_plugin_textdomain(
		'wponepostwidget',
		false,
		dirname( plugin_basename( WP_ONE_POST_WIDGET_FILE ) ) . '/language/'
	);

	wp_one_post_widget_maybe_migrate_legacy_options();
}
add_action( 'plugins_loaded', 'wp_one_post_widget_bootstrap' );

/**
 * Register widget.
 */
function wp_one_post_widget_register_widget() {
	register_widget( 'WP_One_Post_Widget' );
}
add_action( 'widgets_init', 'wp_one_post_widget_register_widget' );

/**
 * Migrate legacy option store into WP_Widget option format (non-destructive).
 */
function wp_one_post_widget_maybe_migrate_legacy_options() {
	$legacy = get_option( 'wp_one_post_widget' );
	if ( ! is_array( $legacy ) || empty( $legacy ) ) {
		return;
	}

	$new_option = 'widget_' . WP_One_Post_Widget::ID_BASE;
	$existing   = get_option( $new_option );

	$has_numeric = false;
	if ( is_array( $existing ) ) {
		foreach ( array_keys( $existing ) as $key ) {
			if ( is_numeric( $key ) ) {
				$has_numeric = true;
				break;
			}
		}
	}

	if ( $has_numeric ) {
		return;
	}

	$widget   = new WP_One_Post_Widget();
	$migrated = array( '_multiwidget' => 1 );

	foreach ( $legacy as $number => $instance ) {
		if ( ! is_numeric( $number ) || ! is_array( $instance ) ) {
			continue;
		}
		$migrated[ (int) $number ] = $widget->normalize_instance( $instance );
	}

	if ( count( $migrated ) <= 1 ) {
		return;
	}

	update_option( $new_option, $migrated );
	update_option( 'wp_one_post_widget_legacy_migrated', 1, false );
}

/**
 * Asset version based on filemtime, with plugin version fallback.
 *
 * @param string $relative_path Path relative to the plugin root (e.g. css/file.css).
 * @return string Version string for wp_enqueue_*.
 */
function wp_one_post_widget_asset_version( $relative_path ) {
	$relative_path = ltrim( str_replace( '\\', '/', (string) $relative_path ), '/' );
	$file          = WP_ONE_POST_WIDGET_DIR . $relative_path;

	if ( is_string( $file ) && is_file( $file ) ) {
		$mtime = filemtime( $file );
		if ( false !== $mtime ) {
			return (string) $mtime;
		}
	}

	return WP_ONE_POST_WIDGET_VERSION;
}

/**
 * Frontend stylesheet — only when this widget is active.
 */
function wp_one_post_widget_enqueue_front_assets() {
	if ( is_admin() ) {
		return;
	}

	if ( ! is_active_widget( false, false, WP_One_Post_Widget::ID_BASE, true ) && ! is_customize_preview() ) {
		return;
	}

	$relative = 'css/wp-one-post-widget.css';

	wp_enqueue_style(
		'wp-one-post-widget',
		WP_ONE_POST_WIDGET_URL . $relative,
		array(),
		wp_one_post_widget_asset_version( $relative )
	);
}
add_action( 'wp_enqueue_scripts', 'wp_one_post_widget_enqueue_front_assets' );

/**
 * Admin assets only on widget-related screens.
 *
 * @param string $hook Current admin page hook.
 */
function wp_one_post_widget_enqueue_admin_assets( $hook ) {
	$allowed = array(
		'widgets.php',
		'customize.php',
		'appearance_page_gutenberg-widgets',
	);

	if ( ! in_array( $hook, $allowed, true ) ) {
		return;
	}

	$admin_css = 'css/wp-one-post-admin.css';
	$admin_js  = 'js/wp-one-post-admin.js';

	wp_enqueue_style(
		'wp-one-post-admin',
		WP_ONE_POST_WIDGET_URL . $admin_css,
		array(),
		wp_one_post_widget_asset_version( $admin_css )
	);

	wp_enqueue_script( 'jquery-ui-autocomplete' );

	wp_enqueue_script(
		'wp-one-post-admin',
		WP_ONE_POST_WIDGET_URL . $admin_js,
		array( 'jquery', 'jquery-ui-autocomplete' ),
		wp_one_post_widget_asset_version( $admin_js ),
		true
	);

	wp_localize_script(
		'wp-one-post-admin',
		'wpOnePostWidget',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wp_one_post_widget_search' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'wp_one_post_widget_enqueue_admin_assets' );

/**
 * AJAX autocomplete for published posts.
 */
function wp_one_post_widget_ajax_search() {
	if ( ! check_ajax_referer( 'wp_one_post_widget_search', 'nonce', false ) ) {
		wp_send_json( array(), 403 );
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json( array(), 403 );
	}

	$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
	$term = trim( $term );

	if ( '' === $term ) {
		wp_send_json( array() );
	}

	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => 'publish',
			's'                      => $term,
			'posts_per_page'         => 20,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$results = array();
	foreach ( $query->posts as $post ) {
		if ( ! ( $post instanceof WP_Post ) ) {
			continue;
		}
		$title     = html_entity_decode( get_the_title( $post ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$results[] = array(
			'id'    => (int) $post->ID,
			'label' => $title,
			'value' => $title,
		);
	}

	wp_send_json( $results );
}
add_action( 'wp_ajax_wp_one_post_widget_search', 'wp_one_post_widget_ajax_search' );
