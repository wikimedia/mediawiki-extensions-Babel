<?php

namespace MediaWiki\Babel;

use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;

class BabelServices {

	private MediaWikiServices $coreServices;

	/**
	 * @param MediaWikiServices $coreServices
	 */
	public function __construct( MediaWikiServices $coreServices ) {
		$this->coreServices = $coreServices;
	}

	/**
	 * Static version of the constructor, for nicer syntax.
	 * @param MediaWikiServices $coreServices
	 * @return static
	 */
	public static function wrap( MediaWikiServices $coreServices ) {
		return new static( $coreServices );
	}

	public function getConfig(): Config {
		return $this->coreServices->getService( 'Babel.Config' );
	}
}
