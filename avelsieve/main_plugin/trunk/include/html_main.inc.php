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
 * @version $Id: html_main.inc.php,v 1.1 2004/11/02 15:06:17 avel Exp $
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
		return "<TABLE WIDTH=\"70%\" COLS=1 ALIGN=CENTER cellpadding=4 cellspacing=0 border=0>\n";
	}
	
	/**
	 * All sections table end
	 * @return string
	 */
	function all_sections_end() {
		return "</table>";
	}
	
	/**
	 * Table 'section' start
	 * @return string
	 */
	function section_start($title) {
		global $color, $addrule_error;
		$out = "<TR><TD BGCOLOR=\"$color[9]\" ALIGN=CENTER><B>".
		     $title .
		     "</B></TD></TR>";
	
		if($addrule_error) {
			$out .= '<TR><TD BGCOLOR="'.$color[2].'" ALIGN="CENTER"><p><font color="'.$color[8].'"><strong>'.
			$addrule_error .
		'</strong></font></TD></TR>';
		
		}
		$out .= "<TR><TD BGCOLOR=\"$color[0]\" >";
		return $out;
	}
	
	/**
	 * Table 'section' end
	 * @return string
	 */
	function section_end() {
		global $color;
		$out = "</TD></TR>\n";
		//echo "</table>";
		$out .= "<tr><td bgcolor=\"$color[4]\">&nbsp;</td></tr>\n";
		return $out;
	}
}

?>
