<?php

namespace MediaWiki\Babel\Config;

use MediaWiki\Babel\Utils;
use MediaWiki\Extension\CommunityConfiguration\Hooks\CommunityConfigurationProvider_initListHook;

/**
 * Hooks for CommunityConfiguration
 *
 * @note Needs to be separate from Hooks to keep CommunityConfiguration a soft dependency.
 */
class CommunityConfigurationHooks implements CommunityConfigurationProvider_initListHook {

	/**
	 * @inheritDoc
	 */
	public function onCommunityConfigurationProvider_initList( array &$providers ) {
		if ( !Utils::useCommunityConfiguration() ) {
			// Do not show the Babel provider in the dashboard when CommunityConfiguration is not in
			// use (to avoid user confusion).
			unset( $providers['Babel'] );
		}
	}
}
