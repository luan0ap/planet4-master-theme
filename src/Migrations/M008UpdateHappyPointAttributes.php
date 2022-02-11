<?php

declare(strict_types=1);

namespace P4\MasterTheme\Migrations;

use P4\MasterTheme\MigrationRecord;
use P4\MasterTheme\MigrationScript;
use P4GBKS\Search\BlockSearch;
use WP_Block_Parser;

/**
 * Update Happy Point attributes to latest version
 */
class M008UpdateHappyPointAttributes extends MigrationScript {
	/**
	 * Perform the actual migration.
	 *
	 * @param MigrationRecord $record Information on the execution, can be used to add logs.
	 *
	 * @return void
	 */
	public static function execute( MigrationRecord $record ): void {
		$search     = new BlockSearch();
		$parser     = new WP_Block_Parser();
		$block_name = 'planet4-blocks/happypoint';

		$post_ids = $search->get_posts_with_block( $block_name );
		if ( empty( $post_ids ) ) {
			return;
		}

		$args  = [
			'include'     => $post_ids,
			'post_type'   => [ 'post', 'page', 'campaign' ],
			'post_status' => [ 'publish', 'private', 'draft' ],
		];
		$posts = get_posts( $args ) ?? [];

		foreach ( $posts as $post ) {
			if ( empty( $post->post_content ) ) {
				continue;
			}

			$blocks          = $parser->parse( $post->post_content );
			$updated_content = $post->post_content;

			foreach ( $blocks as $block ) {
				// Skip other blocks.
				if ( ! isset( $block['blockName'] ) || $block['blockName'] !== $block_name ) {
					continue;
				}

				$current_html = self::compile_block_html( $block );
				if ( empty( $current_html )
					|| false === strpos( $post->post_content, $current_html )
				) {
					// Compilation function error or invalid output, skipping.
					continue;
				}

				$updated_block = self::update_block( $block );
				$updated_html  = self::compile_block_html( $updated_block );

				echo "\n", $post->ID, ", ", $post->post_title, "\n";
				echo $current_html, "\n";
				echo $updated_html, "\n";

				$updated_content = str_replace($current_html, $updated_html, $updated_content);
			}

			// Blocks were updated, saving.
			if ( $updated_content !== $post->post_content ) {
				echo "Updating...\n";
				wp_update_post( ['ID' => $post->ID, 'post_content' => $updated_content] );
				echo "Done.\n\n";
			}
		}
	}

	/**
	 * Update block data to last version.
	 *
	 * @param array Block.
	 *
	 * @return array Updated block.
	 */
	public static function update_block( array $block ): array {
		// Filter already updated blocks
		if ( isset( $block['attrs']['override_content'] ) ) {
			return $block;
		}

		// new attributes
		$block['attrs']['override_content'] = true;

		$iframe_checked = $block['attrs']['mailing_list_iframe'] ?? false;
		$block['attrs']['local_content_provider'] = $iframe_checked ? 'iframe_url' : 'none';

		// unset deprecated attributes
		unset( $block['attrs']['load_iframe'] );
		unset( $block['attrs']['mailing_list_iframe'] );

		return $block;
	}

	/**
	 * Compile block to HTML version.
	 *
	 * @param array Block.
	 *
	 * @return string Block HTML.
	 */
	public static function compile_block_html( array $block ): string {
		if ( empty( $block['innerHTML'] ) ) {
			return sprintf(
				'<!-- wp:%s %s /-->',
				$block['blockName'],
				wp_json_encode( $block['attrs'] )
			);
		}

		return sprintf(
			'<!-- wp:%s %s -->%s<!-- /wp:%s -->',
			$block['blockName'],
			wp_json_encode( $block['attrs'] ),
			$block['innerHTML'],
			$block['blockName']
		);
	}
}
