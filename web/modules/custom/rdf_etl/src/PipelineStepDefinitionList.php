<?php
/**
 * Created by PhpStorm.
 * User: sander
 * Date: 04.01.18
 * Time: 17:38
 */

namespace Drupal\rdf_etl;


class PipelineStepDefinitionList implements \Iterator {
  protected $list = [];
  protected $position = 0;

  public function add(String $name) : PipelineStepDefinition {
    $this->list[] = new PipelineStepDefinition($name, end($this->list));
    $this->position = key($this->list);
    return $this->list[$this->position];
  }

  public function first() : PipelineStepDefinition{
    return $this->list[0];
  }

  public function current() : PipelineStepDefinition {
    if (!$this->valid()) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$this->position];
  }

  public function get($position) : PipelineStepDefinition {
    if (!isset($this->list[$position])) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$position];
  }

  public function next() {
    ++$this->position;
  }

  public function key() {
    return $this->position;
  }

  public function valid() {
    return isset($this->list[$this->position]);
  }

  public function rewind() {
    $this->position = 0;
  }

  public function seek($position) {
    $this->position = $position;
    return $this->current();
  }


}
