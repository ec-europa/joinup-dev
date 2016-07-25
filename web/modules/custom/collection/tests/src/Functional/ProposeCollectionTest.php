<?php

namespace Drupal\Tests\og\Functional;

use Drupal\joinup\JoinupJavascriptTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Tests the complex widget.
 *
 * @group og
 */
class ProposeCollectionTest extends JoinupJavascriptTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Tests Javascript behaviour of the 'Closed collection' checkbox.
   */
  public function testClosedCollection() {
    // Log in as a normal user.
    $this->drupalLogin($this->users['authenticated']);

    // Go to the Propose Collection form.
    $html = $this->drupalGet('collection/propose');
    file_put_contents('/home/pieter/v/joinup/web/test.html', $html);
    $session = $this->assertSession();

    // Initially the "Closed collection" checkbox should be unchecked, and the
    // options to publish new content should be limited to "Only members" and
    // "Any registered user". The option "Only collection facilitator" should be
    // hidden.
    $checkbox = $session->fieldExists('edit-field-ar-closed-value');
    $session->assert(!$checkbox->isChecked(), 'Closed collection checkbox is initially not checked.');
    // @todo Add comment.
    $labels = [
      'Only members can publish new content',
      'Any registered user can publish new content',
    ];
    foreach ($labels as $label) {
      $this->assertSliderLabelPresent('edit-field-ar-elibrary-creation-wrapper', $label);
    }
    $this->assertSliderLabelNotPresent('Only collection facilitator can publish new content');
  }

}
