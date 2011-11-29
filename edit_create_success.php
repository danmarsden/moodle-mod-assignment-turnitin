<?php
/**
 * Success Screen
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 *
 * v 1.1 2007/02/01 10:00:00 Northumbria Learning
 * Adapted for Moodle 1.8
 */
require_once("../../../../config.php");
global $DB;

$id = required_param('id', PARAM_INT);    // Assignment ID

if (!$cm = get_coursemodule_from_instance('assignment', $id) ) {
    print_error("Course Module Not Found".$id);
}

$redirect_url = $CFG->wwwroot."/mod/assignment/view.php?id=".$cm->id;
echo '<script>parent.document.location.href="'.$redirect_url.'";</script>';
echo '<a href="'.$redirect_url.'" target="_top">Return to homepage</a>'; //fix for instant redirect issue where redirect() is called before js stuff is handled
//redirect($CFG->wwwroot."/mod/assignment/view.php?id=".$cm->id);

?>



