# Skills - Monitoring skills and learning time

## Overview

The "Skills" feature is designed to enhance learner engagement within Moodle. As a tool-type plugin, Skills facilitates the awarding of skills based on points. Learners can acquire skills by completing linked courses or activities, fostering a sense of achievement and progress.

The "Skills" feature allows administrators and teachers to configure up to 10 levels for each skill. Each level corresponds to a specific set of points, creating a structured progression system. This system enhances the granularity of skill development and provides learners with clear milestones to achieve.

This documentation provides an in-depth guide to the various aspects and functionalities of the Skills feature.

## Installation and initial setup

### Installation

You can install the Pulse plugin using the Moodle plugin installer. Here are the steps to follow:

1. Download the "Skills" plugin from the Moodle plugins repository or from the bdecent website.
2. Log in to your Moodle site as an administrator.
3. Go to "Site administration > Plugins > Install plugins".
4. Upload the downloaded plugin ZIP file.
5. Follow the prompts to install the plugin.
6. Once the admin tool plugin is installed, you can manage it by going to Site Administration > Plugins > Admin Tools > Skills. From there, you can set up the skills and levels system, assign to courses and modules.

Alternatively, you can also install the Skills plugin manually. Here are the steps to follow:

1. Download the "Skills" plugin from the Moodle plugins repository
2. Unzip the downloaded file.
3. Upload the skills folder to the moodle/admin/tool directory on your Moodle server.
4. Log in to your Moodle site as an administrator.
5. Go to "Site administration > Notifications".
6. Follow the prompts to install the plugin.

## Key Features:

1. **Skill Creation and Management**: Create, edit, and delete skills seamlessly.

2. **Add Levels**: Create multiple levels with specific set of points for each skill.

3. **User Points System**: Allow users to earn points for mastering skills and track their progress.

4. **Logs and Reports**: Access detailed logs of user points and generate reports on skill usage.

5. **Privacy Controls**: Configure privacy settings to handle user consent and data protection.

6. **Integration with Courses**: Link skills to courses, providing a holistic view of a user's achievements.


## How it works.

Admins initiate the process by creating a skill and establishing levels with assigned points for the skill. The skill is then linked to a course through the "Manage Skills" page. Teachers wield multiple options for awarding points:

1. **Points**: Assign a specific number of points to the user.

2. **Force Levels**: Compel the user's points to align with a designated level (potentially decreasing their current points).

3. **Set Levels**: Adjust the user's points to match a specified level, provided the user has sufficient points.

Upon course completion, users earn points and acquire skills based on the established setup.


# Manage skills:

   Manage skills to create a new skill and edit existing skills.


2. **Filter:** The "Filter" option is used to filter the list of skills within the category lists.

### Active Skills:

List of skills currently active and user can earn skills and teachers can use it in there courses.

The "Active Skills" tab displays a full list of created skills or the filtered skills within the categories.

1. **Key:**

   Each skill should be uniquely identified by a distinct key to maintain clarity and organization in the system.

2. **Skill Name:**

   The designation 'Skill Name' serves as the unique identifier for each individual skill.

3. **Description:**

   The "Description" refers to a detailed explanation or information provided about various skills.

4. **Time created:**

   Time Created" indicates the record of when specific skills were established.

5. **Course Categories:**

   It displays the list of categories added for specific skills.

6. **Actions:**

   1. ***Edit settings:*** Click the 'Edit' icon in the table to make changes to a specific skill.
   2. ***Status:*** Use this toggle icon in the table to enable or disable the status of the specific skill.
   3. **Archive:** Click the "Archive" option in the table to Archive the specific skill.

### ***Archived Skills:***

Archived skills are not available in course list, and not awared to students.

The "Archived Skills" tab displays a list of archived skills or the filtered skills within the categories.

**Actions:**

   1. ***Delete:*** Click the 'delete' option to remove a specific skill.
   2. ***Active:*** Click the 'active' option to move a specific skill to the "Active" tab.


## General Configuration

Use the "**Create Skill**" button to create a new skills. Skills comes with following configurations.

These configurations establish the rules and standards for assessing, tracking, and awarding skills.

1. **Skill Name:**

   The designation 'Skill Name' serves as the unique identifier for each individual skill.

2. **Key:**

   Each skill should be uniquely identified by a distinct key to maintain clarity and organization in the system.

3. **Description:**

   The term "Skills Description" refers to a detailed explanation or information provided about various skills.

4. **Status:**

   Choose the status for this skill:

   ***Enabled:*** The skill will be added to all courses that match the course categories setting below and can be configured by teachers.

   ***Disabled:*** The skill will not be added to any courses and cannot be used by teachers.

5. **Learning time:**

    The time required to complete this skill within the course.

6. **Skill color:**

    Choose a color to represent the skill level.

7. **Available in course categories:**

    Select the categories to make this skill available exclusively to courses within the chosen category. If no category is selected, the course will be available globally across all categories.

## Levels - General settings

1. **Number of levels**

Select the number of levels available for this skill. Each level have a specific point requirement for achievement and other following configurations.

2. **Level Name**

Provide the level name for the specific skill.

3. **Level Point**

Please specify the point value for the specific skill level.

4. **Level Color**

Select a color to represent the level. This will override the general skill color for visualization purposes.

5. **Level Image**

Please upload an image that represents the level of skill. This will be used for visualization.


# Course settings

To access the skills list and assign them to a course, utilize the "Manage Skills" link found in the secondary navigation of the course. Within this interface, you have the option to grant a precise number of points or set the points required to reach a specific skill level.

Simply employ the "Edit" icon in the table to activate the skill for the course and configure the settings for "Upon Course Completion" and "Points."

1. **Status:**

   Choose the status for this skill:

   ***Enabled:*** The skill will be added to the course that match the upon completion setting below and can be configured by teachers.

   ***Disabled:*** The skill will not be added to any courses and cannot be used by teachers.

2. **Upon course completion:**

   Upon course completion, you can choose from several options to determine what should happen at the end of the course.

   ***Nothing:*** Choose 'Nothing' to use activity completion, instead of course completion, for awarding points.

   ***Add points:*** Select 'Add points' to have the specified number of skill points added upon course completion. Please note that using negative numbers will result in a deduction of points.

   ***Set level:*** Choose 'Set level' to have the completion of the course add the necessary number of points required to reach that level, unless the student already has more points.

   ***Force level:*** Select 'Force level' to set the number of points to the amount required for that level upon course completion, regardless of the student's previous level/points. This may result in students having fewer points than before.

3. **Points:**

   Enter the number of skill points to be awarded or deducted. Use a positive number to add points and a negative number to deduct points.

   ***Example:***
   Entering "50" will add 50 points.
   Entering "-20" will deduct 20 points.
