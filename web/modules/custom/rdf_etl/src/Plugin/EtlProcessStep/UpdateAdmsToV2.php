<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\rdf_etl\ProcessStepBase;

/**
 * Defines a step that updates the imported data from ADMSv1 to ADMSv2.
 *
 * @EtlProcessStep(
 *   id = "update_adms_to_v2",
 *   label = @Translation("Update ADMSv1 to v2"),
 * )
 */
class UpdateAdmsToV2 extends ProcessStepBase {

  /**
   * {@inheritdoc}
   */
  public function execute(array $data): void {
    // @todo Implement ADMS update in ISAICP-4210.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4210
  }

}
