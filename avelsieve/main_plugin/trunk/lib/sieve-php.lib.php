<?php

/*
 * $Id: sieve-php.lib.php,v 1.3 2003/10/27 11:37:42 avel Exp $ 
 *
 * Copyright 2001 Dan Ellis <danellis@rushmore.com>
 *
 * See the enclosed file COPYING for license information (GPL).  If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

/*

SIEVE-PHP.LIB VERSION 0.1.0cvs

(C) 2001 Dan Ellis.

PLEASE READ THE README FILE FOR MORE INFORMATION.

Basically, this is the first re-release.  Things are much better than before.

Notes:
This program/libary has bugs.
	.	This was quickly hacked out, so please let me know what is wrong and if you feel ambitious submit
		a patch :).

Todo:
	.	remove ::status() and dependencies
	.	Provide better error diagnostics.  			(mostly done with ver 0.0.5)
	.	Allow other auth mechanisms besides plain		(in progress)
	.	Have timing mechanism when port problems arise.		(not done yet)
	.	Maybe add the NOOP function.				(not done yet)
	.	Other top secret stuff....				(some done, believe me?)

Dan Ellis (danellis@rushmore.com)

This program is released under the GNU Public License.

You should have received a copy of the GNU Public
 License along with this package; if not, write to the
 Free Software Foundation, Inc., 59 Temple Place - Suite 330,
 Boston, MA 02111-1307, USA.        

See CHANGES for updates since last release

Contributers of patches:
	Atif Ghaffar
	Andrew Sterling Hanenkamp <sterling@hanenkamp.com>
	"Ilya Pizik" <polzun@scar.jinr.ru> (AUTH LOGIN)
	Scott Russell <lnxgeek@us.ibm.com> (DIGEST-MD5 & CRAM-MD5)
	Alexandros Vellis <avel@noc.uoa.gr>

*/

define ("F_NO", 0);		
define ("F_OK", 1);
define ("F_DATA", 2);
define ("F_HEAD", 3);

define ("EC_NOT_LOGGED_IN", 0);
define ("EC_QUOTA", 10);
define ("EC_NOSCRIPTS", 20);
define ("EC_UNKNOWN", 255);

class sieve
{
  var $host;
  var $port;
  var $user;
  var $pass;
  var $auth_types;		/* a comma seperated list of allowed auth types, in order of preference */
  var $auth_in_use;		/* type of authentication attempted */
  
  var $line;
  var $fp;
  var $retval;
  var $tmpfile;
  var $fh;
  var $len;
  var $script;

  var $loggedin;
  var $capabilities;
  var $error;
  var $error_raw;
  var $responses;

  //maybe we should add an errorlvl that the user will pass to new sieve = sieve(,,,,E_WARN)
  //so we can decide how to handle certain errors?!?


  function get_response()
  {
    if($this->loggedin == false or feof($this->fp)){
        $this->error = EC_NOT_LOGGED_IN;
        $this->error_raw = "You are not logged in.";
        return false;
    }

    unset($this->response);
    unset($this->error);
    unset($this->error_raw);

    $this->line=fgets($this->fp,1024);
    $this->token = split(" ", $this->line, 2);

    if($this->token[0] == "NO"){
        /* we need to try and extract the error code from here.  There are two possibilites: one, that it will take the form of:
           NO ("yyyyy") "zzzzzzz" or, two, NO {yyyyy} "zzzzzzzzzzz" */
        $this->x = 0;
        list($this->ltoken, $this->mtoken, $this->rtoken) = split(" ", $this->line." ", 3);
        if($this->mtoken[0] == "{"){
            while($this->mtoken[$this->x] != "}" or $this->err_len < 1){
                $this->err_len = substr($this->mtoken, 1, $this->x);
                $this->x++;    
            }
            //print "<br>Trying to receive $this->err_len bytes for result<br>";
            $this->line = fgets($this->fp,$this->err_len);
            $this->error_raw[]=substr($this->line, 0, strlen($this->line) -2);    //we want to be nice and strip crlf's
            $this->err_recv = strlen($this->line);

            while($this->err_recv < $this->err_len){
                //print "<br>Trying to receive ".($this->err_len-$this->err_recv)." bytes for result<br>";
                $this->line = fgets($this->fp, ($this->err_len-$this->err_recv));
                $this->error_raw[]=substr($this->line, 0, strlen($this->line) -2);    //we want to be nice and strip crlf's
                $this->err_recv += strlen($this->line);
            } /* end while */
            $this->line = fgets($this->fp, 1024);	//we need to grab the last crlf, i think.  this may be a bug...
            $this->error=EC_UNKNOWN;
      
        } /* end if */
        elseif($this->mtoken[0] == "("){
            switch($this->mtoken){
                case "(\"QUOTA\")":
                    $this->error = EC_QUOTA;
                    $this->error_raw=$this->rtoken;
                    break;
                default:
                    $this->error = EC_UNKNOWN;
                    $this->error_raw=$this->rtoken;
                    break;
            } /* end switch */
        } /* end elseif */
        else{
            $this->error = EC_UNKNOWN;
            $this->error_raw = $this->line;
        }     
        return false;

    } /* end if */
    elseif(substr($this->token[0],0,2) == "OK"){
         return true;
    } /* end elseif */
    elseif($this->token[0][0] == "{"){
        
        /* Unable wild assumption:  that the only function that gets here is the get_script(), doesn't really matter though */       

        /* the first line is the len field {xx}, which we don't care about at this point */
        $this->line = fgets($this->fp,1024);
        while(substr($this->line,0,2) != "OK" and substr($this->line,0,2) != "NO"){
            $this->response[]=$this->line;
            $this->line = fgets($this->fp, 1024);
        }
        if(substr($this->line,0,2) == "OK")
            return true;
        else
            return false;
    } /* end elseif */
    elseif($this->token[0][0] == "\""){

        /* I'm going under the _assumption_ that the only function that will get here is the listscripts().
           I could very well be mistaken here, if I am, this part needs some rework */

        $this->found_script=false;        

        while(substr($this->line,0,2) != "OK" and substr($this->line,0,2) != "NO"){
            $this->found_script=true;
            list($this->ltoken, $this->rtoken) = explode(" ", $this->line." ",2);
		//hmmm, a bug in php, if there is no space on explode line, a warning is generated...
           
            if(strcmp(rtrim($this->rtoken), "ACTIVE")==0){
                $this->response["ACTIVE"] = substr(rtrim($this->ltoken),1,-1);  
            }
            else
                $this->response[] = substr(rtrim($this->ltoken),1,-1);
            $this->line = fgets($this->fp, 1024);
        } /* end while */
        
        return true;
        
    } /* end elseif */
    else{
            $this->error = EC_UNKNOWN;
            $this->error_raw = $this->line;
	    print '<b><i>UNKNOWN ERROR (Please report this line to <a
	    href="mailto:sieve-php-devel@lists.sourceforge.net">sieve-php-devel
	    Mailing List</a> to include in future releases):
	    '.$this->line.'</i></b><br>';

            return false;
    } /* end else */   
  } /* end get_response() */

  function sieve($host, $port, $user, $pass, $auth="", $auth_types="PLAIN")
  {
    $this->host=$host;
    $this->port=$port;
    $this->user=$user;
    $this->pass=$pass;
    if(!strcmp($auth, ""))		/* If there is no auth user, we deem the user itself to be the auth'd user */
        $this->auth = $this->user;
    else
        $this->auth = $auth;
    $this->auth_types=$auth_types;	/* Allowed authentication types */
    $this->fp=0;
    $this->line="";
    $this->retval="";
    $this->tmpfile="";
    $this->fh=0;
    $this->len=0;
    $this->capabilities="";
    $this->loggedin=false;
    $this->error= "";
    $this->error_raw="";
  }

  function parse_for_quotes($string)
  {
      /* This function tokenizes a line of input by quote marks and returns them as an array */

      $start = -1;
      $index = 0;

      for($ptr = 0; $ptr < strlen($string); $ptr++){
          if($string[$ptr] == '"' and $string[$ptr] != '\\'){
              if($start == -1){
                  $start = $ptr;
              } /* end if */
              else{
                  $token[$index++] = substr($string, $start + 1, $ptr - $start - 1);
                  $found = true;
                  $start = -1;
              } /* end else */

          } /* end if */  

      } /* end for */

      if(isset($token))
          return $token;
      else
          return false;
  } /* end function */            

  function status($string)
  {
      //this should probably be replaced by a smarter parser.

      /*  Need to remove this and all dependencies from the class */

      switch (substr($string, 0,2)){
          case "NO":
              return F_NO;		//there should be some function to extract the error code from this line
					//NO ("quota") "You are oly allowed x number of scripts"
              break;
          case "OK":
              return F_OK;
              break;
          default:
              switch ($string[0]){
                  case "{":
                      //do parse here for {}'s  maybe modify parse_for_quotes to handle any parse delimiter?
                      return F_HEAD;
                      break;
                  default:
                      return F_DATA;
                      break;
              } /* end switch */
        } /* end switch */
  } /* end status() */

  function sieve_login()
  {

    $this->fp=fsockopen($this->host,$this->port);
    if($this->fp == false)
        return false;
 
    $this->line=fgets($this->fp,1024);

    //Hack for older versions of Sieve Server.  They do not respond with the Cyrus v2. standard
    //response.  They repsond as follows: "Cyrus timsieved v1.0.0" "SASL={PLAIN,........}"
    //So, if we see IMLEMENTATION in the first line, then we are done.

    if(ereg("IMPLEMENTATION",$this->line))
    {
      //we're on the Cyrus V2 sieve server
      while(sieve::status($this->line) == F_DATA){

          $this->item = sieve::parse_for_quotes($this->line);

          if(strcmp($this->item[0], "IMPLEMENTATION") == 0)
              $this->capabilities["implementation"] = $this->item[1];
        
          elseif(strcmp($this->item[0], "SIEVE") == 0 or strcmp($this->item[0], "SASL") == 0){

              if(strcmp($this->item[0], "SIEVE") == 0)
                  $this->cap_type="modules";
              else
                  $this->cap_type="auth";            

              $this->modules = split(" ", $this->item[1]);
              if(is_array($this->modules)){
                  foreach($this->modules as $this->module)
                      $this->capabilities[$this->cap_type][$this->module]=true;
              } /* end if */
              elseif(is_string($this->modules))
                  $this->capabilites[$this->cap_type][$this->modules]=true;
          }    
          elseif(strcmp($this->item[0], "STARTTLS") == 0) {
	          $this->capabilities['starttls'] = true;
	  
          }
	  else{ 
              $this->capabilities["unknown"][]=$this->line;
          }    
      $this->line=fgets($this->fp,1024);

       }// end while
    }
    else
    {
        //we're on the older Cyrus V1. server  
        //this version does not support module reporting.  We only have auth types.
        $this->cap_type="auth";
       
        //break apart at the "Cyrus timsieve...." "SASL={......}"
        $this->item = sieve::parse_for_quotes($this->line);

        $this->capabilities["implementation"] = $this->item[0];

        //we should have "SASL={..........}" now.  Break out the {xx,yyy,zzzz}
        $this->modules = substr($this->item[1], strpos($this->item[1], "{"),strlen($this->item[1])-1);

        //then split again at the ", " stuff.
        $this->modules = split($this->modules, ", ");
 
        //fill up our $this->modules property
        if(is_array($this->modules)){
            foreach($this->modules as $this->module)
                $this->capabilities[$this->cap_type][$this->module]=true;
        } /* end if */
        elseif(is_string($this->modules))
            $this->capabilites[$this->cap_type][$this->module]=true;
    }




    if(sieve::status($this->line) == F_NO){		//here we should do some returning of error codes?
        $this->error=EC_UNKNOWN;
        $this->error_raw = "Server not allowing connections.";
        return false;
    }

    /* decision login to decide what type of authentication to use... */


     /* Loop through each allowed authentication type and see if the server allows the type */
     foreach(explode(" ", $this->auth_types) as $auth_type)
     {
        if ($this->capabilities["auth"][$auth_type])
        {
            /* We found an auth type that is allowed. */
            $this->auth_in_use = $auth_type;
        }
     }
    
     /* call our authentication program */
   
    return sieve::authenticate();

  }

  function sieve_logout()
  {
    if($this->loggedin==false)
        return false;

    fputs($this->fp,"LOGOUT\r\n");
    fclose($this->fp);
    $this->loggedin=false;
    return true;
  }

  function sieve_sendscript($scriptname, $script)
  {
    if($this->loggedin==false)
        return false;
    $this->script=stripslashes($script);
    $len=strlen($this->script);
    fputs($this->fp, "PUTSCRIPT \"$scriptname\" \{$len+}\r\n");
    fputs($this->fp, "$this->script\r\n");
  
    return sieve::get_response();

  }  
  
  //it appears the timsieved does not honor the NUMBER type.  see lex.c in timsieved src.
  //don't expect this function to work yet.  I might have messed something up here, too.
  function sieve_havespace($scriptname, $scriptsize)
  {
    if($this->loggedin==false)
        return false;
    fputs($this->fp, "HAVESPACE \"$scriptname\" $scriptsize\r\n");
    return sieve::get_response();

  }  

  function sieve_setactivescript($scriptname)
  {
    if($this->loggedin==false)
        return false;

    fputs($this->fp, "SETACTIVE \"$scriptname\"\r\n");   
    return sieve::get_response();

  }
  
  function sieve_getscript($scriptname)
  {
    unset($this->script);
    if($this->loggedin==false)
        return false;

    fputs($this->fp, "GETSCRIPT \"$scriptname\"\r\n");
    return sieve::get_response();
   
  }


  function sieve_deletescript($scriptname)
  {
    if($this->loggedin==false)
        return false;

    fputs($this->fp, "DELETESCRIPT \"$scriptname\"\r\n");    

    return sieve::get_response();
  }

  
  function sieve_listscripts() 
   { 
     fputs($this->fp, "LISTSCRIPTS\r\n"); 
     sieve::get_response();		//should always return true, even if there are no scripts...
     if(isset($this->found_script) and $this->found_script)
         return true;
     else{
         $this->error=EC_NOSCRIPTS;	//sieve::getresponse has no way of telling wether a script was found...
         $this->error_raw="No scripts found for this account.";
         return false;
     }
   }

  function sieve_alive()
  {
      if(!isset($this->fp) or $this->fp==0){
          $this->error = EC_NOT_LOGGED_IN;
          return false;
      }
      elseif(feof($this->fp)){			
          $this->error = EC_NOT_LOGGED_IN;
          return false;
      }
      else
          return true;
  }

  function authenticate()
  {

    switch ($this->auth_in_use) {

        case "PLAIN":
            $auth=base64_encode("$this->auth\0$this->user\0$this->pass");
   
            $this->len=strlen($auth);			
            fputs($this->fp, "AUTHENTICATE \"PLAIN\" \{$this->len+}\r\n");
            fputs($this->fp, "$auth\r\n");

            $this->line=fgets($this->fp,1024);		
            while(sieve::status($this->line) == F_DATA)
               $this->line=fgets($this->fp,1024);

             if(sieve::status($this->line) == F_NO)
               return false;
             $this->loggedin=true;
               return true;    
	    break;
	
        case "DIGEST-MD5":
	     // SASL DIGEST-MD5 support works with timsieved 1.1.0
	     // follows rfc2831 for generating the $response to $challenge
	     fputs($this->fp, "AUTHENTICATE \"DIGEST-MD5\"\r\n");
	     // $clen is length of server challenge, we ignore it. 
	     $clen = fgets($this->fp, 1024);
	     // read for 2048, rfc2831 max length allowed
	     $challenge = fgets($this->fp, 2048);
	     // vars used when building $response_value and $response
	     $cnonce = base64_encode(bin2hex(hmac_md5(microtime())));
	     $ncount = "00000001";
	     $qop_value = "auth"; 
	     $digest_uri_value = "sieve/$this->host";
	     // decode the challenge string
	     $result = decode_challenge($challenge);
	     // verify server supports qop=auth 
	     $qop = explode(",",$result['qop']);
	     if (!in_array($qop_value, $qop)) {
	        // rfc2831: client MUST fail if no qop methods supported
	        return false;
	     }
	     // build the $response_value
	     $string_a1 = utf8_encode($this->user).":";
	     $string_a1 .= utf8_encode($result['realm']).":";
	     $string_a1 .= utf8_encode($this->pass);
	     $string_a1 = hmac_md5($string_a1);
	     $A1 = $string_a1.":".$result['nonce'].":".$cnonce.":".utf8_encode($this->auth);
	     $A1 = bin2hex(hmac_md5($A1));
	     $A2 = bin2hex(hmac_md5("AUTHENTICATE:$digest_uri_value"));
	     $string_response = $result['nonce'].":".$ncount.":".$cnonce.":".$qop_value;
	     $response_value = bin2hex(hmac_md5($A1.":".$string_response.":".$A2));
	     // build the challenge $response
	     $reply = "charset=utf-8,username=\"".$this->user."\",realm=\"".$result['realm']."\",";
	     $reply .= "nonce=\"".$result['nonce']."\",nc=$ncount,cnonce=\"$cnonce\",";
	     $reply .= "digest-uri=\"$digest_uri_value\",response=$response_value,";
	     $reply .= "qop=$qop_value,authzid=\"".utf8_encode($this->auth)."\"";
	     $response = base64_encode($reply);
	     fputs($this->fp, "\"$response\"\r\n");
 	
             $this->line = fgets($this->fp, 1024);
             while(sieve::status($this->line) == F_DATA)
                $this->line = fgets($this->fp,1024);

             if(sieve::status($this->line) == F_NO)
               return false;
             $this->loggedin = TRUE;
               return TRUE;    
             break;
	
        case "CRAM-MD5":
  	     // SASL CRAM-MD5 support works with timsieved 1.1.0
	     // follows rfc2195 for generating the $response to $challenge
	     // CRAM-MD5 does not support proxy of $auth by $user
	     // requires php mhash extension
	     fputs($this->fp, "AUTHENTICATE \"CRAM-MD5\"\r\n");
	     // $clen is the length of the challenge line the server gives us
	     $clen = fgets($this->fp, 1024);
	     // read for 1024, should be long enough?
	     $challenge = fgets($this->fp, 1024);
	     // build a response to the challenge
	     $hash = bin2hex(hmac_md5(base64_decode($challenge), $this->pass));
	     $response = base64_encode($this->user." ".$hash);
	     // respond to the challenge string
	     fputs($this->fp, "\"$response\"\r\n");
	     
             $this->line = fgets($this->fp, 1024);		
             while(sieve::status($this->line) == F_DATA)
                $this->line = fgets($this->fp,1024);

             if(sieve::status($this->line) == F_NO)
               return false;
             $this->loggedin = TRUE;
               return TRUE;    
             break;

	case "LOGIN":
	    /*
	    // Untested code!

	    $login=base64_encode($this->user);
	    $pass=base64_encode($this->pass);

	    fputs($this->fp, "AUTHENTICATE \"LOGIN\" {0+}\r\n\r\n");
	    fputs($this->fp, "{".strlen($login)."+}\r\n");
	    fputs($this->fp, "$login\r\n");
	    fputs($this->fp, "{".strlen($pass)."+}\r\n");
	    fputs($this->fp, "$pass\r\n");

	    $this->line=fgets($this->fp,1024);
	    while(sieve::status($this->line) == F_DATA)
		$this->line=fgets($this->fp,1024);

	    if(sieve::status($this->line) == F_NO)
		return false;
	    $this->loggedin=true;
		return true;
	break;
	*/

        default:
            return false;
            break;

    }//end switch


  }//end authenticate()
  
  /* This function returns an array of available capabilities */
  function sieve_get_capability()
  {
    if($this->loggedin==false)
        return false;
    fputs($this->fp, "CAPABILITY\r\n"); 
    $this->line=fgets($this->fp,1024);

    //Hack for older versions of Sieve Server.  They do not respond with the Cyrus v2. standard
    //response.  They repsond as follows: "Cyrus timsieved v1.0.0" "SASL={PLAIN,........}"
    //So, if we see IMLEMENTATION in the first line, then we are done.

    if(ereg("IMPLEMENTATION",$this->line))
    {
      //we're on the Cyrus V2 sieve server
      while(sieve::status($this->line) == F_DATA){

          $this->item = sieve::parse_for_quotes($this->line);

          if(strcmp($this->item[0], "IMPLEMENTATION") == 0)
              $this->capabilities["implementation"] = $this->item[1];
        
          elseif(strcmp($this->item[0], "SIEVE") == 0 or strcmp($this->item[0], "SASL") == 0){

              if(strcmp($this->item[0], "SIEVE") == 0)
                  $this->cap_type="modules";
              else
                  $this->cap_type="auth";            

              $this->modules = split(" ", $this->item[1]);
              if(is_array($this->modules)){
                  foreach($this->modules as $this->module)
                      $this->capabilities[$this->cap_type][$this->module]=true;
              } /* end if */
              elseif(is_string($this->modules))
                  $this->capabilites[$this->cap_type][$this->modules]=true;
          }    
          else{ 
              $this->capabilities["unknown"][]=$this->line;
          }    
      $this->line=fgets($this->fp,1024);

       }// end while
    }
    else
    {
        //we're on the older Cyrus V1. server  
        //this version does not support module reporting.  We only have auth types.
        $this->cap_type="auth";
       
        //break apart at the "Cyrus timsieve...." "SASL={......}"
        $this->item = sieve::parse_for_quotes($this->line);

        $this->capabilities["implementation"] = $this->item[0];

        //we should have "SASL={..........}" now.  Break out the {xx,yyy,zzzz}
        $this->modules = substr($this->item[1], strpos($this->item[1], "{"),strlen($this->item[1])-1);

        //then split again at the ", " stuff.
        $this->modules = split($this->modules, ", ");
 
        //fill up our $this->modules property
        if(is_array($this->modules)){
            foreach($this->modules as $this->module)
                $this->capabilities[$this->cap_type][$this->module]=true;
        } /* end if */
        elseif(is_string($this->modules))
            $this->capabilites[$this->cap_type][$this->module]=true;
    }

    return $this->modules;
  }


}


/* Support functions follow. */

if(!function_exists('hmac_md5')) {

/** Creates a HMAC digest that can be used for auth purposes.
 *
 * Squirrelmail has this function in functions/auth.php, and it might have been
 * included already. However, it helps remove the dependancy on mhash.so PHP
 * extension, for some sites. If mhash.so _is_ available, it is used for its
 * speed.
 *
 * This function is Copyright (c) 1999-2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 */
function hmac_md5($data, $key='') {
    // See RFCs 2104, 2617, 2831
    // Uses mhash() extension if available
    if (extension_loaded('mhash')) {
      if ($key== '') {
        $mhash=mhash(MHASH_MD5,$data);
      } else {
        $mhash=mhash(MHASH_MD5,$data,$key);
      }
      return $mhash;
    }
    if (!$key) {
         return pack('H*',md5($data));
    }
    $key = str_pad($key,64,chr(0x00));
    if (strlen($key) > 64) {
        $key = pack("H*",md5($key));
    }
    $k_ipad =  $key ^ str_repeat(chr(0x36), 64) ;
    $k_opad =  $key ^ str_repeat(chr(0x5c), 64) ;
    /* Heh, let's get recursive. */
    $hmac=hmac_md5($k_opad . pack("H*",md5($k_ipad . $data)) );
    return $hmac;
}
}

/** FIXME: this function is a hack to decode the challenge from timsieved
 * 1.1.0. It may not work with other versions and most certainly won't work
 * with other DIGEST-MD5 implentations
 */
function decode_challenge ($input) {
    $input = base64_decode($input);
    preg_match("/nonce=\"(.*)\"/U",$input, $matches);
    $resp['nonce'] = $matches[1];
    preg_match("/realm=\"(.*)\"/U",$input, $matches);
    $resp['realm'] = $matches[1];
    preg_match("/qop=\"(.*)\"/U",$input, $matches);
    $resp['qop'] = $matches[1];
    return $resp;
}

// vim:ts=4:et:ft=php

?>