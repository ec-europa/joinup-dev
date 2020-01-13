<?php

namespace Drupal\Tests\error_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests custom error/exception page.
 *
 * @group error_page
 *
 * @todo Figure out how to test fatal errors, user errors and notices. It seems
 * impossible with a functional test, as the exception handler detects the
 * testing environment and always throws the standard error.
 */
class ErrorPageTest extends BrowserTestBase {

  /**
   * The UUID regex pattern.
   *
   * @var string
   */
  const UUID_PATTERN = '/([0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12})/i';

  /**
   * The settings.php content.
   *
   * @var string
   */
  protected $settingsPhp;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'error_page_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Adds custom error handlers.
    $settings_php_file = $this->siteDirectory . '/settings.php';
    $settings_php = file_get_contents($settings_php_file);

    // Save the original settings.php content.
    $this->settingsPhp = $settings_php;

    $settings_php .= "\nset_error_handler(['Drupal\\error_page\\ErrorPageErrorHandler', 'handleError']);\n";
    $settings_php .= "set_exception_handler(['Drupal\\error_page\\ErrorPageErrorHandler', 'handleException']);\n";
    $settings_php .= "\$config['system.logging']['error_level'] = 'verbose';\n";
    chmod($settings_php_file, 0666);
    file_put_contents($settings_php_file, $settings_php);
  }

  /**
   * Test error/exception custom handlers.
   */
  public function test() {
    foreach ($this->getTestCases() as $path => $case) {
      $this->doTest($path, $case['code']);
      // Restore settings.php.
      file_put_contents($this->siteDirectory . '/settings.php', $this->settingsPhp);
    }
  }

  /**
   * Test one case.
   */
  protected function doTest($path, $code) {
    // Run with ERROR_REPORTING_DISPLAY_VERBOSE error level.
    $this->setSetting(['system.logging', 'error_level'], "'verbose'", 'config');

    $this->drupalGet($path);
    $assert = $this->assertSession();

    // Test with default settings.
    $assert->statusCodeEquals($code);
    preg_match(static::UUID_PATTERN, $this->getSession()->getPage()->getContent(), $found);
    $assert->pageTextContains('There was an unexpected problem serving your request');
    $assert->pageTextContains("Please try again and contact us if the problem persist including {$found[1]} in your message.");
    $log = $this->getLastLog();
    $this->assertEquals('%type: @message in %function (line %line of %file) [@uuid].', $log->message);
    $variables = unserialize($log->variables);
    $this->assertEquals($found[1], $variables['@uuid']);

    // Disable UUID.
    $this->setSetting(['error_page', 'uuid'], 'FALSE');

    $this->getSession()->reload();
    preg_match(static::UUID_PATTERN, $this->getSession()->getPage()->getContent(), $found);
    $this->assertArrayNotHasKey(1, $found);
    $assert->pageTextContains('There was an unexpected problem serving your request');
    // Note that the following message looks broken because we're using the
    // default template which assumes an UUID variable.
    $assert->pageTextContains("Please try again and contact us if the problem persist including in your message.");
    $log = $this->getLastLog();
    $this->assertEquals('%type: @message in %function (line %line of %file) [@uuid].', $log->message);
    $variables = unserialize($log->variables);
    $this->assertArrayNotHasKey('@uuid', $variables);

    // Customize the template.
    $template = file_get_contents(drupal_get_path('module', 'error_page') . '/markup/error_page.html');
    // Fix the message to work without UUID.
    $template = str_replace(' including <em>{{ uuid }}</em> in your message', '', $template);
    file_put_contents('public://error_page.html', $template);
    $this->setSetting(['error_page', 'template_dir'], "'{$this->siteDirectory}/files'");

    $this->getSession()->reload();
    preg_match(static::UUID_PATTERN, $this->getSession()->getPage()->getContent(), $found);
    $this->assertArrayNotHasKey(1, $found);
    $assert->pageTextContains('There was an unexpected problem serving your request');
    $assert->pageTextContains("Please try again and contact us if the problem persist.");
    $log = $this->getLastLog();
    $this->assertEquals('%type: @message in %function (line %line of %file) [@uuid].', $log->message);
    $variables = unserialize($log->variables);
    $this->assertArrayNotHasKey('@uuid', $variables);

    // Check that the error messge and tge backtrace are displayed.
    $assert->pageTextContains('Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()');
    $assert->responseContains('<pre class="backtrace">');

    // Run with ERROR_REPORTING_DISPLAY_ALL error level.
    $this->setSetting(['system.logging', 'error_level'], "'all'", 'config');
    // Check that the error message is shown but the backtrace is hidden.
    $this->getSession()->reload();
    $assert->pageTextContains('Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()');
    $assert->responseNotContains('<pre class="backtrace">');

    // Run with ERROR_REPORTING_HIDE error level.
    $this->setSetting(['system.logging', 'error_level'], "'hide'", 'config');
    // Check that the both, error message and backtrace are hidden.
    $this->getSession()->reload();
    $assert->pageTextNotContains('Exception: donuts in Drupal\error_page_test\Controller\ErrorPageTestController->exception()');
    $assert->responseNotContains('<pre class="backtrace">');
  }

  /**
   * Provides test cases.
   *
   * Not using PHPUnit @dataProvider because that would install the Drupal site
   * for each case.
   *
   * @return array
   *   A list of test cases.
   */
  protected function getTestCases() {
    return [
      '/error_page_test/exception' => ['code' => 500],
      // @todo Figure out how to test fatal errors, user errors and notices.
      // '/error_page_test/fatal_error' => ['code' => 500],
      // '/error_page_test/user_error' => ['code' => 200],
      // '/error_page_test/php_notice' => ['code' => 200],
    ];
  }

  /**
   * Returns the last logged message and empty the log.
   *
   * @return object
   *   The last log entry.
   */
  protected function getLastLog() {
    /** @var \Drupal\Core\Database\Connection $db */
    $db = $this->container->get('database');
    $log = $db->select('watchdog', 'w')
      ->fields('w')
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetch();
    $db->truncate('watchdog');

    return $log;
  }

  /**
   * Adds a new setting in settings.php file.
   *
   * @param string[] $trail
   *   The array path.
   * @param string $value
   *   The value as is represented in code.
   * @param string $variable
   *   (optional) The variable to be set. Defaults to 'settings'.
   */
  protected function setSetting(array $trail, $value, $variable = 'settings') {
    $settings_file = $this->siteDirectory . '/settings.php';
    $line = "\n\${$variable}['" . implode("']['", $trail) . "'] = $value;\n";
    chmod($settings_file, 0666);
    file_put_contents($settings_file, $line, FILE_APPEND);
  }

}
