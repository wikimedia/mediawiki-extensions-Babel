<?php

/**
 * Main class for the Babel extension.
 *
 * @ingroup Extensions
 */
class Babel {

	/**
	 * @var Title
	 */
	static $title;

	/**
	 * Render the Babel tower.
	 *
	 * @param $parser Parser.
	 * @return string: Babel tower.
	 */
	public static function Render( $parser ) {
		$parameters = func_get_args();
		array_shift( $parameters );
		self::$title = $parser->getTitle();

		self::mTemplateLinkBatch( $parameters );

		$content = '';
		foreach ( $parameters as $name ) {
			$components = self::mParseParameter( $name );
			$template = wfMsgForContent( 'babel-template', $name );
			if ( $name === '' ) {
				continue;
			} elseif ( self::mPageExists( $template ) ) {
				$content .= $parser->replaceVariables( "{{{$template}}}" );
			} elseif ( $components !== false ) {
				$content .= self::mGenerateBox( $components['code'], $components['level'] );
				$content .= self::mGenerateCategories( $components['code'], $components['level'] );
			} elseif ( self::mValidTitle( $template ) ) {
				// Non-existent page and invalid parameter syntax, red link.
				$content .= "\n[[Template:$template]]";
			} else {
				// Invalid title, output raw.
				$content .= "\nTemplate:$template";
			}
		}

		$footer = wfMsgForContent( 'babel-footer' );
		if ( wfEmptyMsg( $footer ) ) {
			$footer = '';
		} else {
			$footer = 'class="mw-babel-footer" | ' . $footer;
		}

		$cellspacing = wfMsgForContent( 'babel-box-cellspacing' );
		$url = wfMsgForContent( 'babel-url' );
		$top = wfMsgExt( 'babel', array( 'parsemag', 'content' ), self::$title->getDBkey() );
		$tower = <<<EOT
{| cellspacing="$cellspacing" class="mw-babel-wrapper"
! [[$url|$top]]
|-
| $content
|-
$footer
|}
EOT;
		return $tower;
	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param $parameters Array: Templates to perform the link batch on.
	 */
	protected static function mTemplateLinkBatch( $parameters ) {
		$titles = array();
		foreach ( $parameters as $name ) {
			$title = Title::newFromText( wfMsgForContent( 'babel-template', $name ) );
			if ( is_object( $title ) ) {
				$titles[] = $title;
			}
		}

		$batch = new LinkBatch( $titles );
		$batch->execute();
	}

	/**
	 * Identify whether or not a page exists or not.
	 *
	 * @param $name String: Name of the page to check.
	 * @return Boolean: Indication of whether the page exists.
	 */
	protected static function mPageExists( $name ) {
		$titleObj = Title::newFromText( $name );
		return ( is_object( $titleObj ) && $titleObj->exists() );
	}

	/**
	 * Identify whether or not the passed string would make a valid page name.
	 *
	 * @param $name string: Name of page to check.
	 * @return Boolean: Indication of whether or not the title is valid.
	 */
	protected static function mValidTitle( $name ) {
		$titleObj = Title::newFromText( $name );
		return is_object( $titleObj );
	}

	/**
	 * Parse a parameter, getting a language code and level.
	 *
	 * @param $parameter String: Parameter.
	 * @return Array: { 'code' => xx, 'level' => xx }
	 */
	protected static function mParseParameter( $parameter ) {
		global $wgBabelDefaultLevel, $wgBabelCategoryNames;
		$return = array();

		// Try treating the paramter as a language code (for default level).
		$code = BabelLanguageCodes::getCode( $parameter );
		if ( $code !== false ) {
			$return['code'] = $code;
			$return['level'] = $wgBabelDefaultLevel;
			return $return;
		}
		// Try splitting the paramter in to language and level, split on last hyphen.
		$lastSplit = strrpos( $parameter, '-' );
		if ( $lastSplit === false ) {
			return false;
		}
		$code  = substr( $parameter, 0, $lastSplit );
		$level = substr( $parameter, $lastSplit + 1 );

		// Validate code.
		$return['code'] = BabelLanguageCodes::getCode( $code );
		if ( $return['code'] === false ) {
			return false;
		}
		// Validate level.
		$level = strtoupper( $level );
		if( !isset( $wgBabelCategoryNames[$level] ) ) {
			return false;
		}
		$return['level'] = $level;

		return $return;
	}

	/**
	 * Generate a babel box for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @return String: A single babel box, in wikitext format.
	 */
	protected static function mGenerateBox( $code, $level ) {
		$portal = wfMsgForContent( 'babel-portal', $code );
		$header = "[[$portal|" . wfBCP47( $code ) . "]]<span class=\"mw-babel-box-level-$level\">-$level</span>";

		$name = BabelLanguageCodes::getName( $code );
		$code = BabelLanguageCodes::getCode( $code );
		$text = self::mGetText( $name, $code, $level );

		$dir_content = wfMsgForContent( 'babel-directionality' );
		$dir_current = wfMsgExt( 'babel-directionality', array( 'language' => $code ) );
		$cellspacing = wfMsgForContent( 'babel-cellspacing' );

		$box = <<<EOT
<div class="mw-babel-box mw-babel-box-$level" dir="$dir_content">
{| cellspacing="$cellspacing"
!  dir="$dir_content" | $header
|  dir="$dir_current" | $text
|}
</div>
EOT;
		return $box;
	}

	/**
	 * Get the text to display in the language box for specific language and
	 * level.
	 *
	 * @param $language String: Language code of language to use.
	 * @param $level String: Level to use.
	 * @return String: Text for display, in wikitext format.
	 */
	protected static function mGetText( $name, $language, $level ) {
		global $wgBabelMainCategory, $wgBabelCategoryNames;

		if ( $wgBabelCategoryNames[$level] === false ) {
			$categoryLevel = self::$title->getFullText();
		} else {
			$categoryLevel = ':Category:' . self::mReplaceCategoryVariables( $wgBabelCategoryNames[$level], $language );
		}

		if ( $wgBabelMainCategory === false ) {
			$categoryMain = self::$title->getFullText();
		} else {
			$categoryMain = ':Category:' . self::mReplaceCategoryVariables( $wgBabelMainCategory, $language );
		}

		$text = wfMsgExt( "babel-$level-n",
			array( 'language' => $language, 'parsemag' ),
			$categoryMain, $categoryMain, '', self::$title->getDBkey()
		);

		$fallback = wfMsgExt( "babel-$level-n",
			array( 'language' => Language::getFallbackfor( $language ), 'parsemag' ),
			$categoryMain, $categoryMain, '', self::$title->getDBkey()
		);

		if ( $text == $fallback ) {
			$text = wfMsgExt( "babel-$level",
				array( 'language' => $language, 'parsemag' ),
				$categoryMain, $categoryMain, $name, self::$title->getDBkey()
			);
		}

		return $text;
	}

	/**
	 * Generate categories for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @return String: Wikitext to add categories.
	 */
	protected static function mGenerateCategories( $code, $level ) {
		global $wgBabelMainCategory, $wgBabelCategoryNames, $wgLanguageCode;

		$r = '';

		if ( $wgBabelMainCategory !== false && $wgBabelCategoryNames[$level] !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelMainCategory, $code );
			$r .= "[[Category:$category|$level]]";
			BabelAutoCreate::create( $category, BabelLanguageCodes::getName( $code, $wgLanguageCode ) );
		}

		if ( $wgBabelCategoryNames[$level] !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelCategoryNames[$level], $code );
			$r .= "[[Category:$category]]";
			BabelAutoCreate::create( $category, BabelLanguageCodes::getName( $code, $wgLanguageCode ), $level );
		}

		return $r;
	}

	/**
	 * Replace the placeholder variables from the category names configurtion
	 * array with actual values.
	 *
	 * @param $category String: Category name (containing variables).
	 * @param $code String: Language code of category.
	 * @return String: Category name with variables replaced.
	 */
	protected static function mReplaceCategoryVariables( $category, $code ) {
		global $wgLanguageCode;
		$vars = array(
			'%code%' => $code,
			'%wikiname%' => BabelLanguageCodes::getName( $code, $wgLanguageCode ),
			'%nativename%' => BabelLanguageCodes::getName( $code )
		);
		foreach ( $vars as $find => $replace ) {
			$category = str_replace( $find, $replace, $category );
		}
		return $category;
	}
}
