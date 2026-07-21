<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tool Skills - PHPUnit tests for lib.php profile navigation.
 *
 * @package   tool_skills
 * @copyright 2023 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/skills/lib.php');

/**
 * Unit tests for the "Skills earned" profile node visibility.
 *
 * @covers ::tool_skills_myprofile_navigation
 */
final class lib_test extends \advanced_testcase {

    /**
     * Create a course with an enabled skill, enrol the user and award some points.
     *
     * @return array [stdClass $user, stdClass $course, int $skillid]
     */
    private function setup_user_with_skill(): array {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);

        $skillid = $DB->insert_record('tool_skills', (object) [
            'name' => 'Communication', 'identitykey' => 'communication', 'description' => '',
            'status' => 1, 'categories' => '[]', 'learningtime' => 7776000, 'levelscount' => 1,
            'archived' => 0, 'timearchived' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('tool_skills_levels', (object) [
            'skill' => $skillid, 'name' => 'Level 1', 'points' => 50, 'status' => 1,
            'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('tool_skills_courses', (object) [
            'courseid' => $course->id, 'skill' => $skillid, 'status' => 1,
            'uponcompletion' => skills::COMPLETIONPOINTS, 'points' => 50, 'level' => 0, 'timemodified' => time(),
        ]);
        $DB->insert_record('tool_skills_userpoints', (object) [
            'skill' => $skillid, 'userid' => $user->id, 'points' => 60,
            'timecreated' => time(), 'timemodified' => time(),
        ]);

        return [$user, $course, $skillid];
    }

    /**
     * Build a fresh profile tree and run the navigation callback.
     *
     * @param \stdClass $user profile owner
     * @param bool $iscurrentuser
     * @param \stdClass $course
     * @return \core_user\output\myprofile\tree
     */
    private function run_callback($user, bool $iscurrentuser, $course): \core_user\output\myprofile\tree {
        $tree = new \core_user\output\myprofile\tree();
        tool_skills_myprofile_navigation($tree, $user, $iscurrentuser, $course);
        return $tree;
    }

    /**
     * Return the keys of any per-skill nodes added to the tree (keyed "skill_<skillid>").
     *
     * @param \core_user\output\myprofile\tree $tree
     * @return string[]
     */
    private function skill_node_keys(\core_user\output\myprofile\tree $tree): array {
        return array_values(array_filter(
            array_keys($tree->__get('nodes')),
            fn($key) => strpos($key, 'skill_') === 0
        ));
    }

    /**
     * A per-skill node is shown on the user's own profile, with the course breakdown in its content.
     *
     * The navigation callback surfaces the current user's own skills, so the profile owner must be the
     * logged-in user. Nodes are keyed "skill_<skillid>", not a single combined node.
     */
    public function test_node_shown_on_own_profile(): void {
        $this->resetAfterTest(true);
        [$user, $course, $skillid] = $this->setup_user_with_skill();
        $this->setUser($user);

        $tree = $this->run_callback($user, true, $course);
        $nodes = $tree->__get('nodes');

        $this->assertArrayHasKey('skill_' . $skillid, $nodes);
        $content = $nodes['skill_' . $skillid]->content;
        // The skill name and the per-course breakdown (name + earned points) must be present.
        $this->assertStringContainsString('Communication', $content);
        $this->assertStringContainsString(format_string($course->fullname), $content);
        $this->assertStringContainsString(get_string('earned', 'tool_skills'), $content);
    }

    /**
     * No skill node is shown on another user's profile when the viewer lacks the capability.
     */
    public function test_node_hidden_for_other_user_without_capability(): void {
        $this->resetAfterTest(true);
        [$user, $course] = $this->setup_user_with_skill();
        // A plain user without tool/skills:viewotherspoints is the viewer.
        $this->setUser($this->getDataGenerator()->create_user());

        $tree = $this->run_callback($user, false, $course);
        $this->assertEmpty($this->skill_node_keys($tree));
    }

    /**
     * The profile navigation only ever surfaces the current user's own skills: even a viewer who holds
     * tool/skills:viewotherspoints does not see another user's skills on that user's profile.
     */
    public function test_other_users_skills_not_exposed_with_capability(): void {
        $this->resetAfterTest(true);
        [$user, $course] = $this->setup_user_with_skill();
        // Admin holds tool/skills:viewotherspoints but is viewing someone else's profile.
        $this->setAdminUser();

        $tree = $this->run_callback($user, false, $course);
        $this->assertEmpty($this->skill_node_keys($tree));
    }
}
