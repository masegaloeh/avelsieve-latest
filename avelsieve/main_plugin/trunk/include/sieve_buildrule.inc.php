<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_buildrule.inc.php,v 1.3 2004/11/12 13:31:08 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
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
 * keep			string (email)	valid only for: action==4 (?TBC)
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
 * Build a snippet which is used for header rules and spam rule whitelists.
 * Takes arguments in natural English language order: 'From contains foo'.
 * @param string $header
 * @param string $matchtype
 * @param string $headermatch
 * @param string $type 'verbose', 'terse' or 'rule'
 *   verbose = return a (verbose) textual description of the rule.
 *   terse = return a very terse description
 *   rule = return a string with the appropriate SIEVE code.
 * @return string 
 */
 
function build_headerrule_snippet($header, $matchtype, $headermatch, $type='rule') {
	$out = '';
	$text = '';
	$terse = '';
		
		
		if($header == 'toorcc') {
			$text .= " <strong>&quot;To&quot; / &quot;Cc&quot; </strong> ";
			$terse .= " TO OR CC";
		} else {
			$text .= " <strong>&quot;".htmlspecialchars($header)."&quot;</strong> ";
			$terse .= " ".htmlspecialchars($header)." ";
		}
		// $escapeslashes = false;

 	switch ($matchtype) {
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
 				$out .= "not header :regex :comparator \"i;ascii-casemap\"";
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

		if($header == 'toorcc') {
			$out .= ' ["to", "cc"] ';
		} else {
			$out .= " \"" . $header . "\" ";
		}

		/* Escape slashes and double quotes */
		$out .= "\"". avelsieve_addslashes($headermatch) . "\"";

		$text .= " \"". htmlspecialchars($headermatch) . "\"";

 		if ($matchtype == "contains") {
			$terse .= " *".htmlspecialchars($headermatch)."* ";
		} else {
			$terse .= " ".htmlspecialchars($headermatch)." ";
		}

	if ($type == "terse") {
		return $terse;
	} elseif (($type == "text") || ($type == "verbose")) {
		return $text;
	} else {
		return $out;
	}
}


/** 
 * Gets a $rule array and builds a part of a SIEVE script (aka a rule).
 *
 * @param $rule	A rule array.
 * @param $type	What to return. Can be one of:
 *   verbose = return a (verbose) textual description of the rule.
 *   terse = return a very terse description
 *   rule = return a string with the appropriate SIEVE code.
 * @return string
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
		if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
		        	header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
				),
		  	header :value "gt" :comparator "i;ascii-numeric" "80" ) {
			
			fileinto "INBOX.Junk";
			discard;
		}
			
		// Whitelist scenario:
		if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
		        	header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
				),
		  	header :value "gt" :comparator "i;ascii-numeric" "80" ,
		  	anyof(header :contains "From" "Important Person",
		        	header :contains "From" "Foo Person"
		  	)
			) {
			
			fileinto "INBOX.Junk";
			discard;
		}
		*/
		
		$out .= 'allof( ';
		$text = _("All messages considered as <strong>SPAM</strong> (unsolicited commercial messages)");
		$terse .= "SPAM";
		
		if(sizeof($te) > 1) {
			$out .= ' anyof( ';
			for($i=0; $i<sizeof($te); $i++ ) {
				$out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[$i].'"';
				if($i < (sizeof($te) -1 ) ) {
					$out .= ",";
				}
			}
			$out .= " ),\n";
		} else {
			$out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[0].'", ';
		}
	
		$out .= "\n";
		$out .= ' header :value "ge" :comparator "i;ascii-numeric" "'.$spamrule_score_header.'" "'.$sc.'" ';
	
		if(isset($rule['whitelist']) && sizeof($rule['whitelist']) > 0) {
			/* Insert here header-match like rules, ORed of course. */
			$text .= ' (' . _("unless") . ' ';
			$terse .= ' !(WHITELIST:<br/>';
	
			$out .= " ,\n";
			$out .= ' not anyof( ';
			for($i=0; $i<sizeof($rule['whitelist']); $i++ ) {
				$out .= build_headerrule_snippet($rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'rule');
				$text .= build_headerrule_snippet($rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'verbose');
				$terse .= build_headerrule_snippet($rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
					$rule['whitelist'][$i]['headermatch'] ,'terse');
				if($i<sizeof($rule['whitelist'])-1) {
					$out .= ', ';
					$text .= ' ' . _("or") . ' ';
					$terse .= ' | <br/>';
				}
			}
			$text .= '), '; 
			$terse .= ') '; 
			$out .= " )";
		}
		$out .= " )\n";
		$out .= ' { ';
		$out .= "\n";
		
		$text .= ', ';
		if($spamrule_advanced == true) {
			$text .= _("matching the Spam List(s):");
	
			for($i=0; $i<sizeof($te); $i++) {
				$text .= $spamrule_tests[$te[$i]].', ';
			}
			$text .= sprintf( _("and with score greater than %s") , $sc );
			$terse .= "<br/>SCORE > $sc";
		}
	
		$text .= ', ' . _("will be") . ' ';
		$terse .= '</td><td align="right">';
	
		if($ac == 'junk') {
			$text .= _("stored in the Junk Folder.");
			$out .= 'fileinto "INBOX.Junk";';
			$terse .= 'JUNK';
	
		} elseif($ac == 'trash') {
			$text .= _("stored in the Trash Folder.");
	
			global $data_dir, $username;
			$trash_folder = getPref($data_dir, $username, 'trash_folder');
			/* Fallback in case it does not exist. Thanks to Eduardo
		 	* Mayoral. If not even Trash does not exist, it will end up in
		 	* INBOX... */
			if($trash_folder == '' || $trash_folder == 'none') {
				$trash_folder = "Trash";
			}
			$out .= 'fileinto "'.$trash_folder.'";';
			$terse .= 'TRASH';
	
		} elseif($ac == 'discard') {
			$text .= _("discarded.");
			$out .= 'discard;';
			$terse .= 'DISCARD';
		}
	
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
		for ( $i=0; $i<sizeof($rule['headermatch']); $i++) {
			$text .= _("the header");
	
			$out .= build_headerrule_snippet($rule['header'][$i], $rule['matchtype'][$i],
				$rule['headermatch'][$i] ,'rule');
			$text .= build_headerrule_snippet($rule['header'][$i], $rule['matchtype'][$i],
				$rule['headermatch'][$i] ,'verbose');
			$terse .= build_headerrule_snippet($rule['header'][$i], $rule['matchtype'][$i],
				$rule['headermatch'][$i] ,'terse');
	
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
	
	
	
	if(isset($rule['keep'])) {
		$out .= "keep;\n";
	}
	
	/* Fallback to default action */
	if(!isset($rule['action'])) {
		$rule['action'] = 1;
	}
	
	switch ($rule['action']) {
	case "1":	/* keep (default) */
	default:
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
	
 		$out .= 'vacation :days '.$rule['vac_days'];
		
		/* If vacation address does not exist, do not set the :addresses
	 	* argument. */
	
 		if(isset($rule['vac_addresses']) && trim($rule['vac_addresses'])!="") {
			$addresses = str_replace(",",'","',str_replace(" ","",$rule['vac_addresses']));
 			$out .= ' :addresses ["'.$addresses.'"]';
		}
	
  		$out .= " text:\n".$rule['vac_message']."\r\n.\r\n;";
	
 		$text .= _("reply with this vacation message: ") . htmlspecialchars($rule['vac_message']);
		$terse .= "VACATION";
 		break;
	
	default:
		break;
	}
	
	if(isset($rule['keep'])) {
		$text .= ' ' . _("Also keep a local copy.");
		$terse .= "<br/>KEEP";
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
 * @return $string
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
	//print "DEBUG: The script is: <pre>"; print $out; print "</pre>";

	return avelsieve_encode_script($out);
}

?>
