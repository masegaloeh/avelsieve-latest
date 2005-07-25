<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Functions for getting existing rules out from an avelsieve script.
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_getrule.inc.php,v 1.2 2005/07/25 10:30:27 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
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
	    (
	     isset($scriptinfo['version']['major']) &&
	     $scriptinfo['version']['major'] == 0 &&
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
		for($i=0; $i<sizeof($rulestrings[0]); $i++) {
			/* remove the last 14 characters from a string */
			$rulestrings[0][$i] = substr($rulestrings[0][$i], 0, -14); 
			/* remove the first 16 characters from a string */
			$rulestrings[0][$i] = substr($rulestrings[0][$i], 16);

			$rulearray[$i] = unserialize(base64_decode(urldecode($rulestrings[0][$i])));
		}
	} else {
		/* No rules; return an empty array */
		return array();
	}
	
	/* Migrate for avelsieve <= 1.9.3 to 1.9.4+-style rules */
	if( (!isset($scriptinfo['version']) ) ||
	    (
	     isset($scriptinfo['version']['major']) &&
	     $scriptinfo['version']['major'] == 0
		) ||
	    (
	     isset($scriptinfo['version']['major']) &&
	     $scriptinfo['version']['major'] == 1 &&
	     $scriptinfo['version']['minor'] <= 9 &&
	     $scriptinfo['version']['release'] <= 3 
	    ) ||
	    (isset($scriptinfo['version']['old']) && ($scriptinfo['version']['old'] == true ))
	) {
		if(AVELSIEVE_DEBUG == 1) {
		   	print "Notice: Backward compatibility mode - transitioning from <=1.9.3 to 1.9.4+";
		}
		avelsieve_migrate_1_9_4($rulearray);
	}

	/* print "<p>DEBUG: Returning...<pre>"; print_r($rulearray); reset($rulearray); print "</pre>"; */
	return $rulearray;
}

/**
 * Migration of avelsieve rules from avelsieve <= 1.9.3 to 1.9.4+.
 * Changes the condition (if part) to the new 'cond' schema for more complex
 * conditions and adds support for envelope, body etc.
 *
 * All the rules with type = 2, 3 or 4 will use type = 1 from now on.
 *
 * @param $rulearray The array of rules which will be modified.
 * @return void
 */
function avelsieve_migrate_1_9_4(&$rulearray) {
	foreach($rulearray as $no => $r) {
		if($r['type'] == '2') { // header
			$rulearray[$no]['type'] = 1;
			for($i=0;$i<sizeof($rulearray[$no]['header']);$i++) {
				$rulearray[$no]['cond'][$i]['type'] = 'header';
				$rulearray[$no]['cond'][$i]['header'] = $r['header'][$i];
				$rulearray[$no]['cond'][$i]['matchtype'] = $r['matchtype'][$i];
				$rulearray[$no]['cond'][$i]['headermatch'] = $r['headermatch'][$i];
			}
			unset($rulearray[$no]['header']);
			unset($rulearray[$no]['matchtype']);
			unset($rulearray[$no]['headermatch']);
		
		} elseif($r['type'] == '3') { // size
			$rulearray[$no]['type'] = 1;
			$rulearray[$no]['cond'][0]['type'] = 'size';
			$rulearray[$no]['cond'][0]['sizerel'] = $r['sizerel'];
			$rulearray[$no]['cond'][0]['sizeamount'] = $r['sizeamount'];
			$rulearray[$no]['cond'][0]['sizeunit'] = $r['sizeunit'];
			unset($rulearray[$no]['sizerel']);
			unset($rulearray[$no]['sizeamount']);
			unset($rulearray[$no]['sizeunit']);


		} elseif($r['type'] == '4') { // all
			$rulearray[$no]['type'] = 1;
			$rulearray[$no]['cond'][0]['type'] = 'all';
			
		} elseif($rulearray[$no]['type'] == '10') { // spam
			/* TODO */
		}
	}
}

?>
