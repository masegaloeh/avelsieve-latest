<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: html_ruleedit.11.inc.php,v 1.1 2007/01/22 19:48:54 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004-2007 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/** Includes */
include_once(SM_PATH . 'plugins/avelsieve/include/html_main.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/html_ruleedit.inc.php');
include_once(SM_PATH . 'plugins/avelsieve/include/sieve_rule_spam.inc.php');

/**
 * Rule #11: Customized Anti-SPAM rule with features such as RBL
 * checking, Sender Address Verification (SAV) etc.
 *
 * @package plugins
 * @subpackage avelsieve
 */
class avelsieve_html_edit_11 extends avelsieve_html_edit_spamrule {
    /** @var boolean Advanced SPAM rule? */
    var $spamrule_advanced = false;

    /** @var int Initial number of whitelist items (input boxes) to display in 
     * the UI, if none are set in the rule.
     */
    var $whitelistitems = 3;

    /**
     * Constructor, that just calls the parent one.
     */     
    function avelsieve_html_edit_11(&$s, $mode = 'edit', $rule = array(), $popup = false, $errmsg = '') {
        global $avelsieve_rules_settings;
        $this->settings = $avelsieve_rules_settings[11];
        $this->avelsieve_html_edit($s, $mode, $rule, $popup, $errmsg);
    }
    
    /**
     * Spamrule module settings (Reusable function)
     *
     * @return string
     */
    function module_settings($module) {
        $out = '';
        foreach($this->settings['spamrule_tests'][$module]['available'] as $key=>$val) {
            $out .= '<div id="test_'.$key.'"> <p><strong>'.$val.'</strong>';
            $out .= '<br/><input type="radio" name="'.$key.'" value="NONE" id="'.$key.'_NONE" '.
                    ((!isset($this->rule[$key]) || (isset($this->rule[$key]) && $this->rule[$key] == 'NONE')) ? 'checked=""' : '' ). 
                    '/> '.
                    '<label for="'.$key.'_NONE">'. _("No check") . '</label>';

            foreach($this->settings['spamrule_tests'][$module]['values'] as $res=>$res_desc) {
                    $out .= '<br/><input type="radio" name="'.$key.'" value="'.$res.'" id="'.$key.'_'.$res.'" '.
                        
                        ((isset($this->rule[$key]) && $this->rule[$key] == $res) ? 'checked=""' : '' ). 
                        ' /> '.
                        '<label for="'.$key.'_'.$res.'">'.
                        ( isset($this->settings['icons'][$res]) ?  '<img src="'.$this->settings['icons'][$res].'" alt="[]" /> ' : '' ) .
                        $res_desc.'</label>';
                            
            }
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
        global $PHP_SELF, $color;

        if($this->mode == 'edit') {
            /* 'edit' */
            $out = '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
                '<input type="hidden" name="edit" value="'.$edit.'" />'.
                $this->table_header( _("Editing Mail Filtering Rule") . ' #'. ($edit+1) ).
                $this->all_sections_start();
        } else {
            /* 'duplicate' or 'addnew' */
            $out = '<form name="addrule" action="'.$PHP_SELF.'" method="POST">'.
                $this->table_header( _("Create New Mail Filtering Rule") ).
                $this->all_sections_start();
        }
        /* ---------- Error (or other) Message, if it exists -------- */
        $out .= $this->print_errmsg();
        
        /* --------------------- 'module settings ----------------------- */
        
        if(!$this->spamrule_advanced) {
            // FIXME string
            $out .= '<p>'. sprintf( _("Select %s to add the predefined rule, or select the advanced SPAM filter to customize the rule."), '<strong>' . _("Add Spam Rule") . '</strong>' ) . '</p>';

            $out .= '<div width="50%" style="width: 50%; margin-left: auto; margin-right: auto; text-align:center; border: 1px dotted;">'.
                    '<p><a href="#" onclick="ToggleShowDivWithImg(\'lala\')">'.
                    '<img src="images/triangle.gif" alt="&gt;" name="lala_img" border="0" />'. 
                    '<img src="images/icons/information.png" alt="(i)" border="0" />'. ' ' .
                    _("What does the predefined rule contain?") . '</a><p>'.
                    '<div id="lala" style="display:none">Description</div>'.
                    '</div>';

            $out .= '<p style="text-align:center">
                    <input type="submit" name="spamrule_advanced" value="'. _("Advanced Spam Filter...") .'" />
                    </p>';
        } else {
        
            /*
            $out .= $this->section_start( _("Message Spam Checks: RBLs") );
            $out .= '<p>'. _("The following RBLs (Real-time SPAM Black Lists) can be enabled:") . '</p>';
            $out .= $this->module_settings('rbls');
            $out .= $this->section_end();
            
            $out .= $this->section_start( _("Sender Address Verification") );
            $out .= $this->module_settings('sav');
            $out .= $this->section_end();
    
    
            $out .= $this->section_start( _("Additional Verification Tests") );
            $out .= $this->module_settings('additional');
            $out .= $this->section_end();
            */
            $out .= $this->section_start( _("Checks") );
            $out .= $this->section_start( _("Message Spam Checks: RBLs") );
            $out .= '<p>'. _("The following RBLs (Real-time SPAM Black Lists) can be enabled:") . '</p>';
            $out .= $this->module_settings('rbls');
            
            $out .= $this->section_start( _("Sender Address Verification") );
            $out .= $this->module_settings('sav');
    
            $out .= $this->section_start( _("Additional Verification Tests") );
            $out .= $this->module_settings('additional');
            $out .= $this->section_end();
    
            /* --------------------- 'then' ----------------------- */
            
            $out .= $this->section_start( _("Action") );
    
            $spamrule_actions = array('keep', 'discard', 'fileinto');
            foreach($spamrule_actions as $act) $out .= $this->action_html($act);
            $out .= $this->section_end();

            $out .= $this->section_start( _("Additional Actions") );
            $out .= $this->rule_3_additional_actions();
        }

        /* --------------------- buttons ----------------------- */

        $out .= $this->section_end();

        $out .= $this->submit_buttons().
            '</div></td></tr>'.
            $this->all_sections_end() .
            $this->table_footer().
            '</form>';

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
        global $startitems;
        if(isset($ns['spamrule_advanced'])) {
            $this->spamrule_advanced = true;
            $this->rule['advanced'] = true;
        } elseif (isset($edit) && isset($this->rule['advanced'])) {
            $this->spamrule_advanced = true; // FIXME
            $this->rule['advanced'] = 1;
        } else {
            $this->spamrule_advanced = false;
            $this->rule['advanced'] = 0;
        }

        foreach($this->settings['spamrule_tests'] as $groupname => $group) {
            foreach($group['available'] as $test=>$desc) {
                $test_request = str_replace('.', '_', $test); // For $_POST, $_GET variables
                if(isset($ns[$test_request]) && in_array($ns[$test_request], array_merge( array('NONE'), array_keys($group['values']) ) )) {
                    $this->rule['tests'][$test] = $ns[$test_request];
                }
            }
        }
        //print_r($this->rule);
        //$this->errmsg = 'bogus error message for development';

    }
}

