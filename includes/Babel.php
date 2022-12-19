<?php
/**
 * Contains main code.
 *
 * @file
 * @author Robert Leverington
 * @author Robin Pepermans
 * @author Niklas Laxström
 * @author Brian Wolff
 * @author Purodha Blissenbach
 * @author Sam Reed
 * @author Siebrand Mazeland
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\Babel\BabelBox\LanguageBabelBox;
use MediaWiki\Babel\BabelBox\NotBabelBox;
use MediaWiki\Babel\BabelBox\NullBabelBox;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Parser;
use ParserOutput;
use Title;
use WikiMap;

/**
 * Main class for the Babel extension.
 */
class Babel {
	/**
	 * @var Title
	 */
	protected static $title;

	/**
	 * Render the Babel tower.
	 *
	 * @param Parser $parser
	 * @param string ...$parameters
	 * @return string Babel tower.
	 */
	public static function Render( Parser $parser, string ...$parameters ): string {
		global $wgBabelUseUserLanguage;
		self::$title = $parser->getTitle();

		self::mTemplateLinkBatch( $parameters );

		$parser->getOutput()->addModuleStyles( [ 'ext.babel' ] );

		$content = self::mGenerateContentTower( $parser, $parameters );

		if ( preg_match( '/^plain\s*=\s*\S/', reset( $parameters ) ) ) {
			return $content;
		}

		if ( $wgBabelUseUserLanguage ) {
			$uiLang = $parser->getOptions()->getUserLangObj();
		} else {
			$uiLang = self::$title->getPageLanguage();
		}

		$top = wfMessage( 'babel', self::$title->getDBkey() )->inLanguage( $uiLang );

		if ( $top->isDisabled() ) {
			$top = '';
		} else {
			$top = $top->text();
			$url = wfMessage( 'babel-url' )->inContentLanguage();
			if ( !$url->isDisabled() ) {
				$top = '[[' . $url->text() . '|' . $top . ']]';
			}
			$top = '! class="mw-babel-header" | ' . $top;
		}
		$footer = wfMessage( 'babel-footer', self::$title->getDBkey() )->inLanguage( $uiLang );

		$url = wfMessage( 'babel-footer-url' )->inContentLanguage();
		$showfooter = '';
		if ( !$footer->isDisabled() && !$url->isDisabled() ) {
			$showfooter = '! class="mw-babel-footer" | [[' .
				$url->text() . '|' . $footer->text() . ']]';
		}

		$tower = <<<EOT
{|class="mw-babel-wrapper"
$top
|-
| $content
|-
$showfooter
|}
EOT;

		return $tower;
	}

	/**
	 * @param Parser $parser
	 * @param string[] $parameters
	 *
	 * @return string Wikitext
	 */
	private static function mGenerateContentTower( Parser $parser, array $parameters ): string {
		$content = '';
		$templateParameters = []; // collects name=value parameters to be passed to wiki templates.

		foreach ( $parameters as $name ) {
			if ( strpos( $name, '=' ) !== false ) {
				$templateParameters[] = $name;
				continue;
			}

			$content .= self::mGenerateContent( $parser, $name, $templateParameters );
		}

		return $content;
	}

	private static function setExtensionData(
		ParserOutput $parserOutput,
		string $code,
		string $level
	): void {
		$data = $parserOutput->getExtensionData( 'babel' ) ?: [];
		$data[ BabelLanguageCodes::getCategoryCode( $code ) ] = $level;
		$parserOutput->setExtensionData( 'babel', $data );
	}

	/**
	 * @param Parser $parser
	 * @param string $name
	 * @param string[] $templateParameters
	 *
	 * @return string Wikitext
	 */
	private static function mGenerateContent(
		Parser $parser,
		string $name,
		array $templateParameters
	): string {
		$createCategories = !$parser->getOptions()->getIsPreview();
		$components = self::mParseParameter( $name );
		$template = wfMessage( 'babel-template', $name )->inContentLanguage()->text();
		$parserOutput = $parser->getOutput();

		if ( $name === '' ) {
			$box = new NullBabelBox();
		} elseif ( $components !== false ) {
			// Valid parameter syntax (with lowercase language code), babel box
			$box = new LanguageBabelBox(
				self::$title,
				$components['code'],
				$components['level'],
				$createCategories
			);
			self::setExtensionData( $parserOutput, $components['code'], $components['level'] );
		} elseif ( self::mPageExists( $template ) ) {
			// Check for an existing template
			$templateParameters[0] = $template;
			$template = implode( '|', $templateParameters );
			$box = new NotBabelBox(
				self::$title->getPageLanguage()->getDir(),
				$parser->replaceVariables( "{{{$template}}}" )
			);
		} elseif ( self::mValidTitle( $template ) ) {
			// Non-existing page, so try again as a babel box,
			// with converting the code to lowercase
			$components2 = self::mParseParameter( $name, /* code to lowercase */
				true );
			if ( $components2 !== false ) {
				$box = new LanguageBabelBox(
					self::$title,
					$components2['code'],
					$components2['level'],
					$createCategories
				);
				self::setExtensionData( $parserOutput,
					$components2['code'], $components2['level'] );
			} else {
				// Non-existent page and invalid parameter syntax, red link.
				$box = new NotBabelBox(
					self::$title->getPageLanguage()->getDir(),
					'[[' . $template . ']]'
				);
			}
		} else {
			// Invalid title, output raw.
			$box = new NotBabelBox(
				self::$title->getPageLanguage()->getDir(),
				$template
			);
		}

		foreach ( $box->getCategories() as $cat => $sortKey ) {
			if ( $sortKey === false ) {
				if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
					$sortKey = $parserOutput->getPageProperty( 'defaultsort' );
				} else {
					// MW < 1.38
					$sortKey = $parser->getDefaultSort();
				}
			}

			$parserOutput->addCategory( $cat, $sortKey ?? '' );
		}

		return $box->render();
	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param string[] $parameters Templates to perform the link batch on.
	 */
	protected static function mTemplateLinkBatch( array $parameters ): void {
		$titles = [];
		foreach ( $parameters as $name ) {
			$title = Title::newFromText( wfMessage( 'babel-template', $name )->inContentLanguage()->text() );
			if ( is_object( $title ) ) {
				$titles[] = $title;
			}
		}

		$batch = MediaWikiServices::getInstance()->getLinkBatchFactory()->newLinkBatch( $titles );
		$batch->setCaller( __METHOD__ );
		$batch->execute();
	}

	/**
	 * Identify whether or not a page exists.
	 *
	 * @param string $name Name of the page to check.
	 * @return bool Indication of whether the page exists.
	 */
	protected static function mPageExists( string $name ): bool {
		$titleObj = Title::newFromText( $name );

		return ( is_object( $titleObj ) && $titleObj->exists() );
	}

	/**
	 * Identify whether or not the passed string would make a valid page name.
	 *
	 * @param string $name Name of page to check.
	 * @return bool Indication of whether or not the title is valid.
	 */
	protected static function mValidTitle( string $name ): bool {
		$titleObj = Title::newFromText( $name );

		return is_object( $titleObj );
	}

	/**
	 * Parse a parameter, getting a language code and level.
	 *
	 * @param string $parameter Parameter.
	 * @param bool $strtolower Whether to convert the language code to lowercase
	 * @return array|bool [ 'code' => xx, 'level' => xx ] false on failure
	 */
	protected static function mParseParameter( string $parameter, bool $strtolower = false ) {
		global $wgBabelDefaultLevel, $wgBabelCategoryNames;
		$return = [];

		$babelcode = $strtolower ? strtolower( $parameter ) : $parameter;
		// Try treating the parameter as a language code (for default level).
		$code = BabelLanguageCodes::getCode( $babelcode );
		if ( $code !== false ) {
			$return['code'] = $code;
			$return['level'] = $wgBabelDefaultLevel;
			return $return;
		}
		// Try splitting the parameter in to language and level, split on last hyphen.
		$lastSplit = strrpos( $parameter, '-' );
		if ( $lastSplit === false ) {
			return false;
		}
		$code = substr( $parameter, 0, $lastSplit );
		$level = substr( $parameter, $lastSplit + 1 );

		$babelcode = $strtolower ? strtolower( $code ) : $code;
		// Validate code.
		$return['code'] = BabelLanguageCodes::getCode( $babelcode );
		if ( $return['code'] === false ) {
			return false;
		}
		// Validate level.
		$level = strtoupper( $level );
		if ( !isset( $wgBabelCategoryNames[$level] ) ) {
			return false;
		}
		$return['level'] = $level;

		return $return;
	}

	/**
	 * Gets the language information a user has set up with Babel.
	 * This function gets the actual info directly from categories
	 * or database. For performance, it is recommended to use
	 * getCachedUserLanguageInfo instead.
	 *
	 * @param UserIdentity $user
	 * @return string[] [ language code => level ]
	 */
	public static function getUserLanguageInfo( UserIdentity $user ): array {
		$userLanguageInfo = self::getUserLanguagesDB( $user );

		ksort( $userLanguageInfo );

		return $userLanguageInfo;
	}

	/**
	 * Gets the language information a user has set up with Babel,
	 * from the cache. It's recommended to use this when this will
	 * be called frequently.
	 *
	 * @param UserIdentity $user
	 * @return string[] [ language code => level ]
	 *
	 * @since Version 1.10.0
	 */
	public static function getCachedUserLanguageInfo( UserIdentity $user ): array {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$userId = $user->getId();
		$key = $cache->makeKey( 'babel-local-languages', $userId );
		$checkKeys = [ $key ];
		$centralId = MediaWikiServices::getInstance()->getCentralIdLookupFactory()
				->getLookup()->centralIdFromLocalUser( $user );

		if ( $centralId ) {
			$checkKeys[] = $cache->makeGlobalKey( 'babel-central-languages', $centralId );
		}

		$cachedUserLanguageInfo = $cache->getWithSetCallback(
			$key,
			$cache::TTL_MINUTE * 30,
			function ( $oldValue, &$ttl, array &$setOpts ) use ( $userId, $user ) {
				wfDebug( "Babel: cache miss for user $userId\n" );

				return self::getUserLanguageInfo( $user );
			},
			[
				'checkKeys' => $checkKeys,
			]
		);

		return $cachedUserLanguageInfo;
	}

	/**
	 * Gets only the languages codes list out of the user language info.
	 *
	 * @param string[] $languageInfo [ language code => level ], the return value of
	 *   getUserLanguageInfo.
	 * @param string|null $level Minimal level as given in $wgBabelCategoryNames
	 * @return string[] List of language codes
	 *
	 * @since Version 1.10.0
	 */
	private static function getLanguages( array $languageInfo, ?string $level ): array {
		if ( !$languageInfo ) {
			return [];
		}

		if ( $level !== null ) {
			// filter down the set, note that this uses a text sort!
			$languageInfo = array_filter(
				$languageInfo,
				static function ( $value ) use ( $level ) {
					return ( strcmp( $value, $level ) >= 0 );
				}
			);
			// sort and retain keys
			uasort(
				$languageInfo,
				static function ( $a, $b ) {
					return -strcmp( $a, $b );
				}
			);
		}

		return array_keys( $languageInfo );
	}

	/**
	 * Gets the cached list of languages a user has set up with Babel.
	 *
	 * @param UserIdentity $user
	 * @param string|null $level Minimal level as given in $wgBabelCategoryNames
	 * @return string[] List of language codes
	 *
	 * @since Version 1.10.0
	 */
	public static function getCachedUserLanguages(
		UserIdentity $user,
		string $level = null
	): array {
		return self::getLanguages( self::getCachedUserLanguageInfo( $user ), $level );
	}

	/**
	 * Gets the list of languages a user has set up with Babel.
	 * For performance it is recommended to use getCachedUserLanguages.
	 *
	 * @param UserIdentity $user
	 * @param string|null $level Minimal level as given in $wgBabelCategoryNames
	 * @return string[] List of language codes
	 *
	 * @since Version 1.9.0
	 */
	public static function getUserLanguages( UserIdentity $user, string $level = null ): array {
		return self::getLanguages( self::getUserLanguageInfo( $user ), $level );
	}

	private static function getUserLanguagesDB( UserIdentity $user ): array {
		global $wgBabelCentralDb;

		$babelDB = new Database();
		$result = $babelDB->getForUser( $user->getId() );
		/** If local data or no central source, return */
		if ( $result || !$wgBabelCentralDb ) {
			return $result;
		}

		if ( $wgBabelCentralDb === WikiMap::getCurrentWikiId() ) {
			// We are the central wiki, so no fallback we can do
			return [];
		}

		$lookup = MediaWikiServices::getInstance()->getCentralIdLookupFactory()->getLookup();
		if ( !$lookup->isAttached( $user )
			|| !$lookup->isAttached( $user, $wgBabelCentralDb )
		) {
			return [];
		}

		return $babelDB->getForRemoteUser( $wgBabelCentralDb, $user->getName() );
	}
}

class_alias( Babel::class, 'Babel' );
