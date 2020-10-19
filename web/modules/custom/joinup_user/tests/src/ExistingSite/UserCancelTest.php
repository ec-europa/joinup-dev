<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_user\ExistingSite;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drupal\Tests\rdf_entity\Traits\DrupalTestTraits\RdfEntityCreationTrait;
use Drupal\file\Entity\File;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use weitzman\LoginTrait\LoginTrait;

/**
 * Tests user cancellation.
 *
 * @group joinup_user
 */
class UserCancelTest extends JoinupExistingSiteTestBase {

  use LoginTrait;
  use RdfEntityCreationTrait;

  /**
   * Tests user cancellation.
   */
  public function testUserCancellation(): void {
    /** @var \Drupal\externalauth\AuthmapInterface $authmap */
    $authmap = $this->container->get('externalauth.authmap');
    /** @var \Drupal\og\MembershipManagerInterface $og_membership */
    $og_membership = $this->container->get('og.membership_manager');
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = $this->container->get('user.data');

    $collection = $this->createRdfEntity([
      'rid' => 'collection',
      'label' => 'Collection',
      'field_ar_state' => 'validated',
    ]);
    $solution = $this->createRdfEntity([
      'rid' => 'solution',
      'label' => 'Solution',
      'collection' => $collection,
      'field_is_state' => 'validated',
    ]);

    $photo_field_definition = $this->container->get('entity_field.manager')->getFieldDefinitions('user', 'user')['field_user_photo'];
    $photo_value = ImageItem::generateSampleValue($photo_field_definition);

    /** @var \Drupal\file\FileInterface $photo_file */
    $photo_file = File::load($photo_value['target_id']);
    // Check that the file entity has been created.
    $this->assertFileExists($photo_file->getFileUri());

    $username = strtolower($this->randomMachineName());
    /** @var \Drupal\joinup_user\Entity\JoinupUserInterface $account */
    $account = $this->createUser([], $username, FALSE, [
      'mail' => "{$username}.init@example.com",
      'pass' => 'plain',
      'langcode' => 'nl',
      'preferred_langcode' => 'cs',
      'preferred_admin_langcode' => 'et',
      'timezone' => 'Europe/Bucharest',
      'roles' => [
        'moderator',
      ],
      'field_user_family_name' => 'Doe',
      'field_user_first_name' => 'John',
      'field_social_media' => [
        'platform' => 'some platform',
        'value' => 'some value',
        'values' => serialize([
          'facebook' => ['value' => 'fb'],
          'github'  => ['value' => 'gh'],
          'slideshare'  => ['value' => 'ss'],
          'twitter'  => ['value' => 'tw'],
          'vimeo'  => ['value' => 'vm'],
          'youtube'  => ['value' => 'yt'],
        ]),
      ],
      'field_user_business_title' => 'CEO',
      'field_user_content' => serialize([
        'fields' => [
          'user_content_bundle' => [
            'weight' => 1,
            'region' => 'top',
          ],
        ],
        'enabled' => TRUE,
        'query_presets' => '',
        'limit' => 12,
      ]),
      'field_user_frequency' => 'weekly',
      'field_user_nationality' => 'http://example.com/canada',
      'field_user_organisation' => 'ACME',
      'field_user_photo' => $photo_value,
      'field_user_professional_domain' => 'http://example.com/gov',
    ]);
    // Change the email to have a different initial email.
    $account->setEmail("{$username}@example.com")->save();

    // Create a CAS link.
    $authmap->save($account, 'cas', $username);
    $this->assertSame($username, $authmap->get($account->id(), 'cas'));

    // Add account membership to collection and solution.
    $og_membership->createMembership($collection, $account)->save();
    $og_membership->createMembership($solution, $account)->save();

    // Add some user data.
    $user_data->set('joinup_user', $account->id(), 'foo', 'bar');

    // Store the hashed password for later comparision.
    $password_hash = $account->getEmail();

    // Cancel the account.
    $account->cancel()->save();

    $account = User::load($account->id());

    // Check preserved fields.
    $this->assertSame($username, $account->getAccountName());
    $this->assertSame('Doe', $account->get('field_user_family_name')->value);
    $this->assertSame('John', $account->get('field_user_first_name')->value);

    // Check anonymized fields.
    $this->assertNull($account->getEmail());
    $this->assertNull($account->getInitialEmail());
    $this->assertNotSame($password_hash, $account->getPassword());
    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $account->language()->getId());
    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $account->get('preferred_langcode')->value);
    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $account->get('preferred_admin_langcode')->value);
    $this->assertSame($this->container->get('config.factory')->get('system.date')->get('timezone.default'), $account->getTimeZone());
    $this->assertEmpty($account->getRoles(TRUE));
    $this->assertTrue($account->get('field_social_media')->isEmpty());
    $this->assertNull($account->get('field_user_business_title')->value);
    $this->assertTrue($account->get('field_user_content')->isEmpty());
    $this->assertNull($account->get('field_user_frequency')->value);
    $this->assertNull($account->get('field_user_nationality')->value);
    $this->assertNull($account->get('field_user_organisation')->value);
    $this->assertFileNotExists($photo_file->getFileUri());
    $this->assertNull($account->get('field_user_photo')->value);
    $this->assertNull($account->get('field_user_professional_domain')->value);
    $this->assertFalse($authmap->get($account->id(), 'cas'));
    $this->assertEmpty($og_membership->getMemberships($account->id(), OgMembershipInterface::ALL_STATES));
    $this->assertNull($user_data->get('joinup_user', $account->id(), 'foo'));

    // Cancelling again is not possible.
    $moderator = $this->createUser([], NULL, FALSE, ['roles' => ['moderator']]);
    $this->drupalLogin($moderator);
    $this->drupalGet("/user/{$account->id()}/cancel");
    $this->assertSession()->statusCodeEquals(403);
  }

}
