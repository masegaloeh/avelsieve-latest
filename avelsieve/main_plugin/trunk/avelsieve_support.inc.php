<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Copyright (c) 2002-2003 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 */

/**
 * Various support functions, useful or useless.  NB. THEY MUST NOT DEPEND
 * ELSEWHERE.
 */

/* Array functions */

function array_del($array, $place) {

	$newarray = array();
	$n=0;
	for ($i=0; $i<sizeof($array); $i++)
		if ($i!=$place) 
			$newarray[$n++] = $array[$i];
	return $newarray;
} 

function array_swapval ($array, $i, $j) {

	$temp[$i] = $array[$j];
	$temp[$j] = $array[$i];

	$array[$i] = $temp[$i];
	$array[$j] = $temp[$j];

	return $array;

}

/**
 * This plugin's error display function.
 * Probably I should use Squirrelmail's.
 */
function print_errormsg($errormsg) {

	printheader2(_("Error Encountered"));
	print_all_sections_start();
	print_section_start(_("Error Encountered"));
	// print_create_new();
	print $errormsg;
	print_section_end(); 
	print_all_sections_end();
	printfooter2();
	exit;

}

?>
