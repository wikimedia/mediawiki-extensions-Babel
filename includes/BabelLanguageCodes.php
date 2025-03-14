<?php
/**
 * Code for language code and name processing.
 *
 * @file
 * @author Robert Leverington
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\Language\LanguageCode;
use MediaWiki\Languages\LanguageNameUtils;
use MediaWiki\MediaWikiServices;

/**
 * Handle language code and name processing for the Babel extension, it can also
 * be used by other extension which need such functionality.
 */
class BabelLanguageCodes {

	/**
	 * @var array|null
	 */
	private static $mapToMediaWikiCodeCache = null;

	/**
	 * Map BCP 47 codes or old deprecated internal codes to current MediaWiki
	 * internal codes (which may not be standard BCP 47 codes).
	 *
	 * @param string $code Code to try and get an internal code for
	 * @return string|bool Language code, or false if code is not mapped
	 */
	private static function mapToMediaWikiCode( string $code ) {
		if ( !self::$mapToMediaWikiCodeCache ) {
			self::$mapToMediaWikiCodeCache = [];
			// Is the code a proper BCP 47 code for one of MediaWiki's nonstandard codes?
			// If so, return the internal MediaWiki code.
			$mapping = LanguageCode::getNonstandardLanguageCodeMapping();
			foreach ( $mapping as $mwCode => $bcp47code ) {
				// Careful, because the non-standard language code mapping
				// also maps deprecated codes to bcp-47 equivalents; we
				// don't want to return a deprecated code.
				self::$mapToMediaWikiCodeCache[ strtolower( $bcp47code ) ] =
					LanguageCode::replaceDeprecatedCodes( $mwCode );
			}

			// Is the code one of MediaWiki's legacy fake codes? If so, return the modern
			// equivalent code (T101086)
			$mapping = LanguageCode::getDeprecatedCodeMapping();
			foreach ( $mapping as $deprecatedCode => $mwCode ) {
				self::$mapToMediaWikiCodeCache[ strtolower( $deprecatedCode ) ] =
					$mwCode;
			}
		}
		return self::$mapToMediaWikiCodeCache[ strtolower( $code ) ] ?? false;
	}

	/**
	 * Takes a language code, and attempt to obtain a better variant of it,
	 * checks the MediaWiki language codes for a match, otherwise checks the
	 * internal Babel language codes (preferring ISO 639-1 over ISO 639-3) map.
	 *
	 * @param string $code Code to try and get a "better" code for.
	 * @return string|null Mediawiki-internal language code, or null
	 *   for invalid language code.
	 */
	public static function getCode( string $code ): ?string {
		// Map BCP 47 codes and/or deprecated codes to internal MediaWiki codes
		$mediawiki = self::mapToMediaWikiCode( $code );
		if ( $mediawiki !== false ) {
			$code = $mediawiki;
		}

		// Is the code known to MediaWiki?
		$mediawiki = MediaWikiServices::getInstance()->getLanguageNameUtils()->getLanguageName( $code );
		if ( $mediawiki !== '' ) {
			return strtolower( $code );
		}

		// Otherwise, fall back to the ISO 639 codes database
		static $isoCodes = false;
		if ( !$isoCodes ) {
			$isoCodes = require __DIR__ . '/../codes.php';
		}

		return $isoCodes[$code] ?? null;
	}

	/**
	 * Take a code as input, and search a language name for it in
	 * a given language via LanguageNameUtils:getLanguageNames() or
	 * else via the internal Babel language names map.
	 *
	 * @param string $code Code to get name for.
	 * @param string|null $language Code of language to attempt to get name in,
	 *  defaults to language of code.
	 * @return string|bool Name of language, or false for invalid language code.
	 */
	public static function getName( string $code, ?string $language = null ) {
		// Get correct code, even though it should already be correct.
		$code = self::getCode( $code );
		if ( $code === null ) {
			return false;
		}
		$code = strtolower( $code );

		$language ??= $code;
		$names = MediaWikiServices::getInstance()->getLanguageNameUtils()
			->getLanguageNames( $language, LanguageNameUtils::ALL );
		if ( isset( $names[$code] ) ) {
			return $names[$code];
		}

		static $isoNames = false;
		if ( !$isoNames ) {
			$isoNames = require __DIR__ . '/../names.php';
		}

		return $isoNames[$code] ?? false;
	}

	/**
	 * Return an appropriate category name, given a MediaWiki-internal
	 * language code.  MediaWiki-internal codes are all-lowercase, but
	 * historically our category codes have been partially uppercase
	 * in the style of BCP 47. Eventually, we should probably use true
	 * BCP 47 for category names, but historically we've had internal
	 * codes like `simple` which we don't want to rename to `en-simple`
	 * quite yet.
	 *
	 * @param string $code MediaWiki-internal code.
	 * @return string A backwards-compatible category name for this code.
	 * @since 1.32
	 */
	public static function getCategoryCode( string $code ): string {
		if ( strpos( $code, '-' ) !== false ) {
			return LanguageCode::bcp47( $code );
		}

		return $code;
	}
}
