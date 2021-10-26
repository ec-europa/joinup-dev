# Ventuno for Joinup theme

Custom theme for Joinup. Built on the [OpenEuropa Bootstrap
theme](https://github.com/openeuropa/oe_bootstrap_theme).

#### Step 1
Make sure you have Node and npm installed.
You can read a guide on how to install node here:
https://docs.npmjs.com/getting-started/installing-node

#### Step 2
Go to the root of Ventuno theme and run `npm install`

#### Step 3
Run `npm run watch` to compile files onchange.

## Development workflow

The code is publicly available at https://github.com/ec-europa/joinup-dev/ but
following European Commission policy the development is coordinated in a private
Jira instance: https://citnet.tech.ec.europa.eu/CITnet/jira/projects/ISAICP.

When frontend development is requested, in most cases this will be preceded by a
backend ticket that will be developed first. After the backend work is reviewed
and approved in user acceptance testing a separate frontend sister ticket is
created. Since the backend and frontend will need to be delivered to production
simultaneously, the frontend ticket will be forked off the branch that contains
the backend functionality. In Joinup parlance the combination of both tickets is
called a "mini-epic". Once the FE ticket is reviewed and accepted, both will be
merged into the `develop` branch to be included in the next release.

There is often some coordination needed between frontend and backend teams
during the development of the frontent ticket, so a default time allocation of 4
hours is provided for the backend team to provide assistance if required. This
is intended for making any necessary changes to test coverage, or to expose any
missing data to the theme layer.


## Getting started

Install Joinup locally or using Docker. See the main README file in the project
root for instructions.

Joinup is an open source project which is intended to be reusable by different
organisations. When a clean install is performed it is fully functional but does
not contain any content. An empty site is not practical for frontend development
so in order to get some content to work with there are two approaches: download
the production database from the main instance at https://joinup.ec.europa.eu/
or run the Behat test scenario that is provided in the backend ticket.

### Using production databases

The simplest way to get a populated website is by downloading and installing the
the production databases. A nightly snapshot is made of the various databases
that make up Joinup (MariaDB, Solr, Virtuoso).

The advantages of this approach are that the site will be fully populated with
existing real world data. It is also quicker to download and restore the
databases than building a clean website from scratch, especially when using
Docker.

The disadvantage is that for newly developed functionality the production
database will not contain any data related to it, so manual steps are often
needed. Also the production instance has certain protections that will need to
be worked around. For example it is not possible to log in as the Drupal root
user, and EU Login will need to be bypassed. Special permission is also needed
to be able to download backups of production databases. This access is typically
only granted to developers that work full time under contract with the European
Commission.

```shell
# Install dependencies.
$ ./vendor/bin/composer install

# Download latest production backups.
$ ./vendor/bin/run dev:download-databases

# Build an instance of Joinup using the downloaded databases.
$ ./vendor/bin/run dev:rebuild-environment

# Unblock and log in as the Drupal root user (not recommended!)
$ ./vendor/bin/drush uublk joinup-admin
$ ./vendor/bin/drush uli

# Bypass EU Login and log in as a Joinup moderator (recommended)
$ ./vendor/bin/drush role-add-perm moderator "bypass limited access"
$ ./vendor/bin/drush uli --name=joinup-moderator
```

### Using a clean install

The most reliable way to get relevant content for a specific frontend ticket is
to perform a clean installation and run the Behat tests that are written for the
backend sister ticket. These tests are located in the `tests/features` folder.

The advantage of this is that for new functionality that has never been deployed
to production data can still be populated. Also reading the Behat test scenario
will give valuable insight in the functionality that needs to be themed, since
these scenarios are written as a step-by-step user scenario.

Disadvantages are that only the content specific to the new functionality will
be populated. The remainder of the site will be a barren wasteland. Also the
Behat test scenarios will clean up any created content at the end of the test
run, so a manual breakpoint needs to be placed inside the test at a strategic
point (usually after the initial test setup, before a test user logs in). A full
clean installation can also be much slower than a production database restore,
especially when using Docker.

The example below assumes that we are going to provide a frontend implementation
for a block showing a list of topics on the homepage. The file containing the
test scenario is `tests/features/homepage.feature`. The right scenario and the
file that contains it can be found by looking at the pull request of the backend
ticket.

```shell
# Install dependencies.
$ ./vendor/bin/composer install

# Perform clean installation.
$ ./vendor/bin/run toolkit:build-dev
$ ./vendor/bin/run toolkit:install-clean

# Enable error logging so notices and warnings will be visible.
$ ./vendor/bin/drush cset system.logging error_level --value=verbose
```

Now edit the test scenario using your favorite editor. Find the right section
and insert a Behat "breakpoint" in the scenario, after the necessary content is
being created:

```gherkin
...
And the "Discover topics" content listing contains:
  | type  | label                            |
  | topic | Employment and Support Allowance |
  | topic | E-justice                        |

# After the content creation, insert the following Behat "breakpoint". The
# test scenario will halt at this point.
Then I break

When I am logged in as an authenticated user
...
```

It is also recommended to disable any scenarios that are not relevant to save
time. Find all lines that start with `Scenario` and add a `@wip` tag above them.
Any scenarios tagged with `@wip` are skipped.

```gherkin
# Adding a "@wip" tag will disable this unrelated test scenario.
@wip
Scenario: Explore block shows a list of news.
...
```

Now run the test. When the test runner encounters the breakpoint the scenario
will be paused, and when the website is opened in a browser the content will be
available. After finishing work, press "enter" in the terminal to continue the
test scenario.

```shell
$ cd tests/
$ ./behat features/homepage.feature
```

## Notes

Joinup deviates from some standard Drupal practices. Here are some things that
might not be immediately obvious to devs expecting a standard Drupal site:

* The Drupal administration interface is not used by any user in production. It
  is solely used during development. The end users have a fully custom admin UI.
  When doing frontend work it is highly recommended to NOT log in as the Drupal
  admin. Instead log in as a normal user or a moderator, in order to view the
  site in the way an actual user would.
* In production config is locked down using the Config Read Only module. This
  ensures no unexpected configuration changes can be made in the production
  environment. All configuration changes need to be done in a clean install.
* Joinup uses a complex backend using multiple databases. This might cause
  unexpected side effects. For example newly created content might not become
  immediately visible in overview pages, but will require some additional steps
  like triggering indexing of the search engine, waiting for cron jobs to run,
  or forcing a cache clear. In most cases inspecting the Behat test scenarios
  that accompany the functionality will give insight to which steps might be
  needed for content to show up.
