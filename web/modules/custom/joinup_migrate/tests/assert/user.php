<?php

/**
 * @file
 * Assertions for 'user' migration.
 */

use Drupal\user\Entity\User;

$account = User::load(7217);
$this->assertEquals('user7217', $account->getUsername());
$this->assertEquals('user7217@example.com', $account->getEmail());
$this->assertEquals(1226583638, $account->getCreatedTime());
$this->assertEquals(1323856812, $account->getLastAccessedTime());
$this->assertEquals(1323856810, $account->getLastLoginTime());
$this->assertEquals('Europe/Rome', $account->getTimeZone());
$this->assertEquals('init7217@example.com', $account->getInitialEmail());
$this->assertEquals('LastName7217', $account->get('field_user_family_name')->value);
$this->assertEquals('FirstName7217', $account->get('field_user_first_name')->value);
$this->assertEquals('Professional Profile for Pietro.', $account->get('field_user_professional_profile')->value);
$this->assertEquals('my.fb.account', $account->get('field_social_media')->platform_values['facebook']['value']);
$this->assertEquals('tw_user_acc', $account->get('field_social_media')->platform_values['twitter']['value']);
$this->assertEquals('in/myacc', $account->get('field_social_media')->platform_values['linkedin']['value']);
$this->assertRedirects([
  'profile/pietrodabbra-profile',
  'people/7217',
  'node/20962',
], $account);
