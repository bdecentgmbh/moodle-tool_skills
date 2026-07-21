# Skills — documentation

*Skills* (`tool_skills`) is a Moodle admin-tool plugin for defining skills, awarding points for them
through course (and activity) completion, and showing learners the skills they have earned.

Instead of Moodle's full competency framework — which is more than many organisations need — Skills
gives you a lightweight way to model achievement: define a skill, split it into up to 10 levels with
point thresholds, make it available site-wide or per course category, and let course completion award
or adjust a learner's points.

## What Skills does

- **Skills & levels** — create skills and give each one up to 10 levels, each with its own point
  threshold, colour and image.
- **Award points on completion** — link a skill to a course and choose what completing the course
  does: add points, or move/force the learner to a level.
- **Learner progress** — learners see a *Skills earned* panel on their profile with their current
  level, points earned, learning time and the courses that contribute.
- **Reports** — view every user's points for a skill.
- **Privacy** — full Privacy (GDPR) API support for the points and award-log data the plugin stores.

## How it fits together

1. An **administrator** (or **manager**) creates a skill, adds its levels, and makes it available
   globally or to specific course categories.
2. A **teacher** (or manager) opens a course, enables the skill, and chooses what course completion
   awards.
3. A **learner** completes the course, earns the configured points, and sees the skill, level and
   progress on their profile.

## Choose your guide

| You are a… | You want to… | Guide |
| --- | --- | --- |
| **Administrator** | Install the plugin and create/manage skills, levels and categories site-wide | [Administrator guide](admin-guide.md) |
| **Teacher / editing teacher** | Enable skills in your course and decide what completion awards | [Teacher guide](teacher-guide.md) |
| **Manager** | Do both of the above across the site, and view users' points reports | [Manager guide](manager-guide.md) |

## Capabilities at a glance

| Capability | Default roles | Lets you |
| --- | --- | --- |
| `tool/skills:manage` | Manager (and admin) | Create, edit, archive and delete skills and levels site-wide |
| `tool/skills:managecourseskillslist` | Teacher, editing teacher, manager | Enable and configure skills within a course |
| `tool/skills:viewotherspoints` | Manager | View the users-points report for a skill |

## Requirements & support

- Moodle 4.5 and later (tested through Moodle 5.1).
- Developed and tested on the Boost theme and Boost child themes (including Classic).

For bug reports and feature proposals, see the project on
[GitHub](https://github.com/bdecentgmbh/moodle-tool_skills).
