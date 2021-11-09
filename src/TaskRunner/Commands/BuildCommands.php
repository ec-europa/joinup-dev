<?php

declare(strict_types = 1);

namespace Joinup\TaskRunner\Commands;

use OpenEuropa\TaskRunner\Commands\AbstractCommands;
use OpenEuropa\TaskRunner\Tasks\CollectionFactory\loadTasks;
use Robo\Collection\CollectionBuilder;
use Robo\Robo;
use Symfony\Component\Console\Input\InputOption;

/**
 * Provides commands to build the site's distribution.
 */
class BuildCommands extends AbstractCommands {

  use loadTasks;

  /**
   * Replaces the toolkit:build-dist command.
   *
   * This is a slightly changed fork of original BuildCommands::buildDist(). The
   * main differences are:
   * - Uses the `--worktree-attributes` option of `git archive` to filter out
   *   non-production code.
   * - Uses the `drupal:settings` command to build the `settings.php` file.
   * - Compiles SCSS to CSS.
   * - The Git tag and commit hash are computed, if not passed.
   *
   * @param array $options
   *   The command line options.
   *
   * @return \Robo\Collection\CollectionBuilder
   *   The Robo collection builder.
   *
   * @hook replace-command toolkit:build-dist
   *
   * @see \EcEuropa\Toolkit\TaskRunner\Commands\BuildCommands::buildDist()
   */
  public function buildDist(array $options = [
    'tag' => InputOption::VALUE_OPTIONAL,
    'sha' => InputOption::VALUE_OPTIONAL,
    'root' => InputOption::VALUE_REQUIRED,
    'dist-root' => InputOption::VALUE_REQUIRED,
    'keep' => InputOption::VALUE_REQUIRED,
  ]): CollectionBuilder {
    $config = $this->getConfig();
    $distDir = $config->get('joinup.dir') . '/' . $options['dist-root'];

    // Delete and (re)create the dist folder.
    $tasks = [$this->taskFilesystemStack()->remove($distDir)->mkdir($distDir)];

    // Copy all (tracked) files to the dist folder.
    $tasks[] = $this->taskExecStack()
      ->stopOnFail()
      ->exec('git archive HEAD --worktree-attributes | tar -x -C ' . $options['dist-root']);

    $tasks[] = $this->taskComposerInstall($config->get('composer.bin'))
      ->env('COMPOSER_MIRROR_PATH_REPOS', 1)
      ->workingDir($options['dist-root'])
      ->optimizeAutoloader()
      ->noDev();

    $tasks[] = $this->taskExec("{$config->get('runner.bin_dir')}/run")
      ->arg('drupal:settings')
      ->arg('prod')
      ->option('root', "{$distDir}/web");

    $tasks[] = $this->taskFilesystemStack()
      ->mkdir("{$distDir}/web/themes/joinup/css");

    $scssMap = [
      $config->get('scss.input') => "{$distDir}/web/themes/joinup/css/style.min.css",
    ];
    $tasks[] = $this->taskScss($scssMap)
      ->setFormatter('ScssPhp\\ScssPhp\\Formatter\\' . ucfirst($config->get('scss.style')))
      ->addImportPath($config->get('scss.import_dir'));

    // Prepare sha and tag variables.
    $tag = $this->getGitTag();
    $sha = $this->getGitCommitHash();

    // Write version tag in manifest.json and VERSION.txt.
    $tasks[] = $this->taskWriteToFile("{$distDir}/manifest.json")->text(
      json_encode(['version' => $tag, 'sha' => $sha], JSON_PRETTY_PRINT)
    );
    $tasks[] = $this->taskWriteToFile("{$distDir}/web/VERSION.txt")->text($tag);

    // Collect and execute list of commands set on local runner.yml.
    $commands = $config->get("toolkit.build.dist.commands");
    if (!empty($commands)) {
      $tasks[] = $this->taskCollectionFactory($commands);
    }

    return $this->collectionBuilder()->addTaskList($tasks);
  }

  /**
   * Returns the current Git tag.
   *
   * @return string
   *   Current Git tag.
   */
  protected function getGitTag(): string {
    /** @var \Gitonomy\Git\Repository $repository */
    $repository = Robo::getContainer()->get('repository');

    // Handle a potential case where the repository has been shallow cloned.
    // Typically, this happens in GitLab pipelines, where the repos are shallow
    // cloned for performance reasons. A shallow clone repository prevents us to
    // get the latest Git tag as no tags are available. But we require this info
    // during the coding standards check.
    $is_shallow_repository = $repository->run('rev-parse', ['--is-shallow-repository']) === 'true';
    if ($is_shallow_repository) {
      $repository->run('fetch', ['--unshallow']);
    }

    return trim($repository->run('describe', ['--tags']));
  }

  /**
   * Returns the current Git commit hash.
   *
   * @return string
   *   Current Git hash.
   */
  protected function getGitCommitHash(): string {
    return Robo::getContainer()->get('repository')->getHeadCommit()->getHash();
  }

}
