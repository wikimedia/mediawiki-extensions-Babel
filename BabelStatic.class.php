<?php

/**
 * Static functions related to Babel.
 *
 * @addtogroup Extensions
 */

class BabelStatic {

	/**
	 * Registers the parser function hook.
	 * 
	 * @return true
	 */
	public static function Setup() {

		/* Register the hook within the parser object.
		 */
		global $wgParser;
		$wgParser->setFunctionHook( 'babel', 'BabelStatic::Render' );

		/* Return true to ensure processing is continued and an exception is not
		 * generated.
		 */
		return true;

	}

	/**
	 * Registers the parser function magic word.
	 * 
	 * @param array $magicWords The associative array of magic words on the
	 *                          wiki for adding too.
	 * @param string $langCode Content language code of the wiki.
	 * @return true
	 */
	public static function Magic( array $magicWords, $langCode ) {

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
	 * Return Babel tower, initializing the Babel object if necessery,
	 *
	 * @param parser $parser Parser object.
	 * @return string Babel tower.
	 */
	public static function Render( $parser ) {

		/* Get the location of the language codes file.
		 */
		global $wgLanguageCodesFiles;

		/* Grab the Babel object.
		 */
		global $wgBabel;

		/* Initialize Babel object if not already initialized.
		 */
		if( !is_object( $wgBabel ) ) {

			$wgBabel = new Babel( $wgLanguageCodesFiles );

		}

		/* Get arguments passed to this function.
		 */
		$args = func_get_args();

		/* Render the Babel tower and return.
		 */
		return call_user_func_array( array( $wgBabel, 'render' ), $args );

	}

}