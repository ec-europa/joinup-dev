<?php

namespace Drupal\rdf_etl;

class EtlState {
    public $step;
    public function __construct(String $step, String $pipeline) {
        $this->step = $step;
        $this->pipeline = $pipeline;
    }
}
