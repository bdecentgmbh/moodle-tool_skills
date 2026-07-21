# Manager guide

Managers have the widest view of Skills. By default the **manager** role holds all three Skills
capabilities, so a manager can do everything an administrator does with skills (short of installing
the plugin), everything a teacher does in a course, **and** view how users are progressing.

This guide orients you to that combined role and documents the manager-only **users-points report**.
For step-by-step detail, follow the links to the other guides.

## What you can do

| Task | Capability | Where | Guide |
| --- | --- | --- | --- |
| Create, edit, archive and delete skills and levels site-wide | `tool/skills:manage` | Site administration > Plugins > Admin tools > Skills | [Administrator guide](admin-guide.md) |
| Enable skills in a course and configure completion awards | `tool/skills:managecourseskillslist` | A course > **Manage skills** | [Teacher guide](teacher-guide.md) |
| View every user's points for a skill | `tool/skills:viewotherspoints` | Users-points report (below) | this guide |

Because you hold `tool/skills:managecourseskillslist` in every course, you can manage the skills of
any course, not only ones you teach — useful for setting up skills consistently across a programme.

## Managing skills site-wide

Under **Site administration > Plugins > Admin tools > Skills** you have the same **Manage skills**
interface as an administrator: the **Active** and **Archived** tabs, the category **Filter**, and the
create/edit/archive/activate/delete actions. See the [Administrator guide](admin-guide.md) for the
full description of each skill and level setting (name, key, status, learning time, colours, level
points, level image, and course-category availability).

You do **not** need to be a full site administrator to do any of this — the `tool/skills:manage`
capability is enough, and it is granted to managers by default.

## Assigning skills in courses

In any course, open **Manage skills** from the secondary navigation to enable skills and choose what
course completion awards (Points, Set level or Force level). The [Teacher guide](teacher-guide.md)
describes each option in detail.

## Users-points report

The users-points report lets you see how learners are doing with a given skill.

- Open a learner's profile and, under **Skills earned**, select **View users points for this skill**
  on the relevant skill; or open the report directly for a skill from the skills management pages.
- The report lists each user together with the **points** they have earned for that skill.

Access to the report requires the **`tool/skills:viewotherspoints`** capability, which managers hold
by default. Only the user's name and their points are shown — no other personal data.

## Privacy

The points and award-log data behind these reports is covered by Moodle's Privacy API and is exported
and erased through the standard data-request tooling. See the [Administrator guide](admin-guide.md#privacy)
for details.
