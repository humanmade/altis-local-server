# Elasticsearch

Elasticsearch is available on some Altis plans, and is fully integrated into Altis with the [Enhanced Search module](docs://search/), enabling enhanced search and relevancy as well as powering the analytics data query layer.

(Note: Altis uses [OpenDistro for Elasticsearch](https://opendistro.github.io/for-elasticsearch-docs/) and [OpenSearch](https://opensearch.org/), which are compatible with Elasticsearch.)


## Enabling

Elasticsearch support in Local Server is enabled when the Enhanced Search module is installed. It can be disabled through configuration if desired:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "search": {
                    "local": {
                        "enabled": false
                    }
                }
            }
        }
    }
}
```


## Available Versions

Elasticsearch defaults to version 7.10, however you can change the version in your config if using a different version. This should match your cloud environments; consult the Settings > Environment page for your environment for details about the configuration being used.

```json
{
    "extra": {
        "altis": {
            "modules": {
                "search": {
                    "local": {
                        "version": "6.8"
                    }
                }
            }
        }
    }
}
```

The current available versions are:

- 7.10 (default)
- 6.8
- 6.3

You can also use the major version on its own to get the latest minor version, for example "6" will resolve to version "6.8".

**Note**: If your device has an ARM chip you must use Elasticsearch 7 or higher.

## Kibana

Local Server provides [Kibana](https://www.elastic.co/products/kibana) out of the box, a powerful tool for viewing indexes, creating
and debugging queries and more.

Kibana is available at [`/kibana/`](internal://site/kibana/).

The version will always match the current Elasticsearch version. Kibana is enabled in Local Server by default when Enhanced Search is installed, but can be disabled via configuration:

```json
{
    "extra": {
        "altis": {
            "modules": {
                "search": {
                    "local": {
                        "kibana": false
                    }
                }
            }
        }
    }
}
```


### Adding Index Patterns

Before you can get started querying in Kibana you will need to add some index patterns. This is slightly different depending on your
current version.

1. Go to the "Management" tab, or "Stack Management" if using Elasticsearch 7.x
1. Select "Index Patterns"
1. Click the "Create Index Pattern" button
1. Enter a pattern to match indexes from the list, you can use asterisks as wildcards

- A good first pattern is `analytics*` for viewing your native analytics data

1. Click "Next Step"
1. Choose a time field if applicable, or "I don't want to use the time filter" if your data is not time based

- For the `analytics*` pattern choose `attributes.date` for the time field
- For a posts index you could use `post_date` to see publishing activity over time

You can add additional index patterns from the Management section at any time.

### Developing & Debugging Queries

Use the "Dev Tools" tab to enter and run queries. This provides useful features including linting and autocompletion based on your
data.

![Kibana "Dev Tools" panel](./assets/kibana-dev-tools.png)

### Viewing & Understanding Data

The easiest way to view your data is in the Discover tab. You will need to create some index patterns first before you can explore
your data here.

You can create basic queries, select and sort by columns as well as drill down into the indexed data to see it's structure and data
types.

![Kibana Discover panel](./assets/kibana-discover.png)

## Accessing Elasticsearch Directly

The Elasticsearch host name is not directly exposed however you can find the dynamic port and IP to connect to by
running `composer server status | grep elasticsearch`.

You should see output similar to this:

```text
project_elasticsearch_1   /elastic-entrypoint.sh ela ...   Up (healthy)   0.0.0.0:32871->9200/tcp, 9300/tcp
```

Copy the mapped IP and port (`0.0.0.0:32871` in the example above) and use it to query Elasticsearch directly:

```shell
curl -XGET http://0.0.0.0:32871
```

## Elasticsearch Memory Limit

Elasticsearch requires more memory on certain operating systems such as Ubuntu or when using Continuous Integration services. If
Elasticsearch does not have enough memory it can cause other services to stop working. The Local Server supports an environment
variable which can change the default memory limit for Elasticsearch called `ES_MEM_LIMIT`.

You can set the `ES_MEM_LIMIT` variable in 2 ways:

- Set it globally e.g. `export ES_MEM_LIMIT=2g`
- Set it for the local server process only: `ES_MEM_LIMIT=2g composer server start`
