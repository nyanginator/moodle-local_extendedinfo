# Moodle - Extended Info
https://github.com/nyanginator/moodle-local_extendedinfo

Custom fields for [Moodle](https://moodle.org) categories, courses, and modules.

Table of Contents
=================
* [What This Plugin Does](#what-this-plugin-does)
* [Install](#install)
* [Usage](#usage)
  * [Admin Configuration](#admin-configuration)
  * [Extended Info Button](#extended-info-button)
  * [Adding Extended Info](#adding-extended-info)
  * [Custom Variables](#custom-variables)
  * [Retrieving Extended Info](#retrieving-extended-info)
* [Cron Task](#cron-task)
* [Uninstall](#uninstall)
* [Contact](#contact)

What This Plugin Does
=====================
This is a Moodle local plugin that allows you to add extra information about categories, courses, and modules by associating them with custom text fields that you define. The Moodle Cache is utilized for more efficiency.

Install
=======
Create the folder `local/extendedinfo` in your Moodle installation and copy the contents of this repository there. Login as the Moodle admin and proceed through the normal installation of this new plugin. If the plugin is not automatically found, you may have to go to Site Administration > Notifications.

Usage
=====

Admin Configuration
-------------------
You can access all Extended Info data by going to Site Administration > Plugins > Local Plugins > Extended Info. There are 5 links:

* View: Course Categories
  - View a table of available categories
* View: Courses
  - View a table of available courses
* View: Activity Modules
  - View a table of available activity modules
* Dashboard
  - Edit Extended Info for the main home page (i.e. `/my`).
* List Of Categories
  - Edit Extended Info for the categories index page (i.e. `/course/index.php`).

![Admin Configuration](https://raw.githubusercontent.com/nyanginator/moodle-local_extendedinfo/master/screenshots/admin-config.jpg)

In the table view, you will see names show up in bold and italic wherever Extended Info exists. For each row:

* Click the gear icon to edit the Extended Info fields of an instance.
* In the Categories view, click the table icon to view courses of that category.
* In the Courses view, click the table icon to view activity modules of that course.
* Click the trash icon to delete all Extended Info fields of an instance.
* Click a column header to sort the table by that column.
* Click a name to go to the category/course/module page.

![Table View](https://raw.githubusercontent.com/nyanginator/moodle-local_extendedinfo/master/screenshots/table-view.jpg)

Extended Info Button
--------------------
For convenience, you can add an Extended Info Button to your theme layout/template code so that you can jump directly to the Extended Info of any category, course, or module. Remember that you need to be logged in with administrator privileges to see the button.

Use this code to generate the button:
```php
require_once($CFG->dirroot . '/local/extendedinfo/lib.php');
$extendedinfo_btn = local_extendedinfo_settings_button();

echo $extendedinfo_btn; // Or include in your mustache template's context
```

You can add this button, for example, above your main content:

![Button](https://raw.githubusercontent.com/nyanginator/moodle-local_extendedinfo/master/screenshots/button.jpg)

Adding Extended Info
--------------------
You can access the form for adding/editing Extended Info either through the Site Administration interface, or the Extended Info Button shortcut. When adding or editing Extended Info fields, you can add/remove as many fields as you like. All fields are defined by a name and a value. To delete a field, tick its Delete checkbox and click Save Changes.

![Edit View](https://raw.githubusercontent.com/nyanginator/moodle-local_extendedinfo/master/screenshots/edit-view.jpg)

Custom Variables
----------------
There is a function called `subvars()` in `lib.php`. The purpose of this function is to automatically substitute predefined variables with a hardcoded value. By default, only `wwwroot` is defined.
```php
$subnames = [ 'wwwroot' ];

...

// Add more if-else cases here to define more variables
if ($var == 'wwwroot') {
    $subbed_var = $CFG->wwwroot;
}
```
Enclose the variable in double square brackets when you use it in an extended field's value (e.g. `[[wwwroot]]/articles/img/advanced_activity.jpg`).


Retrieving Extended Info
------------------------
To retrieve Extended Info using PHP, use the `local_extendedinfo_get()` function with `category`, `course`, or `module` as the first parameter and the ID as the second, which will return an associative array of Extended Info values indexed by names. For example, on a course page:
```php
require_once($CFG->dirroot . '/local/extendedinfo/lib.php');

global $PAGE;
$extinfo = local_extendedinfo_get('course', $PAGE->course->id);

if (isset($extinfo['bannerimage'])) {
    echo '<img src="' . $extinfo['bannerimage'] . '">';
}
```
 There is an optional third parameter for formatting the value fields (i.e. `FORMAT_MOODLE`, `FORMAT_HTML`, `FORMAT_PLAIN`, `FORMAT_WIKI`, `FORMAT_MARKDOWN`). The default is `FORMAT_HTML`. A value of -1 will return the raw unprocessed values.

Cron Task
=========
Make sure you periodically run the Moodle cron command. This will clear out any Extended Info fields of deleted categories, courses, and modules. Note that Extended Info will only be deleted after the associated categories, courses, or modules are actually deleted from the database, which may require their own separate cron run first.

Uninstall
=========
Uninstall by going to Site Administration > Plugins > Plugins Overview and using the Uninstall link for the `local/extendedinfo` plugin.

Contact
=======
Nicholas Yang\
https://nicky.pairsite.com
