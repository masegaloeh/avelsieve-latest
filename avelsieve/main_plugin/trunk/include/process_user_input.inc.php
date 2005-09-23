<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: process_user_input.inc.php,v 1.15 2005/09/23 12:03:48 avel Exp $
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
 * @param string $errormsg If processing fails, error message will be returned in
 *    this variable.
 * @param boolean $truncate_empty_conditions
 * @return array Resulting Rule
 * @todo Use the rules, actions etc. schema variables & classes.
 */
function process_input($search = SQ_POST, &$errormsg, $truncate_empty_conditions = false) {
	global $comparators;

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
	$vars = array('type', 'condition');

	if($truncate_empty_conditions) {
		if(isset($ns['cond'])) {
			/* Decide how much of the items to use for the condition of the
			 * rule, based on the first zero / null /undefined variable to be
			 * found. Also, reorder the conditions. */
			$match_vars = array('headermatch', 'addressmatch', 'envelopematch', 'sizeamount', 'bodymatch');
			$new_cond_indexes = array();
			foreach($ns['cond'] as $n => $c) {
				foreach($match_vars as $m) {
					if(!empty($c[$m])) {
						$new_cond_indexes[] = $n;
					}
				}
			}
			$new_cond_indexes = array_unique($new_cond_indexes);
			$new_cond_indexes = array_values($new_cond_indexes);
	
			foreach($new_cond_indexes as $n => $index) {
				$rule['cond'][] = $ns['cond'][$index];
			}
		}
	} else {
		$vars[] = 'cond';
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
				avelsieve_action_redirect::validate($ns, $errormsg);
				array_push($vars, 'redirectemail', 'keep');
				break;
			case "5": /* fileinto */
				array_push($vars, 'folder');
				break;
			case "6": /* vacation */
				avelsieve_action_vacation::validate($ns, $errormsg);
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
	if(isset($ns['notify']['on']) && isset($ns['notify']['options']) &&
		!empty($ns['notify']['options'])) {
		$vars[] = 'notify';
	}

	if(isset($ns['disabled'])) {
		$rule['disabled'] = 1;
	}
	
	/* Put all variables from the defined namespace (e.g. $_POST in the rule
	 * array. */
	foreach($vars as $myvar) {
		if(isset($ns[$myvar])) {
			$rule[$myvar]= $ns[$myvar];
		}
	}

	/* Special hack for newly-created folder */
	if(isset($rule['folder'])) {
		global $created_mailbox_name;
		if(isset($created_mailbox_name) && $created_mailbox_name) {
			$rule['folder'] = $created_mailbox_name;
		}
	}
	return $rule;
}
	
?>
