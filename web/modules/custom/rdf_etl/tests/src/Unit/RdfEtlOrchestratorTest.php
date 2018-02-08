<?php

namespace Drupal\Tests\rdf_etl\Unit;

use Drupal\Core\Form\FormBuilder;
use Drupal\rdf_etl\EtlOrchestrator;
use Drupal\rdf_etl\EtlState;
use Drupal\rdf_etl\EtlStateManager;
use Drupal\rdf_etl\Plugin\EtlDataPipelineManager;
use Drupal\rdf_etl\Plugin\EtlProcessStepManager;
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
   * @var \Drupal\rdf_etl\Plugin\EtlDataPipelineManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $etlDataPipelineManager;

  /**
   * The process step plugin manager.
   *
   * @var \Drupal\rdf_etl\Plugin\EtlProcessStepManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $etlProcessStepManager;

  /**
   * The ETL state manager.
   *
   * @var \Drupal\rdf_etl\EtlStateManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $etlStateManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->etlDataPipelineManager = $this->prophesize(EtlDataPipelineManager::class);
    $this->etlProcessStepManager = $this->prophesize(EtlProcessStepManager::class);
    $this->etlStateManager = $this->prophesize(EtlStateManager::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
  }

  /**
   * Tests instantiating the orchestrator.
   *
   * @covers ::__construct
   */
  public function testInstance() {
    $orchestrator = new EtlOrchestrator($this->etlDataPipelineManager->reveal(), $this->etlProcessStepManager->reveal(), $this->etlStateManager->reveal(), $this->formBuilder->reveal());
    $this->assertInstanceOf(EtlOrchestrator::class, $orchestrator);
  }

  /**
   * Test resetting the state machine.
   *
   * @covers ::reset
   */
  public function testReset() {
    $state = $this->etlStateManager;
    $state->reset()->shouldBeCalled();
    $orchestrator = new EtlOrchestrator($this->etlDataPipelineManager->reveal(), $this->etlProcessStepManager->reveal(), $state->reveal(), $this->formBuilder->reveal());
    $orchestrator->reset();
  }

  /**
   * Test running the state machine: Build the form.
   *
   * @covers ::run
   */
  public function testRun() {
    $state = new EtlState('demo_pipe', 0);
    $state_manager = $this->etlStateManager;
    $state_manager->isPersisted()->willReturn(TRUE);
    $state_manager->state()->willReturn($state);
    // As we are simply building the form, no state is persisted.
    $state_manager->setState()->shouldNotBeCalled();

    $pipelinePlugin = new TestDataPipeline([], '', []);

    $pipelineManager = $this->etlDataPipelineManager;
    $pipelineManager->createInstance('demo_pipe')->willReturn($pipelinePlugin);

    $orchestrator = new EtlOrchestrator($pipelineManager->reveal(), $this->etlProcessStepManager->reveal(), $state_manager->reveal(), $this->formBuilder->reveal());
    $orchestrator->run();
  }

}
