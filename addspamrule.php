<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Copyright (c) 2002-2003 Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * $Id: addspamrule.php,v 1.3 2003/12/18 12:25:54 avel Exp $
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


/* If we need to get spamrule RBLs from LDAP, then do so now. */

if(isset($_SESSION['spamrule_rbls'])) {
	$spamrule_rbls = $_SESSION['spamrule_rbls'];
} elseif(isset($spamrule_tests_ldap) && $spamrule_tests_ldap == true &&
   !isset($_SESSION['spamrule_rbls'])) {
	include_once('spamrulefunctions.php');
	$spamrule_rbls = avelsieve_askldapforrbls();
	$_SESSION['spamrule_rbls'] = $spamrule_rbls;
}


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

if(isset($_POST['stop']))  {
	$stop = $_POST['stop'];
}

/* Other stuff */
sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);

if(isset($_POST['finished'])) {
	/* get it together & save it */
	if($action == 'junk' && isset($_POST['junkprune_saveme'])) {
		/* Save previously unset (or zero) junkprune variable */
		setPref($data_dir, $username, 'junkprune', $_POST['junkprune_saveme']);
	}

	$newrule['type'] = 10;
	$newrule['tests'] = $tests;
	$newrule['score'] = $score;
	$newrule['action'] = $action;
	if(isset($stop)) {
		$newrule['stop'] = $stop;
	}
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

	/*
	include_once(SM_PATH . 'plugins/filters/filters.php');
	$spamfilters = load_spam_filters();
	*/

	print '<input type="hidden" name="spamrule_advanced" value="1" />';

	print '<ul>';

	print '<li><strong>';
	print _("Target Score");
	print '</strong></li>';
	
	/* If using sendmail LDAP configuration, get the sum of maximum score */
	if(isset($spamrule_rbls)) {
		$spamrule_score_max = 0;
		foreach($spamrule_rbls as $no=>$info) {
			if(isset($info['serverweight'])) {
				$spamrule_score_max += $info['serverweight'];
			}
		}
	}

	print '<p>'. sprintf( _("Messages with SPAM-Score higher than the target value, the maximum value being %s, will be considered SPAM.") , $spamrule_score_max ) . '<br />';

	
	print _("Target Score") . ': <input name="score" id="score" value="'.$score.'" size="4" /></p><br />';


	print '<li><strong>';
	print _("SPAM Lists to check against");
	print '</strong></li><p>';
	
	/**
	 * Print RBLs that are available in this system.
	 * 1) Check for RBLs in LDAP Sendmail configuration
	 * 2) Use RBLs supplied in config.php
	 */
	 
	if(isset($spamrule_rbls)) {
		/* from LDAP */
		foreach($spamrule_rbls as $no=>$info) {
			print '<input type="checkbox" name="tests[]" value="'.$info['test'].'" id="spamrule_test_'.$no.'" ';
			if(in_array($info['test'], $tests)) {
				print 'checked="" ';
			}
			print '/> ';
			print '<label for="spamrule_test_'.$no.'">'.$info['name'].' ('.$info['serverweight'].')</label><br />';
		}
			
	} elseif(isset($spamrule_tests)) {
		/* from config.php */
		foreach($spamrule_tests as $st=>$txt) {
			print '<input type="checkbox" name="tests[]" value="'.$st.'" id="spamrule_test_'.$st.'" ';
			if(in_array($st, $tests)) {
				print 'checked="" ';
			}
			print '/> ';
			print '<label for="spamrule_test_'.$st.'">'.$txt.'</label><br />';
		}
	/* TODO: Import spamrule_filters from Filters plugin */
	/*
	} elseif(isset($spamrule_filters)) {
	foreach($spamrule_filters as $st=>$fi) {
		print '<input type="checkbox" name="tests[]" value="'.$st.'" id="spamrule_test_'.$st.'" ';
		if(in_array($st, $tests)) {
			print 'checked="" ';
		}
		print '/> ';
		print '<label for="spamrule_test_'.$st.'">'$fi.['name'].'</label><br />';
	}
	*/
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

if(isset($junkprune_saveme)) {
	print '<input type="hidden" name="junkprune_saveme" value="'.$junkfolder_days.'" />';
}
	
	/* STOP */
	
	print '<br /><input type="checkbox" name="stop" id="stop" ';
	if(isset($stop)) {
		print 'checked="" ';
	}
	print '/> ';
	print '<label for="stop">';
	if ($useimages) {
		print '<img src="images/stop.gif" width="35" height="33" border="0" alt="';
		print _("STOP");
		print '" align="middle" /> ';
	} else {
		print "<strong>"._("STOP").":</strong> ";
	}
	print _("If this rule matches, do not check any rules after it.");
	print '</label>';
		

print_section_end();
print_all_sections_end();
printaddbuttons();
printfooter2();


?>
