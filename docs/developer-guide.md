# Skills — Developer guide

This guide is for developers of skill addons and other plugins that extend Skills. It describes the
hooks Skills provides. For using Skills, see the [Administrator guide](admin-guide.md),
[Teacher guide](teacher-guide.md) and [Manager guide](manager-guide.md).

## Contents

- [Pills in the profile Skills section](#pills-in-the-profile-skills-section)
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

## Behat steps

Skills provides the step `Given the user "<username>" has "<points>" points for the skill "<key>"`, which
sets a user's points for the skill with the given key directly (without an award log entry). Addons use
this step instead of defining their own, so their Behat contexts do not clash when several addons are
installed.
