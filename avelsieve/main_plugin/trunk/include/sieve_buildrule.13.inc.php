<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_buildrule.13.inc.php,v 1.2 2007/03/19 18:08:49 avel Exp $
 * @author Kostantinos Koukopoulos <kouk@noc.uoa.gr>
 * @copyright 2007 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Rule type: #13; Description: Custom Sieve Code
 *
 * @param array $rule
 * @return array array($out,$text,$terse, array('skip_further_execution'=>true, 'replace_output'=>true))
 */
function avelsieve_buildrule_13($rule) {
    global $displaymodes; 
    $sourcelnk = '<a href="'.$_SERVER['SCRIPT_NAME'].'?mode=source" title="'.$displaymodes['source'][1].'">'.$displaymodes['source'][0].'</a>';
    $out = $rule['code']; 
    $text = _("Custom Rule").' ('. _("not avelsieve - view ").$sourcelnk.')';
    $terse = $text; 
    
    return(array($out,$text,$terse, array('skip_further_execution'=>true, 'replace_output'=>true)));
}
?>
