<?php

/**
 * @file
 * Builds placeholder replacement tokens for CAS computed attributes.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info_alter().
 */
function joinup_eulogin_token_info_alter(&$data): void {
  // Alter existing token definition provided by CAS to add the dynamic computed
  // attribute token.
  $data['tokens']['cas']['computed-attribute'] = [
    'name' => t('Computed attribute'),
    'description' => t('An attribute computed from CAS attributes. Requires the "Sitewide token support" feature of the CAS Attributes module.'),
    'dynamic' => TRUE,
  ];
}

/**
 * Implements hook_tokens().
 */
function joinup_eulogin_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];
  if ($type !== 'cas') {
    return $replacements;
  }

  // Find all attribute tokens formatted like [cas:computed-attribute:*].
  if ($attribute_tokens = \Drupal::token()->findWithPrefix($tokens, 'computed-attribute')) {
    $cas_attributes = [];
    // Attributes may have been explicitly passed into the token replacer, so
    // check for them there first. Otherwise, fallback to checking the session
    // data. The main CAS module stores all attributes in the session when login
    // is finalized.
    if (!empty($data['cas_attributes'])) {
      $cas_attributes = $data['cas_attributes'];
    }
    else {
      $session = \Drupal::request()->getSession();
      if ($session->has('cas_attributes')) {
        $cas_attributes = $session->get('cas_attributes');
      }
    }

    /** @var \Drupal\joinup_eulogin\JoinupEuLoginComputedAttributesInterface $computed_attributes_service */
    $computed_attributes_service = \Drupal::service('joinup_eulogin.computed_attributes');
    foreach ($attribute_tokens as $attribute => $original_token) {
      $replacements[$original_token] = $computed_attributes_service->getReplacementValue($attribute, $original_token, $cas_attributes, $bubbleable_metadata);
    }
  }

  return $replacements;
}
