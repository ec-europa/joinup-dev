Release procedure
=================

1. Merge the latest develop into master.
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
   version in the format `v1.23.4` and set the target branch to `master`.
1. Enter the changelog in the release description field, with headings for `New
   features`, `Improvements`, `Bug fixes` and `Security`. Make sure to choose
   the option *Save draft*!
1. Deploy the release branch to the acceptance environment using the Jenkins
   job: https://jenkins.fpfis.eu/job/Joinup/job/acceptance/job/Build-acceptance/
1. Move the release ticket in UAT.
1. If any last minute problems are discovered during acceptance testing, these
   will be fixed in pull requests that are merged directly into master.
1. After receiving approval from the functional team, publish the new release on
   GitHub. This will automatically create the tag.
1. Merge back master into develop if needed.
1. Create a followup PR against develop that cleans up the update and deploy
   scripts.
