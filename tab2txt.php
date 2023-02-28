<?php
/**
 * tab2txt: Converts the original tabulated data file of ISO codes to a three
 * column text file (ISO 639-1, ISO 639-3, Natural Name).
 *
 * Usage: <tab file> | php tab2txt.php > codes.txt
 */

namespace MediaWiki\Babel;

use Maintenance;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . "/maintenance/Maintenance.php"
	: __DIR__ . '/../../../maintenance/Maintenance.php';

// phpcs:ignore MediaWiki.Files.ClassMatchesFilename.WrongCase
class TAB2TXT extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Converts tabulated data file to text file' );
	}

	public function execute() {
		$fr = fopen( 'php://stdin', 'r' );
		$fw = fopen( 'php://stdout', 'w' );

		// Read and discard header line.
		fgets( $fr );

		while ( true ) {
			$line = fgets( $fr );
			if ( !$line ) {
				break;
			}

			$line = explode( "\t", $line );
			$iso1 = trim( $line[3] );
			if ( $iso1 === '' ) {
				$iso1 = '-';
			}
			$iso3 = trim( $line[0] );
			$name = $line[6];
			fwrite( $fw, "$iso1 $iso3 \"$name\"\n" );
		}
		fclose( $fr );
		fclose( $fw );
	}
}

$maintClass = TAB2TXT::class;
require_once RUN_MAINTENANCE_IF_MAIN;
