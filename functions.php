<?php
/**
 * Functions
 *
 * @package P4MT
 */

// Composer install might have happened inside the master-theme directory, instead of the app root. Specifically for
// php lint and unit test job this is the case.
// Also this helps with local development environments that pulled in changes for master-theme but did not rerun
// base-fork's composer installation, which is currently tricky to do as it puts the fetched versions of master theme
// and the plugins into the theme and plugin folders, which might be messy if you have changes there.
// With this fallback for tests in place, you can just run composer dump autoload in master-theme.
// Probably there's a better way to handle this, but for now let's try load both.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	require_once __DIR__ . '/../../../../vendor/autoload.php';
}

/**
 * A simpler way to add a filter that only returns a static value regardless of the input.
 *
 * @param string   $filter_name The WordPress filter.
 * @param mixed    $value The value to be returned by the filter.
 * @param int|null $priority The priority for the fitler.
 *
 * @return void
 */
function simple_value_filter( string $filter_name, $value, $priority = null ): void {
	add_filter(
		$filter_name,
		static function () use ( $value ) {
			return $value;
		},
		$priority,
		0
	);
}

/**
 * Generate a bunch of placeholders for use in an IN query.
 * Unfortunately WordPress doesn't offer a way to do bind IN statement params, it would be a lot easier if we could pass
 * the array to wpdb->prepare as a whole.
 *
 * @param array  $items The items to generate placeholders for.
 * @param int    $start_index The start index to use for creating the placeholders.
 * @param string $type The type of value.
 *
 * @return string The generated placeholders string.
 */
function generate_list_placeholders( array $items, int $start_index, $type = 'd' ): string {
	$placeholders = [];
	foreach ( range( $start_index, count( $items ) + $start_index - 1 ) as $i ) {
		$placeholder = "%{$i}\${$type}";
		// Quote it if it's a string.
		if ( 's' === $type ) {
			$placeholder = "'{$placeholder}'";
		}
		$placeholders[] = $placeholder;
	}

	return implode( ',', $placeholders );
}

/**
 * Wrapper function around cmb2_get_option.
 *
 * @param string $key Options array key.
 * @param bool   $default The default value to use if the options is not set.
 * @return mixed Option value.
 */
function planet4_get_option( $key = '', $default = null ) {
	$options = get_option( 'planet4_options' );

	return $options[ $key ] ?? $default;
}

use P4\MasterTheme\Features\ListingPagePagination;
use P4\MasterTheme\ImageArchive\Rest;
use P4\MasterTheme\Loader;
use P4\MasterTheme\Notifications\Slack;
use P4\MasterTheme\Post;
use Timber\Timber;

if ( ! class_exists( 'Timber' ) ) {
	add_action(
		'admin_notices',
		function() {
			printf(
				'<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="%s">Plugins menu</a></p></div>',
				esc_url( admin_url( 'plugins.php#timber' ) )
			);
		}
	);

	add_filter(
		'template_include',
		function( $template ) {
			return get_stylesheet_directory() . '/static/no-timber.html';
		}
	);

	return;
} else {
	// Enable Timber template cache unless this is a debug environment.
	if ( defined( 'WP_DEBUG' ) && is_bool( WP_DEBUG ) ) {
		Timber::$cache = ! WP_DEBUG;
	} else {
		Timber::$cache = true;
	}
}
add_action(
	'rest_api_init',
	function () {
		Rest::register_endpoints();
	}
);

// Ensure no actions trigger a purge everything.
simple_value_filter( 'cloudflare_purge_everything_actions', [] );
// Remove the menu item to the Cloudflare page.
add_action(
	'admin_menu',
	function () {
		remove_submenu_page( 'options-general.php', 'cloudflare' );
	}
);
// remove_submenu_page does not prevent accessing the page. Add a higher prio action that dies instead.
add_action(
	'settings_page_cloudflare',
	function () {
		die( 'This page is blocked to prevent excessive cache purging.' );
	},
	1
);

/**
 * Hide core updates notification in the dashboard, to avoid confusion while an upgrade is already in progress.
 */
function hide_wp_update_nag() {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_filter( 'update_footer', 'core_update_footer' );
}

add_action( 'admin_menu', 'hide_wp_update_nag' );

require_once 'load-class-aliases.php';

Loader::get_instance();

add_action(
	'notification/elements',
	static function () {
		notification_register_carrier( new Slack( 'slack', 'Slack' ) );
	}
);

// WP core's escaping logic doesn't take the case into account where a gradient is followed by a URL.
add_filter(
	'safecss_filter_attr_allow_css',
	function ( bool $allow_css, $css_test_string ) {
		// Short circuit in case the CSS is already allowed.
		// This filter only runs to catch the case where it's not allowed but should be.
		if ( $allow_css ) {
			return true;
		}

		$without_property = preg_replace( '/.*:/', '', $css_test_string );

		// Same regex as in WordPress core, except it matches anywhere in the string.
		// See https://github.com/WordPress/WordPress/blob/a5293aa581802197b0dd7c42813ba137708ad0e1/wp-includes/kses.php#L2438.
		$gradient_regex = '/(repeating-)?(linear|radial|conic)-gradient\(([^()]|rgb[a]?\([^()]*\))*\)/';

		// Check if a gradient is still present. The only case where $css_test_string can still have this present is if it
		// was missed by the faulty WP regex.
		if ( ! preg_match( $gradient_regex, $css_test_string ) ) {
			return $allow_css;
		}

		$without_gradient = preg_replace( $gradient_regex, '', trim( $without_property ) );

		return trim( $without_gradient, ', ' ) === '';
	},
	10,
	2
);
add_action(
	'wpml_after_update_attachment_texts',
	function ( $original_attachment_id, $translation ) {
		$original_sm_cloud = get_post_meta( $original_attachment_id, 'sm_cloud', true );
		update_post_meta( $translation->element_id, 'sm_cloud', $original_sm_cloud );
	},
	1,
	2
);

/**
 * This is not a column in WordPress by default, but is added by the Post Type Switcher plugin.
 * It's not needed for the plugin to work, and needlessly takes up space on pages where everything has the same post
 * type.
 *
 * Showing the field is only somewhat useful when using quick edit to switch a single post from the admin listing page.
 */
add_filter(
	'default_hidden_columns',
	function ( $hidden ) {
		$hidden[] = 'post_type';

		return $hidden;
	},
	10,
	1
);

/**
 * TODO: Move to editor only area.
 * Set the editor width per post type.
 */
add_filter(
	'block_editor_settings_all',
	function ( array $editor_settings, WP_Block_Editor_Context $block_editor_context ) {
		if ( 'post' !== $block_editor_context->post->post_type ) {
			$editor_settings['__experimentalFeatures']['layout']['contentSize'] = '1320px';
		}

		return $editor_settings;
	},
	10,
	2
);

/**
 * I'll move this somewhere else in master theme.
 *
 * @return void
 */
function register_more_blocks() {
	register_block_type(
		'p4/reading-time',
		[
			'render_callback' => [ Post::class, 'reading_time_block' ],
			'uses_context'    => [ 'postId' ],
		]
	);
	register_block_type(
		'p4/post-author-name',
		[
			'render_callback' => function ( array $attributes, $content, $block ) {
				$author_override = get_post_meta( $block->context['postId'], 'p4_author_override', true );
				$post_author_id  = get_post_field( 'post_author', $block->context['postId'] );

				$is_override = ! empty( $author_override );

				$name = $is_override ? $author_override : get_the_author_meta( 'display_name', $post_author_id );
				$link = $is_override ? '#' : get_author_posts_url( $post_author_id );

				$block_content = $author_override ? $name : "<a href='$link'>$name</a>";

				return "<span class='article-list-item-author'>$block_content</span>";
			},
			'uses_context'    => [ 'postId' ],
		]
	);
	// Like the core block but with an appropriate sizes attribute.
	register_block_type(
		'p4/post-featured-image',
		[
			'render_callback' => function ( array $attributes, $content, $block ) {
				$post_id        = $block->context['postId'];
				$post_link      = get_permalink( $post_id );
				$featured_image = get_the_post_thumbnail(
					$post_id,
					null,
					// For now hard coded sizes to the ones from Articles, as it's the single usage.
					// This can be made a block attribute, or even construct a sizes attr with math based on context.
					// For example, it could already access displayLayout from Query block to know how many columns are
					// being rendered. If it then also knows the flex gap and container width, it should have all needed
					// info to support a large amount of cases.
					[ 'sizes' => '(min-width: 1600px) 389px, (min-width: 1200px) 335px, (min-width: 1000px) 281px, (min-width: 780px) 209px, (min-width: 580px) 516px, calc(100vw - 24px)' ]
				);

				return "<a href='$post_link'>$featured_image</a>";
			},
			'uses_context'    => [ 'postId' ],
		]
	);
}

add_action( 'init', 'register_more_blocks' );

add_filter(
	'cloudflare_purge_by_url',
	function ( $urls, $post_id ) {
		if ( ! ListingPagePagination::is_active() ) {
			return $urls;
		}
		$new_urls = [];
		// Most of this logic is copied from the start of \CF\WordPress\Hooks::getPostRelatedLinks.
		// I had to adapt it to our CS, it used snake case and old arrays.
		// I only changed the part that creates the pagination URLs.
		// And for now early return on other taxonomies as only tags need it.
		$post_type = get_post_type( $post_id );

		// Purge taxonomies terms and feeds URLs.
		$post_type_taxonomies = get_object_taxonomies( $post_type );

		foreach ( $post_type_taxonomies as $taxonomy ) {
			// Only do post tags for now, but we'll need this loop when more pages have pagination.
			if ( 'post_tag' !== $taxonomy ) {
				continue;
			}
			// Only if taxonomy is public.
			$taxonomy_data = get_taxonomy( $taxonomy );
			if ( $taxonomy_data instanceof WP_Taxonomy && false === $taxonomy_data->public ) {
				continue;
			}

			$terms = get_the_terms( $post_id, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );

				if ( ! is_wp_error( $term_link ) ) {
					$args = [
						'post_type'      => 'post',
						'post_status'    => 'publish',
						'posts_per_page' => - 1,
						'tax_query'      => [
							'relation' => 'AND',
							[
								'taxonomy' => $taxonomy,
								'field'    => 'id',
								'terms'    => [ $term->term_id ],
							],
						],
					];

					$query = new WP_Query( $args );
					$pages = $query->post_count / get_option( 'posts_per_page', 10 );
					if ( $pages > 1 ) {
						$numbers = range( 2, 1 + round( $pages ) );

						$new_urls = array_map( fn( $i ) => "{$term_link}page/{$i}/", $numbers );
					}
				}
			}
		}

		return array_merge( $urls, $new_urls );
	},
	10,
	2
);

// Calls attachment metadata update on importer job.
// This triggers the wp-stateless hook (if it exists),
// which sets the sm_cloud metadata for the uploaded file.
// Wp-stateless is then able to find the file on GCS on step 2,
// instead of looking for it in the local uploads folder.
add_action(
	'add_attachment',
	function ( $post_id ): void {
		if (
			! defined( 'WP_IMPORTING' )
			|| ! WP_IMPORTING
			|| ! isset( $_GET['step'] ) // phpcs:ignore WordPress.Security.NonceVerification
			|| '1' !== $_GET['step'] // phpcs:ignore WordPress.Security.NonceVerification
			|| ! class_exists( 'wpCloud\StatelessMedia\Bootstrap' )
		) {
			return;
		}

		if ( version_compare( \wpCloud\StatelessMedia\Bootstrap::$version, '3.0', '<' ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'attachment' !== $post->post_type ) {
			return;
		}

		$cloud_meta = get_post_meta( $post_id, 'sm_cloud', true );
		if ( ! empty( $cloud_meta ) ) {
			return;
		}

		$metadata = wp_get_attachment_metadata( $post_id );
		wp_update_attachment_metadata( $post_id, $metadata );
	},
	99
);

// WP Stateless plugin short-circuits the image_downsize() process
// with wpCloud\StatelessMedia\Bootstrap::image_downsize().
// Contrary to the native function, it will return attachment data
// even if the attachment is not an image.
// The attachment is then treated as an image by the function
// wp_get_attachment_link() generating the link, even for a PDF.
// We overrule wp-stateless response if file is not an image.
add_filter(
	'image_downsize',
	function ( $downsize, $id, $size ) {
		return wp_attachment_is_image( $id ) ? $downsize : false;
	},
	100,
	3
);

// This action overrides the WordPress functionality for adding a notice message
// https://github.com/WordPress/wordpress-develop/blob/trunk/src/wp-admin/edit-form-blocks.php#L303-L305
// When it's a page for posts.
add_action(
	'admin_enqueue_scripts',
	function() {
		global $post;

		if ( $post && (int) get_option( 'page_for_posts' ) === $post->ID ) {
			// Adding this style, it works as a workaround for editors
			// To disable the ability to edit the content of the listing page.
			echo '<style>
				.edit-post-header-toolbar,
				.block-list-appender {
					pointer-events: none;
					opacity: 0;
				}

				.block-editor-block-list__layout {
					display: none;
				}

				.components-notice__actions {
					display: inline-flex !important;
					margin-left: 5px;
				}
			</style>';

			wp_add_inline_script(
				'wp-notices',
				sprintf(
					'wp.data.dispatch( "core/notices" ).createNotice("warning", "%s" , { isDismissible: false, actions: [ { label: "%s", url: "/wp-admin/options-reading.php"} ] } )',
					__( 'The content on this page is hidden because this page is being used as your \"All Posts\" listing page. You can disable this by un-setting the \"Posts page\"', 'planet4-master-theme' ),
					__( 'here', 'planet4-master-theme' )
				)
			);
		}
	},
	100
);

// The new `pagenum` query_var is used ONLY trough the default listing pages (index.php),
// when the Load more feature is enabled.
// Also added in replacement of the `page` query_var since that param is returning always zero.
add_filter(
	'query_vars',
	function( $vars ) {
		$vars[] = 'page_num';
		return $vars;
	}
);
