<?php
/**
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
 * $Id: table.php,v 1.14 2004/11/03 11:24:07 avel Exp $
 *
 * @version $Id: table.php,v 1.14 2004/11/03 11:24:07 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * table.php: main routine that shows a table of all the rules and allows
 * manipulation.
 */

define('AVELSIEVE_DEBUG',0);

define('SM_PATH','../../');
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
include_once(SM_PATH . 'functions/page_header.php');
include_once(SM_PATH . 'functions/imap.php');
include_once(SM_PATH . 'functions/date.php');

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_rulestable.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');

sqsession_is_active();

sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

sqgetGlobalVar('authz', $authz, SQ_SESSION);

sqgetGlobalVar('haschanged', $haschanged, SQ_SESSION);

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
	if(isset($cyrusadmins_map[$username])) {
		$bind_username = $cyrusadmins_map[$username];
	} else {
		$bind_username = $username;
	}
	
	$sieve=new sieve($imap_server, $sieveport, $bind_username, $acctpass, $authz, $preferred_mech);
} else {
	$sieve=new sieve($imap_server, $sieveport, $username, $acctpass, $username, $preferred_mech);
}


sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');
	
require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

if (!isset($rules)) {
	/* Login. But if the rules are cached, don't even login to SIEVE
	 * Server. */ 
	avelsieve_login();

	/* Get script list from SIEVE server. */

	if($sieve->sieve_listscripts()) {
		if(!isset($sieve->response)) {
			/* There is no SIEVE script on the server. */
			$sieve->sieve_logout();
			$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
			textdomain ('squirrelmail');
			displayPageHeader($color, 'None');
			$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
			textdomain ('avelsieve');
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

	/* Actually get the script 'phpscript' (hardcoded ATM). */

	$sievescript = '';
	unset($sieve->response);

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
}

unset($sieve->response);


/* On to the code that executes if phpscript exists or if a new rule has been
 * created. */

if ($logout) {
	/* Activate phpscript and log out. */
	avelsieve_login();

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

} elseif (isset($_POST['addrule'])) {
	header("Location: $location/addrule.php");
	exit;

} elseif (isset($_POST['addspamrule'])) {
	header("Location: $location/addspamrule.php");
	exit;
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
			} 
			$rules = array_values($rules2);
			$_SESSION['comm']['deleted'] = $_POST['selectedrules'];

		} elseif(isset($_GET['rm'])) {
			$rules2 = $rules;
			unset($rules2[$_GET['rule']]);
			$rules = array_values($rules2);
			$_SESSION['comm']['deleted'] = $_GET['rule'];
		}

		if(sizeof($rules) == "0") {
			if (!$conservative) {
				avelsieve_login();
				avelsieve_delete_script();
			}
			$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
			textdomain ('squirrelmail');
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
			sqsession_register($rules, 'rules');
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

	sqsession_register($rules, 'rules');
	
	/* Register changes to timsieved if we are not conservative in our
	 * connections with him. */

	if ($conservative == false && $rules) {
		$newscript = makesieverule($rules);
		avelsieve_login();
		avelsieve_upload_script($newscript);
	}
}	

if (isset($_SESSION['returnnewrule'])) { /* Get the new rule and put it in the script */
	
	$newrule = $_SESSION['returnnewrule'];
	// unserialize(base64_decode(urldecode($returnnewrule)));
	session_unregister('returnnewrule');

	// print "DEBUG: Adding new: ";	print_r($newrule);
	if (!is_array($rules)) {
		unset($rules);
		$rules[0] = $newrule;
	} else {
		$rules[] = $newrule;
	}
	$haschanged = true;
}

if( (!$conservative && isset($haschanged) ) ) {
	avelsieve_login();
	$newscript = makesieverule($rules);
	avelsieve_upload_script($newscript);
	if(isset($_SESSION['haschanged'])) {
		unset($_SESSION['haschanged']);
	}
}

if(isset($rules)) {
	$_SESSION['rules'] = $rules;
	$_SESSION['scriptinfo'] = $scriptinfo;
}

if(isset($sieve_loggedin)) {
	$sieve->sieve_logout();
}

/* -------------------- Presentation Logic ------------------- */

$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
textdomain ('squirrelmail');
displayPageHeader($color, 'None');
$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

//print "<pre>SESSION: "; print_r($_SESSION); print "</pre>";
//print "<pre>POST: "; print_r($_POST); print "</pre>";
//print "<pre>COOKIE: "; print_r($_COOKIE); print "</pre>";
//print "<pre>rules: "; print_r($rules); print "</pre>";

if(isset($_GET['mode'])) {
	if(array_key_exists($_GET['mode'], $displaymodes)) {
		$mode = $_GET['mode'];
	} else {
		$mode = $avelsieve_default_mode;
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
		$mode = $avelsieve_default_mode;
	}
}

$ht = new avelsieve_html_rules(&$rules, $mode);
echo $ht->rules_table();
echo $ht->table_footer();

?>
</body></html>
