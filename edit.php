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
 * @version $Id: edit.php,v 1.10 2004/11/11 14:29:13 avel Exp $
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

$errormsg = '';

sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
sqgetGlobalVar('key', $key, SQ_COOKIE);
sqgetGlobalVar('popup', $popup, SQ_GET);
sqgetGlobalVar('rules', $rules, SQ_SESSION);
sqgetGlobalVar('edit', $edit, SQ_GET & SQ_POST);
sqgetGlobalVar('dup', $dup, SQ_GET & SQ_POST);
sqgetGlobalVar('previoustype', $previoustype, SQ_POST);

if(isset($edit)) {
	/* Editing an existing rule */
	// print "/* Editing an existing rule */ ";
	$rule = &$rules[$edit];
	$type = $rule['type'];
} elseif(isset($_GET['type'])) {
	/* Adding a new rule through $_GET */
	// print " /* Adding a new rule through _GET */";
	$rule = process_input(SQ_GET, &$errrormsg);

} elseif(isset($_POST['type'])) {
	if(!isset($_GET['type'])) {
		$type = 0;
	} else {
		$type = $_GET['type'];
	}
	$rule = process_input(SQ_POST, &$errrormsg);
}

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
	header("Location: table.php");
	exit;

} elseif($changetype) {
	/* Changing of rule type */
	$rule['type'] = $_POST['type'];
	

} elseif(isset($_POST['apply']) && !$changetype) {
	/* Apply change in existing rule */
	$_SESSION['rules'][$edit] = process_input($type);

	/* Communication: */
	$_SESSION['comm']['edited'] = $edit;
	$_SESSION['haschanged'] = true;

	header('Location: table.php');
	exit;

} elseif(isset($_POST['addnew']) && !$changetype) {
	/* Add new rule */
 	$newrule = process_input($type);
     
	if(isset($dup)) {
		// insert moving rule in place
		array_splice($_SESSION['rules'], $_POST['edit']+1, 0, array($newrule));
		// Reindex
		$_SESSION['rules'] = array_values($_SESSION['rules']);
	} else {
		$_SESSION['rules'][] = $newrule;
	}

	/* Communication: */
	$_SESSION['comm']['edited'] = $edit;
	$_SESSION['comm']['new'] = true;
	$_SESSION['haschanged'] = true;

	header('Location: table.php');
	exit;
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

if(isset($popup)) {
	displayHtmlHeader('', $js);
} else {
	displayPageHeader($color, 'None');
	echo $js;
}

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

$ht = new avelsieve_html_edit('edit', $rule);

if(isset($edit)) {
	echo $ht->edit_rule($edit);
} else {
	echo $ht->edit_rule();
}
	
echo $ht->table_footer();

?>
</body></html>
