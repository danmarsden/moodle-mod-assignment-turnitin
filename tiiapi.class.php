<?php
 /**
 * API Assignment
 * v 1.0 2007/06/13 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 10:00:00 Northumbria Learning
 * Adapted for Moodle 1.8
 */
require_once("tiilib.php");

class tii_api {
    var $aid, $encrypt, $md5, $src, $diagnostic;
    var $fcmd, $fid;
    var $uid, $uem, $ufn, $uln, $username, $utp;
    var $assign, $assignid;
    var $cid, $ctl;
    var $sso, $oid, $newMoodle, $version;
    var $format, $grade, $preventlate, $assignmenttype;
    var $groupmode, $visible, $course, $coursemodule, $section, $module, $modulename, $instance, $maxbytes;
    var $mytimeavailable, $mytimedue;
    var $action;
    
    var $_vals;        // Value Array
    var $_xml_parser;  // Value Array
    var $_index;       // Index Array

    function tii_api($cid, $fcmd, $fid) {
    	global $CFG, $USER;
        $this->uid = $USER->id;
        $this->username = $USER->username;
	    $this->uem = $USER->email;
    	$this->ufn = $USER->firstname;
    	$this->uln = $USER->lastname;
    	
    	$this->cid = $cid;

    	$this->fcmd = $fcmd;
    	$this->fid = $fid;
    	
    	$this->src = TII_SRC;
    	$this->version = TII_VERSION;
    	$this->newMoodle = 1;

        if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_COURSE, $cid), $USER->id)) {
    	    $this->utp = 2;
    	} else {
    	    $this->utp = 1;
    	}
    	
    	if ($this->fid == TII_FID_CREATEASSIGNMENT) {
    	    if ($this->fcmd == TII_FCMD_DELETEASSIGNMENT) {
    	        $this->action = "ASSIGNMENT_DELETE";
    	    } elseif ($this->fcmd == TII_FCMD_EDITASSIGNMENT) {
    	        $this->action = "ASSIGNMENT_MODIFY";
    	    } else {
    	        $this->action = "ASSIGNMENT_CREATE";
    	    }
	    } 
	    
	    $this->sso = $CFG->wwwroot."/mod/assignment/type/turnitin/edit_create_success.php?id=";
    	
    }
    
    function set_turnitin_params() {
        global $CFG;
    	$url =  "gmtime=".tii_api::get_gmtime();
      	$url .= "&fid=".$this->fid;
    	$url .= "&fcmd=".$this->fcmd;
    	$url .= "&encrypt=".TII_ENCRYPT;
        $url .= "&src=".$this->src;
    	$url .= "&md5=".$this->get_md5string();
    	$url .= "&aid=".TII_AID;
    	$url .= "&diagnostic=".TII_DIAGNOSTIC;
        $url .= "&wsurl=".urlencode(TII_WEBSERVICEURL);
    	$url .= "&version=".TII_VERSION;
    	$url .= "&newMoodle=".$this->newMoodle;

    	$url .= "&uid=".urlencode($this->uid);
    	$url .= "&uem=".urlencode($this->uem);
    	$url .= "&ufn=".urlencode($this->ufn);
    	$url .= "&uln=".urlencode($this->uln);
    	$url .= "&username=".urlencode($this->username);
    	$url .= "&utp=".$this->utp;

    	$url .= "&cid=".urlencode($this->cid);
    	$url .= "&ctl=".urlencode($this->ctl);

    	$url .= "&assign=".urlencode(stripslashes($this->assign));
    	$url .= "&assignid=".urlencode($this->assignid);

    	$url .= "&oid=".urlencode($this->oid);
    	$url .= "&theSsoUrl=".urlencode($this->sso);

    	$url .= "&mdl_username=".urlencode($this->username);
    	$url .= "&mdl_assignid=".urlencode($this->assignid);
    	$url .= "&mdl_ctl=".urlencode(stripslashes($this->ctl));
    	$url .= "&mdl_title=".urlencode(stripslashes($this->assign));
        $url .= "&mdl_uid=".urlencode($this->uid);
    	$url .= "&mdl_cid=".urlencode($this->cid);
    	$url .= "&mdl_format=".urlencode($this->format);
    	$url .= "&mdl_grade=".urlencode($this->grade);
        $url .= "&mdl_timeavailable=".$this->mytimeavailable;
        $url .= "&mdl_timedue=".$this->mytimedue;
    	$url .= "&mdl_preventlate=".$this->preventlate;
    	$url .= "&mdl_assignmenttype=".$this->assignmenttype;
    	$url .= "&mdl_groupmode=".$this->groupmode;
    	$url .= "&mdl_visible=".$this->visible;
    	$url .= "&mdl_course=".$this->course;
    	$url .= "&mdl_coursemodule=".$this->coursemodule;
    	$url .= "&mdl_section=".$this->section;
    	$url .= "&mdl_module=".$this->module;
    	$url .= "&mdl_modulename=".$this->modulename;
    	$url .= "&mdl_instance=".$this->instance;
    	$url .= "&mdl_maxbytes=".$this->maxbytes;
    	$url .= "&mdl_ufn=".urlencode($this->ufn);
        $url .= "&mdl_uln=".urlencode($this->uln);
    	$url .= "&mdl_uem=".urlencode($this->uem);
    	$url .= "&action=".urlencode($this->action);


    	if (defined('TII_LOGGING')) {
            if (TII_LOGGING == true) {
                require_once($CFG->dirroot."/mod/assignment/type/turnitin/logger.class.php");
                $logger = new Logger;
                $logger->log("TIIAPI:".$url);
            }
    	}
    	return $url;
    }
    function get_url() {
        global $CFG;
        $url = $this->set_turnitin_params();
	    $url = TII_BASEURL."?".$url;
	    return $url;
    }


    function setup_parameters($assignment, $course, $instance) {
        $this->ctl = $course->fullname;
        $this->course = $assignment->course;
        $this->section = $instance->section;
        
        $this->assign = $assignment->name;
        $this->assignid = $assignment->id;
        $this->description = $assignment->intro;
        $this->format = $assignment->introformat;
        $this->assignmenttype = $assignment->assignmenttype;
        $this->grade = $assignment->grade;
         
        $this->mytimeavailable = $assignment->timeavailable;
        $this->mytimedue = $assignment->timedue;
        $this->preventlate = $assignment->preventlate;
        
        $this->version = TII_VERSION;
        $this->newMoodle = 1;
        $this->sessionkey = sesskey();

    	$this->groupmode = $instance->groupmode;
    	$this->visible = $instance->visible;
    	$this->coursemodule = $instance->coursemodule;

    	$this->module = $instance->module;
    	$this->modulename = $instance->modulename;
    	$this->instance = $instance->instance;
    	$this->maxbytes = $assignment->maxbytes;
    }
    
    function send_post_request() {
        global $CFG;
        
        $ch = curl_init(TII_BASEURL);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
        $params = $this->set_turnitin_params();
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $xml_return = curl_exec($ch);

        curl_close($ch);

        $this->_xml_parser = xml_parser_create();
        xml_parse_into_struct($this->_xml_parser, $xml_return, $this->_vals, $this->_index);
        xml_parser_free($this->_xml_parser);
    }
    
    function debug() {
        return print_r($this->_vals);
    }

    function getRmessage() {
        $pos = $this->_index['RMESSAGE'][0];
        return $this->_vals[$pos]['value'];
    }

    function getRcode() {
        $pos = $this->_index['RCODE'][0];
        return $this->_vals[$pos]['value'];
    }
    
    
    function set_objectID($oid) {
        $this->oid = $oid;
    }

    function get_gmtime() {
        return substr(gmdate('YmdHi'), 0, -1);
    }

    function get_md5string(){
        global $CFG;
    	$md5string = TII_AID.
	    $this->assign.
    	$this->assignid.
    	$this->cid.
    	$this->ctl.
    	TII_DIAGNOSTIC.
    	TII_ENCRYPT.
    	$this->fcmd.
    	$this->fid.
    	tii_api::get_gmtime().
    	$this->oid.
    	$this->uem.
    	$this->ufn.
    	$this->uid.
    	$this->uln.
    	$this->username.
    	$this->utp.
    	TII_SHAREDKEY;

    	if (defined('TII_LOGGING')) {
            if (TII_LOGGING == true) {
                require_once($CFG->dirroot."/mod/assignment/type/turnitin/logger.class.php");
                $logger = new Logger;
                $logger->log("TIIAPI: MD5".$md5string.":".md5($md5string));
            }
    	}
    	return md5($md5string);
    }
}

?>
