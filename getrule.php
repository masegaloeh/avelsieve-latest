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
 * $Id: getrule.php,v 1.2 2003/10/07 13:24:52 avel Exp $
 */

/**
 * Decode data from an existing SIEVE script
 *
 * @param str $sievescript A SIEVE script to get information from
 * @param array $scriptinfo Store Script Information (creation date,
 * modification date, avelsieve version) here
 *
 * @return array Rules array
 */

function getruledata($sievescript, &$scriptinfo) {

	/* print '<pre>'.$sievescript.'</pre>'; */
	
	/* Get avelsieve script version info, if it exists. */

	$regexp = '/AVELSIEVE_VERSION.+\n/sU';
	
	if (preg_match($regexp, $sievescript, $verstrings) == 1) {
		$tempstr = substr(trim($verstrings[0]), 17);
		$scriptinfo['version'] = unserialize(base64_decode($tempstr));

	} else {
		$scriptinfo['version'] = array('old' => true);
	}


	/* Creation date */

	$regexp = '/AVELSIEVE_CREATED.+\n/sU';
	
	if (preg_match($regexp, $sievescript, $verstrings) == 1) {
		$scriptinfo['created'] = substr(trim($verstrings[0]), 17);
	}
	
	/* Last modification date */

	$regexp = '/AVELSIEVE_MODIFIED.+\n/sU';

	if (preg_match($regexp, $sievescript, $verstrings) == 1) {
		$scriptinfo['modified'] = substr(trim($verstrings[0]), 18);
	}


	/* Only decode script if it was created from avelsieve 0.9.6 +.
	 * Backward compatibility: If version==0.9.5 or 0.9.4 or not defined,
	 * don't decode it! */

	if( (!isset($scriptinfo['version']) ) ||
	   ( $scriptinfo['version']['major'] == 0 &&
	     $scriptinfo['version']['minor'] == 9 &&
	     ($scriptinfo['version']['release'] == 4 || $scriptinfo['version']['release'] == 5) 
	    ) ||
	    (isset($scriptinfo['version']['old']) && ($scriptinfo['version']['old'] == true ))
	) {
		if(AVELSIEVE_DEBUG == 1) {
		    	print "Notice: Backward compatibility mode - not decoding script.";
		}
		
	} else {
		$sievescript = avelsieve_decode_script($sievescript);
	}

	/* Get Rules */
	
	$regexp = "/START_SIEVE_RULE.+END_SIEVE_RULE/sU";

	if (preg_match_all($regexp,$sievescript,$rulestrings)) {
		
		/* print "DEBUG: Some rules found: <pre>"; print_r ($rulestrings[0]); print "</pre>";
		print '<b>I have found '.sizeof($rulestrings[0]).' rules in your sieve script.</b>'; */

		for($i=0; $i<sizeof($rulestrings[0]); $i++) {
			/* remove the last 14 characters from a string */
			$rulestrings[0][$i] = substr($rulestrings[0][$i], 0, -14); 
			/* remove the first 16 characters from a string */
			$rulestrings[0][$i] = substr($rulestrings[0][$i], 16);
			/* print "<pre>"; print_r($rulestrings); print "</pre>"; */

			$rulearray[$i] = unserialize(base64_decode(urldecode($rulestrings[0][$i])));
			/* print "<pre>"; print_r($rulearray); print "</pre>"; */
		}
	
	} else {
		/* print "DEBUG: No rules in this file."; */
		return false;
	}

	/* print "<p>DEBUG: Returning...<pre>"; print_r($rulearray); reset($rulearray); print "</pre>"; */
	return $rulearray;
}

?>
