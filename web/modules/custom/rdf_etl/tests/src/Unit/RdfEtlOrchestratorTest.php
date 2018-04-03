<?php

/**
 * @file
 * Tests the orchestrator.
 */

namespace Drupal\Tests\rdf_etl\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Messenger\Messenger;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginBase;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginBase;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager;
use Drupal\rdf_etl\RdfEtlOrchestrator;
use Drupal\rdf_etl\RdfEtlState;
use Drupal\rdf_etl\RdfEtlStateManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the data pipeline processor.
 *
 * @group rdf_etl
 * @coversDefaultClass \Drupal\rdf_etl\RdfEtlOrchestrator
 */
class RdfEtlOrchestratorTest extends UnitTestCase {

  /**
   * The data pipeline plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pipelinePluginManager;

  /**
   * The process step plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stepPluginManager;

  /**
   * The state manager.
   *
   * @var \Drupal\rdf_etl\RdfEtlStateManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stateManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $formBuilder;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pipelinePluginManager = $this->prophesize(RdfEtlPipelinePluginManager::class);
    $this->stepPluginManager = $this->prophesize(RdfEtlStepPluginManager::class);
    $this->stateManager = $this->prophesize(RdfEtlStateManager::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
    $this->messenger = $this->prophesize(Messenger::class);
  }

  /**
   * Tests instantiating the orchestrator.
   *
   * @covers ::__construct
   */
  public function testInstance() {
    $this->assertInstanceOf(RdfEtlOrchestrator::class, $this->createOrchestrator());
  }

  /**
   * Test resetting the state machine.
   *
   * @covers ::reset
   */
  public function testReset() {
    $this->stateManager->reset()->shouldBeCalled();
    $this->createOrchestrator()->reset();
  }

  /**
   * Test running the state machine: Build the form.
   *
   * @covers ::run
   */
  public function testRun() {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('plugin.manager.rdf_etl_step', $this->stepPluginManager->reveal());
    \Drupal::setContainer($container);

    $state = new RdfEtlState('demo_pipe', 0);
    $state_manager = $this->stateManager;
    $state_manager->isPersisted()->willReturn(TRUE);
    $state_manager->state()->willReturn($state);
    $state_manager->reset()->shouldBeCalled();

    $this->stepPluginManager->hasDefinition('test_step')->willReturn(TRUE);
    $definition = ['label' => 'Bar', 'steps' => ['test_step']];
    $this->pipelinePluginManager->createInstance('demo_pipe')
      ->willReturn(new TestPipeline([], '', $definition, $this->stepPluginManager->reveal()));

    (new TestOrchestrator(
      $this->pipelinePluginManager->reveal(),
      $this->stepPluginManager->reveal(),
      $this->stateManager->reveal(),
      $this->formBuilder->reveal(),
      $this->messenger->reveal()
    ))->run('demo_pipe');
  }

  /**
   * Initializes a new orchestrator object.
   *
   * @return \Drupal\rdf_etl\RdfEtlOrchestratorInterface
   *   The new orchestrator object.
   */
  protected function createOrchestrator() {
    return new RdfEtlOrchestrator(
      $this->pipelinePluginManager->reveal(),
      $this->stepPluginManager->reveal(),
      $this->stateManager->reveal(),
      $this->formBuilder->reveal(),
      $this->messenger->reveal()
    );
  }

}

/**
 * Testing step plugin.
 */
class TestStep extends RdfEtlStepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {}

}

/**
 * Testing orchestrator.
 *
 * Used to replace the ::getStepInstance() method with a mock.
 */
class TestOrchestrator extends RdfEtlOrchestrator {

  /**
   * {@inheritdoc}
   */
  protected function getStepInstance(RdfEtlState $state): RdfEtlStepInterface {
    return new TestStep([], '', ['label' => 'Foo']);
  }

}

/**
 * Testing pipeline plugin.
 */
class TestPipeline extends RdfEtlPipelinePluginBase {}
