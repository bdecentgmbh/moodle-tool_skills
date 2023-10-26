<?php

require_once('../../../config.php');

$userid = 6;
$courseid = 259;

\tool_skills\courseskills::get($courseid)->manage_course_completions($userid, []);
