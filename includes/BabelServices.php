<?php

namespace MediaWiki\Babel;

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

class BabelServices {

	public function __construct(
		private readonly MediaWikiServices $coreServices,
	) {
	}

	/**
	 * Static version of the constructor, for nicer syntax.
	 * @return static
	 */
	public static function wrap( MediaWikiServices $coreServices ) {
		return new static( $coreServices );
	}

	public function getConfig(): Config {
		return $this->coreServices->getService( 'Babel.Config' );
	}
}
