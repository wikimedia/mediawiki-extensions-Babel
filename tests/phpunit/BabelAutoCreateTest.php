<?php
declare( strict_types = 1 );

namespace Babel\Tests;

use MediaWiki\Babel\BabelAutoCreate;
use MediaWiki\MainConfigNames;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;

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

		$this->overrideConfigValue( MainConfigNames::LanguageCode, 'qqx' );
	}

	/**
	 * @dataProvider getCategoryTextProvider
	 */
	public function testGetCategoryText(
		string $code,
		?string $level,
		?string $parent,
		string $expected
	): void {
		$this->assertEquals( $expected, BabelAutoCreate::getCategoryText( $code, $level, $parent ) );
	}

	public static function getCategoryTextProvider(): array {
		return [
			[
				'en', null, 'top',
				'(babel-autocreate-text-main: English, en, [[Category:top|en]])'
			],
			[
				'en', 'level-2', 'en',
				'(babel-autocreate-text-levels: level-2, English, en, [[Category:en|level-2]])'
			],
			[
				'en', 'level-2', null,
				'(babel-autocreate-text-levels: level-2, English, en, )'
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
