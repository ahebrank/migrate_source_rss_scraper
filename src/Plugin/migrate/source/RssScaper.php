<?php

namespace Drupal\cos_migrate\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\cos_migrate\RssScraperIterator;

/**
 * Scrape a page listed in RSS.
 *
 * @MigrateSource(
 *   id = "rss_scraper",
 *   source_module = "cos_migrate",
 * )
 */
class Scraper extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    // Listing URLs are required.
    if (empty($this->configuration['rss_url'])) {
      throw new MigrateException('You must declare the "rss_url" to the source URL list page(s) in your configuration.');
    }

    // Item selectors are required.
    if (empty($this->configuration['fields'])) {
      throw new MigrateException('You must declare fields to scrape in your configuration.');
    }

    // Item selectors are required.
    if (empty($this->configuration['ids'])) {
      throw new MigrateException('You must declare an ID field in your configuration.');
    }
  }

  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', $this->configuration['list_urls']);
    return $urls;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    return new RssScraperIterator($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];
    foreach ($this->configuration['fields'] as $f) {
      $fields[$f['name']] = $this->t($f['name']);
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return $this->configuration['ids'];
  }

}
