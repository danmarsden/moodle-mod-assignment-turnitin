<?php
/*
 * web service
 * v 1.0 2006/06/13 10:00:00 Northumbria Learning
 *
 * v 1.1 2006/09/28 14:21:10 Northumbria Learning
 * Add urldecode to description
 *
 * v 2.0 2007/06/27 8:50:14 Northumbria Learning
 * Adapted for Moodle 1.8
 *
 * v 2.0.3 08/07/2008 iParadigms - David Wu
 * Adapted for Moodle 1.9.2 - Integrated Gradebook changes and adapted web service calls
 *
 */
require_once($CFG->dirroot.'/course/lib.php');

class tii_ws {
    var $config;
    var $assign;
    var $submission;
    var $msg;
    var $code;
    var $cid;
    var $user;
    var $returnid;
    var $paper_id;
    var $paper_title;


    function tii_ws() {
	    $this->code = 0;
    }

    function generate_message() {
    	$message = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<moodleApiResult>'."\n";
        if ($this->code >= 0) {
	        $message .= "<rcode>$this->code</rcode>\n";
	        $message .= '<rmessage>'.$this->msg.'</rmessage>'."\n";
	        $message .= '<assignmentId>'.$this->code.'</assignmentId>'."\n";
        } else {
	        $message .= '<rcode>'.$this->code.'</rcode>'."\n";
	        $message .= '<rmessage>'.$this->msg.'</rmessage>'."\n";
        }
    	$message .= '</moodleApiResult>'."\n";

        return $message;
    }

    function create_assignment() {
        global $DB;
        if ($this->check_md5()) {
            $this->assign = new stdClass();
            $this->assign->course = urldecode($_REQUEST['cid']);
            $this->assign->name = urldecode($_REQUEST['title']);
            $this->assign->intro = urldecode($_REQUEST['description']);
            $this->assign->description = urldecode($_REQUEST['description']);
    	    $this->assign->assignmenttype = urldecode($_REQUEST['assignmenttype']);
    	    $this->assign->type = urldecode($_REQUEST['assignmenttype']);
    	    $this->assign->preventlate = urldecode($_REQUEST['preventlate']);
    	
    	    $this->assign->var1 = $_REQUEST['tii_assign_id'];
    	    
    	    $this->assign->maxbytes = urldecode($_REQUEST['maxbytes']);
    	    $this->assign->timedue = urldecode($_REQUEST['timedue']);
    	    $this->assign->timeavailable = urldecode($_REQUEST['timeavailable']);
    	    $this->assign->grade = urldecode($_REQUEST['grade']);
    	    
    	    $this->assign->introformat = urldecode($_REQUEST['format']);
    	    $this->assign->coursemodule = urldecode($_REQUEST['coursemodule']);
    	    $this->assign->cmidnumber = urldecode($_REQUEST['coursemodule']);
    	    $this->assign->section = urldecode($_REQUEST['section']);
    	    $this->assign->module = urldecode($_REQUEST['module']);
    	    $this->assign->modulename = urldecode($_REQUEST['modulename']);
    	    $this->assign->instance = urldecode($_REQUEST['instance']);
    	    
    	    $this->user = $DB->get_record("user", array("id" => $_REQUEST['uid']));
            
    	    if (trim($this->assign->name) == '') {
    	        $this->assign->name = get_string("modulename", $this->assign->modulename);
    	    }

    	    $assignment = new assignment_turnitin();
    	    $this->assign->instance = $assignment->intercept_add_instance($this->assign);

    	    if ($this->assign->instance < 0) {
    	        $this->code = -100;
    	        $this->msg = "Assignment Instance Creation Error";
    	    }
    	    if ($this->code >= 0) {
    	        if (!isset($this->assign->groupmode)) {
    	            $this->assign->groupmode = 0;
    	        }
    	    }
    	    if ($this->code >= 0) {
    	        if (! $this->assign->coursemodule = add_course_module($this->assign) ) {
    	            $this->code = -150;
    	           	$this->msg = "Could not add a new course module";
    	        }
    	    }
    	    if ($this->code >= 0) {
    	        if (! $this->assign->sectionid = add_mod_to_section($this->assign) ) {
    	            $this->code = -200;
    	            $this->msg = "Could not add the new course module to that section";
    	        }
    	    }
    	    if ($this->code >= 0) {
    	        if (! $DB->set_field("course_modules", "section", $this->assign->sectionid, array('id' => $this->assign->coursemodule)) ) {
    	            $this->code = -250;
    	            $this->msg = "Could not update the course module with the correct section";
    	        }
    	    }
    	    if ($this->code >= 0) {
    	        $this->code = $this->assign->instance;
    	        if (!isset($this->assign->visible)) {   // We get the section's visible field status
    	            $this->assign->visible = $DB->get_field("course_sections","visible", array('id' => $this->assign->sectionid));
    	        }
    		    // make sure visibility is set correctly (in particular in calendar)
    		    set_coursemodule_visible($this->assign->coursemodule, $this->assign->visible);
    		    rebuild_course_cache($this->assign->course);
    		    $this->msg = "Assignment Created Successfully!";
    	    }
        }
    }
    
    function modify_assignment() {
        global $DB;
   	    if ($this->check_md5()) {
   	        $this->assign = new stdClass();
       	    $this->assign->id = urldecode($_REQUEST['assignid']);
       	    $this->assign->name = urldecode($_REQUEST['title']);
       	    $this->assign->intro = urldecode($_REQUEST['description']);
       	    $this->assign->description = urldecode($_REQUEST['description']);
       	    $this->assign->introformat = urldecode($_REQUEST['format']);
       	    $this->assign->grade = urldecode($_REQUEST['grade']);
       	    $this->assign->preventlate = urldecode($_REQUEST['preventlate']);
       	    $this->assign->resubmit = 0;
       	    $this->assign->assignmenttype = urldecode($_REQUEST['assignmenttype']);
       	    $this->assign->type = urldecode($_REQUEST['assignmenttype']);;
       	    $this->assign->course = urldecode($_REQUEST['cid']);
       	    $this->assign->coursemodule = urldecode($_REQUEST['coursemodule']);
       	    $this->assign->section = urldecode($_REQUEST['section']);
       	    $this->assign->module = urldecode($_REQUEST['module']);
       	    $this->assign->modulename = urldecode($_REQUEST['modulename']);
       	    $this->assign->instance = urldecode($_REQUEST['instance']);
       	    $this->assign->maxbytes = urldecode($_REQUEST['maxbytes']);
       	    $this->assign->timedue = urldecode($_REQUEST['timedue']);
       	    $this->assign->timeavailable = urldecode($_REQUEST['timeavailable']);
       	    $this->assign->timemodified = time();
       	    $this->assign->cmidnumber = urldecode($_REQUEST['coursemodule']);
       	    $this->assign->var1 = $_REQUEST['assign_id'];
       	    $this->user = $DB->get_record("user", array('id' => $_REQUEST['uid']));

       	    $this->code = urldecode($_REQUEST['assignid']);

       	    if ($this->code >= 0) {
       	        if (trim($this->assign->name) == '') {
       	        	  $this->assign->name = get_string("modulename", $this->assign->modulename);
       	        }
       	        $assignment = new assignment_turnitin;
       	        $assignment->intercept_update_instance($this->assign);
       	    }
       	    if ($this->code >= 0) {
       	        if (!isset($this->assign->groupmode)) {
       	            $this->assign->groupmode = 0;
       	        }
       	        set_coursemodule_groupmode($this->assign->coursemodule, $this->assign->groupmode);
       	    }
       	    if ($this->code >= 0) {
       	        if (!isset($this->assign->visible)) {   // We get the section's visible field status
       	            $this->assign->visible = $DB->get_field("course_modules","visible", array('instance' => $this->assign->id, 'module' => 1));
       	        }
       	        // make sure visibility is set correctly (in particular in calendar)
       	        set_coursemodule_visible($this->assign->coursemodule, $this->assign->visible);
       	        rebuild_course_cache($this->assign->course);
       	        $this->code = $this->assign->instance;
       	        $this->msg = "Assignment Modified Successfully!";
       	    }
       	}
    }

    function get_roster() {
    	if ($this->check_md5()) {
    	    $this->cid = $_REQUEST['cid'];
    	    $context = get_context_instance(CONTEXT_COURSE, $this->cid);
    	    $courseusers = get_users_by_capability($context, 'mod/assignment:submit');
    	    $message = '<moodleApiResult students="'.count($courseusers).'"'.' courseid="'.$this->cid.'">'."\n";
    	    foreach ($courseusers as $courseuser) {
    	        $message .= "<student>\n";
    	        $data = unpack("H*",$courseuser->id);
    	        $message .= "<id>".$data[1]."</id>\n";
    	        $data = unpack("H*",$courseuser->username);
    	        $message .= "<username>".$data[1]."</username>\n";
    	        $data = unpack("H*",$courseuser->firstname);
    	        $message .= "<firstname>".$data[1]."</firstname>\n";
    	        $data = unpack("H*",$courseuser->lastname);
    	        $message .= "<lastname>".$data[1]."</lastname>\n";
    	        $data = unpack("H*",urlencode($courseuser->email));
    	        $message .= "<email>".$data[1]."</email>\n";
    	        $message .= "</student>\n";
    	    }
    	    $message .= '</moodleApiResult>'."\n";
    	    add_to_log($this->cid,"","","","TII API GET ROSTER",0,2);
    	    return $message;
    	} else {
    	    return $this->generate_message();
    	}
    }
    
    function submit_assignment() {
        global $USER, $DB;
    	if (! $this->assign = $DB->get_record("assignment", array("id" => urldecode($_REQUEST['moodle_assign_id'])) ) ) {
        	if (! $this->assign = $DB->get_record("assignment", array("var1" => urldecode($_REQUEST['assign_id']))))  {
    	        $this->code = -250;
    	        $this->msg = "Assignment Not Found!";
            } 
    	} 

        $assignment = new assignment_turnitin;
        $cm = $DB->get_record("course_modules", array('instance' => $this->assign->id));
        $this->assign->cmidnumber = $cm->id;
        $assignment->assignment = $this->assign;

        if ($this->assign->id) {
            if ($submission = $assignment->get_submission($_REQUEST['user_id'])) {
                //TODO: change later to ">= 0", to prevent resubmission when graded 0
            }

            if ($submission) {
                $submission->timemodified = time();
                $submission->numfiles = 1;
                $submission->submissioncomment = addslashes($submission->submissioncomment);
                $submission->data1 = $_REQUEST['paper_id']; 
                $submission->data2 = $_REQUEST['paper_title'];  // Don't need to update this.
                if ($DB->update_record("assignment_submissions", $submission)) {
                    $submission = $assignment->get_submission($_REQUEST['user_id']);
                    $assignment->update_grade($submission);
                    $assignment->email_teachers($submission);
        	    $this->msg = "Submission Updated!";
                } else {
                    $this->code = -350;
                    $this->msg = "Error Updating Submission!";
                }
            } else {
                $newsubmission = $assignment->prepare_new_submission($_REQUEST['user_id']);
                $newsubmission->timemodified = time();
                $newsubmission->numfiles = 1;
                $newsubmission->data1 = $_REQUEST['paper_id'];
                $newsubmission->data2 = $_REQUEST['paper_title'];
                if ($DB->insert_record('assignment_submissions', $newsubmission)) {
                    $submission = $assignment->get_submission($_REQUEST['user_id']);
                    $assignment->update_grade($submission);
                    $assignment->email_teachers($newsubmission);
                    $this->msg = "Submission Created!";
                } else {
                    $this->code = -450;
                    $this->msg = "Error Creating Submission!";
                }
            }
        }
    }

    function delete_submission() {
        global $DB;
    	if ($this->check_md5()) {
    	    if (! $this->assign = $DB->get_record("assignment", array("id" => urldecode($_REQUEST['assign_id'])))) {
    	          $this->code = -250;
    	          $this->msg = "Assignment Not Found!";
    	    } 
    	    
    	    $cm = $DB->get_record("course_modules", array('instance' => $this->assign->id));
            $this->assign->cmidnumber = $cm->id;
            $assignment = new assignment_turnitin;
            $assignment->assignment = $this->assign;
            
    	    if ($this->code >= 0) {
    	        if (!$this->user = $DB->get_record("user", array("id" => urldecode($_REQUEST['user_id'])))) {
    	            $this->code = -260;
    	            $this->msg = "User Not Found!";
    	        }
    	    }
    	    if ($this->code >= 0) {
                // Trying to erase the "final" grade when a submission is deleted
                $submission = $DB->get_record('assignment_submissions', array('assignment' => urldecode($_REQUEST['assign_id']), "userid" => urldecode($_REQUEST['user_id'])));
    	        $submission->grade = -1;
        	    $submission->timemarked = time();
    	        if (! $DB->update_record('assignment_submissions',$submission)) {
    	            $this->code = -270;
    	            $this->msg = "Error clearing out grade when deleting submission!";
    	        } else {
                    $assignment->update_grade($submission);
                }

    #		    $submission = create_submission($this->user->id,$this->assign->id);
    	        if (! $DB->delete_records('assignment_submissions', array("userid"=> $this->user->id, "assignment" => $this->assign->id))) {
    	            $this->code = -270;
    	            $this->msg = "Error Removing Submission Record!";
    	        } else {
    	            $this->code = $this->assign->id;
    	            $this->msg = "Submission Removed!";
    	        }
    		    add_to_log($this->assign->course,"","","","TII API ASSIGNMENT DELETED - (".$this->user->username." - ".$this->assign->name.")", 0, 2);
    	    }
    	}
    }
    
    function set_grade() {
        global $DB;
       	if ($this->check_md5()) {
       	    if (! $this->assign = $DB->get_record("assignment", array("id" => urldecode($_REQUEST['assign_id'])))) {
       	        $this->code = -250;
       	        $this->msg = "Assignment Not Found!";
       	    } else {
                $assignment = new assignment_turnitin;
                $assignment->assignment = $this->assign;
            }
       	    
       	    $cm = $DB->get_record("course_modules", array('instance' => $this->assign->id));
            $this->assign->cmidnumber = $cm->id;
            $assignment = new assignment_turnitin;
            $assignment->assignment = $this->assign;
       	    
   	        if (trim($this->assign->name) == '') {
       	        $this->assign->name = get_string("modulename", $this->assign->modulename);
       	    }
       	    
       	    if ($this->code >= 0) {
       	        if (!$this->user = $DB->get_record("user", array("id" => urldecode($_REQUEST['user_id'])))) {
       	            $this->code = -260;
       	            $this->msg = "User Not Found!";
       	        }
       	    }

       	    if ($this->code >= 0) {
       	        $submission = $DB->get_record('assignment_submissions', array('assignment' => urldecode($_REQUEST['assign_id']), "userid" => urldecode($_REQUEST['user_id'])));
       	        $submission->grade = $_REQUEST['grade'];
       	        $submission->timemarked = time();
       	        if (! $DB->update_record('assignment_submissions',$submission)) {
       	            $this->code = -270;
       	            $this->msg = "Error Updating Submission Record!";
       	        } else {
       	            $this->code = $this->assign->id;
       	            $this->msg = "Submission Updated!";
       	            add_to_log($this->assign->course,"","","","TII API SUBMISSION GRADED (".$this->user->username." - ".$this->assign->name.")",$submission->userid, 2);

                    $assignment->update_grade($submission);
       	        }
       	    }
       	}
       }

    function system_error() {
	    $this->code = -999;
    	$this->msg = "System Error!";
    }

    function get_gmtime() {
        return substr(gmdate('YmdHi'), 0, -1);
    }

    function check_md5(){
        global $CFG;
    	$md5string = pack("H*",urldecode($_REQUEST['message'])).TII_SHAREDKEY;
    	if (TII_LOGGING == true) {
            require_once($CFG->dirroot."/mod/assignment/type/turnitin/logger.class.php");
    	    $logger = new Logger;
    	    $logger->log("MD5:".urldecode($_REQUEST['md5'])." - ".md5($md5string)." - ".$md5string);
    	}
    	if ($_REQUEST['md5'] == md5($md5string)) {
    	    return true;
    	} else {
    	    $this->code = -1;
    	    $this->msg = "MD5 not verified. No access.";
    	    return false;
    	}
    }
}


?>
