<?php

namespace MediaWiki\Babel\Maintenance;

use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\CommunityConfiguration\CommunityConfigurationServices;
use MediaWiki\Extension\CommunityConfiguration\Provider\ConfigurationProviderFactory;
use MediaWiki\Json\FormatJson;
use MediaWiki\Maintenance\LoggedUpdateMaintenance;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Status\StatusFormatter;
use MediaWiki\User\User;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class MigrateConfigToCommunity extends LoggedUpdateMaintenance {

	private StatusFormatter $statusFormatter;
	private ConfigurationProviderFactory $providerFactory;

	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'Babel' );
		$this->requireExtension( 'CommunityConfiguration' );

		$this->addOption( 'dry-run', 'Print the migration summary.' );
	}

	private function initServices(): void {
		$services = $this->getServiceContainer();
		$this->statusFormatter = $services->getFormatterFactory()
			->getStatusFormatter( RequestContext::getMain() );

		$ccServices = CommunityConfigurationServices::wrap( $this->getServiceContainer() );
		$this->providerFactory = $ccServices->getConfigurationProviderFactory();
	}

	protected function doDBUpdates() {
		$this->initServices();

		if ( !$this->providerFactory->isProviderSupported( 'Babel' ) ) {
			$this->fatalError(
				'The `Babel` CommunityConfiguration provider is not supported; ' .
				'maybe BabelUseCommunityConfiguration is not set to true?'
			);
		}

		$provider = $this->providerFactory->newProvider( 'Babel' );
		$dryRun = $this->hasOption( 'dry-run' );

		$config = $this->getConfig();
		$newConfig = (object)[
			'BabelCategoryNames' => (object)$config->get( 'BabelCategoryNames' ),
			'BabelMainCategory' => $config->get( 'BabelMainCategory' ),
			'BabelUseUserLanguage' => $config->get( 'BabelUseUserLanguage' ),
			'BabelAutoCreate' => $config->get( 'BabelAutoCreate' ),
			'BabelCategorizeNamespaces' => $config->get( 'BabelCategorizeNamespaces' ),
		];

		if ( $dryRun ) {
			$this->output( FormatJson::encode( $newConfig, true ) . PHP_EOL );
		} else {
			$status = $provider->storeValidConfiguration(
				$newConfig,
				new UltimateAuthority( User::newSystemUser( User::MAINTENANCE_SCRIPT_USER ) )
			);
			if ( $status->isOK() ) {
				$this->output( 'Done!' . PHP_EOL );
			} else {
				$this->error( 'Error when saving the new configuration' );
				$this->error( '== Error details' );
				$this->error( $this->statusFormatter->getWikiText( $status ) );
				return false;
			}
		}

		return !$dryRun;
	}

	/**
	 * @inheritDoc
	 */
	protected function getUpdateKey() {
		return 'BabelMigrateConfigToCommunity';
	}
}

$maintClass = MigrateConfigToCommunity::class;
require_once RUN_MAINTENANCE_IF_MAIN;
