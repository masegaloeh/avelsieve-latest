<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_buildrule.12.inc.php,v 1.4 2007/03/19 18:08:49 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2007 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Rule type: #12; Description: Global Whitelist
 *
 * This was written for the needs of the University of Athens
 * (http://www.uoa.gr, http://email.uoa.gr)
 *
 * @param array $rule
 * @return array array($out,$text,$terse, array('skip_further_execution'=>true)))
 */
function avelsieve_buildrule_12($rule) {
    global $avelsieve_rules_settings;
    extract($avelsieve_rules_settings[12]);

    $out = '';
    $text = '';
    $terse = '';
    
    $whitelist = array();
    if(isset($rule['whitelist'])) {
        $whitelist = $rule['whitelist'];
    }
    
    $out .= "# Whitelist Addresses Definition\n".
            "# Generated by Avelsieve\n";
    $text .= _("<strong>Whitelist</strong> - The following email addresses are whitelisted and will not end up in Junk folders or considered as SPAM:") . ' ';
    $terse .= '<br/>' . _("Whitelist:") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
    
    $count = 0; $max_count = 6; $too_much_count = 20; // Only for UI purposes
    for($i=0; $i<sizeof($whitelist); $i++ ) {
        $text .= $whitelist[$i];
        if(isset($whitelist[$i+1])) $text .= ', ';
        $terse .= '<li>'. $whitelist[$i] . '</li>';
        $count++;
        if(sizeof($whitelist) > $too_much_count && $count > $max_count) {
            $rest_count = sizeof($whitelist) - $max_count;
            $text .= sprintf( _("and %s more email addresses / expressions."), $rest_count );
            $terse .= '<li><em>'. sprintf( _("%s more entries..."), $rest_count) .'</em></li>';
            break;
        }
    }
    $terse .= '</ul>';
    
    return(array($out,$text,$terse, array('skip_further_execution'=>true)));
}

?>
