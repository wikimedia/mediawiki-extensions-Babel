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

namespace MediaWiki\Babel;

use LoadBalancer;
use MediaWiki\MediaWikiServices;

class Database {

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	public function __construct() {
		$this->loadBalancer = MediaWikiServices::getInstance()
			->getDBLoadBalancer();
	}

	/**
	 * @param $index
	 * @return \IDatabase
	 */
	protected function getDB( $index ) {
		return $this->loadBalancer->getLazyConnectionRef( $index );
	}

	/**
	 * @param int $id user id
	 * @return string[] [ lang => level ]
	 */
	public function getForUser( $id ) {
		$rows = $this->getDB( DB_REPLICA )->select(
			'babel',
			[ 'babel_lang', 'babel_level' ],
			[ 'babel_user' => $id ],
			__METHOD__
		);

		$return = [];
		foreach ( $rows as $row ) {
			$return[$row->babel_lang] = $row->babel_level;
		}

		return $return;
	}

	/**
	 * @param string $id
	 * @param string[] $data [ lang => level ]
	 */
	public function setForUser( $id, array $data ) {
		$dbw = $this->getDB( DB_MASTER );
		$dbw->delete( 'babel', [ 'babel_user' => $id ], __METHOD__ );

		$rows = [];
		foreach ( $data as $lang => $level ) {
			$rows[] = [
				'babel_lang' => $lang,
				'babel_level' => $level,
				'babel_user' => $id
			];
		}

		$dbw->insert( 'babel', $rows, __METHOD__ );
	}
}
