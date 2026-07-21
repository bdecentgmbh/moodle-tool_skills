# Administrator guide

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
- [Privacy](#privacy)

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
their availability.

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
| **Skill colour** | A colour used to represent the skill (for example, the accent shown on the learner's *Skills earned* panel). Enter a hex value such as `#3b82f6`, or leave it empty. |
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

## Privacy

Skills implements the Moodle Privacy API. It stores, per user, the points earned for each skill and a
log of how those points were awarded (which course or activity). These records are exported and
deleted through Moodle's standard data-request tooling, scoped to the relevant course context.
