<?php

class LanguageCodes {

	private $_codes;
	private $_order = array(
		ISO_639_1,
		ISO_639_3,
	);

	/**
	 * Class constructor.
	 * 
	 * @param string $file File to load language codes from.
	 */
	public function __construct( $file ) {

		/* Load the codes from the passed file.
		 */
		$this->_load( $file );

	}

	/**
	 * Load the language codes from a given file into the language codes array.
	 *
	 * @param string $file File to load language codes from.
	 */
	private function _load( $file ) {

		/* Include the codes file.
		 */
		include( $file );

		/* Push the array of codes into the class method.
		 */
		$this->_codes = $codes;

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
		 * listed.
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

}
		