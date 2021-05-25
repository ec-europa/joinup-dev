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
   branch that was created in step 1 above.
1. Enter the changelog in the release description field, with headings for `New
   features`, `Improvements`, `Bug fixes` and `Security`. Make sure to choose
   the option *Save draft*!
1. Build the release using the following Jenkins job:
   https://jenkins.fpfis.eu/job/Joinup/job/acceptance/job/build-rpm-acc/
   Make sure to enter the name of the release branch as the `RELEASE_TAG`. We
   are actually only creating the tag _after_ the release is accepted.
1. Deploy the release to the acceptance environment using the Jenkins job:
   https://jenkins.fpfis.eu/job/Joinup/job/acceptance/job/Build-acceptance/
1. Move the release ticket in UAT.
1. If any last minute problems are discovered during acceptance testing, these
   will be fixed in pull requests that are merged directly into the release
   branch.
1. After receiving approval from the functional team, merge the release branch
   into master, then publish the new release on GitHub. This will automatically
   create the tag. The release branch can then be deleted.
1. In the Joinup project page in Jira, click the "Releases" icon in the left
   sidebar. Find the release in the table, click the three-dot 'Actions' menu
   and choose 'Release'. Set the correct date for the release.
1. Merge back master into develop.
1. Create a followup PR against develop that cleans up the update and deploy
   scripts, and a corresponding Jira ticket, and move this in QA.
1. After the PR is merged and the release is deployed, move the release ticket
   from 'Accepted' to 'Resolved'.
