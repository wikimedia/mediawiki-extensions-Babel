<?php

namespace Babel\Tests\Integration;

use MediaWiki\Babel\Maintenance\MigrateConfigToCommunity;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;

/**
 * @group Database
 * @covers \MediaWiki\Babel\Maintenance\MigrateConfigToCommunity
 */
class MigrateConfigToCommunityTest extends MaintenanceBaseTestCase {

	protected function setUp(): void {
		parent::setUp();
		$this->overrideConfigValue( 'BabelUseCommunityConfiguration', true );
	}

	protected function getMaintenanceClass() {
		return MigrateConfigToCommunity::class;
	}

	public function testWithDefaultConfig() {
		$status = $this->maintenance->execute();
		$this->assertTrue( $status, 'migrateConfigToCommunity.php failed' );
	}
}
