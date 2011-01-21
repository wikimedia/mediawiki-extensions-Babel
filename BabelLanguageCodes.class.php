<?php

/**
 * Handle language code (and name) processing for the Babel extension, can also
 * be used by other extension which need such functionality.
 *
 * @ingroup Extensions
 */
class BabelLanguageCodes {
	/**
	 * Take a code as input, then use various magic to return a "better"
	 * code if available.  The following order is used
	 *     - MediaWiki language codes.
	 *     - ISO 639-1 and 639-3 language codes constant database.
	 *
	 * @param $code String: Code to try and get a "better" code for.
	 * @return String (language code) or false (invalid language code).
	 */
	public static function getCode( $code ) {
		// Try MediaWiki language files.
		global $wgLang;
		$mediawiki = $wgLang->getLanguageName( $code );
		if ( $mediawiki !== '' ) {
			return $code;
		}

		// Try ISO codes constant database.
		global $wgBabelLanguageCodesCdb;
		$codes = CdbReader::open( $wgBabelLanguageCodesCdb );
		return $codes->get( $code );
	}

	/**
	 * Take a code as input, and attempt to find a language name for it in
	 * a language that is as native as possible.  The following order is used:
	 *     - CLDR extension.
	 *     - MediaWiki native.
	 *     - Names constant database.
	 *
	 * @param $code String: Code to get name for.
	 * @param $language String: Code of language to attempt to get name in,
	 *                  defaults to language of code.
	 * @return String (name of language) or false (invalid language code).
	 */
	public static function getName( $code, $language = null ) {
		$cacheType = 'name';
		// Get correct code, even though it should already be correct.
		$code = self::getCode( $code );
		if ( $code === false ) {
			return false;
		}

		if ( $language === null ) {
			$language = $code;
		}

		// Try CLDR extension, then MediaWiki native.
		if ( class_exists( 'LanguageNames' ) ) {
			$names = LanguageNames::getNames( $language, LanguageNames::FALLBACK_NORMAL, LanguageNames::LIST_MW_AND_CLDR );
		} else {
			$names = Language::getLanguageNames();
		}
		if ( array_key_exists( $code, $names ) ) {
			return $names[ $code ];
		}

		//  Use English names, from names constant database.
		global $wgBabelLanguageNamesCdb;
		$names = CdbReader::open( $wgBabelLanguageNamesCdb );
		return $names->get( $code );
	}
}
