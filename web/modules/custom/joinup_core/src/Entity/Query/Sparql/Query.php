<?php

namespace Drupal\joinup_core\Entity\Query\Sparql;

use Drupal\rdf_entity\Entity\Query\Sparql\Query as RdfEntityQuery;

/**
 * Provides workaround for 'solution' and 'asset_release' bundles.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3126
 */
class Query extends RdfEntityQuery {

  /**
   * {@inheritdoc}
   */
  public function condition($property, $value = NULL, $operator = '=', $langcode = NULL) {
    if ($property === 'rid' && in_array($operator, ['=', 'IN'])) {
      if ($operator === '=') {
        $bundle = $value;
      }
      // IN() with a single item. This is equivalent to '='.
      elseif (is_array($value) && count($value) === 1) {
        $bundle = reset($value);
      }
      // Multiple items. This is tricky!
      elseif (is_array($value)) {
        // @todo This is more complex. Implement it if a use case appears. The
        //   basic approach would be to split:
        //   @code
        //   ->condition('rid', ['solution', 'asset_release', others...], 'IN')
        //   @endcode
        //   into:
        //   @code
        //   ->condition(
        //   (new Condition('OR'))
        //   ->condition('rid', 'solution')
        //   ->condition('rid', 'asset_release')
        //   ->condition('rid', [others...], 'IN')
        //   )
        //   @endcode
        return parent::condition($property, $value, $operator, $langcode);
      }
      else {
        return parent::condition($property, $value, $operator, $langcode);
      }

      if ($bundle === 'asset_release') {
        $this->condition->exists('field_isr_is_version_of', $langcode);
      }
      elseif ($bundle === 'solution') {
        $this->condition->notExists('field_isr_is_version_of', $langcode);
      }
    }

    return parent::condition($property, $value, $operator, $langcode);
  }

}
