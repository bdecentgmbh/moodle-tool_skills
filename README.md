# Skills

Moodle admin tool plugin to manage skills

# Requirements

This plugin requires Moodle 4.x

# Motivation for this plugin

We believe that Moodle's competency system is too complex for many organisations. At the same time, just using course completions and badges is often not enough. With this plugin, organisations have a simple tool at their disposal to can manage skills across their moodle site.

# Installation

Install the plugin like any other plugin to folder /admin/tool/skills
See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins

# Quick start guide

Admins or users with the capability tool/skills:manage (by default given to managers) need to create skills under Site Administration > Plugins > Tools > Skills and make them available either globally or for specific course categories.

Teachers (or users with the capability tool/skills:managecourseskillslist) can then manage skills in their course from the "Manage skills" page which is found in the secondary navigation of the course. From there, teachers can then enable specific skills and configure how many points students earn upon completion of the course.

# Documentation

Please refer to the documentation for more information: https://github.com/bdecentgmbh/moodle-tool_skills/wiki

# Theme support

This plugin is developed and tested on Moodle Core's Boost theme. It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.

# Plugin repositories

This plugin will be published and regularly updated in the Moodle plugins repository: https://moodle.org/plugins/tool_skills
The latest development version can be found on Github: https://github.com/bdecentgmbh/moodle-tools_skills

# Bug and problem reports / Support requests

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear. Please report bugs and problems on Github: https://github.com/bdecentgmbh/moodle-tool_skills/issues We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.

# Feature proposals

Please issue feature proposals on Github: https://github.com/bdecentgmbh/moodle-tool_skills/issues Please create pull requests on Github: https://github.com/bdecentgmbh/moodle-tool_skills/pulls We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature proposals and not as feature requests.

# Moodle release support

This plugin is maintained for the two most recent major releases of Moodle as well as the most recent LTS release of Moodle. If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.

# Translating this plugin

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

# Copyright

bdecent gmbh
bdecent.de
