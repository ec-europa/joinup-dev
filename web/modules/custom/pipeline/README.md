# Pipeline

The _Pipeline_ module allows splitting processes in steps and then running them
via an orchestrator service. Pipelines are defined as plugins of type
`pipeline_pipeline`. A pipeline consists in a number of pipeline steps. Each
step is a plugin of type `pipeline_step`.


### @todo

* Add an example sub-module implementing a pipeline and several steps.
* Improve testing:
  * Unit-test the pipeline as iterator.
  * Browser test the entire flow.
* Feature: Steps that are running as batch processes.
* Allow data to persist and to be collected across the steps.
* Show a summary on success.
* Extend this README.md with more API docs.
* Cutting edge: Make the pipeline iterator really smart, allowing next() result
  to be computed based on the decision of pipeline and step methods. In this way
  the pipeline flow is dynamic, it can have a conditional path depending on the
  data gathered during the execution and allowing even loops.
