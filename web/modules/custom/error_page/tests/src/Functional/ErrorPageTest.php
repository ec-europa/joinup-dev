<?php

namespace Drupal\Tests\error_page\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests custom error/exception page.
 *
 * @group error_page
 *
 * @todo Figure out how to test fatal and user errors. It seems impossible with
 * a functional test, as the exception handler detects the testing environment
 * and always throws the standard error.
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
    $settings_php .= "\nset_error_handler(['Drupal\\error_page\\ErrorPageErrorHandler', 'handleError']);\n";
    $settings_php .= "set_exception_handler(['Drupal\\error_page\\ErrorPageErrorHandler', 'handleException']);\n";
    chmod($settings_php_file, 0666);
    file_put_contents($settings_php_file, $settings_php);
    $this->settingsPhp = $settings_php;
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

    // Disable appending the UUID to the message.
    $this->setSetting(['error_page', 'uuid', 'add_to_message'], 'FALSE');

    $this->getSession()->reload();
    preg_match(static::UUID_PATTERN, $this->getSession()->getPage()->getContent(), $found);
    $assert->pageTextContains('There was an unexpected problem serving your request');
    $assert->pageTextContains("Please try again and contact us if the problem persist including {$found[1]} in your message.");
    $log = $this->getLastLog();
    $this->assertEquals('%type: @message in %function (line %line of %file).', $log->message);
    $variables = unserialize($log->variables);
    $this->assertEquals($found[1], $variables['@uuid']);

    // Disable appending the UUID to the message.
    $this->setSetting(['error_page', 'uuid', 'enabled'], 'FALSE');

    $this->getSession()->reload();
    preg_match(static::UUID_PATTERN, $this->getSession()->getPage()->getContent(), $found);
    $this->assertArrayNotHasKey(1, $found);
    $assert->pageTextContains('There was an unexpected problem serving your request');
    // Note that the following message looks broken because we're using the
    // default template which assumes an UUID variable.
    $assert->pageTextContains("Please try again and contact us if the problem persist including in your message.");
    $log = $this->getLastLog();
    $this->assertEquals('%type: @message in %function (line %line of %file).', $log->message);
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
    $this->assertEquals('%type: @message in %function (line %line of %file).', $log->message);
    $variables = unserialize($log->variables);
    $this->assertArrayNotHasKey('@uuid', $variables);
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
      // @todo Figure out how to test fatal and user errors.
      // '/error_page_test/fatal_error' => ['code' => 500],
      // '/error_page_test/user_error' => ['code' => 200],
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
   */
  protected function setSetting(array $trail, $value) {
    $settings_file = $this->siteDirectory . '/settings.php';
    $line = "\n\$settings['" . implode("']['", $trail) . "'] = $value;\n";
    chmod($settings_file, 0666);
    file_put_contents($settings_file, $line, FILE_APPEND);
  }

}
