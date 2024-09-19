# OS2udoglaer

A Drupal Premium Headless project for the new OS2udoglaer.

The full stack is:

- Drupal 10
- PHP 8.2 (with Composer 2)
- Nginx latest
- Solr latest
- Redis latest
- mariadb latest

For a better understanding of the projects staging and production environments, please see the [Deployment](#deployment)
section.


## Development

You can run this project in a local environment using either Ddev (quick setup) or locally installed
with LAMP stack (long initial setup)

### Getting Started

These instructions will get you a copy of the project up and running on your local machine for development, demo and
testing purposes. See deployment for notes on how to deploy the project staging, QA and production systems.


#### DDEV

DDEV sets up the project and necessary backing services.

##### Benefits

DDEV is fast, very reliable, open source and we have a lot of experience using it during development at Novicell.

##### Requirements

On your host machine install the following:

- [Git](https://git-scm.com/)
- [Docker](https://ddev.readthedocs.io/en/stable/users/docker_installation/)
- [DDEV](https://ddev.readthedocs.io/en/stable/)

1. Clone the project repository
   ```sh
   git clone git@github.com:os2ulf/os2udoglaer.git
   ```
2. Enter the newly created project directory
   ```sh
   cd os2udoglaer
   ```
3. Create local `.env` file from `.env.dist`:
   ```sh
   cp .env.dist .env
   ```
   _**Note: Insert values or reach out if there are any issues with the default Ddev setup.**_
4. Install dependencies with Composer
   ```sh
   ddev composer install
   ```
5. Now run project with DDEV:
   ```sh
   ddev import-db
   ```
6. Import the latest database using DDEV:
   ```sh
   ddev start
   ```
   Project can be reached at [https://os2udoglaer.localhost](https://os2udoglaer.localhost)
7. List entire DDEV project:
   ```sh
    ddev describe
    ```

#### DDEV commands to fixup your project

You have to run these commands if you have issues with your project. If you have issues with your project, you can
run these commands to fix it.

1. Update your local master branch or feature branch:
    ```sh
    git pull
    ```
2. Install latest modules and themes:
   ```sh
   ddev composer install
   ```
3. Run custom Ddev Drupal local update script:
    ```sh
    .ddev/commands/host/local_update
    ```

If the project is still broken after running these commands, you can try to run the following command:

```sh
ddev restart
```

If the project is still broken after running these commands, you can try to reimport the database:

```sh
ddev import-db
```

#### Traditional development environment

Install Apache, MariaDB (or MySQL), PHP 8.2 and
Composer - [Guide to Ubuntu LAMP stack install](https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-20-04)

Use a Linux pc/server or setup an alternative way of running the stack on your machine if using Windows or Macintosh PC
like [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/en/)

1. Clone the project repository
   ```sh
   git clone git@github.com:os2ulf/os2udoglaer.git
   ```
2. Enter the newly created project directory
   ```sh
   cd os2udoglaer
   ```
3. Install dependencies with Composer
   ```sh
   composer install
   ```
4. Make sure that your Apache server is configured correctly depending on local LAMP-stack.

### Apache Solr search

The project uses Apache Solr as search engine. The Solr server is running on the same software stack as the Drupal
site.

#### Solr configuration

### Composer

New functionality is often added through contributed, premium or custom modules. These are installed and maintained
using Composer, which is a dependency manager for PHP. This documentation
on [Composer](https://getcomposer.org/doc/01-basic-usage.md)
will get you up to speed and managing dependencies in no time.

**_Note: While developing new features or structures we do not wish to update our Composer dependencies unless this is
necessary. This means not running `composer update` if this is not part of the task at hand._**

#### Quick commands list

To add a dependency to a project:

   ```sh
   composer require drupal/example_contrib_module
   ```

To install all dependencies from composer.json or composer.lock:

   ```sh
   composer install
   ```

Changes to dependencies are written to `composer.json` and `composer.lock` in the root of our project, from where they
can be committed and pushed remotely.

#### Drupal configuration during development

While developing new feature or structure in Drupal it is important to export the configuration of the feature you are
working on. You can always check the status of the configuration synchronisation using Drush:

   ```sh
   drush config:status
   ```

If you have changes to the configuration use Drush to export these:

   ```sh
   drush cex -y
   ```

The configuration will be written to `.yml` files in `/config/sync`, from where they can be committed and pushed.

_**Note: When exporting config do a check on language switches (they do unfortunately happen) and overwriting customized
config (like standard Drupal mails changed through core/updb - we have that issue on other projects)

### Git strategy & collaboration

We use Git in order to ease collaboration and create versioning of our code. Furthermore, we aim to improve the overall
quality during development by keeping our git strategy lean and without complexity. This in order to get a dynamic
workflow that suites the size of the project and developer team.

#### Branch naming convention and commit message

The branch naming convention has been taken from Gitflow and customised with the standard Novicell approach - some
examples and benefits:

* Branch name should include issue tag (example: `OS2UOL-50`):
   ```
   OS2UOL-50
   ```

By including the issue tag in the commit messages and branch name we get the benefit of the Github/Jira integration.

##### Rebase in developer workflow

Developers work differently but here is an example of a workflow using Git for this project.

Pull the latest changes of the master branch - _**Note: use `--rebase` flag to align our local master’s history
with the remote**_:

   ```sh
   git pull --rebase origin master
   ```

Create a feature branch before starting new work:

   ```sh
   git switch -c my-awesome-feature-branch
   ```

Make some changes and commit often in the feature branch:

   ```sh
   git add .
   ```

   ```sh
   git commit -m "My awesome comment"
   ```

Periodically rebase your work onto master branch - in case new features have been merged to master.
First off we update our local master branch:

   ```sh
   git checkout master
   ```

   ```sh
   git pull --rebase origin master
   ```

Include the latest commits of our local master branch, and get them into our local feature branch.

   ```sh
   git checkout feature/my-awesome-feature-branch
   ```

   ```sh
   git rebase master
   ```

_**Note: During a rebase you might have to deal with conflicts, this is expected and unavoidable if there are changes
**_:

Now our branch is up-to-date. Now we build and test locally again after which we can push to our remote:

   ```sh
   git push origin feature/my-awesome-feature-branch --force
   ```

_**Note: new commits are added to the branch and by using the `--force` or  `-f` flag we allow git to overwrite the
history of the remote branch. Forgetting this step will lead to a Git error as the branch histories of local and remote
differ. Do not `git pull` even if prompted for it - your current feature branch will include all changes from master and
your own changes - being both ahead and behind master.**_

Lastly the developer merges the new feature to staging branch. For added benefits a Pull Request (PR) can be created as
the foundation for a code review or a colleague giving their input. Once the code has been tested on staging and/or
reviewed it is merged to our master branch. This strategy gives less importance to the maintenance and state of the
staging branch, which can always be recreated when needed - all code is always merged to master after test and/or code
review.
_**Note: Any existing PR will be updated with any changes made to our feature branch.**_

#### Pull requests & reviews

It is good practice that new features are submitted to the project in the form of Pull Requests, which are subsequently
reviewed and approved by other developers on the team. A flow for good handover should include the following before
reassign:

* Locally building project with new feature.
* Testing new feature locally.
* Create PR including an added/removed lists.
* Short guide as to how the feature or change can be tested by reviewer.
* Communicate if the PR is blocked by another PR, decision or action - for complex integrations include check list.

##### Best practices for best collaboration

Git and collaboration on a big team can be confusing at times, and we have gathered a few best practices to ensure good
collaboration:

* Merges come from Pull Requests (PR), which invites team members to engage - main branch is protected.
* Merge squash your multiple changes in short live branches - improved git history.
* Rebasing master against your short-lived branch to keep it up to date is best.
  _**Note: do NOT merge master back to your short-lived branch and then back again to trunk, effectively creating
  multiple
  merge commits and confusing history.**_
* Feature branches should be short-lived - this fits our delivery model and tasks broken down to maximum two working
  days.
* Keep commit messages as concise as possible, while still making sense and add the case number as the first thing.
* NEVER commit secrets of any kind to the repository — EVER.
* With Composer use the `--sort-packages` flag, which is a nice feature, though it should be a default on composer 2,
  it is not always running automatically ¯\_(ツ)_/¯
* Let us keep the repository clean and delete old feature branches.

### Deployment

This is done using Platform.sh CI/CD and Gitops.

### Deploy bumper
This has been done 2 times
