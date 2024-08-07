<?php
declare( strict_types = 1 );

namespace Babel\Tests\Unit;

use HashConfig;
use MediaWiki\Babel\Babel;
use MediaWiki\Babel\BabelAutoCreate;
use MediaWiki\Babel\Hooks;
use MediaWikiUnitTestCase;
use Parser;

/**
 * @covers \MediaWiki\Babel\Hooks
 *
 * @group Babel
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class HooksTest extends MediaWikiUnitTestCase {

	public function testOnParserFirstCallInit(): void {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
		$parser->expects( $this->once() )
			->method( 'setFunctionHook' )
			->with( 'babel', [ Babel::class, 'Render' ] )
			->willReturn( true );

		( new Hooks( new HashConfig ) )->onParserFirstCallInit( $parser );
	}

	public function testOnUserGetReservedNames(): void {
		$names = [];
		$this->assertSame( [], $names, 'Precondition' );

		( new Hooks( new HashConfig ) )->onUserGetReservedNames( $names );
		$this->assertSame( [ 'msg:' . BabelAutoCreate::MSG_USERNAME ], $names );
	}

}
