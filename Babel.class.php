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
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

/**
 * Main class for the Babel extension.
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
		wfProfileIn( __METHOD__ );
		global $wgBabelUseUserLanguage;
		$parameters = func_get_args();
		array_shift( $parameters );
		self::$title = $parser->getTitle();

		self::mTemplateLinkBatch( $parameters );

		$parser->getOutput()->addModuleStyles( 'ext.babel' );

		$content = '';
		$templateParameters = array(); // collects name=value parameters to be passed to wiki templates.
		$createCategories = !$parser->mOptions->mIsPreview;
		foreach ( $parameters as $name ) {
			if ( strpos( $name, '=' ) !== false ) {
				$templateParameters[] = $name;
				continue;
			}
			$components = self::mParseParameter( $name );
			$template = wfMessage( 'babel-template', $name )->inContentLanguage()->text();
			if ( $name === '' ) {
				continue;
			} elseif ( $components !== false ) {
				// Valid parameter syntax (with lowercase language code), babel box
				$content .= self::mGenerateBox( $components['code'], $components['level'] );
				$content .= self::mGenerateCategories( $components['code'], $components['level'], $createCategories );
			} elseif ( self::mPageExists( $template ) ) {
				// Check for an existing template
				$templateParameters[0] = $template;
				$template = implode( '|', $templateParameters );
				$content .= self::mGenerateNotaBox( $parser->replaceVariables( "{{{$template}}}" ) );
			} elseif ( self::mValidTitle( $template ) ) {
				// Non-existing page, so try again as a babel box, with converting the code to lowercase
				$components2 = self::mParseParameter( $name, /* code to lowercase */ true );
				if ( $components2 !== false ) {
					$content .= self::mGenerateBox( $components2['code'], $components2['level'] );
					$content .= self::mGenerateCategories( $components2['code'], $components2['level'], $createCategories );
				} else {
					// Non-existent page and invalid parameter syntax, red link.
					$content .= self::mGenerateNotaBox( '[[' . $template . ']]' );
				}
			} else {
				// Invalid title, output raw.
				$content .= self::mGenerateNotaBox( $template );
			}
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
			if ( ! $url->isDisabled() ) {
				$top = '[[' . $url->text() . '|' . $top . ']]';
			}
			$top = '! class="mw-babel-header" | ' . $top;
		}
		$footer = wfMessage( 'babel-footer', self::$title->getDBkey() )->inLanguage( $uiLang );

		$url = wfMessage( 'babel-footer-url' )->inContentLanguage();
		$showfooter = '';
		if ( !$footer->isDisabled() && !$url->isDisabled() ) {
			$showfooter = '! class="mw-babel-footer" | [[' . $url->text() . '|' . $footer->text() . ']]';
		}
		$cellspacing = Babel::mHtmlAttrib( 'cellspacing', 'babel-box-cellspacing' );
		$cellpadding = Babel::mHtmlAttrib( 'cellpadding', 'babel-box-cellpadding' );

		$tower = <<<EOT
{|$cellspacing$cellpadding class="mw-babel-wrapper"
$top
|-
| $content
|-
$showfooter
|}
EOT;
		wfProfileOut( __METHOD__ );
		return $tower;
	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param $parameters Array: Templates to perform the link batch on.
	 */
	protected static function mTemplateLinkBatch( $parameters ) {
		wfProfileIn( __METHOD__ );
		$titles = array();
		foreach ( $parameters as $name ) {
			$title = Title::newFromText( wfMessage( 'babel-template', $name )->inContentLanguage()->text() );
			if ( is_object( $title ) ) {
				$titles[] = $title;
			}
		}

		$batch = new LinkBatch( $titles );
		$batch->setCaller( __METHOD__ );
		$batch->execute();
		wfProfileOut( __METHOD__ );
	}

	/**
	 * Identify whether or not a page exists.
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
	 * @param $strtolower Boolean: Whether to convert the language code to lowercase
	 * @return Array: { 'code' => xx, 'level' => xx }
	 */
	protected static function mParseParameter( $parameter, $strtolower = false ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelDefaultLevel, $wgBabelCategoryNames;
		$return = array();

		$babelcode = $strtolower ? strtolower( $parameter ) : $parameter;
		// Try treating the paramter as a language code (for default level).
		$code = BabelLanguageCodes::getCode( $babelcode );
		if ( $code !== false ) {
			$return['code'] = $code;
			$return['level'] = $wgBabelDefaultLevel;
			wfProfileOut( __METHOD__ );
			return $return;
		}
		// Try splitting the paramter in to language and level, split on last hyphen.
		$lastSplit = strrpos( $parameter, '-' );
		if ( $lastSplit === false ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		$code  = substr( $parameter, 0, $lastSplit );
		$level = substr( $parameter, $lastSplit + 1 );

		$babelcode = $strtolower ? strtolower( $code ) : $code;
		// Validate code.
		$return['code'] = BabelLanguageCodes::getCode( $babelcode );
		if ( $return['code'] === false ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		// Validate level.
		$level = strtoupper( $level );
		if ( !isset( $wgBabelCategoryNames[$level] ) ) {
			wfProfileOut( __METHOD__ );
			return false;
		}
		$return['level'] = $level;
		wfProfileOut( __METHOD__ );
		return $return;
	}

	/**
	 * Generate an inner item which is not a babel box.
	 *
	 * @param $content String: what's inside the box, in wikitext format.
	 * @return String: A single non-babel box, in wikitext format.
	 */
	protected static function mGenerateNotaBox( $content ) {
		$dir_head = self::$title->getPageLanguage()->getDir();
		$notabox = <<<EOT
<div class="mw-babel-notabox" dir="$dir_head">$content</div>
EOT;
		return $notabox;
	}

	/**
	 * Generate a babel box for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @return String: A single babel box, in wikitext format.
	 */
	protected static function mGenerateBox( $code, $level ) {
		wfProfileIn( __METHOD__ );
		$lang =  wfBCP47( $code );
		$portal = wfMessage( 'babel-portal', $code )->inContentLanguage()->plain();
		if ( $portal !== '' ) {
			$portal = "[[$portal|$lang]]";
		} else {
			$portal = $lang;
		}
		$header = "$portal<span class=\"mw-babel-box-level-$level\">-$level</span>";

		$code = strtolower( $code );
		$name = BabelLanguageCodes::getName( $code );
		$code = BabelLanguageCodes::getCode( $code );
		$text = self::mGetText( $name, $code, $level );

		$dir_current = Language::factory( $code )->getDir();

		$cellspacing = Babel::mHtmlAttrib( 'cellspacing', 'babel-cellspacing' );
		$cellpadding = Babel::mHtmlAttrib( 'cellpadding', 'babel-cellpadding' );
		$dir_head = self::$title->getPageLanguage()->getDir();

		$box = <<<EOT
<div class="mw-babel-box mw-babel-box-$level" dir="$dir_head">
{|$cellspacing$cellpadding
! dir="$dir_head" | $header
| dir="$dir_current" lang="$lang" | $text
|}
</div>
EOT;
		wfProfileOut( __METHOD__ );
		return $box;
	}

	/**
	 * Get the text to display in the language box for specific language and
	 * level.
	 *
	 * @param $name string
	 * @param $language String: Language code of language to use.
	 * @param $level String: Level to use.
	 * @return String: Text for display, in wikitext format.
	 */
	protected static function mGetText( $name, $language, $level ) {
		wfProfileIn( __METHOD__ );
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

		$text = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', self::$title->getDBkey()
		)->inLanguage( $language )->text();

		$fallbackLanguage = Language::getFallbackfor( $language );
		$fallback = wfMessage( "babel-$level-n",
			$categoryLevel, $categoryMain, '', self::$title->getDBkey()
		)->inLanguage( $fallbackLanguage ? $fallbackLanguage : $language )->text();

		if ( $text == $fallback ) {
			$text = wfMessage( "babel-$level",
				$categoryLevel, $categoryMain, $name, self::$title->getDBkey()
			)->inLanguage( $language )->text();
		}

		wfProfileOut( __METHOD__ );
		return $text;
	}

	/**
	 * Generate categories for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 * @param $createCategories Boolean: If true, creates non existing categories; otherwise, doesn't create them.
	 * @return String: Wikitext to add categories.
	 */
	protected static function mGenerateCategories( $code, $level, $createCategories = true ) {
		wfProfileIn( __METHOD__ );
		global $wgBabelMainCategory, $wgBabelCategoryNames;

		$r = '';

		# Add main category
		if ( $wgBabelMainCategory !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelMainCategory, $code );
			$r .= "[[Category:$category|$level]]";
			if ( $createCategories ) {
				BabelAutoCreate::create( $category, $code );
			}
		}

		# Add level category
		if ( $wgBabelCategoryNames[$level] !== false ) {
			$category = self::mReplaceCategoryVariables( $wgBabelCategoryNames[$level], $code );
			$r .= "[[Category:$category]]";
			if ( $createCategories ) {
				BabelAutoCreate::create( $category, $code, $level );
			}
		}

		wfProfileOut( __METHOD__ );
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
		$category = strtr( $category, array(
			'%code%' => $code,
			'%wikiname%' => BabelLanguageCodes::getName( $code, $wgLanguageCode ),
			'%nativename%' => BabelLanguageCodes::getName( $code )
		) );
		return $category;
	}

	/**
	 * Determine an HTML attribute, such as "cellspacing" or "title", from a localizeable message.
	 *
	 * @param $name String: name of HTML attribute.
	 * @param $key String: Message key of attribute value.
	 * TODO: move this function to a more appropriate place, likely outside the class.
	 * @return Message|string
	 */
	protected static function mHtmlAttrib( $name, $key ) {
		$value = wfMessage( $key )->inContentLanguage();
		if ( $value->isDisabled() ) {
			$value = '';
		} else {
			$value = ' ' . $name . '="' . htmlentities( $value->text(), ENT_COMPAT, 'UTF-8' ) .
						'"'; // must get rid of > and " inside value
		}
		return $value;
	}
}
