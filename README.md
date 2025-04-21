# JsonRPCEncryptBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![Build Status](https://img.shields.io/travis/tourze/json-rpc-encrypt-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/json-rpc-encrypt-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/json-rpc-encrypt-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)

A Symfony bundle for encrypting and decrypting JsonRPC requests and responses, ensuring sensitive data security during transmission.

## Features

- AES-256-CBC symmetric encryption for data transmission
- Automatic decryption for incoming requests, automatic encryption for outgoing responses
- Multi-AppID and multi-secret management
- Compatible with standard JsonRPC workflow
- Flexible event subscriber mechanism

## Installation

- Requires PHP 8.1+
- Requires Symfony 6.0+
- Install via Composer:

```bash
composer require tourze/json-rpc-encrypt-bundle
```

- Requires `tourze/jsonrpc-caller-bundle` for AppID/secret management

## Quick Start

1. Register the bundle in your Symfony application:

```php
return [
    // ...
    Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle::class => ['all' => true],
];
```

2. Configure `ApiCallerRepository` to provide appId and appSecret.

3. Client requests must add `Encrypt-AppID` header and encrypt the payload using the corresponding appSecret with AES-256-CBC:

```http
POST /jsonrpc HTTP/1.1
Encrypt-AppID: your-app-id
Content-Type: application/json

<encrypted payload>
```

4. The server will automatically decrypt the request body and encrypt the response body.

## Documentation

- See `DataEncryption.puml` or the generated flowchart for the encryption process.
- Custom encryption logic is supported by extending the Encryptor service.
- For advanced usage and configuration, see the source code and inline comments.

## Contributing

- Issues and PRs are welcome
- Follow PSR coding standards
- Please ensure new features are fully tested

## License

- MIT License
- Copyright © Tourze Team

## Changelog

See [CHANGELOG.md] (if available)
