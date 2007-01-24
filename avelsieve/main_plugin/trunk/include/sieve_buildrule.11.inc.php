<?php
/**
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: sieve_buildrule.11.inc.php,v 1.3 2007/01/24 17:14:56 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2007 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Rule type: #11; Description: New-style SPAM-rule with
 * various features.
 *
 * This was written for the needs of the University of Athens
 * (http://www.uoa.gr, http://email.uoa.gr)
 * It might not suit your needs without proper adjustments
 * and hacking.
 *
 * @param array $rule
 * @param boolean $force_advanced_mode This flag is used when i want to get
 *   an analytical textual representation of the spam rule. This is used for
 *   being shown in the UI ("What does the predefined rule contain?").
 * @return array
 */
function avelsieve_buildrule_11($rule, $force_advanced_mode = false) {
    global $avelsieve_rules_settings, $rules;
    extract($avelsieve_rules_settings[11]);

    $out = '';
    $text = '';
    $terse = '';
    $tech = '';
    
    $spamrule_advanced = false;
    
    if(isset($rule['advanced']) && $rule['advanced']) {
        $spamrule_advanced = true;
    }
    
    if(isset($rule['tests'])) {
        $tests = $rule['tests'];
    }
    
    if(isset($rule['action'])) {
        $ac = $rule['action'];
    } else {
        $ac = $spamrule_action_default;
    }
    
    $out .= 'if allof( ';
    $text .= _("All messages considered as <strong>SPAM</strong> (unsolicited commercial messages)");
    $terse .= _("SPAM");
    $tech .= 'SPAM';
    
    $out_part = array();
    foreach($tests as $test=>$val) {
        $out_part[] = 'header :contains "'.$spamrule_tests_header.'" "'.$test.':'.$val.'"';
    }
    if(sizeof($out_part) > 1) {
        $out .= ' anyof( '. implode( ",\n", $out_part ) . "),\n";
    } else {
        $out .= $out_part[0];
    }
    
    /** Placeholder: if there's a score in the future, it should be placed here. */
    //$out .= "\n";
    //$out .= ' header :value "ge" :comparator "i;ascii-numeric" "'.$spamrule_score_header.'" "'.$sc.'" ';
    
    /* Search the global variable $rules, to retrieve the whitelist rule data, if any. */
    for($i=0; $i<sizeof($rules); $i++) {
        if($rules[$i]['type'] == 12 && !empty($rules[$i]['whitelist'])) {
            $whitelistRef = &$rules[$i]['whitelist'];
            break;
        }
    }

    /* And now, use that data to build the actual whitelist in Sieve. */
    if(isset($whitelistRef)) {
        $out .= "\nnot anyof(\n";

        $outParts = array();
        foreach($whitelistRef as $w) {
            $outParts[] = build_rule_snippet('header', 'From', 'contains', $w ,'rule');
            $outParts[] = build_rule_snippet('header', 'Sender', 'contains', $w ,'rule');
        }
        $out .= implode(",\n", $outParts); 
        $out .= ')';

    } else {
        $out .= "true ";
    }
    $out .= ")\n{\n";  // closes 'allof'


    /* The textual descriptions follow */
    if($spamrule_advanced == true || $force_advanced_mode) {
        $text .= '<ul>'; // 1st level ul
        $text .= '<li>' . _("matching the Spam tests as follows:"). '</li><ul style="margin-top: 1px; margin-bottom: 1px;">';
        $terse .= '<br/>' . _("Spam Tests:") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
        $tech .= '<br/>' . _("Spam Tests:") . '<ul style="margin-top: 1px; margin-bottom: 1px;">';
        foreach($tests as $test=>$val) {
            foreach($spamrule_tests as $group=>$data) {
                if(array_key_exists($test, $data['available'])) {
                    $text .= '<li><strong>' . $data['available'][$test]. '</strong> = '. 
                             ( isset($icons[$val]) ? '<img src="'.$icons[$val].'" alt="'.$val.'" /> ' : '') .
                             $val. '</li>';
                    $terse .= '<li>' . $data['available'][$test].'</li>';
                    $tech .= '<li>' . $data['available'][$test].'</li>';
                    break;
                }
            }
        }
        $text .= '</ul><br/>';
        $terse .= '</ul>';
        $tech .= '</ul>';
        
        if(isset($whitelistRef)) {
             $text .= '<li>' . _("and where the sender does <em>not</em> match any of the addresses / expressions in your <strong>Whitelist</strong>") . '</li>';
        }
        $text .= '</ul>'; // 1st level ul
    }
    
    
    /* ------------------------ 'then' ------------------------ */

    $text .= '<br/>' . _("will be") . ' ';
    $terse .= '</td><td align="right">';
    $tech .= '</td><td align="right">';

    /* FIXME - Temporary Copy/Paste kludge */
    switch($rule['action']) {
	case '1':	/* keep (default) */
	default:
		$out .= "keep;";
		$text .= _("<em>keep</em> it.");
		$terse .= _("Keep");
		$tech .= 'KEEP';
		break;
	
	case '2':	/* discard */
		$out .= "discard;";
		$text .= _("<em>discard</em> it.");
		$terse .= _("Discard");
		$tech .= 'DISCARD';
		break;
	
	case '3':	/* reject w/ excuse */
		$out .= "reject text:\n".$rule['excuse']."\r\n.\r\n;";
		$text .= _("<em>reject</em> it, sending this excuse back to the sender:")." \"".htmlspecialchars($rule['excuse'])."\".";
		$terse .= _("Reject");
		$tech .= "REJECT";
		break;
	
	case '4':	/* redirect to address */
		if(strstr(trim($rule['redirectemail']), ' ')) {
			$redirectemails = explode(' ', trim($rule['redirectemail']));
		}
		if(!isset($redirectemails)) {
			if(strstr(trim($rule['redirectemail']), ',')) {
				$redirectemails = explode(',', trim($rule['redirectemail']));
			}
		}
		if(isset($redirectemails)) {
			foreach($redirectemails as $redirectemail) {
				$out .= 'redirect "'.$redirectemail."\";\n";
				$terse .= _("Redirect to").' '.htmlspecialchars($redirectemail). '<br/>';
				$tech .= 'REDIRECT '.htmlspecialchars($redirectemail). '<br/>';
			}
			$text .= sprintf( _("<em>redirect</em> it to the email addresses: %s."), implode(', ',$redirectemails));
		} else {
			$out .= "redirect \"".$rule['redirectemail']."\";";
			$text .= _("<em>redirect</em> it to the email address")." ".htmlspecialchars($rule['redirectemail']).".";
			$terse .= _("Redirect to") . ' ' .htmlspecialchars($rule['redirectemail']);
			$tech .= 'REDIRECT' . ' ' .htmlspecialchars($rule['redirectemail']);
		}
		break;
	
	case '5':	/* fileinto folder */
		$out .= 'fileinto "'.$rule['folder'].'";';

		if(!empty($inconsistent_folders) && in_array($rule['folder'], $inconsistent_folders)) {
			$clr = '<span style="color:'.$color[2].'">';
			$text .= $clr;
			$terse .= $clr;
			$tech .= $clr;
		}
		$text .= sprintf( _("<em>file</em> it into the folder %s"),
			' <strong>' . htmlspecialchars(imap_utf7_decode_local($rule['folder'])) . '</strong>');
		$terse .= sprintf( _("File into %s"), htmlspecialchars(imap_utf7_decode_local($rule['folder'])));
		$tech .= "FILEINTO ".htmlspecialchars(imap_utf7_decode_local($rule['folder']));
		
		if(!empty($inconsistent_folders) && in_array($rule['folder'], $inconsistent_folders)) {
			$cls = '<em>' . _("(Warning: Folder not available)") . '</em></span>';
			$text .= ' '.$cls;
			$terse .= '<br/>'.$cls;
			$tech .= '<br/>'.$cls;
		}
		$text .= '. ';
		break;
    /* END first copy/paste kludge */

        /* Added */
	case '7':	/* junk folder */
        $out .= 'fileinto "INBOX.Junk";';
        $text .= _("stored in the Junk Folder.");
        $terse .= _("Junk");
        $tech .= 'JUNK';
        break;
    
	case '8':	/* junk folder */
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
        break;
    
    }

    return(array($out,$text,$terse,$tech));
}

?>
