User TeamRoles: Parent/Child synched between Joomla Groups and Moodle Mentees via Joomdle
=========================================================================================

The aim of the plugin is to allow your users to be organised in Joomla into a hierarchy of teams with designated team leaders, and for those teams and team leader roles to be reflected in Moodle via its parent/child (aka mentor/mentee) relationships.

Unlike other similar plugins that work with Joomdle, it does not rely on any other Joomla social plugins; it simply uses Joomla's standard user group and access levels functionality.


Dependencies:
-------------

* Joomla (http://joomla.org/)
* Moodle (http://moodle.org/)
* Joomdle (http://www.joomdle.com) (>v1.0.6; see note below)


Version History:
----------------

* v1.3.0  Added visibility of the team roles in Joomla on the User Profile form, including ability to hide users in the team
* v1.2.0  Internal release.
* v1.1.0  Fixed bugs with syncing new users. But also note the caveat below about plugin ordering.
* v1.0.0  Initial release.


Notes:
------

* NB: This plugin uses an API method (remove_parent_role) that was only added to Joomdle v1.0.6. You should ensure that your Joomdle version is 1.0.6 or higher. In any case, it is of course always recommended to use the latest version.

* As far as Joomla is concerned, this is a User plugin not a Joomdle plugin. This is because hooks into the events triggered by Joomla's User component. It does not hook into any Joomdle events. Instead it uses Joomdle's API to make direct calls to the linked Moodle system. It therefore requires Joomdle, but will be listed in Joomla as a User plugin.


Installation:
-------------

1. Install Joomla, Moodle, and Joomdle.
2. In Moodle, create a parent role with appropriate permissions (see https://docs.moodle.org/30/en/Parent_role for instructions), and define it as the parent in the Joomdle config (go to Site Admin/Plugins/Auth/Joomdle, and populate the "Parent Role ID" field with the ID of the role you just created).
3. In Joomla, create a user group structure: One group for the parents, and another group with multiple nested groups for the teams. See below for more detail on this.
4. In Joomla, Create an access level for the parents; link it to the parents group you created above.
5. Install the plugin to Joomla using whatever method is most convenient.
6. Configure plugin: Find the plugin config page in Extensions/Plugins. Once there, set the "Access for 'Parent' role" to the access level created in step 3, and the "Top-level group for sync" to the parent group containing all the team groups. Activate the plugin by setting the status to Enabled, and press save.

You should now be able to create Joomla users in your team groups and in the parent group, and the plugin will automatically update the Moodle parent/child relationships for those users. You can then use Moodle features and plugins (like the built-in My Mentees block, or the ones listed here: https://moodle.org/plugins/search.php?s=mentee&search=Search+plugins) to allow the parents to see management details about their children.


Tip: We have used this in conjunction with the [Multi Usergroup Registration plugin](http://extensions.joomla.org/extensions/extension/clients-a-communities/user-management/multi-usergroup-registration) which means we can allow end users to specify which team they belong to when they create their account.


Group Structure and Access Levels:
----------------------------------

The plugin is designed to work with a group structure that looks like this in Joomla:

    - Team Leaders
    - Teams
      |- Team 1
      |- Team 2
      |- Team 3
      |- etc...

You would also define a Joomla Access Level for Team Leader, which should be granted to the Team Leaders group.

The plugin config should be set with the "Teams" group as the "top-level group", and the "Team Leader" access level as the access level for the parent role.

With the groups and the access level in place, and the plugin configured, you are now ready to start organising your users.

Your users should be allocated into their group within Teams (in our case, we are using the Joomla Multi-usergroup Registration plugin (http://extensions.joomla.org/extensions/extension/clients-a-communities/user-management/multi-usergroup-registration) to allow users to select their own group, but you can add users to groups in whatever way suits your use-case). Team leaders would be members of their group in the same way as other users, but would additionally be added to the Team Leaders group. This would then give them the team leader access level, which would in turn trigger Joomdle to give them parent role over the rest of the team in Moodle.


User Profile Management In Joomla
---------------------------------

As of version 1.3, the plugin has additional features to allow the team list to be managed in Joomla, via the user profile page.

If you view the user profile form (either in the admin panel or front-end) for a user with team leader role, you will see that there is an extra tab on the form, with the heading "Team Role".

This tab contains headings for each team group that the user is a member of, along with a list of the users in that team.

In addition to simply seeing the user list, this form also allows a limited amount of management. Each user name in this list has a button next to them which can be toggled on or off. Clicking to turn it off will remove the user from the team list for this team leader. This will then be reflected by that user no longer appearing in the Moodle Mentees list for the team leader. This may be useful for team leaders with a large number of team members, but where they are only interested in a few of them from the perspective of the Moodle mentee relationship.

At the bottom of the form, below the team user lists, is an additional button labelled "Resync Team Members". This button allows an admin user (or the team leader themselves) to manually re-sync the Moodle mentees list with the team in Joomla. A bug in an earlier version of the plugin resulted in some teams getting out of sync, and while this bug has been resolved, there may still be teams that are affected. There may also be other bugs in the future, or data corruption that may make this button useful.


Caveats and Limitations:
------------------------

The plugin works well for the use-case for which it was designed. However, if you intend to implement it for your site, you may need to note the following:

* No nested teams: The plugin code looks at the list of groups that are immediate child groups of the top-level group. It does not recurse any further down the group tree. This means that the plugin does not support any kind of nested group structure within the team groups.

* Multiple groups: You may want to set users and team leaders to be members of multiple teams. This is possible, but has limitations and has not been properly tested. The most obvious limitation is that there's only one team leader flag, so you can't be a leader of one group without being a leader of all other groups that you belong to. Also, since the group structure does not carry over to Moodle; just the parent-child relationships between team leaders and other users, a team leader who is in multiple groups will see all the users he is responsible for in his Mentees list, but nothing to tell him which groups they belong to.

* Large number of teams: There are reports on various forums that Joomla has been known to have performance issues when it has a large number of groups. Please bear this in mind if you are likely to need a lot of groups. It is in use on a system with around 250 teams with no performance issues.

* This plugin has only been tested against the current versions of Joomla (3.6.5), Moodle (3.0.2+) and Joomdle (1.0.8) as at the time of writing. Provided that Joomla's plugin API remains the same and that Joomdle itself works, there is no reason why it shouldn't work in other versions, but if you are using other versions, please be aware that it has not been tested, and respond accordingly.

* Be sure that your Joomdle version includes the remove_parent_role method. See notes section above.

* The plugin must be executed after the main Joomdle user hooks plugin. Use Joomla's plugin ordering to set this. Failure to do this will result in new users not being synced as they won't exist yet in Moodle when the plugin tries to sync them.

* The plugin is not translated or translatable. This may be added in the future if there is any demand.

* Marking users as removed from the team on the user profile page only affects their Moodle Mentee status with the team leader. It does not remove their access rights to Moodle, nor does it change their Joomla group membership, or change their status in any other way.
