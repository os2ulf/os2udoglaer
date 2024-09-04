<?php

namespace Drupal\os2uol_pretix;

use Drupal\Core\Entity\EditorialContentEntityBase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class PretixClient {

  /**
   * @var \GuzzleHttp\Client
   */
  private \GuzzleHttp\Client $client;

  private string $pretix_url;

  private string $api_token;

  private string $organizer;

  /**
   * @param \GuzzleHttp\Client $client
   * @param string $pretix_url
   * @param string $api_token
   * @param string $organizer
   */
  public function __construct(\GuzzleHttp\Client $client, string $pretix_url, string $api_token, string $organizer) {
    $this->client = $client;
    $this->pretix_url = $pretix_url;
    $this->api_token = $api_token;
    $this->organizer = $organizer;
  }

  /**
   * @param string $default_event
   * @param array $event
   *
   * @return array
   */
  public function createEvent(string $default_event, array $event): array {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $default_event . '/clone/';
    $event['has_subevents'] = TRUE;
    $options = $this->getOptions();
    $options['json'] = $event;
    try {
      return json_decode($this->client->request('POST', $url, $options)
        ->getBody()
        ->getContents(), TRUE);
    }
    catch (ClientException $e) {
      return [
        'error' => $e->getCode(),
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'json' => json_decode($e->getResponse()->getBody()->getContents(), TRUE)
      ];
    }
  }

  public function createSubEvent(string $eventSlug, array $subevent) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/subevents/';
    $options = $this->getOptions();
    $options['json'] = $subevent;
    return json_decode($this->client->request('POST', $url, $options)->getBody()->getContents(), TRUE);
  }

  public function updateSubEvent(string $eventSlug, array $subevent) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/subevents/' . $subevent['id'] . '/';
    $options = $this->getOptions();
    $options['json'] = $subevent;
    return json_decode($this->client->request('PATCH', $url, $options)->getBody()->getContents(), TRUE);
  }

  public function deleteSubEvent(string $eventSlug, string $subevent_id) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/subevents/' . $subevent_id . '/';
    $options = $this->getOptions();
    return $this->client->request('DELETE', $url, $options)->getStatusCode();
  }

  public function createMetadata(string $eventSlug, array $metadata) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/item_meta_properties/';
    $options = $this->getOptions();
    $options['json'] = $metadata;
    return json_decode($this->client->request('POST', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get an event.
   *
   * @param string $eventSlug
   *
   * @return array
   *   The result.
   */
  public function getEvent(string $eventSlug): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug;
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Update event.
   *
   * @param string $eventSlug
   *   The event slug.
   * @param array $data
   *   The data.
   *
   * @return array
   *   The result.
   */
  public function updateEvent(string $eventSlug, array $data): array {
    $options = $this->getOptions();
    $options['json'] = $data;
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug. '/';
    try {
      return json_decode($this->client->request('PATCH', $url, $options)
        ->getBody()
        ->getContents(), TRUE);
    }
    catch (ClientException $e) {
      return [
        'error' => $e->getCode(),
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'json' => json_decode($e->getResponse()->getBody()->getContents(), TRUE)
      ];
    }
  }

  /**
   * Get events.
   *
   * @return array
   *   The result.
   */
  public function getEvents(): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get a singular sub-event.
   *
   * @param string $eventSlug
   *   The event slug.
   * @param string $subevent_id
   *    The subevent id.
   *
   * @return array
   *   The result.
   */
  public function getSubEvent(string $eventSlug, string $subevent_id): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/subevents/' . $subevent_id . '/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get sub-events (event series dates).
   *
   * @param string $eventSlug
   *   The event slug.
   *
   * @return array
   *   The result.
   */
  public function getSubEvents(string $eventSlug): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/subevents/';
    try {
      return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
    }
    catch (ClientException $e) {
      return [
        'error' => $e->getCode(),
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'json' => json_decode($e->getResponse()->getBody()->getContents(), TRUE)
      ];
    }

  }

  /**
   * Get products for an event.
   *
   * @param string $eventSlug
   *   The event slug.
   *
   * @return array
   *   The result.
   */
  public function getProducts(string $eventSlug): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/items/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get quotas for a subevent.
   *
   * @param string $eventSlug
   *    The event slug.
   * @param array $subevent
   *    The sub event.
   *
   * @return array
   *   The result.
   */
  public function getQuotas(string $eventSlug, array $subevent = []): array {
    $options = $this->getOptions();
    if (isset($subevent['id'])) {
      $options['query'] = ['subevent' => $subevent['id']];
    }
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/quotas/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get quota availability.
   *
   * @param string $eventSlug
   *   The event slug.
   * @param int $quotaId
   *   The quota id.
   *
   * @return array
   *   The result.
   */
  public function getQuotaAvailability(string $eventSlug, int $quotaId): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/quotas/' . $quotaId . '/availability/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Create quota.
   *
   * @param object|string $event
   *   The event or event slug.
   * @param array $quota
   *   The data.
   *
   * @return array
   *   The result.
   */
  public function createQuota($eventSlug, array $quota) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/quotas/';
    $options = $this->getOptions();
    $options['json'] = $quota;
    return json_decode($this->client->request('POST', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Update quota.
   *
   * @param object|string $event
   *   The event or event slug.
   * @param int $quotaId
   *   The quota id.
   * @param array $quota
   *   The quota data.
   *
   * @return array
   *   The result.
   */
  public function updateQuota($eventSlug, $quotaId, array $quota) {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/quotas/' . $quotaId . '/';
    $options = $this->getOptions();
    $options['json'] = $quota;
    return json_decode($this->client->request('PATCH', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get webhooks.
   *
   * @return array
   *   The result.
   */
  public function getWebhooks(): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/webhooks/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Create webhook.
   *
   * @param array $data
   *   The data.
   *
   * @return array
   *   The result.
   */
  public function createWebhook(array $data): array {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/webhooks/';
    $options = $this->getOptions();
    $options['json'] = $data;
    return json_decode($this->client->request('POST', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Update webhook.
   *
   * @param array $webhook
   *   The webhook.
   * @param array $data
   *   The data.
   *
   * @return array
   *   The result.
   */
  public function updateWebhook(array $webhook, array $data): array {
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/webhooks/' . $webhook['id'];
    $options = $this->getOptions();
    $options['json'] = $data;
    return json_decode($this->client->request('PATCH', $url, $options)->getBody()->getContents(), TRUE);
  }

  /**
   * Get order.
   *
   * @param string $eventSlug
   *   The event slug.
   * @param string $code
   *   The code.
   *
   * @return array
   *   The result.
   */
  public function getOrder($eventSlug, $code): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/orders/' . $code . '/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }
  protected function getOptions(): array {
    return [
      'headers' => [
        'Authorization' => 'Token ' . $this->api_token
      ]
    ];
  }

  /**
   * Get questions.
   *
   * @param string $eventSlug
   *   The event.
   *
   * @return array
   *   The result.
   */
  public function getQuestions(string $eventSlug): array {
    $options = $this->getOptions();
    $url = $this->pretix_url . 'api/v1/organizers/' . $this->organizer . '/events/' . $eventSlug . '/questions/';
    return json_decode($this->client->request('GET', $url, $options)->getBody()->getContents(), TRUE);
  }

  public function getPretixUrl(): string {
    return $this->pretix_url;
  }

  public function getApiToken(): string {
    return $this->api_token;
  }

  public function getOrganizer(): string {
    return $this->organizer;
  }

}
