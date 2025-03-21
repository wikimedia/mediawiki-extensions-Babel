<?php
/**
 * Contains code for language boxes.
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

namespace MediaWiki\Babel\BabelBox;

use MediaWiki\Babel\BabelAutoCreate;
use MediaWiki\Babel\BabelLanguageCodes;
use MediaWiki\Babel\BabelServices;
use MediaWiki\Config\Config;
use MediaWiki\Language\Language;
use MediaWiki\Language\LanguageCode;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\PageReference;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;

/**
 * Class for babel language boxes.
 */
class LanguageBabelBox implements BabelBox {

	private Config $config;
	private PageReference $page;
	private Language $targetLanguage;
	private string $code;
	private string $level;

	/**
	 * Construct a babel box for the given language and level.
	 *
	 * @param Config $config
	 * @param PageReference $page
	 * @param Language $targetLanguage Target language of the parse.
	 * @param string $code Language code to use.
	 *   This is a mediawiki-internal code (not necessarily a valid BCP-47 code)
	 * @param string $level Level of ability to use.
	 */
	public function __construct(
		Config $config,
		PageReference $page,
		Language $targetLanguage,
		string $code,
		string $level
	) {
		$this->config = $config;
		$this->page = $page;
		$this->targetLanguage = $targetLanguage;
		$this->code = BabelLanguageCodes::getCode( $code ) ?? $code;
		$this->level = $level;
	}

	/**
	 * Get a Config instance to use
	 *
	 * @todo Use proper Dependency Injection.
	 * @return Config
	 */
	private static function getConfig(): Config {
		return BabelServices::wrap( MediaWikiServices::getInstance() )->getConfig();
	}

	/**
	 * Return the babel box code.
	 *
	 * @return string A babel box for the given language and level.
	 */
	public function render(): string {
		$code = $this->code;
		$catCode = BabelLanguageCodes::getCategoryCode( $code );
		$bcp47 = LanguageCode::bcp47( $code );

		$portal = wfMessage( 'babel-portal', $catCode )->inContentLanguage()->text();
		if ( $portal !== '' ) {
			$portal = "[[$portal|$catCode]]";
		} else {
			$portal = $catCode;
		}
		$header = "$portal<span class=\"mw-babel-box-level-{$this->level}\">-{$this->level}</span>";

		$name = BabelLanguageCodes::getName( $code );
		$text = self::getText( $this->page, $name, $code, $this->level );

		$dir_current = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $code )->getDir();

		$dir_head = $this->targetLanguage->getDir();

		return <<<EOT
<div class="mw-babel-box mw-babel-box-{$this->level} mw-babel-box-{$catCode}" dir="$dir_head">
{|
! dir="$dir_head" | $header
| dir="$dir_current" lang="$bcp47" | $text
|}
</div>
EOT;
	}

	/**
	 * Get the text to display in the language box for specific language and
	 * level. If MediaWiki:Babel-<level>-n (the message that includes the
	 * language autonym) is translated into the given language, use that
	 * otherwise use MediaWiki:Babel-<level> (the message that takes the
	 * language name as a parameter)
	 *
	 * @param PageReference $page
	 * @param string $name
	 * @param string $code Mediawiki-internal language code of language to use.
	 * @param string $level Level to use.
	 * @return string Text for display, in wikitext format.
	 */
	private static function getText(
		PageReference $page,
		string $name,
		string $code,
		string $level
	): string {
		$categoryLevel = self::getCategoryLink( $page, $level, $code );
		$categoryMain = self::getCategoryLink( $page, null, $code );

		// Give grep a chance to find the usages:
		// babel-0-n, babel-1-n, babel-2-n, babel-3-n, babel-4-n, babel-5-n, babel-N-n
		$text = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', $page->getDBkey()
		)->inLanguage( $code )->text();

		$fallbackLanguage = MediaWikiServices::getInstance()->getLanguageFallback()->getFirst( $code );
		// Because of T75473, the above wfMessage call will ignore any
		// MediaWiki namespace overrides for fallback languages. Hence, we
		// must explicitly ignore them here, or else the comparison will fail,
		// resulting in a message claiming that the user knows the fallback
		// language (probably English), rather than the language
		// they actually specified.
		$fallback = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', $page->getDBkey()
		)->useDatabase( false )->inLanguage( $fallbackLanguage ?? $code )->text();

		// Give grep a chance to find the usages:
		// babel-0, babel-1, babel-2, babel-3, babel-4, babel-5, babel-N
		if ( $text == $fallback ) {
			$text = wfMessage( "babel-$level",
				$categoryLevel, $categoryMain, $name, $page->getDBkey()
			)->inLanguage( $code )->text();
		}

		return $text;
	}

	/**
	 * Add appropriate categories for the language box to the given parser output
	 *
	 * @param ParserOutput $parserOutput Output to add categories to
	 */
	public function addCategories( ParserOutput $parserOutput ): void {
		$namespaces = $this->config->get( 'BabelCategorizeNamespaces' );
		if (
			$namespaces !== null &&
			!Title::newFromPageReference( $this->page )->inNamespaces( $namespaces )
		) {
			return;
		}

		$footerPage = Title::newFromText( wfMessage( 'babel-footer-url' )->inContentLanguage()->text() );
		if ( $footerPage != null && $footerPage->inNamespace( NS_CATEGORY ) ) {
			$footerCategory = $footerPage->getDBkey();
		} else {
			$footerCategory = null;
		}
		# Add main category
		if ( $this->level !== '0' ) {
			$mainCategory = $this->addCategory( $parserOutput, $this->code, null, $this->level, $footerCategory );
		} else {
			$mainCategory = null;
		}

		# Add level category
		$this->addCategory( $parserOutput, $this->code, $this->level, false, $mainCategory );
	}

	/**
	 * Adds one category to the given ParserOutput, and arranges for its creation if it doesn't exist.
	 *
	 * @param ParserOutput $parserOutput Parser output to use
	 * @param string $code Code of language that the category is for.
	 * @param string|null $level Level that the category is for.
	 * @param string|bool $sortkey The sortkey to use for the category, or false to use the default sort
	 * @param string|null $parent An eventual parent category to add to the newly-created category if one is created.
	 * @return string|null The name of the category that was eventually added
	 */
	private function addCategory( ParserOutput $parserOutput,
		string $code, ?string $level, $sortkey, ?string $parent
	): ?string {
		$isOverridden = false;
		$category = self::getCategoryName( $level, $code, $isOverridden );
		if ( $category === null ) {
			return null;
		}
		if ( $sortkey === false ) {
			$sortkey = $parserOutput->getPageProperty( 'defaultsort' );
		}
		$parserOutput->addCategory( $category, $sortkey ?? '' );

		if ( $this->config->get( 'BabelAutoCreate' ) ) {
			// Now arrange for autocreation (in LinksUpdate hook) unless the category was overridden locally
			// (to reduce the risk if a compromised admin edits MediaWiki:Babel-category-override)
			$title = Title::makeTitleSafe( NS_CATEGORY, $category );
			$text = BabelAutoCreate::getCategoryText( $code, $level, $parent );
			if ( !$isOverridden && !$title->exists() ) {
				$parserOutput->appendExtensionData( "babel-tocreate", $category );
				$parserOutput->setExtensionData( "babel-category-text-$category", $text );
			}
		}
		return $category;
	}

	/**
	 * Replace the placeholder variables from the category names configuration
	 * array with actual values.
	 *
	 * @param ?string $level Level of babel category in question, or null for the main category
	 * @param string $code Mediawiki-internal language code of category.
	 * @param bool &$isOverridden Output parameter. Set to true if the category is overridden on-wiki
	 * so that the caller knows not to create categories.
	 * @return string|null Category name with variables replaced and possibly
	 * overridden by the wiki, or null if no category is desired.
	 */
	private static function getCategoryName( ?string $level, string $code, bool &$isOverridden ): ?string {
		global $wgLanguageCode;

		$categoryDef = $level !== null ? self::getConfig()->get( 'BabelCategoryNames' )[$level] :
			self::getConfig()->get( 'BabelMainCategory' );
		if ( $categoryDef === false || $categoryDef === '' ) {
			return null;
		}

		$category = strtr( $categoryDef, [
			'%code%' => BabelLanguageCodes::getCategoryCode( $code ),
			'%wikiname%' => BabelLanguageCodes::getName( $code, $wgLanguageCode ),
			'%nativename%' => BabelLanguageCodes::getName( $code )
		] );

		$oldCategory = $category;

		// Chance to locally override categorization
		if ( self::getConfig()->get( 'BabelAllowOverride' ) ) {
			$category = wfMessage( "babel-category-override",
				$category, $code, $level ?? ''
			)->inContentLanguage()->text();
			if ( $category !== $oldCategory ) {
				$isOverridden = true;
			}
		}

		// Normalize using Title
		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		if ( !$title ) {
			// babel-category-override returned an invalid page name
			return null;
		}

		return $title->getDBkey();
	}

	/**
	 * Returns the right link target for a category (either the category itself or the
	 * title given to get a self-link)
	 * @param PageReference $page The page to point the self-link to
	 * @param ?string $level Level of babel category in question, or null for the main category
	 * @param string $code Mediawiki-internal language code of category.
	 * @return string Link target to use for the given category
	 */
	private static function getCategoryLink( PageReference $page, ?string $level, string $code ): string {
		$isOverridden = false;
		$category = self::getCategoryName( $level, $code, $isOverridden );
		if ( $category !== null ) {
			return ":Category:" . $category;
		}
		return ":" . MediaWikiServices::getInstance()->getTitleFormatter()->getPrefixedText( $page );
	}
}
