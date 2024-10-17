<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\CommunityConfigurationExample\Tests\Integration;

use MediaWiki\Extension\CommunityConfiguration\Tests\SchemaProviderTestCase;

/**
 * @coversNothing
 */
class BabelSchemaProviderTest extends SchemaProviderTestCase {

	protected function getExtensionName(): string {
		return 'Babel';
	}

	protected function getProviderId(): string {
		return 'Babel';
	}

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValue( 'BabelUseCommunityConfiguration', true );
	}

}
