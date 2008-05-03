<?php

/**
 * Maintenance script for generating language codes files.
 *
 * @addtogroup Extensions
 */

/* Need bootstrap.
 */
require_once( dirname( __FILE__ ) . '/bootstrap.php' );

if( array_key_exists( 'standard', $options ) ) {

	switch( $options[ 'standard' ] ) {
		case 'ISO_639-3':
			echo "Generating language codes file for ISO 639-3.\n";
			$generateLanguageCodes = new GenerateLanguageCodes_ISO_639_3(
				dirname( __FILE__ ) . '/raw/ISO_639_3.tab',
				dirname( dirname( __FILE__ ) ) . '/codes/ISO_639-3.php'
			);
			echo "Language codes file for ISO 639-3 generated successfully.\n";
			break;
		default:
			echo "Invalid standard, aborting.\n";
			break;
	}

} else {

	echo <<<HEREDOC
Usage: php generateLanguageCodes.php [options]
    --help                Show this message

    --standard=<standard> Standard to use, choose from:
                              * ISO_639-3
HEREDOC;

}