<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: search_integration.inc.php,v 1.1 2006/01/13 16:25:28 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'plugins/avelsieve/include/managesieve_wrapper.inc.php');

function avelsieve_search_integration_do() {
    global $mailbox_array, $biop_array, $unop_array, $where_array, $what_array,
        $exclude, $color, $compose_new_win;
    
    $cond = asearch_to_avelsieve($mailbox_array, $biop_array, $unop_array, $where_array, $what_array, $exclude, $info);
    $rule = array('cond' => $cond);

    /*
    print "<PRE>";
    print "\nMailbox:";
    print_r($mailbox_array);
    print "\nBiop:";
    print_r($biop_array);
    print "\nUnop:";
    print_r($unop_array);
    print "\nWhere:";
    print_r($where_array);
    print "\nWhat:";
    print_r($what_array);
    print "\nExclude:";
    print_r($exclude_array);
    print "\nSub:";
    print_r($sub_array);
    
    print "\nCond:";
    print_r($cond);
    print "</PRE>";
    */
	
    if(!empty($cond)) {
        bindtextdomain('avelsieve', SM_PATH . 'plugins/avelsieve/locale');
	    textdomain ('avelsieve');
        
        $url = '../plugins/avelsieve/edit.php?addnew=1&amp;type=1&amp;serialized_rule='.rawurlencode(serialize($rule));

        echo '<div align="center" style="text-align:center; font-size:120%; padding: 0.3em;">';
        echo '<a href="'.$url.'" style="font-size: 120%"><strong>'. _("Create Filter") . '</strong></a> ' .
            _("(Creates a new server-side filtering rule, based on the above criteria)") . '</a>';

        if(isset($info['features_disabled'])) {
            echo '<br/><em>' .
                _("Notice: The following criteria cannot be expressed as server-side filtering rules:") . '</em><ul>';

            foreach($info['disabled_criteria'] as $no) {
                $mailbox_array_tmp = array($mailbox_array[$no]);
                $biop_array_tmp = array($biop_array[$no]);
                $unop_array_tmp = array($unop_array[$no]);
                $where_array_tmp = array($where_array[$no]);
                $what_array_tmp = array($what_array[$no]);
                if(isset($exclude_array[$no])) {
                    $exclude_array_tmp = array($exclude_array[$no]);
                } else {
                    $exclude_array_tmp = array();
                }
                if(isset($sub_array[$no])) {
                $sub_array_tmp = array($sub_array[$no]);
                    $sub_array_tmp = array($sub_array[$no]);
                } else {
                    $sub_array_tmp = array();
                }

                echo '<li>('. ($no+1). ') ' .
                    asearch_get_query_display($color, $mailbox_array_tmp, $biop_array_tmp, $unop_array_tmp,
                    $where_array_tmp, $what_array_tmp, $exclude_array_tmp, $sub_array_tmp);
            
                if(isset($info['disabled_criteria_reasons'][$no])) {
                    echo ' <small>(' . _("Reason:") . ' ' . $info['disabled_criteria_reasons'][$no] . ')</small>';
                }
                echo '</li>';
            }
            echo '</ul><br/>';
        }
        echo '</div>';
	
        bindtextdomain('squirrelmail', SM_PATH . 'locale');
    	textdomain ('squirrelmail');
    }
}

/**
 * Map the query data from an advanced search to an avelsieve filter.
 *
 * @param array $mailbox_array
 * @param array $biop_array
 * @param array $unop_array
 * @param array $where_array
 * @param array $what_array
 * @param array $exclude 
 * @param array $info Some additional information that can be passed back to
 *  the caller. For instance, if $info['features_disabled'] exists, then not
 *  all search criteria could be made into Sieve rules.
 * @return array The condition part of an avelsieve rule structure.
 * @todo implement avelsieve_initialize()
 */
function asearch_to_avelsieve(&$mailbox_array, &$biop_array, &$unop_array, &$where_array, &$what_array, &$exclude, &$info) {
    global $sieve_capabilities;
    if(!isset($sieve_capabilities)) {
        sqgetGlobalVar('sieve_capabilities', $sieve_capabilities, SQ_SESSION);
        if(!isset($sieve_capabilities)) {
            // Have to connect to timsieved to get the capabilities. Luckily
            // this will only happen once.
            // TODO
            // avelsieve_initialize();
            print "Please Click on the &quot;Filters&quot; page once! :/";
        }
    }
    $cond = array();
    $info = array();

    foreach($where_array as $no=>$w) {
        if(!isset($idx)) {
            $idx = 0;
        }
        if($no == 0 || !isset($exclude[$no])) {
            switch($w) {
                /* ----------- Header match ---------- */
                case 'FROM':
                case 'SUBJECT':
                case 'TO':
                case 'CC':
                case 'BCC':
                    $cond[$idx]['type'] = 'header';
                    $cond[$idx]['header'] = ucfirst(strtolower($w));
                    $cond[$idx]['matchtype'] = 'contains';
                    $cond[$idx]['headermatch'] = $what_array[$no];
                    break;

                /* ----------- Header match - Specialized "any" Header ---------- */
                case 'HEADER':
                    $cond[$idx]['type'] = 'header';
                    $cond[$idx]['matchtype'] = 'contains';

                    preg_match('/^([^:]+):(.*)$/', $what_array[$no], $w_parts);

                    if (count($w_parts) == 3) {
                        /* This canonicalization will better have to be dealt
                         * with inside avelsieve itself */
                        $hdr = str_replace(':', '', ucfirst(strtolower($w_parts[1])));
                        if(($pos = strpos($hdr, '-')) !== false) {
                           $hdr[$pos+1] = strtoupper($hdr[$pos+1]);
                        }
                        $cond[$idx]['header'] = $hdr;
                        $cond[$idx]['headermatch'] = $w_parts[2];
                        unset($w_parts);
                    }
                    break;
                
                /* ----------- Header OR Body ---------- */
                case 'TEXT':
                    $cond[$idx]['type'] = 'header';
                    $cond[$idx]['matchtype'] = 'contains';
                    $cond[$idx]['headermatch'] = $what_array[$no];
                    
                    if(avelsieve_capability_exists('body')) {
                        $idx++;
                        $cond[$idx]['type'] = 'body';
                        $cond[$idx]['matchtype'] = 'contains';
                        $cond[$idx]['bodymatch'] = $what_array[$no];
                        $cond['condition'] = 'or';
                    } else {
                        $idx--;
                        $info['features_disabled'] = true; 
                        $info['disabled_criteria'][] = $no;
                        $info['disabled_criteria_reasons'][$no] = _("The Body extension is not supported in this server.");
                    }
                    break;
                
                /* ----------- Size ---------- */
                case 'LARGER':
                case 'SMALLER':
                    $cond[$idx]['type'] = 'size';
                    if($w == 'LARGER') {
                        $cond[$idx]['sizerel'] = 'bigger';
                    } elseif($w == 'SMALLER') {
                        $cond[$idx]['sizerel'] = 'smaller';
                    }
                    $cond[$idx]['sizerel'] = '';
                    $cond[$idx]['sizeamount'] = floor($what_array[$no] / 1024);
                    $cond[$idx]['sizeunit'] = 'K';
                    break;


                /* ----------- Body ---------- */
                case 'BODY':
                    if(avelsieve_capability_exists('body')) {
                        $cond[$idx]['type'] = 'body';
                        $cond[$idx]['matchtype'] = 'contains';
                        $cond[$idx]['bodymatch'] = $what_array[$no];
                    } else {
                        $idx--;
                        $info['features_disabled'] = true; 
                        $info['disabled_criteria'][] = $no;
                        $info['disabled_criteria_reasons'][$no] = _("The Body extension is not supported in this server.");
                    }
                    break;
                
                /* ----------- All ---------- */
                case 'ALL':
                    $cond[$idx]['type'] = 'all';
                    break;
                
                /* ----------- Rest, unsupported + catch ---------- */
                case 'ANSWERED':
                case 'DELETED':
                case 'DRAFT':
                case 'FLAGGED':
                case 'KEYWORD':
                case 'NEW':
                case 'OLD':
                case 'RECENT':
                case 'SEEN':
                case 'UNANSWERED':
                case 'UNDELETED':
                case 'UNDRAFT':
                case 'UNFLAGGED':
                case 'UNKEYWORD':
                case 'UNSEEN':

                case 'BEFORE':
                case 'ON':
                case 'SENTBEFORE':
                case 'SENTON':
                case 'SENTSINCE':
                case 'SINCE':

                case 'UID':
                default:
                    /* Unsupported; stay at same index */
                    $info['features_disabled'] = true; 
                    $info['disabled_criteria'][] = $no;
                    $info['disabled_criteria_reasons'][$no] = _("These search expressions are not applicable during message delivery.");
                    $idx--;
                    break;

            }
        }
        $idx++;
    }
    if(sizeof($cond) > 1 && isset($biop_array[1])) {
        switch($biop_array[1]){
            case 'ALL':
                $cond['condition'] = 'and';
                break;
            case 'OR':
                $cond['condition'] = 'or';
                break;
        }
    }
    return $cond;
}
?>
