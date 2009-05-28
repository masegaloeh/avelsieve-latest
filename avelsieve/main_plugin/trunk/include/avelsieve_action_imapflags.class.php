<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * @version $Id$
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2009 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */


/**
 * Set IMAP flags.
 *
 * The avelsieve structure that corresponds to the Sieve actions defined in RFC5232
 * is as follows:
 *
 * 'setflag' => array,    (Sets & replaces flags for following actions)
 * 'addflag' => array,    (Adds flags to internal variable)
 * 'removeflag' => array  (Removes flags to internal variable)
 * 'flags' => array       (Only for a parameter to a certain action, either fileinto
 *                         or keep, does not change internal Sieve variable)
 *
 * where each array has the flags stored as array _keys_. (The value doesn't matter).
 */
class avelsieve_action_imapflags extends avelsieve_action {
    var $num = 0;
    var $name = 'imapflags';
    var $capability = 'imap4flags';
    var $image_src = 'images/icons/tag_blue.png';
    
    var $options = array(
        'imapflags' => array(
            'on' => '',
            'setflag' => array(),
            'addflag' => array(),
            'removeflag' => array(),
            'flags' => array(),
        )
    );
    
    var $two_dimensional_options = true;

    function avelsieve_action_imapflags(&$s, $rule = array()) {
        $this->init();
        $this->text = _("Flag");
        $this->helptxt = _("Set message flags");
        $this->avelsieve_action($s, $rule);

        $this->imapflagsGroups = array(
            'standard' => _("Standard Flags"),
            'labels' => _("Message Labels"),
            'custom' => _("Custom Flags"),
        );

        $this->imapflags = array(
            'standard' => array(
                '\\\\Seen' => _("Seen"),
                '\\\\Answered' => _("Answered"),
                '\\\\Flagged' => _("Flagged"),
                '\\\\Deleted' => _("Deleted"),
                '\\\\Draft' => _("Draft"),
            ),
            'labels' => array(
                '$Important' => _("Important"),
                '$Work' => _("Work"),
                '$Personal' => _("Personal"),
                '$ToDo' => _("To Do"),
                '$Later' => _("Later"),
                '$Junk' => _("Junk"),
                '$NotJunk' => _("Not Junk"),
            ),
            'custom' => array(
            ),
        );
    }
    
    /**
     * Markup for options for "additional action"
     *
     * @return string
     */
    function options_html($val) {
        $out = '<blockquote>';
        $out .= $this->_set_of_checkboxes('flags', $this->rule['imapflags']['flags']);
        $out .= '</blockquote>';
        return $out;
    }

    /**
     * Generates :flags argument with list of flags stored in 'flags' action
     *
     * @return array ($out, $text, $terse)
     */
    public function generate_sieve() {
        $out = $text = $terse = '';
        if(!empty($this->rule['imapflags']) && !empty($this->rule['imapflags']['flags'])) {
            $out = ':flags ["' . implode('", "', array_keys($this->rule['imapflags']['flags'])). '"] ';

            // Retrieve human-readable (and localized) descriptions
            $descs = array();
            foreach($this->imapflags as $key => $val) {
                foreach($val as $k=>$v) {
                    $descs[$k] = $v;
                }
            }
            $flags = array_keys($this->rule['imapflags']['flags']);
            $flagsHumanReadable = array();
            foreach($flags as $flag) {
                if(isset($descs[$flag])) {
                    $flagsHumanReadable[] = $descs[$flag];
                } else {
                    $flagsHumanReadable[] = $flag;
                }
            }

            if(sizeof($flagsHumanReadable) == 1) {
                $text = sprintf(_("Also, set message flag %s"), '<strong>' . implode(' ', $flagsHumanReadable) . '</strong>');
            } else {
                $text = sprintf(_("Also, set message flags: %s"), '<strong>' . implode('</strong>, <strong>', $flagsHumanReadable) . '</strong>');
            }
            $terse = sprintf( _("Flags: %s"), implode(' ', $flagsHumanReadable));
        }
        return array($out, $text, $terse);

    }

    /**
     * Set of checkboxes for a certain action
     *
     * @param string $action 
     * @param array $rulePart reference to the part of the rule where the certain imap4flags
     *   action is stored
     * @return string
     */
    private function _set_of_checkboxes($action, &$rulePart) {
        global $base_uri;
        $out = '';
        $out .= '<table style="border:none" cellpadding="2" cellspacing="1">';
        foreach($this->imapflagsGroups as $group => $groupDesc) {
            $out .= '<tr><td align="left" valign="top">
                    <img src="'.$base_uri.'plugins/avelsieve/images/icons/tag_blue'.($group == 'custom' ? '_edit' : '') .'.png" /> '.
                    '<strong><small>'. $groupDesc.'</small></strong>'.
                '</td>';

            foreach($this->imapflags[$group] as $f => $t) {
                $out .= '<td align="left" style="white-space: nowrap;">'.
                    '<input type="checkbox" name="imapflags['.$action.']['.$this->_encode_flag($f).']" '.
                    'value="1" id="'.$this->_encode_flag('action_flag_'.$f).'" ';
                if(isset($rulePart[$f]) && $rulePart[$f]) {
                    $out .= 'checked="CHECKED" ';
                }
                $out .= '/><label for="'.$this->_encode_flag('action_flag_'.$f).'"> '.$t.'</label>'.
                    '</td>';
            }

            if($group == 'custom') {
                $out .= '<td colspan="'.sizeof($this->imapflags['labels']).'">'.
                    '<input type="text" name="imapflags[custom]['.$action.']" size="35" value="'.$this->_format_rest_of_imapflags($rulePart).'" />'.
                    '<br/><small>'._("Enter here a list of flags separated by spaces. Example: <tt>\$MyFlag \$Special</tt>"). '</small>'.
                    '</td>';
            }

            $out .= '</tr>';
        }
        $out .= '</table>';
        return $out;
    }

    /**
     * Imap flags have characters such as $ and \ that are not allowed in HTML id & name attributes
     * (CNAME). This function encodes them accordingly.
     *
     * @param string $f
     * @return string
     */
    private function _encode_flag($f) {
        return base64_encode($f);
    }

    private function _format_rest_of_imapflags($rulePart) {
        if(empty($rulePart)) return '';
        $existing = $rulePart;
        $rest = array();

        foreach($existing as $flag => $x) {
            // Foreach existing flag, check if it exists in the predefined checkboxes.
            $exists = false;
            foreach($this->imapflagsGroups as $group => $t) {
                foreach($this->imapflags[$group] as $f => $t) {
                    if($flag == $f) {
                        $exists = true;
                        continue 3;
                    }
                }
            }
            if(!$exists) {
                $rest[$flag] = 1;
            }
        }
        if(!empty($rest)) {
            return implode(' ', array_keys($rest));
        }
        return '';
    }
}

