<?php
/**
 * Class for automatic rcreate of Babel category pages.
 *
 * @ingroup Extensions
 */
class BabelAutoCreate {

	/**
	 * @var User
	 */
	static $user = false;

	/**
	 * Abort user creation if the username is that of the autocreation username.
	 */
	public static function RegisterAbort( User $user, &$message ) {
		$message = wfMsg( 'babel-autocreate-abort', wfMsg( 'babel-url' ) );
		return $user->getName() !== wfMsgForContent( 'babel-autocreate-user' );
	}

	/**
	 * Create category.
	 *
	 * @param $category String: Name of category to create.
	 * @param $language String: Name of language that the category is for.
	 * @param $level String: Level that the category is for.
	 */
	public static function create( $category, $language, $level = null ) {
		$category = strip_tags( $category );
		$title = Title::newFromText( $category, NS_CATEGORY );
		if ( $title === null || $title->exists() ) {
			return;
		}
		if ( $level === null ) {
			$text = wfMsgForContent( 'babel-autocreate-text-main', $language );
		} else {
			$text = wfMsgForContent( 'babel-autocreate-text-levels', $level, $language );
		}
		$article = new Article( $title );
		$article->doEdit(
			$text,
			wfMsgForContent( 'babel-autocreate-reason', wfMsgForContent( 'babel-url' ) ),
			EDIT_FORCE_BOT,
			false,
			self::user()
		);
	}

	/**
	 * Get user object.
	 *
	 * @return User object: User object for autocreate user.
	 */
	public static function user() {
		if ( !self::$user ) {
			self::$user = User::newFromName( wfMsgForContent( 'babel-autocreate-user' ), false );
			if ( !self::$user->isLoggedIn() ) {
				self::$user->addToDatabase();
			}
		}
		return self::$user;
	}
}
