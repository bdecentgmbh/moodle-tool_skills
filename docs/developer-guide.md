# Skills — Developer guide

This guide is for developers of skill addons and other plugins that extend Skills. It describes the
hooks, the points history and the events Skills provides. For using Skills, see the [Administrator guide](admin-guide.md),
[Teacher guide](teacher-guide.md) and [Manager guide](manager-guide.md).

## Contents

- [Pills in the profile Skills section](#pills-in-the-profile-skills-section)
- [Points history](#points-history)
- [Events](#events)
- [Behat steps](#behat-steps)

## Pills in the profile Skills section

The hook `\tool_skills\hook\extend_profile_skill` lets a plugin add pills (small badges) below a
skill's points in the **Skills** section of the user profile, for example the points removed by decay or
the user's target for the skill. It is dispatched once per skill while the section is built, in both the
detailed display and the *Skill totals only* display, and not at all when the section is disabled.

The hook carries:

- `userid` — the user whose profile is shown;
- `skill` — the `\tool_skills\skills` instance of the skill;
- `points` — the user's current points for the skill.

Add a pill with `add_pill(string $name, string $label, string $title = '', string $variant = 'secondary')`:

| Argument | Meaning |
| --- | --- |
| `name` | Identifier, letters, digits, `_` and `-` only, for example your component name. The pill gets the CSS class `toolskill-pill-<name>`; adding a pill with a name that is already used replaces it. |
| `label` | Plain text shown in the pill. Make it understandable on its own; the title adds the details. |
| `title` | Optional plain text shown in a popover when the pill is tapped, clicked or activated with the keyboard (the same mechanism as Moodle's help icons, so it works on touch devices). A pill without a title is a plain badge. |
| `variant` | Colour: `secondary`, `info`, `success`, `warning` or `danger`. |

`remove_pill($name)` removes a pill and `get_pills()` returns them in the order they were added. Labels
and titles are escaped when rendered, so pass plain text, not HTML.

Register the callback in your plugin's `db/hooks.php`:

```php
$callbacks = [
    [
        'hook' => \tool_skills\hook\extend_profile_skill::class,
        'callback' => [\skilladdon_example\hook_callbacks::class, 'extend_profile_skill'],
    ],
];
```

and implement it:

```php
namespace skilladdon_example;

class hook_callbacks {
    public static function extend_profile_skill(\tool_skills\hook\extend_profile_skill $hook): void {
        $removed = 12; // Look up what your plugin wants to show for $hook->userid and $hook->skill->get_id().
        if ($removed > 0) {
            $hook->add_pill('skilladdon_example', "−{$removed} points", 'Removed by the example rule', 'warning');
        }
    }
}
```

Purge the caches (or bump your plugin version) after adding or changing `db/hooks.php`. The hook is
listed under **Site administration > Development > Hooks overview**.

## Points history

Every change of a user's skill points is recorded in `tool_skills_points_history`: the change, the points
and levels before and after, the reason, the source and the time. The award logs stay as they were (the
current points per source); the history is what reports over a period use. The Reports addon reads it
through its **Skill points history** report source.

All changes go through `\tool_skills\skills::set_userskill_points()`, which records them. Pass a
`\tool_skills\points_change` to say why and through which source the points change:

```php
use tool_skills\points_change;
use tool_skills\points_history;

$change = new points_change(
    points_history::REASON_DECAY, // Reason.
    'decay',                      // Source type (method).
    $rule->get_id(),              // ID of the source in the table of its method.
    null,                         // Course of the source.
    null,                         // Course module of the source.
    $rule->get_label_text(),      // Name of the source at the time, unformatted.
    $USER->id                     // User who made a manual change, else null.
);
\tool_skills\skills::get($skillid)->set_userskill_points($userid, $newpoints, $change);
```

| Reason | Meaning | Counted as |
| --- | --- | --- |
| `award` | Points given by a source (course or activity completion, grade) | Earned |
| `revoke` | A source's earlier points taken back, for example when its points or the grade changed | Earned |
| `decay` | Points removed by decay | Decay |
| `import` | Imported from the award logs when the history started; without a method it is the correction of the import | Earned |
| `other` | A change without a `points_change` (the default) | Net only |

Allocation methods build the change themselves: `allocation_method::get_points_change($reason)` uses the
method name and the current skill instance, and `get_points_source()` (override it) adds the course,
course module and name of the source. `skills::increase_points()`, `moveto_level()` and `force_level()`
pass `award`. Nothing is recorded when the points do not change.

When the history starts (upgrade to Skills 1.4), an ad-hoc task imports what is known from before: each
award log row with its date, plus the rows addons return from a `get_points_history_import(int $skillid,
int $historystart): array` method of their `manage_skills` class (Decay returns its decay log). Addons
that own an allocation method also implement `get_points_history_source(string $method, int $methodid):
?array` to name the source of their award logs. Each user's rows are replayed by date; where the result
differs from the user's points when the history started, one correction row is added, dated then.
Imported rows fire no events.

## Events

| Event | Fired when | Context and object |
| --- | --- | --- |
| `\tool_skills\event\skill_points_changed` | A row is added to the points history | Course of the source (system when there is none); object: the history row; related user: the user; `other`: skillid, points, oldpoints, newpoints, reason, method, methodid |
| `\tool_skills\event\skill_level_reached` | A change raises the user's level | As above; object: the level reached; `other`: skillid, previouslevelid, points, historyid |
| `\tool_skills\event\skill_created` | A skill is created | System; object: the skill |
| `\tool_skills\event\skill_updated` | A skill, its levels or its status are saved | System; object: the skill |
| `\tool_skills\event\skill_archived` | A skill is archived | System; object: the skill |
| `\tool_skills\event\skill_restored` | A skill is restored from the archive | System; object: the skill |
| `\tool_skills\event\skill_deleted` | A skill is deleted | System; object: the skill; `other`: name |

Observe them in `db/events.php` like any Moodle event, for example to notify a learner who reached a
level.

## Behat steps

Skills provides the step `Given the user "<username>" has "<points>" points for the skill "<key>"`, which
sets a user's points for the skill with the given key directly (without an award log entry). Addons use
this step instead of defining their own, so their Behat contexts do not clash when several addons are
installed.
