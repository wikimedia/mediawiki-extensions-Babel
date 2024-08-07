<?php

namespace MediaWiki\Babel\Config;

use MediaWiki\Extension\CommunityConfiguration\Schema\JsonSchema;
use MediaWiki\Extension\CommunityConfiguration\Schemas\MediaWiki\MediaWikiDefinitions;

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
class BabelSchema extends JsonSchema {

	/** @var string[] Array of recognised language knowledge levels. */
	private const LANGUAGE_LEVELS = [ '0', '1', '2', '3', '4', '5', 'N' ];

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

	public const BabelDefaultLevel = [
		self::TYPE => self::TYPE_STRING,
		self::ENUM => self::LANGUAGE_LEVELS,
		self::DEFAULT => 'N',
	];

	public const BabelUseUserLanguage = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => false,
	];

	public const BabelCategorizeNamespaces = [
		self::REF => [
			'class' => MediaWikiDefinitions::class, 'field' => 'Namespaces'
		]
	];

	public const BabelAutoCreate = [
		self::TYPE => self::TYPE_BOOLEAN,
		self::DEFAULT => true,
	];
}
