<?php
/**
 * txt2php: Converts the text file of ISO codes to a PHP static array definition.
 *
 * Usage: php txt2php.php
 */

namespace MediaWiki\Babel;

use MediaWiki\Maintenance\Maintenance;
use Wikimedia\StaticArrayWriter;

require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . "/maintenance/Maintenance.php"
	: __DIR__ . '/../../../maintenance/Maintenance.php';

// phpcs:ignore MediaWiki.Files.ClassMatchesFilename.WrongCase
class TXT2PHP extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Converts ISO code text file to PHP static array' );
	}

	public function execute() {
		$dir = __DIR__;

		$names = [];
		$codes = [];
		$fr = fopen( "$dir/codes.txt", 'r' );

		while ( true ) {
			$line = fgets( $fr );
			if ( !$line ) {
				break;
			}

			// Format is code1 code2 "language name"
			$line = explode( ' ', $line, 3 );
			$iso1 = trim( $line[0] );
			$iso3 = trim( $line[1] );
			// Strip quotes
			$name = substr( trim( $line[2] ), 1, -1 );
			if ( $iso1 !== '-' ) {
				$codes[ $iso1 ] = $iso1;
				if ( $iso3 !== '-' ) {
					$codes[ $iso3 ] = $iso1;
				}
				$names[ $iso1 ] = $name;
				$names[ $iso3 ] = $name;
			} elseif ( $iso3 !== '-' ) {
				$codes[ $iso3 ] = $iso3;
				$names[ $iso3 ] = $name;
			}
		}

		fclose( $fr );

		$writer = new StaticArrayWriter();
		$header = 'This file is generated by txt2php.php. Do not edit it directly.';
		$code = $writer->create( $names, $header );
		file_put_contents( "$dir/names.php", $code );

		$code = $writer->create( $codes, $header );
		file_put_contents( "$dir/codes.php", $code );
	}
}

$maintClass = TXT2PHP::class;
require_once RUN_MAINTENANCE_IF_MAIN;
