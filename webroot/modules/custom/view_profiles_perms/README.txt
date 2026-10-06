This module provides permissions to view user profiles depending on what
roles the profile requested to be viewed has.

INSTRUCTIONS:
Simply enable the module as usual, and go to admin/people/permissions to
configure the permissions under the 'View profiles permissions' section.
Note that core's 'View user information' permission still applies,
and will override any permission not set by this module.

USE CASE EXAMPLE:
If you want all users to only be able to access profiles of users with
the role 'blogger', uncheck 'View user information' and check
'Access Blogger users profiles' for anonymous and authenticated users.
