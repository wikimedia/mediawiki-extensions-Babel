<?php

/**
 * Main class for the Babel extension.
 *
 * @ingroup Extensions
 */
class Babel {
	/**
	 * Render the Babel tower.
	 *
	 * @param $parser Object: Parser.
	 * @return string: Babel tower.
	 */
	public function Render( $parser ) {
		$parameters = func_get_args();
		array_shift( $parameters );

		$this->mTemplateLinkBatch( $parameters );

		$contents = '';
		foreach ( $parameters as $name ) {
			if ( $name === '' ) {
				continue;
			} elseif ( $this->mTemplateExists( $name ) ) {
				$contents .= $parser->replaceVariables( "{{{$this->mAddFixes( $name,'template' )}}}" );
			} elseif ( $chunks = $this->mParseParameter( $name ) ) {
				$contents .= $this->mGenerateBox(        $chunks['code'], $chunks['level'] );
				$contents .= $this->mGenerateCategories( $chunks['code'], $chunks['level'] );
			} elseif ( $this->mValidTitle( $name ) ) {
				// Non-existent page and invalid parameter syntax, red link.
				$contents .= "\n[[Template:{$this->mAddFixes( $name,'template' )}]]";
			} else {
				// Invalid title, output raw.
				$contents .= "\nTemplate:{$this->mAddFixes( $name,'template' )}";
			}
		}

		$footer = wfMsgForContent( 'babel-footer' );
		if( wfEmptyMsg( $footer ) ) {
			$footer = '';
		} else {
			$footer = 'class="mw-babel-footer" | ' . $footer;
		}

		global $wgTitle;
		$cellspacing = wfMsgForContent( 'babel-box-cellspacing' );
		$url = wfMsgForContent( 'babel-url' );
		$top = wfMsgExt( 'babel', array( 'parsemag', 'content' ), $wgTitle->getDBkey() );
		return <<<PHP
{| cellspacing="$cellspacing" class="mw-babel-wrapper"
! [[$url|$top]]
|-
| $contents
|-
$footer
|}
PHP;
	}

	/**
	 * Adds prefixes and suffixes for a particular type to the string.
	 *
	 * @param $string String: Value to add prefixes and suffixes too.
	 * @param $type String: Type of prefixes and suffixes (template/portal/category).
	 * @return String: Value with prefixes and suffixes added.
	 */
	protected function mAddFixes( $string, $type ) {
		return wfMsgForContent( "babel-$type-prefix" ) . $string . wfMsgForContent( "babel-$type-suffix" );
	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param $parameters Array: Templates to perform the link batch on.
	 */
	protected function mTemplateLinkBatch( $parameters ) {
		$titles = array();
		foreach ( $parameters as $name ) {
			$title = Title::newFromText( $this->mAddFixes( $name, 'template' ), NS_TEMPLATE );
			if ( is_object( $title ) ) {
				$titles[] = $title;
			}
		}

		$batch = new LinkBatch( $titles );
		$batch->execute();
	}

	/**
	 * Identify whether or not the template exists or not.
	 *
	 * @param $title String: Name of the template to check.
	 * @return Boolean: Indication of whether the template exists.
	 */
	protected function mTemplateExists( $title ) {
		$titleObj = Title::newFromText( $this->mAddFixes( $title, 'template' ), NS_TEMPLATE );
		return ( is_object( $titleObj ) && $titleObj->exists() );
	}

	/**
	 * Identify whether or not the passed string would make a valid title.
	 *
	 * @param $title string: Name of title to check.
	 * @return Boolean: Indication of whether or not the title is valid.
	 */
	protected function mValidTitle( $title ) {
		$titleObj = Title::newFromText( $this->mAddFixes( $title, 'template' ), NS_TEMPLATE );
		return is_object( $titleObj );
	}

	/**
	 * Parse a parameter, getting a language code and level.
	 *
	 * @param $parameter String: Parameter.
	 * @return Array: { 'code' => xx, 'level' => xx }
	 */
	protected function mParseParameter( $parameter ) {
		$return = array();

		// Try treating the paramter as a language code (for native).
		if ( BabelLanguageCodes::getCode( $parameter ) ) {
			$return['code'] = BabelLanguageCodes::getCode( $parameter );
			$return['level'] = 'N';
			return $return;
		}
		// Try splitting the paramter in to language and level, split on last hyphen.
		$lastSplit = strrpos( $parameter, '-' );
		if ( $lastSplit === false ) return false;
		$code  = substr( $parameter, 0, $lastSplit );
		$level = substr( $parameter, $lastSplit + 1 );

		// Validate code.
		$return['code'] = BabelLanguageCodes::getCode( $code );
		if ( !$return['code'] ) return false;
		// Validate level.
		$intLevel = (int) $level;
		if ( ( $intLevel < 0 || $intLevel > 5 ) && $level !== 'N' ) return false;
		$return['level'] = $level;

		return $return;
	}

	/**
	 * Generate a babel box for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 */
	protected function mGenerateBox( $code, $level ) {
		$header = "[[{$this->mAddFixes( $code,'portal' )}|" . wfBCP47( $code ) . "]]<span class=\"mw-babel-box-level-$level\">-$level</span>";

		$name = BabelLanguageCodes::getName( $code );
		$code = BabelLanguageCodes::getCode( $code );
		$text = $this->mGetText( $name, $code, $level );

		$dir_content = wfMsgForContent( 'babel-directionality' );
		$dir_current = wfMsgExt( 'babel-directionality', array( 'language' => $code ) );
		$cellspacing = wfMsgForContent( 'babel-cellspacing' );

		return <<<PHP
<div class="mw-babel-box mw-babel-box-$level" dir="$dir_content">
{| cellspacing="$cellspacing"
!  dir="$dir_content" | $header
|  dir="$dir_current" | $text
|}
</div>
PHP;
	}

	/**
	 * Get the text to display in the language box for specific language and
	 * level.
	 *
	 * @param $language String: Language code of language to use.
	 * @param $level String: Level to use.
	 * @return String: Text for display, in wikitext format.
	 */
	protected function mGetText( $name, $language, $level ) {
		global $wgTitle, $wgBabelUseLevelZeroCategory;

		$categoryLevel = ":Category:{$this->mAddFixes( "$language-$level",'category' )}";
		$categorySuper = ":Category:{$this->mAddFixes( $language,'category' )}";

		if ( !$wgBabelUseLevelZeroCategory && $level === '0' ) {
			$categoryLevel = $wgTitle->getFullText();
		}

		$text = wfMsgExt( "babel-$level-n",
			array( 'language' => $language, 'parsemag' ),
			$categoryLevel, $categorySuper, '', $wgTitle->getDBkey()
		);

		$fallback = wfMsgExt( "babel-$level-n",
			array( 'language' => Language::getFallbackfor( $language ), 'parsemag' ),
			$categoryLevel, $categorySuper, '', $wgTitle->getDBkey()
		);

		if ( $text == $fallback ) {
			$text = wfMsgExt( "babel-$level",
				array( 'language' => $language, 'parsemag' ),
				$categoryLevel, $categorySuper, $name, $wgTitle->getDBkey()
			);
		}

		return $text;
	}

	/**
	 * Generate categories for the given language and level.
	 *
	 * @param $code String: Language code to use.
	 * @param $level String or Integer: Level of ability to use.
	 */
	protected function mGenerateCategories( $code, $level ) {
		global $wgBabelUseMainCategories, $wgBabelUseLevelZeroCategory,
			$wgBabelUseSimpleCategories;

		$r = '';

		if ( $wgBabelUseMainCategories && ( $level === 'N' || ( $wgBabelUseLevelZeroCategory && $level === '0' ) || $level > 0 ) ) {
			$r .= "[[Category:{$this->mAddFixes( $code,'category' )}|$level]]";
			BabelAutoCreate::create( $this->mAddFixes( "$code", 'category' ), BabelLanguageCodes::getName( $code ) );
		}

		if ( !$wgBabelUseSimpleCategories && ( $level === 'N' || ( $wgBabelUseLevelZeroCategory && $level === '0' ) || $level > 0 ) ) {
			$r .= "[[Category:{$this->mAddFixes( "$code-$level",'category' )}]]";
			BabelAutoCreate::create( $this->mAddFixes( "$code-$level", 'category' ), $level, BabelLanguageCodes::getName( $code ) );
		}

		return $r;
	}
}
