# JsonRPCEncryptBundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![Build Status](https://img.shields.io/travis/tourze/json-rpc-encrypt-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/json-rpc-encrypt-bundle)
[![Quality Score](https://img.shields.io/scrutinizer/g/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://scrutinizer-ci.com/g/tourze/json-rpc-encrypt-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)

一个用于在 JsonRPC 服务中实现请求与响应加解密的 Symfony Bundle，确保敏感数据在传输过程中的安全。

## 功能特性

- 支持基于 AES-256-CBC 的对称加密传输
- 请求数据自动解密，响应数据自动加密
- 支持多 AppID 多密钥管理
- 兼容标准 JsonRPC 流程
- 灵活的事件订阅机制

## 安装说明

- 依赖 PHP 8.1 及以上版本
- 依赖 Symfony 6.0 及以上版本
- 通过 Composer 安装：

```bash
composer require tourze/json-rpc-encrypt-bundle
```

- 需依赖 `tourze/jsonrpc-caller-bundle` 用于 AppID/密钥管理

## 快速开始

1. 注册 Bundle 到 Symfony：

```php
return [
    // ...
    Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle::class => ['all' => true],
];
```

2. 配置 ApiCallerRepository，确保能正确获取到 appId 与 appSecret。

3. 客户端请求时在 Header 添加 `Encrypt-AppID`，并使用对应 appSecret 进行 AES-256-CBC 加密：

```http
POST /jsonrpc HTTP/1.1
Encrypt-AppID: your-app-id
Content-Type: application/json

<加密后的 Payload>
```

4. 服务端会自动解密请求体，业务处理后自动加密响应。

## 详细文档

- 加密流程详见 `DataEncryption.puml` 或生成的流程图文档。
- 支持自定义加密逻辑，可扩展 Encryptor 服务。
- 详细配置与高级用法请参考源码及注释。

## 贡献指南

- 欢迎提交 Issue 与 PR
- 遵循 PSR 代码规范
- 请确保新增功能有完整测试

## 版权和许可

- 遵循 MIT 开源协议
- 版权所有 © Tourze 团队

## 更新日志

详见 [CHANGELOG.md]（如有）
