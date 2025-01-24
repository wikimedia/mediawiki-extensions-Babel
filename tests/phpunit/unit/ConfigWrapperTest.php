<?php

namespace Babel\Tests\Unit;

use MediaWiki\Babel\Config\ConfigWrapper;
use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
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
			$configReaderMock,
			$this->createNoOpMock( Config::class )
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

		$wrapper = new ConfigWrapper( $configReaderMock, $this->createNoOpMock( Config::class ) );
		$this->assertFalse( $wrapper->has( 'BabelConfig' ) );
	}

	public static function provideExcludedConfigs() {
		foreach ( ConfigWrapper::SERVER_SIDE_CONFIGS as $config ) {
			yield [ $config ];
		}
	}

	/**
	 * @dataProvider provideExcludedConfigs
	 */
	public function testExcludedConfigs( string $configName ) {
		$wrapper = new ConfigWrapper(
			$this->createNoOpMock( MediaWikiConfigReader::class ),
			new HashConfig( [ $configName => 'foo' ] )
		);
		$this->assertTrue( $wrapper->has( $configName ) );
		$this->assertSame( 'foo', $wrapper->get( $configName ) );
	}
}
