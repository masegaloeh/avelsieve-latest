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

/**
 * This file contains functions that spit out HTML, mostly intended for use by
 * addrule.php and edit.php.
 */

/**
 * Start form.
 */
function avelsieve_printheader() {

	global $PHP_SELF;
	print '<form name="addrule" action="'.$PHP_SELF.'" method="POST">';

}


/**
 * Print bottom control and navigation buttons.
 */
function printaddbuttons() {

	global $part, $spamrule;

	print '<input name="reset" value="';
	print _("Clear this Form");
	print '" type="reset" />';

	if (isset($part) && $part != 1) {
		print '<input name="startover" value="';
		print _("Start Over");
		print '" type="submit" />';
	}

	print '<input name="cancel" value="';
	print _("Cancel");
	print '" type="submit" /><br />';
	
	if (isset($spamrule) && $spamrule == true ) {
		print '<input style="font-weight:bold" name="finished" value="';
		print _("Add SPAM Rule");
		print '" type="submit" />';
		return;
	}
	
	if (($part < 1) || ($part > 4)) {
		return;
	}

	if ($part!=1) {
		print '<input name="prev" value="&lt;&lt; ';
		print _("Move back to step");
		print ' '.($part-1).'" type="submit" />';
	}
	
	if ($part=="4") {
		print '<input style="font-weight:bold"  name="finished" value="';
		print _("Finished");
		print '" type="submit" />';
	} else {
		print '<input name="next" value="';
		print _("Move on to step");
		print ' '.($part+1).' &gt;&gt;" type="submit" />';
	}
}

/**
 * Print simple footer that closes tables, form and HTML.
 */
function printnakedfooter() {

	print '</td></tr></table> </form></body></html>';

}


/**
 * Print mailbox select widget.
 * 
 * @param string $selectname name for the select HTML variable
 * @param string $selectedmbox which mailbox to be selected in the form
 * @param boolean $sub 
 */
function printmailboxlist($selectname, $selectedmbox, $sub = false) {

	global $boxes_append, $boxes_admin, $imap_server_type,
	$default_sub_of_inbox;

	if(isset($boxes_admin) && $sub) {
		$boxes = $boxes_admin;
	} elseif(isset($boxes_append)) {
		$boxes = $boxes_append;
	} else {
		global $boxes;
	}
	
	if (count($boxes)) {
	    $mailboxlist = '<select name="'.$selectname.'" onclick="checkOther(\'5\');" >';
	

	    for ($i = 0; $i < count($boxes); $i++) {
	    
	    	if($sub) {
			if ($default_sub_of_inbox == false ) {
				echo '<option selected value="">[ '._("None")." ]\n";
			}
		}
        
	            $box = $boxes[$i]['unformatted-dm'];

	            $box2 = str_replace(' ', '&nbsp;', imap_utf7_decode_local($boxes[$i]['unformatted']));
	            //$box2 = str_replace(' ', '&nbsp;', $boxes[$i]['formatted']);

	            if (strtolower($imap_server_type) != 'courier' || strtolower($box) != 'inbox.trash') {
	                $mailboxlist .= "<option value=\"$box\"";
			if($selectedmbox == $box) {
				$mailboxlist .= ' selected=""';
			}
			$mailboxlist .= ">$box2</option>\n";
	            }
	    }
	    $mailboxlist .= "</select>\n";

	} else {
	    $mailboxlist = "No folders found.";
	}
	return $mailboxlist;

}

function print_1_ruletype() {

	global $types, $sieve_capabilities;

	print '<p>';
	print _("What kind of rule would you like to add?");
	print '</p>';

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
		if($i==2) {
			print '<input type="radio" name="type" id="type_'.$i.'" value="'.$i.'" checked="" /> ';
		} else {
			print '<input type="radio" name="type" id="type_'.$i.'" value="'.$i.'" /> ';
		}
		print '<label for="type_'.$i.'">';
		print $tp['name'];
		print '<br /><blockquote>';
		print $tp['description'];
		print '</blockquote>';
		print '</label>';
	}
}


function print_2_1_addressmatch() {

	print _("The rule will trigger if the following addresses appear anywhere in the message's headers:");
	/* TODO */

}


/*
 * Header match stuff functions.
 */

/**
 * Print listbox widget with available headers to choose from.
 *
 * @param $selected_header Selected header
 * @param $n option number
 */
function print_header_listbox($selected_header, $n) {

	global $headers;
	
	print '<select name="header['.$n.']">';
	
	foreach($headers as $head) {
		if ($head==$selected_header) {
			print '<option name="header['.$n.']"  value="'.$head.'" selected="">'.$head.':</option>';
		} else {
			print '<option name="header['.$n.']"  value="'.$head.'">'.$head.':</option>';
		}
	}
	
	print '</select>';
}

function print_matchtype_listbox($selected_matchtype, $n) {

	global $matchtypes, $comparators, $matchregex, $sieve_capabilities;
	reset($matchtypes);
	reset($comparators);
	reset($matchregex);
	
	print '<select name="matchtype['.$n.']">';
	
	while(list ($matchtype, $matchstring) = each ($matchtypes)) {
		if ($matchtype==$selected_matchtype) {
			print '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
		} else {
			print '<option value="'.$matchtype.'">'.$matchstring.'</option>';
		}
	}
	if(avelsieve_capability_exists('relational')) {
		while(list ($matchtype, $matchstring) = each ($comparators)) {
			if ($matchtype==$selected_matchtype) {
				print '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
			} else {
				print '<option value="'.$matchtype.'">'.$matchstring.'</option>';
			}
		}
	}
	if(avelsieve_capability_exists('regex')) {
		while(list ($matchtype, $matchstring) = each ($matchregex)) {
			if ($matchtype==$selected_matchtype) {
				print '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
			} else {
				print '<option value="'.$matchtype.'">'.$matchstring.'</option>';
			}
		}
	}
	print '</select>';
}

function print_condition_listbox($selected_condition) {

	$conditions = array(
		"and" => _("AND (Every item must match)"),
		"or" => _("OR (Either item will match)")
	);

	print _("The condition for the following rules is:");
	print '<select name="condition">';

	while(list ($condition, $conditionstring) = each ($conditions)) {
		if($condition==$selected_condition) {
			print '<option value="'.$condition.'" selected="">'.$conditionstring.'</option>';
		} else {
			print '<option value="'.$condition.'">'.$conditionstring.'</option>';
		}
	}
	print '</select>';

}

function print_2_2_headermatch($items) {

	global $HTTP_POST_VARS, $maxitems, $edit, $matchtypes, $comparators;
	
	print '<input type="hidden" name="items" value="'.$items.'" />';
	
	if(isset($edit)) {
		
		if(isset($_SESSION['rules'][$edit]['condition'])) {
			$condition = $_SESSION['rules'][$edit]['condition'];
		} else {
			$condition = "and"; /* FIXME */
		}
		if(isset($_SESSION['rules'][$edit]['header']))
			$header = $_SESSION['rules'][$edit]['header'];
		if(isset($_SESSION['rules'][$edit]['matchtype']))
			$matchtype = $_SESSION['rules'][$edit]['matchtype'];
		if(isset($_SESSION['rules'][$edit]['headermatch']))
			$headermatch = $_SESSION['rules'][$edit]['headermatch'];
	
	} else {
		if(isset($_POST['condition'])) {
			$condition = $_POST['condition'];
		} else {
			$condition = false;
		}
		if(isset($_POST['header'])) {
			$header = $_POST['header'];
		} else {
			$header = false;
		}
		if(isset($_POST['matchtype'])) {
			$matchtype = $_POST['matchtype'];
		} else {
			$matchtype = false;
		}
		if(isset($_POST['headermatch'])) {
			$headermatch = $_POST['headermatch'];
		} else {
			$headermatch = false;
		}
	}
	
	if($items > 1) {
		print_condition_listbox($condition);
	}
	
	print '<br /><ul>';
	
	for ( $n=0; $n< $items; $n++) {
	
		print '<li>';
		print _("The header ");
		if(isset($header[$n])) {
			print_header_listbox($header[$n], $n);
		} else {
			print_header_listbox("", $n);
		}
		
		if(isset($matchtype[$n])) {
			print_matchtype_listbox($matchtype[$n], $n);
		} else {
			print_matchtype_listbox("", $n);
		}
	
		print '<input type="text" name="headermatch['.$n.']" size="24" maxlength="40" value="';
		if(isset($headermatch[$n])) {
			print htmlspecialchars($headermatch[$n]);
		}
		print '" /></li><br />';
	
	} /* End for loop */
	
	print '</ul><br />';
	
	if($items > 1) {
		print '<input name="less" value="';
		print _("Less...");
		print '" type="submit" />';
	}
	
	if($items < $maxitems) {
		print '<input name="append" value="';
		print _("More...");
		print '" type="submit" />';
	}
	
}

function print_2_3_sizematch() {

	global $edit;
	
	print '<p>';
	print _("This rule will trigger if message is");
	
	print '<select name="sizerel"><option value="bigger" name="sizerel"';
	
	if(isset($edit)) {
		if($_SESSION['rules'][$edit]['sizerel'] == "bigger") {
			print ' selected=""';
		}
	}
	print '>';
	print _("bigger");
	print '</option><option value="smaller" name="sizerel"';
	if(isset($edit)) {
		if($_SESSION['rules'][$edit]['sizerel'] == "smaller") {
			print ' selected=""';
		}
	}
	print '>';
	print _("smaller");
	print '</option></select>';
	print _("than");
	
	print '<input type="text" name="sizeamount" size="10" maxlength="10" value="';
	
	if(isset($edit)) {
		print $_SESSION['rules'][$edit]['sizeamount'];
	} else {
		print '50';
	}
	print '" />
	<select name="sizeunit">
	<option value="kb" name="sizeunit';
	if(isset($edit)) {
		if($_SESSION['rules'][$edit]['sizeunit'] == "kb") {
			print ' selected=""';
		}
	}
	print '">';
	print _("KB (kilobytes)");
	print '</option><option value="mb" name="sizeunit"';
	if(isset($edit)) {
		if($_SESSION['rules'][$edit]['sizeunit'] == "mb") {
			print ' selected=""';
		}
	}
	print '">';
	print _("MB (megabytes)");
	print '</option></select></p>';
	
}
	
	
function print_2_4_allmessages() {

	print _("The following action will be applied to <strong>all</strong> incoming messages that do not match any of the previous rules.");

}

function print_2_5_bodymatch() {

	print '<p>';
	print _("This rule will trigger upon the occurrence of one or more strings in the body of an e-mail message. ");


}

function print_3_action_checkedaction($num, $selectedaction) {

	print '<input type="radio" name="action" id="action_'.$num.'" value="'.$num.'" ';
	if($selectedaction) {
		if($selectedaction == $num) {
			print ' checked=""';
		}
	}
	print '/> ';
}


function print_3_action() {

	/* Preferences from config.php */
	global $useimages, $translate_return_msgs;
	
	/* Data taken from addrule.php */
	global $boxes, $createnewfolder, $emailaddresses, $sieve_capabilities;
	
	/* If editing an existing rule */
	global $edit, $selectedmailbox;
	
	if(isset($edit)) {
		$selectedaction = $_SESSION['rules'][$edit]['action'];
	} else {
		$selectedaction = 1;
	}
	
	//print '<tr><td>'
	
	print '<p>';
	print _("Choose what to do when this rule triggers, from one of the following:");
	
	/*-*-*-*/
	
	print '</p>';
	print_3_action_checkedaction(1, $selectedaction);
	print '<label for="action_1">';
	print _("Keep (Default action)");
	print '</label>';
	print '<br />';
	
	/*-*-*-*/
	
	print_3_action_checkedaction(2, $selectedaction);
	print '<label for="action_2">';
	print _("Discard Silently");
	print '</label>';
	print '<br />';
	
	/*-*-*-*/
	
	if(avelsieve_capability_exists('reject')) {
	
		print_3_action_checkedaction(3, $selectedaction);
		print '<label for="action_3">';
		print _("Reject, sending this excuse to the sender:");
		print '</label>';

		print '<br /><blockquote><textarea name="excuse" rows="4" cols="50">';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['excuse']))
				print $_SESSION['rules'][$edit]['excuse'];
		} else {
			if($translate_return_msgs==true) {
				print _("Please do not send me large attachments.");
			} else {
				print "Please do not send me large attachments.";
			}
		}
		print '</textarea></blockquote><br />';
		
	}
	
	/*-*-*-*/
	
	print_3_action_checkedaction(4, $selectedaction);
	print '<label for="action_4">';
	print _("Redirect to the following email address:");
	print '</label>';

	print '<br /><blockquote><input type="text" name="redirectemail" size="26" maxlength="58" value="';
	if(isset($edit)) {
		if(isset($_SESSION['rules'][$edit]['redirectemail']))
			print $_SESSION['rules'][$edit]['redirectemail'];
	} else {
		print _("someone@example.org");
	}
	print '" /></blockquote><br />';
	
	/*-*-*-*/
	
	if(avelsieve_capability_exists('fileinto')) {
	
		print_3_action_checkedaction(5, $selectedaction);
		
		global $selectedmailbox;
		
		print '<label for="action_5">';
		print _("Move message into");
		print '</label>';
	
		if(isset($edit)) {
			/* The section here will be slightly different for the edit
			 * page. This part takes care of this. */
	
			print '<input type="hidden" name="newfolder" value="5a" onclick="checkOther(\'5\');" /> ';
			print _("the existing folder");
			print ' ';
			print printmailboxlist("folder", $selectedmailbox);
	
		} else {
			/* This is the section for the addrule part. Is it kludgy? Is
			 * it? IS IT? :-p */
	
			print '<br /><blockquote><input type="radio" name="newfolder" value="5a" checked="" onclick="checkOther(\'5\');" /> ';
			print _("the existing folder");
			print ' ';
			print printmailboxlist("folder", $selectedmailbox);
	
			if ($createnewfolder) {
	
				print '<br /><input type="radio" name="newfolder" value="5b" onclick="checkOther(\'5\');" /> ';
				print _("a new folder, named");
				print '<input type="text" size="25" name="folder_name" onclick="checkOther(\'5\');" /> ';
				print _("created as a subfolder of");
				print printmailboxlist("subfolder", false, true);
			}
		
		}
	
		if(avelsieve_capability_exists('imapflags')) {
		
			if(isset($edit)) {
				print '<blockquote>';
			}
		
			print '<br /><input type="checkbox" name="keepdeleted" id="keepdeleted" ';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['keepdeleted'])) {
					print 'checked="checked" ';
				}
			}
			print '/> ';
			print '<label for="keepdeleted">';
			print _("Also keep copy in INBOX, marked as deleted.");
			print '</label>';
		}
		
		print '</blockquote>';
		print '<br />';
	
	
	}
	
	/*-*-*-*/
	
	
	if(avelsieve_capability_exists('vacation')) {
	
		print_3_action_checkedaction(6, $selectedaction);
	
		global $emailaddresses;
	
		print '<label for="action_6">';
		print '<strong>&quot;';
		print _("Vacation");
		print '&quot;</strong>: ';
		print _("The notice will be sent only once to each person that sends you mail, and will not be sent to a mailing list address.");
		print '<br /><blockquote>';
		print '</label>';

		print _("Addresses: Only reply if sent to these addresses:");
		print '<input type="text" name="vac_addresses" value="';
	
		if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_addresses']) ) {
			print $_SESSION['rules'][$edit]['vac_addresses'];
		} else {
			print $emailaddresses;
		}
	
		print '" size="80" maxsize="200"><br />';
		
		print _("Days: Reply message will be resent after");
		print ' <input type="text" name="vac_days" value="';
		if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_days'])) {
			print $_SESSION['rules'][$edit]['vac_days'];
		} else {
			print "7";
		}
		print '" size="3" maxsize="4"> ';
		print _("days");
		print '<br />';
	
		print _("Use the following message:");
		print '<br /><textarea name="vac_message" rows="4" cols="50">';
		if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_message']) ){
			print $_SESSION['rules'][$edit]['vac_message'];
		} else {
			if($translate_return_msgs==true) {
				print _("This is an automated reply; I am away and will not be able to reply to you immediately.");
				print _("I will get back to you as soon as I return.");
			} else {
				print "This is an automated reply; I am away and will not be able to reply to you immediately.";
				print "I will get back to you as soon as I return.";
			}
		}
		print '</textarea></blockquote><br />';
	
	}
	
	/*-*-*-*/
	
	print '<h3>'. _("Additional Actions") . '</h3>';
	
	/*-*-*-*/
	
	/* STOP */
	
	print '<input type="checkbox" name="stop" id="stop" ';
	if(isset($edit)) {
		if(isset($_SESSION['rules'][$edit]['stop'])) {
			print 'checked="" ';
		}
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
		
		
	/*-*-*-*/
	
	/* Notify */
	
	if(avelsieve_capability_exists('notify')) {
		
		global $notifymethods, $notifystrings;
	
		print '<br><input type="checkbox" name="notifyme" id="notifyme" ';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['notify'])) {
				print 'checked="" ';
			}
		}
		print '/> ';
	
		print '<label for="notifyme">';
		print _("Notify me, using the following method:");
		print '</label> ';
		
		if(is_array($notifymethods) && sizeof($notifymethods) == 1) {
			
			/* No need to provide listbox, there's only one choice */
			print '<input type="hidden" name="notify[method]" value="'.$notifymethods[0].'" />';
			if(array_key_exists($notifymethods[0], $notifystrings)) {
				print $notifystrings[$notifymethods[0]];
			} else {
				print $notifymethods[0];
			}

		} elseif(is_array($notifymethods)) {
			print '<select name="notify[method]">';
			foreach($notifymethods as $no=>$met) {
				print '<option value="'.$met.'"';
				if(isset($edit)) {
					if(isset($_SESSION['rules'][$edit]['notify']['method']) &&
					  $_SESSION['rules'][$edit]['notify']['method'] == $met) {
						print ' selected=""';
					}
				}
				print '>';
	
				if(array_key_exists($met, $notifystrings)) {
					print $notifystrings[$met];
				} else {
					print $met;
				}
				print '</option>';
			}
			print '</select>';
			


		} elseif($notifymethods == false) {
			print '<input name="notify[method]" value="';
			if(isset($edit)) {
				if($_SESSION['rules'][$edit]['notify']['method']) {
					print  $_SESSION['rules'][$edit]['notify']['method'];
				}
			}
			print '" size="20" />';
		}
	
	
		print '<br /><blockquote>';
	
		/* Not really used, remove it. */
		$dummy =  _("Notification ID"); // for gettext
		/*
		print _("Notification ID") . ": ";
		print '<input name="notify[id]" value="';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['notify']['id'])) {
				print $_SESSION['rules'][$edit]['notify']['id'];
			}
		}
		print '" /><br />';
		*/
	
		print _("Destination") . ": ";
		print '<input name="notify[options]" size="30" value="';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['notify']['options'])) {
				print $_SESSION['rules'][$edit]['notify']['options'];
			}
		}
		print '" /><br />';
	
		global $prioritystrings;
		
		print 'Priority: <select name="notify[priority]">';
		foreach($prioritystrings as $pr=>$te) {
			print '<option value="'.$pr.'"';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['notify']['priority'])) {
					if($_SESSION['rules'][$edit]['notify']['priority'] == $pr) {
						print ' checked=""';
					}
				}
			}
			print '>';
			print $prioritystrings[$pr];
			print '</option>';
		}
		print '</select><br />';
	
		print _("Message") . " ";
		print '<textarea name="notify[message]" rows="4" cols="50">';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['notify']['message'])) {
				print $_SESSION['rules'][$edit]['notify']['message'];
			}
		}
		print '</textarea><br />';
		
		print '<small>';
		print _("Help: Valid variables are:");
		print ' $from$, $env-from$, $subject$</small>';
		// $text$ is not supported by Cyrus yet. Put it back if it gets fixed.
		// print ' $from$, $env-from$, $subject$, $text$, $text[n]$</small>';
		
		print '</blockquote>';
	}
}

function print_4_confirmation() {

	/* global $sieverule; */
	global $text;

	print '<p>';
	print _("Your new rule states:");
	print '</p><blockquote><p>'.$text.'</p></blockquote><p>';
	print _("If this is what you wanted, select Finished. You can also start over or cancel adding a rule altogether.");
	print '</p>';
	
}

?>
