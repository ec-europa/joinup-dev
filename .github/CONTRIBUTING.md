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