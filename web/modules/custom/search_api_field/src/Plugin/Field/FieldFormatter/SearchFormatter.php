<?php

namespace Drupal\search_api_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Entity\Index;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'link' formatter.
 *
 * @FieldFormatter(
 *   id = "search",
 *   label = @Translation("Search"),
 *   field_types = {
 *     "search"
 *   }
 * )
 */
class SearchFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $settings = $this->getSettings();

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $request = \Drupal::request();
    $entity = $items->getEntity();
    // Avoid infinite recursion when a search node is shown as a result.
    if ($entity->do_not_recurse) {
      return [];
    }
    /** @var \Drupal\field\Entity\FieldConfig $field_definition */
    $field_definition = $items->getFieldDefinition();
    $index = $field_definition->getSetting('index');
    /* @var $search_api_index \Drupal\search_api\IndexInterface */
    $search_api_index = Index::load($index);
    // @todo Get from field settings.
    $limit = 10;

    // Create the query.
    $options = [
      'limit' => $limit,
      'offset' => !is_null($request->get('page')) ? $request->get('page') * $limit : 0,
      'search id' => 'search_api_field:' . $field_definition->getTargetEntityTypeId() . '.' . $field_definition->getName(),
    ];
    $query = $search_api_index->query($options);

    $query->setParseMode('direct');

    // Search for keys.
    if (!empty($keys)) {
      $query->keys($keys);
    }

    // Index fields.
    $query->setFulltextFields();

    $result = $query->execute();
    $items = $result->getResultItems();

    /* @var $item \Drupal\search_api\Item\ItemInterface*/
    $results = array();
    foreach ($items as $item) {

      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $item->getOriginalObject()->getValue();
      if (!$entity) {
        continue;
      }
      $entity->do_not_recurse = TRUE;

      // Render as view modes.
      if (TRUE) {
        $key = 'entity:' . $entity->getEntityTypeId() . '_' . $entity->bundle();
        // @todo $search_api_page->getViewModeConfiguration();
        $view_mode_configuration = [];
        $view_mode = isset($view_mode_configuration[$key]) ? $view_mode_configuration[$key] : 'default';
        // @todo Inject...
        $results[] = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId())->view($entity, $view_mode);
      }
      // Render as snippets.
      else {
        $results[] = array(
          '#theme' => 'search_api_page_result',
          '#item' => $item,
          '#entity' => $entity,
        );
      }
    }

    if (!empty($results)) {

      $build['#search_title'] = array(
        '#markup' => $this->t('Search results'),
      );

      $build['#no_of_results'] = array(
        '#markup' => $this->formatPlural($result->getResultCount(), '1 result found', '@count results found'),
      );

      $build['#results'] = $results;

      // Build pager.
      pager_default_initialize($result->getResultCount(), $limit);
      $build['#pager'] = array(
        '#type' => 'pager',
      );
    }
    else {
      $build['#no_results_found'] = array(
        '#markup' => $this->t('Your search yielded no results.'),
      );

      $build['#search_help'] = array(
        '#markup' => $this->t('<ul>
<li>Check if your spelling is correct.</li>
<li>Remove quotes around phrases to search for each word individually. <em>bike shed</em> will often show more results than <em>&quot;bike shed&quot;</em>.</li>
<li>Consider loosening your query with <em>OR</em>. <em>bike OR shed</em> will often show more results than <em>bike shed</em>.</li>
</ul>'),
      );
    }

    $results['#cache'] = [
      'max-age' => [
        // The "current user" is used above, which depends on the request,
        // so we tell Drupal to vary by the 'user' cache context.
        0,
      ],
    ];

    return $results;
  }

}
