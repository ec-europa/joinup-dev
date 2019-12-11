<?php

/**
 * @file
 * Post update functions for the contact information module.
 */

declare(strict_types = 1);

/**
 * Split off contacts with multiple parents.
 *
 * @param array $sandbox
 *   The sandbox array for batch operations.
 *
 * @return string
 *   The result message.
 */
function contact_information_post_update_split_off_multi_parent_contacts(array &$sandbox): string {
  /** @var \Drupal\Driver\Database\sparql\Connection $connection */
  $connection = \Drupal::service('sparql.endpoint');
  if (empty($sandbox['contact_ids'])) {
    $query = <<<QUERY
SELECT ?entity_id
FROM <http://joinup.eu/collection/published>
FROM <http://joinup.eu/collection/draft>
FROM <http://joinup.eu/solution/published>
FROM <http://joinup.eu/solution/draft>
WHERE {
  ?parent <http://www.w3.org/ns/dcat#contactPoint> ?entity_id
}
GROUP BY ?entity_id
HAVING (COUNT(DISTINCT ?parent) > 1)
QUERY;
    $results = $connection->query($query);
    $sandbox['contact_ids'] = array_map(function (stdClass $result): string {
      return $result->entity_id->getUri();
    }, $results->getArrayCopy());
    $sandbox['max'] = count($sandbox['contact_ids']);
    $sandbox['current'] = 0;
  }

  $storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $next_batch = array_splice($sandbox['contact_ids'], 0, 10);
  foreach ($next_batch as $id) {
    $query = $storage->getQuery();
    $condition_or = $query->orConditionGroup();
    $query->condition('rid', ['collection', 'solution'], 'IN');
    $condition_or->condition('field_is_contact_information', $id);
    $condition_or->condition('field_ar_contact_information', $id);
    $query->condition($condition_or);
    $parent_ids = $query->execute();

    // Load the contact entity itself to create a duplicate.
    $contact = $storage->load($id);
    // The first element can maintain the original contact entity since all the
    // rest will retrieve a copy.
    array_shift($parent_ids);

    // @todo: Skip the first parent as they already have the original contact.
    foreach ($parent_ids as $parent_id) {
      $new_contact = clone $contact;
      $new_contact->set('id', NULL);
      $new_contact->enforceIsNew();
      $new_contact->save();

      // Directly delete the previous value and insert the new to avoid updating
      // the parent entity.
      $graphs = [
        'http://joinup.eu/collection/published',
        'http://joinup.eu/collection/draft',
        'http://joinup.eu/solution/published',
        'http://joinup.eu/solution/draft',
        'http://joinup.eu/asset_release/published',
        'http://joinup.eu/asset_release/draft',
      ];

      foreach ($graphs as $graph) {
        $old_id = $contact->id();
        $new_id = $new_contact->id();
        $query = <<<QUERY
WITH <{$graph}>
DELETE { <$parent_id> <http://www.w3.org/ns/dcat#contactPoint> <$old_id> }
INSERT { <$parent_id> <http://www.w3.org/ns/dcat#contactPoint> <$new_id> }
WHERE { <$parent_id> <http://www.w3.org/ns/dcat#contactPoint> <$old_id> }
QUERY;
        $connection->query($query);
      }
    }

    $sandbox['current']++;
  }

  $sandbox['#finished'] = (float) ($sandbox['current'] / $sandbox['max']);
  return "Finished processing {$sandbox['current']} out of {$sandbox['max']}.";
}
