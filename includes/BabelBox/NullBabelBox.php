<?php
/**
 * Contains code for inner items which render as empty strings.
 *
 * @file
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel\BabelBox;

use ParserOutput;

/**
 * Class for inner items which render as empty strings.
 */
class NullBabelBox implements BabelBox {

	/**
	 * Return the babel box code.
	 *
	 * @return string Empty string
	 */
	public function render(): string {
		return '';
	}

	public function addCategories( ParserOutput $parserOutput ): void {
	}

}
