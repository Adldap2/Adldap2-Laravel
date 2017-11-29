# Unreleased

## Added

- `Adldap\Laravel\Scopes\UidScope` for limiting users for OpenLDAP ([e6a6bb1](https://github.com/Adldap2/Adldap2-Laravel/commit/e6a6bb1e304a0ff83cfaf7b67494be243fa9da24))
- `auto_connect` are now retrived by `env()` by default ([27aae7a](https://github.com/Adldap2/Adldap2-Laravel/commit/27aae7a9f2079a822d02afbedd020f8ad7c5e402))
- `use_ssl` and `use_tls` are now retrieved by `env()` by default ([89a8493](https://github.com/Adldap2/Adldap2-Laravel/commit/89a84932a4756ef2036c2aece84fd64e27aa9f8a))
- `Adldap\Laravel\Events\Synchronized` event is now fired when a user **has been** synchronized ([2c9bb46](https://github.com/Adldap2/Adldap2-Laravel/commit/2c9bb462748e456c1b8f4c3d4b4f5b3813a3c9a6))
- `Adldap\Laravel\Events\Synchronizing` event is now fired when a user **is being** synchronized ([a087213](https://github.com/Adldap2/Adldap2-Laravel/commit/a0872135888d6c7b9e616a6b0cff8f4d0dd13d3b), [90f3fce](https://github.com/Adldap2/Adldap2-Laravel/commit/90f3fce6ee984e531c0364281d00a0fa1af6d1c6))
- `Adldap\Laravel\Events\Importing` event is now fired for new users who are being imported ([a087213](https://github.com/Adldap2/Adldap2-Laravel/commit/a0872135888d6c7b9e616a6b0cff8f4d0dd13d3b), [90f3fce](https://github.com/Adldap2/Adldap2-Laravel/commit/90f3fce6ee984e531c0364281d00a0fa1af6d1c6))

## Changed

- Password sync is now disabled by  default ([7ffc437](https://github.com/Adldap2/Adldap2-Laravel/commit/7ffc43777e802b5517923b9f32b191aae8215782))
- Windows authenticated users are now remembered by default ([6c7a671](https://github.com/Adldap2/Adldap2-Laravel/commit/6c7a671df1c7e4ba412ba01c6a6b60d4fa1994ee))
- Windows auth configuration is now an array ([4c6a77d](https://github.com/Adldap2/Adldap2-Laravel/commit/4c6a77de1cbfc4f59d0ee32bbce7f76cf5b15a1c))
- Locate users by their `objectGUID` instead of their `objectSID` ([ff9ca96](https://github.com/Adldap2/Adldap2-Laravel/commit/ff9ca963aa2e54110d2509319a70a7c09033c32f))

## Removed

- Removed configurable importer ([82f6df8](https://github.com/Adldap2/Adldap2-Laravel/commit/82f6df8350562ab73f208cdfc30024c5e91f8b70))
- Removed configurable resolver ([11e9914](https://github.com/Adldap2/Adldap2-Laravel/commit/11e991401702605d5125b9343f8589aac23ad0a4))
