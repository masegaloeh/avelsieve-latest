<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: process_user_input.inc.php,v 1.2 2004/11/11 13:50:10 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'functions/global.php');

/**
 * Process rule data input for a filtering rule, coming from a specific
 * namespace (GET or POST). Puts the result in an array and returns that.
 *
 * @param int $search Defaults to $_POST.
 * @return array Resulting Rule
 * @todo Use the rules, actions etc. schema variables & classes.
 */
function process_input($search = SQ_POST, $errmsg = '') {
	$vars = array();
    
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
	if(isset($ns['type'])) {
		$type = $ns['type'];
		print $type;
		$vars[] = 'type';
		
		switch ($type) { 
		case "1":
			array_push($vars, 'address', 'addressrel');
			break;
		case "2":
			/* Decide how much of the items to use for the rule, based on
			 * the first zero variable to be found. */
			if(!$ns['headermatch'][0]) {
				$errormsg = _("You have to define at least one header match text.");
			}
	
			for ($i=0; $i<sizeof($ns['headermatch']) ; $i++) {
				if ($ns['headermatch'][$i]) {
					$rule['header'][$i] = $ns['header'][$i];
					$rule['matchtype'][$i] = $ns['matchtype'][$i];
					$rule['headermatch'][$i] = $ns['headermatch'][$i];
					if($i>0) {
						$rule['condition'] = $ns['condition'];
					}
				} else {
					break 1;
				}
			}
			break;
	
		case "3":
			if($ns['sizeamount']) {
				array_push($vars, 'sizerel', 'sizeamount', 'sizeunit');
			}
			break;
	
		case "4":
		default:
			break;
	}
	}
	
	if(isset($ns['action'])) {
		array_push($vars, 'action');
		switch ($ns['action']) { 
		case "1": /* keep */
			break;
		case "2": /* discard */
			break;
		case "3": /* reject w/ excuse */
			array_push($vars, 'excuse');
			break;
		case "4": /* redirect */
			array_push($vars, 'redirectemail', 'keep');
			break;
		case "5": /* fileinto */
			array_push($vars, 'folder');
			break;
		case "6": /* vacation */
			array_push($vars, 'vac_addresses', 'vac_days', 'vac_message');
			break;
		default:
			break;
	}
	}
	
	if(isset($ns['keepdeleted'])) {
		$vars[] = 'keepdeleted';
	}
	if(isset($ns['stop'])) {
		$vars[] = 'stop';
	}
	if(isset($ns['notifyme'])) {
		$vars[] = 'notify';
	}
	
	/* Put all variables from the defined namespace (e.g. $_POST in the rule
	 * array. */
	foreach($vars as $myvar) {
		if(isset($ns[$myvar])) {
			$rule[$myvar]= $ns[$myvar];
		}
	}
	
	return $rule;
}
	
?>
