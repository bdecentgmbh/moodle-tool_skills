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
 * Tool skills - Context of a change of a user's skill points.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_skills;

/**
 * Why and through which source a user's skill points change, recorded in the points history.
 *
 * Pass it to {@see skills::set_userskill_points()}. Allocation methods build it with
 * {@see allocation_method::get_points_change()}.
 */
final class points_change {
    /**
     * Describe a change.
     *
     * @param string $reason One of the points_history::REASON_* constants.
     * @param string|null $method Source type, for example 'course', 'activity' or 'decay'.
     * @param int|null $methodid ID of the source in the table of its method.
     * @param int|null $courseid Course of the source.
     * @param int|null $cmid Course module of the source.
     * @param string|null $sourcename Name of the source at the time of the change, unformatted.
     * @param int|null $usermodified User who made a manual change.
     */
    public function __construct(
        /** @var string One of the points_history::REASON_* constants. */
        public readonly string $reason,
        /** @var string|null Source type, for example 'course', 'activity' or 'decay'. */
        public readonly ?string $method = null,
        /** @var int|null ID of the source in the table of its method. */
        public readonly ?int $methodid = null,
        /** @var int|null Course of the source. */
        public readonly ?int $courseid = null,
        /** @var int|null Course module of the source. */
        public readonly ?int $cmid = null,
        /** @var string|null Name of the source at the time of the change, unformatted. */
        public readonly ?string $sourcename = null,
        /** @var int|null User who made a manual change. */
        public readonly ?int $usermodified = null,
    ) {
    }

    /**
     * The same change with another reason, for example to record that a source's earlier points were revoked.
     *
     * @param string $reason One of the points_history::REASON_* constants.
     * @return self
     */
    public function with_reason(string $reason): self {
        return new self(
            $reason,
            $this->method,
            $this->methodid,
            $this->courseid,
            $this->cmid,
            $this->sourcename,
            $this->usermodified
        );
    }
}
