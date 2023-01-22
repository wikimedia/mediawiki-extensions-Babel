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

use LanguageCode;
use MediaWiki\Babel\BabelAutoCreate;
use MediaWiki\Babel\BabelLanguageCodes;
use MediaWiki\MediaWikiServices;
use ParserOutput;
use Title;

/**
 * Class for babel language boxes.
 */
class LanguageBabelBox implements BabelBox {

	/**
	 * @var Title
	 */
	private $title;

	/**
	 * @var string
	 */
	private $code;

	/**
	 * @var string
	 */
	private $level;

	/**
	 * @var bool
	 */
	private $createCategories;

	/**
	 * Construct a babel box for the given language and level.
	 *
	 * @param Title $title
	 * @param string $code Language code to use.
	 *   This is a mediawiki-internal code (not necessarily a valid BCP-47 code)
	 * @param string $level Level of ability to use.
	 */
	public function __construct(
		Title $title,
		string $code,
		string $level
	) {
		$this->title = $title;
		$this->code = BabelLanguageCodes::getCode( $code ) ?? $code;
		$this->level = $level;
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
		$text = self::getText( $this->title, $name, $code, $this->level );

		$dir_current = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( $code )->getDir();

		$dir_head = $this->title->getPageLanguage()->getDir();

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
	 * level.
	 *
	 * @param Title $title
	 * @param string $name
	 * @param string $code Mediawiki-internal language code of language to use.
	 * @param string $level Level to use.
	 * @return string Text for display, in wikitext format.
	 */
	private static function getText(
		Title $title,
		string $name,
		string $code,
		string $level
	): string {
		$categoryLevel = self::getCategoryLink( $title, $level, $code );
		$categoryMain = self::getCategoryLink( $title, null, $code );

		// Give grep a chance to find the usages:
		// babel-0-n, babel-1-n, babel-2-n, babel-3-n, babel-4-n, babel-5-n, babel-N-n
		$text = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', $title->getDBkey()
		)->inLanguage( $code )->text();

		$fallbackLanguage = MediaWikiServices::getInstance()->getLanguageFallback()->getFirst( $code );
		$fallback = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', $title->getDBkey()
		)->inLanguage( $fallbackLanguage ?? $code )->text();

		// Give grep a chance to find the usages:
		// babel-0, babel-1, babel-2, babel-3, babel-4, babel-5, babel-N
		if ( $text == $fallback ) {
			$text = wfMessage( "babel-$level",
				$categoryLevel, $categoryMain, $name, $title->getDBkey()
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
		global $wgBabelCategorizeNamespaces;

		if (
			$wgBabelCategorizeNamespaces !== null &&
			!$this->title->inNamespaces( $wgBabelCategorizeNamespaces )
		) {
			return;
		}

		# Add main category
		if ( $this->level !== '0' ) {
			self::addCategory( $parserOutput, $this->code, null, $this->level );
		}

		# Add level category
		self::addCategory( $parserOutput, $this->code, $this->level, false );
	}

	/**
	 * Adds one category to the given ParserOutput, and arranges for its creation if it doesn't exist.
	 *
	 * @param ParserOutput $parserOutput Parser output to use
	 * @param string $code Code of language that the category is for.
	 * @param string|null $level Level that the category is for.
	 * @param string|bool $sortkey The sortkey to use for the category, or false to use the default sort
	 */
	private function addCategory( ParserOutput $parserOutput,
		string $code, ?string $level, $sortkey
	) {
		$isOverridden = false;
		$category = self::getCategoryName( $level, $code, $isOverridden );
		if ( $category === null ) {
			return;
		}
		if ( $sortkey === false ) {
			$sortkey = $parserOutput->getPageProperty( 'defaultsort' );
		}
		$parserOutput->addCategory( $category, $sortkey ?? '' );

		// Now arrange for autocreation (in LinksUpdate hook) unless the category was overridden locally
		// (to reduce the risk if a compromised admin edits MediaWiki:Babel-category-override)
		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		$text = BabelAutoCreate::getCategoryText( $code, $level );
		if ( !$isOverridden && !$title->exists() ) {
			$parserOutput->appendExtensionData( "babel-tocreate", $category );
			$parserOutput->setExtensionData( "babel-category-text-$category", $text );
		}
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
		global $wgLanguageCode, $wgBabelAllowOverride, $wgBabelMainCategory, $wgBabelCategoryNames;

		$categoryDef = $level !== null ? $wgBabelCategoryNames[$level] : $wgBabelMainCategory;
		if ( $categoryDef === false ) {
			return null;
		}

		$category = strtr( $categoryDef, [
			'%code%' => BabelLanguageCodes::getCategoryCode( $code ),
			'%wikiname%' => BabelLanguageCodes::getName( $code, $wgLanguageCode ),
			'%nativename%' => BabelLanguageCodes::getName( $code )
		] );

		$oldCategory = $category;

		// Chance to locally override categorization
		if ( $wgBabelAllowOverride ) {
			$category = wfMessage( "babel-category-override",
				$category, $code, $level
			)->inContentLanguage()->text();
			if ( $category !== $oldCategory ) {
				$isOverridden = true;
			}
		}

		// Normalize using Title
		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		if ( !$title ) {
			// babel-category-override return an invalid page name
			return null;
		}

		return $title->getDBkey();
	}

	/**
	 * Returns the right link target for a category (either the category itself or the
	 * title given to get a self-link)
	 * @param Title $title
	 * @param ?string $level Level of babel category in question, or null for the main category
	 * @param string $code Mediawiki-internal language code of category.
	 * @return string Link target to use for the given category
	 */
	private static function getCategoryLink( Title $title, ?string $level, string $code ): string {
		$isOverridden = false;
		$category = self::getCategoryName( $level, $code, $isOverridden );
		if ( $category !== null ) {
			return ":Category:" . $category;
		}
		return ":" . $title->getFullText();
	}
}
