<?php

/**
 * @file
 * Contains \TallinnSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\FileTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions and related methods provided by the tallinn module.
 */
class TallinnSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use FileTrait;
  use RdfEntityTrait;
  use EntityTrait;

  /**
   * The tallinn initiative custom page.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $tallinnInitiativePage;

  /**
   * The tallinn collection.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $tallinnCollection;

  /**
   * Creates the standard 'Tallinn' collection.
   *
   * @beforeScenario @tallinn
   */
  public function setupTallinnData() {
    if (!Rdf::load(TALLINN_COMMUNITY_ID)) {
      $this->createCollection([
        'id' => TALLINN_COMMUNITY_ID,
        'label' => 'Tallinn declaration',
        'field_ar_state' => 'validated',
      ]);
    }

    // Create the page that will show all information related to the agreement.
    $data = [
      'title' => t('Tallinn initiative'),
      'type' => 'custom_page',
      'og_audience' => TALLINN_COMMUNITY_ID,
      'field_cp_content_listing' => [[
        "value" => [
          "fields" => [
            "field_cp_content_listing_content_type" => [
              "weight" => 0,
              "region" => "top",
            ],
          ],
          "enabled" => 1,
          "query_presets" => "sm_entity_bundle:tallinn_report",
          // Show all tallinn reports in one page.
          "limit" => "33",
        ],
      ],
      ],
    ];
    $this->tallinnInitiativePage = Node::create($data);
    $this->tallinnInitiativePage->save();
  }

  /**
   * Creates a collection from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new collection entity.
   *
   * @throws \Exception
   *   Thrown when a given image is not found.
   */
  protected function createCollection(array $values) {
    // Add images.
    $image_fields = ['field_ar_banner', 'field_ar_logo'];
    foreach ($image_fields as $field_name) {
      if (!empty($values[$field_name])) {
        foreach ($values[$field_name] as &$filename) {
          $filename = [$this->createFile($filename)->id()];
        }
      }
    }

    $collection = $this->createRdfEntity('collection', $values);
    $this->tallinnCollection = $collection;

    // We have to force reindex of affiliated solutions so the relationship
    // with this collection will be indexed in Solr.
    if (!$collection->get('field_ar_affiliates')->isEmpty()) {
      foreach ($collection->get('field_ar_affiliates')->referencedEntities() as $solution) {
        // Reindex the solution, so that the value of the "collection" computed
        // field will be populated and indexed correctly.
        $this->forceSearchApiReindex($solution);
      }
    }

    return $collection;
  }

  /**
   * Removes any content created for tallinn.
   *
   * @AfterScenario
   */
  public function cleanTallinEntities() {
    if (!empty($this->tallinnInitiativePage)) {
      $this->tallinnInitiativePage->skipNotification = TRUE;
      $this->tallinnInitiativePage->delete();
    }
    if (!empty($this->tallinnCollection)) {
      $this->tallinnCollection->skip_notification = TRUE;
      $this->tallinnCollection->delete();
    }
  }

}
