<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * This file contains functions that spit out HTML, mostly intended for use by
 * addrule.php and edit.php.
 *
 * @version $Id: html_ruleedit.inc.php,v 1.6 2004/11/11 13:49:49 avel Exp $
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
	 * Output ruletype select form.
	 *
	 * @param string $select 'radio' or 'select'
	 */
	function rule_1_type($select = 'radio') {
		global $types, $sieve_capabilities;
		if($select == 'radio') {
			$out = '<p>'._("What kind of rule would you like to add?"). '</p>';
		} elseif($select == 'select') {
			$out = '<p align="center">' . _("Rule Type") . ': '.
				'<input type="hidden" name="previoustype" value=""';
			if(isset($this->rule['type'])) {
				$out .= $this->rule['type'];
			} else {
				$out .= '0';
			}
			$out .= '" /><select name="type" onChange="addrule.submit();">';
		}

		$active_types = array();
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
			$active_types[$tp['order']] = $i;
		}
		sort($active_types);

		for($i=0; $i<sizeof($active_types); $i++) {
			$k = $active_types[$i];
			if($select == 'radio') {
				$out .= '<input type="radio" name="type" id="type_'.$k.'" value="'.$k.'" ';
				if(isset($this->rule['type']) && $this->rule['type'] == $k) {
					$out .= 'selected=""';
				}
				$out .= '/> '.
					'<label for="type_'.$k.'">'.$types[$k]['name'].'<br />'.
					'<blockquote>'.$types[$k]['description'].'</blockquote>'.
					'</label>';
			} elseif($select == 'select') {
				$out .= '<option value="'.$k.'" ';
				if(isset($this->rule['type']) && $this->rule['type'] == $k) {
					$out .= 'selected=""';
				}
				$out .= '>'. $types[$k]['name'] .'</option>';
			}
		}
		if($select == 'select') {
				$out .= '</select>';
		}
		return $out;
		/* ??
		$out .= ' <input type="submit" name="changetype" value="'._("Change Type").'" /> </p>';
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
		$out = '<select name="header['.$n.']">';
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
	 * @return string
	 */
	function rule_2_2_header($items = 0) {
	
		global $maxitems, $matchtypes, $comparators;

		if($items==0) {
			if(isset($this->rule['headermatch'])) {
				$items = sizeof($this->rule['headermatch']) + 1;
			} else {
				$items = 3; // Nice default.
			}
		} else {
			$items++;
		}

		$out = '<input type="hidden" name="items" value="'.$items.'" />';
		
		if(isset($this->rule['condition'])) {
			$condition = $rule['condition'];
		} else {
			$condition = 'and';
		}

		if(isset($this->rule['header']))
			$header = $this->rule['header'];
		if(isset($this->rule['matchtype']))
			$matchtype = $this->rule['matchtype'];
		if(isset($this->rule['headermatch'])) 
			$headermatch = $this->rule['headermatch'];
		
		if($items > 1) {
			$out .= $this->condition_listbox($condition);
		}
		
		$out .= '<br /><ul>';
		
		for ( $n=0; $n< $items; $n++) {
		
			$out .= '<li>';
			$out .= _("The header ");
			if(isset($header[$n])) {
				$out .= $this->header_listbox($header[$n], $n);
			} else {
				$out .= $this->header_listbox("", $n);
			}
			
			if(isset($matchtype[$n])) {
				$out .= $this->matchtype_listbox($matchtype[$n], $n);
			} else {
				$out .= $this->matchtype_listbox("", $n);
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
		if(isset($this->rule['sizerel'])) {
			$sizerel = $this->rule['sizerel'];
		} else {
			$sizerel = 'bigger';
		}
		if(isset($this->rule['sizeamount'])) {
			$sizeamount = $this->rule['sizeamount'];
		} else {
			$sizeamount = 50;
		}
		if(isset($this->rule['sizeunit'])) {
			$sizeunit = $this->rule['sizeunit'];
		} else {
			$sizeunit = 'kb';
		}

		$out = '<p>'._("This rule will trigger if message is").
			'<select name="sizerel"><option value="bigger" name="sizerel"';
		if($sizerel == "bigger") $out .= ' selected=""';
		$out .= '>'. _("bigger") . '</option>'.
			'<option value="smaller" name="sizerel"';
		if($this->rule['sizerel'] == 'smaller') $out .= ' selected=""';
		$out .= '>'. _("smaller") . '</option>'.
			'</select>' .
			_("than") . 
			'<input type="text" name="sizeamount" size="10" maxlength="10" value="'.$sizeamount.'" /> '.
			'<select name="sizeunit">'.
			'<option value="kb" name="sizeunit';
		if($sizeunit == 'kb') $out .= ' selected=""';
		$out .= '">' . _("KB (kilobytes)") . '</option>'.
			'<option value="mb" name="sizeunit"';
		if($sizeunit == "mb") $out .= ' selected=""';
		$out .= '">'. _("MB (megabytes)") . '</option>'.
			'</select></p>';
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
	 * Output available actions in a radio-button style.
	 * @return string
	 */
	function rule_3_action() {
		/* Preferences from config.php */
		global $useimages, $translate_return_msgs;
		/* Data taken from addrule.php */
		global $boxes, $createnewfolder, $emailaddresses, $sieve_capabilities;
		/* Other */
		global $actions, $additional_actions;
		$out = '<p>'. _("Choose what to do when this rule triggers, from one of the following:"). '</p>';
		
		foreach($actions as $action) {
			$classname = 'avelsieve_action_'.$action;
			if(class_exists($classname)) {
				$$classname = new $classname($this->rule, 'html');
				if($$classname != null) {
					$out .= $$classname->action_html();
				}
			}
		}
		return $out;
		
		//	global $emailaddresses;
		$out .= '<h3>'. _("Additional Actions") . '</h3>';
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
	function edit_rule($edit = false) {

		global $PHP_SELF;
		if(is_numeric($edit)) {
			$out = $this->table_header( _("Editing Mail Filtering Rule") . ' #'. ($edit+1) ).
			$this->all_sections_start().
			'<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
			'<input type="hidden" name="edit" value="'.$edit.'" />';
		} else {
			$out = $this->table_header( _("Create New Mail Filtering Rule") ).
			$this->all_sections_start().
			'<form name="addrule" action="'.$PHP_SELF.'" method="POST">';
		}
		
		/* -------------------- type ------------------------ */
		$out .= $this->section_start( _("Rule Type") ).
			$this->rule_1_type('select') .
			$this->section_end();

		/* --------------------- 'if' ----------------------- */
		$out .= $this->section_start( _("Condition") );
		switch ($this->rule['type']) { 
			case 1: 
				$out .= 'Not implemented yet.';
				break;
			case 2:			/* header */
				if(!isset($items)) {
					if(isset($this->rule['header'])) {
						$items = sizeof($this->rule['header']) + 1;
					} else {	
						global $startitems;
						$items = $startitems;
					}
					$out .= '<input type="hidden" name="items" value="'.$items.'" />';
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
		
		$out .= '</div></form></td></tr>';
	
		$out .= $this->all_sections_end() .
			$this->table_footer();
		return $out;
	}
}
?>
