<?php

/**
 * Main class for the Babel extension.
 *
 * @addtogroup Extensions
 */

class Babel {

	private $_LanguageTools;
	private $_prefixes, $_suffixes, $_cellspacing, $_directionality;

	/**
	 * Registers the parser function hook.
	 */
	static public function Setup() {

		/* Get the parser object.
		 */
		global $wgParser;

		/* Get Babel object.
		 */
		global $wgBabel;

		/* Register the hook within the parser object.
		 */
		$wgParser->setFunctionHook( 'babel', array( $wgBabel, 'Render' ) );

		/* Return true to ensure processing is continued and an exception is not
		 * generated.
		 */
		return true;

	}

	/**
	 * Registers the parser function magic word.
	 */
	static public function Magic( $magicWords, $langCode ) {

		/* Register the magic word, maybe one day this could be localised by adding
		 * synonyms into the array -- but there is currently no simple way of doing
		 * that given the current way of localisation.  The first element is set to
		 * 0 so that it can be case insensitive.
		 */
		$magicWords[ 'babel' ] = array( 0, 'babel' );

		/* Return true to ensure processing is continued and an exception is not
		 * generated.
		 */
		return true;

	}

	public function __construct( $LanguageTools ) {

		/* Add passed language tool to the object method.
		 */
		$this->_LanguageTools = $LanguageTools;

	}

	public function Render( $parser ) {

		/* Store all the parameters passed to this function in an array.
		 */
		$parameters = func_get_args();

		/* Remove the first parameter (the parser object), the rest correspond
		 * to the parameters passed to the babel parser function.
		 */
		unset( $parameters[ 0 ] );

		/* Load the extension messages.
		 */
		wfLoadExtensionMessages( 'Babel', true );

		/* Create an array of all prefixes.
		 */
		$this->_prefixes = array(
			'category' => wfMsgForContent( 'babel-category-prefix' ),
			'template' => wfMsgForContent( 'babel-template-prefix' ),
			'portal'   => wfMsgForContent( 'babel-portal-prefix'   ),
		);

		/* Create an array of all suffixes.
		 */
		$this->_suffixes = array(
			'category' => wfMsgForContent( 'babel-category-suffix' ),
			'template' => wfMsgForContent( 'babel-template-suffix' ),
			'portal'   => wfMsgForContent( 'babel-portal-suffix'   ),
		);

		/* Miscellaneous messages.
		 */
		$url                   = wfMsgForContent( 'babel-url'             );
		$top                   = wfMsgForContent( 'babel'                 );
		$this->_directionality = wfMsgForContent( 'babel-directionality'  );
		$this->_cellspacing    = wfMsgForContent( 'babel-box-cellspacing' );

		/* Do a link batch on all the parameters so that their information is
		 * cached for use later on.
		 */
		$this->_doTemplateLinkBatch( $parameters );

		/* Initialise an empty string for storing the contents of the tower.
		 */
		$contents = '';

		/* Loop through each of the input parameters.
		 */
		foreach( $parameters as $name ) {

			/* Check if the parameter is a valid template name, if it is then
			 * include that template.
			 */
			if( $this->_templateExists( $name ) ) {

				$contents .= $parser->replaceVariables( "{{{$this->addFixes( $name,'template' )}}}" );

			} elseif( $chunks = $this->_parseParameter( $name ) ) {

				$contents .= $this->_generateBox(        $chunks[ 'code' ], $chunks[ 'level' ] );
				$contents .= $this->_generateCategories( $chunks[ 'code' ], $chunks[ 'level' ] );

			} elseif( $this->_validTitle( $name ) ) {

				/* Non-existant page and invalid parameter syntax, red link */
				$contents .= "[[Template:{$this->addFixes( $name,'template' )}]]";

			} else {

				/* Invalid title, output raw.
				 */
				$contents .= "Template:{$this->addFixes( $name,'template' )}";

			}

		}

		/* Generate tower, filled with contents from loop.
		 */
		$r = <<<HEREDOC
{| cellspacing="{$this->_cellspacing}" class="mw-babel-wrapper"
! [[$url|$top]]
|-
| $contents
|}
HEREDOC;

		/* Outupt tower.
		 */
		return $r;


	}

	/**
	 * Adds prefixes and suffixes for a particular type to the string.
	 *
	 * @param string $string String to add prefixes and suffixes too.
	 * @param string $type Type of prefixes and suffixes (template/portal/category).
	 * @return string String with prefixes and suffixes added.
	 */
	private function addFixes( $string, $type ) {

		return $this->_prefixes[ $type ] . $string . $this->_suffixes[ $type ];

	}

	/**
	 * Performs a link batch on a series of templates.
	 *
	 * @param array $parameters Array of templates to perform the link batch on.
	 */
	private function _doTemplateLinkBatch( $parameters ) {

		/* Prepare an array, this will be used to store the title objects for
		 * each of the parameters.
		 */
		$titles = array();

		/* Loop through the array passed to this function and generate a title
		 * object for each, then add that title object to the title object
		 * array if it is an object (invalid page names generate NULL rather
		 * than a valid title object.
		 */
		foreach( $parameters as $name ) {

			/* Create the title object.
			 */
			$title = Title::newFromText( $this->addFixes( $name,'template' ), NS_TEMPLATE );

			/* Check if the title object was created sucessfully.
			 */
			if( is_object( $title ) ) {

				/* It was, add it to the array of title objects.
				 */
				$titles[] = $title;

			}

		}

		/* Create the link batch object for the array of title objects that has
		 * been generated.
		 */
		$batch = new LinkBatch( $titles );

		/* Execute the link batch.
		 */
		$batch->execute();

	}

	/**
	 * Identify whether or not the template exists or not.
	 *
	 * @param string $title Name of the template to check.
	 * @return boolean Indication of whether the template exists.
	 */
	private function _templateExists( $title ) {

		/* Make title object from the templates title.
		 */
		$titleObj = Title::newFromText( $this->addFixes( $title,'template' ), NS_TEMPLATE );

		/* If the title object has been created (is of a valid title) and the template
		 * exists return true, otherwise return false.
		 */
		return ( is_object( $titleObj ) && $titleObj->exists() );

	}

	/**
	 * Identify whether or not the passed string would make a valid title.
	 *
	 * @param string $title Name of title to check.
	 * @return boolean Indication of whether or not the title is valid.
	 */
	private function _validTitle( $title ) {

		/* Make title object from the templates title.
		 */
		$titleObj = Title::newFromText( $this->addFixes( $name,'template' ), NS_TEMPLATE );

		/* If the title object has been created (is of a valid title) return true.
		 */
		return is_object( $titleObj );

	}

	/**
	 * Parse a parameter, getting a language code and level.
	 *
	 * @param string $parameter Parameter.
	 * @return array array( 'code' => xx, 'level' => xx )
	 */
	private function _parseParameter( $parameter ) {

		/* Get the favoured standard.
		 */
		global $wgBabelFavorStandard;

		/* Break up the parameter on - (which seperates it's two parts).
		 */
		$chunks = explode( '-', $parameter );

		/* Initialise the return array.
		 */
		$return = array();

		/* Actually parse the parameter.
		 */
		if( count( $chunks ) == 1 ) {

			/* The parameter is in the form 'xx'.
			 */

			/* Check whether the language code is valid.
			 */
			if( $this->_LanguageTools->checkCode( $chunks[ 0 ] ) ) {

				/* Set the code for returning.
				 */
				$return[ 'code' ] = $this->_LanguageTools->getCode( $chunks[ 0 ], $wgBabelFavorStandard );

				/* This form defaults to level 'N'.
				 */
				$return[ 'level' ] = 'N';

				/* Everything needed has been gathered, return.
				 */
				return $return;

			} else {

				/* Invalid language code, return false.
				 */
				return false;

			}

		} elseif( count( $chunks ) == 2 ) {

			/* The parameter is in the form 'xx-x'.
			 */

			/* Check whether the language code is valid.
			 */
			if( $this->_LanguageTools->checkCode( $chunks[ 0 ] ) ) {

				/* Set the code for returning.
				 */
				$return[ 'code' ] = $this->_LanguageTools->getCode( $chunks[ 0 ], $wgBabelFavorStandard );

			} else {

				/* Invalid language code, return false.
				 */
				return false;

			}

			/* Check whether the level is valid.
			 */
			if( strtoupper( $chunks[ 1 ] ) == 'N' ) {

				$return[ 'level' ] = 'N';

			} elseif( $chunks[ 1 ] >= 0 && $chunks[ 1 ] <= 5 ) {

				$return[ 'level' ] = $chunks[ 1 ];

			} else {

				/* Invalid language code.
				 */
				return false;

			}

			/* Parameters decided, return parameters.
			 */
			return $return;

		} else {

			/* Invalid parameters.
			 */
			return false;

		}

	}

	/**
	 * Generate a babel box for the given language and level.
	 *
	 * @param string $code Language code to use.
	 * @param string or integer $level Level of ability to use.
	 */
	private function _generateBox( $code, $level ) {

		/* Get favored standard.
		 */
		global $wgBabelFavorStandard;

		/* Get code in favoured standard.
		 */
		$code = $this->_LanguageTools->getCode( $code, $wgBabelFavorStandard );

		/* Generate the text displayed on the left hand side of the
		 * box.
		 */
		$header = "[[{$this->addFixes( $code,'portal' )}|$code]]-$level";

		/* Get the language names.
		 */
		if( class_exists( 'LanguageNames' ) ) {
			$names = LanguageNames::getNames( $code );
		} else {
			$names = Language::getLanguageNames();
		}

		/* Ensure the language code has a corresponding name.
		 */
		if( array_key_exists( $code, $names ) ) {
			$name = $names[ $code ];
		} else {
			$name = $code;
		}

		/* Generate the text displayed on the right hand side of the
		 * box.
		 */

		/* Try the language of the box.
		 */
		$text = wfMsgExt( "babel-$level-n",
			array( 'nofallback', 'language' => $code ),
			":Category:{$this->addFixes( "$code-$level",'category' )}",
			":Category:{$this->addFixes( $code,'category' )}"
		);

		/* Get the fallback message for comparison.
		 */
		$fallback = wfMsgExt( "babel-$level-n",
			array( 'nofallback', 'language' => Language::getFallbackfor( $code ) ),
			":Category:{$this->addFixes( "$code-$level",'category' )}",
			":Category:{$this->addFixes( $code,'category' )}"
		);

		/* Translation not found, use the generic translation of the
		 * highest level fallback possible.
		 */
		if( $text == $fallback ) {
			$text = wfMsgExt( "babel-$level",
				array( 'language' => $code ),
				":Category:{$this->addFixes( "$code-$level",'category')}",
				":Category:{$this->addFixes( $code,'category' )}",
				$name
			);
		}

		/* Get the directionality for the current language.
		 */
		$dir = wfMsgExt( "babel-directionality",
			array( 'language' => $code )
		);

		/* Generate box and add return.
		 */
		return <<<HEREDOC
<div class="mw-babel-box mw-babel-box-$level" dir="{$this->_directionality}">
{| cellspacing="{$this->_cellspacing}"
!  dir="{$this->_directionality}" | $header
|  dir="$dir" | $text
|}
</div>
HEREDOC;

	}


	/**
	 * Generate categories for the given language and level.
	 *
	 * @param string $code Language code to use.
	 * @param string or integer $level Level of ability to use.
	 */
	private function _generateCategories( $code, $level ) {

		/* Get whether or not to use main categories.
		 */
		global $wgBabelUseMainCategories;

		/* Get whether or not to use level zero categories.
		 */
		global $wgBabelUseLevelZeroCategory;

		/* Get whether or not to use simple categories.
		 */
		global $wgBabelUseSimpleCategories;

		/* Get user object.
		 */
		global $wgUser;

		/* Intialise return value.
		 */
		$r = '';

		/* Add to main language category if the level is not zero.
		 */
		if( $wgBabelUseMainCategories && ( $level === 'N' || ( $wgBabelUseLevelZeroCategory && $level === 0 ) || $level > 0 ) ) {

			/* Add category wikitext to box tower.
			 */
			$r .= "[[Category:{$this->addFixes( $code,'category' )}|$level{$wgUser->getName()}]]";

		}

		/* Add to level categories, only adding it to the level 0
		 * one if it is set to be used.
		 */
		if( !$wgBabelUseSimpleCategories && ( $level === 'N' || ( $wgBabelUseLevelZeroCategory && $level === 0 ) || $level > 0 ) ) {

			/* Add category wikitext to box tower.
			 */
			$r .= "[[Category:{$this->addFixes( "$code-$level",'category' )}|{$wgUser->getName()}]]";

		}

		/* Return categories.
		 */
		return $r;

	}

}