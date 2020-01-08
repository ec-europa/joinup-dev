<?php

declare(strict_types = 1);

namespace DrupalProject\Phing;

use Drupal\Core\Serialization\Yaml;

/**
 * Builds the docker-compose.yml file used to start the eCAS Mock Server.
 */
class EuLoginBuildEcasMockDockerCompose extends \Task {

  /**
   * The location where the file will be created.
   *
   * @var string
   */
  protected $destination;

  /**
   * The Docker server version.
   *
   * @var string
   */
  protected $dockerServerVersion;

  /**
   * {@inheritdoc}
   */
  public function main(): void {
    $properties = $this->getProject()->getProperties();

    // @todo Add MacOS NFS optimisation.
    // @see https://medium.com/@sean.handley/how-to-set-up-docker-for-mac-with-native-nfs-145151458adc
    $compose = [
      'version' => '2',
      'services' => [
        'authentication' => [
          'image' => $properties['eulogin.ecas_mock.docker.image'],
          'volumes' => [
            "{$properties['eulogin.ecas_mock.config_fixtures.dir']}:/data/ecas-mock-server-shared",
          ],
          'ports' => [
            '7001:7001',
            '7002:7002',
            '7003:7003',
          ],
        ],
      ],
    ];

    file_put_contents("{$this->destination}/docker-compose.yml", Yaml::encode($compose));
    $this->log("Created {$this->destination}/docker-compose.yml.");
  }

  /**
   * Sets the destination directory.
   *
   * @param string $destination
   *   The destination directory.
   */
  public function setDestination(string $destination): void {
    $this->destination = $destination;
  }

  /**
   * Sets the Docker Server version.
   *
   * @param string $dockerServerVersion
   *   The he Docker Server version.
   */
  public function setDockerServerVersion(string $dockerServerVersion): void {
    $this->dockerServerVersion = $dockerServerVersion;
  }

}
