# Contributing to Joinup

## Before you start
* You're thinking of setting up your own code repository using the Joinup
  codebase?
* You are about to develop a big feature on top of this codebase?
* You're having trouble installing this project?
* If you want to report an issue?

Use the Github issue queue to get in touch! We'd like to hear about your plans.

## Legal
By submitting a pull request to the Joinup repository, you implicitly accept the conditions in the
 [Joinup Individual Contributor Assignment Agreement](/docs/agreement.md) as well as the  [code of conduct](/docs/code_of_conduct.md).

The Joinup codebase is released under the [European Union Public Licence (EUPL)](https://joinup.ec.europa.eu/community/eupl/og_page/eupl).

## How to contribute
Contribution to Joinup is similar to most other projects on GitHub:
We use the [fork and pull](https://help.github.com/articles/types-of-collaborative-development-models/) model, which means that everyone whom wants to contribute, can do so through a pull request from a fork of Joinup towards the Joinup repository.
More information on pull requests is available on the '[using pull request](https://help.github.com/articles/using-pull-requests/)' page.

We are however not obligated to use your contribution as part of Joinup and may decide to include any contribution we consider appropriate.
 
## Code quality
We try to keep the quality of Joinup as high as possible, and therefore a few measures are put in place:
* Adherence of the drupal coding standards is verified on each commit.
  (Please note that the coding standards can change in the future.
* Functional tests ([Behat](http://behat.org)) are ran on each commit to avoid the introduction of regression.

You can [check our current test scenarios here](/tests/features/).

If you plan to make contributions to the Joinup codebase, we kindly ask you to
run the coding standards checks, as well as the Behat test suite before making
a pull request. Also make sure you add test coverage for the functionality
covered in the pull request.

## Workflow
In order to get a contribution accepted in Joinup, follow this procedure:

1. _fork_: Most contributions are forked from the _develop_ branch where the
   main development takes place. However if an issue is highly critical and
   needs to be urgently put into production it should be forked from the
   _master_ branch so it can be deployed as a hotfix.
1. _develop_: Make sure the individual commits are well described and scoped. It
   is allowed to rebase feature branches to clean up the history. The pull
   pull request should only contain changes that are strictly necessary to
   complete the task on hand. It's fine to include small fixes in related code
   even if it is not strictly in scope, such as fixing typos, updating
   documentation and removing unused variables and use statements. The reviewer
   will have the right to reject any of these small fixes though if they are not
   immediately clear.
1. _qa_: When development on the pull request is ready the code needs to be
   reviewed by the Joinup development team. The functionality offered in the PR
   should be covered by tests, and obviously all existing tests should pass. The
   development team regularly checks the open pull requests and will pick up
   tickets to be reviewed. Note that this might take some time since the work is
   organized in 2-weekly sprints. To avoid any confusion about the state of a PR
   feel free to post a comment mentioning that the PR is ready to be reviewed.
1. _uat_: Once a PR has been approved by the development team it will be merged
   into the `UAT-ready` branch. A user acceptance test will be performed by the
   Joinup functional team which has the final decision whether the functionality
   will be accepted in Joinup.
1. _merge_: Only after the functional team approves the PR it will be merged
   into the main branch by one of the developers.
1. _deploy_: At the end of every 2-weekly sprint the _develop_ branch will be
   merged into the _master_ branch and a new release will be tagged. This
   release will then be deployed into production.
