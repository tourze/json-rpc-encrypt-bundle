# JsonRPCEncryptBundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP 版本](https://img.shields.io/packagist/php-v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![最新版本](https://img.shields.io/packagist/v/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![许可证](https://img.shields.io/packagist/l/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)
[![构建状态](https://img.shields.io/travis/tourze/json-rpc-encrypt-bundle/master.svg?style=flat-square)](https://travis-ci.org/tourze/json-rpc-encrypt-bundle)
[![代码覆盖率](https://img.shields.io/codecov/c/github/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://codecov.io/gh/tourze/json-rpc-encrypt-bundle)
[![下载量](https://img.shields.io/packagist/dt/tourze/json-rpc-encrypt-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/json-rpc-encrypt-bundle)

一个用于在 JsonRPC 服务中实现请求与响应加解密的 Symfony Bundle，
确保敏感数据在传输过程中的安全。

## 目录

- [功能特性](#功能特性)
- [安装说明](#安装说明)
- [配置说明](#配置说明)
- [快速开始](#快速开始)
- [高级用法](#高级用法)
- [安全性](#安全性)
- [依赖包](#依赖包)
- [贡献指南](#贡献指南)
- [版权和许可](#版权和许可)

## 功能特性

- 支持基于 AES-256-CBC 的对称加密传输
- 请求数据自动解密，响应数据自动加密
- 支持多 AppID 多密钥管理
- 兼容标准 JsonRPC 流程
- 灵活的事件订阅机制
- 完整的测试覆盖

## 安装说明

**系统要求：**
- PHP 8.1 及以上版本
- Symfony 6.4 及以上版本

**通过 Composer 安装：**

```bash
composer require tourze/json-rpc-encrypt-bundle
```

## 依赖包

此 Bundle 需要以下依赖包：

- `tourze/json-rpc-core` - JsonRPC 核心功能  
- `tourze/json-rpc-endpoint-bundle` - JsonRPC 端点处理
- `symfony/framework-bundle` - Symfony 框架
- `doctrine/orm` - 数据库 ORM 支持

## 配置说明

1. **注册 Bundle** 到 Symfony 应用：

```php
// config/bundles.php
return [
    // ...
    Tourze\JsonRPCEncryptBundle\JsonRPCEncryptBundle::class => ['all' => true],
];
```

3. **服务配置**（可选）：

```yaml
# config/services.yaml
services:
    Tourze\JsonRPCEncryptBundle\Service\Encryptor:
        # 如需要可在此自定义配置
```

## 快速开始

**客户端设置：**

1. 在 JsonRPC 请求中添加 `Encrypt-AppID` 头部
2. 使用 appSecret 通过 AES-256-CBC 算法加密载荷

```bash
curl -X POST http://your-server/jsonrpc \
  -H "Content-Type: application/json" \
  -H "Encrypt-AppID: your-app-id" \
  -d "<加密后的载荷>"
```

**服务端行为：**

- 自动解密带有 `Encrypt-AppID` 头部的传入请求
- 自动加密加密请求的传出响应
- 保持标准 JsonRPC 错误处理

## 高级用法

### 自定义加密逻辑

扩展 `Encryptor` 服务以实现自定义加密：

```php
use Tourze\JsonRPCEncryptBundle\Service\Encryptor;

class CustomEncryptor extends Encryptor
{
    public function encryptData(string $rawString, string $signSecret, string $signKey): string
    {
        // 您的自定义加密逻辑
        return parent::encryptData($rawString, $signSecret, $signKey);
    }
}
```

### 事件订阅者

该 Bundle 提供 `EncryptSubscriber` 处理：

- `RequestStartEvent` - 解密传入请求
- `ResponseSendingEvent` - 加密传出响应

### 错误处理

该 Bundle 抛出特定异常：

- `EncryptAppIdMissingException` - 当缺少 Encrypt-AppID 头部时
- `EncryptAppIdNotFoundException` - 当 AppID 未找到或无效时

## 安全性

**加密详情：**
- 算法：AES-256-CBC
- 密钥派生：appSecret 的 SHA-256 哈希
- IV 生成：appId 的 MD5 哈希（16 字节）
- 数据编码：Base64

**安全注意事项：**
- 生产环境务必使用 HTTPS
- 定期轮换 appSecret
- 验证 AppID 权限
- 监控加解密失败情况
- 保护加密密钥安全

**重要安全提示：**
- 此 Bundle 提供数据加密但不能替代适当的身份验证
- 确保适当的密钥管理实践
- 考虑为加密端点实施速率限制

## 详细文档

- 加密流程详见 `DataEncryption.puml` 流程图
- 查看内联 PHPDoc 注释获取详细 API 文档
- 查看测试用例了解使用示例

## 贡献指南

1. Fork 此仓库
2. 创建功能分支
3. 遵循 PSR 代码规范
4. 确保新增功能有完整测试
5. 提交 pull request

**开发环境设置：**

```bash
git clone https://github.com/tourze/json-rpc-encrypt-bundle.git
cd json-rpc-encrypt-bundle
composer install
vendor/bin/phpunit
```

## 版权和许可

MIT 许可证 - 详见 [LICENSE](LICENSE) 文件。

版权所有 © Tourze 团队

## 更新日志

详见 [CHANGELOG.md](CHANGELOG.md) 了解发布说明和版本历史。