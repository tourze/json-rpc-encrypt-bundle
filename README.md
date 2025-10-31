# JsonRPCEncryptBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/packagist/php-v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![Latest Version](https://img.shields.io/packagist/v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![License](https://img.shields.io/packagist/l/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![Build Status](https://img.shields.io/travis/tourze/json-rpc-encrypt-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/json-rpc-encrypt-bundle)
[![Coverage Status](https://img.shields.io/codecov/c/github/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://codecov.io/gh/tourze/json-rpc-encrypt-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)

A Symfony bundle for encrypting and decrypting JsonRPC requests and responses, 
ensuring sensitive data security during transmission.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Quick Start](#quick-start)
- [Advanced Usage](#advanced-usage)
- [Security](#security)
- [Dependencies](#dependencies)
- [Contributing](#contributing)
- [License](#license)

## Features

- AES-256-CBC symmetric encryption for data transmission
- Automatic decryption for incoming requests, automatic encryption for outgoing responses
- Multi-AppID and multi-secret management
- Compatible with standard JsonRPC workflow
- Flexible event subscriber mechanism
- Comprehensive test coverage

## Installation

**Requirements:**
- PHP 8.1+
- Symfony 6.4+

**Install via Composer:**

```bash
composer require tourze/json-rpc-encrypt-bundle
```

## Dependencies

This bundle requires the following packages:

- `tourze/json-rpc-core` - Core JsonRPC functionality  
- `tourze/json-rpc-endpoint-bundle` - JsonRPC endpoint handling
- `symfony/framework-bundle` - Symfony framework
- `doctrine/orm` - Database ORM support

## Configuration

1. **Register the bundle** in your Symfony application:

```php
// config/bundles.php
return [
    // ...
    Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle::class => ['all' => true],
];
```

3. **Service Configuration** (optional):

```yaml
# config/services.yaml
services:
    Tourze\JsonRPCEncryptBundle\Service\Encryptor:
        # Custom configuration if needed
```

## Quick Start

**Client-side setup:**

1. Add `Encrypt-AppID` header to your JsonRPC requests
2. Encrypt the payload using AES-256-CBC with your appSecret

```bash
curl -X POST http://your-server/jsonrpc \
  -H "Content-Type: application/json" \
  -H "Encrypt-AppID: your-app-id" \
  -d "<encrypted-payload>"
```

**Server-side behavior:**

- Automatically decrypts incoming requests with `Encrypt-AppID` header
- Automatically encrypts outgoing responses for encrypted requests
- Maintains standard JsonRPC error handling

## Advanced Usage

### Custom Encryption Logic

Extend the `Encryptor` service for custom encryption implementations:

```php
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class CustomEncryptor extends Encryptor
{
    public function encryptData(string $rawString, string $signSecret, string $signKey): string
    {
        // Your custom encryption logic
        return parent::encryptData($rawString, $signSecret, $signKey);
    }
}
```

### Event Subscribers

The bundle provides `EncryptSubscriber` that handles:

- `RequestStartEvent` - Decrypts incoming requests
- `ResponseSendingEvent` - Encrypts outgoing responses

### Error Handling

The bundle throws specific exceptions:

- `EncryptAppIdMissingException` - When Encrypt-AppID header is missing
- `EncryptAppIdNotFoundException` - When AppID is not found or invalid

## Security

**Encryption Details:**
- Algorithm: AES-256-CBC
- Key derivation: SHA-256 hash of appSecret
- IV generation: MD5 hash of appId (16 bytes)
- Data encoding: Base64

**Security Considerations:**
- Always use HTTPS in production
- Rotate appSecret regularly
- Validate AppID permissions
- Monitor encryption/decryption failures
- Keep encryption keys secure

**Important Security Notes:**
- This bundle provides data encryption but does not replace proper authentication
- Ensure proper key management practices
- Consider implementing rate limiting for encryption endpoints

## Documentation

- See `DataEncryption.puml` for the encryption process flowchart
- Check inline PHPDoc comments for detailed API documentation
- Review test cases for usage examples

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR coding standards
4. Ensure new features are fully tested
5. Submit a pull request

**Development setup:**

```bash
git clone https://github.com/tourze/json-rpc-encrypt-bundle.git
cd json-rpc-encrypt-bundle
composer install
vendor/bin/phpunit
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

Copyright © Tourze Team

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for release notes and version history.