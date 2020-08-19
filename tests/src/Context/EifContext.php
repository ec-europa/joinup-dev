<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\eif\EifInterface;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\node\Entity\Node;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\sparql_entity_storage\UriEncoder;
use Drupal\taxonomy\Entity\Term;

/**
 * Behat step definitions and related methods provided by the eif module.
 */
class EifContext extends RawDrupalContext {

  use SearchTrait;
  use StringTranslationTrait;

  /**
   * Creates the standard 'EIF' solution and a random parent collection.
   *
   * @beforeScenario @eif_community
   */
  public function setupEifData(BeforeScenarioScope $scope): void {
    // Create two policy domain terms.
    Term::create([
      'vid' => 'policy_domain',
      'tid' => 'http://example.com/term/1',
      'name' => 'Term 1',
    ])->save();

    // Create an owner.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner',
      'field_owner_name' => 'Owner',
    ])->save();

    Rdf::create([
      'rid' => 'contact_information',
      'id' => 'http://example.com/contact',
      'field_contact_name' => 'Contact name',
      'field_contact_email' => 'contact.email@example.com',
    ])->save();

    // Create the NIFO sample collection.
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://nifo.collection',
      'label' => 'NIFO collection',
      'field_ar_state' => 'validated',
      'field_policy_domain' => 'http://example.com/term/1',
      'field_ar_owner' => 'http://example.com/owner',
      'field_ar_contact_information' => 'http://example.com/contact',
    ])->save();

    // Create the EIF Toolbox solution.
    $solution = Rdf::create([
      'rid' => 'solution',
      'id' => EifInterface::EIF_ID,
      'label' => 'EIF Toolbox',
      'collection' => 'http://nifo.collection',
      'field_is_state' => 'validated',
      'field_policy_domain' => 'http://example.com/term/1',
      'field_is_owner' => 'http://example.com/owner',
      'field_is_contact_information' => 'http://example.com/contact',
    ]);
    $solution->save();

    Node::create([
      'type' => 'custom_page',
      'nid' => EifInterface::EIF_SOLUTIONS_NID,
      'title' => 'Solutions',
      'og_audience' => EifInterface::EIF_ID,
      'field_paragraphs_body' => Paragraph::create([
        'type' => 'simple_paragraph',
        'field_body' => [
          'value' => 'Currently available supporting solutions that can be used as added components that help build interoperability solutions.',
          'format' => 'content_editor',
        ],
      ]),
    ])->save();

    $instances = \Drupal::entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties([
      'type' => 'navigation',
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $solution->id(),
    ]);
    $instance = reset($instances);
    $menu_name = "ogmenu-{$instance->id()}";
    MenuLinkContent::create([
      'title' => $this->t('Recommendations'),
      'menu_name' => $menu_name,
      'link' => [
        'uri' => Url::fromRoute('view.eif_recommendation.page', [
          'rdf_entity' => UriEncoder::encodeUrl(EifInterface::EIF_ID),
        ])->toUriString(),
      ],
      'weight' => 4,
    ])->save();

    // Ensure the taxonomy terms are indexed.
    $vids = [
      'eif_conceptual_model',
      'eif_interoperability_layer',
      'eif_principle',
      'eif_recommendation',
    ];

    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('taxonomy_term');
    $tids = $storage->getQuery()->condition('vid', $vids, 'IN')->execute();
    foreach ($storage->loadMultiple($tids) as $term) {
      ContentEntity::indexEntity($term);
    }

    $index = \Drupal::entityTypeManager()->getStorage('search_api_index')->load('published');
    $index->indexItems(-1, 'entity:taxonomy_term');
  }

  /**
   * Clears the content created for the purpose of this test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the created entities could not be deleted.
   *
   * @afterScenario @eif_community
   */
  public function cleanEifData(AfterScenarioScope $scope): void {
    // Temporarily disable the feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    $rdf_ids = [
      EifInterface::EIF_ID,
      'http://nifo.collection',
      'http://example.com/owner',
      'http://example.com/contact',
    ];
    foreach ($rdf_ids as $id) {
      $entity = Rdf::load($id);
      $entity->skip_notification = TRUE;
      $entity->delete();
    }

    Term::load('http://example.com/term/1')->delete();
    $this->enableCommitOnUpdate();
  }

}
