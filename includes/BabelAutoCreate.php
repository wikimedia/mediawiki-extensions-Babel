<?php
/**
 * Code for automatic creation of categories.
 *
 * @file
 * @author Robert Leverington
 * @author Robin Pepermans
 * @author Niklas Laxström
 * @author Brian Wolff
 * @author Purodha Blissenbach
 * @author Sam Reed
 * @author Siebrand Mazeland
 * @author Winston Sung
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use ContentHandler;
use DeferredUpdates;
use MediaWiki\MediaWikiServices;
use Title;
use User;

/**
 * Class for automatic creation of Babel category pages.
 */
class BabelAutoCreate {
	public const MSG_USERNAME = 'babel-autocreate-user';

	/**
	 * Create category.
	 *
	 * @param string $category Name of category to create.
	 * @param string $text Text to use when creating the category.
	 */
	public static function create( string $category, string $text ): void {
		$category = strip_tags( $category );
		$title = Title::makeTitleSafe( NS_CATEGORY, $category );
		# T170654: We need to check whether non-existing category page in one language variant actually
		#  exists in another language variant when a language supports multiple language variants.
		MediaWikiServices::getInstance()->getLanguageConverterFactory()->getLanguageConverter()
			->findVariantLink( $category, $title, true );
		DeferredUpdates::addCallableUpdate( function () use ( $title, $text ) {
			// Extra exists check here in case the category was created while this code was running
			if ( $title === null || $title->exists() ) {
				return;
			}

			$user = self::user();
			# Do not add a message if the username is invalid or if the account that adds it, is blocked
			if ( !$user || $user->getBlock() ) {
				return;
			}

			if ( !MediaWikiServices::getInstance()->getPermissionManager()
				->quickUserCan( 'create', $user, $title )
			) {
				# The Babel AutoCreate account is not allowed to create the page
				return;
			}

			$url = wfMessage( 'babel-url' )->inContentLanguage()->plain();
			$article = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );

			$content = ContentHandler::makeContent( $text, $title );
			$editSummary = wfMessage( 'babel-autocreate-reason', $url )->inContentLanguage()->text();
			$article->doUserEditContent(
				$content,
				$user,
				$editSummary,
				EDIT_FORCE_BOT
			);
		} );
	}

	/**
	 * Get user object.
	 *
	 * @return User|null User object for autocreate user, null if invalid.
	 */
	public static function user(): ?User {
		$userName = wfMessage( self::MSG_USERNAME )->inContentLanguage()->plain();
		return User::newSystemUser( $userName, [ 'steal' => true ] );
	}

	/**
	 * Returns the text to use when creating a babel category with the given code and level
	 * @param string $code Code of language that the category is for.
	 * @param string|null $level Level that the category is for.
	 * @return string The text to use to create the category.
	 */
	public static function getCategoryText( string $code, ?string $level ): string {
		global $wgLanguageCode;
		$language = BabelLanguageCodes::getName( $code, $wgLanguageCode );
		$params = [ $language, $code ];
		if ( $level === null ) {
			$text = wfMessage( 'babel-autocreate-text-main', $params )->inContentLanguage()->plain();
		} else {
			array_unshift( $params, $level );
			$text = wfMessage( 'babel-autocreate-text-levels', $params )->inContentLanguage()->plain();
		}
		return $text;
	}
}
