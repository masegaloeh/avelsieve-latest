<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: process_user_input.inc.php,v 1.13 2005/07/25 10:30:27 avel Exp $
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
 * @param string $errmsg If processing fails, error message will be returned in
 *    this variable.
 * @param boolean $truncate_empty_conditions
 * @return array Resulting Rule
 * @todo Use the rules, actions etc. schema variables & classes.
 */
function process_input($search = SQ_POST, &$errmsg, $truncate_empty_conditions = false) {
	global $startitems, $maxitems;

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
			// Decide how much of the items to use for the condition of the rule,
			// based on the first zero variable to be found. Also, reorder the
			// conditions.
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
	/*
	if(isset($ns['type'])) {
		$type = $ns['type'];
		$vars[] = 'type';
		print $type;
		
		switch ($type) { 
		default:
		case "1":
			array_push($vars, 'cond', 'condition');
			break;
		case "2":
			if(isset($ns['headermatch'])) {
				if(!$ns['headermatch'][0]) {
					$errmsg = _("You have to define at least one header match text.");
				} else {
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
				}
			}
			break;
	
		case "3":
			if(isset($ns['sizeamount'])) {
				array_push($vars, 'sizerel', 'sizeamount', 'sizeunit');
			}
			break;
	
		case "4":
			break;
	}
	}
	*/

	/*
	global $items;
	sqgetGlobalVar('items', $items, SQ_FORM);

	if(!isset($items)) {
		$items = $startitems;
	}

	if(isset($ns['append'])) {
		print " APPENDING ";
		$items++;
	} elseif(isset($ns['less'])) {
		$items--;
	}
	if($items > $maxitems) {
		$items = $maxitems;
	}
	*/
	
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
				$errormsg = array();
				//avelsieve_action_redirect->validate($ns, $errormsg);
	
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
