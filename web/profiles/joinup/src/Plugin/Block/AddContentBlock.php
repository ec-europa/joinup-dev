<?php

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\og\Og;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *  id = "add_content_block",
 *  admin_label = @Translation("Add content"),
 * )
 */
class AddContentBlock extends BlockBase {

  /**
   * The collection to join.
   *
   * @var \Drupal\rdf_entity\RdfInterface $collection
   */
  protected $collection;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface $currentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface $account
   */
  protected $account;

  /**
   * The OG membership.
   *
   * @var \Drupal\og\Entity\OgMembership $membership
   */
  protected $membership;

  /**
   * Constructs a AddContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = \Drupal::routeMatch();

    // @todo: This should be restricted to collection rdf_entities only.
    // Retrieve the collection from the route.
    $this->collection = $this->currentRouteMatch->getParameter('rdf_entity');
    $this->account = User::load(\Drupal::currentUser()->id());
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      'collection' => [
        '#type' => 'link',
        '#title' => $this->t('Propose collection'),
        '#url' => Url::fromRoute('collection.propose_form'),
        '#attributes' => ['class' => ['button', 'button--small']],
      ],
    ];

    // This check has to occur here so that the link can be cached correctly
    // for each page.
    if (
      !($this->account->isAnonymous())
      && $this->currentRouteMatch->getRouteName() == 'entity.rdf_entity.canonical'
      && $this->collection->bundle() == 'collection'
    ) {
      $build['custom_page'] = [
        '#type' => 'link',
        '#title' => $this->t('Add custom page'),
        '#url' => Url::fromRoute('custom_page.collection_custom_page.add',
          ['rdf_entity' => $this->collection->sanitizedId()]),
        '#attributes' => ['class' => ['button', 'button--small']],
        '#access' => Og::isMember($this->collection, $this->account),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    if (!empty($this->collection)
      && !$this->account->isAnonymous()
    ) {
      // Load the membership.
      $results = Og::membershipStorage()->loadByProperties([
        'type' => OgMembershipInterface::TYPE_DEFAULT,
        'entity_type' => $this->collection->getEntityTypeId(),
        'entity_id' => $this->collection->id(),
        'uid' => $this->account->id(),
      ]);
      $this->membership = reset($results);

      // Cache per membership.
      if ($this->membership) {
        $tags = Cache::mergeTags($tags, $this->membership->getCacheTags());
      }

      // Build our custom cache tag to invalidate cache on membership insert.
      // This is to avoid rebuilding cache for all users in each membership
      // insert.
      $tag = $this->collection->getEntityTypeId()
        . ':' . $this->account->getEntityTypeId()
        . ':' . $this->account->id();
      $tags = Cache::mergeTags($tags, [$tag]);
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $context = parent::getCacheContexts();
    return Cache::mergeContexts($context, ['user', 'route:rdf_entity']);
  }

}
