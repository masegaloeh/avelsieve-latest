<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: process_user_input.inc.php,v 1.1 2004/11/02 15:06:17 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'functions/global.php');

/**
 * Process rule data input for a rule of type $type, coming from a specific
 * namespace (GET or POST). Defaults to $_POST.
 */
function process_input($type, $search = SQ_POST) {
	
	$rule['type'] = $type;
    
	/* Set Namespace ($ns) referring variable according to $search */
	switch ($search) {
		case SQ_GET:
			$ns = &$_GET;
			break;
		default:
		case SQ_POST:
			$ns = &$_POST;
	}
	
	/* If Part */
	switch ($type) { 
		case "1":
			$vars = array( 'address', 'addressrel');
			foreach($vars as $myvar) {
				$rule[$myvar]= ${$myvar};
			}
			break;
		case "2":
			/* Decide how much of the items to use for the rule, based on
			 * the first zero variable to be found. */
			if(!$ns['headermatch'][0]) {
				//print "Error: You _have_ to define something!";
				return false;
			}
	
			if(false) {
				print _("You have to define at least one header match text.");
			}
			
			for ($i=0; $i<sizeof($ns['headermatch']) ; $i++) {
				if ($ns['headermatch'][$i]) {
					//print "<p><em>START PROC</em>";
					$rule['header'][$i] = $ns['header'][$i];
					$rule['matchtype'][$i] = $ns['matchtype'][$i];
					$rule['headermatch'][$i] = $ns['headermatch'][$i];
					if($i>0) {
						$rule['condition'] = $ns['condition'];
					}
					//print "<b>Added $i series</b><br>";
					//print "<p><em>END PROC</em>";
	
				} elseif (!$ns['headermatch'][$i]) {
					break 1;
				} else {
					//print "Huh?"; 
				}
			}
			break;
	
		case "3":
			if($ns['sizeamount']) {
				$vars = array( 'sizerel', 'sizeamount', 'sizeunit');
				foreach($vars as $myvar) {
					$rule[$myvar]= $ns[$myvar];
				}
			}
			break;
	
		case "4":
			$dont = "1";
			break;
		default:
			$dont = "1";
			break;
	}
	
	switch ($ns['action']) { 
		case "1": /* keep */
		case "2": /* discard */
			$vars = array( 'action');
			break;
		case "3": /* reject w/ excuse */
			$vars = array( 'action', 'excuse');
			break;
		case "4": /* redirect */
			$vars = array( 'action', 'redirectemail', 'keep');
			break;
		case "5": /* fileinto */
			$vars = array( 'action', 'folder', 'keepdeleted');
			break;
		case "6": /* vacation */
			$vars = array( 'action', 'vac_addresses', 'vac_days', 'vac_message');
			break;
		default:
			$vars = array();
			//print "Invalid action value!";
			break;
	}
	
	if(isset($ns['stop'])) {
		$vars = array_merge($vars, array('stop'));
	}
	
	if(isset($ns['notifyme'])) {
		$vars = array_merge($vars, array('notify'));
	}
	
	foreach($vars as $myvar) {
		if(isset($ns[$myvar])) {
			$rule[$myvar]= $ns[$myvar];
		}
	}
	
	return $rule;
}
	
	
?>
