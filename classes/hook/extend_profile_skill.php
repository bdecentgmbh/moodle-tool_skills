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

namespace tool_skills\hook;

use tool_skills\skills;

/**
 * Lets plugins add pills to a skill in the Skills section of the user profile.
 *
 * Dispatched once per skill while the section is built, in both the detailed and the totals-only display.
 * A pill is a short plain-text badge shown below the skill's points, for example the points removed by
 * decay or the user's target for the skill; its optional title opens in a popover when the pill is tapped,
 * clicked or activated with the keyboard. Callbacks are registered in the plugin's db/hooks.php.
 *
 * @package   tool_skills
 * @copyright 2026 bdecent GmbH <https://bdecent.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Allows plugins to add pills (short badges) to a skill in the Skills section of the user profile.')]
#[\core\attribute\tags('tool_skills', 'user')]
final class extend_profile_skill {
    /** @var string[] Colour variants a pill may use, named after the Bootstrap contextual colours. */
    public const VARIANTS = ['secondary', 'info', 'success', 'warning', 'danger'];

    /** @var array[] Pills added so far, keyed by name. */
    private array $pills = [];

    /**
     * Create a new instance of the hook.
     *
     * @param int $userid
     * @param skills $skill
     * @param int $points
     */
    public function __construct(
        /** @var int The user whose profile shows the skill. */
        public readonly int $userid,
        /** @var skills The skill being rendered. */
        public readonly skills $skill,
        /** @var int The user's current points for the skill. */
        public readonly int $points,
    ) {
    }

    /**
     * Add a pill to the skill. Adding a pill with a name that is already used replaces that pill.
     *
     * The label should make sense on its own; the title adds the details.
     *
     * @param string $name Identifier, for example 'skilladdon_decay'; used in the CSS class toolskill-pill-<name>.
     * @param string $label Plain text shown in the pill, for example '−12 decay'.
     * @param string $title Optional plain text shown in a popover, for example '12 points removed by decay'.
     * @param string $variant One of self::VARIANTS.
     * @return self
     */
    public function add_pill(string $name, string $label, string $title = '', string $variant = 'secondary'): self {
        $cleanname = clean_param($name, PARAM_ALPHANUMEXT);
        if ($cleanname === '' || $cleanname !== $name) {
            throw new \coding_exception("Invalid pill name '{$name}', use letters, digits, '_' and '-' only");
        }
        if (!in_array($variant, self::VARIANTS, true)) {
            throw new \coding_exception("Invalid pill variant '{$variant}', use one of " . implode(', ', self::VARIANTS));
        }

        $this->pills[$name] = [
            'name' => $name,
            'label' => $label,
            'title' => $title,
            'variant' => $variant,
        ];
        return $this;
    }

    /**
     * Remove the pill with the given name, if any.
     *
     * @param string $name
     * @return self
     */
    public function remove_pill(string $name): self {
        unset($this->pills[$name]);
        return $this;
    }

    /**
     * Get the pills in the order they were added.
     *
     * @return array[] Each with 'name', 'label', 'title' and 'variant'.
     */
    public function get_pills(): array {
        return array_values($this->pills);
    }
}
