<?php

/**
 * Babel Extension
 * 
 * Adds a parser function to allow automated generation of a babel userbox
 * column with the ability to include custom templates.
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
	'description-msg' => 'babel-desc',
);

// Register setup function.
$wgExtensionFunctions[] = 'efBabelParserFunction_Setup';

// Register required hooks.
$wgHooks[ 'LanguageGetMagic' ][] = 'efBabelParserFunction_Magic';

// Register internationalisation file.
$wgExtensionMessagesFiles[ 'Babel' ] = dirname( __FILE__ ) . '/Babel.i18n.php';

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
	wfLoadExtensionMessages( 'Babel' );

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
			if( $title && $title->exists() ) {
				
				/* Create an article object for the current box.
				 */
				$article = new Article( $title );
				
				/* Add the content of the template page to the box tower content.
				 */
				$boxes .= $article->getContent();
				
			} elseif( strpos( $name, '-' ) && strpos( $name, '-' ) != strlen( $name ) - 1 ) {
				
				/* The parameter is in the correct syntax to refer to a box
				 * that can be automatically, generated attempt to.
				 */
				
				/* Break the parameter up on '-' to get the language code (0)
				 * and level (1).
				 */
				$param = explode( '-', $name );
				
				/* Get the list of language names so that the parameter can be
				 * validated.
				 */
				global $wgLanguageNames;

				if( 
					/* Correct number of parameters */
					count( $param ) == 2 &&
					/* Valid language name. */
					isset( $wgLanguageNames[ $param[ 0 ] ] )  &&
					/* Valid language level. */
					( strtoupper( $param[ 1 ] ) == 'N' || ( $param[ 1 ] <= 5 && $param[ 1 ] >= 0 ) )
				  ) {
				  	
					/* Put the array elements into variables for easier
					 * processing.
					 */
					$code  = $param[ 0 ];
					$level = $param[ 1 ];
					
					/* Make the code have an upper case first character.
					 */
					$ufcode = ucfirst( $code );

					/* Generate the text displayed on the left hand side of the
					 * box.
					 */
					$header = "[[{$prefixes['portal']}$ufcode{$suffixes['portal']}|$code]]-$level";

					/* Generate the text displayed on the right hand side of the
					 * box.
					 */
					$text = wfMsg( "babel-$level",
						":Category:{$prefixes['category']}$code-$level{$suffixes['category']}",
						":Category:{$prefixes['category']}$code{$suffixes['category']}",
						$wgLanguageNames[ $code ]
					);

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

				} else {

					/* Template does not exist and not a valid format to create 
					 * a default box, output a redlink.
					 */
					$boxes .= "[[Template:{$prefixes['template']}$name{$suffixes['template']}|Template:{$prefixes['template']}$name{$suffixes['template']}]]";
					
				}
				
			}  else {

				/* Template does not exist and not a valid format to create 
				 * a default box, output a redlink.
				 */
				$boxes .= "[[Template:{$prefixes['template']}$name{$suffixes['template']}|Template:{$prefixes['template']}$name{$suffixes['template']}]]";
				
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
