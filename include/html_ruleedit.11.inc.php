<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: html_ruleedit.11.inc.php,v 1.13 2007/03/23 12:38:28 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004-2007 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/** Includes */
include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_ruleedit.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_rule_spam.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_buildrule.11.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/junkmail.inc.php');

/**
 * Rule #11: Customized Junk Mail rule with features such as RBL
 * checking, Sender Address Verification (SAV) etc.
 *
 * @package plugins
 * @subpackage avelsieve
 */
class avelsieve_html_edit_11 extends avelsieve_html_edit_spamrule {
    /**
     * Constructor, that just calls the parent one.
     */     
    function avelsieve_html_edit_11(&$s, $mode = 'edit', $rule = array(), $popup = false, $errmsg = '') {
        global $avelsieve_rules_settings;
        $this->settings = $avelsieve_rules_settings[11];
        $this->avelsieve_html_edit($s, $mode, $rule, $popup, $errmsg);

        if($this->settings['spamrule_tests_ldap'] == true) {
             $ldap_rbls = avelsieve_askldapforrbls();
             if(is_array($ldap_rbls)) {
                 // 1) Overwrite Settings to put ALL tests.
                 $this->settings['spamrule_tests']['rbls']['available'] = array();
                 foreach($ldap_rbls as $no=>$info) {
                    $this->settings['spamrule_tests']['rbls']['available'][$info['test']] = $info['name'];
                    // 2) Overwrite default rule to put ALL tests.
                    $this->settings['default_rule']['tests'][$info['test']] =
                            $this->settings['spamrule_tests']['rbls']['fail_values'][0];
                 }
             }
        }
    }

    /**
     * Submit buttons for Junk Mail: For Usability, just use "Apply Changes" 
     * string always.
     *
     * @return string
     */
	function submit_buttons() {
        $out = '<tr><td><div style="text-align: center">'.
            '<input type="submit" name="'. ($this->mode == 'addnew' ? 'addnew' : 'apply') . '" value="'._("Apply Changes").'" style="font-weight: bold" />';
        if($this->popup) {
            $out .= ' <input type="submit" name="cancel" onClick="window.close(); return false;" value="'._("Cancel").'" />';
        } else {
            $out .= ' <input type="submit" name="cancel" value="'._("Cancel").'" />';
        }
		return $out;
    }
    
    /**
     * Spamrule module settings (Reusable function)
     *
     * @return string
     */
    function module_settings($module) {
        $out = '';
        $t = &$this->rule['tests']; // Handy reference to current rule's tests

        foreach($this->settings['spamrule_tests'][$module]['available'] as $key=>$val) {
            // Check current state
            $active_value = '';
            if(isset($this->rule['tests'][$key]) &&
             in_array($this->rule['tests'][$key], $this->settings['spamrule_tests'][$module]['fail_values'])) {
                $active_value = $this->rule['tests'][$key];
            }

            $radio = false;
            if(sizeof($this->settings['spamrule_tests'][$module]['fail_values']) > 1 ) {
                $radio = true;
            }

            foreach($this->settings['spamrule_tests'][$module]['fail_values'] as $fv) {
                $jskey = str_replace('.', '_', $key . '__'.$fv); // because dot (.) is not valid in js
                
                // Checkbox
                $out .= '<li><input type="'.($radio ? 'checkbox' : 'checkbox').'" name="tests['.$key.']" id="'.$key.'_'.$fv.'" value="'.$fv.'" '; 
                $out .= ' onclick="radioCheck(this,\''.$key.'_'.
                        implode( '\',\''.$key.'_', $this->settings['spamrule_tests'][$module]['fail_values']) .
                        '\'); return true;"';

                $out .= ($active_value == $fv ? ' checked=""' : '') .'/>'.
                      '<label for="'.$key.'_'.$fv.'">'.
                      ( isset($this->settings['custom_text'][$key][$fv]) ? $this->settings['custom_text'][$key][$fv] : $val) .
                      '</label>' ;
                
                // Js Link to toggle informational text display.
                if($this->js && isset($this->settings['spamrule_tests_info'][$key][$fv])) {
                    $out .= '  <small><a class="avelsieve_expand_link" onclick="'.$this->js_toggle_display("div_$jskey", '', 1).'return true;">';
                    $out .= '<img src="images/triangle.gif" alt="&gt;" name="div_'.$jskey.'_img" id="'.$jskey.'_img" border="0" /> '.
                        _("Information...") . '</a></small>';
                }

                // Informational text
                if(isset($this->settings['spamrule_tests_info'][$key][$fv]['desc'])) {
                    $out .= '<br/><div class="avelsieve_quoted" id="div_'.$jskey.'"'. ($this->js ? 'style="display:none"' : '') .'><blockquote>'.
                        '<img src="images/icons/information.png" alt="(i)" border="0" />'. ' ' .
                        $this->settings['spamrule_tests_info'][$key][$fv]['desc'].
                            ( isset($this->settings['spamrule_tests_info'][$key][$fv]['url']) ? 
                            '<br/><a href="'.$this->settings['spamrule_tests_info'][$key][$fv]['url'].'" target="_blank">'.
                            '<img src="images/external_link.png" alt="[]" border="0" /> '.
                            htmlspecialchars($this->settings['spamrule_tests_info'][$key][$fv]['url']).'</a>' : '')  .
                        '</blockquote></div>';
                }
            }

            $out .= '</li>';
        }
        return $out;
    }

    /**
     * Main function that outputs a form for editing a whole rule.
     *
     * @param int $edit Number of rule that editing is based on.
     * @return string
     */
    function edit_rule($edit = false) {
        global $PHP_SELF, $color, $javascript_on, $compose_new_win;
        
        $default_rule = avelsieve_buildrule_11($this->settings['default_rule'], true); 
        $default_rule_desc = $default_rule[1]; 
        
        $out = '<form name="addrule" action="'.$PHP_SELF.'" method="POST">';

        if($this->mode == 'edit') {
            /* 'edit' */
            $out .= '<input type="hidden" name="edit" value="'.$edit.'" />'.
                //$this->table_header( _("Junk Mail Options") ).
                $this->all_sections_start();
        } else {
            /* 'duplicate' or 'addnew' */
            $out .= '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
                //$this->table_header( _("Junk Mail Options") ).
                $this->all_sections_start();
        }
        /* ---------- Error (or other) Message, if it exists -------- */
        $out .= $this->print_errmsg();
        
        /* ---------- If adding the default rule, let the user know -------- */
        if($this->mode == 'addnew') {
            $out .= $this->section_start( _("Add New Rule") ).
                '<div style="text-align:center">'.
                '<p><img src="images/icons/information.png" alt="(i)" border="0" /> '. 
                sprintf( _("There is currently no Junk Mail Filter, so we are suggesting the following default settings. To add the Junk Mail Filter, simply choose <em>&quot;%s&quot;</em>."), _("Apply Changes")).
                '</p>'.
                '</div>' . $this->section_end();
        }

        
        /* ---------- Referrer hidden input fields ------------ */
        $out .= $this->referrer_html();

        /* --------------------- module settings ----------------------- */
        $out .= $this->section_start( _("Junk Mail Options") );

        $out .= '<div id="div_junkmail_all" class="avelsieve_div">';

        $out .= '<input type="checkbox" name="enable" id="junkmail_enable" value="1" '. $this->stateCheckbox('enable') .
                ($this->js ? 'onclick="'.$this->js_toggle_display('div_junkmail_enable') : '' ) .'return true;" />
                <label for="junkmail_enable">'. _("Enable Junk Mail Filtering") .'</label>'.
                
                '<div id="div_junkmail_enable" '. $this->stateVisibility('enable'). '>';

        // junkmail_prune + junkmail_days
        if(isset($this->rule['junkmail_days']) && is_numeric($this->rule['junkmail_days'])) {
            $junkfolderDays = $this->rule['junkmail_days'];
        } else {
            $junkfolderDays = $this->settings['default_rule']['junkmail_days'];
        }
        $form = '<select size="0" name="junkmail_days">';
        for($i=1; $i<=30; $i++) {
            $form .= '<option value="'.$i.'"'. ($junkfolderDays == $i ? ' selected=""' : '' ) . '>'.$i.'</option>';
        }
        $form .= '</select>';

        $out .= '<br/><input type="checkbox" name="junkmail_prune" id="junkmail_prune" value="1"'.
                ($this->js ? 'onclick="'.$this->js_toggle_display('span_junkmail_days', 1) : '' ) .'return true;" '.
                $this->stateCheckbox('junkmail_prune') . '/>'.
                '<label for="junkmail_prune">'. _("Automatically delete Junk Messages") . '</label>' .

                '<span id="span_junkmail_days" '.$this->stateVisibility('junkmail_prune').'> '.
                sprintf( _("when they are older than %s days"),$form) . '</span>';
        

        // Whitelist
        $out .= '<br/><input type="checkbox" name="enable_whitelist" id="enable_whitelist" value="1"'. $this->stateCheckbox('enable_whitelist') .
                ($this->js ? 'onclick="'.$this->js_toggle_display('div_whitelist') : '' ) .'return true;" />'.
                '<label for="enable_whitelist">'. _("Enable Whitelist") . '</label>';
        
        $out .= '<div id="div_whitelist" class="avelsieve_div"'. $this->stateVisibility('enable_whitelist') .'>' .
                '<p>'. _("Messages sent from the addresses in your Whitelist will never end up in your Junk Mail folder, or considered as SPAM.").
                '</p><p>';
        
        $whitelist_url = 'edit.php?addnew=1&amp;type=12';
        if($compose_new_win == '1') {
            $whitelist_url .= '&amp;popup=1';
        }
        
        if($compose_new_win == '1') {
            if($javascript_on) {
                $out .= "<a href=\"javascript:void(0)\" onclick=\"comp_in_new('$whitelist_url')\">";
            } else {
                $out .= '<a href="'.$url.'" target="_blank">';
            }
        } else {
            $out .= '<a href="'.$url.'">';
        }

        $out .= '<strong>'. _("Edit Whitelist....") . '</strong></a></p>';

        $out .= '<input type="checkbox" name="whitelist_abook" id="whitelist_abook"  value="1"'. $this->stateCheckbox('whitelist_abook') . '/>' .
                '<label for="whitelist_abook">'. _("Automatically add all your Address Book Contacts in the Whitelist") . '</label>';

        $out .= '</div>'; // div_whitelist

        // advanced junk mail tests
        $out .= '<br/><input type="checkbox" name="junkmail_advanced" id="junkmail_advanced"  value="1"' . $this->stateCheckbox('junkmail_advanced') .
                ($this->js ? 'onclick="'.$this->js_toggle_display('div_junkmail_advanced') : '' ) .'return true;" />'.
                '<label for="junkmail_advanced">'. _("Configure Advanced Junk Mail Tests") . '</label>';

        $out .= '<div id="div_junkmail_advanced" class="avelsieve_div" '. $this->stateVisibility('junkmail_advanced') . '>' ;

        $out .= '<ul>';
        foreach($this->settings['spamrule_tests'] as $module => $info) {
            $out .= '<li>'. $info['action'] .'<br/></li>'.
                '<ul>' . $this->module_settings($module) . '</ul>';
        }
        $out .= '</ul>';
        $out .= '</div>'; // div_junkmail_advanced
        $out .= $this->section_end();

        $out .= '</div>'; // div_junkmail_enable
        
        $out .= '</div>'; // div_junkmail_all
        
        $out .= $this->all_sections_end() .
                $this->submit_buttons();
        return $out;
    }

    /**
     * Process HTML submission from namespace $ns (usually $_POST),
     * and put the resulting rule structure in $this->rule class variable.
     *
     * @param array $ns
     * @param array $rule
     * @param boolean $truncate_empty_conditions 
     * @return void
     */
    function process_input(&$ns, $unused = false) {
        global $username;

        $vars = array('enable', 'junkmail_prune',
                'enable_whitelist', 'whitelist_abook', 'junkmail_advanced');
        
        foreach($vars as $v) {
            if(isset($ns[$v]) && $ns[$v]) { 
                $this->rule[$v] = 1; 
            } else {
                $this->rule[$v] = 0;
            }
        }
        
        if(isset($ns['junkmail_days']) && is_numeric($ns['junkmail_days'])) { 
            $this->rule['junkmail_days'] = $ns['junkmail_days'];
        }

        if($this->rule['enable']) {
            if(isset($this->rule['disabled'])) unset($this->rule['disabled']);
        } else {
            $this->rule['disabled'] = 1; 
        }

        if($this->rule['junkmail_advanced']) {
            foreach($this->settings['spamrule_tests'] as $groupname => $group) {
                foreach($group['available'] as $test=>$desc) {
                    if(isset($ns['tests'][$test]) && in_array($ns['tests'][$test], $this->settings['spamrule_tests'][$groupname]['fail_values'])) {
                        $this->rule['tests'][$test] = $ns['tests'][$test];
                    } else {
                        if(isset($this->rule['tests'][$test])) {
                            unset($this->rule['tests'][$test]);
                        }
                    }
                }
            }
        } else {
            if(isset($this->rule['tests'])) unset($this->rule['tests']);
            $this->rule['tests'] = $this->settings['default_rule']['tests'];
        }

        if(empty($this->rule['tests'])) {
            $this->errmsg = _("You have to enable at least one Junk Mail Test.");
        }

        // Hardcoded ATM:
        $this->rule['action'] = 7;
        $this->rule['stop'] = 1;

        if(empty($this->errmsg)) {
            if(!empty($this->settings['junkprune_backend'])) {
                $updateFunc = 'avelsieve_junkprune_'.$this->settings['junkprune_backend'].'_update';
                call_user_func($updateFunc, $username, $this->rule['junkmail_days']);
            }
        }
    }
    
    /**
     * Return a customized "Rule has been successfully changed"-type message.
     * @return string
     */
    function getSuccessMessage() {
        return '<strong>'.  _("Junk Mail Options have been saved.") . '</strong>';
    }
}

