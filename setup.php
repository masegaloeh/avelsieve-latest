<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Copyright (c) 1999-2003 The SquirrelMail Project Team
 * and Alexandros Vellis <avel@users.sourceforge.net>
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * Also view plugins/README.plugins for more information.
 *
 * $Id: setup.php,v 1.2 2003/10/07 13:24:52 avel Exp $
 */
   
// require_once('../functions/i18n.php');

   function squirrelmail_plugin_init_avelsieve() {
      global $squirrelmail_plugin_hooks;
      global $mailbox, $imap_stream, $imapConnection;
      global $avelsieveheaderlink;

      $squirrelmail_plugin_hooks['optpage_register_block']['avelsieve'] =
      'avelsieve_optpage_register_block';
      $squirrelmail_plugin_hooks['menuline']['avelsieve'] =
      'avelsieve_menuline';
/*      $squirrelmail_plugin_hooks['optpage_set_loadinfo']['avelsieve'] =
      'avelsieve_set_loadinfo'; */
   }

   function avelsieve_optpage_register_block() {
      global $optpage_blocks;
      if (defined("SM_PATH")) {
	bindtextdomain ('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
      } else {
        bindtextdomain ('avelsieve', '../plugins/avelsieve/locale');
      }
      textdomain ('avelsieve');

      $optpage_blocks[] = array(
         'name' => _("Message Filters"),
         'url'  => '../plugins/avelsieve/table.php',
	 'desc' => _("Server-Side mail filtering enables you to add criteria in order to automatically forward, delete or place a given message into a folder."),
         'js'   => false
      );
      if (defined("SM_PATH")) {
	bindtextdomain('squirrelmail', SM_PATH . 'locale');
      } else {
      	bindtextdomain ('squirrelmail', '../locale');
      }
      textdomain('squirrelmail');

   }
   
   function avelsieve_menuline() {
	global $avelsieveheaderlink;
   	if(defined("SM_PATH")) {
		include_once(SM_PATH . 'plugins/avelsieve/config.php');
	} else {
		include_once('../plugins/avelsieve/config.php');
	}
   	if($avelsieveheaderlink) {
		/* There shouldn't be a need for the following. Unless a plugin
		   programmer doesn't stay at plugins/ :-) */

 	  	/*
		$cwd = getcwd();
		if (strstr($cwd, "avelsieve")) {
			print "binding to locale";
		        bindtextdomain ('avelsieve', $cwd.'/locale');

		} elseif (strstr($cwd, "plugins/")) {
			print " inside a plugin... binding to ../avelsieve/locale ";
		        bindtextdomain ('avelsieve', '../avelsieve/locale');

		} elseif ( strstr($cwd, "src") || strstr($cwd, "functions") ) {
			print "binding to ../plugins/avelsieve/locale";
		        bindtextdomain ('avelsieve', '../plugins/avelsieve/locale');
		} 
		*/
		if(defined("SM_PATH")) {
			bindtextdomain('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
		} else {
			bindtextdomain('avelsieve', '../plugins/avelsieve/locale');
		}
	        textdomain ('avelsieve');

   		displayInternalLink('plugins/avelsieve/table.php',_("Filters"),'right');
		echo "&nbsp;&nbsp\n";

		if(defined("SM_PATH")) {
			bindtextdomain('squirrelmail', SM_PATH . 'locale');
		} else {
			bindtextdomain('squirrelmail', '../locale');
		}
	        textdomain ('squirrelmail');
	}
   }    

function avelsieve_version() {
	return '0.9.6';
}
 
/* function avelsieve_set_loadinfo() {
	global $optpage;
   	if ($optpage == 'avelsieve') {
		$optpage_name = _("Filters");
		$optpage_file = SM_PATH . 'plugins/avelsieve/table.php';
	}
   } */
?>
