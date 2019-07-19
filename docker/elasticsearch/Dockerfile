FROM blacktop/elasticsearch:6.3
RUN bin/elasticsearch-plugin install --batch --silent ingest-attachment
RUN bin/elasticsearch-plugin install --batch --silent analysis-kuromoji
RUN apk add --no-cache curl
