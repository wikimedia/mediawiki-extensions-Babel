<?php

namespace Babel\Tests\Unit;

use MediaWiki\Babel\Config\ConfigWrapper;
use MediaWiki\Extension\CommunityConfiguration\Access\MediaWikiConfigReader;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Babel\Config\ConfigWrapper
 */
class ConfigWrapperTest extends MediaWikiUnitTestCase {

	public function testReturnsArray() {
		$configReaderMock = $this->createNoOpMock( MediaWikiConfigReader::class, [ 'get' ] );
		$configReaderMock->expects( $this->once() )
			->method( 'get' )
			->with( 'BabelConfig' )
			->willReturn( (object)[ 'Number' => 42 ] );
		$wrapper = new ConfigWrapper(
			$configReaderMock
		);

		$this->assertSame(
			[ 'Number' => 42 ],
			$wrapper->get( 'BabelConfig' )
		);
	}

	public function testRelaysHas() {
		$configReaderMock = $this->createNoOpMock( MediaWikiConfigReader::class, [ 'has' ] );
		$configReaderMock->expects( $this->once() )
			->method( 'has' )
			->with( 'BabelConfig' )
			->willReturn( false );

		$wrapper = new ConfigWrapper( $configReaderMock );
		$this->assertFalse( $wrapper->has( 'BabelConfig' ) );
	}
}
