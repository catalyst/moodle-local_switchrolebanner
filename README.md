Moodle Switch role banner
===================

[![Codechecker CI](https://github.com/catalyst/moodle-local_switchrolebanner/actions/workflows/ci.yml/badge.svg?branch=MOODLE_39_STABLE&label=ci)](https://github.com/catalyst/moodle-local_switchrolebanner/actions/workflows/ci.yml)

Information
-----------

This Moodle plugin displays a banner above course pages to users who have site roles that allow them to view courses and have roles in that course or can self enrol.

### Types of banners and their conditions:

- When the user has no roles but can self enrol in the course:
    - The banner will notify the user that they can self enrol and will provide a self enrol button that takes to the /enrol/index.php page for that course.
- When the user has roles in the course and is viewing the course with their regular role:
    - The banner will notify the user that their are viewing this course with their site/course category role and can switch to view as one of their enrolled roles. This will also provide quick buttons to switch to the role(s) they have in the course (this also takes into consideration the roles they are permitted to switch to).
- When the user is switched to a role that they have in the course:
    - The banner will notify the user that are viewing the course with that role and that they can switch back to their regular role. A quick button to switch back to their regular role will be provided.

### Extra information:

- At any time the user can click on the "Don't show this again for this session" checkbox to hide the banner for the course for the remainder of their session.
- This plugin remembers the last role the user switched to in each course (only applies to where banners would show) and will automatically switch them to that role on their next session.

Branches
-----------

| Moodle version   | Branch                                                                                              | PHP  |
|------------------|-----------------------------------------------------------------------------------------------------|------|
| Moodle 3.9 +     | [MOODLE_39_STABLE](https://github.com/catalyst/moodle-local_switchrolebanner/tree/MOODLE_39_STABLE) | 7.4+ |
