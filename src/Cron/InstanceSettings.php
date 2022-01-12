<?php

declare(strict_types=1);

namespace P4\MasterTheme\Cron;

use P4\MasterTheme\Settings as Planet4Settings;
use Google\Client;
use Google\Service\Sheets;
use P4\MasterTheme\GoogleDocsClient;

/**
 * Instance settings API
 */
class InstanceSettings {
	/**
	 * Pushes settings infos to google sheet.
	 *
	 * @param string $sheet_id The sheet identifier.
	 */
	public static function push_to_google_sheet( string $sheet_id ): void {
		$docs_client = GoogleDocsClient::from_account_config( '' );
		$service     = new Sheets( $docs_client->client );

		$data = [
			'planet4_options' => self::list_planet4_settings(),
			'plugins'         => self::list_plugins(),
			'themes'          => self::list_themes(),
		];

		$sheet_name   = 'Feuille 1';
		$input_option = 'USER_ENTERED';

		$header_range = $sheet_name . '!A1:1';
		$header_row   = $service->spreadsheets_values->get(
			$sheet_id,
			$header_range
		)[0] ?? [];

		$name_col  = $service->spreadsheets_values->get(
			$sheet_id,
			$sheet_name . '!A2:A'
		) ?? [];
		$sites_col = array_map(
			fn ( $r ) => $r[0] ?? '',
			$name_col->getValues() ?? []
		);

		$first_value_col = $service->spreadsheets_values->get(
			$sheet_id,
			$sheet_name . '!C1:C'
		) ?? [];

		// Sheet header.
		$header_is_empty = empty( array_filter( $header_row ) );
		if ( $header_is_empty ) {
			$service->spreadsheets_values->update(
				$sheet_id,
				$header_range,
				new Sheets\ValueRange(
					[
						'values' => [ [
							'Domain',
							'Lang',
							...array_keys( $data['planet4_options']['en'] ),
						] ],
					]
				),
				[ 'valueInputOption' => $input_option ]
			);
			// Memorize new header row.
			$header_row = $service->spreadsheets_values->get(
				$sheet_id,
				$header_range
			)[0];
		}

		$site_url    = site_url();
		$site_index  = array_search( $site_url, $sites_col, true );
		$site_exists = false !== $site_index;
		// Sites starting at row 2 so index + 2.
		$site_row = $site_exists
			? $site_index + 2
			: count( $first_value_col->getValues() ) + 2;

		// Site name and values.
		$site_range = sprintf( '%s!A%d:%d', $sheet_name, $site_row, $site_row );
		$service->spreadsheets_values->update(
			$sheet_id,
			$site_range,
			new Sheets\ValueRange(
				[
					'values' => [ [ $site_url ], ],
				]
			),
			[
				'valueInputOption' => $input_option,
				//'insertDataOption' => $site_exists ? null : 'INSERT_ROWS',
			]
		);
		foreach ( $data['planet4_options'] as $lang => $options ) {
			++$site_row;
			$site_options_range = sprintf( '%s!B%d:%d', $sheet_name, $site_row, $site_row );
			$service->spreadsheets_values->update(
				$sheet_id,
				$site_options_range,
				new Sheets\ValueRange(
					[
						'values' => [
							[
								$lang,
								...array_values( $data['planet4_options'][ $lang ] ),
							],
						],
					]
				),
				[ 'valueInputOption' => $input_option ]
			);
		}
	}

	/**
	 * List of planet4 settings (key/value), sorted by language.
	 *
	 * @return array List of settings, by language.
	 */
	private static function list_planet4_settings(): array {
		$site_locale     = get_locale();
		$is_multilingual = function_exists( 'icl_get_languages' );

		$settings  = [];
		$languages = $is_multilingual
			? array_column( icl_get_languages(), 'code' )
			: [ $site_locale ];

		foreach ( $languages as $lang ) {
			do_action( 'wpml_switch_language', $lang );
			$local_options = get_option( Planet4Settings::KEY );
			ksort( $local_options );
			$settings[ $lang ] = $local_options;
		}

		do_action( 'wpml_switch_language', $site_locale );
		ksort( $settings );
		return $settings;
	}

	/**
	 * List of plugins installed and their active state.
	 *
	 * @return array List of plugins installed.
	 */
	private static function list_plugins(): array {
		$plugins = get_plugins();
		$list    = [];
		foreach ( $plugins as $key => &$plugin ) {
			$list[ $key ] = [
				'name'    => $plugin['Name'],
				'version' => $plugin['Version'],
				'active'  => is_plugin_active( $key ),
			];
		}
		ksort( $list );
		return $list;
	}

	/**
	 * List of themes installed.
	 *
	 * @return array List of themes installed.
	 */
	private static function list_themes(): array {
		$themes = wp_get_themes();
		$list   = [];
		foreach ( $themes as $name => $theme ) {
			$list[ $name ] = [
				'name'    => $theme->name,
				'version' => $theme->version,
				'dir'     => $theme->get_stylesheet_directory(),
				'parent'  => $theme->parent_theme,
			];
		}
		ksort( $list );
		return $list;
	}
}
