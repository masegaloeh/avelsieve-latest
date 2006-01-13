<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * This page will load in MANAGESIEVE and SIEVE includes.
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve.inc.php,v 1.2 2006/01/13 16:25:28 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/sieve_getrule.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_buildrule.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/managesieve.lib.php');
include_once(SM_PATH . 'plugins/avelsieve/include/managesieve_wrapper.inc.php');

?>
