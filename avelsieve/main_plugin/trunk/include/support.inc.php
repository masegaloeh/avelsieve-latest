<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * Various support functions, useful or useless.  NB. THEY MUST NOT DEPEND
 * ELSEWHERE.
 *
 * @version $Id: support.inc.php,v 1.3 2004/11/08 12:59:52 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Delete element from array.
 */
function array_del($array, $place) {
	$newarray = array();
	$n=0;
	for ($i=0; $i<sizeof($array); $i++)
		if ($i!=$place) 
			$newarray[$n++] = $array[$i];
	return $newarray;
} 


/**
 * Swap values of two elements in array.
 */
function array_swapval ($array, $i, $j) {
	$temp[$i] = $array[$j];
	$temp[$j] = $array[$i];

	$array[$i] = $temp[$i];
	$array[$j] = $temp[$j];

	return $array;
}

/**
 * This plugin's error display function.
 * @todo use the one provided by Squirrelmail
 * @todo use new html class
 */
function print_errormsg($errormsg) {
	printheader2(_("Error Encountered"));
	print_all_sections_start();
	print_section_start(_("Error Encountered"));
	print $errormsg;
	print_section_end(); 
	print_all_sections_end();
	printfooter2();
	exit;
}

/**
 * Create new folder wrapper function for avelsieve.
 * @param string $foldername
 * @return string error message upon error, or the empty string upon success.
 */

function avelsieve_create_folder($foldername, $subfolder = '', $created_mailbox_name = '') {
	/* Copy & paste magic (aka kludge) */
	global $mailboxlist, $delimiter;

	if(!isset($delimiter) && isset($_SESSION['delimiter'])) {
		$delimiter = $_SESSION['delimiter'];
	} else { /* Just in case... */
		$delimiter = sqimap_get_delimiter($imapConnection);
		$_SESSION['delimiter'] = $delimiter;
	}

	if(isset($foldername) && trim($foldername) != '' ) {
		$foldername = imap_utf7_encode_local(trim($foldername));
	} else {
		return _("You have not defined the name for the new folder.") .
				' ' . _("Please try again.");
	}

	if(empty($subfolder)) {
		$subfolder = "INBOX";
	}

	if (strpos($foldername, "\"") || strpos($foldername, "\\") ||
	strpos($foldername, "'") || strpos($foldername, "$delimiter")) {
		return _("Illegal folder name.  Please select a different name"); 
	}

	if (isset($contain_subs) && $contain_subs ) {
		$foldername = "$foldername$delimiter";
	}

	// $folder_prefix = "INBOX";
	if (isset($folder_prefix) && (substr($folder_prefix, -1) != $delimiter)) {
		$folder_prefix = $folder_prefix . $delimiter;
	}
	if ($folder_prefix && (substr($subfolder, 0, strlen($folder_prefix)) != $folder_prefix)){
		$subfolder_orig = $subfolder;
		$subfolder = $folder_prefix . $subfolder;
	} else {
		$subfolder_orig = $subfolder;
	}
	if (trim($subfolder_orig) == '') {
		$mailbox = $folder_prefix.$folder_name; 
	} else {
		$mailbox = $subfolder.$delimiter.$folder_name;
	}
	/*    if (strtolower($type) == 'noselect') {
	        $mailbox = $mailbox.$delimiter;
	    }
	*/
	/* Actually create the folder. */
	sqgetGlobalVar('key', $key, SQ_COOKIE);
	sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
		
	$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);

	/* Here we could do some more error checking to see if the
	 * folder already exists. If it exists, the creation will not
	 * do anything ANW, so it works well as it is. It can be made
	 * better, e.g. by printing a notice "Note that the folder you
	 * wanted to create already exists". */
	
	// $boxes = sqimap_mailbox_list($imapConnection);

	/* Instead of the following line, I use sqimap_run_command so
	 * that I will put 'false' in the error handling. */

	// sqimap_mailbox_create($imapConnection, $mailbox, '');

	$read_ary = sqimap_run_command($imapConnection, "CREATE \"$mailbox\"", false, $response, $message);
   		sqimap_subscribe ($imapConnection, $mailbox);
	$created_mailbox_name = $mailbox;
	return '';
}

/**
 * Print mailbox select widget.
 * 
 * @param string $selectname name for the select HTML variable
 * @param string $selectedmbox which mailbox to be selected in the form
 * @param boolean $sub 
 */
function mailboxlist($selectname, $selectedmbox, $sub = false) {
	
	global $boxes_append, $boxes_admin, $imap_server_type,
	$default_sub_of_inbox;
	
		if(isset($boxes_admin) && $sub) {
			$boxes = $boxes_admin;
		} elseif(isset($boxes_append)) {
			$boxes = $boxes_append;
		} else {
			global $boxes;
		}
		
		if (count($boxes)) {
	    	$mailboxlist = '<select name="'.$selectname.'" onclick="checkOther(\'5\');" >';
		
	    	if($sub) {
			if ($default_sub_of_inbox == false ) {
				$mailboxlist = $mailboxlist."\n".'<option selected value="">[ '._("None")." ] </option>\n";	
			}
	    	}
	
	    	for ($i = 0; $i < count($boxes); $i++) {
	            	$box = $boxes[$i]['unformatted-dm'];
	            	$box2 = str_replace(' ', '&nbsp;', imap_utf7_decode_local($boxes[$i]['unformatted']));
	            	//$box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);
	
	            	if (strtolower($imap_server_type) != 'courier' || strtolower($box) != 'inbox.trash') {
	                	$mailboxlist .= "<option value=\"$box\"";
				if($selectedmbox == $box) {
					$mailboxlist .= ' selected=""';
				}
				$mailboxlist .= ">$box2</option>\n";
	            	}
	    	}
	    	$mailboxlist .= "</select>\n";
	
		} else {
	    	$mailboxlist = "No folders found.";
		}
		return $mailboxlist;
	}
 
?>
