<?php
/**
 * Static functions for extension.
 *
 * @file
 * @author Robert Leverington
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use CentralIdLookup;
use DatabaseUpdater;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\MediaWikiServices;
use Parser;
use User;
use WikiMap;

/**
 * Static functions for Babel extension.
 */
class BabelStatic {
	/**
	 * Registers the parser function hook.
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ): void {
		$parser->setFunctionHook( 'babel', [ Babel::class, 'Render' ] );
	}

	/**
	 * @param DatabaseUpdater $updater
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
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

	/**
	 * Do not add typehint for $linksUpdate until MLEB supports MW < 1.38. See: T306863
	 * @param LinksUpdate $linksUpdate
	 */
	public static function onLinksUpdate( $linksUpdate ): void {
		global $wgBabelCentralDb;

		$title = $linksUpdate->getTitle();
		// Has to be a root userpage
		if ( !$title->inNamespace( NS_USER ) || !$title->getRootTitle()->equals( $title ) ) {
			return;
		}

		// And the user has to exist
		$user = User::newFromName( $title->getText() );
		if ( !$user || !$user->getId() ) {
			return;
		}

		$babelDB = new Database();
		$data = $linksUpdate->getParserOutput()->getExtensionData( 'babel' ) ?: [];
		$changed = $babelDB->setForUser( $user->getId(), $data );
		if ( $changed ) {
			$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
			$cache->touchCheckKey( $cache->makeKey( 'babel-local-languages', $user->getId() ) );
			if ( $wgBabelCentralDb === WikiMap::getCurrentWikiId() ) {
				// If this is the central wiki, invalidate all of the local caches
				if ( method_exists( MediaWikiServices::class, 'getCentralIdLookupFactory' ) ) {
					// MW1.37+
					$centralId = MediaWikiServices::getInstance()->getCentralIdLookupFactory()
						->getLookup()->centralIdFromLocalUser( $user );
				} else {
					$centralId = CentralIdLookup::factory()->centralIdFromLocalUser( $user );
				}
				if ( $centralId ) {
					$cache->touchCheckKey( $cache->makeGlobalKey( 'babel-central-languages', $centralId ) );
				}
			}
		}
	}
}
