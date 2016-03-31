<?php

namespace Drupal\content_entity_example\Tests;

use Drupal\content_entity_example\Entity\Contact;
use Drupal\examples\Tests\ExamplesTestBase;

/**
 * Tests the basic functions of the Content Entity Example module.
 *
 * @package Drupal\content_entity_example\Tests
 *
 * @ingroup content_entity_example
 *
 * @group content_entity_example
 * @group examples
 */
class ContentEntityExampleTest extends ExamplesTestBase {

  public static $modules = array('content_entity_example', 'block', 'field_ui');

  /**
   * Basic tests for Content Entity Example.
   */
  public function testContentEntityExample() {
    $web_user = $this->drupalCreateUser(array(
      'add contact entity',
      'edit contact entity',
      'view contact entity',
      'delete contact entity',
      'administer contact entity',
      'administer content_entity_example_contact display',
      'administer content_entity_example_contact fields',
      'administer content_entity_example_contact form display',
    ));

    // Anonymous User should not see the link to the listing.
    $this->assertNoText(t('Content Entity Example: Contacts Listing'));

    $this->drupalLogin($web_user);

    // Web_user user has the right to view listing.
    $this->assertLink(t('Content Entity Example: Contacts Listing'));

    $this->clickLink(t('Content Entity Example: Contacts Listing'));

    // WebUser can add entity content.
    $this->assertLink(t('Add Contact'));

    $this->clickLink(t('Add Contact'));

    $this->assertFieldByName('name[0][value]', '', 'Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'First Name Field, empty');
    $this->assertFieldByName('name[0][value]', '', 'Gender Field, empty');

    $user_ref = $web_user->name->value . ' (' . $web_user->id() . ')';
    $this->assertFieldByName('user_id[0][target_id]', $user_ref, 'User ID reference field points to web_user');

    // Post content, save an instance. Go back to list after saving.
    $edit = array(
      'name[0][value]' => 'test name',
      'first_name[0][value]' => 'test first name',
      'gender' => 'male',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // Entity listed.
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    $this->clickLink('test name');

    // Entity shown.
    $this->assertText(t('test name'));
    $this->assertText(t('test first name'));
    $this->assertText(t('male'));
    $this->assertLink(t('Add Contact'));
    $this->assertLink(t('Edit'));
    $this->assertLink(t('Delete'));

    // Delete the entity.
    $this->clickLink('Delete');

    // Confirm deletion.
    $this->assertLink(t('Cancel'));
    $this->drupalPostForm(NULL, array(), 'Delete');

    // Back to list, must be empty.
    $this->assertNoText('test name');

    // Settings page.
    $this->drupalGet('admin/structure/content_entity_example_contact_settings');
    $this->assertText(t('Contact Settings'));

    // Make sure the field manipulation links are available.
    $this->assertLink(t('Settings'));
    $this->assertLink(t('Manage fields'));
    $this->assertLink(t('Manage form display'));
    $this->assertLink(t('Manage display'));
  }

  /**
   * Test all paths exposed by the module, by permission.
   */
  public function testPaths() {
    // Generate a contact so that we can test the paths against it.
    $contact = Contact::create(
      array(
        'name' => 'somename',
        'first_name' => 'Joe',
        'gender' => 'female',
      )
    );
    $contact->save();

    // Gather the test data.
    $data = $this->providerTestPaths($contact->id());

    // Run the tests.
    foreach ($data as $datum) {
      // drupalCreateUser() doesn't know what to do with an empty permission
      // array, so we help it out.
      if ($datum[2]) {
        $user = $this->drupalCreateUser(array($datum[2]));
        $this->drupalLogin($user);
      }
      else {
        $user = $this->drupalCreateUser();
        $this->drupalLogin($user);
      }
      $this->drupalGet($datum[1]);
      $this->assertResponse($datum[0]);
    }
  }

  /**
   * Data provider for testPaths.
   *
   * @param int $contact_id
   *   The id of an existing Contact entity.
   *
   * @return array
   *   Nested array of testing data. Arranged like this:
   *   - Expected response code.
   *   - Path to request.
   *   - Permission for the user.
   */
  protected function providerTestPaths($contact_id) {
    return array(
      array(
        200,
        '/content_entity_example_contact/' . $contact_id,
        'view contact entity',
      ),
      array(
        403,
        '/content_entity_example_contact/' . $contact_id,
        '',
      ),
      array(
        200,
        '/content_entity_example_contact/list',
        'view contact entity',
      ),
      array(
        403,
        '/content_entity_example_contact/list',
        '',
      ),
      array(
        200,
        '/content_entity_example_contact/add',
        'add contact entity',
      ),
      array(
        403,
        '/content_entity_example_contact/add',
        '',
      ),
      array(
        200,
        '/content_entity_example_contact/' . $contact_id . '/edit',
        'edit contact entity',
      ),
      array(
        403,
        '/content_entity_example_contact/' . $contact_id . '/edit',
        '',
      ),
      array(
        200,
        '/contact/' . $contact_id . '/delete',
        'delete contact entity',
      ),
      array(
        403,
        '/contact/' . $contact_id . '/delete',
        '',
      ),
      array(
        200,
        'admin/structure/content_entity_example_contact_settings',
        'administer contact entity',
      ),
      array(
        403,
        'admin/structure/content_entity_example_contact_settings',
        '',
      ),
    );
  }

}
