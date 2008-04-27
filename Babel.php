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
 * @copyright Copyright ? 2008 MinuteElectron.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

// Ensure accessed via a valid entry point.
if( !defined( 'MEDIAWIKI' ) ) die( 'Invalid entry point.' );

// Register extension credits.
$wgExtensionCredits[ 'parserhook' ][] = array(
	'name'            => 'Babel',
	'version'         => '0.8',
	'author'          => 'MinuteElectron',
	'url'             => 'http://www.mediawiki.org/wiki/Extension:Babel',
	'description'     => 'Adds a parser function to allow automated generation of a babel userbox column with the ability to include custom templates.',
	'descriptionmsg'  => 'babel-desc',
);

// Register setup function.
if( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'Babel::Setup';
} else {
	$wgExtensionFunctions[] = 'Babel::Setup';
}

// Register required hooks.
$wgHooks[ 'LanguageGetMagic' ][] = 'Babel::Magic';

// Register internationalisation file.
$wgExtensionMessagesFiles[ 'Babel' ] = dirname( __FILE__ ) . '/Babel.i18n.php';

// Register language code file.
$wgLanguageCodeFile =  dirname( __FILE__ ) . '/LanguageCodes.php';

// Include require classes.
require_once( dirname( __FILE__ ) . '/Babel_body.php'    );
require_once( dirname( __FILE__ ) . '/LanguageTools.php' );

// Definitions.
define( 'ISO_639_1', 1 );
define( 'ISO_639_3', 3 );

// Miscellaneous globals.
$wgBabelStandards = array(
	ISO_639_1 => ISO_639_3,
	ISO_639_3 => ISO_639_1,
);

// Configuration setttings.
$wgBabelUseLevelZeroCategory = false;
$wgBabelUseSimpleCategories  = false;
$wgBabelUseMainCategories    = true;
$wgBabelFavorStandard        = ISO_639_1;

// Create LanguageTools class.
$wgLanguageTools = new LanguageTools( $wgLanguageCodeFile );
$wgBabel         = new Babel( $wgLanguageTools            );