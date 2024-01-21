<?php
declare( strict_types = 1 );

namespace Babel\Tests;

use LinkCacheTestTrait;
use MediaWiki\Babel\Babel;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOptions;
use ParserOutput;

/**
 * @covers \MediaWiki\Babel\Babel
 *
 * @group Babel
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class BabelTest extends MediaWikiIntegrationTestCase {
	use LinkCacheTestTrait;

	public function addDBDataOnce(): void {
		$services = MediaWikiServices::getInstance();
		$user = $services->getUserFactory()->newFromName( 'User-1' );
		$user->addToDatabase();
		$this->insertPage( 'User:User-1', '{{#babel:en-1|es-2|de|SIMPLE-1|zh-hant-3}}' );
	}

	protected function setUp(): void {
		parent::setUp();

		$this->setMwGlobals( [
			// Individual tests may change these
			'wgBabelAllowOverride' => false,
			'wgBabelCentralDb' => false,
			'wgCapitalLinks' => false,
			'wgLanguageCode' => 'qqx'
		] );
	}

	/**
	 * @param Title $title
	 * @return Parser
	 */
	private function getParser( Title $title ): Parser {
		$options = ParserOptions::newFromAnon();
		$options->setIsPreview( true );
		$output = new ParserOutput();

		$parser = $this->createMock( Parser::class );
		$parser->method( 'getOptions' )->willReturn( $options );
		$parser->method( 'getTitle' )->willReturn( $title );
		$parser->method( 'getOutput' )->willReturn( $output );
		return $parser;
	}

	/**
	 * @param int $expectedCount
	 * @param string $haystack
	 */
	private function assertBabelBoxCount( int $expectedCount, string $haystack ): void {
		$this->assertSame( $expectedCount, substr_count( $haystack, '<div class="mw-babel-box' ) );
	}

	/**
	 * @param Parser $parser
	 * @param string $cat
	 * @param string $sortKey
	 */
	private function assertHasCategory( Parser $parser, string $cat, string $sortKey ): void {
		$this->assertContains( $cat, $parser->getOutput()->getCategoryNames() );
		$this->assertSame( $sortKey, $parser->getOutput()->getCategorySortKey( $cat ) );
	}

	/**
	 * @param Parser $parser
	 * @param string $cat
	 */
	private function assertNotHasCategory( Parser $parser, string $cat ): void {
		$this->assertNotContains( $cat, $parser->getOutput()->getCategoryNames() );
	}

	public function testRenderEmptyBox(): void {
		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, '' );
		$this->assertSame(
			'{|class="mw-babel-wrapper"'
			. "\n"
			. '! class="mw-babel-header" | [[(babel-url)|(babel: User-1)]]'
			. "\n|-\n| \n|-\n"
			. '! class="mw-babel-footer" | [[(babel-footer-url)|(babel-footer: User-1)]]'
			. "\n|}",
			$wikiText
		);
	}

	/**
	 * Provides different page names, such as pages in the Category namespace.
	 */
	public static function providePageNames(): array {
		return [
			[ 'User:User-1' ],
			[ 'Category:X1' ],
		];
	}

	/**
	 * @dataProvider providePageNames
	 */
	public function testRenderDefaultLevel( string $pageName ): void {
		$parser = $this->getParser( Title::newFromText( $pageName ) );
		$wikiText = Babel::Render( $parser, 'en' );
		$this->assertBabelBoxCount( 1, $wikiText );
		$this->assertStringContainsString(
			'<div class="mw-babel-box mw-babel-box-N mw-babel-box-en" dir="ltr">'
			. "\n"
			. '{|'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | '
			. wfMessage( 'babel-N-n', ':Category:en-N', ':Category:en' )
				->inLanguage( 'en' )->text()
			. "\n|}\n"
			. '</div>',
			$wikiText
		);

		$this->assertHasCategory( $parser, 'en', 'N' );
		$this->assertHasCategory( $parser, 'en-N', '' );
	}

	/**
	 * @dataProvider providePageNames
	 */
	public function testRenderDefaultLevelNoCategory( string $pageName ): void {
		$this->setMwGlobals( [ 'wgBabelMainCategory' => false ] );

		$title = Title::newFromText( $pageName );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'en' );
		$this->assertBabelBoxCount( 1, $wikiText );
		$this->assertStringContainsString(
			'<div class="mw-babel-box mw-babel-box-N mw-babel-box-en" dir="ltr">'
			. "\n"
			. '{|'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | '
			. wfMessage( 'babel-N-n', ':Category:en-N', ':' . $title->getFullText() )
				->inLanguage( 'en' )->text()
			. "\n|}\n"
			. '</div>',
			$wikiText
		);

		$this->assertNotHasCategory( $parser, 'en' );
		$this->assertHasCategory( $parser, 'en-N', '' );
	}

	public function testRenderCustomLevel(): void {
		$parser = $this->getParser( Title::newFromText( 'User:User-1' ) );
		$wikiText = Babel::Render( $parser, 'EN-1', 'zh-Hant' );
		$this->assertBabelBoxCount( 2, $wikiText );
		$this->assertStringContainsString(
			'<div class="mw-babel-box mw-babel-box-1 mw-babel-box-en" dir="ltr">'
			. "\n"
			. '{|'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-1">-1</span>'
			. "\n"
			. '| dir="ltr" lang="en" | '
			. wfMessage( 'babel-1-n', ':Category:en-1', ':Category:en' )
				->inLanguage( 'en' )->text()
			. "\n|}\n"
			. '</div>',
			$wikiText
		);

		$this->assertHasCategory( $parser, 'en', '1' );
		$this->assertHasCategory( $parser, 'en-1', '' );

		$this->assertStringContainsString(
			'<div class="mw-babel-box mw-babel-box-N mw-babel-box-zh-Hant" dir="ltr">'
			. "\n"
			. '{|'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: zh-Hant)|zh-Hant]]'
			. '<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="zh-Hant" | '
			. wfMessage( 'babel-N-n', ':Category:zh-Hant-N', ':Category:zh-Hant' )
				->inLanguage( 'zh-hant' )->text()
			. "\n|}\n"
			. '</div>',
			$wikiText
		);

		$this->assertHasCategory( $parser, 'zh-Hant', 'N' );
		$this->assertHasCategory( $parser, 'zh-Hant-N', '' );
	}

	public function testRenderPlain(): void {
		$parser = $this->getParser( Title::newFromText( 'User:User-1' ) );
		$wikiText = Babel::Render( $parser, 'plain=1', 'en' );
		$this->assertSame(
			'<div class="mw-babel-box mw-babel-box-N mw-babel-box-en" dir="ltr">'
			. "\n"
			. '{|'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | '
			. wfMessage( 'babel-N-n', ':Category:en-N', ':Category:en' )
				->inLanguage( 'en' )->text()
			. "\n|}\n"
			. '</div>',
			$wikiText
		);

		$this->assertHasCategory( $parser, 'en', 'N' );
		$this->assertHasCategory( $parser, 'en-N', '' );
	}

	public function testRenderRedLink(): void {
		$parser = $this->getParser( Title::newFromText( 'User:User-1' ) );
		$wikiText = Babel::Render( $parser, 'redLink' );
		$this->assertBabelBoxCount( 0, $wikiText );
		$this->assertStringContainsString(
			'<div class="mw-babel-notabox" dir="ltr">[[(babel-template: redLink)]]</div>',
			$wikiText
		);
	}

	public function testRenderInvalidTitle(): void {
		$parser = $this->getParser( Title::newFromText( 'User:User-1' ) );
		$wikiText = Babel::Render( $parser, '<invalidTitle>' );
		$this->assertBabelBoxCount( 0, $wikiText );
		$this->assertStringContainsString(
			'<div class="mw-babel-notabox" dir="ltr">(babel-template: <invalidTitle>)</div>',
			$wikiText
		);
	}

	public function testRenderNoSkillNoCategory(): void {
		$parser = $this->getParser( Title::newFromText( 'User:User-1' ) );
		$wikiText = Babel::Render( $parser, 'en-0' );
		$this->assertNotHasCategory( $parser, 'en' );
	}

	public function testGetUserLanguages(): void {
		$mwInstance = MediaWikiServices::getInstance();
		$userIdentity = $mwInstance->getUserIdentityLookup()->getUserIdentityByName( 'User-1' );

		// Sorted by language code
		$this->assertSame( [
			'de',
			'en',
			'es',
			'simple',
			'zh-Hant',
		], Babel::getUserLanguages( $userIdentity ) );

		// Filter based on level, sorted by language level
		$this->assertSame( [
			'de',
			'zh-Hant',
			'es',
		], Babel::getUserLanguages( $userIdentity, '2' ) );

		// Filter based on level, sorted by language level
		$this->assertSame( [
			'de',
			'zh-Hant',
		], Babel::getUserLanguages( $userIdentity, '3' ) );

		// Non-numerical level
		$this->assertSame( [
			'de',
		], Babel::getUserLanguages( $userIdentity, 'N' ) );
	}

	public function testGetUserLanguageInfo(): void {
		$mwInstance = MediaWikiServices::getInstance();
		$userIdentity = $mwInstance->getUserIdentityLookup()->getUserIdentityByName( 'User-1' );

		$languages = Babel::getUserLanguageInfo( $userIdentity );
		// Sorted by language code
		$this->assertSame( [
			'de' => 'N',
			'en' => '1',
			'es' => '2',
			'simple' => '1',
			'zh-Hant' => '3',
		], $languages );
	}

	public function testCategoryOverride(): void {
		$this->setMwGlobals( 'wgBabelAllowOverride', true );
		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'en-1' );
		$this->assertStringContainsString( "babel-category-override:_en-1,_en,_1", $wikiText );
		$this->assertStringContainsString( "babel-category-override:_en,_en,_", $wikiText );
		$this->assertHasCategory( $parser, '(babel-category-override:_en,_en,_)', '1' );
		$this->assertHasCategory( $parser, '(babel-category-override:_en-1,_en,_1)', '' );
		$this->assertArrayHasKey( "Babel-category-override", $parser->getOutput()->getTemplates()[NS_MEDIAWIKI] );
		$this->assertFalse( Title::makeTitle( NS_CATEGORY, "(babel-category-override:_en,_en,_)" )->exists() );
		$this->assertNull( $parser->getOutput()->getExtensionData( 'babel-tocreate' ) );
	}

	public static function provideInvalidTitles(): array {
		return [ [ "<><><" ], [ "" ], [ "foo|bar" ] ];
	}

	/**
	 * @dataProvider provideInvalidTitles
	 */
	public function testFailedOverride( $invalidTitle ): void {
		$this->setMwGlobals( [
			'wgBabelAllowOverride' => true,
			'wgLanguageCode' => 'en'
		] );
		$this->getServiceContainer()->getService( "MessageCache" )->enable();
		$this->insertPage( "MediaWiki:Babel-category-override", $invalidTitle );

		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'en-1' );
		$this->assertStringNotContainsString( $wikiText, "Category:en" );
		$this->assertSame( [], $parser->getOutput()->getCategoryNames() );
	}

	public function testOverrideMessage(): void {
		$this->setMwGlobals( 'wgLanguageCode', 'en' );
		$this->getServiceContainer()->getService( "MessageCache" )->enable();
		$this->insertPage( "MediaWiki:Babel-1-n", "Overridden message" );
		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'zxx-1' );
		$this->assertStringNotContainsString( "English", $wikiText );
	}

	public function testAutoCreationEnabled(): void {
		$this->setMwGlobals( 'wgBabelAutoCreate', true );
		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'zxx-1' );
		$this->assertNotNull( $parser->getOutput()->getExtensionData( 'babel-tocreate' ) );
	}

	public function testAutoCreationDisabled(): void {
		$this->setMwGlobals( 'wgBabelAutoCreate', false );
		$title = Title::newFromText( 'User:User-1' );
		$parser = $this->getParser( $title );
		$wikiText = Babel::Render( $parser, 'zxx-1' );
		$this->assertNull( $parser->getOutput()->getExtensionData( 'babel-tocreate' ) );
	}
}
