<?php

declare( strict_types = 1 );

namespace MediaWiki\Babel\Config;

use MediaWiki\Config\Config;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigRouter;

class ConfigWrapper implements Config {

	private MediaWikiConfigRouter $configRouter;

	public function __construct(
		MediaWikiConfigRouter $configRouter
	) {
		$this->configRouter = $configRouter;
	}

	/**
	 * @inheritDoc
	 */
	public function get( $name ) {
		$value = $this->configRouter->get( $name );
		if ( is_object( $value ) ) {
			// CommunityConfiguration passes objects instead of associative arrays (which Babel
			// expects). This affects eg. BabelCategoryNames. See T369608.
			$value = wfObjectToArray( $value );
		}
		return $value;
	}

	/**
	 * @inheritDoc
	 */
	public function has( $name ): bool {
		return $this->configRouter->has( $name );
	}
}
