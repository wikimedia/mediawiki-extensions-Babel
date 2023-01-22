<?php
declare( strict_types = 1 );

namespace Babel\Tests;

use MediaWiki\Babel\BabelAutoCreate;
use MediaWikiIntegrationTestCase;
use Title;
use User;

/**
 * @covers \MediaWiki\Babel\BabelAutoCreate
 *
 * @group Babel
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BabelAutoCreateTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setContentLang( 'qqx' );
	}

	public function testOnUserGetReservedNames(): void {
		$names = [];
		$this->assertSame( [], $names, 'Precondition' );

		$this->assertTrue( BabelAutoCreate::onUserGetReservedNames( $names ) );
		$this->assertSame( [ 'msg:babel-autocreate-user' ], $names );
	}

	/**
	 * @dataProvider getCategoryTextProvider
	 */
	public function testGetCategoryText(
		string $code,
		?string $level,
		string $expected
	): void {
		$this->assertEquals( BabelAutoCreate::getCategoryText( $code, $level ), $expected );
	}

	public function getCategoryTextProvider(): array {
		return [
			[
				'en', null,
				'(babel-autocreate-text-main: English, en)'
			],
			[
				'en', 'level-2',
				'(babel-autocreate-text-levels: level-2, English, en)'
			],
		];
	}

	public function testUser(): void {
		$user = BabelAutoCreate::user();
		$this->assertInstanceOf( User::class, $user );
	}

	public function testCreate() {
		BabelAutoCreate::create( 'categoryname', 'category text' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle(
			Title::newFromText( 'Category:categoryname' )
		);
		$this->assertTrue( $page->exists() );
		$this->assertSame( 'category text', $page->getContent()->getText() );
	}

}
