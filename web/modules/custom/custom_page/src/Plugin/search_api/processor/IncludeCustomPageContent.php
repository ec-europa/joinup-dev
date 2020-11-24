<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Plugin\search_api\processor;

use Drupal\custom_page\CustomPageProviderInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Includes the content of custom pages in indexed group content.
 *
 * In many collections and solutions the bulk of the information about what the
 * project is about is contained in their custom pages rather than the "About"
 * page. This means that when users are searching for relevant keywords they
 * often do not get the group as a search result, but rather a custom page which
 * might have a title that is unclear when viewed on its own.
 *
 * This processor enriches the indexed data of groups with the content of their
 * custom pages.
 *
 * @SearchApiProcessor(
 *   id = "include_custom_page_content",
 *   label = @Translation("Include custom page content"),
 *   description = @Translation("Improves searchability of collections and solutions by including the content of custom pages."),
 *   stages = {
 *     "preprocess_index" = 0,
 *   },
 * )
 */
class IncludeCustomPageContent extends ProcessorPluginBase {

  /**
   * The custom page provider service.
   *
   * @var \Drupal\custom_page\CustomPageProviderInterface
   */
  protected $customPageProvider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->setCustomPageProvider($container->get('custom_page.provider'));
    return $processor;
  }

  /**
   * Sets the custom page provider.
   *
   * @param \Drupal\custom_page\CustomPageProviderInterface $custom_page_provider
   *   The custom page provider.
   *
   * @return $this
   *   The processor, for chaining.
   */
  public function setCustomPageProvider(CustomPageProviderInterface $custom_page_provider): self {
    $this->customPageProvider = $custom_page_provider;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'rdf_entity') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items): void {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      $entity = $item->getOriginalObject()->getValue();

      // Only process groups.
      if (!JoinupGroupHelper::isGroup($entity)) {
        continue;
      }

      foreach ($this->customPageProvider->getCustomPagesByGroupId($entity->id()) as $custom_page) {
        // Index the title and body text of the custom page data inside the
        // collection or solution.
        $label_field = $item->getField('label');
        $label_field->addValue($custom_page->label());

        $body_field_name = $entity->bundle() === 'collection' ? 'field_ar_description' : 'field_is_description';
        $body_field = $item->getField($body_field_name);

        // The search_api indexes fields for each entity (there is an HTML
        // output but it is used differently). For paragraphs, since this is a
        // reference field, we are indexing the field_body field. The paragraph
        // itself is referenced by the custom page through the
        // field_paragraphs_body field. Iterate through all items in the field
        // and add the markup to the group description.
        //
        // @todo: Automatically add the entries needed.
        // @see: https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-5962
        if (!$custom_page->get('field_paragraphs_body')->isEmpty()) {
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          foreach ($custom_page->get('field_paragraphs_body')->referencedEntities() as $paragraph) {
            $custom_page_paragraph_body_list = $paragraph->get('field_body');
            foreach ($custom_page_paragraph_body_list as $list_item) {
              if (!$list_item->isEmpty()) {
                $body_field->addValue(check_markup($list_item->value, $list_item->format));
              }
            }
          }
        }
      }
    }
  }

}
