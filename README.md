# OCI Adapter

WARNING: This package is under development and does not offer all methods for the Storage facade yet. Do not use this package in production.

Install package:

```bash
composer require patrickriemer/oci-adapter
```

Required environment variables:

```bash
OCI_NAMESPACE=
OCI_REGION=
OCI_KEY=
OCI_SECRET=
OCI_BUCKET=
OCI_TENANCY_ID=
OCI_USER_ID=
OCI_KEY_FINGERPRINT=
OCI_KEY_PATH=
```

Implemented methods:

- fileExists

TODO:

- directoryExists
- write
- writeStream
- read
- readStream
- delete
- deleteDirectory
- createDirectory
- setVisibility
- visibility
- mimeType
- lastModified
- fileSize
- listContents
- move
- copy
- temporary signed urls



The purpose of this repository is to create a flysystem adapter for OCI object storage.

Read more about the background:

https://patricksriemer.medium.com/laravel-and-object-storage-on-oci-e07b2197d709