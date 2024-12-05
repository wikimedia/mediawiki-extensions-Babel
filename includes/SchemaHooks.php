<?php
/**
 * Schema hooks for extension.
 *
 * @file
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class SchemaHooks implements LoadExtensionSchemaUpdatesHook {
	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		$dir = dirname( __DIR__ ) . '/sql/';
		$dbType = $updater->getDB()->getType();

		if ( $dbType === 'mysql' ) {
			$updater->addExtensionTable( 'babel',
				$dir . 'tables-generated.sql'
			);
			$updater->modifyExtensionField(
				'babel',
				'babel_lang',
				$dir . 'babel-babel_lang-length-type.sql'
			);
			$updater->modifyExtensionField(
				'babel',
				'babel_level',
				$dir . 'babel-babel_level-type.sql'
			);
		} elseif ( $dbType === 'sqlite' ) {
			$updater->addExtensionTable( 'babel',
				$dir . 'sqlite/tables-generated.sql'
			);

			$updater->modifyExtensionField(
				'babel',
				'babel_lang',
				$dir . 'sqlite/babel-babel_lang-length.sql'
			);
		} elseif ( $dbType === 'postgres' ) {
			$updater->addExtensionTable( 'babel',
				$dir . 'postgres/tables-generated.sql'
			);
		}
	}
}
