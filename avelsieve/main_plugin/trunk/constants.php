<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Copyright (c) 2002-2003 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * $Id: constants.php,v 1.9 2004/05/17 14:37:54 avel Exp $
 */

$types = array();

$types[1]= array(
	'order' => 0,
	'disabled' => true,
	'name' => _("Address Match"),
	'description' => _("Perform an action depending on email addresses appearing in message headers.")
);

$types[2]= array(
	'order' => 1,
	'name' => _("Header Match"),
	'description' => _("Perform an action on messages matching a specified header (From, To etc.).")
);

$types[3] = array(
	'order' => 2,
	'name' => _("Size"),
	'description' => _("Perform an action on messages depending on their size.")
);

$types[4] = array(
	'order' => 3,
	'name' => _("All Messages"),
	'description' => _("Perform an action on <strong>all</strong> incoming messages.")
);

$types[5] = array(
	'disabled' => true,
	'order' => 4,
	'name' => _("Body Match"),
	'description' => _("Perform an action on messages depending on their content (body text)."),
	'dependencies' => array("body")
);


$actions[1] = array(
	'name' => _("Keep (Default action)") 
);
$actions[2] = array(
	'name' => _("Discard Silently") 
);
$actions[3] = array(
	'name' => _("Reject, sending this excuse to the sender:") 
);
$actions[4] = array(
	'name' => _("Redirect to the following email address:") 
);
$actions[5] = array(
	'name' => _("Move message into")
);


$matchtypes = array(
"contains" => _("contains"),
"does not contain" => _("does not contain"),
"is" => _("is"),
"is not" => _("is not"),
"matches" => _("matches") . " " . _("wildcard"),
"does not match" => _("does not match") . " " . _("wildcard")
);

$matchregex = array(
"regex" => _("matches") . " " . _("regexp"),
"not regex" => _("does not match") . " " . _("regexp")
);


$comparators = array(
'gt' => ">  " . _("is greater than"),
'ge' => "=> " . _("is greater or equal to"),
'lt' => "<  " . _("is lower than"),
'le' => "<= " . _("is lower or equal to"),
'eq' => "=  " . _("is equal to"),
'ne' => "!= " . _("is not equal to")
) ;
// gt" / "ge" / "lt""le" / "eq" / "ne"


$displaymodes = array(
	'verbose' => _("verbose"),
	'terse' => _("terse")
	);

$implemented_capabilities = array("fileinto", "reject", "vacation", "imapflags", "relational", "regex", "notify");

$cap_dependencies['relational'] = array("comparator-i;ascii-numeric");

$notifystrings = array(
'sms' => _("Mobile Phone Message (SMS)") ,
'mailto' => _("Email notification") ,
'zephyr' => _("Notification via Zephyr") ,
'icq' => _("Notification via ICQ")
);

$prioritystrings = array(
'low' => _("Low"),
'normal' => _("Normal"),
'high' => _("High")
);

/* Tools (Icons in table.php) */

$fmt = 'gif';

$avelsievetools = array(
	'rm' => array(
		'desc' => _("Delete"),
		'img' => "del.$fmt"
		),
	'edit' => array(
		'desc' => _("Edit"),
		'img' => "edit.$fmt"
		),
	'dup' => array(
		'desc' => _("Duplicate"),
		'img' => "dup.$fmt"
		),
	'mvup' => array(
		'desc' => _("Move Up"),
		'img' => "up.$fmt"
		),
	'mvtop' => array(
		'desc' => _("Move to Top"),
		'img' => "top.$fmt"
		),
	'mvdn' => array(
		'desc' => _("Move Down"),
		'img' => "down.$fmt"
		),
	'mvbottom' => array(
		'desc' => _("Move to Bottom"),
		'img' => "bottom.$fmt"
		)
);
	

if($spamrule_enable==true) {

if(in_array('junkfolder', $plugins)) {
	include SM_PATH . 'plugins/junkfolder/config.php';
	if(in_array('ldapuserdata', $plugins)) {
		$jd = getpref($data_dir, $username, 'junkprune');
		if(isset($jd) && $jd > 0) {
			$junkfolder_days = $jd;
		} else {
			$junkprune_saveme = true;
		}
	}
} else {
	$junkfolder_days = 7; /* Dummy default for E_ALL */
}


$spamrule_actions = array(
	'junk' => array(
		'short' => _("Junk Folder"),
		'desc' => sprintf( _("Store SPAM message in your Junk Folder. Messages older than %s days will be deleted automatically."), $junkfolder_days) . ' ' . _("Note that you can set the number of days in Folder Preferences.")
		),
	'trash' => array(
		'short' => _("Trash Folder"),
		'desc' => _("Store SPAM message in your Trash Folder. You will have to purge the folder yourself.")
		),

	'discard' => array(
		'short' => _("Discard"),
		'desc' => _("Discard SPAM message. You will get no indication that the message ever arrived.")
		)
);

}

/* Version Info for SIEVE scripts */

$avelsieve_version = array(
	'major' => 0,
	'minor' => 9,
	'release' => 10,
	'string' => "0.9.11"
);

global $implemented_capabilities, $cap_dependencies;  

?>
