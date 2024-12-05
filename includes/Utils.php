<?php

namespace MediaWiki\Babel;

use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;

class Utils {

	public static function useCommunityConfiguration(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'CommunityConfiguration' ) &&
			MediaWikiServices::getInstance()->getMainConfig()->get( 'BabelUseCommunityConfiguration' );
	}
}
