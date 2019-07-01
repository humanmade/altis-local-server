const { send, json } = require('micro');
const {
	router,
	options,
	post,
	put
} = require('micro-fork');
const rp = require('request-promise');
const fs = require('fs');
const { basename } = require('path');
const { inspect, promisify } = require('util');
const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);

const uuid = placeholder =>
  placeholder
    ? (placeholder ^ ((Math.random() * 16) >> (placeholder / 4))).toString(16)
		: ([1e7] + -1e3 + -4e3 + -8e3 + -1e11).replace(/[018]/g, uuid);

const setHeaders = (req, res, headers = {}) => {
	res.setHeader('access-control-allow-origin', '*');
	if (req.headers['access-control-request-headers']) {
		res.setHeader('access-control-allow-headers', req.headers['access-control-request-headers']);
	}
	res.setHeader('access-control-expose-headers', 'x-amzn-RequestId,x-amzn-ErrorType,x-amzn-ErrorMessage,Date');
	res.setHeader('access-control-allow-methods', req.headers['access-control-allow-methods'] || 'GET, PUT, POST, DELETE, HEAD, OPTIONS');
	res.setHeader('access-control-max-age', '172800');
	res.setHeader('date', new Date().toUTCString());
	res.setHeader('x-amzn-requestid', uuid());
	Object.entries(headers, ([key, value]) => {
		res.setHeader(key, value);
	});
}

const makeRecord = data => {
	// Deliver to elasticsearch.
	return {
		application: {
			app_id: 'local-pinpoint',
			cognito_identity_pool_id: '',
			version_name: 'local'
		},
		arrival_timestamp: Date.now(),
		attributes: data.attributes || {},
		metrics: data.metrics || {},
		client: {
			client_id: data.cognitoId || 'default',
			cognito_id: '',
		},
		device: {
			model: data.model || 'unknown',
			make: data.make || 'unknown',
			platform: {
				name: data.platform || 'unknown',
			},
		},
		endpoint: data.endpoint || {},
		event_type: data.type || 'unknown',
		event_timestamp: Date.now(),
		event_version: 'default',
		session: {
			session_id: 'default',
			start_timestamp: 0,
			stop_timestamp: 0
		},
	};
};

const esRequest = async (path, data, method = 'PUT') => {
	try {
		const rsp = await rp({
			uri: `${process.env.ELASTICSEARCH_HOST || 'http://elasticsearch:9200'}/${path}`,
			body: data,
			json: true,
			method: method
		});
		return rsp;
	} catch (err) {
		console.error(inspect(err.error, { showHidden: false, depth: null }));
		return err.error;
	}
}

const putMapping = async () => {
	// Put the mapping.
	const mapping = await readFile(`${__dirname}/mapping.json`);
	return await esRequest('analytics', JSON.parse(mapping));
}

const addRecord = async data => {
	return await esRequest('analytics/record/', data, 'POST');
}

const setEndpoint = async (data, id) => {
	try {
		await writeFile(`/tmp/endpoints/${id}.json`, JSON.stringify(data));
		return true;
	} catch (err) {
		console.error('could not write endpoint.json', err);
		return false;
	}
}

const getEndpoint = async (id) => {
	try {
		const endpoint = await readFile(`/tmp/endpoints/${id}.json`);
		return JSON.parse(endpoint);
	} catch (err) {
		console.error('could not read endpoint.json', err);
		return {};
	}
}

module.exports = router()(
	options('/cognito', (req, res) => {
		setHeaders(req, res);
		send(res, 200);
	}),
	post('/cognito', async (req, res) => {
		const body = await json(req);
		if (body.IdentityId) {
			// Send credentials object.
			setHeaders(req, res, {
				"content-type": "application/x-amz-json-1.1",
			});
			send(res, 200, {
				"Credentials": {
					"AccessKeyId": "not-needed",
					"Expiration": Date.now() + (365 * 24 * 60 * 60),
					"SecretKey": "not-needed",
					"SessionToken": "not-needed"
				},
				"IdentityId": body.IdentityId,
			});
			return;
		}
		send(res, 500);
	}),
	options('/pinpoint*', (req, res) => {
		setHeaders(req, res);
		send(res, 200);
	}),
	// legacy endpoint
	post('/pinpoint*', async (req, res) => {
		const body = await json(req);

		if (!body.BatchItem) {
			send(res, 500);
			return;
		}

		await putMapping();

		Object.entries(body.BatchItem).forEach( async ([cid, item ]) => {
			const storedEndpoint = await getEndpoint(cid);
			const { Events, Endpoint } = item;
			const finalEndpoint = Object.assign({}, storedEndpoint, Endpoint);
			Object.entries(Events).forEach( async ([eid, event]) => {
				await addRecord(makeRecord({
					attributes: event.Attributes || {},
					metrics: event.Metrics || {},
					endpoint: finalEndpoint || {},
					type: event.EventType || 'unknown',
					model: (finalEndpoint.Demographic && finalEndpoint.Demographic.Model) || '',
					make: (finalEndpoint.Demographic && finalEndpoint.Demographic.Make) || '',
					platform: (finalEndpoint.Demographic && finalEndpoint.Demographic.Platform) || '',
					cognitoId: cid,
				}));
			});
		});

		setHeaders(req, res);
		send(res, 202, {
			Results: Object
				.entries(body.BatchItem)
				.reduce((carry, [key, batch]) => ({
					[key]: {
						EndpointItemResponse: {
							StatusCode: 202,
							Message: 'Accepted',
						},
						EventsItemResponse: Object
							.entries(batch.Events, event => event)
							.reduce((carry2, [id, data]) => ({
								[id]: {
									StatusCode: 202,
									Message: 'Accepted',
								},
								...carry2
							}), {}),
					},
					...carry,
				}), {})
		});
	}),
	// new endpoint
	put('/pinpoint*', async (req, res) => {
		const body = await json(req);

		if (!body.Attributes) {
			send(res, 500);
			return;
		}

		const cognitoId = basename(req.url);

		await putMapping();
		await setEndpoint(body, cognitoId);
		await addRecord(makeRecord({
			attributes: body.Attributes || {},
			metrics: body.Metrics || {},
			endpoint: body || {},
			type: body.EventType || 'unknown',
			model: (body.Demographic && body.Demographic.Model) || '',
			make: (body.Demographic && body.Demographic.Make) || '',
			platform: (body.Demographic && body.Demographic.Platform) || '',
			cognitoId: cognitoId,
		}));

		setHeaders(req, res);
		send(res, 202, {
			Message: 'Accepted',
			RequestID: res.getHeader('x-amzn-requestid'),
		});
		return;
	})
);
