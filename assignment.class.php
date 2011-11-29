<?php
/**
 * Extend the base assignment class for assignments for Turn It In API
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 8:50:14 Northumbria Learning
 * Adapted for Moodle 1.8
 * 
 * v 2.0.3 08/07/08 iParadigms - David Wu
 * Adapted for Moodle 1.9 - changed to work with Gradebook changes
 *
 */
require_once($CFG->dirroot.'/mod/assignment/lib.php');
require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->dirroot.'/calendar/lib.php');
require_once("tiiapi.class.php");
require_once("tiilib.php");

class assignment_turnitin extends assignment_base {

    function assignment_turnitin($cmid=0, $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'turnitin';
    }

    function intercept_add_instance($assign) {
        global $COURSE, $DB;
        $assign->timemodified = time();
        $assign->courseid = $assign->course;
        
        if ($returnid = $DB->insert_record("assignment", $assign)) {
            $assign->id = $returnid;
            if ($assign->timedue) {
                $event = new stdClass();
                $event->name        = $assign->name;
                $event->description = $assign->description;
                $event->courseid    = $assign->course;
                $event->groupid     = 0;
                $event->userid      = 0;
                $event->modulename  = 'assignment';
                $event->instance    = $returnid;
                $event->eventtype   = 'due';
                $event->timestart   = $assign->timedue;
                $event->timeduration = 0;

                calendar_event::update($event, false);
            }

           assignment_grade_item_update($assign);
        }
        return $returnid;
    }
    
    function intercept_update_instance($assign) {
        global $DB;
        $assign->timemodified = time();
        $assign->id = $assign->instance;

        if ($returnid = $DB->update_record('assignment', $assign)) {

            if ($assign->timedue) {
                $event = new stdClass();

                if ($event->id = $DB->get_field('event', 'id', array('instance' => $assign->id)) ) {

                    $event->name        = $assign->name;
                    $event->description = $assign->description;
                    $event->timestart   = $assign->timedue;

                    calendar_event::update($event, false);
                } else {
                    $event->name        = $assign->name;
                    $event->description = $assign->description;
                    $event->courseid    = $assign->course;
                    $event->groupid     = 0;
                    $event->userid      = 0;
                    $event->modulename  = 'assignment';
                    $event->instance    = $assign->id;
                    $event->eventtype   = 'due';
                    $event->timestart   = $assign->timedue;
                    $event->timeduration = 0;

                    calendar_event::update($event, false);
                }
            } else {
                $DB->delete_records('event', array('instance' => $assign->id));
            }
        }
       
        assignment_grade_item_update($assign);

        return $returnid;
    }

    function delete_instance($assign) {
        global $CFG, $USER, $DB, $OUTPUT;

        if (!$assignment = $DB->get_record("assignment", array("id" => $assign->id)) ) {
            print_error("Assignment Not Found!");
        }

        if (!$course = $DB->get_record("course", array("id" => $assignment->course)) ) {
            print_error("Course Not Found!");
        }
        
        if (!$cm = $DB->get_record("course_modules", array('instance' => $assign->id)) ) {
            print_error("Course Module Invalid");
        }

        $tii = new tii_api($course->id, TII_FCMD_DELETEASSIGNMENT, TII_FID_CREATEASSIGNMENT);

        $this->setup_moodle_params($cm, $assign->id);
        $tii->setup_parameters($assignment, $course, $this);

        if (TII_LOGGING == true) {
            require_once($CFG->dirroot."/mod/assignment/type/turnitin/logger.class.php");
            $logger = new Logger;
            $logger->log("API DELETE:"."START");
        }

        //$fullurl = $tii->get_url();

        //$request = new TIIRequest($fullurl);
        $tii->send_post_request();

        if ($tii->getRcode() > 100) {
            $OUTPUT->notification("Error on API Deletion!".$tii->getRcode()." - ".$tii->getRmessage());
        }

        $DB->delete_records('assignment_submissions', array('assignment' => $assignment->id));
        $DB->delete_records('assignment', array('id' => $assignment->id));
        $DB->delete_records('event', array('instance' => $assignment->id));

        // delete file area with all attachments - ignore errors
        require_once($CFG->libdir.'/filelib.php');
        fulldelete($CFG->dataroot.'/'.$assignment->course.'/'.$CFG->moddata.'/assignment/'.$assignment->id);

        assignment_grade_item_delete($assignment);
        return true;
    }


    function setup_assignment($is_update) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE;
        
        $assignment = $this->assignment;
        
        if (!$course = $DB->get_record("course", array("id" => $assignment->course)) ) {
            print_error("Course Not Found!");
        }
        
        if ($is_update) {
            $tii = new tii_api($course->id,TII_FCMD_EDITASSIGNMENT,TII_FID_CREATEASSIGNMENT);
        } else {
            $tii = new tii_api($course->id,TII_FCMD_LOGGEDIN,TII_FID_CREATEASSIGNMENT);
        }
        
        $tii->setup_parameters($assignment, $course, $this);
       
        $url = $tii->get_url();
        
        $_SESSION['tii_url'] = $url;
        if($is_update) {
            redirect($CFG->wwwroot."/mod/assignment/type/turnitin/edit.php?course=".$assignment->course."&id=".$assignment->id);
        } else {
            redirect($CFG->wwwroot."/mod/assignment/type/turnitin/create.php?course=".$assignment->course);
        }
        
    }
    
    
    function view() {
        global $USER, $CFG, $OUTPUT;

        $context = get_context_instance(CONTEXT_MODULE,$this->cm->id);
        require_capability('mod/assignment:view', $context);

        add_to_log($this->course->id, "assignment", "view", "view.php?id={$this->cm->id}", $this->assignment->id, $this->cm->id);

        $this->view_header();

        $this->view_intro();

        $this->view_dates();

        $this->view_submit();
        
        $this->view_footer();
    }
    
    function view_header() {
        global $CFG, $PAGE, $OUTPUT;

        $PAGE->set_title($this->pagetitle);
        $PAGE->set_heading($this->course->fullname);
        $PAGE->requires->css("/mod/assignment/type/turnitin/css/turnitin.css", true);

        echo $OUTPUT->header();

        echo '<div class="reportlink">'.$this->submittedlink().'</div>';
        echo '<div class="clearer"></div>';
    }
    
    function view_submit() {
        global $OUTPUT, $CFG;
        echo $OUTPUT->box_start('generalbox boxaligncenter', 'submit');
        echo "<div style=\"text-align: center\">";
        echo "<a href=".$CFG->wwwroot."/mod/assignment/type/turnitin/submit.php?id=".$this->assignment->id."&contextid=".$this->context->id.">[SUBMIT]</a>";
        echo "</div>";
        echo $OUTPUT->box_end();
    }
    
    function submittedlink() {
        global $CFG, $USER;
        $submitted = '';
        $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        if (has_capability('mod/assignment:grade', $context)) {
            if (!has_capability('moodle/course:managegroups', $context) and user_group($this->course->id, $USER->id)) {
                $count = $this->count_real_submissions($this->currentgroup);  // Only their group
            } else {
                $count = $this->count_real_submissions();                     // Everyone
            }
            $submitted = '<a href="type/turnitin/submissions.php?id='.$this->assignment->id.'&contextid='.$this->context->id.'">'.get_string('viewsubmissions', 'assignment', $count).'</a>';
        } else {
            if (isset($USER->id)) {
                if ($submission = $this->get_submission($USER->id)) {
                    if ($submission->timemodified) {
                        if ($submission->timemodified <= $this->assignment->timedue || empty($this->assignment->timedue)) {
                            $submitted = '<span class="tii_early">'.userdate($submission->timemodified).'</span>';
                        } else {
                            $submitted = '<span class="tii_late">'.userdate($submission->timemodified).'</span>';
                        }
                    }
                }
            }
        }
        return $submitted;
    }

    function print_student_answer($userid, $return=false){
        global $CFG;

        $output = '';

        return $output;
    }
    
    function print_user_files($userid, $return=false) {
        global $CFG, $USER, $DB;
        
        $id = required_param('id', PARAM_INT);
        
        if (!$submission = $this->get_submission($userid)) {
            $output = "No Submission";
        } else {
            if (! $cm = $DB->get_record("course_modules", array("id" => $id)) ) {
                print_error("Course Modules Not Found");
            }
                   
            if (!$assignment = $DB->get_record("assignment", array("id" => $cm->instance)) ) {
                print_error("Assignment Not Found!".$cm->instance);
            }
        
            if (!$course = $DB->get_record("course", array("id" => $assignment->course)) ) {
                print_error("Course Not Found!");
            }
        
            $submission = $this->get_submission($userid);
        
            $tii = new tii_api($course->id, TII_FCMD_LOGGEDIN, TII_FID_VIEWPAPER);
        
            $paper_id = $submission->data1;
            $paper_title = $submission->data2;
        
            $this->setup_moodle_params($cm, $assignment->id);
            $tii->setup_parameters($assignment, $course, $this);
            $tii->set_objectID($paper_id);
            $output = '<a target="_blank" href='.$tii->get_url().'>'.$paper_title.'</a>';
                    
        }
        return $output;
    }
    
    function setup_moodle_params($cm, $assignID) {
        global $DB;
        $this->instance = $cm->instance;

        if (! $cw = $DB->get_record("course_sections", array('id' => $cm->section)) ) {
            print_error("This course section doesn't exist");
        }
        if (! $module = $DB->get_record("modules", array('id'=> $cm->module)) ) {
            print_error("This module doesn't exist");
        }

        $this->section = $cw->section;
        $this->groupmode = 0;
        $this->module = $module->id;
        $this->modulename = $module->name;
        $this->coursemodule = $cm->id;
        $this->visible = $DB->get_field("course_modules", "visible", array('instance' => $assignID, 'module' => 1));
    }

    function setup_elements(&$mform) {
        global $CFG, $USER, $DB;

        $courseid = optional_param("course", 0, PARAM_INT);
        $update = optional_param("update", 0, PARAM_INT);
        $section = optional_param("section", 0, PARAM_INT);
        $type = optional_param("type", NULL, PARAM_TEXT);
        $add = optional_param("add", NULL, PARAM_TEXT);
        
        $is_update = ($update > 0);
        $is_create = ($courseid > 0);
        
        $instance = $this;

        if ($is_update) {
            if (! $cm = $DB->get_record("course_modules", array('id' => $update)) ) {
                print_error("Course Module Not Found!");
            }

            if (! $cw = $DB->get_record("course_sections", array('id' => $cm->section)) ) {
                print_error("This course section doesn't exist");
            }

            if (!$assignment = $DB->get_record("assignment", array('id' => $cm->instance)) ) {
                print_error("Assignment Not Found!");
            }

            if (! $module = $DB->get_record("modules", array('id'=> $cm->module)) ) {
                print_error("This module doesn't exist");
            }
        }
        
        if ($is_update){
            $this->type = $assignment->assignmenttype;
            $this->coursemodule = $update;
            $this->instance = $cm->instance;
            $this->section = $cw->section;
            $this->groupmode = 0;
            $this->module = $module->id;
            $this->modulename = $module->name;
            $this->visible = $DB->get_field("course_modules", "visible", array('instance' => $assignment->id, 'module' => 1));
            $this->assignment = $assignment;
        } elseif ($is_create) {
            $assignment = new stdClass();
            $this->type = $type;
            $this->add = $add;
            $this->section = $section;
            $this->groupmode = 0;
            $this->modulename = $add;
            $this->visible = NULL;
            $this->coursemodule = NULL;
            $this->instance = NULL;
            
            $assignment->assignmenttype = $type;
            $assignment->course = $courseid;
            $assignment->name = NULL;
            $assignment->id = NULL;
            $assignment->intro = NULL;
            $assignment->introformat = NULL;
            $assignment->grade = NULL;
            $assignment->timeavailable = NULL;
            $assignment->timedue = NULL;
            $assignment->preventlate = NULL;
            $assignment->maxbytes = NULL;
            
            if (! $module = $DB->get_record("modules",  array("name" => $add)) ) {
                print_error("This module type doesn't exist");
            }
            $this->module = $module->id;
            
            $this->assignment = $assignment;
        } else {
            print_error("Turnitin Assignment Failed to create/update");
        }
        
        $this->setup_assignment($is_update);
    } 
}

?>
