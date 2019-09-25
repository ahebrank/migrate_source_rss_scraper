This plugin provides a migrate source plugin `rss_scraper` that's a combination of RSS feed reader and HTML scraper. It assumes a listing of (for example) news articles provided in RSS, where content for each article is scraped from HTML using the URL link in RSS.

An example migration configuration is provided in the `example_config` directory. 

In the example source configuration,

```
source:
  plugin: rss_scraper
  rss_urls: 
    - https://example.com/news.rss
    - https://example.com/news.rss?page=2
  fields:
    -
      name: title
      selector: 'h1'
    -
      name: created
      selector: '.pre-content .details'
    -
      name: image
      selector: '.content-area img'
      first: true
      attr: src
    -
      name: body
      selector: '.content-area'
      html: true
  ids:
    title:
      type: string
```

- `rss_url`: defines the RSS feed
- `fields`: defines the DOM target for field content within each item (which are read from URLs in the RSS feed).
- `browser_agent`: optionally set (mimic) a custom browser agent
- `http_delay`: optional delay (in seconds) between https requests
- `debug`: optional configuration to log output to console (true/false)

Each field definition contains a `selector` and information about how to retrieve the content: as `text` wrapped by the tag (the default), as `html` from within the tag, or from an attribute `attr` on the tag. The `first` parameter may also be used to limit to the first tag found.