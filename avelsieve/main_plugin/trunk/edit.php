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
 * $Id: edit.php,v 1.6 2004/03/26 18:28:26 avel Exp $
 */

/* edit.php: Editing existing rules. */

define('AVELSIEVE_DEBUG',0);

define('SM_PATH','../../');
require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'functions/imap.php');

include "config.php";
require_once "avelsieve_support.inc.php";
require_once "table_html.php";
require_once "addrule_html.php";
require_once "buildrule.php";
require_once "process_input.php";
require_once "sieve.php";

sqsession_is_active();

if(isset($_GET['edit'])) {
	$edit = $_GET['edit'];
} elseif(isset($_POST['edit'])) {
	$edit = $_POST['edit'];
}

if(isset($_GET['dup']) || isset($_POST['dup'])) {
	$dup = true;
}

/* Have this handy: type of current rule */
$type = $_SESSION['rules'][$edit]['type'];

/* and the rule itself */
$rule = $_SESSION['rules'][$edit];

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

} elseif(isset($_POST['changetype'])) {

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

include "constants.php";

/* N.B. Compatibility code removed. */

//print "<pre>EDIT: "; print_r($_SESSION['rules'][$edit]); print "</pre>";
//print "<pre>session: "; print_r($_SESSION); print "</pre>";

print '<form name="addrule" action="edit.php" method="POST">';
print '<input type="hidden" name="edit" value="'.$edit.'" />';

$titlestring =  _("Editing Mail Filtering Rule");
$titlestring .= ' #'. ($edit+1);
printheader2( $titlestring);
print_all_sections_start();



/* --------------------- 'if' ----------------------- */

print_section_start( _("Condition") );
/* this is for the drop-down box */

/*

print '<p align="center">' . _("Rule Type") . ': <select name="changetype">';

foreach($types as $i=>$tp) {
	if(isset($tp['disabled'])) {
		continue;
	}
		
	if(array_key_exists("dependencies", $tp)) {
		foreach($tp['dependencies'] as $no=>$dep) {
			if(!avelsieve_capability_exists($dep)) {
				continue 2;
			}
		}
	}
	
	print '<option value="'.$i.'" ';

	if($type == $i) {
		print 'selected=""';
	}

	print '>'. $tp['name'] .'</option>';
}
print '</select>';

print ' <input type="submit" name="changetype" value="'._("Change Type").'" /> </p>';
*/


switch ($type) { 
	case 1: 
		print "not implemented yet. :)";
		break;
		
	case 2:			/* header */
		if(!isset($items)) {
			$items = sizeof($rule['header']) + 1;
			print '<input type="hidden" name="items" value="'.$items.'" />';
		}
		print_2_2_headermatch($items);
		break;		
		
	case 3: 		/* size */
		print_2_3_sizematch();
		break;
		
	case 4: 		/* All messages */
		print_2_4_allmessages();
		break;
		
}
print_section_end();


/* --------------------- 'then' ----------------------- */

print_section_start( _("Action") );

if(isset($rule['folder'])) {
	$selectedmailbox = $rule['folder'];
}

/* TODO - Remove this and add new folder creation in edit.php as well. */
$createnewfolder = false; 

print_3_action();

/* End main */
print_section_end();

print '<tr><td><div style="text-align: center">';

if(isset($dup)) {
	print '<input type="hidden" name="dup" value="1" />';
	print '<input type="submit" name="addnew" value="'._("Add New Rule").'" />';
} else {
	print '<input type="submit" name="apply" value="'._("Apply Changes").'" />';
}

print '<input type="submit" name="cancel" value="'._("Cancel").'" />';
print '</div></form></td></tr>';

print_all_sections_end();
printfooter2();

?>
