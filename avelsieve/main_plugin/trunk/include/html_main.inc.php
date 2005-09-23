<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * HTML Functions
 *
 * @version $Id: html_main.inc.php,v 1.5 2005/09/23 12:03:48 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Global HTML Output functions. These contain functions for starting/ending
 * sections, as well as header/footer thingies.
 */
class avelsieve_html {
	/**
	 * @var boolean Javascript Enabled?
	 */
	var $js;

	/**
	 * Constructor function will initialize some variables, depending on the
	 * environment.
	 */
	function avelsieve_html() {
		/* Set up javascript variable */
		global $javascript_on;
		if($javascript_on) {
			$this->js = true;
		} else {
			$this->js = false;
		}
	}

	/**
	 * Page Header
	 */
	function header($customtitle = '') {
		$out = '<h1>'._("Server-Side Mail Filtering");
		if($customtitle) {
			$out .= ' - '.$customtitle;
		}
		$out .= '</h1>';
		return $out;
	}
	
	function my_header() {
		global $color;
		return '<br><table width="100%"><tr><td bgcolor="'.$color[0].'">'.
			'<center><b>' . _("Server-Side Mail Filtering"). '</b></center>'.
			'</td></tr></table>';
	}
	
	/**
	 * Squirrelmail-style table header
	 * @return string
	 */
	function table_header($customtitle) {
		global $color;
		$out = '<br>
		<table bgcolor="'.$color[0].'" width="95%" align="center" cellpadding="2" cellspacing="0" border="0">
		<tr><td align="center">
		    <strong>'.
		    _("Server-Side Mail Filtering");
		    
			if($customtitle) {
				$out .= ' - '.$customtitle;
			}
		 
		    $out .= '</strong>
		
		    <table width="100%" border="0" cellpadding="5" cellspacing="0">
		    <tr><td bgcolor="'.$color[4].'" align="center">
		';
		return $out;
	}
	
	/** 
	 * Squirrelmail-style table footer
	 * @return string
	 */
	function table_footer() {
		return '</td></tr></table>'.
			'</td></tr></table>';
	}
	
	/**
	 * All sections table start
	 * @return string
	 */
	function all_sections_start() {
		return '<table width="95%" cols="1" align="center" cellpadding="4" cellspacing="0" border="0">';
	}
	
	/**
	 * All sections table end
	 * @return string
	 */
	function all_sections_end() {
		return '</table>';
	}
	
	/**
	 * Table 'section' start
	 * @return string
	 */
	function section_start($title, $errmsg = '') {
		global $color;
		return '<tr><td bgcolor="'.$color[9].'" align="center">'.
			'<strong>'.$title.'</strong></td></tr>'.
			'<tr><td bgcolor="'.$color[0].'">';
	}
	
	/**
	 * Table 'section' end
	 * @return string
	 */
	function section_end() {
		global $color;
		$out = "</td></tr>\n".
			"<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr>\n";
		return $out;
	}

	/**
 	 * Generic Listbox widget
 	 *
 	 * @param $selected_header Selected header
 	 * @param $n option number
 	 */
	function generic_listbox($name, $options, $selected_option = '') {
		$out = '<select name="'.$name.'">';
		foreach($options as $o => $desc) {
			if ($selected_option==$o) {
				$out .= '<option value="'.$o.'" selected="">'.$desc.'</option>';
			} else {
				$out .= '<option value="'.$o.'">'.$desc.'</option>';
			}
		}
		$out .= '</select>';
		return $out;
	}
	
}

?>
