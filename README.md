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
OCI_BUCKET=
OCI_TENANCY_ID=
OCI_USER_ID=
OCI_KEY_FINGERPRINT=
OCI_KEY_PATH=
```

Example values:

```bash
OCI_REGION=ap-singapore-1
OCI_BUCKET=my-bucket-name
OCI_TENANCY_ID=ocid1.tenancy.oc1..{longstring}
OCI_USER_ID=ocid1.user.oc1..{longstring}
OCI_KEY_FINGERPRINT=11:12:aa:ab:ac:1d:dd:aa:11:99:22:21:f3:79:12:1b
OCI_KEY_PATH=./oci.pem
```


Implemented methods:

- fileExists
- directoryExists
- fileSize

TODO:

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
- listContents
- move
- copy
- temporary signed urls



The purpose of this repository is to create a flysystem adapter for OCI object storage.

Read more about the background:

https://patricksriemer.medium.com/laravel-and-object-storage-on-oci-e07b2197d709