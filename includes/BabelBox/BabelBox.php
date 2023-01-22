<?php
/**
 * Contains interface code.
 *
 * @file
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel\BabelBox;

use ParserOutput;

/**
 * Interface for babel boxes.
 */
interface BabelBox {

	/**
	 * Return the babel box code.
	 *
	 * @return string HTML
	 */
	public function render(): string;

	/**
	 * Adds categories to the given ParserOutput.
	 *
	 * @param ParserOutput $output Parser output to add categories to
	 */
	public function addCategories( ParserOutput $output ): void;

}
