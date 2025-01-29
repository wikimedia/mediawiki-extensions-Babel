<?php

namespace Babel\Tests\Integration;

use MediaWiki\Babel\BabelServices;
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

	public static function provideSpecificConfig() {
		return [
			'false BabelCategoryNames' => [
				// expected
				[
					'0' => '0',
					'1' => 'User %code%-1',
					'2' => '',
					'3' => 'User %code%-3',
					'4' => 'User %code%-4',
					'5' => 'User %code%-5',
					'N' => 'User %code%-N',
				],
				// config
				'BabelCategoryNames',
				// original value
				[
					// Assert falsy string is migrated correctly
					'0' => '0',
					'1' => 'User %code%-1',
					'2' => false,
					'3' => 'User %code%-3',
					'4' => 'User %code%-4',
					'5' => 'User %code%-5',
					'N' => 'User %code%-N',
				],
			],
			'false BabelMainCategory' => [
				// expected
				'',
				// config
				'BabelMainCategory',
				// original value
				false,
			],
		];
	}

	/**
	 * @param mixed $expected Value to expect in CommunityConfiguration
	 * @param string $variable Config variable to set
	 * @param mixed $value Value to set in server configuration
	 * @dataProvider provideSpecificConfig
	 */
	public function testWithSpecificConfig( $expected, string $variable, $value ) {
		$this->overrideConfigValue( $variable, $value );

		$status = $this->maintenance->execute();
		$this->assertTrue( $status, 'migrateConfigToCommunity.php failed' );

		$this->assertSame(
			$expected,
			BabelServices::wrap( $this->getServiceContainer() )
				->getConfig()
				->get( $variable )
		);
	}
}
