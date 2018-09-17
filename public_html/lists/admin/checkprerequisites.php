<?php
/*******************************************************************************
 * File:    checkprerequisites.php
 * Version: 1.0
 * Purpose: Check that some basic prerequisites to running phplist are met. If
 *          not, fail gracefully
 * Created: 2018-09-15
 * Updated: 2018-09-15
 ******************************************************************************/

// make sure we're running a recent version of php
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50303) {
    die('Your PHP version is too old. Please upgrade PHP before continuing.');
}

// make sure we have access to a cryptographically secure pseudorandom number
// generator (CSPNRG)
try {
    require_once dirname(__FILE__).'/inc/random_compat/random.php';
    random_bytes(1);
} catch (Exception $e) {
    error_log( "Caught Exception: " . $e->getMessage() );
    die (
     "phpList requires a random_bytes function. For more information, please "
    ."see \r\n\r\n<br/><br/>"
    .'* https://tech.michaelaltfield.net/2018/08/25/fix-phplist-500-error-due-to-random_compat/'
    );
} 

?>
