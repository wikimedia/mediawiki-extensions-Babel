<?php

namespace Babel\Tests;

use Babel;
use Language;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;

/**
 * @covers Babel
 *
 * @group Babel
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class BabelTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( [
			'wgContLang' => Language::factory( 'qqx' ),
		] );
		$this->insertPage( 'User:User-1', '[[Category:en]]' );
	}

	/**
	 * @return Parser
	 */
	private function getParser() {
		$options = new ParserOptions();
		$options->setIsPreview( true );

		$parser = $this->getMockBuilder( 'Parser' )
			->disableOriginalConstructor()
			->getMock();

		$parser->expects( $this->any() )
			->method( 'getOptions' )
			->will( $this->returnValue( $options ) );

		$parser->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( 'User:User-1' ) ) );

		$parser->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( new ParserOutput() ) );

		$parser->expects( $this->any() )
			->method( 'getDefaultSort' )
			->will( $this->returnValue( '' ) );

		return $parser;
	}

	/**
	 * @param int $expectedCount
	 * @param string $haystack
	 */
	private function assertBabelBoxCount( $expectedCount, $haystack ) {
		$this->assertSame( $expectedCount, substr_count( $haystack, '<div class="mw-babel-box' ) );
	}

	/**
	 * @param Parser $parser
	 * @param string $cat
	 * @param string $sortKey
	 */
	private function assertHasCategory( $parser, $cat, $sortKey ) {
		$cats = $parser->getOutput()->getCategories();
		$this->assertArrayHasKey( $cat, $cats );
		$this->assertSame( $sortKey, $cats[$cat] );
	}

	public function testRenderEmptyBox() {
		$wikiText = Babel::Render( $this->getParser(), '' );
		$this->assertSame(
			'{|style=" padding: (babel-box-cellpadding);  border-spacing: (babel-box-cellspacing);"'
			. ' class="mw-babel-wrapper"'
			. "\n"
			. '! class="mw-babel-header" | [[(babel-url)|(babel: User-1)]]'
			. "\n|-\n| \n|-\n"
			.  '! class="mw-babel-footer" | [[(babel-footer-url)|(babel-footer: User-1)]]'
			. "\n|}",
			$wikiText
		);
	}

	public function testRenderDefaultLevel() {
		$parser = $this->getParser();
		$wikiText = Babel::Render( $parser, 'en' );
		$this->assertBabelBoxCount( 1, $wikiText );
		$this->assertContains(
			'<div class="mw-babel-box mw-babel-box-N" dir="ltr">'
			. "\n"
			. '{|style=" padding: (babel-cellpadding);  border-spacing: (babel-cellspacing);"'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | This user has a [[:Category:en-N|native]] understanding of '
			. '[[:Category:en|English]].'
			. "\n|}\n"
			. '</div>',
			$wikiText
		);
		$this->assertHasCategory( $parser, 'en', 'N' );
		$this->assertHasCategory( $parser, 'en-N', '' );
	}

	public function testRenderCustomLevel() {
		$parser = $this->getParser();
		$wikiText = Babel::Render( $parser, 'EN-1', 'zh-Hant' );
		$this->assertBabelBoxCount( 2, $wikiText );
		$this->assertContains(
			'<div class="mw-babel-box mw-babel-box-1" dir="ltr">'
			. "\n"
			. '{|style=" padding: (babel-cellpadding);  border-spacing: (babel-cellspacing);"'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-1">-1</span>'
			. "\n"
			. '| dir="ltr" lang="en" | This user has [[:Category:en-1|basic]] knowledge of '
			. '[[:Category:en|English]].'
			. "\n|}\n"
			. '</div>',
			$wikiText
		);
		$this->assertHasCategory( $parser, 'en', '1' );
		$this->assertHasCategory( $parser, 'en-1', '' );
		$this->assertContains(
			'<div class="mw-babel-box mw-babel-box-N" dir="ltr">'
			. "\n"
			. '{|style=" padding: (babel-cellpadding);  border-spacing: (babel-cellspacing);"'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: zh-Hant)|zh-Hant]]'
			. '<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="zh-Hant" | This user has a [[:Category:zh-Hant-N|native]] '
			. 'understanding of [[:Category:zh-Hant|]].'
			. "\n|}\n"
			. '</div>',
			$wikiText
		);
		$this->assertHasCategory( $parser, 'zh-Hant', 'N' );
		$this->assertHasCategory( $parser, 'zh-Hant-N', '' );
	}

	public function testRenderPlain() {
		$parser = $this->getParser();
		$wikiText = Babel::Render( $parser, 'plain=1', 'en' );
		$this->assertSame(
			'<div class="mw-babel-box mw-babel-box-N" dir="ltr">'
			. "\n"
			. '{|style=" padding: (babel-cellpadding);  border-spacing: (babel-cellspacing);"'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | This user has a [[:Category:en-N|native]] understanding of '
			. '[[:Category:en|English]].'
			. "\n|}\n"
			. '</div>',
			$wikiText
		);
		$this->assertHasCategory( $parser, 'en', 'N' );
		$this->assertHasCategory( $parser, 'en-N', '' );
	}

	public function testRenderRedLink() {
		$wikiText = Babel::Render( $this->getParser(), 'redLink' );
		$this->assertBabelBoxCount( 0, $wikiText );
		$this->assertContains(
			'<div class="mw-babel-notabox" dir="ltr">[[(babel-template: redLink)]]</div>',
			$wikiText
		);
	}

	public function testRenderInvalidTitle() {
		$wikiText = Babel::Render( $this->getParser(), '<invalidTitle>' );
		$this->assertBabelBoxCount( 0, $wikiText );
		$this->assertContains(
			'<div class="mw-babel-notabox" dir="ltr">(babel-template: <invalidTitle>)</div>',
			$wikiText
		);
	}

	public function testGetUserLanguages() {
		$user = User::newFromName( 'User-1' );
		$languages = Babel::getUserLanguages( $user );
		$this->assertSame( [
			'en',
		], $languages );
	}

}
