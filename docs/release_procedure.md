Release procedure
=================

1. Create a release branch based on the current master, merge the latest develop
   into it, and push it to GitHub.
   ```
   $ git fetch
   $ git checkout master
   $ git reset --hard origin/master
   $ git checkout -b release-1.23.4
   $ git merge origin/develop
   $ git push origin release-1.23.4 -u
   ```
1. Check all resolved tickets from the previous sprint(s) and verify that they
   have a version set in the "Fix Version" field. Some tickets might be resolved
   but are not part of any Joinup release (for example: analysis/investigations,
   work done upstream, infrastructure work etc.). These should get "NOVERSION".
   The tickets can be listed by browsing all tickets in Jira and using the query
   `project = ISAICP AND Sprint = "Joinup sprint N" AND status = Resolved`.
1. List all tickets in Jira that have the "Fix Version" field set to the
   upcoming release, using the query `project = ISAICP AND fixVersion = x.y.z`.
   These tickets will be used to create the changelog in the next step.
1. Create [a new draft
   release](https://github.com/ec-europa/joinup-dev/releases/new). Enter the tag
   version in the format `v1.23.4` and set the target branch to the new release
   branch that was created in step 1 above. Enter the changelog in the release
   description field, with headings for `New features`, `Improvements`, `Bug
   fixes` and `Security`. Make sure to choose the option *Save draft*!

