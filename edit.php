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
 * $Id: edit.php,v 1.9 2004/11/08 12:59:52 avel Exp $
 */

/* edit.php: Editing existing rules. */

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

if(isset($_GET['edit'])) {
	$edit = $_GET['edit'];
} elseif(isset($_POST['edit'])) {
	$edit = $_POST['edit'];
} elseif(isset($_GET['addnew'])) {
	$addnew = true;
}

if(isset($_GET['dup']) || isset($_POST['dup'])) {
	$dup = true;
}


if(isset($_SESSION['rules'])) {
	$rules = $_SESSION['rules'];
}
$rule = $rules[$edit];

/* Have this handy: type of current rule */
if(isset($addnew)) {
	$type = $_GET['type'];
} else {
	$type = $rules[$edit]['type'];
}

sqgetGlobalVar('key', $key, SQ_COOKIE);

/* Can (& should) replace above with this: */
//sqgetGlobalVar('edit', $edit, SQ_GET&SQ_POST);

sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

global $mailboxlist, $delimiter;


/* --------------- Start processing of variables ------------------- */

if(isset($_POST['append'])) {
	$items = $_POST['items'] + 1;

} elseif(isset($_POST['less'])) {
	$items = $_POST['items'] - 1;

} elseif(isset($_POST['cancel'])) {
	header("Location: table.php");
	exit;

} elseif(isset($_POST['type'])) {

	// eeer.... hmmmm.... o:-)


} elseif(isset($_POST['apply'])) {
	$no = $_POST['edit'];

	$_SESSION['rules'][$no] = process_input($type);

	/* Communication: */
	$_SESSION['comm']['edited'] = $no;

	$_SESSION['haschanged'] = true;

	header('Location: table.php');
	exit;

} elseif(isset($_POST['addnew'])) {
	$no = $_POST['edit'];
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
	$_SESSION['comm']['edited'] = $no;
	$_SESSION['comm']['new'] = true;
	
	$_SESSION['haschanged'] = true;

	header('Location: table.php');
	exit;
}


if(isset($_SESSION['delimiter'])) {
	$delimiter = $_SESSION['delimiter'];
} else { /* These aren't likely to be executed.. just in case... */
	$delimiter = sqimap_get_delimiter($imapConnection);
	$_SESSION['delimiter'] = $delimiter;
}


/* Grab the list of my IMAP folders */
// $folder_prefix = "INBOX";
$imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0); 
$boxes = sqimap_mailbox_list($imapConnection);
sqimap_logout($imapConnection); 

/* ---------------------- Start main ----------------------- */

displayPageHeader($color, 'None');

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

print '
<script language="JavaScript" type="text/javascript">
function checkOther(id){
	for(var i=0;i<document.addrule.length;i++){
		if(document.addrule.elements[i].value == id){
			document.addrule.elements[i].checked = true;
		}
	}
}
// -->
</script>
';

print '
<script language="JavaScript" type="text/javascript">
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
  // return tru;
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


require_once (SM_PATH . 'plugins/avelsieve/include/constants.inc.php');

$ht = new avelsieve_html_edit('edit', &$rules[$edit]);

echo $ht->edit_rule($edit);
echo $ht->table_footer();

?>
</body></html>
