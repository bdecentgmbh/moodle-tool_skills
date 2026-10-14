# Changes

## 1.4 (unreleased)

- Points history: every change of a user's skill points is recorded with its time, reason (award,
  revoke, decay, import, other), source (course, activity or decay rule, with its name at the time) and the
  level before and after. Points earned before the update are imported once in the background; where the
  import does not add up to a user's points, one correction row is added. The Reports addon reports on
  it with its new *Skill points history* source.
- Events: *Skill points changed* and *Skill level reached*, and *Skill created*, *updated*, *archived*,
  *restored* and *deleted*.
- Archiving and restoring a skill show their confirmation message again.
- Privacy: the points history is exported and deleted in the user's context; the manager of a manual
  change in the system context.
- Developers: `skills::set_userskill_points()` takes a `points_change` describing the change; see the
  developer guide.

## 1.3 (2026101400)

Includes the changes of 1.2 R3 and 1.2 R4, which were released for this plugin only and not in a
Skills bundle.

### Skills on the user profile

- The profile section *Skills earned* is now called **Skills** and was redesigned: each skill shows its
  current level with the level's colour and image, the points earned out of the total, and a
  per-course breakdown with points chips. Addons can contribute per-course content (activity
  breakdown).
- New setting *Skills on the user profile* (Site administration > Plugins > Admin tools > Skills >
  General settings): skills with courses and activities (the default), skill totals only (no course
  and activity rows), or disabled (no Skills section on the profile).
- New hook `\tool_skills\hook\extend_profile_skill`: plugins can add pills below a skill's points in
  the profile Skills section, for example the points removed by decay or a target. A pill's details open
  in a popover when it is tapped, clicked or activated with the keyboard, also on touch devices. See the
  developer guide.
- The skill accordion works on Moodle 4.5, and pills of a collapsed skill are not covered by the
  skill's header.

### Skills and levels

- Skills and levels can carry a colour and levels an image; a skill may have no levels at all (points
  only). The level count now means the total number of levels.
- The course and activity navigation entry is now called **Skills**.
- The course skills list is always sorted the same way.
- The users' points report no longer fails with a database error (a regression in 1.2 R3).

### Addons

- Skill addons are pulled in as git submodules of this repository, including the new Targets addon
  (`skilladdon_targets`); only the submodule commits are recorded here, never the addon code.
  Customers with addon access install the Skills bundle, which contains this plugin with the addons in
  place. The former separate "Pro" repository no longer holds addon code.
- The source zips of this repository (GitHub releases) contain no empty folders for the addon
  submodules, which Moodle would report as plugins with a missing `version.php`.

### Compatibility, security and quality

- Moodle 5.2 and 5.3 support; Moodle 4.5 remains the minimum.
- Security fixes: the course skill form resolves where it saves from the course and checks that the
  level belongs to the skill; the risk flags of the capabilities were reviewed (MDL Shield findings).
- Database indexes on the award log and level tables; the deprecated `db/subplugins.php` is removed.
- PHPUnit suite added and Behat suites updated, with the Behat step
  `the user "…" has "…" points for the skill "…"` for the addons' suites. CI runs on every push to
  main and on pull requests, one job per Moodle version from 4.5 to 5.3.
- Documentation per audience (administrator, manager, teacher and developer guides).

## 1.2 (2026032400)

- Moodle 4.5 is the minimum; Moodle 5.1 support.
- Improved form to create skills.
- 1.2 R2 (2026032401): PHPDoc fixes, missing language strings and file headers.

## 1.1

- Moodle 4.5 and 5.0 compatibility.
- Admin settings category for Skills.
- Dash widget support (moved to addons in 1.2).
