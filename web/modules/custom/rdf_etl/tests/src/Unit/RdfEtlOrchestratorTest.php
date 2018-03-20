<?php

/**
 * @file
 * Tests the orchestrator.
 */

namespace Drupal\Tests\rdf_etl\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Messenger\Messenger;
use Drupal\rdf_etl\EtlOrchestrator;
use Drupal\rdf_etl\EtlState;
use Drupal\rdf_etl\EtlStateManager;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginBase;
use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginManager;
use Drupal\rdf_etl\Plugin\RdfEtlStepInterface;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginBase;
use Drupal\rdf_etl\Plugin\RdfEtlStepPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the data pipeline processor.
 *
 * @group rdf_etl
 * @coversDefaultClass \Drupal\rdf_etl\EtlOrchestrator
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
   * @var \Drupal\rdf_etl\EtlStateManager|\Prophecy\Prophecy\ObjectProphecy
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
    $this->stateManager = $this->prophesize(EtlStateManager::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
    $this->messenger = $this->prophesize(Messenger::class);
  }

  /**
   * Tests instantiating the orchestrator.
   *
   * @covers ::__construct
   */
  public function testInstance() {
    $this->assertInstanceOf(EtlOrchestrator::class, $this->createOrchestrator());
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
    \Drupal::setContainer($container);

    $state = new EtlState('demo_pipe', 0);
    $state_manager = $this->stateManager;
    $state_manager->isPersisted()->willReturn(TRUE);
    $state_manager->state()->willReturn($state);
    $state_manager->reset()->shouldBeCalled();

    $this->pipelinePluginManager->createInstance('demo_pipe')
      ->willReturn(new TestPipeline([], '', ['label' => 'Bar']));

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
   * @return \Drupal\rdf_etl\EtlOrchestratorInterface
   *   The new orchestrator object.
   */
  protected function createOrchestrator() {
    return new EtlOrchestrator(
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
  public function execute(array &$data): void {}

}

/**
 * Testing orchestrator.
 *
 * Used to replace the ::getStepInstance() method with a mock.
 */
class TestOrchestrator extends EtlOrchestrator {

  /**
   * {@inheritdoc}
   */
  protected function getStepInstance(EtlState $state): RdfEtlStepInterface {
    return new TestStep([], '', ['label' => 'Foo']);
  }

}

/**
 * Testing pipeline plugin.
 */
class TestPipeline extends RdfEtlPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function initStepDefinition(): void {
    $this->steps->add('test_step');
  }

}
