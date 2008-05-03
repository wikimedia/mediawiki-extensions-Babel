<?php

/**
 * Set various variables needed by extension maintenance scripts.
 *
 * @addtogroup Extensions
 */

/* Set IP
 */
$IP = dirname( __FILE__ ) . '/../..';

/* Allow override of IP.
 */
if( file_exists( dirname( dirname( dirname( __FILE__ ) ) ) . '/CorePath.php' ) ) {

	require_once( dirname( dirname( dirname( __FILE__ ) ) ) . '/CorePath.php' );

}

/* Include command line starter.
 */
require_once( "$IP/maintenance/commandLine.inc" );