# Changes

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
