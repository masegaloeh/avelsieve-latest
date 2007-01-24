<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id: html_ruleedit.11.inc.php,v 1.3 2007/01/24 17:14:56 avel Exp $
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

    /** @var Spamrule actions that will be available. */
    var $spamrule_actions = array(
        'group_main' => array(
                'radio' => array('keep', 'junk', 'trash'),
                'checkbox' => array('stop')
        ),
        'group_additional' => array(
                'radio' => array('fileinto', 'discard'),
                'checkbox' => array('notify', 'disabled')
        )
    );
    
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
        $t = &$this->rule['tests']; // Handy reference to current rule's tests

        foreach($this->settings['spamrule_tests'][$module]['available'] as $key=>$val) {
            $out .= '<div id="test_'.$key.'"> <p><strong>'.$val.'</strong>';
            $out .= '<br/><input type="radio" name="tests['.$key.']" value="NONE" id="'.$key.'_NONE" '.
                    ((!isset($t[$key]) || (isset($t[$key]) && $t[$key] == 'NONE')) ? 'checked=""' : '' ). 
                    '/> '.
                    '<label for="'.$key.'_NONE"> &nbsp;<small>'. _("No check") . '</small></label>';

            foreach($this->settings['spamrule_tests'][$module]['values'] as $res=>$res_desc) {
                    $out .= '<br/><input type="radio" name="tests['.$key.']" value="'.$res.'" id="'.$key.'_'.$res.'" '.
                        
                        ((isset($t[$key]) && $t[$key] == $res) ? 'checked=""' : '' ). 
                        ' /> '.
                        '<label for="'.$key.'_'.$res.'">'.
                        ( isset($this->settings['icons'][$res]) ?  '<img src="'.$this->settings['icons'][$res].'" alt="[]" /> ' : '' ) .
                        '<em>' . $res . '</em> - ' . $res_desc.'</label>';
                            
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
                // FIXME
                // '<input type="advanced" name="edit" value="'.$this->rule['advanced'].'" />'.
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
            $default_rule = avelsieve_buildrule_11($this->settings['default_rule'], true); 
            $default_rule_desc = $default_rule[1]; 

            // FIXME string
            $out .= '<p>'. sprintf( _("Select %s to add the predefined rule, or select the advanced SPAM filter to customize the rule."), '<strong>' . _("Add Spam Rule") . '</strong>' ) . '</p>';

            $out .= '<div width="50%" style="width: 50%; margin-left: auto; padding: 0.5em; margin-right: auto; text-align:left; border: 1px dotted;">'.
                    '<p><a href="#" onclick="ToggleShowDivWithImg(\'predefined_rule_desc\')">'.
                    '<img src="images/triangle.gif" alt="&gt;" name="predefined_rule_desc_img" border="0" />'. 
                    '<img src="images/icons/information.png" alt="(i)" border="0" />'. ' ' .
                    _("What does the predefined rule contain?") . '</a><p>'.
                        '<div id="predefined_rule_desc" style="display:none">'.$default_rule_desc.'</div>'.
                    '</div>';

            $out .= '<p style="text-align:center">
                    <input type="submit" name="intermediate_action[spamrule_switch_to_advanced]" value="'. _("Advanced Spam Filter...") .'" />
                    </p>';
        } else {
            $out .= '<input type="hidden" name="spamrule_advanced" value="1" />';
        
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
            
            $out .= $this->section_start( '<a name="anchor_action">'. _("Action"). '</a>' );
    

            /**
             * Main spamrule actions
             */
            foreach($this->spamrule_actions['group_main'] as $k=>$sActions) {
                foreach($sActions as $act) $out .= $this->action_html($act);
            }
            
            /**
             * Additional actions: these will initially be in a hidden div.
             * TODO: open this div when there is an action enabled in there.
             */
            $out .= '<br/>'.
                    '<p><a href="#anchor_action" onclick="ToggleShowDivWithImg(\'more_actions\')">'.
                    '<img src="images/triangle.gif" alt="&gt;" name="more_actions_img" border="0" />'. 
                    '<strong>'. _("More Actions") . '</strong></a></p>'.

                    '<div id="more_actions" style="display:none;">';
            foreach($this->spamrule_actions['group_additional'] as $k=>$sActions) {
                foreach($sActions as $act) $out .= $this->action_html($act);
            }
            $out .= '</div>';
            $out .= $this->section_end();
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

        if(isset($ns['intermediate_action']['spamrule_switch_to_advanced'])) {
            // Just switched to advanced. Place the default values.
            $this->rule = $this->settings['default_rule'];
            $this->spamrule_advanced = true;
            $this->rule['advanced'] = 1;
        }elseif(isset($ns['spamrule_advanced'])) {
            $this->spamrule_advanced = true;
            $this->rule['advanced'] = 1;
        } elseif (isset($this->rule['advanced']) && $this->rule['advanced']) {
            $this->spamrule_advanced = true; // FIXME
            $this->rule['advanced'] = 1;
        } else {
            $this->rule = $this->settings['default_rule'];
            $this->spamrule_advanced = false;
            $this->rule['advanced'] = 0;
        }

        $this->rule['type'] = 11;

        foreach($this->settings['spamrule_tests'] as $groupname => $group) {
            foreach($group['available'] as $test=>$desc) {
                if(isset($ns['tests'][$test]) && in_array($ns['tests'][$test], array_merge( array('NONE'), array_keys($group['values']) ) )) {
                    $this->rule['tests'][$test] = $ns['tests'][$test];
                }
            }
        }
        
        // FIXME more variables/options, validation, also, probably gather this stuff
        // (user input processing) in the avelsieve actions classes.
        // Generally, Process input of actions has to be unified/fixed.

        // Gather all actions together
        $actions_radio = array(); // Mutually Exlcusive actions (junk, fileinto,...)
        $actions_checkbox = array(); // Optional actions (stop, notify,...)
        foreach($this->spamrule_actions as $group=>$sActions) {
            $actions_radio = array_merge($sActions['radio'], $actions_radio);
            $actions_checkbox = array_merge($sActions['checkbox'], $actions_checkbox);
        }

        // And now process them from user input
        // foreach($actions_radio as $act)  // Nothing for numeric values, the mapping
        // is currently only known by the respective classes.
        
        if(isset($ns['action']) && is_numeric($ns['action'])) {
            $this->rule['action'] = $ns['action'];
        }
    
        foreach($actions_checkbox as $a) {
            $classname = 'avelsieve_action_'.$a;
            if(class_exists($classname)) {
                $classvars = get_class_vars($classname);

                if(isset($classvars['two_dimensional_options']) && $classvars['two_dimensional_options'] == true) {
                    // Two dimensional: key 'on' has to be checked
                    if(isset($ns[$a]) && isset($ns[$a]['on']) && $ns[$a]['on']) { 
                        $this->rule[$a] = $ns[$a];
                    }
                
                } else {
                    // simple actions such as 'stop'
                    if(isset($ns[$a]) && $ns[$a]) { 
                        $this->rule[$a] = $ns[$a]; 
                    }
                }
                unset($classvars);
            }
        }

        //$this->errmsg = 'This is a bogus error message for development purposes.';
    }
}

