<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4
 *
 * Copyright (c) 2002 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Based on Dan Ellis' test scripts that came with sieve-php.lib
 * <danellis@rushmore.com> <URL:http://sieve-php.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * $Id: table.php,v 1.3 2003/10/09 11:25:37 avel Exp $
 */

/* table.php: main routine that shows a table of all the rules and allows
 * manipulation. */

define('AVELSIEVE_DEBUG',0);

define('SM_PATH','../../');
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');
require_once(SM_PATH . 'functions/date.php');

include "config.php";
include_once "avelsieve_support.inc.php";
include_once "table_html.php";
include_once "getrule.php";
include_once "buildrule.php";
include_once "sieve.php";

sqsession_is_active();

sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('authz', $authz, SQ_SESSION);

if(isset($_SESSION['part'])) {
	session_unregister('part');
}

if(isset($_SESSION['sess'])) {
	session_unregister('sess');
}

$location = get_location();

/* Need the cleartext password to login to timsieved */
$acctpass = OneTimePadDecrypt($key, $onetimepad);

sqgetGlobalVar('rules', $rules, SQ_SESSION);
sqgetGlobalVar('scriptinfo', $scriptinfo, SQ_SESSION);
sqgetGlobalVar('logout', $logout, SQ_POST);

if(isset($authz)) {
	$imap_server =  sqimap_get_user_server ($imapServerAddress, $authz);
	
} else {
	$imap_server =  sqimap_get_user_server ($imapServerAddress, $username);

	if ($imapproxymode == true) { /* Need to do mapping so as to connect directly to server */
		$imap_server = $imapproxyserv[$imap_server];
	}
}

if(isset($authz)) {
	$sieve=new sieve($imap_server, $sieveport, $username, $acctpass, $authz, $preferred_mech);
} else {
	$sieve=new sieve($imap_server, $sieveport, $username, $acctpass, $username, $preferred_mech);
}


sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

	
/* Login. But if the rules are cached, don't even login to SIEVE Server. */ 
if (!isset($rules)) {

	if ($sieve->sieve_login()){	/* User has logged on */
		if(!isset($sieve_capabilities)) {
			$sieve_capabilities = $sieve->sieve_get_capability();
			// sqsession_register($sieve_capabilities, 'sieve_capabilities');
			 $_SESSION['sieve_capabilities'] = $sieve_capabilities;
		}
	} else {
		$errormsg = _("Could not log on to timsieved daemon on your IMAP server");
		$errormsg .= " " . $imapServerAddress.".<br />";
		$errormsg .= _("Please contact your administrator.");
		print_errormsg($errormsg);
		exit;
	}
}


/* Get script list from SIEVE server. */

if (!isset($rules)) {

	if($sieve->sieve_listscripts() /* && isset($currentrules) */ ){
		if(!isset($sieve->response)) {
			/* There is no SIEVE script on the server. */
			displayPageHeader($color, 'None');
			printheader2( _("Current Mail Filtering Rules") );
			print_all_sections_start();
			print_section_start(_("No Filtering Rules Defined Yet"));
			print_create_new();
			print_section_end(); 
			print_all_sections_end();
			printfooter();
			printfooter2();
			exit;
			
		} elseif(is_array($sieve->response)){
			$i = 0;
			foreach($sieve->response as $line){
				$scripts[$i] = $line;
				$i++;
			}
			// print "Available scripts on server: "; print_r($scripts);

		} else {
			print "sieve-php.lib.php bug: listscripts() returned a string instead of an array.";
			exit;
		}
	}
}
/* If we got here, everything should be ok.*/

/* On to the code that executes if phpscript exists or if a new rule has been
 * created. */

if($logout) {
	/* Activate phpscript and log out. */
	
	if (!($sieve->sieve_login())){
		$errormsg = _("Could not log on to timsieved daemon on your IMAP server");
		$errormsg .= " " . $imapServerAddress.".<br />";
		$errormsg .= _("in order to save your rules.") . "<br />";
		$errormsg .= _("Please contact your administrator.");
		print_errormsg($errormsg);
		exit;
	}
	
	require_once("constants.php");

	if ($newscript = makesieverule($rules)) {

		avelsieve_upload_script($newscript);

		if(!($sieve->sieve_setactivescript("phpscript"))){
			/* Just to be safe. */
			$errormsg = _("Could not set active script on your IMAP server");
			$errormsg .= " " . $imapServerAddress.".<br />";
			$errormsg .= _("Please contact your administrator.");
			print_errormsg($errormsg);
			exit;
		}
		$sieve->sieve_logout();
	
	} else {
		/* upload a null thingie!!! :-) This works for now... some time
		 * it will get better. */
		avelsieve_upload_script(""); 
		/* if(sizeof($rules) == "0") {
			avelsieve_delete_script();
		} */
	}
	session_unregister('rules');
	
	header("Location: $location/../../src/options.php\n\n");

	// header("Location: $location/../../src/options.php?optpage=avelsieve\n\n");
	exit;

} elseif (isset($_POST['add'])) {
	/* code to start wizard to add a new rule */
	header("Location: $location/addrule.php");
	exit;

} elseif (isset($_POST['addspamrule'])) {
	/* code to start wizard to add a new spam rule */
	header("Location: $location/addspamrule.php");
	exit;
}


/* Actually get the script 'phpscript' (hardcoded ATM). */

if (!isset($rules)) {
	$sievescript = '';
	if($sieve->sieve_getscript("phpscript")){
		if(is_array($sieve->response)) {
			foreach($sieve->response as $line){
				$sievescript .= "$line\n";
			}
		} else {
			$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
			textdomain ('avelsieve');
			$errormsg = _("Could not get SIEVE script from your IMAP server");
			$errormsg .= " " . $imapServerAddress.".<br />";
			$errormsg .= _("(Probably the script is size null).");
			$errormsg .= _("Please contact your administrator.");
			print_errormsg($errormsg);
			exit;
		}
	}
	
	/* $sievescript has a SIEVE script. Parse that. */
	$scriptinfo = array();
	$rules = getruledata($sievescript, $scriptinfo);
	
	if ($rules == "0" && !isset($_SESSION['returnnewrule']) ) {
		/* NO SCRIPT, WHAT HAPPENS IF THERE IS NO SCRIPT. (Stop shouting). */

		displayPageHeader($color, 'None');
		$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
		textdomain ('avelsieve');
		printheader2(false);
		print_all_sections_start();
		print_section_start(_("No Filtering Rules Defined Yet"));
		print_create_new();
		print_section_end(); 
		print_all_sections_end();
		print_buttons();
		printfooter2();
		exit;
	}
}

/* Routine for Delete / Delete selected / edit / duplicate / moveup/down */
if(isset($_GET['rule']) || isset($_POST['deleteselected'])) {
	if (isset($_GET['edit'])) {
		header("Location: $location/edit.php?edit=".$_POST['rule']."");
		exit;

	} elseif (isset($_GET['dup'])) {
		header("Location: $location/edit.php?edit=".$_POST['rule']."&dup=1");
		exit;

	} elseif (isset($_GET['rm']) || ( isset($_POST['deleteselected']) && isset($_POST['selectedrules'])) ) {
		if (isset($_POST['deleteselected'])) {
			$rules2 = $rules;
			foreach($_POST['selectedrules'] as $no=>$sel) {
				unset($rules2[$sel]);
				// $rules = array_del($rules, $sel); 
			} 
			$rules = array_values($rules2);
			$_SESSION['comm']['deleted'] = $_POST['selectedrules'];

		} elseif(isset($_GET['rm'])) {
			$rules = array_del($rules, $_GET['rule']);
			$_SESSION['comm']['deleted'] = $_GET['rule'];
		}


		if(sizeof($rules) == "0") {
			// print "DEBUG: Ok, size of rules is 0 apparently.";
	
			if (!$conservative) {
				avelsieve_delete_script();
			}
			session_unregister('rules');
			displayPageHeader($color, 'None');
			$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
			textdomain ('avelsieve');
			printheader2(false);
			print_all_sections_start();
			print_section_start(_("All your rules have been deleted"));
			print_create_new();
			print_section_end(); 
			print_all_sections_end();
			print_buttons();
			print_footer();
			printfooter2();
			exit;
		} 


	} elseif (isset($_GET['mvup'])) {
		$rules = array_swapval($rules, $_GET['rule'], $_GET['rule']-1);

	} elseif (isset($_GET['mvdn'])) {
		$rules = array_swapval($rules, $_GET['rule'], $_GET['rule']+1);
	
	} elseif (isset($_GET['mvtop'])) {

		/* Rule to get to the top: */
		$ruletop = $rules[$_GET['rule']];

		unset($rules[$_GET['rule']]);
		array_unshift($rules, $ruletop);

	} elseif (isset($_GET['mvbottom'])) {
		
		/* Rule to get to the bottom: */
		$rulebot = $rules[$_GET['rule']];
		
		unset($rules[$_GET['rule']]);
		
		/* Reindex */
		$rules = array_values($rules);

		/* Now Append it */
		$rules[] = $rulebot;

	}

	session_register('rules');
	
	/* Register changes to timsieved if we are not conservative in our
	 * connections with him. */

	if (!($conservative && $rules)) {
		$newscript = makesieverule($rules);
		avelsieve_upload_script($newscript);
	}
}	

if (isset($_SESSION['returnnewrule'])) { /* Get the new rule and put it in the script */
	
	$newrule = $_SESSION['returnnewrule'];
	// unserialize(base64_decode(urldecode($returnnewrule)));
	session_unregister('returnnewrule');

	//print "DEBUG: Adding new: ";	print_r($newrule);
	
	if (!is_array($rules)) {
		unset($rules);
		$rules[0] = $newrule;
	} else {
		$rules[] = $newrule;
	}
	
	if ($conservative == false) {
		$newscript = makesieverule($rules);
		avelsieve_upload_script($newscript);
	} 
}

$_SESSION['rules'] = $rules;

$_SESSION['scriptinfo'] = $scriptinfo;

// $compose_new_win = getPref($data_dir, $username, 'compose_new_win');

$sieve->sieve_logout();

/* --------------------------------- main --------------------------------- */

/* Printing, part zero: Headers et al */
displayPageHeader($color, 'None');

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

require "constants.php";

//print "<pre>SESSION: "; print_r($_SESSION); print "</pre>";
//print "<pre>POST: "; print_r($_POST); print "</pre>";
//print "<pre>COOKIE: "; print_r($_COOKIE); print "</pre>";

if(isset($_GET['mode'])) {
	if(array_key_exists($_GET['mode'], $displaymodes)) {
		$mode = $_GET['mode'];
	} else {
		$mode = "verbose";
	}
	sqsession_register($mode, 'mode');
} else {
	if(isset($_SESSION['mode'])) {
		if(array_key_exists($_SESSION['mode'], $displaymodes)) {
			$mode = $_SESSION['mode'];
		} else {
			$mode = "verbose";
		}
	} else {
		$mode = "verbose";
	}
}

// print_my_header();
printheader2( _("Current Mail Filtering Rules") );
print_all_sections_start();

/*
print "DEBUG:<pre>";
print_r($_SESSION);
print_r($_POST);
print "</pre>";

( [version] => Array ( [major] => 0 [minor] => 9 [release] => 5 [string] => 0.9.5 )

*/


/* Printing, part one: the table with the rules. */

print '<form name="rulestable" method="POST" action="table.php">';

print_table_header();

$toggle = false;

for ($i=0; $i<sizeof($rules); $i++) {
	print "<tr";
	if ($toggle) {
		print ' bgcolor="'.$color[12].'"';
	}
	print "><td>".($i+1)."</td><td>";
	print '<input type="checkbox" name="selectedrules[]" value="'.$i.'" /></td><td>';
	print makesinglerule($rules[$i], $mode);
	print '</td><td style="white-space: nowrap"><p>';

	/* print '</td><td><input type="checkbox" name="rm'.$i.'" value="1" /></td></tr>'; */
	/* Edit */
	avelsieve_print_toolicon ("edit", $i, "edit.php", "");
	// was: print '<a href="edit.php?rule='.$i.'&amp;edit='.$i.'">';

	/* Duplicate */
	avelsieve_print_toolicon ("dup", $i, "edit.php", "edit=$i&amp;dup=1");
	// CHECKME: was print ' <a href="edit.php?rule='.$i.'&amp;edit='.$i.'&amp;dup=1">';

	/* Delete */
	avelsieve_print_toolicon ("rm", $i, "table.php", "");
	// was: print ' <a href="table.php?rule='.$i.'&amp;rm=">';

	/* Move up / Move to Top */
	if ($i != 0) {
		if($i != 1) {
			avelsieve_print_toolicon ("mvtop", $i, "table.php", "");
		}
		avelsieve_print_toolicon ("mvup", $i, "table.php", "");
	}

	/* Move down / to bottom */
	if ($i != sizeof($rules)-1 ) {
		avelsieve_print_toolicon ("mvdn", $i, "table.php", "");
		if ($i != sizeof($rules)-2 ) {
			avelsieve_print_toolicon ("mvbottom", $i, "table.php", "");
		}
	}

	print '</p></td></tr>';

	if(!$toggle) {
		$toggle = true;
	} elseif($toggle) {
		$toggle = false;
	}
}


print '<tr><td colspan="4">';
	print '<table width="100%" border="0"><tr><td align="left">';
	print '<input type="submit" name="deleteselected" value="' . _("Delete Selected") . '" /> '; 
	print '</td><td align="right">';
	print_addnewrulebutton();
	print '</td></tr></table>'; 
print '</td></tr>';

print_table_footer();
print '</form>';
// print_buttons();

print_all_sections_end();
print_footer();

printfooter2();
//textdomain('squirrelmail');

?>
