<?php
/**
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 *
 * @version $Id$
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2009 Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

/**
 * Condition for 'date / time' feature
 */
class avelsieve_condition_datetime extends avelsieve_condition {
    /**
     * The "ui_tree" variable describes how the user interface is structured.
     * Each "varname" array key represents an HTML input widget.
     */
    public $ui;

    /**
     * Constructor, sets up localized variables of the structures that define
     * the various date/time options (properties $this->ui, $this->ui_subnodes)
     *
     * @return void
     */
    function __construct(&$s, $rule, $n) {
        parent::__construct(&$s, $rule, $n);

        $tpl_date_metrics = array(
            'year' => _("Year"),
            'month' => _("Month"),
            'day' => _("Day"),
            'weekday' => _("Weekday"),
            //'date' => _("Date"),
            'hour' => _("Hour"),
            'minute' => _("Minute"),
            'second' => _("Second"),
            'time' => _("Time"),
            'specificdate' => _("Specific Date"),
        );

        $tpl_weekdays = array(
            '0' => _("Sunday"),
            '1' => _("Monday"),
            '2' => _("Tuesday"),
            '3' => _("Wednesday"),
            '4' => _("Thursday"),
            '5' => _("Friday"),
            '6' => _("Saturday"),
        );

        $tpl_date_condition = array(
            'on' => _("On"),
            'before' => _("Before"),
            'after' =>  _("After"),
        );
        $tpl_cond_2 = array(
            'is' => _("Is"),
            'before' => _("Before"),
            'after' =>  _("After"),
            // 'matches' => _("Matches"),
        );

        $tpl_months = array(
            '1' => _("January"),
            '2' => _("February"),
            '3' => _("March"),
            '4' => _("April"),
        );
        
        $this->ui['datetype'] = array(
            'name' => 'datetype',
            'input' => 'select',
            'values' => array(
                'specific_date' => _("Specific Date"),
                'occurence' => _("Occurence"),
            ),
            'children' => array(
                'specific_date' => 'specific_date_conditional',
                'occurence' => 'occurence_metric',
            ),
        );

        $this->ui['specific_date_conditional'] = array(
            'input' => 'select',
            'values' => $tpl_date_condition,
            'children' => array(
                'on' => 'specific_date_picker',
                'before' => 'specific_date_picker',
                'after' => 'specific_date_picker',
            ),
        );
        
        $this->ui['specific_date_picker'] = array(
            'input' => 'datepicker',
            'terminal' => true,
        );

        $this->ui['occurence_metric'] = array(
            'input' => 'select',
            'values' => $tpl_date_metrics,
            'children' => array(),
        );
        foreach($tpl_date_metrics as $k => $v) {
            $this->ui['occurence_metric']['children'][$k] = $k.'_occurence_conditional';
            $this->ui[$k.'_occurence_conditional'] = array(
                'input' => 'select',
                'values' => $tpl_cond_2,
                'children' => array()
            );
            foreach($tpl_cond_2 as $k2 => $v2) {
                $this->ui[$k.'_occurence_conditional']['children'][$k2] = 'occurence_'.$k;
            }
            
            $this->ui['occurence_'.$k] = array(
                'input' => 'text',
                'terminal' => true,
                'children' => array()
            );
        }
        
        $this->ui['occurence_month']['input'] = 'select';
        $this->ui['occurence_month']['values'] = $tpl_months;
        
        $this->ui['occurence_day']['input'] = 'select';
        $this->ui['occurence_day']['values'] = range(1, 31);
        
        $this->ui['occurence_weekday']['input'] = 'select';
        $this->ui['occurence_weekday']['values'] = $tpl_weekdays;
       
        $this->ui_subnodes = array(
            'datetype' => array('specific_date_conditional', 'occurence_metric'),
            'specific_date_conditional' => array('date'),
            'occurence_metric' => array('occurence_conditional'),
            'occurence_conditional' => array('occurence_year', 'occurence_month', 'occurence_day',
                   'occurence_weekday', 'occurence_hour', 'occurence_minute', 'occurence_second'
            ),
            /*
            'year' => array(),
            'month' => array(),
            'day' => array(),
            'hour' => array(),
            'minute' => array(),
            'second' => array(),
             */
        );
    }

    /**
     * @return string
     */
    public function datetime_common_ui() {
        $out = $this->ui_tree_output();
        return $out;
    }

    /**
     *
     * @param $varname string   Name of input element from which to start off
     * @param $varvalue string  Value of this input element.
     * @return string
     */
    public function ui_tree_output($varname = '', $varvalue = '') {
        if(!empty($varname) && !empty($varvalue)) {
            $k = $this->_getChildOf($varname, $varvalue);
        } else {
            $k = 'datetype';
        }
        $out = '';
        if(!empty($k)) {
            $out .= $this->_printWidgetHtml($k);
        }

        return $out;
    }
    
    private function _printWidgetHtml($k, $selected = '') {
        $u = &$this->ui[$k];

        $out = '<span id="datetime_condition_'.$k.'_'.$this->n.'">';

        switch($u['input']) {
        case 'select':
            $out .= '<select name="cond['.$this->n.']['.$k.']" id="datetime_input_'.$k.'_'.$this->n.'" ';
            if(!isset($u['terminal'])) {
                $out .= 'onchange="AVELSIEVE.edit.datetimeGetChildren(\''.$k.'\', \''.$this->n.'\'); ';
            }
            $out .= '">';
            $out .= '<option value=""></option>';
            foreach($u['values'] as $key=>$val) {
                $out .= '<option value="'.$key.'">'.$val.'</option>';
            }
            $out .= '</select>';
            break;

        case 'datepicker': 
            $out .= '<input type="text" name="cond['.$this->n.']['.$k.']" id="datetime_input_'.$k.'_'.$this->n.'" value="" />';
            break;

        case 'text': 
            $out .= '<input type="text" name="cond['.$this->n.']['.$k.']" id="datetime_input_'.$k.'_'.$this->n.'" value="" />';
            break;

        default:
            $out .= ' nothing ';
            break;
        }

        $out .= '</span>';
        $out .= '<span id="datetime_condition_after_'.$k.'_'.$this->n.'"></span>';
        return $out;
    }

    function _getChildOf($varname, $varvalue) {
        // if(isset($ui_subnodes[$varname]) && isset($ui[$varname]['children'])) {
        if(isset($this->ui[$varname]['children'])) {
            foreach($this->ui[$varname]['children'] as $child => $widget) {
                if($varvalue == $child) {
                    return $widget;
                }
            }
        }
        return false;
    }
}

