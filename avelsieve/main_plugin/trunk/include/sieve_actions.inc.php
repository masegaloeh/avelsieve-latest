<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 *
 * @version $Id: sieve_actions.inc.php,v 1.5 2004/11/12 11:58:02 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2002-2004 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Root class for SIEVE actions.
 *
 * Each class that extends this class describes a SIEVE action and can contain
 * the following variables:
 *
 * num			Number of action
 * capability	Required capability(ies), if any
 * text			Textual description
 * helptxt		Explanation text
 * options		Array of Options and their default values
 *
 * It can also contain these functions:
 *
 * options_html()	Returns the HTML printout of the action's options 
 */
class avelsieve_action {
	var $frontend = '';
	var $translate_return_msgs = false;
	var $useimages = true;

	/**
	 * Constructor function that should be called from every class
	 */
	function avelsieve_action($rule, $frontend) {
		$this->frontend = $frontend;
		$this->rule = $rule;
			
		/* Check if required capability exists */
		if(isset($this->capability) && !empty($this->capability)) {
			if(!avelsieve_capability_exists($this->capability)) {
				$this = null;
				return;
			}
		}
	}

	/**
	 * Return All HTML Code that describes this action.
	 */
	function action_html() {
		/* Radio button */
		$out = $this->action_radio();

		/* Main text */
		if($this->num) {
			/* Radio Button */
			$out .= '<label for="action_'.$this->num.'">' . $this->text .'</label>';
		} else {
			/* Checkbox */
			$out .= '<label for="'.$this->name.'">'. $this->text .'</label>';
		}

		if(isset($this->helptxt)) {
			$out .= ': ' . $this->helptxt;
		}

		/* Options */
		if(isset($this->options) and sizeof($this->options) > 0) {
			$optval = array();
			foreach($this->options as $opt=>$defaultval) {
				if(isset($this->rule[$opt])) {
					$optval[$opt] = $this->rule[$opt];
				} else {
					$optval[$opt] = $defaultval;
				}
			}
			if($this->num) {
				$out .= '<div id="options_'.$this->num.'"';
				if(isset($this->rule['action']) && $this->rule['action'] == $this->num) {
					$out .= '';
				} else {
					$out .= ' style="display:none"';
				}
			} else {
				$out .= '<div id="options_'.$this->name.'"';
				if(isset($this->rule[$this->name]) && $this->rule[$this->name]) {
					$out .= '';
				} else {
					$out .= ' style="display:none"';
				}
			}
			$out .= '>';

			$out .= '<blockquote>';
			if(method_exists($this, 'options_html')) {
				$out .= $this->options_html($optval);
			} else {
				$out .= $this->options_html_generic($optval);
			}
			$out .= '</blockquote>';
			$out .= '</div>';
			unset($val);
		}
		$out .= '<br />';
		return $out;
	}

	/**
	 * Generic Options for an action.
	 *
	 * @todo Not implemented yet.
	 */
	function options_html_generic($val) {
		return "Not implemented yet.";
	}

	/**
	 * Output radio or checkbox button for this action.
	 * @return string
	 */
	function action_radio() {
		$out = '';
		if($this->num) {
			/* Radio */
			$out .= '<input type="radio" name="action" onClick="';
				// global $actions;
				/*
				foreach($actions as $action) {
					$out .= 'HideDiv(\''.$action.'\');';
				}
				*/
				for($i=0;$i<9;$i++) {
					if($i!=$this->num) {
						$out .= 'HideDiv(\'options_'.$i.'\');';
					}
				}
				$out .= 'ShowDiv(\'options_'.$this->num.'\');return true;"'.
					' id="action_'.$this->num.'" value="'.$this->num.'" ';

			if(isset($this->rule['action'])  && $this->rule['action'] == $this->num) {
				$out .= ' checked=""';
			}
			$out .= '/> ';
		} else {
			/* Checkbox */
			$out .= '<input type="checkbox" name="'.$this->name.'" '.
					' onClick="ToggleShowDiv(\'options_'.$this->name.'\');return true;"'.
					' id="'.$this->name.'" ';
			if(isset($this->rule[$this->name])) {
				$out .= ' checked=""';
			}
			$out .= '/> ';
		}
		return $out;
	}
}

/**
 * Keep Action
 */
class avelsieve_action_keep extends avelsieve_action {
	var $num = 1;
	var $capability = '';
	var $options = array(); 

	function avelsieve_action_keep($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Keep (Default action)");
		if(!isset($rule['action'])) {
			/* Hack to make the radio button selected for a new rule, for GUI
			 * niceness */
			$this->rule['action'] = 1;
		}
	}
}

/**
 * Discard Action
 */
class avelsieve_action_discard extends avelsieve_action {
	var $num = 2;
	var $capability = '';
	var $options = array(); 

	function avelsieve_action_discard($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Discard Silently");
	}
}

/**
 * Reject Action
 */
class avelsieve_action_reject extends avelsieve_action {
	var $num = 3;
	var $capability = 'reject';
	var $options = array(
		'excuse' => ''
	);
 	
	function avelsieve_action_reject($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Reject, sending this excuse to the sender:");

		if($this->translate_return_msgs==true) {
			$this->options['excuse'] = _("Please do not send me large attachments.");
		} else {
			$this->options['excuse'] = "Please do not send me large attachments.";
		}
	}

	function options_html($val) {
		return '<textarea name="excuse" rows="4" cols="50">'.$val['excuse'].'</textarea>';
	}
}

/**
 * Redirect Action
 */
class avelsieve_action_redirect extends avelsieve_action {
	var $num = 4;

	function avelsieve_action_redirect($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Redirect to the following email address:");
		$this->options = array(
			'redirectemail' => _("someone@example.org"),
			'keep' => ''
		);
	}

	function options_html($val) {
		$out = '<input type="text" name="redirectemail" size="26" maxlength="58" value="'.$val['redirectemail'].'"/>'.
				'<br />'.
				'<input type="checkbox" name="keep" id="keep" ';
		if(isset($val['keep'])) {
				$out .= ' checked=""';
		}
		$out .= '/>'.
				'<label for="keep">'. _("Keep a local copy as well.") . '</label>';
		return $out;
	}
}


/**
 * Fileinto Action
 */
class avelsieve_action_fileinto extends avelsieve_action {
	var $num = 5;
	var $capability = 'fileinto';
	var $options = array(
		'folder' => '',
	);

	function avelsieve_action_fileinto($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Move message into");
	}
	
	function options_html ($val) {
		$out = '<input type="radio" name="folder" value="5a" onclick="checkOther(\'5\');" ';
		if(isset($val['folder'])) {
			$out .= 'checked=""';
		}
		$out .= '/> '. _("the existing folder") . ' ';
		if(isset($val['folder'])) {
			$out .= mailboxlist('folder', $val['folder']);
		} else {
			$out .= mailboxlist('folder', false);
		}
			
		$out .=	'<br />'.
				'<input type="radio" name="newfolder" value="5b" onclick="checkOther(\'5\');" /> '.
				_("a new folder, named").
				'<input type="text" size="25" name="folder_name" onclick="checkOther(\'5\');" /> '.
				_("created as a subfolder of").
				mailboxlist('subfolder', false, true);
		return $out;
	}
}

/**
 * Vacation Action
 */
class avelsieve_action_vacation extends avelsieve_action {
	var $num = 6;
	var $capability = 'vacation';
	
	var $options = array(
		'vac_addresses' => '',
		'vac_days' => '7',
		'vac_message' => ''
	);

	function avelsieve_action_vacation($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);

		$this->text = _("Vacation");
		$this->options['vac_addresses'] = get_user_addresses();

		if($this->translate_return_msgs==true) {
			$this->options['vac_message'] = _("This is an automated reply; I am away and will not be able to reply to you immediately.").
			_("I will get back to you as soon as I return.");
		} else {
			$this->options['vac_message'] = "This is an automated reply; I am away and will not be able to reply to you immediately.".
			"I will get back to you as soon as I return.";
		}
		
		$this->helptxt = _("The notice will be sent only once to each person that sends you mail, and will not be sent to a mailing list address.");

	}


	function options_html($val) {
	 	return _("Addresses: Only reply if sent to these addresses:").
				' <input type="text" name="vac_addresses" value="'.$val['vac_addresses'].'" size="80" maxsize="200"><br />'.
				_("Days: Reply message will be resent after").
				' <input type="text" name="vac_days" value="'.$val['vac_days'].'" size="3" maxsize="4"> ' . _("days").
				'<br />'.
				_("Use the following message:") . '<br />' .
				'<textarea name="vac_message" rows="4" cols="50">'.$val['vac_message'].'</textarea>';
	}

}


/**
 * STOP Action
 */
class avelsieve_action_stop extends avelsieve_action {
	var $num = 0;
	var $name = 'stop';
	var $text = '';

	function avelsieve_action_stop($rule = array(), $frontend = 'html') {
		$this->helptxt = _("If this rule matches, do not check any rules after it.");
		$this->avelsieve_action($rule, $frontend);
		if ($this->useimages) {
			$this->text = '<img src="images/stop.gif" width="35" height="33" border="0" alt="'. _("STOP") .'" align="middle" /> ';
		} else {
			$this->text = "<strong>"._("STOP").":</strong> ";
		}
	}
}

/**
 * Notify Action
 */
class avelsieve_action_notify extends avelsieve_action {
	var $num = 0;
	var $name = 'notify';
	var $options = array(
		'notify[method]' => '',
		'notify[id]' => '',
		'notify[options]' => ''
	);

	/**
	 * The notification action is a bit more complex than the others. The
	 * oldcyrus variable is for supporting the partially implemented notify
	 * extension implementation of Cyrus < 2.3.
	 *
	 * @see https://bugzilla.andrew.cmu.edu/show_bug.cgi?id=2135
	 */
	function avelsieve_action_notify($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		global $notifymethods;
		
		if(is_array($notifymethods) && sizeof($notifymethods) > 0) {
			$this->text = _("Notify me, using the following method:");
			$this->notifystrings = array(
				'sms' => _("Mobile Phone Message (SMS)") ,
				'mailto' => _("Email notification") ,
				'zephyr' => _("Notification via Zephyr") ,
				'icq' => _("Notification via ICQ")
			);

			$this->oldcyrus = true;

		} else {
			$this = null;
			return;
		}
	}

	function options_html($val) {
		global $notifymethods;
		$out = '';
		if(is_array($notifymethods) && sizeof($notifymethods) == 1) {
				/* No need to provide listbox, there's only one choice */
				$out .= '<input type="hidden" name="notify[method]" value="'.$notifymethods[0].'" />';
				if(array_key_exists($notifymethods[0], $this->notifystrings)) {
					$out .= $this->notifystrings[$notifymethods[0]];
				} else {
					$out .= $notifymethods[0];
				}
	
		} elseif(is_array($notifymethods)) {
				/* Listbox */
				$out .= '<select name="notify[method]">';
				foreach($notifymethods as $no=>$met) {
					$out .= '<option value="'.$met.'"';
					if(isset($val['notify']['method']) &&
					  $val['notify']['method'] == $met) {
						$out .= ' selected=""';
					}
					$out .= '>';
		
					if(array_key_exists($met, $this->notifystrings)) {
						$out .= $this->notifystrings[$met];
					} else {
						$out .= $met;
					}
					$out .= '</option>';
				}
				$out .= '</select>';
				
		} elseif($notifymethods == false) {
				$out .= '<input name="notify[method]" value="'.$val['notify']['method']. '" size="20" />';
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
				if(isset($val['notify']['options'])) {
					$out .= $val['notify']['options'];
				}
			}
			$out .= '" /><br />';
		
			global $prioritystrings;
			
			$out .= 'Priority: <select name="notify[priority]">';
			foreach($prioritystrings as $pr=>$te) {
				$out .= '<option value="'.$pr.'"';
				if(isset($edit)) {
					if(isset($val['notify']['priority']) && $val['notify']['priority'] == $pr) {
						$out .= ' checked=""';
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
				if(isset($val['notify']['message'])) {
					$out .= $val['notify']['message'];
				}
			}
			$out .= '</textarea><br />';
			
			$out .= '<small>';
			$out .= _("Help: Valid variables are:");
			if($this->oldcyrus) {
				/* $text$ is not supported by Cyrus IMAP < 2.3 . */
				$out .= ' $from$, $env-from$, $subject$</small>';
			} else {
				$out .= ' $from$, $env-from$, $subject$, $text$, $text[n]$</small>';
			}
			
			$out .= '</blockquote>';
		return $out;
	}
}

/**
 * Keep a copy in INBOX marked as Deleted
 */
class avelsieve_action_keepdeleted extends avelsieve_action {
	var $num = 0;
	var $name = 'keepdeleted';
	var $capability = 'imapflags';

	function avelsieve_action_keepdeleted($rule = array(), $frontend = 'html') {
		$this->avelsieve_action($rule, $frontend);
		$this->text = _("Also keep copy in INBOX, marked as deleted.");
	}
}

?>
