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
 * $Id: sieve.php,v 1.3 2003/10/10 12:59:37 avel Exp $
 */

/**
 * These are just my own wrapper functions around sieve-php.lib.php, with error
 * handling et al.
 */

require_once 'lib/sieve-php.lib.php';
require_once 'avelsieve_support.inc.php';

/**
 * Upload script
 *
 * @param $newscript str The SIEVE script to be uploaded
 *
 * @return true on success, false upon failure
 */
function avelsieve_upload_script ($newscript) {
	global $sieve;


	if($sieve->sieve_sendscript("phpscript", stripslashes($newscript))) {
		return true;
		/* print "Your rules have been successfully updated.<br />"; */

	} else {
		$errormsg = '<p>';
		$errormsg .= _("Unable to load script to server.");
		$errormsg .= '</p>';


		if(isset($sieve->error_raw)) {
			
			$errormsg = '<p>';
			$errormsg .= _("Server responded with:");
			$errormsg .= '<br />';
			
			if (is_array($sieve->error_raw)) {
				foreach($sieve->error_raw as $error_raw) {
	                        	$errormsg .= $error_raw . "<br />";
				}
			} else {
	                	$errormsg .= $sieve->error_raw . "<br />";
			}

		
			/* The following serves for viewing the script that
			 * tried to be uploaded, for debugging purposes. */


			if(AVELSIEVE_DEBUG == 1) {
				$errormsg .= "<br />DEBUG MODE -
				<strong>avelsieve bug</strong> <br /> Script
				that probably is buggy follows.<br /> Please
				copy &amp; paste it, and email it to <a
				href=\"mailto:avel@users.sourceforge.net\">avel@users.sourceforge.net</a>.
				<br /><br /><blockquote><pre>" . $newscript
				."</pre></blockquote>";
			}

			$errormsg .= _("Please contact your administrator.");

			print_errormsg($errormsg);
		}
		return false;
	}
}

/**
 * Delete script.
 *
 * Deletes "phpscript" script on SIEVE server
 *
 * @return true on success, false upon failure
 */
function avelsieve_delete_script () {
	global $sieve;

	if($sieve->sieve_deletescript("phpscript")) {
		return true;
	} else {
		print 'Unable to delete script from server. See server response below:<br />
		<blockquote><font color="red">';
		if(is_array($sieve->error_raw)) {
			foreach($sieve->error_raw as $error_raw)
				print $error_raw."<br>";
		} else {
			print $sieve->error_raw."<br>";
		}
	print "</font></blockquote>";
	return false;
	}
}

/**
 * Check if avelsieve capability exists.
 *
 * avelsieve capability is defined as SIEVE capability, NOT'd with
 * $disable_avelsieve_capabilities from configuration file.
 *
 * $disable_avelsieve_capabilities specifies capabilities to disable. If you
 * would like to force avelsieve not to display certain features, even though
 * there _is_ a capability for them by Cyrus/timsieved, you should specify
 * these here. For instance, if you would like to disable the notify extension,
 * even though timsieved advertises it, you should add 'notify' in this array:
 * $force_disable_avelsieve_capability = array("notify");. This will still
 * leave the defined feature on, and if the user can upload her own scripts
 * then she can use that feature; this option just disables the GUI of it.
 *
 * @param $cap capability to check for
 *
 * @return true if capability exists, false if it does not exist
 */
function avelsieve_capability_exists ($cap) {

	global $disable_avelsieve_capabilities, $sieve_capabilities;
	
	if(in_array($cap, $sieve_capabilities)) {
		if(!in_array($cap, $disable_avelsieve_capabilities)) {
			return true;
		}
	}
	return false;
}


/** 
 * Escape only double quotes and backslashes, as required by SIEVE RFC. For the
 * reverse procedure, PHP function stripslashes() will do.
 */
function avelsieve_addslashes ($string) {

	/* 1) quoted string
	 * 2) str_replace
	 * 3) sieve.lib.php
	 * 4) .....
	 */

	$temp =  str_replace("\\", "\\\\\\\\\\\\\\\\", $string);
	return str_replace('"', "\\\\\\\\\"", $temp);

}


/**
 * Encode script from user's charset to UTF-8.
 */
function avelsieve_encode_script($script) {

	global $languages, $squirrelmail_language, $default_charset;

	/* change $default_charset to user's charset (THANKS Tomas) */
	set_my_charset();

	if(strtolower($default_charset) == 'utf-8') {
		// No need to convert.
		return $script;
	
	} elseif(function_exists('sqimap_mb_convert_encoding')) {
		// sqimap_mb_convert_encoding() returns '' if mb_convert_encoding() doesn't exist!
		$utf8_s = sqimap_mb_convert_encoding($script, 'UTF-8', $default_charset, $default_charset);
		if(empty($utf8_s)) {
			return $script;
		} else {
			return $utf8_s;
		}

	} elseif(function_exists('mb_convert_encoding')) {
		/* Squirrelmail 1.4.0 ? */

		if ( stristr($default_charset, 'iso-8859-') ||
		  stristr($default_charset, 'utf-8') || 
		  stristr($default_charset, 'iso-2022-jp') ) {
			return mb_convert_encoding($script, "UTF-8", $default_charset);
		}
	}

	return $script;
}


/**
 * Decode script from UTF8 to user's charset.
 *
 */
function avelsieve_decode_script($script) {

	global $languages, $squirrelmail_language, $default_charset;

	/* change $default_charset to user's charset (THANKS Tomas) */
	set_my_charset();

	if(strtolower($default_charset) == 'utf-8') {
		// No need to convert.
		return $script;
	
	} elseif(function_exists('sqimap_mb_convert_encoding')) {
		// sqimap_mb_convert_encoding() returns '' if mb_convert_encoding() doesn't exist!
		$un_utf8_s = sqimap_mb_convert_encoding($script, $default_charset, "UTF-8", $default_charset);
		if(empty($un_utf8_s)) {
			return $script;
		} else {
			return $un_utf8_s;
		}

	} elseif(function_exists('mb_convert_encoding')) {
		/* Squirrelmail 1.4.0 ? */

		if ( stristr($default_charset, 'iso-8859-') ||
		  stristr($default_charset, 'utf-8') || 
		  stristr($default_charset, 'iso-2022-jp') ) {
			return mb_convert_encoding($script, $default_charset, "UTF-8");
		}
	}
	return $script;
}

?>
