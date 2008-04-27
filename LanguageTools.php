<?php

class LanguageTools {

	private $_cache;

	public function __construct( $file ) {

		/* Include language code file.
		 */
		include( $file );

		/* Push language codes into the code cache.
		 */
		$this->_cache = $codes;

	}

	/**
	 * Check if the specified code is a valid ISO 639-1 or ISO 639-3 language
	 * code.
	 *
	 * @param string $code Code to check.
	 * @return Boolean Whether or not the code is valid.
	 */
	public function checkCode( $code ) {

		/* Check if the specified code has a key in the codes array for each of the
		 * standards and return result.
		 */
		if( array_key_exists( strtolower( $code ), $this->_cache[ ISO_639_1 ] ) ) {
			return true;
		}
		if( array_key_exists( strtolower( $code ), $this->_cache[ ISO_639_3 ] ) ) {
			return true;
		}

	}

	/**
	 * Get the language code to use for a specific language, favouring a specific
	 * standard. For example, if the code given was 'eng' but the standard that is
	 * being favoured is ISO 639-1 then 'en' would be returned; if the ISO 639-3
	 * standard was being favoured then 'eng' would be returned.
	 *
	 * @param string $code Code to get language code for.
	 * @param constant $standard Favoured standard.
	 * @return String Correct code.
	 */
	public function getCode( $code, $standard ) {

		if( !$this->checkCode( $code ) ) {

			return false;

		}

		/* Check if it is listed in the favoured array.
		 */
		if( array_key_exists( $code, $this->_cache[ $standard ] ) ) {

			return $code;

		} else {

			/* It is not, try to find it in the opposite array.
			 */

			/* Get the standards array.
			 */
			global $wgBabelStandards;

			/* Find the opposite array.
			 */
			$opposite = $wgBabelStandards[ $standard ];

			/* Check the opposite array for the code.
			 */
			if( array_key_exists( $standard, $this->_cache[ $opposite ][ $code ] ) ) {

				return $this->_cache[ $opposite ][ $code ][ $standard ];

			} else {

				return $code;
			}
		}
	}

}