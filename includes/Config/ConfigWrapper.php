<?php

declare( strict_types = 1 );

namespace MediaWiki\Babel\Config;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;

class ConfigWrapper implements Config {

	private MediaWikiConfigReader $configReader;

	public function __construct( MediaWikiConfigReader $configReader ) {
		$this->configReader = $configReader;
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
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
		return $this->configReader->has( $name );
	}
}
