<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\NewsletterSubscriber;

use Drupal\oe_newsroom_newsletter\Exception\BadResponseException;
use Drupal\oe_newsroom_newsletter\Exception\EmailAddressAlreadySubscribedException;
use Drupal\oe_newsroom_newsletter\Exception\InvalidEmailAddressException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default newsletter subscriber using the Newsroom Newsletter API.
 */
class NewsletterSubscriber implements NewsletterSubscriberInterface {

  /**
   * The URL for the Newsroom newsletter subscription API endpoint.
   */
  const NEWSROOM_NEWSLETTER_SUBSCRIPTION_URL = 'https://ec.europa.eu/newsroom/api/v1/subscriptions';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Constructs a new NewsletterSubscriber object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function subscribe(string $email, string $universe, string $service_id): void {
    $uri = $this->getApiEndpoint($universe);
    $options = [
      'query' => [
        'service_id' => $service_id,
        'email' => $email,
        'response_type' => 'json',
      ],
    ];
    try {
      $response = $this->httpClient->get($uri, $options);
      if ($response->getStatusCode() === 200) {
        $body = json_decode((string) $response->getBody()->getContents());
        // Check if the JSON data about the subscription has been returned.
        if (!empty($body->subscription)) {
          $data = $body->subscription;
          if (!empty($data->isNewSubscription) && $data->isNewSubscription === 'True') {
            // The subscription was successful.
            return;
          }
          // The user has not been subscribed. Throw a relevant exception.
          if (!empty($data->feedbackMessage)) {
            switch (trim($data->feedbackMessage)) {
              case 'A subscription for this service is already registered for this email address':
                throw new EmailAddressAlreadySubscribedException();

              case 'Invalid email address.':
                throw new InvalidEmailAddressException();

              default:
                throw new BadResponseException($data->feedbackMessage);
            }
          }
          throw new BadResponseException('Response returned by Newsroom newsletter API is not according to specification.');
        }
        throw new BadResponseException('Empty response returned by Newsroom newsletter API.');
      }
      throw new BadResponseException('Newsroom newsletter API returned a response with HTTP status ' . $response->getStatusCode());
    }
    catch (GuzzleException $e) {
      throw new BadResponseException('Invalid response returned by Newsroom newsletter API.', 0, $e);
    }
  }

  /**
   * Returns the URL for the Newsroom newsletter subscription API.
   *
   * @param string $universe
   *   The Newsroom universe for which to return the endpoint.
   *
   * @return string
   *   The URL for the newsletter subscription API.
   */
  protected function getApiEndpoint(string $universe): string {
    return str_replace('{universe}', $universe, static::NEWSROOM_NEWSLETTER_SUBSCRIPTION_URL);
  }

}
