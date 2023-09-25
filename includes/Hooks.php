<?php
/**
 * @file
 * @author Robert Leverington
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\LinksUpdateHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\Hook\UserGetReservedNamesHook;
use Parser;
use WikiMap;

/**
 * Hook handler functions for Babel extension.
 */
class Hooks implements
	ParserFirstCallInitHook,
	LinksUpdateHook,
	UserGetReservedNamesHook
{
	/**
	 * Registers the parser function hook.
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setFunctionHook( 'babel', [ Babel::class, 'Render' ] );
	}

	/**
	 * @param LinksUpdate $linksUpdate
	 */
	public function onLinksUpdate( $linksUpdate ): void {
		global $wgBabelCentralDb;

		$title = $linksUpdate->getTitle();
		$toCreate = $linksUpdate->getParserOutput()->getExtensionData( 'babel-tocreate' ) ?: [];

		// Create categories
		foreach ( $toCreate as $category => $value ) {
			$text = $linksUpdate->getParserOutput()->getExtensionData( "babel-category-text-{$category}" );
			BabelAutoCreate::create( $category, $text );
		}

		// Has to be a root userpage
		if ( !$title->inNamespace( NS_USER ) || !$title->getRootTitle()->equals( $title ) ) {
			return;
		}

		$mwInstance = MediaWikiServices::getInstance();
		$userIdentityLookup = $mwInstance->getUserIdentityLookup();
		// And the user has to exist
		$userIdentity = $userIdentityLookup->getUserIdentityByName( $title->getText() );
		if ( $userIdentity === null || !$userIdentity->isRegistered() ) {
			return;
		}

		$babelDB = new Database();
		$data = $linksUpdate->getParserOutput()->getExtensionData( 'babel' ) ?: [];
		$changed = $babelDB->setForUser( $userIdentity->getId(), $data );
		if ( $changed ) {
			$cache = $mwInstance->getMainWANObjectCache();
			$cache->touchCheckKey( $cache->makeKey( 'babel-local-languages', $userIdentity->getId() ) );
			if ( $wgBabelCentralDb === WikiMap::getCurrentWikiId() ) {
				// If this is the central wiki, invalidate all of the local caches
				$centralId = $mwInstance->getCentralIdLookupFactory()
					->getLookup()->centralIdFromLocalUser( $userIdentity );
				if ( $centralId ) {
					$cache->touchCheckKey( $cache->makeGlobalKey( 'babel-central-languages', $centralId ) );
				}
			}
		}
	}

	/**
	 * @param array &$names
	 */
	public function onUserGetReservedNames( &$names ): void {
		$names[] = 'msg:' . BabelAutoCreate::MSG_USERNAME;
	}
}
