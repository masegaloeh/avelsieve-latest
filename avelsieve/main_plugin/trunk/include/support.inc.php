<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * Various support functions, useful or useless.  NB. THEY MUST NOT DEPEND
 * ELSEWHERE.
 *
 * @version $Id: support.inc.php,v 1.1 2004/11/02 15:06:17 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Delete element from array.
 */
function array_del($array, $place) {
	$newarray = array();
	$n=0;
	for ($i=0; $i<sizeof($array); $i++)
		if ($i!=$place) 
			$newarray[$n++] = $array[$i];
	return $newarray;
} 


/**
 * Swap values of two elements in array.
 */
function array_swapval ($array, $i, $j) {
	$temp[$i] = $array[$j];
	$temp[$j] = $array[$i];

	$array[$i] = $temp[$i];
	$array[$j] = $temp[$j];

	return $array;
}

/**
 * This plugin's error display function.
 * @todo use the one provided by Squirrelmail
 * @todo use new class
 */
function print_errormsg($errormsg) {
	printheader2(_("Error Encountered"));
	print_all_sections_start();
	print_section_start(_("Error Encountered"));
	print $errormsg;
	print_section_end(); 
	print_all_sections_end();
	printfooter2();
	exit;
}

?>
