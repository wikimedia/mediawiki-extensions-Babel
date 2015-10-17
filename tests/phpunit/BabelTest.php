<?php

namespace Babel\Tests;

use Babel;
use Language;
use MediaWikiTestCase;
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

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'qqx' ),
		) );
		$this->insertPage( 'User:User-1', '[[Category:en]]' );
	}

	public function testRender() {
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

		$wikiText = Babel::Render( $parser, 'en' );
		$this->assertSame(
			'{|style=" padding: (babel-box-cellpadding);  border-spacing: (babel-box-cellspacing);" class="mw-babel-wrapper"'
			. "\n"
			. '! class="mw-babel-header" | [[(babel-url)|(babel: User-1)]]'
			. "\n|-\n"
			. '| <div class="mw-babel-box mw-babel-box-N" dir="ltr">'
			. "\n"
			. '{|style=" padding: (babel-cellpadding);  border-spacing: (babel-cellspacing);"'
			. "\n"
			. '! dir="ltr" | [[(babel-portal: en)|en]]<span class="mw-babel-box-level-N">-N</span>'
			. "\n"
			. '| dir="ltr" lang="en" | This user has a [[:Category:en-N|native]] understanding of [[:Category:en|English]].'
			. "\n|}\n"
			. '</div>[[Category:en|N]][[Category:en-N]]'
			. "\n|-\n"
			.  '! class="mw-babel-footer" | [[(babel-footer-url)|(babel-footer: User-1)]]'
			. "\n|}",
			$wikiText
		);
	}

	public function testGetUserLanguages() {
		$user = User::newFromName( 'User-1' );
		$languages = Babel::getUserLanguages( $user );
		$this->assertSame( array(
			'en',
		), $languages );
	}

}
