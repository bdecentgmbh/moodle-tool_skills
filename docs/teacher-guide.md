# Teacher guide

This guide covers enabling skills in your course and choosing what happens when learners complete it.
It applies to **teachers** and **editing teachers**, and to anyone with the
**`tool/skills:managecourseskillslist`** capability (which also includes **managers**).

Skills themselves are created site-wide by an administrator or manager (see the
[Administrator guide](admin-guide.md)). As a teacher, you decide **which** of the available skills
your course awards and **how**.

## Contents

- [Opening the course skills page](#opening-the-course-skills-page)
- [Enabling a skill for your course](#enabling-a-skill-for-your-course)
- [What course completion awards](#what-course-completion-awards)
- [Points](#points)
- [Levels](#levels)
- [What learners see](#what-learners-see)

## Opening the course skills page

In your course, open **Manage skills** from the course's secondary navigation. This lists the skills
that are available to your course — that is, skills that are enabled site-wide and either global or
allowed in your course's category.

Each skill row has an **Edit** action; use it to enable the skill and configure how it is awarded.

## Enabling a skill for your course

Select **Edit** on a skill and set:

- **Status** — **Enabled** activates the skill for this course so learners can earn it; **Disabled**
  leaves it off.
- **Upon course completion** — what completing the course does (see below).

Only skills that are available to your course's category can be enabled here.

## What course completion awards

The **Upon course completion** option determines what happens when a learner completes the course:

| Option | Effect |
| --- | --- |
| **Nothing** | The course does not award skill points on completion. Use this when points should come from **activity** completion instead of course completion. |
| **Points** | Adds a fixed number of skill **points** on completion. A negative number **deducts** points. |
| **Set level** | Adds just enough points to reach the chosen **level** — unless the learner already has more, in which case nothing changes. |
| **Force level** | Sets the learner's points to exactly the chosen level's value, **regardless** of their current points. This can reduce a learner's points. |

### Points

When **Upon course completion** is **Points**, enter the number of points to award in the **Points**
field:

- A **positive** number adds points — for example, `50` adds 50 points.
- A **negative** number deducts points — for example, `-20` removes 20 points.

### Levels

When **Upon course completion** is **Set level** or **Force level**, choose the target **Level** from
the skill's levels. On completion the learner receives the points associated with that level:

- **Set level** only raises a learner towards the level (never lowers them).
- **Force level** sets them exactly to the level's points, up or down.

The available levels come from the skill's own configuration, so only that skill's levels can be
selected.

## What learners see

Learners see their skills on their **profile**, under **Skills earned**. Each skill is shown as an
expandable panel with:

- the skill's **current level** (name, colour and image) and its **learning time**;
- the **points earned** out of the total needed to fully earn the skill;
- the **courses** contributing to the skill, each with the points earned there.

Points are awarded when the learner meets the completion condition you configured — so a learner who
completes your course sees their points and level update on their profile.
