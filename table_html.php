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
 */

/* HTML Functions for main GUI - table.php */

function print_header($customtitle) {
	
	print '<h1>'._("Server-Side Mail Filtering");
	
	if($customtitle) {
		print ' - '.$customtitle;
	}
	
	print '</h1>';

}

function print_my_header() {
	global $color;
	
	echo "<BR>\n".
	     "<table width=\"100%\">\n".
	        "<TR><td bgcolor=\"$color[0]\">\n".
	            "<CENTER><B>" . _("Server-Side Mail Filtering"). "</B></CENTER>\n".
	        "</TD></TR>\n".
	     "</TABLE>\n";
	
}

function printheader2($customtitle) {

	global $color;
	
	print '<br>
	<table bgcolor="'.$color[0].'" width="95%" align="center" cellpadding="2" cellspacing="0" border="0">
	<tr><td align="center">
	    <strong>'.
	    _("Server-Side Mail Filtering");
	    
		if($customtitle) {
			print ' - '.$customtitle;
		}
	 
	    print '</strong>
	
	    <table width="100%" border="0" cellpadding="5" cellspacing="0">
	    <tr><td bgcolor="'.$color[4].'" align="center">
	
	';


}

function printfooter2() {

	print '</td></tr></table>';
	print '</td></tr></table>';

}

function print_all_sections_start() {

	echo "<TABLE WIDTH=\"70%\" COLS=1 ALIGN=CENTER cellpadding=4 cellspacing=0 border=0>\n";

}

function print_all_sections_end() {

	echo "</table>";

}

function print_section_start($title) {

	global $color, $addrule_error;

	print "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><B>".
	     $title .
	     "</B></TD></TR>";

	if($addrule_error) {
		print '<TR><TD BGCOLOR="'.$color[2].'" ALIGN="CENTER"><p><font color="'.$color[8].'"><strong>'.
		$addrule_error .
	'</strong></font></TD></TR>';
	
	}

	print "<TR><TD BGCOLOR=\"$color[0]\" >";

}

function print_section_end() {

	global $color;
	
	echo "</TD></TR>\n";
	//echo "</table>";
	echo "<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr>\n";
	
}

function print_create_new() {

	print ' <p>';
	print _("Here you can add or delete filtering rules for your email account. These filters will always apply to your incoming mail, wherever you check your email.");
	print '</p>';
	
	print "<p>" . _("You don't have any rules yet. Feel free to add any with the button &quot;Add a New Rule&quot;. When you are done, please select &quot;Save Changes&quot; to get back to the main options screen.") . "</p>";

}

function print_table_header() {
	
	global $color, $conservative, $displaymodes, $mode, $scriptinfo;
	
	print " <p>"._("Here you can add or delete filtering rules for your email account. These filters will always apply to your incoming mail, wherever you check your email.")."</p> ";
	
	if($conservative) {
		print "<p>"._("When you are done with editing, <strong>remember to select &quot;Save Changes&quot;</strong> to activate your changes!")."</p>";
	}

	/* Print the 'communication' string from the previous screen */

	if(isset($_SESSION['comm'])) {
		print '<p><font color="'.$color[2].'">';
	
		if(isset($_SESSION['comm']['new'])) {
			print _("Successfully added new rule.");
	
		} elseif (isset($_SESSION['comm']['edited'])) {
			print _("Successfully updated rule #");
			print $_SESSION['comm']['edited']+1;
	
		} elseif (isset($_SESSION['comm']['deleted'])) {
			if(is_array($_SESSION['comm']['deleted'])) {
				print _("Successfully deleted rules #");
				for ($i=0; $i<sizeof($_SESSION['comm']['deleted']); $i++ ) {
					print $_SESSION['comm']['deleted'][$i] +1;
					if($i != (sizeof($_SESSION['comm']['deleted']) -1) ) {
						print ", ";
					}
				}
			} else {
				print _("Successfully deleted rule #");
				print $_SESSION['comm']['deleted']+1;
			}
		}
	
		print '</font></p>';
		session_unregister('comm');
	
	}

	if(isset($scriptinfo['created'])) {
		avelsieve_print_scriptinfo();
	}
	
	print "<p>"._("The following table summarizes your current mail filtering rules.")."</p>";
	
	/* NEW*/
	print '
	<form name="actionform" method="POST" action="table.php">
	
	<table cellpadding="3" cellspacing="2" border="0" align="center" valign="middle" width="97%" frame="box">
	<tr bgcolor="'.$color[0].'">
	<td nowrap="">';
	
	print _("No");
	
	print '</td><td>';
	print '</td><td>';
	
	print _("Description of Rule");
	
	print ' <small>(';
	print _("Display as:");
	
	
	foreach($displaymodes as $id=>$name) {
		if($mode == $id) {
			print ' <strong>'.$name.'</strong>';
		} else {
			print ' <a href="'.$_SERVER['SCRIPT_NAME'].'?mode='.$id.'">'.$name.'</a>';
		}
	}
	print ')</small>';
	
	print " </td><td>"._("Options")."</td></tr>";

}

function print_table_footer() {
	
	print '</table>';
	print '</form>';
}

function print_buttons () {

	print '<br /><div style="text-align: center;">';
	print_addnewrulebutton();
	/* <input name="del" value="Delete Selected Rules" type="submit" /> */
	print '</div>';

}

function print_buttons_new () {

	print '<br /><div style="text-align: center;">';
	print_addnewrulebutton();
	print '</div>';

}

function print_addnewrulebutton() {

	print '<form action="addrule.php" method="POST">';
	print '<input name="add" value="' . _("Add a New Rule") . '" type="submit" /> </form>';

}

function print_footer() {

	print '<div style="text-align: center;"><p>';
	print _("When you are done, please click the button below to return to your webmail.");
	print '</p><form action="table.php" method="POST"><input name="logout" value="';
	print _("Save Changes");
	print '" type="submit" /></form></div>';

}


/**
 * Print link for corresponding rule function (such as edit, delete, move).
 *
 * @param name str
 * @param i int
 * @param url str which page to link to
 * @param xtra str extra stuff to be passed to URL
 */
function avelsieve_print_toolicon ($name, $i, $url = "table.php", $xtra = "") {
	global $useimages, $imagetheme, $location, $avelsievetools;

	$desc = $avelsievetools[$name]['desc'];
	$img = $avelsievetools[$name]['img'];

	if(empty($xtra)) {
		print ' <a href="'.$url.'?rule='.$i.'&amp;'.$name.'='.$i.'">';
	} else {
		print ' <a href="'.$url.'?rule='.$i.'&amp;'.$name.'='.$i.'&amp;'.$xtra.'">';
	}

	if($useimages) {
		print '<img title="'.$desc.'" src="'.$location.'/images/'.$imagetheme.
		'/'.$img.'" alt="'.$desc.'" value="'.$desc.'" border="0" />';
	} else {
		print " | ". $desc;
	}
	print '</a>';
}

/**
 * Print script information (last modification date etc.)
 */
function avelsieve_print_scriptinfo() {
	global $scriptinfo;

	if(function_exists('getLongDateString')) {
		bindtextdomain('squirrelmail', SM_PATH . 'locale');
		textdomain('squirrelmail');
		$cr = getLongDateString($scriptinfo['created']);
		$mo = getLongDateString($scriptinfo['modified']);
		bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
		textdomain ('avelsieve');
	
		print '<p><em>'._("Created:").'</em> '.$cr.'.<br /><em>'.
		_("Last modified:").'</em> <strong>'.$mo.'</strong></p>';
	
	} else {
		print '<p><em>'._("Created:").'</em> '.
		date("Y-m-d H:i:s",$scriptinfo['created']).'. <em>'.
		_("Last modified:").'</em> <strong>'.
		date("Y-m-d H:i:s",$scriptinfo['modified']).'</strong></p>';
	}

	if(AVELSIEVE_DEBUG == 1) {
		global $avelsieve_version;
		print '<p>Versioning Information:</p>';
		print '<ul><li>Script Created using Version: '.$scriptinfo['version']['string'].'</li>'.
		'<li>Installed Avelsieve Version: '.$avelsieve_version['string'] .'</li></ul>';
	}
}

?>
