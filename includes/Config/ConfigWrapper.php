<?php

declare( strict_types = 1 );

namespace MediaWiki\Babel\Config;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;

class ConfigWrapper implements Config {

	private MediaWikiConfigReader $configReader;
	private Config $globalConfig;

	/**
	 * @var string[] List of config names excluded from Community Configuration due to technical
	 * challenges (see tasks linked below).
	 * @todo Resolve the challenges and make all community configurable
	 * @internal Exposed only for ConfigWrapperTest
	 */
	public const SERVER_SIDE_CONFIGS = [
		// T383905
		'BabelCategorizeNamespaces',
	];

	public function __construct(
		MediaWikiConfigReader $configReader,
		Config $globalConfig
	) {
		$this->configReader = $configReader;
		$this->globalConfig = $globalConfig;
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		if ( in_array( $name, self::SERVER_SIDE_CONFIGS ) ) {
			return $this->globalConfig->get( $name );
		}

		$value = $this->configReader->get( $name );
		if ( is_object( $value ) ) {
			// Convert the BabelCategoryNames key to an array rather than an object. See T369608
			$value = wfObjectToArray( $value );
		}
		return $value;
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ): bool {
		if ( in_array( $name, self::SERVER_SIDE_CONFIGS ) ) {
			return $this->globalConfig->has( $name );
		}

		return $this->configReader->has( $name );
	}
}
