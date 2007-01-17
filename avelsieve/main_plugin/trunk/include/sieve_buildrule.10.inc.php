<?php

/**
 * Rule type: #10; Description: generic SPAM-rule, with SPAM-score,
 * whitelist and RBLs defined in the configuration file.
 */
function avelsieve_buildrule_10($rule) {
    global $spamrule_score_default, $spamrule_score_header,
        $spamrule_tests, $spamrule_tests_header, $spamrule_action_default;

    $out = '';
    $text = '';
    $terse = '';
    $tech = '';
    
    $spamrule_advanced = false;
    
    if(isset($rule['advanced'])) {
        $spamrule_advanced = true;
    }
    
    if(isset($rule['score'])) {
        $sc = $rule['score'];
    } else {
        $sc = $spamrule_score_default;
    }
    
    if(isset($rule['tests'])) {
        $te = $rule['tests'];
    } else {
        $te = array_keys($spamrule_tests);
    }
    
    if(isset($rule['action'])) {
        $ac = $rule['action'];
    } else {
        $ac = $spamrule_action_default;
    }
    
    /*
    if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
                header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
            ),
          header :value "gt" :comparator "i;ascii-numeric" "80" ) {
        
        fileinto "INBOX.Junk";
        discard;
    }
        
    // Whitelist scenario:
    if allof( anyof(header :contains "X-Spam-Rule" "Open.Relay.Database" ,
                header :contains "X-Spam-Rule" "Spamhaus.Block.List" 
            ),
          header :value "gt" :comparator "i;ascii-numeric" "80" ,
          not anyof(header :contains "From" "Important Person",
                header :contains "From" "Foo Person"
          )
        ) {
        
        fileinto "INBOX.Junk";
        discard;
    }
    */
    
    $out .= 'allof( ';
    $text .= _("All messages considered as <strong>SPAM</strong> (unsolicited commercial messages)");
    $terse .= _("SPAM");
    $tech .= 'SPAM';
    
    if(sizeof($te) > 1) {
        $out .= ' anyof( ';
        for($i=0; $i<sizeof($te); $i++ ) {
            $out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[$i].'"';
            if($i < (sizeof($te) -1 ) ) {
                $out .= ",";
            }
        }
        $out .= " ),\n";
    } else {
        $out .= 'header :contains "'.$spamrule_tests_header.'" "'.$te[0].'", ';
    }
    
    $out .= "\n";
    $out .= ' header :value "ge" :comparator "i;ascii-numeric" "'.$spamrule_score_header.'" "'.$sc.'" ';
    
    if(isset($rule['whitelist']) && sizeof($rule['whitelist']) > 0) {
        /* Insert here header-match like rules, ORed of course. */
        $text .= ' (' . _("unless") . ' ';
        $terse .= '<br/>' . _("Whitelist:") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
        $tech .= ' !(WHITELIST:<br/>';
    
        $out .= " ,\n";
        $out .= ' not anyof( ';
        for($i=0; $i<sizeof($rule['whitelist']); $i++ ) {
            $out .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
                $rule['whitelist'][$i]['headermatch'] ,'rule');
            $text .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
                $rule['whitelist'][$i]['headermatch'] ,'verbose');
            $terse .= '<li>'. build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
                $rule['whitelist'][$i]['headermatch'] ,'terse') . '</li>';
            $tech .= build_rule_snippet('header', $rule['whitelist'][$i]['header'], $rule['whitelist'][$i]['matchtype'],
                $rule['whitelist'][$i]['headermatch'] ,'tech') . '<br/>';
            if($i<sizeof($rule['whitelist'])-1) {
                $out .= ', ';
                $text .= ' ' . _("or") . ' ';
            }
        }
        $text .= '), '; 
        $tech .= '), '; 
        $terse .= '</ul>'; 
        $out .= " )";
    }
    $out .= " )\n{\n";

    if($spamrule_advanced == true) {
        $text .= _("matching the Spam List(s):");
        $terse .= '<br/>' . _("Spam List(s):") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
        $tech .= '<br/>' . _("Spam List(s):") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
        for($i=0; $i<sizeof($te); $i++) {
            $text .= $spamrule_tests[$te[$i]].', ';
            $terse .= '<li>' . $spamrule_tests[$te[$i]].'</li>';
            $tech .= '<li>' . $spamrule_tests[$te[$i]].'</li>';
        }
        $text .= sprintf( _("and with score greater than %s") , $sc );
        $terse .= '</ul>' . sprintf( _("Score > %s") , $sc);
        $tech .= '</ul>' . sprintf( _("Score > %s") , $sc);
    }
    
    $text .= ', ' . _("will be") . ' ';
    $terse .= '</td><td align="right">';
    $tech .= '</td><td align="right">';
    
    if($ac == 'junk') {
        $out .= 'fileinto "INBOX.Junk";';
        $text .= _("stored in the Junk Folder.");
        $terse .= _("Junk");
        $tech .= 'JUNK';
    
    } elseif($ac == 'trash') {
        $text .= _("stored in the Trash Folder.");
    
        global $data_dir, $username;
        $trash_folder = getPref($data_dir, $username, 'trash_folder');
        /* Fallback in case it does not exist. Thanks to Eduardo
         * Mayoral. If not even Trash does not exist, it will end up in
         * INBOX... */
        if($trash_folder == '' || $trash_folder == 'none') {
            $trash_folder = "Trash";
        }
        $out .= 'fileinto "'.$trash_folder.'";';

        $terse .= _("Trash");
        $tech .= 'TRASH';
    
    } elseif($ac == 'discard') {
        $out .= 'discard;';
        $text .= _("discarded.");
        $terse .= _("Discard");
        $tech .= _("Discard");
    }

    return(array($out,$text,$terse,$tech));
}

?>
