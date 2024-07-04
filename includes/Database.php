<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

declare( strict_types = 1 );

namespace MediaWiki\Babel;

use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\LBFactory;

class Database {

	/**
	 * @var LBFactory
	 */
	private $loadBalancerFactory;

	public function __construct() {
		$this->loadBalancerFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
	}

	/**
	 * @param int $index
	 * @param string|bool $wiki Database name if querying a different wiki
	 * @return IDatabase
	 */
	protected function getDB( int $index, $wiki = false ): IDatabase {
		return $this->loadBalancerFactory->getMainLB( $wiki )
			->getConnection( $index, [], $wiki );
	}

	/**
	 * @param int $id user id
	 * @return string[] [ lang => level ]
	 */
	public function getForUser( int $id ): array {
		$rows = $this->getDB( DB_REPLICA )->newSelectQueryBuilder()
			->select( [ 'babel_lang', 'babel_level' ] )
			->from( 'babel' )
			->where( [ 'babel_user' => $id ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$return = [];
		foreach ( $rows as $row ) {
			$return[$row->babel_lang] = $row->babel_level;
		}

		return $return;
	}

	/**
	 * @param string $wiki Database name
	 * @param string $username
	 * @return string[] [ lang => level ]
	 */
	public function getForRemoteUser( string $wiki, string $username ): array {
		$rows = $this->getDB( DB_REPLICA, $wiki )->newSelectQueryBuilder()
			->select( [ 'babel_lang', 'babel_level' ] )
			->from( 'babel' )
			->join( 'user', null, 'babel_user=user_id' )
			->where( [
				'user_name' => $username
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$return = [];
		foreach ( $rows as $row ) {
			$return[$row->babel_lang] = $row->babel_level;
		}

		return $return;
	}

	/**
	 * @param int $id
	 * @param string[] $data [ lang => level ]
	 * @return bool true if changes to the db were made
	 */
	public function setForUser( int $id, array $data ): bool {
		$dbw = $this->getDB( DB_PRIMARY );

		$newRows = [];
		foreach ( $data as $lang => $level ) {
			$newRows[$lang] = [
				'babel_lang' => $lang,
				'babel_level' => $level,
				'babel_user' => $id
			];
		}

		$rowsDelete = [];
		$res = $dbw->newSelectQueryBuilder()
			->select( [ 'babel_lang', 'babel_level' ] )
			->from( 'babel' )
			->where( [ 'babel_user' => $id ] )
			->caller( __METHOD__ )
			->fetchResultSet();
		foreach ( $res as $row ) {
			if ( isset( $newRows[$row->babel_lang] ) ) {
				if ( $newRows[$row->babel_lang]['babel_level'] === $row->babel_level ) {
					// Matching row already exists
					unset( $newRows[$row->babel_lang] );
				}
			} else {
				$rowsDelete[] = $row->babel_lang;
			}
		}

		if ( $rowsDelete ) {
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'babel' )
				->where( [ 'babel_user' => $id, 'babel_lang' => $rowsDelete ] )
				->caller( __METHOD__ )
				->execute();
		}
		if ( $newRows ) {
			$dbw->newReplaceQueryBuilder()
				->replaceInto( 'babel' )
				->uniqueIndexFields( [ 'babel_user', 'babel_lang' ] )
				->rows( array_values( $newRows ) )
				->caller( __METHOD__ )
				->execute();
		}

		return $rowsDelete || $newRows;
	}
}
