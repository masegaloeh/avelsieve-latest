<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * This file contains functions that spit out HTML, mostly intended for use by
 * addrule.php and edit.php.
 *
 * @version $Id: html_ruleedit.inc.php,v 1.3 2004/11/03 12:48:48 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');

/**
 * HTML Output functions for rule editing / adding
 */
class avelsieve_html_edit extends avelsieve_html {

	/**
	 * @var int Which part of add new rule wizard are we in. 0 means 'any'.
	 */
	var $part = 0;

	/**
	 * @var boolean Enable spamrule building?
	 */
	var $spamrule_enable = false;

	/**
	 * @var string Mode of operation, for editing new rule. One of:
	 * 'wizard', 'addnew', 'edit', 'duplicate'
	 */
	var $mode;

	/**
	 * Constructor function. Takes as an optional argument a reference to a
	 * rule array which will be edited.
	 */
	function avelsieve_html_edit($mode = 'edit', $rule = array()) {
		$this->rule = $rule;
		$this->mode = $mode;
	}

	/**
	 * Start form.
	 * @return string
	 */
	function formheader() {
		global $PHP_SELF;
		return '<form name="addrule" action="'.$PHP_SELF.'" method="POST">';
	}

	/**
	 * Bottom control and navigation buttons.
	 * @return string
	 */
	function addbuttons() {
		$out = '<input name="reset" value="' . _("Clear this Form") .'" type="reset" />';

		if (isset($part) && $part != 1) {
			$out .= '<input name="startover" value="'. _("Start Over") .'" type="submit" />';
		}
		$out .= '<input name="cancel" value="'. _("Cancel").'" type="submit" /><br />';
	
		if ($this->spamrule) {
			$out .= '<input style="font-weight:bold" name="finished" value="'.
				_("Add SPAM Rule") . '" type="submit" />';
		}
		return $out;
	
		/*
		if ($part!=1) {
			$out .= '<input name="prev" value="&lt;&lt; ';
			$out .= _("Move back to step");
			$out .= ' '.($part-1).'" type="submit" />';
		}
		*/
		$dummy = _("Move back to step");
		
		if ($part=="4") {
			$out .= '<input style="font-weight:bold"  name="finished" value="'.
				_("Finished").'" type="submit" />';
		} else {
			$out .= '<input name="next" value="'._("Move on to step").' '.($part+1).' &gt;&gt;" type="submit" />';
		}
	}

	/**
	 * Simple footer that closes tables, form and HTML.
	 * @return string
	 */
	function nakedfooter() {
		return '</td></tr></table> </form></body></html>';
	}

	/**
 	* $out .= mailbox select widget.
 	* 
 	* @param string $selectname name for the select HTML variable
 	* @param string $selectedmbox which mailbox to be selected in the form
 	* @param boolean $sub 
 	*/
	function mailboxlist($selectname, $selectedmbox, $sub = false) {
	
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
		
	    	if($sub) {
			if ($default_sub_of_inbox == false ) {
				$mailboxlist = $mailboxlist."\n".'<option selected value="">[ '._("None")." ] </option>\n";	
			}
	    	}
	
	    	for ($i = 0; $i < count($boxes); $i++) {
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

	/**
	 * Output ruletype radio buttons.
	 * @param string $select 'radio' or 'select'
	 */
	function rule_1_type_radio($select = 'radio') {
		global $types, $sieve_capabilities;

		if($select == 'radio') {
			$out = '<p>'._("What kind of rule would you like to add?"). '</p>';
		} else {
			$out = '<p align="center">' . _("Rule Type") . ': <select name="type">';
		}
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
				if($select == 'radio') {
					$out .= '<input type="radio" name="type" id="type_'.$i.'" value="'.$i.'" checked="" /> ';
				} else {
					print '<option value="'.$i.'" ';
					if($type == $i) {
						print 'selected=""';
					}
					print '>'. $tp['name'] .'</option>';
				}
			} else {
				$out .= '<input type="radio" name="type" id="type_'.$i.'" value="'.$i.'" /> ';
			}
			if($select == 'radio') {
				$out .= '<label for="type_'.$i.'">'.$tp['name'].'<br />'.
					'<blockquote>'.$tp['description'].'</blockquote>'.
					'</label>';
			}
		}
		if($select == 'select') {
			$out .= '</select>';
		}
		return $out;
		/* ??
		print ' <input type="submit" name="changetype" value="'._("Change Type").'" /> </p>';
		*/
	}


	/**
	 * Address match
	 * @todo Not implemented yet.
	 * @return void
	 */
	function rule_2_1_address() {
		$out = _("The rule will trigger if the following addresses appear anywhere in the message's headers:");
		return $out;
	}

	/**
 	 * Listbox widget with available headers to choose from.
 	 *
 	 * @param $selected_header Selected header
 	 * @param $n option number
 	 */
	function header_listbox($selected_header, $n) {
	
		global $headers;
		
		$out .= '<select name="header['.$n.']">';
		
		/* 'Special' shortcut for To: or Cc: headers */
		$out .= '<option name="header['.$n.']"  value="toorcc"';
			if($selected_header=='toorcc')
				$out .= ' selected=""';
		$out .= '><strong>'. _("To: or Cc") .':</strong></option>';
		
		foreach($headers as $head) {
			if ($head==$selected_header) {
				$out .= '<option name="header['.$n.']"  value="'.$head.'" selected="">'.$head.':</option>';
			} else {
				$out .= '<option name="header['.$n.']"  value="'.$head.'">'.$head.':</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}
	
	/**
	 * Matchtype listbox. Returns an HTML select listbox with available match
	 * types, such as 'contains', 'is' etc.
	 *
	 * @param string $selected_matchtype
	 * @param int $n
	 * @param string $varname
	 * @return string
	 */
	function matchtype_listbox($selected_matchtype, $n, $varname = 'matchtype') {
		global $matchtypes, $comparators, $matchregex, $sieve_capabilities;
		reset($matchtypes);
		reset($comparators);
		reset($matchregex);
		
		$out = '<select name="'.$varname.'['.$n.']">';
		
		while(list ($matchtype, $matchstring) = each ($matchtypes)) {
			if ($matchtype==$selected_matchtype) {
				$out .= '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
			} else {
				$out .= '<option value="'.$matchtype.'">'.$matchstring.'</option>';
			}
		}
		if(avelsieve_capability_exists('relational')) {
			while(list ($matchtype, $matchstring) = each ($comparators)) {
				if ($matchtype==$selected_matchtype) {
					$out .= '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
				} else {
					$out .= '<option value="'.$matchtype.'">'.$matchstring.'</option>';
				}
			}
		}
		if(avelsieve_capability_exists('regex')) {
			while(list ($matchtype, $matchstring) = each ($matchregex)) {
				if ($matchtype==$selected_matchtype) {
					$out .= '<option value="'.$matchtype.'" selected="">'.$matchstring.'</option>';
				} else {
					$out .= '<option value="'.$matchtype.'">'.$matchstring.'</option>';
				}
			}
		}
		$out .= '</select>';
		return $out;
	}
	
	/**
	 * The condition listbox shows the available conditions for a given match
	 * type. Usually 'and' and 'or'.
	 * @return string
	 */
	function condition_listbox($selected_condition) {
		$conditions = array(
			"and" => _("AND (Every item must match)"),
			"or" => _("OR (Either item will match)")
		);
	
		$out = _("The condition for the following rules is:").
			'<select name="condition">';
	
		while(list ($condition, $conditionstring) = each ($conditions)) {
			if($condition==$selected_condition) {
				$out .= '<option value="'.$condition.'" selected="">'.$conditionstring.'</option>';
			} else {
				$out .= '<option value="'.$condition.'">'.$conditionstring.'</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}
	
	/**
	 * Output HTML code for header match rule.
	 * @param array $items
	 * @return string
	 */
	function rule_2_2_header($items) {
	
		global $maxitems, $edit, $matchtypes, $comparators;
		
		$out = '<input type="hidden" name="items" value="'.$items.'" />';
		
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
			$out .= condition_listbox($condition);
		}
		
		$out .= '<br /><ul>';
		
		for ( $n=0; $n< $items; $n++) {
		
			$out .= '<li>';
			$out .= _("The header ");
			if(isset($header[$n])) {
				$out .= header_listbox($header[$n], $n);
			} else {
				$out .= header_listbox("", $n);
			}
			
			if(isset($matchtype[$n])) {
				$out .= matchtype_listbox($matchtype[$n], $n);
			} else {
				$out .= matchtype_listbox("", $n);
			}
		
			$out .= '<input type="text" name="headermatch['.$n.']" size="24" maxlength="40" value="';
			if(isset($headermatch[$n])) {
				$out .= htmlspecialchars($headermatch[$n]);
			}
			$out .= '" /></li><br />';
		
		} /* End for loop */
		
		$out .= '</ul><br />';
		
		if($items > 1) {
			$out .= '<input name="less" value="'. _("Less...") .'" type="submit" />';
		}
		if($items < $maxitems) {
			$out .= '<input name="append" value="'. _("More..."). '" type="submit" />';
		}
		return $out;
	}
	
	/**
	 * Size match
	 * @return string
	 */
	function rule_2_3_size() {
	
		global $edit;
		
		$out = '<p>'._("This rule will trigger if message is");
		
		$out .= '<select name="sizerel"><option value="bigger" name="sizerel"';
		
		if(isset($edit)) {
			if($_SESSION['rules'][$edit]['sizerel'] == "bigger") {
				$out .= ' selected=""';
			}
		}
		$out .= '>';
		$out .= _("bigger");
		$out .= '</option><option value="smaller" name="sizerel"';
		if(isset($edit)) {
			if($_SESSION['rules'][$edit]['sizerel'] == "smaller") {
				$out .= ' selected=""';
			}
		}
		$out .= '>';
		$out .= _("smaller");
		$out .= '</option></select>';
		$out .= _("than");
		
		$out .= '<input type="text" name="sizeamount" size="10" maxlength="10" value="';
		
		if(isset($edit)) {
			$out .= $_SESSION['rules'][$edit]['sizeamount'];
		} else {
			$out .= '50';
		}
		$out .= '" />
		<select name="sizeunit">
		<option value="kb" name="sizeunit';
		if(isset($edit)) {
			if($_SESSION['rules'][$edit]['sizeunit'] == "kb") {
				$out .= ' selected=""';
			}
		}
		$out .= '">';
		$out .= _("KB (kilobytes)");
		$out .= '</option><option value="mb" name="sizeunit"';
		if(isset($edit)) {
			if($_SESSION['rules'][$edit]['sizeunit'] == "mb") {
				$out .= ' selected=""';
			}
		}
		$out .= '">';
		$out .= _("MB (megabytes)");
		$out .= '</option></select></p>';
		
		return $out;
	}
		
	/**
	 * All messages 
	 * @return string
	 */
	function rule_2_4_allmessages() {
		$out = _("The following action will be applied to <strong>all</strong> incoming messages that do not match any of the previous rules.");
		return $out;
	}
	
	/**
	 * Mail Body match
	 * @return string
	 */
	function rule_2_5_body() {
		$out .= '<p>'.
			_("This rule will trigger upon the occurrence of one or more strings in the body of an e-mail message. ").
			'</p>';
		return $out;
	}
	
	/**
	 * Output radio button for action # $num.
	 * @param int $num
	 * @param int $selectedaction Number of action to be pre-selected
	 * @return string
	 */
	function action_radio($num, $selectedaction) {
		$out = '<input type="radio" name="action" id="action_'.$num.'" value="'.$num.'" ';
		if($selectedaction) {
			if($selectedaction == $num) {
				$out .= ' checked=""';
			}
		}
		$out .= '/> ';
		return $out;
	}
	
	
	/**
	 * Output available actions in a radio-button style.
	 * @return string
	 * @todo split into different functions
	 * @todo make a schema that describes each action
	 * @todo use text[n] which is supported by Cyrus 2.2+ (?)
	 */
	function rule_3_action() {
	
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
		
		$out = '<p>';
		$out .= _("Choose what to do when this rule triggers, from one of the following:");
		
		/*-*-*-*/
		
		$out .= '</p>';
		$out .= $this->action_radio(1, $selectedaction);
		$out .= '<label for="action_1">';
		$out .= _("Keep (Default action)");
		$out .= '</label>';
		$out .= '<br />';
		
		/*-*-*-*/
		
		$out .= $this->action_radio(2, $selectedaction);
		$out .= '<label for="action_2">';
		$out .= _("Discard Silently");
		$out .= '</label>';
		$out .= '<br />';
		
		/*-*-*-*/
		
		if(avelsieve_capability_exists('reject')) {
		
			$out .= $this->action_radio(3, $selectedaction);
			$out .= '<label for="action_3">';
			$out .= _("Reject, sending this excuse to the sender:");
			$out .= '</label>';
	
			$out .= '<br /><blockquote><textarea name="excuse" rows="4" cols="50">';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['excuse']))
					$out .= $_SESSION['rules'][$edit]['excuse'];
			} else {
				if($translate_return_msgs==true) {
					$out .= _("Please do not send me large attachments.");
				} else {
					$out .= "Please do not send me large attachments.";
				}
			}
			$out .= '</textarea></blockquote><br />';
			
		}
		
		/*-*-*-*/
		
		$out .= $this->action_radio(4, $selectedaction);
		$out .= '<label for="action_4">';
		$out .= _("Redirect to the following email address:");
		$out .= '</label>';
	
		$out .= '<br /><blockquote><input type="text" name="redirectemail" size="26" maxlength="58" value="';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['redirectemail']))
				$out .= $_SESSION['rules'][$edit]['redirectemail'];
		} else {
			$out .= _("someone@example.org");
		}
		$out .= '" />';
		
		$out .= '<br /><input type="checkbox" name="keep" id="keep" ';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['keep'])) {
				$out .= 'checked="" ';
			}
		}
		$out .= '/> ';
		$out .= '<label for="keep">';
		$out .= _("Keep a local copy as well.");
		$out .= '</label>';
		
		$out .= '</blockquote><br />';
		
		/*-*-*-*/
		
		if(avelsieve_capability_exists('fileinto')) {
		
			$out .= $this->action_radio(5, $selectedaction);
			
			global $selectedmailbox;
			
			$out .= '<label for="action_5">';
			$out .= _("Move message into");
			$out .= '</label>';
		
			if(isset($edit)) {
				/* The section here will be slightly different for the edit
			 	* page. This part takes care of this. */
		
				$out .= '<input type="hidden" name="newfolder" value="5a" onclick="checkOther(\'5\');" /> ';
				$out .= _("the existing folder");
				$out .= ' ';
				$out .= $this->mailboxlist("folder", $selectedmailbox);
		
			} else {
				/* This is the section for the addrule part. Is it kludgy? Is
			 	* it? IS IT? :-p */
		
				$out .= '<br /><blockquote><input type="radio" name="newfolder" value="5a" checked="" onclick="checkOther(\'5\');" /> ';
				$out .= _("the existing folder");
				$out .= ' ';
				$out .= printmailboxlist("folder", $selectedmailbox);
		
				if ($createnewfolder) {
		
					$out .= '<br /><input type="radio" name="newfolder" value="5b" onclick="checkOther(\'5\');" /> ';
					$out .= _("a new folder, named");
					$out .= '<input type="text" size="25" name="folder_name" onclick="checkOther(\'5\');" /> ';
					$out .= _("created as a subfolder of");
					$out .= printmailboxlist("subfolder", false, true);
				}
			
			}
		
			if(avelsieve_capability_exists('imapflags')) {
			
				if(isset($edit)) {
					$out .= '<blockquote>';
				}
			
				$out .= '<br /><input type="checkbox" name="keepdeleted" id="keepdeleted" ';
				if(isset($edit)) {
					if(isset($_SESSION['rules'][$edit]['keepdeleted'])) {
						$out .= 'checked="checked" ';
					}
				}
				$out .= '/> ';
				$out .= '<label for="keepdeleted">';
				$out .= _("Also keep copy in INBOX, marked as deleted.");
				$out .= '</label>';
			}
			
			$out .= '</blockquote>';
			$out .= '<br />';
		
		
		}
		
		/*-*-*-*/
		
		
		if(avelsieve_capability_exists('vacation')) {
		
			$out .= $this->action_radio(6, $selectedaction);
		
			global $emailaddresses;
		
			$out .= '<label for="action_6">';
			$out .= '<strong>&quot;';
			$out .= _("Vacation");
			$out .= '&quot;</strong>: ';
			$out .= _("The notice will be sent only once to each person that sends you mail, and will not be sent to a mailing list address.");
			$out .= '<br /><blockquote>';
			$out .= '</label>';
	
			$out .= _("Addresses: Only reply if sent to these addresses:");
			$out .= '<input type="text" name="vac_addresses" value="';
		
			if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_addresses']) ) {
				$out .= $_SESSION['rules'][$edit]['vac_addresses'];
			} else {
				$out .= $emailaddresses;
			}
		
			$out .= '" size="80" maxsize="200"><br />';
			
			$out .= _("Days: Reply message will be resent after");
			$out .= ' <input type="text" name="vac_days" value="';
			if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_days'])) {
				$out .= $_SESSION['rules'][$edit]['vac_days'];
			} else {
				$out .= "7";
			}
			$out .= '" size="3" maxsize="4"> ';
			$out .= _("days");
			$out .= '<br />';
		
			$out .= _("Use the following message:");
			$out .= '<br /><textarea name="vac_message" rows="4" cols="50">';
			if(isset($edit) && isset($_SESSION['rules'][$edit]['vac_message']) ){
				$out .= $_SESSION['rules'][$edit]['vac_message'];
			} else {
				if($translate_return_msgs==true) {
					$out .= _("This is an automated reply; I am away and will not be able to reply to you immediately.");
					$out .= _("I will get back to you as soon as I return.");
				} else {
					$out .= "This is an automated reply; I am away and will not be able to reply to you immediately.";
					$out .= "I will get back to you as soon as I return.";
				}
			}
			$out .= '</textarea></blockquote><br />';
		
		}
		
		/*-*-*-*/
		
		$out .= '<h3>'. _("Additional Actions") . '</h3>';
		
		/*-*-*-*/
		
		/* STOP */
		
		$out .= '<input type="checkbox" name="stop" id="stop" ';
		if(isset($edit)) {
			if(isset($_SESSION['rules'][$edit]['stop'])) {
				$out .= 'checked="" ';
			}
		}
		$out .= '/> ';
		$out .= '<label for="stop">';
		if ($useimages) {
			$out .= '<img src="images/stop.gif" width="35" height="33" border="0" alt="';
			$out .= _("STOP");
			$out .= '" align="middle" /> ';
		} else {
			$out .= "<strong>"._("STOP").":</strong> ";
		}
		$out .= _("If this rule matches, do not check any rules after it.");
		$out .= '</label>';
			
			
		/*-*-*-*/
		
		/* Notify */
		
		if(avelsieve_capability_exists('notify')) {
			
			global $notifymethods, $notifystrings;
		
			$out .= '<br><input type="checkbox" name="notifyme" id="notifyme" ';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['notify'])) {
					$out .= 'checked="" ';
				}
			}
			$out .= '/> ';
		
			$out .= '<label for="notifyme">';
			$out .= _("Notify me, using the following method:");
			$out .= '</label> ';
			
			if(is_array($notifymethods) && sizeof($notifymethods) == 1) {
				
				/* No need to provide listbox, there's only one choice */
				$out .= '<input type="hidden" name="notify[method]" value="'.$notifymethods[0].'" />';
				if(array_key_exists($notifymethods[0], $notifystrings)) {
					$out .= $notifystrings[$notifymethods[0]];
				} else {
					$out .= $notifymethods[0];
				}
	
			} elseif(is_array($notifymethods)) {
				$out .= '<select name="notify[method]">';
				foreach($notifymethods as $no=>$met) {
					$out .= '<option value="'.$met.'"';
					if(isset($edit)) {
						if(isset($_SESSION['rules'][$edit]['notify']['method']) &&
					  	$_SESSION['rules'][$edit]['notify']['method'] == $met) {
							$out .= ' selected=""';
						}
					}
					$out .= '>';
		
					if(array_key_exists($met, $notifystrings)) {
						$out .= $notifystrings[$met];
					} else {
						$out .= $met;
					}
					$out .= '</option>';
				}
				$out .= '</select>';
				
	
	
			} elseif($notifymethods == false) {
				$out .= '<input name="notify[method]" value="';
				if(isset($edit)) {
					if($_SESSION['rules'][$edit]['notify']['method']) {
						$out .=  $_SESSION['rules'][$edit]['notify']['method'];
					}
				}
				$out .= '" size="20" />';
			}
		
		
			$out .= '<br /><blockquote>';
		
			/* Not really used, remove it. */
			$dummy =  _("Notification ID"); // for gettext
			/*
			$out .= _("Notification ID") . ": ";
			$out .= '<input name="notify[id]" value="';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['notify']['id'])) {
					$out .= $_SESSION['rules'][$edit]['notify']['id'];
				}
			}
			$out .= '" /><br />';
			*/
		
			$out .= _("Destination") . ": ";
			$out .= '<input name="notify[options]" size="30" value="';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['notify']['options'])) {
					$out .= $_SESSION['rules'][$edit]['notify']['options'];
				}
			}
			$out .= '" /><br />';
		
			global $prioritystrings;
			
			$out .= 'Priority: <select name="notify[priority]">';
			foreach($prioritystrings as $pr=>$te) {
				$out .= '<option value="'.$pr.'"';
				if(isset($edit)) {
					if(isset($_SESSION['rules'][$edit]['notify']['priority'])) {
						if($_SESSION['rules'][$edit]['notify']['priority'] == $pr) {
							$out .= ' checked=""';
						}
					}
				}
				$out .= '>';
				$out .= $prioritystrings[$pr];
				$out .= '</option>';
			}
			$out .= '</select><br />';
		
			$out .= _("Message") . " ";
			$out .= '<textarea name="notify[message]" rows="4" cols="50">';
			if(isset($edit)) {
				if(isset($_SESSION['rules'][$edit]['notify']['message'])) {
					$out .= $_SESSION['rules'][$edit]['notify']['message'];
				}
			}
			$out .= '</textarea><br />';
			
			$out .= '<small>';
			$out .= _("Help: Valid variables are:");
			$out .= ' $from$, $env-from$, $subject$</small>';
			// $text$ is not supported by Cyrus yet. Put it back if it gets fixed.
			// $out .= ' $from$, $env-from$, $subject$, $text$, $text[n]$</small>';
			
			$out .= '</blockquote>';
		}
		return $out;
	}
	
	/**
	 * Output notification message for new rule wizard
	 * @param string $text
	 * @return string
	 */
	function confirmation($text) {
		$out = '<p>'. _("Your new rule states:") .
			'</p><blockquote><p>'.$text.'</p></blockquote><p>'.
			_("If this is what you wanted, select Finished. You can also start over or cancel adding a rule altogether.").
			'</p>';
		return $out;
	}

	/**
	 * Submit buttons for edit form -- not applicable for wizard
	 * @return string
	 */
	function submit_buttons() {
		$out = '<tr><td><div style="text-align: center">';
		switch ($this->mode) {
			case 'addnew':
				$out .= '<input type="submit" name="addnew" value="'._("Add New Rule").'" />';
				break;
			case 'duplicate':
				$out .= '<input type="hidden" name="dup" value="1" />';
				$out .= '<input type="submit" name="addnew" value="'._("Add New Rule").'" />';
				break;
			case 'edit':
				$out .= '<input type="submit" name="apply" value="'._("Apply Changes").'" />';
				break;
		}
		$out .= ' <input type="submit" name="cancel" value="'._("Cancel").'" />';
		return $out;
	}

	/**
	 * Main function that outputs a form for editing a whole rule.
	 */
	function edit_rule() {
		global $PHP_SELF;
		$out = $this->table_header( _("Editing Mail Filtering Rule") . ' #'. ($edit+1) ).
			$this->all_sections_start().
			'<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
			'<input type="hidden" name="edit" value="'.$edit.'" />';

		/* --------------------- 'if' ----------------------- */
		$out .= $this->section_start( _("Condition") );
		switch ($type) { 
			case 1: 
				$out .= 'Not implemented yet.';
				break;
			case 2:			/* header */
				if(!isset($items)) {
					$items = sizeof($rule['header']) + 1;
					print '<input type="hidden" name="items" value="'.$items.'" />';
				}
				$out .= $this->rule_2_2_header($items);
				break;		
				
			case 3: 		/* size */
				$out .= $this->rule_2_3_size();
				break;
				
			case 4: 		/* All messages */
				$out .= $this->rule_2_4_allmessages();
				break;
				
		}
		$out .= $this->section_end();

		/* --------------------- 'then' ----------------------- */
		
		$out .= $this->section_start( _("Action") );
		
		if(isset($rule['folder'])) {
			$selectedmailbox = $rule['folder'];
		}
		
		/* TODO - Remove this and add new folder creation in edit.php as well. */
		$createnewfolder = false; 
		
		$out .= $this->rule_3_action();

		$out .= $this->section_end();

		$out .= $this->submit_buttons();
		
		print '</div></form></td></tr>';
	
		$out .= $this->all_sections_end() .
			$this->table_footer();
		return $out;
	}
}
?>
