# ElasticSearch

ElasticSearch is an integral component of Altis, enabling enhanced search and relevancy as well as powering the analytics data query layer.

## Kibana

Local Server provides [Kibana](https://www.elastic.co/products/kibana) out of the box, a powerful tool for viewing indexes, creating and debugging queries and more.

Kibana is available at [`/kibana/`](internal://site/kibana/).

### Adding Index Patterns

Before you can get started querying in Kibana you'll need to [add some index patterns at `/kibana/app/kibana#/management/kibana/index/`](internal://site/kibana/app/kibana#/management/kibana/index/).

![Kibana Index Patterns UI](./assets/kibana-index-patterns.png)

Enter an index pattern into the field (available indexes are shown below) using wildcards if you wish to work across multiple indexes at a time. You will then be prompted to choose a date field to use for time series filters, make your selection, for example `post_date`, and save your index pattern to start analysing your data.

You can add additional index patterns from the Kibana Management page in future.

**Note**: When adding an index pattern for the `analytics` index choose "I don't want to use the Time Filter" from the dropdown on the next screen. Analytics event timestamps are stored in milliseconds and Kibana's time filter does not currently work with this by default.

### Developing & Debugging Queries

Use the [Dev Tools tab at `/kibana/app/kibana#/dev_tools/console`](internal://site/kibana/app/kibana#/dev_tools/console) to enter and run queries. This provides useful features including autocomplete based on your data and linting.

![Kibana Dev Tools panel](./assets/kibana-dev-tools.png)

### Viewing & Understanding Data

The easiest way to view your data is in the [Discover tab at `/kibana/app/kibana#/discover`](internal://site/kibana/app/kibana#/discover). Here you can create basic queries, select and sort by columns as well drill down into the indexed data to see it's structure and data types.

![Kibana Discover panel](./assets/kibana-discover.png)


## Accessing ElasticSearch Directly

The ElasticSearch hostname is not directly exposed however you can find the dynamic port and IP to connect to by running `composer server status | grep elasticsearch`.

You should see output similar to this:

<pre><code>
project_elasticsearch_1   /elastic-entrypoint.sh ela ...   Up (healthy)   <strong>0.0.0.0:32871</strong>->9200/tcp, 9300/tcp
</code></pre>

Copy the mapped IP and port (highlighted in bold above) and use it to query ElasticSearch directly:

```
curl -XGET http://0.0.0.0:32871
```

## ElasticSearch Memory Limit

ElasticSearch requires more memory on certain operating systems such as Ubuntu or when using Continuous Integration services. If ElasticSearch does not have enough memory it can cause other services to stop working. The Local Server supports an environment variable which can change the default memory limit for ElasticSearch called `ES_MEM_LIMIT`.

You can set the `ES_MEM_LIMIT` variable in 2 ways:

- Set it globally eg: `export ES_MEM_LIMIT=2g`
- Set it for the local server process only: `ES_MEM_LIMIT=2g composer server start`
