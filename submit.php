<?php
/**
 * Submit Assignment
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 8:50:14 Northumbria Learning
 * Adapted for Moodle 1.8
 */
require_once("../../../../config.php");
require_once($CFG->dirroot.'/mod/assignment/lib.php');
require_once("assignment.class.php");
require_once("tiilib.php");
require_once("tiiapi.class.php");
global $USER, $CFG, $DB, $OUTPUT, $PAGE;

$id = required_param('id', PARAM_INT);     // Assignment ID
$contextid = required_param('contextid', PARAM_INT);

list($context, $course, $cm) = get_context_info_array($contextid);

if (!$assignment = $DB->get_record("assignment", array('id' => $id)) ) {
    print_error('invalidid', 'assignment');
}

require_login($course);

$url = new moodle_url('/mod/assignment/type/turnitin/submit.php', array('id' => $id, 'contextid' => $contextid));

$strassignments = get_string('modulenameplural', 'assignment');
$strassignment = get_string('modulename', 'assignment');
$title = $course->shortname.': '.$strassignment.': '.$assignment->name;

$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);
$PAGE->set_url($url);
$PAGE->requires->css("/mod/assignment/type/turnitin/css/turnitin.css", true);

$PAGE->navbar->add($strassignments, new moodle_url($CFG->wwwroot.'/mod/assignment/index.php?id='.$course->id), null, navigation_node::TYPE_CUSTOM, null);
$PAGE->navbar->add($assignment->name, null, null, navigation_node::TYPE_CUSTOM, null);
echo $OUTPUT->header();
 
if (has_capability('mod/assignment:grade', get_context_instance(CONTEXT_COURSE, $course->id))) {
    $tii = new tii_api($course->id, TII_FCMD_LOGGEDIN, TII_FID_SUBMITASSIGNMENTSTAFF);
} else {
    $tii = new tii_api($course->id, TII_FCMD_LOGGEDIN, TII_FID_SUBMITASSIGNMENTSTUDENT);
}

$instance = new assignment_turnitin($cm->id, $assignment, $cm, $course);
$instance->setup_moodle_params($cm, $id);

$tii->setup_parameters($assignment, $course, $instance);
                  
echo "<iframe class=\"tii_frame\" marginheight=\"0\" marginwidth=\"0\" scrolling=\"auto\" frameborder=\"0\" width=\"100%\" height=\"100%\" src=\"".$tii->get_url()."\" />";
    
?>
