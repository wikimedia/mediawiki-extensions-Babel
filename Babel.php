<?php

/**
 * Babel Extension
 * 
 * Adds a parser function to allow automated generation of a babel userbox
 * column with the ability to include custom templates.
 *
 * TODO:
 *  Supress category for babel level 0.
 *
 * @addtogroup Extensions
 *
 * @link http://www.mediawiki.org/wiki/Extension:Babel
 *
 * @author MinuteElectron <minuteelectron@googlemail.com>
 * @copyright Copyright � 2008 MinuteElectron.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Ensure accessed via a valid entry point.
if( !defined( 'MEDIAWIKI' ) ) die( 'Invalid entry point.' );

// Register extension credits.
$wgExtensionCredits[ 'parserhook' ][] = array(
	'name'            => 'Babel',
	'version'         => '1.0',
	'author'          => 'MinuteElectron',
	'url'             => 'http://www.mediawiki.org/wiki/Extension:Babel',
	'description'     => 'Adds a parser function to allow automated generation of a babel userbox column with the ability to include custom templates.',
	'descriptionmsg'  => 'babel-desc',
);

// Register setup function.
$wgExtensionFunctions[] = 'efBabelParserFunction_Setup';

// Register required hooks.
$wgHooks[ 'LanguageGetMagic' ][] = 'efBabelParserFunction_Magic';

// Register internationalisation file.
$wgExtensionMessagesFiles[ 'Babel' ] = dirname( __FILE__ ) . '/Babel.i18n.php';

// Register language code file.
$wgLanguageCodeFile =  dirname( __FILE__ ) . '/LanguageCodes.php';

// Create language code cache.
$wgLanguageCodeCache = false;

/**
 * Registers the parser function hook.
 */
function efBabelParserFunction_Setup() {

	/* Get the parser object.
	 */
	global $wgParser;

	/* Register the hook within the parser object.
	 */
	$wgParser->setFunctionHook( 'babel', 'efBabelParserFunction_Render' );

	/* Return true to ensure processing is continued and an exception is not
	 * generated.
	 */
	return true;

}

/**
 * Registers the parser function magic word.
 */
function efBabelParserFunction_Magic( &$magicWords, $langCode ) {

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

/**
 * Render the parser function output.
 */
function efBabelParserFunction_Render( $parser ) {

	/* Load extension messages.
	 */
	wfLoadExtensionMessages( 'Babel', true );

	/* Initialise variable for storing the content of the babel tower.
	 */
	$boxes = '';

	/* Get all parameters passed to the function, there could be an endless
	 * ammount so this cannot be hardcoded.
	 */
	$args = func_get_args();
	
	/* Create an array of all prefixes.
	 */
	$prefixes = array(
		'category' => wfMsgForContent( 'babel-category-prefix' ),
		'template' => wfMsgForContent( 'babel-template-prefix' ),
		'portal'   => wfMsgForContent( 'babel-portal-prefix'   ),
	);

	/* Create an array of all suffixes.
	 */
	$suffixes = array(
		'category' => wfMsgForContent( 'babel-category-suffix' ),
		'template' => wfMsgForContent( 'babel-template-suffix' ),
		'portal'   => wfMsgForContent( 'babel-portal-suffix'   ),
	);

	/* Miscellaneous messages.
	 */
	$url            = wfMsgForContent( 'babel-url'             );
	$top            = wfMsgForContent( 'babel'                 );
	$directionality = wfMsgForContent( 'babel-directionality'  );
	$cellspacing    = wfMsgForContent( 'babel-box-cellspacing' );

	/* Get content language of the wiki.
	 */
	global $wgLanguageCode;
	
	/* Get the mult-language message cache.
	 */
	global $wgMultiMessageCache;

	/* Loop through the array of parameters.
	 */
	foreach( $args as $name ) {
		
		/* Skip this itteration if the parameter is an object to avoid
		 * accidentally trying to process the parser object.
		 */
		if( !is_object( $name) ) {
			
			/* Create a title object for the current box being generated,
			 */
			$title = Title::newFromText( "{$prefixes['template']}$name{$suffixes['template']}", NS_TEMPLATE );

			/* If the template exists then use that, rather than generating a
			 * default babel box.
			 */
			if( is_object( $title ) && $title->exists() ) {
				
				/* Transclude the template, this uses a private function in the
				 * parser so could break; at some point it would be nice to
				 * find a way to move it to a public function.
				 */
				$boxes .= $parser->replaceVariables( '{{' . $title->getDbKey() . '}}' );
				
			} else {
				
				/* Check for validity of the syntax.
				 */
				
				/* Get lower case of name.
				 */
				$lname = strtolower( $name );
				
				/* Default validity to false.
				 */
				$validity = false;

				/* Check if parameter is exactly equal to a valid language
				 * code.
				 */
				if( efBabelCheckLanguageCode( $lname ) ) {
					
					$code = $lname;
					$level = 'N';
					$validity = true;
					
				}
				
				/* Break parameter in to chunks for validation.
				 */
				$chunks = explode( '-', $name );
				
				/* Ensure there are only two parts.
				 */
				if( count( $chunks ) == 2 ) {
					
					/* Move into variables.
					 */
					$code  = strtolower( $chunks[ 0 ] );
					$level = strtoupper( $chunks[ 1 ] ); 
					
					/* Check whether the first chunk is a valid language code.
					 */
					if( efBabelCheckLanguageCode( $code ) ) {
						
						/* Check whether the second chunk is within the valid
						 * limits.
						 */
						if( is_numeric( $level ) && $level >= 0 && $level <= 5  ) {

							$validity = true;
							
						} elseif( $level == 'N' ) {

							$validity = true;
							
						}
						
					}
					
				}

				if( $validity ) {
				  	
					/* The parameter is in a valid format for rendering of a
					 * default box.
					 */
					
					/* Generate the text displayed on the left hand side of the
					 * box.
					 */
					$header = "[[{$prefixes['portal']}$code{$suffixes['portal']}|$code]]-$level";

					/* Get the language name.
					 */
					$names = Language::getLanguageNames();
					
					/* Temporary measure to supress PHP Notice until ISO 639-3
					 * to ISO 639-1 correlation is implemented and a system to
					 * also try and get messages from CLDR is implemented.
					 */
					if( $names[ $code ] ) {
						$name = $names[ $code ];
					}

					/* Generate the text displayed on the right hand side of the
					 * box.
					 */
					$text = wfMsgExt( "babel-$level",
						array( 'language' => $code ),
						":Category:{$prefixes['category']}$code-$level{$suffixes['category']}",
						":Category:{$prefixes['category']}$code{$suffixes['category']}",
						$name
					);
					
					/* If the message is not found use the -r variant.
					 * Temporarily disabled until a way for it to work is found.
					 *//*
					if( $text == htmlspecialchars( "<bable-$level>" ) ) {
						
						$text = wfMsgContent( "babel-$level-r",
							":Category:{$prefixes['category']}$code-$level{$suffixes['category']}",
							":Category:{$prefixes['category']}$code{$suffixes['category']}",
							$name
						);
					
					}*/

					/* Generate box and add to the end of the boxes tower.
					 */
					$boxes .= <<<HEREDOC
<div class="mw-babel-box mw-babel-box-$level" dir="$directionality">
{| cellspacing="$cellspacing"
!  dir="$directionality" | $header
|  dir="$directionality" | $text
|}
</div>[[Category:{$prefixes['category']}$code-$level{$suffixes['category']}]][[Category:{$prefixes['category']}$code{$suffixes['category']}]]
HEREDOC;

				} elseif( is_object( $title ) ) {

					/* Template does not exist and not a valid format to create 
					 * a default box, but the template name is valid; output a
					 * red link.
					 */
					$boxes .= "[[Template:{$prefixes['template']}$name{$suffixes['template']}|Template:{$prefixes['template']}$name{$suffixes['template']}]]";
					
				} else {
					
					/* Template name is invalid, output the template name on 
					 * it's own.
					 */
					$boxes .= "Template:{$prefixes['template']}$name{$suffixes['template']}";
					
				}
				
			}
			
		}
		
	}
	
	/* Generate tower.
	 */
	$r = <<<HEREDOC
{| cellspacing="$cellspacing" class="mw-babel-wrapper"
! [[$url|$top]]
|-
| $boxes
|}
HEREDOC;

	/* Outupt tower.
	 */
	return $r;

}

/**
 * Check if the specified code is a valid ISO 936-1 or ISO 936-3 language
 * code.
 * @return Boolean Whether or not the code is valid.
 * @param $code String Code to check.
 */
function efBabelCheckLanguageCode( $code ) {
	
	/* Get language cache.
	 */
	global $wgLanguageCodeCache;
	
	/* Ensure the codes are not already cached, or skip inclusion if they
	 * are.
	 */
	if( !is_array( $wgLanguageCodeCache ) ) {

		/* Get location of language code file.
		 */
		global $wgLanguageCodeFile;
		
		/* Include language code file.
		 */
		include( $wgLanguageCodeFile );
		
		/* Push language codes into the code cache.
		 */
		$wgLanguageCodeCache = $codes;
		
	}
	
	/* Check if the specified code is in the codes array and return result.
	 */
	return in_array( strtolower( $code ), $wgLanguageCodeCache );
	
}
