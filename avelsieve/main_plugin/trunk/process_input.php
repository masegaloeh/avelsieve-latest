<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Copyright (c) 2002-2003 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Based on Dan Ellis' test scripts that came with sieve-php.lib
 * <danellis@rushmore.com> <URL:http://sieve-php.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * $Id: process_input.php,v 1.2 2003/10/07 13:24:52 avel Exp $
 */

/**
 * Process input from $_POST.
 */
function process_input($type) {
	
	$rule['type'] = $type;
	
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
			if(!$_POST['headermatch'][0]) {
				//print "Error: You _have_ to define something!";
				return false;
			}
	
			if(false) {
				print _("You have to define at least one header match text.");
			}
			
			for ($i=0; $i<sizeof($_POST['headermatch']) ; $i++) {
				if ($_POST['headermatch'][$i]) {
					//print "<p><em>START PROC</em>";
					$rule['header'][$i] = $_POST['header'][$i];
					$rule['matchtype'][$i] = $_POST['matchtype'][$i];
					$rule['headermatch'][$i] = $_POST['headermatch'][$i];
					if($i>0) {
						$rule['condition'] = $_POST['condition'];
					}
					//print "<b>Added $i series</b><br>";
					//print "<p><em>END PROC</em>";
	
				} elseif (!$_POST['headermatch'][$i]) {
					break 1;
				} else {
					//print "Huh?"; 
				}
			}
			break;
	
		case "3":
			if($_POST['sizeamount']) {
				$vars = array( 'sizerel', 'sizeamount', 'sizeunit');
				foreach($vars as $myvar) {
					$rule[$myvar]= $_POST[$myvar];
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
	
	switch ($_POST['action']) { 
		case "1": /* keep */
		case "2": /* discard */
			$vars = array( 'action');
			break;
		case "3": /* reject w/ excuse */
			$vars = array( 'action', 'excuse');
			break;
		case "4": /* redirect */
			$vars = array( 'action', 'redirectemail');
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
	
	if(isset($_POST['stop'])) {
		$vars = array_merge($vars, array('stop'));
	}
	
	if(isset($_POST['notifyme'])) {
		$vars = array_merge($vars, array('notify'));
	}
	
	foreach($vars as $myvar) {
		if(isset($_POST[$myvar])) {
			$rule[$myvar]= $_POST[$myvar];
		}
	}
	
	return $rule;
}
	
	
?>
