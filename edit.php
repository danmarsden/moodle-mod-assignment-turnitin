<?php
/**
 * Edit Assignment
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 *
 * v 2.0 2007/06/27 10:00:00 Northumbria Learning
 * Adpated for Moodle 1.8
 */
require_once("../../../../config.php");

global $USER, $CFG, $DB, $OUTPUT, $PAGE;

$assignID = required_param('id', PARAM_INT);
$courseID = required_param('course', PARAM_INT);

$tii_url = $_SESSION['tii_url'];
unset($_SESSION['tii_url']);

if(!$tii_url) {
    print_error("URL Not Defined");
} 

if (! $assignment = $DB->get_record("assignment", array('id' => $assignID)) ){
    print_error("Assignment Not Found".$assignID);
}

if (! $course = $DB->get_record("course", array("id" => $courseID)) ) {
    print_error("Course is misconfigured".$courseID);
}

require_login( $course );

$url = new moodle_url('/mod/assignment/type/turnitin/edit.php', array('course' => $courseID, 'id' => $assignID));

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

echo "<iframe class=\"tii_frame\" marginheight=\"0\" marginwidth=\"0\" scrolling=\"auto\" frameborder=\"0\" width=\"100%\" height=\"100%\" src=\"".$tii_url."\" />";

?>

