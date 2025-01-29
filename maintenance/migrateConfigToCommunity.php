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
		$this->addOption(
			'summary-note',
			'A note to be included in the edit summary (for example, a link to an announcement ' .
			'page or a bug tracker; use wikitext syntax for internal links)',
			false, true
		);
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
			'BabelCategoryNames' => (object)array_map(
				// Community configuration does not support multi-typed configs
				// An empty string is interpreted as "no category" (same as false). See T384941.
				static fn ( $categoryName ) => $categoryName !== false ? $categoryName : '',
				$config->get( 'BabelCategoryNames' )
			),
			// Community configuration does not support multi-typed configs
			// An empty string is interpreted as "no category" (same as false). See T384941.
			'BabelMainCategory' => $config->get( 'BabelMainCategory' ) !== false ?
				$config->get( 'BabelMainCategory' ) : '',
			'BabelUseUserLanguage' => $config->get( 'BabelUseUserLanguage' ),
			'BabelAutoCreate' => $config->get( 'BabelAutoCreate' ),
		];

		if ( $dryRun ) {
			$this->output( FormatJson::encode( $newConfig, true ) . PHP_EOL );
		} else {
			$summary = 'Migrating server configuration to an on-wiki JSON file';
			if ( $this->hasOption( 'summary-note' ) ) {
				$summary .= ' (' . $this->getOption( 'summary-note' ) . ')';
			}
			$status = $provider->storeValidConfiguration(
				$newConfig,
				new UltimateAuthority( User::newSystemUser( User::MAINTENANCE_SCRIPT_USER ) ),
				$summary
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
