<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Updates distribution audience field.
 *
 * @MigrateSource(
 *   id = "distribution_audience"
 * )
 */
class DistributionAudience extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'string',
        'max_length' => 2048,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Distribution ID'),
      'solution' => $this->t('Solution'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $parents = [
      'solution' => 'field_is_distribution',
      'asset_release' => 'field_isr_distribution',
    ];

    $query = \Drupal::entityTypeManager()->getStorage('rdf_entity')->getQuery();
    $query->condition('rid', array_keys($parents), 'IN');

    $rows = [];
    foreach ($query->execute() as $rdf_id) {
      if ($rdf = Rdf::load($rdf_id)) {
        $bundle = $rdf->bundle();
        if ($bundle === 'asset_release') {
          // On releases, we need the parent solution.
          $solution_id = $rdf->field_isr_is_version_of->target_id;
          if (!$solution_id) {
            continue;
          }
        }
        else {
          $solution_id = $rdf->id();
        }

        $field_name = $parents[$bundle];
        // Iterate over child distributions.
        foreach ($rdf->get($field_name) as $item) {
          if (!empty($item->target_id)) {
            $rows[$item->target_id] = [
              'id' => $item->target_id,
              'solution' => $solution_id,
            ];
          }
        }
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'distribution_audience';
  }

}
