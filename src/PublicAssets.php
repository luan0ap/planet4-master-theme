<?php

namespace P4\MasterTheme;

/**
 * Wrapper class for the enqueue function because we can't autoload functions.
 */
final class PublicAssets {
	/**
	 * Enqueue theme scripts.
	 */
	public static function enqueue_js() {
		$js_creation = filectime( get_template_directory() . '/assets/build/index.js' );

		$jquery_should_wait = is_plugin_active( 'planet4-plugin-gutenberg-blocks/planet4-gutenberg-blocks.php' ) && ! is_user_logged_in();

		$jquery_deps = $jquery_should_wait ? [ 'planet4-blocks-script' ] : [];

		// JS files.
		wp_deregister_script( 'jquery' );
		wp_register_script(
			'jquery',
			'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js',
			$jquery_deps,
			'3.3.1',
			true
		);

		$theme_dir = get_template_directory_uri();
		// Variables reflected from PHP to the JS side.
		$localized_variables = [
			// The ajaxurl variable is a global js variable defined by WP itself but only for the WP admin
			// For the frontend we need to define it ourselves and pass it to js.
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
			'show_scroll_times' => Search::SHOW_SCROLL_TIMES,
		];

		wp_register_script(
			'main',
			$theme_dir . '/assets/build/index.js',
			[ 'jquery' ],
			$js_creation,
			true
		);
		wp_localize_script( 'main', 'localizations', $localized_variables );
		wp_enqueue_script( 'main' );
		wp_enqueue_script(
			'youtube',
			$theme_dir . '/assets/build/lite-yt-embed.js',
			[],
			1,
			true
		);

		// Sets translated strings for a JS script.
		wp_set_script_translations( 'main', 'planet4-master-theme', get_template_directory() . '/languages' );
	}

	/**
	 * Enqueue theme styles.
	 *
	 * Drop enqueuing styles if main file is not built.
	 */
	public static function enqueue_css() {
		$css_file = get_template_directory() . '/assets/build/style.min.css';
		if ( ! file_exists( $css_file ) ) {
			//phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			trigger_error( 'File ' . esc_url( $css_file ) . ' does not exist or is not accessible.' );
			return;
		}

		$theme_dir = get_template_directory_uri();
		// CSS files.
		wp_enqueue_style(
			'bootstrap',
			$theme_dir . '/assets/build/bootstrap.min.css',
			[],
			Loader::theme_file_ver( 'assets/build/bootstrap.min.css' )
		);

		// This loads a linked style file since the relative images paths are outside the build directory.
		wp_enqueue_style(
			'parent-style',
			$theme_dir . '/assets/build/style.min.css',
			[ 'bootstrap' ],
			filectime( $css_file )
		);

		self::conditionally_load_partials();
		self::load_blocks_assets();
	}

	/**
	 * Load any conditional CSS partials.
	 * We can further split partials for other things:
	 * - CSS per post type
	 * - CSS only loaded based on options (e.g. minimal navigation)
	 * - CSS specific to a particular page (e.g. search)
	 */
	private static function conditionally_load_partials(): void {
		$navbar_version = planet4_get_option( 'website_navigation_style', 'dark' );
		$navbar_file    = '/assets/build/navigation-bar-' . $navbar_version . '.min.css';
		Loader::enqueue_versioned_style( $navbar_file, 'navigation-bar', [ 'parent-style' ] );

		$country_selector_file = '/assets/build/country-selector.min.css';
		Loader::enqueue_versioned_style( $country_selector_file, 'country-selector', [ 'parent-style' ] );

		$post_type = get_post_type();
		if ( is_single() && in_array( $post_type, [ 'post', 'attachment', 'idea' ], true ) ) {
			Loader::enqueue_versioned_style(
				'/assets/build/post.min.css',
				'post-type--post',
				[ 'parent-style' ]
			);
		}
	}

	/**
	 * Load assets based on block presence in current page.
	 * If class \P4GBKS\Blocks\BlockList is not active, load everything.
	 *
	 * @todo Move \P4GBKS\Blocks\BlockList class to master-theme
	 */
	private static function load_blocks_assets(): void {
		$blocks_assets = [
			'gravityforms/form' => [
				[ 'gravity-forms-style', 'gravity-forms.min.css' ],
			],
		];

		// If possible, filter to keep only assets from blocks on page.
		if ( class_exists( '\P4GBKS\Blocks\BlockList' ) ) {
			$block_list    = \P4GBKS\Blocks\BlockList::get_block_list();
			$blocks_assets = array_intersect_key(
				$blocks_assets,
				array_fill_keys( $block_list, null )
			);
		}

		$assets = array_merge( ...array_values( $blocks_assets ) );

		array_walk(
			$assets,
			function ( $asset_data ): void {
				$handle = $asset_data[0];
				$file   = '/assets/build/' . $asset_data[1];
				'js' === pathinfo( $file, \PATHINFO_EXTENSION )
					? Loader::enqueue_versioned_script( $file, $handle )
					: Loader::enqueue_versioned_style( $file, $handle );
			}
		);
	}
}
