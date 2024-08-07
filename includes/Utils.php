<?php

namespace MediaWiki\Babel;

use ExtensionRegistry;
use MediaWiki\MediaWikiServices;

class Utils {

	public static function useCommunityConfiguration(): bool {
		return ExtensionRegistry::getInstance()->isLoaded( 'CommunityConfiguration' ) &&
			MediaWikiServices::getInstance()->getMainConfig()->get( 'BabelUseCommunityConfiguration' );
	}
}
