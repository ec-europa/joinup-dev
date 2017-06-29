<?php

namespace DrupalProject\Phing;

require_once 'phing/Task.php';

/**
 * Sets the {comment}, {file_managed}, {node} and {users} tabled auto-increment.
 *
 * For the installed site, the auto-increment values for the next tables are set
 * as follows:
 * - {comment}.cid -> MAX(D6.comments.cid) + 500,000.
 * - {file_managed}.fid -> MAX(D6.files.fid) + 500,000.
 * - {node}.nid -> MAX(D6.node.nid) + 500,000.
 * - {users}.uid -> MAX(D6.users.uid) + 500,000.
 */
class SetMysqlAutoincrement extends \Task {

  /**
   * Sets table auto-increment.
   *
   * @todo For performance reasons on the CI containers we hardcode the values
   *   for auto-increment, instead of computing them. The hardcoded values are
   *   safely covering the actual database maximum values.
   */
  public function main() {
    $project = $this->getProject();

    // $d6_host = $project->getProperty('migration.db.host');
    // $d6_port = $project->getProperty('migration.db.port');
    // $d6_username = $project->getProperty('migration.db.user');
    // $d6_passwd = $project->getProperty('migration.db.password');
    // $d6_dbname = $project->getProperty('migration.db.name');

    $d8_host = $project->getProperty('drupal.db.host');
    $d8_port = $project->getProperty('drupal.db.port');
    $d8_username = $project->getProperty('drupal.db.user');
    $d8_passwd = $project->getProperty('drupal.db.password');
    $d8_dbname = $project->getProperty('drupal.db.name');

    // PHP 7.x uses the pdo_mysql extension by default, while PHP 5.x defaults
    // to mysqli.
    if (class_exists('\mysqli')) {
      // $d6 = new \mysqli($d6_host, $d6_username, $d6_passwd, $d6_dbname, $d6_port);
      $d8 = new \mysqli($d8_host, $d8_username, $d8_passwd, $d8_dbname, $d8_port);
    }
    elseif (class_exists('\PDO')) {
      $d8 = new \PDO("mysql:host=$d8_host;port=$d8_port;dbname=$d8_dbname", $d8_username, $d8_passwd);
    }
    else {
      throw new \Exception('No supported MySQL extension found.');
    }

    $safety_margin = 500000;

    // Comments.
    // $result = $d6->query("SELECT MAX(cid) FROM comments");
    // $max_cid = $result->fetch_assoc()['MAX(cid)'] + $safety_margin;
    $max_cid = $safety_margin;
    $d8->query("ALTER TABLE comment AUTO_INCREMENT=$max_cid");

    // Files.
    // $result = $d6->query("SELECT MAX(fid) FROM files");
    // $max_fid = $result->fetch_assoc()['MAX(fid)'] + $safety_margin;
    $max_fid = $safety_margin;
    $d8->query("ALTER TABLE file_managed AUTO_INCREMENT=$max_fid");

    // Nodes.
    // $result = $d6->query("SELECT MAX(nid) FROM node");
    // $max_nid = $result->fetch_assoc()['MAX(nid)'] + $safety_margin;
    $max_nid = 700000;
    $d8->query("ALTER TABLE node AUTO_INCREMENT=$max_nid");

    // Users.
    // $result = $d6->query("SELECT MAX(uid) FROM users");
    //$max_uid = $result->fetch_assoc()['MAX(uid)'] + $safety_margin;
    $max_uid = 700000;
    // The {users} table is not using auto-increment but {sequences}.
    // @see \Drupal\Core\Database\Driver\mysql\Connection::nextId()
    $d8->query("INSERT INTO sequences (value) VALUES ($max_uid) ON DUPLICATE KEY UPDATE value = value");
    $d8->query("INSERT INTO sequences () VALUES ()");
  }

}
