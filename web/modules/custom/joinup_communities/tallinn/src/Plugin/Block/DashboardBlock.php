<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Plugin\Block;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\tallinn\DashboardAccessInterface;
use Drupal\tallinn\Tallinn;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Block showing the Tallinn Agreement reporting progress.
 *
 * @Block(
 *   id = "tallinn_dashboard",
 *   admin_label = @Translation("Tallinn Agreement reporting progress"),
 * )
 */
class DashboardBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The dashboard access service.
   *
   * @var \Drupal\tallinn\DashboardAccessInterface
   */
  protected $dashboardAccess;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new plugin class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\tallinn\DashboardAccessInterface $dashboard_access
   *   The dashboard access service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DashboardAccessInterface $dashboard_access, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->dashboardAccess = $dashboard_access;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('tallinn.dashbord.access'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = $this->buildRenderArray();
    $this->addSelectors($build);
    return [$build];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    // Add the collection cache tags.
    $tags = Cache::mergeTags(parent::getCacheTags(), Rdf::load(TALLINN_COMMUNITY_ID)->getCacheTags());

    // Merge the tags of each report.
    $storage = $this->entityTypeManager->getStorage('node');
    foreach ($storage->loadByProperties(['type' => 'tallinn_report']) as $report) {
      $tags = Cache::mergeTags($tags, $report->getCacheTags());
    }

    // Add the cache tags of 'tallinn.settings' object.
    return Cache::mergeTags($tags, $this->configFactory->get('tallinn.settings')->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    return $this->dashboardAccess->access($account);
  }

  /**
   * Builds the block render array.
   *
   * @return array
   *   Render array
   */
  protected function buildRenderArray(): array {
    return [
      '1' => [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'tallinn-chart',
          ],
        ],
        '#attached' => [
          'library' => [
            'tallinn/dashboard',
          ],
          'drupalSettings' => [
            'tallinn' => [
              'dataEndpoint' => Url::fromRoute('tallinn.dashboard')->setAbsolute()->toString(),
            ],
          ],
        ],
        '1.1' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => [
              'mdl-grid',
            ],
          ],
          '1.1.1' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'mdl-cell',
                'mdl-cell--8-col',
                'mdl-typography--text-center',
              ],
            ],
            '1.1.1.1' => [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'tallinn-chart__container',
              ],
              '1.1.1.1.1' => [
                '#markup' => '...',
              ],
            ],
            '1.1.1.2' => [
              '#type' => 'button',
              '#attributes' => [
                'class' => [
                  'mdl-button',
                  'mdl-js-button',
                  'tallinn-chart__button--csv',
                ],
              ],
              '#value' => $this->t('Download csv data'),
            ],
          ],
          '1.1.2' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'mdl-cell',
                'mdl-cell--4-col',
              ],
            ],
            '1.1.2.1' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => [
                  'mdl-layout',
                  'tallinn-chart__selects',
                ],
              ],
            ],
            '1.1.2.2' => [
              '#type' => 'fieldset',
              '#title' => $this->t('Legend'),
              '#attributes' => [
                'class' => [
                  'tallinn-chart__legend',
                ],
              ],
              '1.1.2.2.1' => [
                // We cannot render an empty list with the 'item_list' theme.
                // @see web/core/modules/system/templates/item-list.html.twig
                '#type' => 'html_tag',
                '#tag' => 'ul',
                '#attributes' => [
                  'class' => [
                    'mdl-list',
                  ],
                ],
              ],
            ],
          ],
          '1.1.3' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                'mdl-layout',
              ],
            ],
            '1.1.3.1' => [
              '#type' => 'html_tag',
              '#tag' => 'h4',
              '#attributes' => [
                'class' => [
                  'tallinn-chart__country',
                  'mdl-typography--display-1',
                ],
              ],
            ],
            '1.1.3.2' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => [
                'class' => [
                  'tallinn-chart__description',
                  'mdl-typography--title',
                ],
              ],
            ],
            '1.1.3.3' => [
              '#type' => 'html_tag',
              '#tag' => 'div',
              '#attributes' => [
                'class' => [
                  'tallinn-chart__body',
                  'mdl-typography--body-1',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Adds the required select elements under 1.1.2.1.
   *
   * @param array $build
   *   The build render array.
   */
  protected function addSelectors(array &$build): void {
    foreach ([1, 2, 3] as $select) {
      switch ($select) {
        case 1:
          $options = [
            'All members' => $this->t('All member states'),
          ] + Tallinn::COUNTRIES;
          $label = '';
          break;

        case 2:
          $options = [
            '' => $this->t('No selection'),
            'All members' => $this->t('All member states'),
          ] + Tallinn::COUNTRIES;
          $label = $this->t('compared to');
          break;

        case 3:
          $options = ['' => $this->t('Principles')];
          $label = $this->t('from');
      }

      $build['1']['1.1']['1.1.2']['1.1.2.1']["1.1.2.1.$select"] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'tallinn-chart__selector-container',
          ],
        ],
        [
          '#markup' => $label,
          '#prefix' => '<span>',
          '#suffix' => '</span>',
        ],
        [
          '#type' => 'select',
          '#options' => $options,
          '#attributes' => [
            'id' => "select$select",
            'class' => [
              'tallinn-chart__selector',
            ],
          ],
        ],
      ];
    }
  }

}
