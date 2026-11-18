# Changes

## 1.4 (2026111800)

- Skills page of a course: a **Skills** tab in each course that awards a skill opens *Skills in this
  course*, with a card per skill. Learners see their level and points, the points from this course and
  what the course and its activities award, with the points earned there; teachers see what the course
  awards, and reach *Manage course skills* from its menu. New capability `tool/skills:viewcourseskills`
  (students, teachers, managers). Learners do not get the tab when the profile Skills section is disabled.
- Participants' progress of a course: teachers follow the course's learners one skill at a time, with the
  points from this course and, with the new capability `tool/skills:viewcourseparticipants` (teachers,
  managers; personal data risk), their level and total points. Group filter, sorting, initials bar and
  download. The course's Skills pages share a menu like the Participants page, which is also their
  heading: Overview, Participants' progress and Manage course skills.
- Teachers' overview: each skill card says how the skill is earned in this course, links to its
  participants' progress and, with `tool/skills:viewcourseparticipants`, counts the learners per level.
- Users-points report: users with the same points are each listed (only one of them was before), and the
  report is sorted, most points first, by either column.
- Courses for skills: a page for learners to find the courses that award a skill, with what the course
  and (with Activity skills) its activities award, and whether the learner has completed the course, is
  enrolled or how to enrol. It lists the courses the learner sees elsewhere in Moodle that award something
  in the skill. Linked from the profile Skills section and from each skill on a course's Skills page; new
  capability `tool/skills:browsecourses` (authenticated users). Not available when the profile Skills
  section is disabled. Addons add their awards through a `get_course_awards()` method.
- Profile Skills section: a line about the section opens it, with an actions menu beside it that replaces
  the links and buttons below the skills: *Find courses*, the addons' actions (*Select targets*) and, as the
  user may, *Manage skills*, *Manage targets* and the decay pages; addons add theirs through a
  `get_profile_actions()` method. Each skill has a menu with *Find courses*, *Users' points* (instead of the
  *View users points for this skill* link) and *Edit skill*.
- Learning time: shown as the first pill of a skill, an outline pill that explains it when tapped, in the
  unit entered on the skill ("40 hours" instead of "1 day 16 hours"), on the profile and on the course's
  Skills page, also when the skill's panel is collapsed.
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
- Management pages without the profile Skills section: the menu of a course's Skills pages has a
  *Site-wide* group with the pages the user may open there, *Manage skills* and the addons' pages (*Manage
  targets*, the decay pages), as in the actions menu of the profile. Users who manage skills, targets or
  decay without being site administrators also find them in the *Skills* section of Site administration,
  under Plugins > Admin tools as for site administrators (Moodle 4.5, which shows Admin tools to site
  administrators only: as a section of its own). The description of *Skills on the user
  profile* says where these pages are when it is disabled, and that learners then cannot choose optional
  targets (SKI-231).
- Levels: each level needs more points than the level before it; the skill form rejects levels with the
  same or fewer points. Skills whose levels were saved out of order show the same level everywhere, the
  highest level whose points the user reaches: the profile, the course's Skills pages, the *Skills* report
  source (which showed the last level in database order) and the Skill progress and Level visuals widgets.
  Level lists, such as the level to award in a course, are sorted by points (SKI-230).

### Addons in the Skills bundle (1.4)

- Skill targets: optional targets, which members of the target's cohort choose on their profile
  (*Select targets*), with their own date; targets for all members of the cohort work as before.
- Skill decay: a *Next decay* pill with the date, about how many points and the rule; decays (with their
  rule, manual decays with the manager) are recorded in the points history.
- Skills reports: new report source *Skill points history*.
- Activity skills: activity awards on the course's Skills page and the Courses for skills page, and in the
  points history; the profile lists only activities the learner sees.
- Skill progress and Level visuals: the current and next level follow the levels' points, not the order in
  the database.
- Skill decay and Skill targets: the skill and cohort fields of the manual decay, decay rule and target
  forms start with *Choose...* instead of the first entry (SKI-229).
- Every addon's changelog has the details. Level visuals and Skill progress are unchanged (1.3).

## 1.3 R3 (2026101402)

- Skill decay (Skills bundle): managers and other users who are not site administrators can open the
  decay pages their capabilities allow (*Manage skill decay rules*, *Skill decay queue*, *Manual skill
  decay*). They got an *Access denied* error, because the Skills section of Site administration is built
  for site administrators only. Skill decay 1.3 R2.

## 1.3 R2 (2026101401)

- Moodle 4.5: the Skills section of the user profile looks as on Moodle 5.x again. It used Bootstrap 5
  classes (accordion, gap, font weight and size) that Moodle 4.5's Bootstrap 4 does not have, so the skill
  panels showed as plain grey buttons without borders, spacing or collapse arrow. It now uses its own
  styles. A level without a colour shows as a grey badge (it was invisible on Moodle 5.x).
- Themes: the skill panels no longer have the Bootstrap accordion classes. Custom CSS that targets
  `.accordion-button` or `.accordion-body` in the Skills section should use `.toolskill-toggle` and
  `.toolskill-body`.

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
