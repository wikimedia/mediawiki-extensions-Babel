<?php

use MediaWiki\Babel\Config\ConfigWrapper;
use MediaWiki\Babel\Utils;
use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\MediaWikiServices;

return [
	'Babel.Config' => static function ( MediaWikiServices $services ): Config {
		if ( Utils::useCommunityConfiguration() ) {
			return new ConfigWrapper(
				CommunityConfigurationServices::wrap( $services )->getMediaWikiConfigReader(),
				$services->getMainConfig()
			);
		} else {
			return MediaWikiServices::getInstance()->getMainConfig();
		}
	},
];
