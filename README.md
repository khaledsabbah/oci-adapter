# OCI Adapter

This package is an adapter for the Oracle Cloud Infrastructure Object Storage API for the Flysystem for Laravel.

Integration guide:

https://medium.com/@patricksriemer/using-oci-object-storage-in-laravel-15f501f747a9

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
OCI_STORAGE_TIER=
```

Example values:

```bash
OCI_REGION=ap-singapore-1
OCI_BUCKET=my-bucket-name
OCI_TENANCY_ID=ocid1.tenancy.oc1..{longstring}
OCI_USER_ID=ocid1.user.oc1..{longstring}
OCI_KEY_FINGERPRINT=11:12:aa:ab:ac:1d:dd:aa:11:99:22:21:f3:79:12:1b
OCI_KEY_PATH=./oci.pem
OCI_STORAGE_TIER=Standard
```

## Technical notes

1. Temporary URLs leverage OCIs pre-authenticated requests. When a temporaryUrl is not called, the pre-authenticated request will remain even if it is expired. It is advisable to create a scheduler that is automatically cleaning up expired requests.
2. The move operation will only copy the object as it will be processed within a work request. As the work request is processed asynchronously an immediate delete operation after a copy, will delete the object before it can be copied. A possible workaround is to create a temporary directory for uploaded files and cleaning it up automatically with a lifecycle policy.
3. THe PHP extension ext-fileinfo is required so that the mime type can be detected from the file stream.