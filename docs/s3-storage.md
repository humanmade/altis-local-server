# S3 Storage

Local Server uses [VersityGW](https://github.com/versity/versitygw) as a local S3-compatible storage backend. WordPress uploads are
stored in S3 via the S3 Uploads plugin. A `sync to host` service automatically syncs the S3 bucket contents back to
`content/uploads` on the host.

## S3 Commands

Use `composer server s3 import-uploads` to import existing files from `content/uploads` on the host into the S3 bucket. This is
useful when setting up a project for the first time or restoring uploads from a backup.

Use `composer server s3 ls` to list all objects in the S3 bucket. You can optionally provide a path prefix:

```sh
composer server s3 ls uploads/
```

Use `composer server s3 exec -- <command>` to run an arbitrary AWS CLI command against the local S3 endpoint:

```sh
composer server s3 exec -- s3api list-buckets
composer server s3 exec -- s3 cp s3://s3-my-project/uploads/2026/01/image.png .
```

## Configuration

| Setting    | Value                   |
|------------|-------------------------|
| Endpoint   | `https://s3-{hostname}` |
| Bucket     | `s3-{project-name}`     |
| Access Key | `admin`                 |
| Secret Key | `password`              |
| Region     | `us-east-1`             |

## Troubleshooting

Files placed directly in `content/uploads` on the host are not automatically imported to S3. Use `import-uploads` to import them.

The sync to host service runs every 10 seconds. If uploads are not appearing in `content/uploads`, check the sync service logs:

```sh
composer server logs s3-sync-to-host
```
