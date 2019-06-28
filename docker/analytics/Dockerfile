FROM mhart/alpine-node:12

COPY ./ /srv/app
WORKDIR /srv/app

RUN mkdir -p /tmp/endpoints

ARG ELASTICSEARCH_HOST

RUN npm install --production

EXPOSE 3000
ENTRYPOINT npm start
