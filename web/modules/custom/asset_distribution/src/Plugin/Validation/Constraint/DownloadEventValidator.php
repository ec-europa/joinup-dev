<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for 'DownloadEvent' constraint.
 */
class DownloadEventValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;

  /**
   * The query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The block plugin manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a featured content constraint validator.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   */
  public function __construct(QueryFactory $query_factory, BlockManagerInterface $block_manager) {
    $this->queryFactory = $query_factory;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\asset_distribution\Entity\DownloadEvent $entity */
    if (isset($entity)) {
      if (!$entity->getOwner() && !$entity->get('mail')->value) {
        $this->context->addViolation($constraint->userOrMail);
      }
    }
  }

}
