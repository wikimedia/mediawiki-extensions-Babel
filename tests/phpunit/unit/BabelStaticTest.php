<?php
declare( strict_types = 1 );

namespace Babel\Tests\Unit;

use MediaWiki\Babel\Babel;
use MediaWiki\Babel\BabelStatic;
use Parser;

/**
 * @covers \MediaWiki\Babel\BabelStatic
 *
 * @group Babel
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BabelStaticTest extends \MediaWikiUnitTestCase {

	public function testOnParserFirstCallInit(): void {
		$parser = $this->getMockBuilder( Parser::class )
			->disableOriginalConstructor()
			->getMock();
		$parser->expects( $this->once() )
			->method( 'setFunctionHook' )
			->with( 'babel', [ Babel::class, 'Render' ] )
			->willReturn( true );

		BabelStatic::onParserFirstCallInit( $parser );
	}

}
