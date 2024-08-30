<?php

namespace Drupal\os2uol_pretix;

use GuzzleHttp\Client;

class PretixConnector {

  /**
   * @var \GuzzleHttp\Client
   */
  private Client $client;

  public function __construct(Client $client) {
    $this->client = $client;
  }

  public function getClient($host, $api_token, $organizer = ''): PretixClient {
    return new PretixClient($this->client, $host, $api_token, $organizer);
  }

}
