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
 * $Id: buildrule.php,v 1.5 2003/10/09 13:34:14 avel Exp $
 */

/**
 * Script Variables Schema
 * NB: Might be Incomplete.
 *
 * The following table tries to describe the variables schema that is used by
 * avelsieve.
 *
 * VARIABLES
 * ---------
 * AVELSIEVE_CREATED
 * AVELSIEVE_MODIFIED
 * AVELSIEVE_COMMENT
 * AVELSIEVE_VERSION
 * 
 * Condition
 *
 * 1											// Address Match
 * 1											// Not implemented yet.
 * 2	header[$n]									// Header Match
 * 2	matchtype[$n]		"is" | "contains" | "matches" | "lt" | "regex" | ...
 * 2	headermatch[$n]		string
 * 2	condition		undefined | "or" | "and"
 * 3	sizerel			"bigger" | "smaller"					// Size match
 * 3	sizeamount		int
 * 3	sizeunit		"kb" | "mb"
 * 4											// Always
 * 10	score			int							// Spam Rule
 * 10	tests			array
 * 10	action			'trash' | 'junk' | 'discard'
X-Spam-Score: 80
X-Spam-Tests: Open.Relay.DataBase;Spamhaus.Block.List;
 *
 * 
 * Action
 *
 * action		1 | 2 | 3 | 4 | 5 | 6
 *
 * 1) // Keep
 *
 * 2) // Discard
 *
 * 3) // Reject w/ excuse
 *
 * excuse		string		valid only for: action==3
 *
 * 4) // Redirect
 *
 * redirectemail	string (email)	valid only for: action==4
 *
 * 5) // Fileinto
 *
 * folder				valid only for: action==5
 *
 * 6) // Vacation
 *
 * vac_days	int
 * vac_addresses	string
 * vac_message	string		valid only for: action==6
 *
 * 
 * -) // All
 *
 * keepdeleted	boolean
 * stop		boolean
 * notify	array
 *		'method' => string
 *		'id' => string
 *		'options' => array( [0]=> foo, [1] => bar )
 *		'priority' => low|normal|high
 *		'message' => string
 *
 *
 */ 

/** 
 * Gets a $rule array and builds a part of a SIEVE script (aka a rule).
 *
 * @param $rule	A rule array.
 * @param $type	What to return. Can be one of:
 *   verbose = return a (verbose) textual description of the rule.
 *   terse = return a very terse description
 *   rule = return a string with the appropriate SIEVE code.
 */
function makesinglerule($rule, $type="rule") {

global $maxitems;

/* Step zero :-) : serialize & encode my array */

$coded = urlencode(base64_encode(serialize($rule)));

$out = "#START_SIEVE_RULE".$coded."END_SIEVE_RULE\n";

/* Step one: make the if clause */

/* The actual 'if' will be added by makesieverule() */

$terse = '<table width="100%" border="0" cellspacing="2" cellpadding="2"><tr><td align="left">';

if($rule['type']=="4") {
 	$text = _("For <strong>ALL</strong> incoming messages; ");
	$terse .= "ALL";
	$terse .= '</td><td align="right">';

} elseif($rule['type'] == "10") {
	/* SpamRule */

	global $spamrule_score_default, $spamrule_score_header,
	$spamrule_tests, $spamrule_tests_header, $spamrule_action_default;
	
	$spamrule_advanced = false;

	if(isset($rule['advanced'])) {
		$spamrule_advanced = true;
	}

	if(isset($rule['score'])) {
		$sc = $rule['score'];
	} else {
		$sc = $spamrule_score_default;
	}
	
	if(isset($rule['tests'])) {
		$te = $rule['tests'];
	} else {
		$te = array_keys($spamrule_tests);
	}

	if(isset($rule['action'])) {
		$ac = $rule['action'];
	} else {
		$ac = $spamrule_action_default;
	}

	/*
	if allof( header :contains "X-Spam-Rule" "Open.Relay.Database" ,
		  header :contains "X-Spam-Rule" "Spamhaus.Block.List" ,
		  header :value "gt" :comparator "i;ascii-numeric" "80" ) {
		
		fileinto "INBOX.Junk";
		discard;
	}
	*/
	
	$out .= 'allof( ';
	$text = _("All messages considered as <strong>SPAM</strong> (unsolicited commercial messages)");
	$terse .= "SPAM";
	
	for($i=0; $i<sizeof($te); $i++ ) {
		$out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[$i].'",';
		$out .= "\n";
	}

	$out .= ' header :value "gt" :comparator "i;ascii-numeric" "'.$spamrule_score_header.'" "'.$sc.'" ) { ';
	$out .= "\n";
	
	
	$text .= ', ';
	if($spamrule_advanced == true) {
		$text .= _("matching the Spam List(s):");

		for($i=0; $i<sizeof($te); $i++) {
			$text .= $spamrule_tests[$te[$i]].', ';
		}
		$text .= sprintf( _("and with score greater than %s") , $sc );
	}

	$text .= _("will be") . ' ';
	$terse .= '</td><td align="right">';

	if($ac == 'junk') {
		$text .= _("stored in the Junk Folder.");
		$out .= 'fileinto "INBOX.Junk";';
		$terse .= 'JUNK';

	} elseif($ac == 'trash') {
		$text .= _("stored in the Trash Folder.");

		global $data_dir, $username;
		$tf = getPref($data_dir, $username, 'trash_folder');

		$out .= 'fileinto "'.$tf.'";';
		$terse .= 'TRASH';

	} elseif($ac == 'discard') {
		$text .= _("discarded.");
		$out .= 'discard;';
		$terse .= 'DISCARD';
	}

	/*
	print "DEBUG: <pre>";
	print $text;
	print $terse;
	print $out;
	print_r($rule);
	print "</pre>";
	*/

} else {
	$text = "<strong>"._("If")."</strong> ";
} 

switch ($rule['type']) {
case "1":	/* address --- slated for the future. */

	for ( $i=0; $i<3; $i++) {
		$out .= 'address :'.${'address'.$i};
		if(${'addressrel'.$i} != "0") {
			$out .= ":";
		}
	}
	break;

case "2":	/* header */
	if(isset($rule['condition'])) {
	switch ($rule['condition']) {
		case "or":
			$out .= "anyof (";
			$text .= _("<em>any</em> of the following mail headers match: ");
			// $terse .= "ANY (";
			break;
		case "and":
			$out .= "allof (";
			$text .= _("<em>all</em> of the following mail headers match: ");
			// $terse .= "ALL (";
			break;
		default: /* condition was not defined, so there's only one header item. */
			$lonely = true;
			break;
	}
	}
	/* if ( $i<sizeof($rule['headermatch'][$i] > 1) {
		$out .=
	}
	*/
	for ( $i=0; $i<sizeof($rule['headermatch']); $i++) {
		$text .= _("the header");

		$text .= " <strong>&quot;".htmlspecialchars($rule['header'][$i])."&quot;</strong> ";
		$terse .= " ".htmlspecialchars($rule['header'][$i])." ";

		// $escapeslashes = false;

 		switch ($rule['matchtype'][$i]) {
 			case "is":
 				$out .= "header :is";
				$text .= _("is");
				$terse .= "=";
 				break 1;
 			case "is not":
 				$out .= "not header :is";
				$text .= _("is not");
				$terse .= "!=";
 				break 1;
 			case "contains":
 				$out .= "header :contains";
				$text .= _("contains");
				$terse .= "=";
 				break 1;
 			case "does not contain":
 				$out .= "not header :contains";
				$text .= _("does not contain");
				$terse .= "!~=";
 				break 1;
 			case "matches":
 				$out .= "header :matches";
				$text .= _("matches");
				$terse .= "M=";
				$escapeslashes = true;
 				break 1;
 			case "does not match":
 				$out .= "not header :matches";
				$text .= _("does not match");
				$terse .= "!M=";
				$escapeslashes = true;
 				break 1;
 			case "gt":
				$out .= "header :value \"gt\" :comparator \"i;ascii-numeric\"";
				$text .= _("is greater than");
				$terse .= ">";
 				break 1;
 			case "ge":
				$out .= "header :value \"ge\" :comparator \"i;ascii-numeric\"";
				$text .= _("is greater or equal to");
				$terse .= ">=";
 				break 1;
 			case "lt":
				$out .= "header :value \"lt\" :comparator \"i;ascii-numeric\"";
				$text .= _("is lower than");
				$terse .= "<";
 				break 1;
 			case "le":
				$out .= "header :value \"le\" :comparator \"i;ascii-numeric\"";
				$text .= _("is lower or equal to");
				$terse .= "<=";
 				break 1;
 			case "eq":
				$out .= "header :value \"eq\" :comparator \"i;ascii-numeric\"";
				$text .= _("is equal to");
				$terse .= "==";
 				break 1;
 			case "ne":
				$out .= "header :value \"ne\" :comparator \"i;ascii-numeric\"";
				$text .= _("is not equal to");
				$terse .= "!=";
 				break 1;
 			case "regex":
 				$out .= "header :regex :comparator \"i;ascii-casemap\"";
				$text .= _("matches the regural expression");
				$terse .= "R=";
				$escapeslashes = true;
 				break 1;
 			case "not regex":
 				$out .= "not header :matches";
				$text .= _("does not match the regural expression");
				$terse .= "!R=";
				$escapeslashes = true;
 				break 1;
 			case "exists":
 				$out .= "exists";
				$text .= _("exists");
				$terse .= "E";
 				break 1;
 			case "not exists":
 				$out .= "not exists";
				$text .= _("does not exist");
				$terse .= "!E";
 				break 1;
 			default:
 				break 1;
 		}
		$out .= " \"" . $rule['header'][$i] . "\" ";

		/* Escape slashes and double quotes */
		$out .= "\"". avelsieve_addslashes($rule['headermatch'][$i]) . "\"";

		$text .= " \"". htmlspecialchars($rule['headermatch'][$i]) . "\"";

 		if ($rule['matchtype'][$i] == "contains") {
			$terse .= " *".htmlspecialchars($rule['headermatch'][$i])."* ";
		} else {
			$terse .= " ".htmlspecialchars($rule['headermatch'][$i])." ";
		}

		if(isset($rule['headermatch'][$i+1])) {
			$out .= ",\n";
			$text .= ", ";

			if ($rule['condition'] == "or" ) {
				$terse .= " OR<br />";
			} elseif ($rule['condition'] == "and" ) {
				$terse .= " AND<br />";
			}
		} elseif($i == 0  && !isset($rule['headermatch'][1]) ) {
		// && ($lonely == true)
			$out .= "\n";
			$text .= ", ";
		} else {
			$out .= ")\n";
			$text .= ", ";
		}

	} /* end for */
	
	break;

case "3":	/* size */
	$out .= 'size :';
	$text .= _("the size of the message is");
	$text .= "<em>";
	$terse .= "SIZE";
	
	if($rule['sizerel'] == "bigger") {
		$out .= "over ";
		$terse .= " > ";
		$text .= _(" bigger");
	} else {
		$out .= "under ";
		$terse .= " < ";
		$text .= _(" smaller");
	}
	$text .= " "._("than")." ". htmlspecialchars($rule['sizeamount']) . " ". htmlspecialchars($rule['sizeunit']) . "</em>, ";
	
	$terse .= $rule['sizeamount'];
	$out .= $rule['sizeamount'];
	
	if($rule['sizeunit']=="kb") {
		$out .= "K\n";
		$terse .= "K\n";
	} elseif($rule['sizeunit']=="mb") {
		$out .= "M\n";
		$terse .= "M\n";
	}
	break;

case "4":	/* always */
	$out .= "true {\n";
	break;
}



/* step two: make the then clause */


if( $rule['type'] != "4" && $rule['type']!=10 ) {
	$out .= "{\n";
	$terse .= '</td><td align="right">';
	$text .= "<strong>";
	$text .= _("then");
	$text .= "</strong> ";
}



switch ($rule['action']) {
case "1":	/* keep (default) */
	$out .= "keep;";
	$text .= _("<em>keep</em> it.");
	$terse .= "KEEP";
	break;

case "2":	/* discard */
	$out .= "discard;";
	$text .= _("<em>discard</em> it.");
	$terse .= "DISCARD";
	break;

case "3":	/* reject w/ excuse */
	
	$out .= "reject text:\n".$rule['excuse']."\r\n.\r\n;";
	$text .= _("<em>reject</em> it, sending this excuse back to the sender:")." \"".htmlspecialchars($rule['excuse'])."\".";
	$terse .= "REJECT";
	break;

case "4":	/* redirect to address */
	$out .= "redirect \"".$rule['redirectemail']."\";";
	$text .= _("<em>redirect</em> it to the email address")." ".htmlspecialchars($rule['redirectemail']).".";
	$terse .= "REDIRECT ".htmlspecialchars($rule['redirectemail']);
	break;

case "5":	/* fileinto folder */

	$out .= 'fileinto "'.$rule['folder'].'";';
	$text .= _("<em>file</em> it into the folder <strong>");
	/* FIXME - funny stuff has entered gettext function ... */
	$text .= " <strong>" . htmlspecialchars(imap_utf7_decode_local($rule['folder'])) . "</strong></strong>";
	$text .= "</strong>.";
	$terse .= "FILEINTO ".htmlspecialchars(imap_utf7_decode_local($rule['folder']));
	break;

case "6":      /* vacation message */
	/* Check if $addresses is valid */

	/* If vacation address does not exist, put inside the default, which is
	 * user's addresses. */

	if( !isset($rule['vac_addresses']) || 
	    (isset($rule['vac_addresses']) && trim($rule['vac_addresses'])=="" ) ) {

		global $data_dir, $username;
		$addresses = getPref($data_dir, $username, 'email_address');

	} else {
		/* Ugly... */
		$addresses = str_replace(",",'","',str_replace(" ","",$rule['vac_addresses']));
	}

 	$out .= 'vacation :days '.$rule['vac_days'].' :addresses ["'.$addresses.
	'"] '." text:\n".$rule['vac_message']."\r\n.\r\n;";
 	/* Used to be: '"] "'.$rule['vac_message'].'";'; */

 	$text .= _("reply with this vacation message: ") . htmlspecialchars($rule['vac_message']);
	$terse .= "VACATION";
 	break;
default:
	// return false;
	break;
}

if (isset($rule['keepdeleted'])) {
	$text .= _(" Also keep a copy in INBOX, marked as deleted.");
	$out .= "\naddflag \"\\\\\\\\\\\\\\\\Deleted\";\nkeep;";
	$terse .= "<br />KEEP DELETED";
}


/* Notify extension */

if (array_key_exists("notify", $rule) && is_array($rule['notify']) && ($rule['notify']['method'] != '')) {
	global $notifystrings, $prioritystrings;
	$text .= _(" Also notify using the method")
		. " <em>" . htmlspecialchars($notifystrings[$rule['notify']['method']]) . "</em>, ".
		_("with")
		. " " . htmlspecialchars($prioritystrings[$rule['notify']['priority']]) . " " .
		_("priority and the message")
		. " <em>&quot;" . htmlspecialchars($rule['notify']['message']) . "&quot;</em>.";
		
	$out .= "\nnotify :method \"".$rule['notify']['method']."\" ";
	
	$out .= ":options \"".$rule['notify']['options']."\" ";

	if(isset($rule['notify']['id'])) {
		$out .= ":id \"".$rule['notify']['id']."\" ";
	}
	if(isset($rule['notify']['priority']) && array_key_exists($rule['notify']['priority'], $prioritystrings)) {
		$out .= ":".$rule['notify']['priority'] . " ";
	}
	$out .= ":message \"".$rule['notify']['message']."\"";
	$out .= ";\n";

	$terse .= "<br />NOTIFY";
}


/* Stop processing other rules */

if (isset($rule['stop'])) {
	$text .= _(" Then <strong>STOP</strong> processing rules.");
	$out .= "\nstop;";
	$terse .= "<br />STOP";
}


$out .= "\n}";
$terse .= "</td></tr></table>";

if ($type == "terse") {
	return $terse;
} elseif (($type == "text") || ($type == "verbose")) {
	return $text;
} else {
	return $out;
}

}


/**
 * Make a complete set of rules, that is, a SIEVE script.
 *
 * @param $rulearray An array of associative arrays, each one describing a
 * rule.
 * 
 */
function makesieverule ($rulearray) {
	global $implemented_capabilities, $cap_dependencies,
	       $sieve_capabilities, $avelsieve_version,
	       $creation_date, $scriptinfo;

	if ( (sizeof($rulearray) == 0) || $rulearray[0] == "0" ) {
		return false;
	}

	/* Encoded avelsieve version information */

	$versionencoded = base64_encode(serialize($avelsieve_version));
	
	//global $creation_date;
	// $modification_date = ...
	//if (!isset($creation_date)) { $creation_date == $modification_date; }

	$out = "# This script has been automatically generated by avelsieve\n";
	$out .= "# (Sieve Mail Filters Plugin for Squirrelmail)\n";

	$out .= "#AVELSIEVE_VERSION" . $versionencoded . "\n";

	$modification_date = time();

	if(isset($scriptinfo['created'])) {
		$out .= "#AVELSIEVE_CREATED" . $scriptinfo['created'] . "\n";

	} else { /* New script */
		$creation_date = $modification_date;
		$out .= "#AVELSIEVE_CREATED" . $creation_date . "\n";

	}

	$out .= "#AVELSIEVE_MODIFIED" . $modification_date . "\n";
	// $out .= "#AVELSIEVE_COMMENT" . $script_comment . "\n"




	/* Capability requirements check */

	foreach($implemented_capabilities as $no=>$cap) {
		if(in_array($cap, $sieve_capabilities)) {
			$torequire[] = $cap;
			if(array_key_exists($cap, $cap_dependencies)) {
				foreach($cap_dependencies[$cap] as $no2=>$dep) {
					$torequire[] = $dep;
				}
			}
		}
	}
		
 	$out .= "require [";
	for($i=0; $i<sizeof($torequire); $i++) {
		$out .= '"'.$torequire[$i].'"';
		if($i != (sizeof($torequire) -1) ) {
			$out .= ',';
		}
	}
	$out .= "];\n";


	/* The actual rules */

	for ($i=0; $i<sizeof($rulearray); $i++) {
		switch ($i) {
			case 0:				$out .= "if\n";		break;
			/* case sizeof($rulearray): 	$out .= "\nelse\n";	break; */
 			default:			$out .= "\nif\n";	break;
		}		
		$out .= makesinglerule($rulearray[$i],"rule");
	}

	/* Uncomment this to see for yourself the script that is created. */
	// print "DEBUG: The script is: <pre>"; print $out; print "</pre>";

	return avelsieve_encode_script($out);
}

?>
