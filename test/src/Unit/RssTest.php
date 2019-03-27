<?php

namespace Drupal\Tests\migrate_source_rss_scraper\Unit;

use PHPUnit\Framework\TestCase;
use Drupal\migrate_source_rss_scraper\RssScraperIterator;

/**
 * Testing the RSS link reader.
 *
 * @group migrate_source_rss_scraper
 *
 * Run me in web/core with ../../vendor/bin/phpunit ../modules/custom/migrate_source_rss_scraper/test/src/Unit/RssTest.php.
 */
class RssTest extends TestCase {

  protected $rss;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $config = [
      'rss_url' => 'https://feeds.bbci.co.uk/news/rss.xml',
      'fields' => [
        [
          'name' => 'title',
          'selector' => 'meta[property="og:title"]',
          'text' => FALSE,
          'attr' => 'content',
        ],
        [
          'name' => 'body',
          'selector' => '.story-body__inner',
          'text' => FALSE,
          'html' => TRUE,
        ],
      ],
      'ids' => [
        'title' => [
          'type' => 'string',
        ],
      ],
    ];
    $this->rss = new RssScraperIterator($config);
  }

  /**
   * Test link retrieval from RSS.
   */
  public function testLinks() {
    $urls = $this->rss->get('itemUrls');
    $this->assertInternalType('array', $urls);
    $this->assertGreaterThan(1, count($urls));
  }

  /**
   * Test scraper content retrieval.
   */
  public function testScrapeItem() {
    $this->rss->next();
    $item = $this->rss->get('currentItem');
    $this->assertNotEmpty($item['title']);
    $this->assertNotEmpty($item['body']);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    unset($this->rss);
  }

}
