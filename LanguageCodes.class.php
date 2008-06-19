<?php

/**
 * LanguageCodes class for Babel extension, deals with ISO 639-1 and ISO 639-3
 * code checking and ordering.
 *
 * @addtogroup Extensions
 */

class LanguageCodes {

	private $_codes;
	private $_order = array(
		ISO_639_1,
		ISO_639_3,
	);

	/**
	 * Class constructor.
	 * 
	 * @param array $file Array of files to load language codes from.
	 */
	public function __construct( $files ) {

		/* Load the codes from the passed file array.
		 */
		$this->_loadAll( $files );

	}

	/**
	 * Load the language codes from an array of standards to files into the
	 * language codes array.
	 *
	 * @param array $file Array of files to load language codes from.
	 */
	private function _loadAll( $files ) {

		/* Loop through all standards.
		 */
		foreach( $this->_order as $standard ) {

			/* Load file for the current standard.
			 */
			$this->_load( $standard, $files[ $standard ] );

		}

	}

	/**
	 * Load the language codes from a given file into the language codes array.
	 *
	 * @param const $standard Standard for the codes being loaded.
	 * @param string $file File to load language codes from.
	 */
	private function _load( $standard, $file ) {

		/* Include the codes file.
		 */
		include( $file );

		/* Push the array of codes into the class method.
		 */
		$this->_codes[ $standard ] = $codes;

	}

	/**
	 * Check if the specified code is a valid language code.
	 *
	 * @param string $code Code to check.
	 * @return boolean Whether or not the code is valid.
	 */
	public function check( $code ) {

		/* Check if the specified code has a key in the codes array for each of the
		 * standards and return result.
		 */
		foreach( $this->_order as $index ) {

			if( array_key_exists( strtolower( $code ), $this->_codes[ $index ] ) ) {
				return true;
			}

		}

	}

	/**
	 * Get the language code to use for a specific language, in the highest
	 * ordered standard possible.
	 *
	 * @param string $code Code to get language code for.
	 * @return string Correct code.
	 */
	public function get( $code ) {

		/* Loop through all the standards trying to find the language code
		 * specified.
		 */
		foreach( $this->_order as $standard1 ) {

			if( array_key_exists( strtolower( $code ), $this->_codes[ $standard1 ] ) ) {

				/* Loop through all the standards again to find the highest
				 * level alternate code.
				 */
				foreach( $this->_order as $standard2 ) {

					if( $standard1 == $standard2 ) {

							return $code;

					} elseif( array_key_exists( $standard2, $this->_codes[ $standard1 ][ $code ] ) ) {

							return $this->_codes[ $standard1 ][ $code ][ $standard2 ];

					}

				}

			}

		}

		/* Nothing found, return input.
		 */
		return $code;

	}

	/**
	 * Get the name of a language in a specific language (currently only eng
	 * supported until a index of ISO 639-1 is built with language names).
	 *
	 * @param string $code Code to get name for.
	 * @param string $lang Language to get name of code in.
	 * @return string Name of language in specified language.
	 */
	public function name( $code, $lang = 'eng' ) {

		$code = $this->get( $code );

		if( array_key_exists( $code, $this->_codes[ ISO_639_3 ] ) && array_key_exists( "name_$lang", $this->_codes[ ISO_639_3 ][ $code ] ) ) {
			return $this->_codes[ ISO_639_3 ][ $code ][ "name_$lang" ];
		}

		/* Nothing found, return input.
		 */
		return $code;

	}

}
