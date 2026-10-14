# Skills — Administrator guide

This guide covers installing Skills and managing skills, levels and categories site-wide. It applies
to **administrators** and to anyone with the **`tool/skills:manage`** capability (granted to the
**manager** role by default). For assigning skills inside a course, see the
[Teacher guide](teacher-guide.md); for the manager's cross-cutting view, see the
[Manager guide](manager-guide.md).

## Contents

- [Installation](#installation)
- [Where to find Skills](#where-to-find-skills)
- [Creating a skill](#creating-a-skill)
- [Adding levels](#adding-levels)
- [Managing existing skills](#managing-existing-skills)
- [Availability and course categories](#availability-and-course-categories)
- [Skills on the user profile](#skills-on-the-user-profile)
- [Points history](#points-history)
- [Privacy](#privacy)
- [Addons](#addons)

## Installation

Install Skills like any other Moodle plugin, either through the plugin installer or manually.

**Using the plugin installer:**

1. Download the *Skills* plugin from the [Moodle plugins directory](https://moodle.org/plugins/tool_skills)
   or from the bdecent website.
2. Sign in to your Moodle site as an administrator.
3. Go to **Site administration > Plugins > Install plugins**.
4. Upload the plugin ZIP file and follow the prompts.

**Installing manually:**

1. Download and unzip the *Skills* plugin.
2. Copy the `skills` folder into `admin/tool/` on your Moodle server (so it lives at
   `admin/tool/skills`).
3. Sign in as an administrator and go to **Site administration > Notifications** to complete the
   upgrade.

## Where to find Skills

After installation, manage skills under **Site administration > Plugins > Admin tools > Skills**
(the page is titled **Manage skills**). This is where you create skills, add their levels, and set
their availability. Teachers reach the course-level configuration through **Skills** in a course's
secondary navigation (see the [Teacher guide](teacher-guide.md)).

The **Manage skills** page has two tabs:

- **Active skills** — skills that are in use. These can be enabled in courses and can award points to
  learners.
- **Archived skills** — skills that have been taken out of use. Archived skills do not appear in the
  course skills list and are not awarded to learners.

Use the **Filter** control to narrow the list to skills in particular course categories.

## Creating a skill

Select **Create skill** on the **Active skills** tab and complete the form.

| Setting | Description |
| --- | --- |
| **Skill name** | The display name of the skill. |
| **Key** | A unique identifier for the skill. Keep it distinct so skills stay easy to tell apart in the system. |
| **Description** | A longer explanation of the skill, for administrative reference. |
| **Status** | **Enabled** — the skill is added to all courses that match its category setting and can be configured by teachers. **Disabled** — the skill is not added to any course and cannot be used. |
| **Learning time** | The time expected to complete this skill within a course. It is shown to learners on their profile. |
| **Skill colour** | A colour used to represent the skill (for example, the accent shown in the *Skills* section of the learner's profile). Enter a hex value such as `#3b82f6`, or leave it empty. |
| **Available in course categories** | Restrict the skill to courses in the selected categories. Leave empty to make the skill available in **every** category. See [Availability](#availability-and-course-categories). |

## Adding levels

A skill can define up to **10 levels**, each representing a milestone with a point threshold. Set the
**Number of levels** on the skill form, then configure each level:

| Setting | Description |
| --- | --- |
| **Level name** | The name shown for this level (for example, *Beginner*, *Advanced*). |
| **Level point** | The number of points required to reach this level. |
| **Level colour** | A colour for this level's badge. It overrides the general skill colour for this level. Enter a hex value or leave empty. |
| **Level image** | An image representing the level, shown next to the skill on the learner's profile. |

Levels give learners clear milestones: as their points cross each threshold, their **current level**
advances. The highest level's point value is the total needed to fully earn the skill.

> **Note on colours:** skill and level colours must be a hex value (`#rgb` or `#rrggbb`) or empty.
> This is validated when you save, so the values are safe to display.

## Managing existing skills

On the **Active skills** tab, each row shows the skill's key, name, description, creation time and
course categories, with these actions:

- **Edit** — change the skill's settings and levels.
- **Status** — toggle the skill enabled or disabled.
- **Archive** — move the skill to the **Archived skills** tab (it stops being offered in courses and
  awarded to learners).

On the **Archived skills** tab:

- **Activate** — return the skill to the **Active skills** tab.
- **Delete** — permanently remove the skill and its levels.

## Availability and course categories

The **Available in course categories** setting controls where a skill can be used:

- **No categories selected** → the skill is available globally, in every course.
- **One or more categories selected** → the skill is only offered in courses that belong to those
  categories.

This restriction is enforced both when the skill list is shown in a course **and** when a skill is
enabled on a course, so a skill can never be attached to a course outside its allowed categories.

## Skills on the user profile

Learners see their skills in the **Skills** section of their own profile. Choose how much it shows under
**Site administration > Plugins > Admin tools > Skills > General settings**, setting **Skills on the user
profile**:

| Option | The Skills section shows |
| --- | --- |
| **Skills with courses and activities** (default) | Each skill with its current level, points and learning time, expandable to the courses that award it with the points earned there, and, with the Activity skills addon, the activities. |
| **Skill totals only** | Each skill with its current level, points and learning time, without the course and activity rows. |
| **Disabled** | Nothing: the profile has no Skills section, also not the **Manage skills** link for managers. |

Addons can add small badges ("pills") below a skill's points in both displays, for example the points
removed by decay. Tapping or clicking a pill shows its details.

## Points history

Skills records every change of a learner's points: when it happened, why (an award, points taken back
when a course's points or a grade changed, decay), through which course, activity or decay rule, and the
level before and after. Reports over any period use this history; with the Reports addon, build them on
the **Skill points history** report source. Changes also appear in Moodle's logs as the events *Skill
points changed* and *Skill level reached*, next to *Skill created*, *updated*, *archived*, *restored* and
*deleted*.

**Points from before the update to Skills 1.4** are imported once, in the background (cron must run).
Before 1.4 Skills kept only the latest award per course or activity, so earlier periods can be
approximate: a course completed twice counts once, on the later date. Where the imported changes do not
add up to a learner's points at the time of the update, one correction row (reason *Import correction*)
is added, dated when the history started. From the update on, every change is recorded exactly.

## Privacy

Skills implements the Moodle Privacy API. It stores, per user, the points earned for each skill, a
log of how those points were awarded (which course or activity), and the points history. The points and
award logs are exported and deleted in the relevant course context; the points history in the user's
own context (and with the course's data when a course context is deleted). A manager who made a
manual change, such as a manual decay, is exported in the system context and removed from the history
when their data is deleted.

## Addons

Skill addons extend the plugin and are installed under `admin/tool/skills/addon/<name>`. Their
settings pages appear next to **Manage skills** under **Site administration > Plugins > Admin tools >
Skills**, and each addon ships its own guides in its `docs/` folder:

- **Activity skills** — award points, levels or grade-based points when an activity is completed.
- **Reports** — a *Skills* datasource for Report builder.
- **Level visuals** and **Progress** — Dash widgets for learners' dashboards.
- **Decay** — rules that reduce points over time, with a planned queue, manual decay and an audit log.
- **Targets** — level or points targets per cohort, with fixed or relative deadlines and recorded achievements.

When upgrading from Skills Pro 1.1, the addons that were bundled in the Pro package are now separate
plugins in the same folder. Replacing the whole `admin/tool/skills` folder with the Skills bundle and
running the upgrade migrates everything; two addons were renamed (`skill_levelvisuals` to
`levelvisuals`, `skillprogress` to `progress`) and their settings and Dash blocks are carried over
automatically.
