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
 * $Id: config_sample.php,v 1.2 2003/10/07 13:24:52 avel Exp $
 */

/* Configuration parameters for SIEVE mail filters (aka avelsieve) */

/* Port where timsieved listens on the Cyrus IMAP server. Default is 2000. */

$sieveport = 2000;

/* Use images for the move up / down, delete rule buttons and STOP? */

$useimages = true;

/* **** NEW OPTION (as of 0.9.4) ****
 * Space separated list of preferred SASL mechanisms for the authentication to
 * timsieved */

$preferred_mech = "PLAIN";
//$preferred_mech = "PLAIN DIGEST-MD5";


/* **** NEW OPTION (as of 0.9.6) *****  Enable ImapProxy mode.
 * If you use imapproxy, because imapproxy cannot understand and proxy the
 * SIEVE protocol, you must connect to the SIEVE daemon (usually on the IMAP
 * server) itself. So you need to set $imapproxymode to true, and define a
 * mapping, from the imapproxy host (usually localhost) to your real IMAP
 * server (usually the same that is defined on Imapproxy's configuration). */

$imapproxymode = false;
$imapproxyserv = array(
	'localhost' => 'imap.example.org'
);

/* Translate the messages returned by the "Reject" and "Vacation" actions? The
 * default behaviour since 0.9 is not to translate them. Change to true if in
 * an intranet environment or in a same-language environment. */

$translate_return_msgs = false;

/* Theme to use for the images. A directory with the same name must exist under
 * plugins/avelsieve/$imagetheme, that contains the files: up.png, down.png,
 * del.png, dup.png, edit.png, top.png, bottom.png. */

$imagetheme = 'bluecurve_24x24';
//$imagetheme = 'bluecurve_16x16';

/* Beta feature: Enable Create New Folder routine in step #3 of adding a new
 * rule? */

$createnewfolder = true;

/* Number of items to display _initially_, when displaying the header match
 * rule */

$startitems = 3;

/* Maximum number of items to allow in one header match rule. */

$maxitems = 10;

/* Headers to display in listbox widget, when adding a new header rule. */

$headers = array(
"From", "To", "Cc", "Bcc", "Subject", "Reply-To", "Sender", "List-Id",
 "MailingList", "X-ML-Name", "X-List", "X-Mailer", "X-MailingList",
 "X-Spam-Flag", "X-Spam-Status", "X-Priority", "Importance",
 "X-MSMail-Priority");

/* Available :method's for the :notify extension (if applicable) */
$notifymethods = array(
"mailto", "sms"
);
/* use the value "false" if you want to provide a simple input box so that
 * users can edit the method themselves : */
//$notifymethods = false;


/* Capabilities to disable. If you would like to force avelsieve not to display
 * certain features, even though there _is_ a capability for them by
 * Cyrus/timsieved, you should specify these here. For instance, if you would
 * like to disable the notify extension, even though timsieved advertises it,
 * you should add 'notify' in this array: $force_disable_avelsieve_capability =
 * array("notify");. This will still leave the defined feature on, and if the
 * user can upload her own scripts then she can use that feature; this option
 * just disables the GUI of it. Leave as-is (empty array) if you do not need
 * that.
 * 
 * Look in $implemented_capabilities array in constants.php for valid values */

// $disable_avelsieve_capabilities = array("notify");
$disable_avelsieve_capabilities = array();

/* Display Filters link in the top Squirrelmail header? */

$avelsieveheaderlink = true;

/* New option as of 0.9.7:
 * Ldapuserdata mode: Gets user's email addresses (including mailAlternate &
 * mailAuthorized) from LDAP Prefs Backend plugin's cache */

$ldapuserdatamode = false;


/* ------ Custom / Obsolete Settings (leave alone) ------ */

/* Obsolete (leave this here, though) */
$conservative = true;


?>
