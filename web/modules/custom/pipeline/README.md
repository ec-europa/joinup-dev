# Pipeline

The _Pipeline_ module allows splitting processes in steps and then running them
via an orchestrator service. Pipelines are defined as plugins of type
`pipeline_pipeline`. A pipeline consists in a number of pipeline steps. Each
step is a plugin of type `pipeline_step`.
