<?php
/**
 * @file
 * @author Robert Leverington
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\Config\Config;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\Hook\LinksUpdateHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\User\CentralId\CentralIdLookupFactory;
use MediaWiki\User\Hook\UserGetReservedNamesHook;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\WikiMap\WikiMap;
use Parser;
use WANObjectCache;

/**
 * Hook handler functions for Babel extension.
 */
class Hooks implements
	ParserFirstCallInitHook,
	LinksUpdateHook,
	UserGetReservedNamesHook
{

	private Config $config;
	private UserIdentityLookup $userIdentityLookup;
	private CentralIdLookupFactory $centralIdLookupFactory;
	private WANObjectCache $mainWANObjectCache;

	public function __construct(
		Config $config,
		UserIdentityLookup $userIdentityLookup,
		CentralIdLookupFactory $centralIdLookupFactory,
		WANObjectCache $mainWANObjectCache
	) {
		$this->config = $config;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->centralIdLookupFactory = $centralIdLookupFactory;
		$this->mainWANObjectCache = $mainWANObjectCache;
	}

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

		// And the user has to exist
		$userIdentity = $this->userIdentityLookup->getUserIdentityByName( $title->getText() );
		if ( $userIdentity === null || !$userIdentity->isRegistered() ) {
			return;
		}

		$babelDB = new Database();
		$data = $linksUpdate->getParserOutput()->getExtensionData( 'babel' ) ?: [];
		$changed = $babelDB->setForUser( $userIdentity->getId(), $data );
		if ( $changed ) {
			$localLangKey = $this->mainWANObjectCache->makeKey( 'babel-local-languages', $userIdentity->getId() );
			$this->mainWANObjectCache->touchCheckKey( $localLangKey );
			if ( $this->config->get( 'BabelCentralDb' ) === WikiMap::getCurrentWikiId() ) {
				// If this is the central wiki, invalidate all of the local caches
				$centralId = $this->centralIdLookupFactory->getLookup()->centralIdFromLocalUser( $userIdentity );
				if ( $centralId ) {
					$centralLangKey = $this->mainWANObjectCache->makeGlobalKey( 'babel-central-languages', $centralId );
					$this->mainWANObjectCache->touchCheckKey( $centralLangKey );
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
