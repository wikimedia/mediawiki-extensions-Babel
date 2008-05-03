<?php

/**
 * Class to generate language codes from a tab file of ISO 639-3 codes.
 *
 * @addtogroup Extensions
 */

class GenerateLanguageCodes_ISO_639_3 extends GenerateLanguageCodes {

	final protected function parse( $file ) {

		/* Normalise file line-endings.
		 */
		$file = str_replace( "\r\n", "\n", $file );
		$file = str_replace( "\r", "\n", $file );

		/* Break file into rows for parsing.
		 */
		$lines = explode( "\n", $file );

		/* Discard first, header, row.
		 */
		unset( $lines[ 0 ] );

		/* Open array.
		 */
		$codes = array();

		/* Loop through lines.
		 */
		foreach( $lines as $line ) {

			/* Break line into tabs.
			 */
			$tabs = explode( "\t", $line );

			/* Add line to the output.
			 */
			$codes[ $tabs[ 0 ] ] = array();

			if( $tabs[ 3 ] != '' ) {
				$codes[ $tabs[ 0 ] ][ ISO_639_1 ] = $tabs[3];
			}

			$codes[ $tabs[ 0 ] ][ 'name_en' ] = $tabs[6];

		}

		return $codes;

	}

}