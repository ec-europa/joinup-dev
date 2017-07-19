<?php

/**
 * @file
 * Assertions for 'user' migration.
 */

use Drupal\user\Entity\User;

$account = User::load(7217);
$this->assertEquals('pietrodabbra', $account->getUsername());
$this->assertEquals('pietrodabbra@live.it', $account->getEmail());
$this->assertEquals(1226583638, $account->getCreatedTime());
$this->assertEquals(1323856812, $account->getLastAccessedTime());
$this->assertEquals(1323856810, $account->getLastLoginTime());
$this->assertEquals('Europe/Rome', $account->getTimeZone());
$this->assertEquals('pietrodabbra@msn.com', $account->getInitialEmail());
$this->assertEquals('Dabbra', $account->get('field_user_family_name')->value);
$this->assertEquals('Pietro', $account->get('field_user_first_name')->value);
$this->assertRedirects([
  'profile/pietrodabbra-profile',
  'people/7217',
  'node/20962',
], $account);
