<?php

namespace Babel\Tests;

use BabelLanguageCodes;
use PHPUnit_Framework_TestCase;

/**
 * @covers BabelLanguageCodes
 *
 * @group Babel
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class BabelLanguageCodesTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getCodeProvider
	 */
	public function testGetCode( $code, $expected ) {
		$this->assertSame( $expected, BabelLanguageCodes::getCode( $code ) );
	}

	public function getCodeProvider() {
		return array(
			array( 'invalidLanguageCode', false ),
			array( 'en', 'en' ),
			array( 'eng', 'en' ),
			array( 'en-gb', 'en-gb' ),
			array( 'de', 'de' ),
		);
	}

	/**
	 * @dataProvider getNameProvider
	 */
	public function testGetName( $code, $language, $expected ) {
		$this->assertSame( $expected, BabelLanguageCodes::getName( $code, $language ) );
	}

	public function getNameProvider() {
		return array(
			array( 'invalidLanguageCode', null, false ),
			array( 'en', null, 'English' ),
			array( 'en', 'en', 'English' ),
			array( 'eng', null, 'English' ),
			array( 'en-gb', null, 'British English' ),
			array( 'de', null, 'Deutsch' ),
		);
	}

}
