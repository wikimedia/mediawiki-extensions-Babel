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
	 * @param bool $createCategories If true, creates non existing categories;
	 *  otherwise, doesn't create them.
	 */
	public function __construct(
		Title $title,
		string $code,
		string $level,
		bool $createCategories = true
	) {
		$this->title = $title;
		$this->code = BabelLanguageCodes::getCode( $code ) ?? $code;
		$this->level = $level;
		$this->createCategories = $createCategories;
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
	 * Generate categories for the language box.
	 *
	 * @return string[] [ category => sort key ]
	 */
	public function getCategories(): array {
		global $wgBabelCategorizeNamespaces;

		$r = [];

		if (
			$wgBabelCategorizeNamespaces !== null &&
			!$this->title->inNamespaces( $wgBabelCategorizeNamespaces )
		) {
			return $r;
		}

		# Add main category
		if ( $this->level !== '0' ) {
			$category = self::getCategoryName( null, $this->code, $this->createCategories );
			if ( $category !== null ) {
				$r[$category] = $this->level;
			}
		}

		# Add level category
		$category = self::getCategoryName( $this->level, $this->code, $this->createCategories );
		if ( $category !== null ) {
			// Use default sort key
			$r[$category] = false;
		}

		return $r;
	}

	/**
	 * Replace the placeholder variables from the category names configuration
	 * array with actual values.
	 *
	 * @param ?string $level Level of babel category in question, or null for the main category
	 * @param string $code Mediawiki-internal language code of category.
	 * @param bool $createCategories Whether to create any referenced categories that don't yet exist
	 * @return string|null Category name with variables replaced and possibly
	 * overriden by the wiki, or null if no category is desired.
	 */
	private static function getCategoryName( ?string $level, string $code, bool $createCategories = false ): ?string {
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
		}
		// Now autocreate the category unless it was overridden locally
		// (to reduce the risk if a compromised admin edits MediaWiki:Babel-category-override)
		if ( $category === $oldCategory && $createCategories ) {
			BabelAutoCreate::create( $category, $code, $level );
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
		$category = self::getCategoryName( $level, $code );
		if ( $category !== null ) {
			return ":Category:" . $category;
		}
		return ":" . $title->getFullText();
	}
}
