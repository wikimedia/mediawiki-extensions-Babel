<?php
declare( strict_types = 1 );

namespace Babel\Tests;

use MediaWiki\Babel\BabelLanguageCodes;
use MediaWiki\Language\LanguageCode;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Babel\BabelLanguageCodes
 *
 * @group Babel
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BabelLanguageCodesTest extends TestCase {

	/**
	 * @dataProvider getCodeProvider
	 */
	public function testGetCode( string $code, $expected ): void {
		$this->assertSame( $expected, BabelLanguageCodes::getCode( $code ) );
	}

	public static function getCodeProvider(): array {
		$testData = [
			[ 'invalidLanguageCode', false ],
			[ 'en', 'en' ],
			[ 'eng', 'en' ],
			[ 'en-gb', 'en-gb' ],
			[ 'de', 'de' ],
			[ 'be-x-old', 'be-tarask' ],
			// Make sure this special case gets handled properly, instead of returning an unrecognized code
			[ 'en-x-rtl', false ]
		];
		// True BCP 47 normalization was added in MW 1.32
		if ( LanguageCode::bcp47( 'simple' ) === 'en-simple' ) {
			// ensure BCP 47-compliant codes are mapped to MediaWiki's
			// nonstandard internal codes
			$testData = array_merge( $testData, [
				[ 'en-simple', 'simple' ],
				[ 'cbk', 'cbk-zam' ],
			] );
		}
		return $testData;
	}

	/**
	 * @dataProvider getNameProvider
	 */
	public function testGetName( string $code, ?string $language, $expected ): void {
		$this->assertSame( $expected, BabelLanguageCodes::getName( $code, $language ) );
	}

	public static function getNameProvider(): array {
		return [
			[ 'invalidLanguageCode', null, false ],
			[ 'en', null, 'English' ],
			[ 'en', 'en', 'English' ],
			[ 'eng', null, 'English' ],
			[ 'en-gb', null, 'British English' ],
			[ 'de', null, 'Deutsch' ],
			[ 'aaq', null, 'Eastern Abnaki' ],
		];
	}

	/**
	 * @dataProvider getCategoryCodeProvider
	 */
	public function testGetCategoryCode( string $code, string $expected ): void {
		$this->assertSame( $expected, BabelLanguageCodes::getCategoryCode( $code ) );
	}

	public static function getCategoryCodeProvider(): array {
		return [
			[ 'en', 'en' ],
			[ 'de', 'de' ],
			[ 'simple', 'simple' ],
			[ 'zh-hant', 'zh-Hant' ],
		];
	}

}
