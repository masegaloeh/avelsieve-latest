<?php
/*
 * User-friendly interface to SIEVE server-side mail filtering.
 * Plugin for Squirrelmail 1.4+
 *
 * Licensed under the GNU GPL. For full terms see the file COPYING that came
 * with the Squirrelmail distribution.
 *
 * This file contains functions for the per-message commands that appear while
 * viewing a message.
 *
 * @version $Id: message_commands.inc.php,v 1.5 2004/11/15 16:37:28 avel Exp $
 * @author Alexandros Vellis <avel@users.sourceforge.net>
 * @copyright 2004 The SquirrelMail Project Team, Alexandros Vellis
 * @package plugins
 * @subpackage avelsieve
 */

include_once(SM_PATH . 'functions/identity.php');

/**
 * Display available filtering commands for current message.
 */
function avelsieve_commands_menu_do() {
    global $passed_id, $passed_ent_id, $color, $mailbox,
           $message, $compose_new_win;
    
    $output = array();

    $filtercmds = array(
    	'auto' => array(
			'algorithm' => 'auto',
			'desc' => _("Automatically")
		),
    	'sender' => array(
			'algorithm' => 'address',
			'desc' => _("Sender")
		),
    	'from' => array(
			'algorithm' => 'address',
			'desc' => _("From")
		),
    	'to' => array(
			'algorithm' => 'address',
			'desc' => _("To")
		),
    	'subject' => array(
			'algorithm' => 'header',
			'desc' => _("Subject")
		) 
		/*
    	'priority' => array(
			'algorithm' => 'header',
			'desc' => _("Priority")
		)
		*/
    );
	
    $hdr = &$message->rfc822_header;

	/* Have identities handy to check for our email addresses in automatic
	 * algorithm mode */
	$idents = get_identities();
	$myemails = array();
	foreach($idents as $identity) {
		$myemails[] = strtolower($identity['email_address']);
	}

	foreach($filtercmds as $c => $i) {
    	$url = '../plugins/avelsieve/edit.php?addnew=1&amp;type=2';
		switch($i['algorithm']) {
		case 'address':
			if(isset($hdr->$c) && !empty($hdr->$c)) {
				if(is_array($hdr->$c)) {
					for($j=0; $j<sizeof($hdr->$c); $j++) {
						$url .= '&amp;header['.$j.']='.ucfirst($c);
						$url .= '&amp;matchtype['.$j.']=contains';
						$url .= '&amp;headermatch['.$j.']='.urlencode( $hdr->{$c}[$j]->mailbox.'@'.$hdr->{$c}[$j]->host);
					}
				} else {
					$j=0;
					$url .= '&amp;header['.$j.']='.ucfirst($c);
					$url .= '&amp;matchtype['.$j.']=contains';
					$url .= '&amp;headermatch['.$j.']='.urlencode( $hdr->{$c}->mailbox.'@'.$hdr->{$c}->host);
				}
			} else {
				unset($url);
			}
			break;

		case 'header':
			if(isset($hdr->$c) && !empty($hdr->$c)) {
				$j=0;
				$url .= '&amp;header['.$j.']='.ucfirst($c);
				$url .= '&amp;matchtype['.$j.']=contains';
				$url .= '&amp;headermatch['.$j.']='.rawurlencode(decodeHeader($hdr->$c));
			}
			break;

		case 'auto':
			if(isset($hdr->mlist['id']) && isset($hdr->mlist['id']['href'])) {
				/* List-Id: (href) */
				$url .= '&amp;header[0]=List-Id'.
						'&amp;matchtype[0]=contains'.
						'&amp;headermatch[0]='.rawurlencode( $hdr->mlist['id']['href'] );

			} elseif(isset($hdr->mlist['id']) && isset($hdr->mlist['id']['mailto'])) {
				/* List-Id: (mailto) */
				$url .= '&amp;header[0]=List-Id'.
						'&amp;matchtype[0]=contains'.
						'&amp;headermatch[0]='.rawurlencode( $hdr->mlist['id']['mailto'] );

			} elseif(isset($hdr->sender) && !empty($hdr->sender)) {
				/* Sender: */
				$url .= '&amp;header[0]=Sender'.
						'&amp;matchtype[0]=contains'.
						'&amp;headermatch[0]='.rawurlencode($hdr->sender->mailbox.'@'.$hdr->sender->host);
			
			} elseif(isset($hdr->to) && !empty($hdr->to)) {
				/* To:, not including one of my identities*/
				$j = 0;
				for($k=0; $k<sizeof($hdr->to); $k++) {
					$tempurl = '';
					if(!in_array($hdr->to[$k]->mailbox.'@'.$hdr->to[$k]->host,$myemails)) {
						$tempurl .= '&amp;header['.$j.']=toorcc'.
						'&amp;matchtype['.$j.']=contains'.
						'&amp;headermatch['.$j.']='.rawurlencode($hdr->to[$k]->mailbox.'@'.
						$hdr->to[$k]->host);
						$j++;
					}
				}
				if($j > 0) {
					$url .= $tempurl;
				} else {
					/* The above method failed, continue with one of these: */

					if(isset($hdr->from) && !empty($hdr->from)) {
						/* From: */
						for($k=0; $k<sizeof($hdr->from); $k++) {
							$url .= '&amp;header[0]=From'.
								'&amp;matchtype[0]=contains'.
								'&amp;headermatch[0]='.rawurlencode($hdr->from[$k]->mailbox.'@'.$hdr->from[$k]->host);
						}
					
					} elseif(isset($hdr->subject) && !empty($hdr->subject)) {
						/* Subject */
						$url .= '&amp;header[0]=Subject'.
								'&amp;matchtype[0]=contains'.
								'&amp;headermatch[0]='.rawurlencode($hdr->subject);
					}
				}
			}
			break;
		}
		if(isset($url)) {
			if(!$compose_new_win) {
				/* For non-popup page we need this to come back here. */
				$url .= '&amp;passed_id='.$passed_id.'&amp;mailbox='.urlencode($mailbox).
					(isset($passed_ent_id)?'&amp;passed_ent_id='.$passed_ent_id:'');
			}
			
	   		if ($compose_new_win == '1') {
				$url .= '&amp;popup=1';
			}
    		if ($compose_new_win == '1') {
	       		$output[] = "<a href=\"javascript:void(0)\" onclick=\"comp_in_new('$url')\">".$i['desc'].'</a>';
			} else {
       			$output[] = '<a href="'.$url.'">'.$i['desc'].'</a>';
	   		}
		}
		unset($url);
    }

          
    if (count($output) > 0) {
        echo '<tr>';
        echo html_tag('td', '<b>' . _("Create Filter") . ':&nbsp;&nbsp;</b>',
                      'right', '', 'valign="middle" width="20%"') . "\n";
        echo html_tag('td', '<small>' . implode('&nbsp;|&nbsp;', $output) . '</small>',
                      'left', $color[0], 'valign="middle" width="80%"') . "\n";
        echo '</tr>';
    }

}

?>
