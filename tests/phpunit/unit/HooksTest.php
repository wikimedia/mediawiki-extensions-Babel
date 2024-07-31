<?php
declare( strict_types = 1 );

namespace Babel\Tests\Unit;

use HashConfig;
use MediaWiki\Babel\Babel;
use MediaWiki\Babel\BabelAutoCreate;
use MediaWiki\Babel\Hooks;
use MediaWiki\User\CentralId\CentralIdLookupFactory;
use MediaWiki\User\UserIdentityLookup;
use MediaWikiUnitTestCase;
use Parser;
use WANObjectCache;

/**
 * @covers \MediaWiki\Babel\Hooks
 *
 * @group Babel
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class HooksTest extends MediaWikiUnitTestCase {

	private function newInstance() {
		return new Hooks(
			new HashConfig,
			$this->createMock( UserIdentityLookup::class ),
			$this->createMock( CentralIdLookupFactory::class ),
			$this->createMock( WANObjectCache::class )
		);
	}

	public function testOnParserFirstCallInit(): void {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
		$parser->expects( $this->once() )
			->method( 'setFunctionHook' )
			->with( 'babel', [ Babel::class, 'Render' ] )
			->willReturn( true );

		$this->newInstance()->onParserFirstCallInit( $parser );
	}

	public function testOnUserGetReservedNames(): void {
		$names = [];
		$this->assertSame( [], $names, 'Precondition' );

		$this->newInstance()->onUserGetReservedNames( $names );
		$this->assertSame( [ 'msg:' . BabelAutoCreate::MSG_USERNAME ], $names );
	}

}
