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
 * $Id: addspamrule.php,v 1.1 2003/10/09 16:17:45 avel Exp $
 */

/**
 * Wizard-like form for adding new spam rule.
 */
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
require_once "sieve.php";

sqsession_is_active();

if(isset($_POST['cancel'])) {
	header("Location: ./table.php");
	exit;

}  elseif(isset($_POST['spamrule_advanced'])) {
	$spamrule_advanced = $_POST['spamrule_advanced'];
}

$spamrule = true;
global $spamrule;


/* Spam Rule variables */

if(isset($_POST['tests'])) {
	$tests = $_POST['tests'];
} else {
	$tests = array_keys($spamrule_tests);
}

if(isset($_POST['score'])) {
	$score = $_POST['score'];
} else {
	$score = $spamrule_score_default;
}


if(isset($_POST['action']))  {
	$action = $_POST['action'];
} else {
	$action = $spamrule_action_default;
}


/* Other stuff */
sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

if(isset($_POST['finished'])) {
	/* get it together & save it */
	$newrule['type'] = 10;
	$newrule['tests'] = $tests;
	$newrule['score'] = $score;
	$newrule['action'] = $action;
	$_SESSION['comm']['new'] = true;
	$_SESSION['returnnewrule'] = $newrule;
	header('Location: table.php');
	exit;
}


	

/* ----------------- start printing --------------- */

$prev = bindtextdomain ('squirrelmail', SM_PATH . 'locale');
textdomain ('squirrelmail');

displayPageHeader($color, 'None');

$prev = bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
textdomain ('avelsieve');

include "constants.php";

printheader2( _("Add SPAM Rule") );
avelsieve_printheader();
print_all_sections_start();

print_section_start( _("Configure Anti-SPAM Protection") );

print '<p>' . _("All incoming mail is checked for unsolicited commercial content (SPAM) and marked accordingly. This special rule allows you to configure what to do with such messages once they arrive to your Inbox.") . '</p>';


if(!isset($spamrule_advanced)) {
	print '<p>'. sprintf( _("Select %s to add the predefined rule, or select the advanced SPAM filter to customize the rule."), '<strong>' . _("Add Spam Rule") . '</strong>' ) . '</p>';


	print '<p style="text-align:center"> <input type="submit" name="spamrule_advanced" value="'. _("Advanced Spam Filter...") .'" /></p>';

} else {

	print '<input type="hidden" name="spamrule_advanced" value="1" />';

	print '<ul>';

	print '<li><strong>';
	print _("Target Score");
	print '</strong></li>';

	print '<p>'. sprintf( _("Messages with SPAM-Score higher than the target value, the maximum value being %s, will be considered SPAM.") , $spamrule_score_max ) . '<br />';

	
	print _("Target Score") . ': <input name="score" id="score" value="'.$score.'" size="4" /></p><br />';


	print '<li><strong>';
	print _("SPAM Lists to check against");
	print '</strong></li><p>';
	
	foreach($spamrule_tests as $st=>$txt) {
		print '<input type="checkbox" name="tests[]" value="'.$st.'" id="spamrule_test_'.$st.'" ';
		if(in_array($st, $tests)) {
			print 'checked="" ';
		}
		print '/> ';
		print '<label for="spamrule_test_'.$st.'">'.$txt.'</label><br />';
	}
	


	print '</p><br /><li><strong>';
	print _("Action");
	print '</strong></li><br />';
	
	foreach($spamrule_actions as $ac=>$in) {
	
		if($ac == 'junk') {
			if(!in_array('junkfolder', $plugins)) {
				continue;
			}
		}
	
		print '<input type="radio" name="action" id="action_'.$ac.'" value="'.$ac.'" '; 
		if($action == $ac) {
			print 'checked="" ';
		}
		print '/> ';
	
		print ' <label for="action_'.$ac.'"><strong>'.$in['short'].'</strong> - '.$in['desc'].'</label><br />';
	}


	print '</ul>';

}

print_section_end();
print_all_sections_end();
printaddbuttons();
printfooter2();


?>
