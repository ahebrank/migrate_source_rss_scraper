<?php

namespace Drupal\migrate_source_rss_scraper;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use SimpleXMLElement;
use Drupal\migrate\MigrateException;

/**
 * Implement the iterator used by the scraper.
 */
class RssScraperIterator implements \Iterator, \Countable {

  /**
   * List of item URLs found from the listings.
   *
   * @var array
   */
  private $itemUrls = [];

  /**
   * Index in the URL array.
   *
   * @var int
   */
  private $position = NULL;

  /**
   * The current ID.
   *
   * @var int
   */
  private $currentId = NULL;

  /**
   * The current item.
   *
   * @var array
   */
  private $currentItem = NULL;

  /**
   * Scraping client.
   *
   * @var \Goutte\Client
   */
  private $client;

  /**
   * Field configuration.
   *
   * @var array
   */
  private $fields = [];

  /**
   * ID field configuration.
   *
   * @var array
   */
  private $ids = [];

  /**
   * Initialize the iterator.
   *
   * Use a set of list URLs and the DOM selector for links within those lists.
   */
  public function __construct($config) {
    $this->client = new Client();
    $guzzle = new GuzzleClient();
    $guzzle_options = [];
    if ($config['browser_agent']) {
      $guzzle_options['headers'] = [
        'User-Agent' => $config['browser_agent'],
      ];
    }

    $urls = $config['rss_url'];

    $items = [];
    foreach ((array) $urls as $listing_url) {
      try {
        $response = $guzzle->request('GET', $listing_url, $guzzle_options);
        $data = $response->getBody();
        $feed = new SimpleXMLElement($data);
        $page_items = array_map(function ($node) {
          $item_url = (string) $node->link;
          return $item_url;
        }, $feed->xpath('/rss/channel/item'));
        $items = array_merge($items, $page_items);
      }
      catch (Exeception $e) {
        throw new MigrateException("Unable to parse RSS feed: " . $e->getMessage());
      }
    }
    $this->itemUrls = $items;
    $this->fields = $config['fields'];
    $this->ids = $config['ids'];
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    $count = 0;
    foreach ($this as $item) {
      $count++;
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->currentItem;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->currentId;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return !empty($this->currentItem);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->currentId = NULL;
    $this->next();
  }

  /**
   * Implementation of Iterator::next().
   */
  public function next() {
    $this->currentItem = $this->currentId = NULL;
    if (is_null($this->position)) {
      $i = 0;
    }
    else {
      $i = $this->position + 1;
    }

    if (!isset($this->itemUrls[$i])) {
      return;
    }

    $url = $this->itemUrls[$i];
    $this->currentItem = $this->getPage($url);

    if ($this->valid()) {
      $this->position = $i;
      foreach ($this->ids as $id_field_name => $id_info) {
        $this->currentId[$id_field_name] = $this->currentItem[$id_field_name];
      }
    }
  }

  /**
   * Grab the fields for a given page.
   */
  private function getPage($url) {
    $data = [];
    try {
      $crawler = $this->client->request('GET', $url);
    }
    catch (Exception $e) {
      throw new MigrateException("Unable to parse $url");
    }
    // echo "Scraping item $url\n";
    foreach ($this->fields as $field) {
      try {
        $nodes = $crawler->filter($field['selector']);
      }
      catch (Exception $e) {
        throw new MigrateException("Unable to filter $url by " . $field['selector']);
      }
      if ((isset($field['first']) && $field['first']) || count($nodes) == 1) {
        $field_val = $this->parseNode($nodes->first(), $field);
      }
      elseif (count($nodes) > 1) {
        $field_val = $nodes->each(function ($node) use ($field) {
          return $this->parseNode($node, $field);
        });
      }
      else {
        $field_val = NULL;
      }

      $data[$field['name']] = $field_val;
    }
    return $data;
  }

  /**
   * Return a value from a single DOM node based on selector configuration.
   */
  private function parseNode($node, $field_config) {
    if (!count($node)) {
      return NULL;
    }

    // By default, grab the node text.
    if (!isset($field_config['text']) || $field_config['text']) {
      return $node->text();
    }
    elseif (isset($field_config['html']) && $field_config['html']) {
      return $node->html();
    }
    else {
      if (isset($field_config['attr'])) {
        return $node->attr($field_config['attr']);
      }
    }
    return NULL;
  }

  /**
   * Generic getter.
   */
  public function get($key) {
    return $this->{$key};
  }

}
