<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * This page is the main interface to editing and adding new rules.
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: edit.php,v 1.17 2004/11/18 12:06:09 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2002-2004 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

define('AVELSIEVE_DEBUG',0);

define('SM_PATH','../../');
include_once(SM_PATH . 'include/validate.php');
include_once(SM_PATH . 'include/load_prefs.php');
include_once(SM_PATH . 'functions/page_header.php');
include_once(SM_PATH . 'functions/imap.php');

include_once(SM_PATH . 'plugins/avelsieve/config/config.php');
include_once(SM_PATH . 'plugins/avelsieve/include/support.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_rulestable.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_ruleedit.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_actions.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/process_user_input.inc.php');

sqsession_is_active();

$errmsg = '';

/* Session / Server vars */
sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('rules', $rules, SQ_SESSION);
/* Mode of operation */
sqgetGlobalVar('dup', $dup, SQ_GET & SQ_POST);
sqgetGlobalVar('addnew', $addnew, SQ_GET);
/* New folder Creation */
sqgetGlobalVar('newfoldername', $newfoldername, SQ_POST);
sqgetGlobalVar('newfolderparent', $newfolderparent, SQ_POST);
/* Essentials */
sqgetGlobalVar('popup', $popup, SQ_GET);
sqgetGlobalVar('items', $items, SQ_GET);
sqgetGlobalVar('edit', $edit, SQ_GET & SQ_POST);
sqgetGlobalVar('previoustype', $previoustype, SQ_POST);
sqgetGlobalVar('type', $type_get, SQ_GET);
sqgetGlobalVar('type', $type_post, SQ_POST);

if(!isset($rules)) {
	/* FIXME - Make Getting+Caching of rules a function. */
	sqgetGlobalVar('key', $key, SQ_COOKIE);
	sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
	sqgetGlobalVar('authz', $authz, SQ_SESSION);
	$acctpass = OneTimePadDecrypt($key, $onetimepad);

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


	avelsieve_login();
	if($sieve->sieve_listscripts()) {
		if(!isset($sieve->response)) {
			$rules = array();
		} elseif(is_array($sieve->response)){
			$i = 0;
			foreach($sieve->response as $line){
				$scripts[$i] = $line;
				$i++;
			}
			if(!in_array('phpscript', $scripts)) {
				$rules = array();
			} else {
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
		}
	}

	$_SESSION['rules'] = $rules;
	$_SESSION['scriptinfo'] = $scriptinfo;
}

if(isset($popup)) {
	$popup = '?popup=1';
} else {
	$popup = '';
}

/**
 * Create new mailbox, if required by the user.
 */
if($newfoldername) {
	$created_mailbox_name = '';
	avelsieve_create_folder($newfoldername, $newfolderparent, &$created_mailbox_name, &$errmsg);
}

if(isset($edit)) {
	/* Editing an existing rule */
	$rule = &$rules[$edit];
} elseif(!isset($edit) && isset($type_get)) {
	/* Adding a new rule through $_GET */
	$type = $type_get;
	$rule = process_input(SQ_GET, &$errmsg);
} else {
	/* Adding a new rule from scratch */
	$rule = array();
}

if(isset($type_post)) {
	$type = $type_post;
	$rule = process_input(SQ_POST, &$errmsg);
}

// print "<b>PREVIOUS = $previoustype , type = $type</b>";
if(isset($previoustype) && (
	$previoustype == 0 ||
	(isset($type) && $previoustype != $type)
	)) {
		$changetype = true;
} else {
		$changetype = false;
}

/* Available Actions that occur if submitting the form in a number of ways */

if(isset($_POST['append'])) {
	/* More header match items */
	$items = $_POST['items'] + 1;

} elseif(isset($_POST['less'])) {
	/* Less header match items */
	$items = $_POST['items'] - 1;

} elseif(isset($_POST['cancel'])) {
	/* Cancel Editing */
	header("Location: table.php$popup");
	exit;

} elseif($changetype) {
	// print "changing rule type";
	/* Changing of rule type */
	$rule['type'] = $_POST['type'];
	

} elseif(isset($_POST['apply']) && !$changetype) {
	/* Apply change in existing rule */
	$editedrule = process_input(SQ_POST, &$errmsg);
	if(empty($errmsg)) {
		$_SESSION['rules'][$edit] = $editedrule;
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['haschanged'] = true;
		header("Location: table.php$popup");
	}

} elseif(isset($_POST['addnew']) && !$changetype) {
	/* Add new rule */
 	$newrule = process_input(SQ_POST, &$errmsg);
	if(empty($errmsg)) {
		if(isset($dup)) {
			// insert moving rule in place
			array_splice($_SESSION['rules'], $edit+1, 0, array($newrule));
			// Reindex
			$_SESSION['rules'] = array_values($_SESSION['rules']);
		} else {
			$_SESSION['rules'][] = $newrule;
		}
		/* Communication: */
		$_SESSION['comm']['edited'] = $edit;
		$_SESSION['comm']['new'] = true;
		$_SESSION['haschanged'] = true;
		header("Location: table.php$popup");
		exit;
	}
}



/* Grab the list of my IMAP folders. This is only needed for the GUI, and is
 * done as the last step. */
sqgetGlobalVar('delimiter', $delimiter, SQ_SESSION);
if(!isset($delimiter)) {
	$delimiter = sqimap_get_delimiter($imapConnection);
}
// $folder_prefix = "INBOX";
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0); 
$boxes = sqimap_mailbox_list($imapConnection);
sqimap_logout($imapConnection); 

/* ---------------------- Start main ----------------------- */

$js = '
<script language="JavaScript" type="text/javascript">
function checkOther(id){
	for(var i=0;i<document.addrule.length;i++){
		if(document.addrule.elements[i].value == id){
			document.addrule.elements[i].checked = true;
		}
	}
}
function el(id) {
  if (document.getElementById) {
    return document.getElementById(id);
  }
  return false;
}

function ShowDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "";
  }
  return false;
}
function HideDiv(divname) {
  if(el(divname)) {
    el(divname).style.display = "none";
  }
}
function ToggleShowDiv(divname) {
  if(el(divname)) {
    if(el(divname).style.display == "none") {
      el(divname).style.display = "";
	} else {
      el(divname).style.display = "none";
	}
  }	
}
</script>
';

displayHtmlHeader('', $js);

/* Used to be like this: */
/*
if(isset($popup)) {
	displayHtmlHeader('', $js);
} else {
	displayPageHeader($color, 'None');
	echo $js;
}
*/

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

if(isset($dup)) {
	$mode = 'duplicate';
} elseif(isset($addnew)) {
	$mode = 'addnew';
} else {
	$mode = 'edit';
}

if(isset($errmsg) && $errmsg) {
}

$ht = new avelsieve_html_edit($mode, $rule, $popup, $errmsg);

if(isset($edit)) {
	echo $ht->edit_rule($edit);
} else {
	echo $ht->edit_rule();
}
	
echo $ht->table_footer();

?>
</body></html>
