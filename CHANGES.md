# Changes

## 1.2.1 (2026101300)

- The profile section *Skills earned* is now called **Skills**.
- New setting *Skills on the user profile* (Site administration > Plugins > Admin tools > Skills >
  General settings): skills with courses and activities (as before, the default), skill totals only
  (no course and activity rows), or disabled (no Skills section on the profile).
- New hook `\tool_skills\hook\extend_profile_skill`: plugins can add pills below a skill's points in
  the profile Skills section, for example the points removed by decay or a target. A pill's details open
  in a popover when it is tapped, clicked or activated with the keyboard, also on touch devices. See the
  new developer guide.
- The Targets addon (`skilladdon_targets`) is included as a git submodule like the other addons.
- Pills of a collapsed skill are no longer partly covered by the skill's header.
- Behat step `the user "…" has "…" points for the skill "…"` for the addons' suites (previously
  defined by each addon, which clashed when several were installed).

## 1.2 (2026092300)

- Moodle 5.2 and 5.3 support; Moodle 4.5 remains the minimum.
- Skills and levels can carry a colour and levels an image; a skill may have no levels at all
  (points only). The level count now means the total number of levels.
- Redesigned *Skills earned* panel on the user profile: current level with colour and image, points
  earned out of the total, and a per-course breakdown with points chips. Addons can contribute
  per-course content (activity breakdown).
- The course and activity navigation entry is now called **Skills**.
- Skill addons are pulled in as git submodules of this repository; the former separate "Pro"
  repository is retired. Addon settings pages load under the Skills admin category.
- Database indexes on the award log and level tables; deterministic ordering of the course skills
  list.
- Security hardening of the course skill form (write target resolved from the course, level checked
  against the skill) and capability risk masks revisited.
- PHPUnit suite added; Behat suites updated; CI runs on every push to main and on pull requests.

## 1.1

- Moodle 4.5 and 5.0 compatibility.
- Admin settings category for Skills.
- Dash widget support (moved to addons in 1.2).
