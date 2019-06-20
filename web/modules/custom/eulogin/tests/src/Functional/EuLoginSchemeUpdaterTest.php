<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_eulogin\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests the Joinup EU Login schema updater.
 *
 * @group joinup_eulogin
 */
class EuLoginSchemeUpdaterTest extends BrowserTestBase {

  use CronRunTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_eulogin_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Override the joinup_eulogin_test module default configuration in order to
    // use the mocked schema endpoint URL.
    $schema_url = Url::fromRoute('joinup_eulogin_test.schema')->setAbsolute()->toString();
    $settings['config']['joinup_eulogin.settings']['schema']['url'] = (object) [
      'value' => $schema_url,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests the Joinup EU Login schema updater.
   */
  public function testSchemaUpdate(): void {
    $key_value = \Drupal::keyValue('joinup');
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = \Drupal::service('module_installer');
    $state = \Drupal::state();

    // Check that before installing the module there's no data stored.
    $this->assertNull($key_value->get('eulogin.schema'));

    // Check that after installing the module data has been stored.
    $module_installer->install(['joinup_eulogin']);

    // @see \Drupal\joinup_eulogin_test\SchemaEndpointMock::getDomainTypesV310()
    $expected_v310 = [
      'version' => '3.1.0',
      'organisations' => [
        'eu.europa.ec' => 'European Commission (3.1.0)',
        'eu.europa.artemis' => 'Artemis Joint Undertaking',
        'eu.europa.berec' => 'The BEREC Office',
      ],
    ];

    // Check values for version 3.1.0.
    $this->assertSame($expected_v310, $key_value->get('eulogin.schema'));

    // Pretend that a new, 3.2.0, schema version has been uploaded.
    $state->set('joinup_eulogin_test.version', '3.2.0');

    // Run cron as an attempt to update the stored schema.
    $this->cronRun();

    // Stored data is not changed because cron only checks for a new version
    // each three months and the last update was no more than few seconds ago.
    $this->assertSame($expected_v310, $key_value->get('eulogin.schema'));

    // Pretend that the last update occurred three months plus one second ago.
    $three_months_one_second_ago = \Drupal::time()->getRequestTime() - (60 * 60 * 24 * 90 + 1);
    $state->set('joinup_eulogin.schema.last_updated', $three_months_one_second_ago);

    // Run cron again.
    $this->cronRun();

    // @see \Drupal\joinup_eulogin_test\SchemaEndpointMock::getDomainTypesV320()
    $expected_v320 = [
      'version' => '3.2.0',
      'organisations' => [
        // European Commission label has been changed.
        'eu.europa.ec' => 'European Commission (3.2.0)',
        'eu.europa.artemis' => 'Artemis Joint Undertaking',
        'eu.europa.berec' => 'The BEREC Office',
        // This is a new organisation compared to 3.1.0.
        'eu.europa.acme' => 'ACME',
      ],
    ];

    // Check that the stored schema has version 3.2.0.
    $this->assertSame($expected_v320, $key_value->get('eulogin.schema'));

    // Check that after uninstalling the module there's no data stored.
    $module_installer->uninstall(['joinup_eulogin']);
    $this->assertNull($key_value->get('eulogin.schema'));
  }

}
