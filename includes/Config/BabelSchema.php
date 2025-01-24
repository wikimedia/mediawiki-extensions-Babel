<?php

namespace MediaWiki\Babel\Config;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class BabelSchema extends JsonSchema {
	public const VERSION = '1.0.0';

	public const BabelCategoryNames = [
		self::TYPE => self::TYPE_OBJECT,
		self::PROPERTIES => [
			'0' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-0',
			],
			'1' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-1',
			],
			'2' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-2',
			],
			'3' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-3',
			],
			'4' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-4',
			],
			'5' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-5',
			],
			'N' => [
				self::TYPE => self::TYPE_STRING,
				self::DEFAULT => '%code%-N',
			],
		]
	];

	public const BabelMainCategory = [
		self::TYPE => self::TYPE_STRING,
		self::DEFAULT => 'User %code%',
	];

	public const BabelUseUserLanguage = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => false,
	];

	public const BabelAutoCreate = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => true,
	];
}
