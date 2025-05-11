# Json-RPC Encrypt Bundle 测试计划

## 单元测试覆盖情况

| 类名 | 测试覆盖状态 | 测试文件 |
| --- | --- | --- |
| JsonRPCEncryptBundle | ✅ 已完成 | JsonRPCEncryptBundleTest |
| DependencyInjection/JsonRPCEncryptExtension | ✅ 已完成 | DependencyInjection/JsonRPCEncryptExtensionTest |
| Service/Encryptor | ✅ 已完成 | Service/EncryptorTest |
| EventSubscriber/EncryptSubscriber | ✅ 已完成 | EventSubscriber/EncryptSubscriberTest |
| Exception/EncryptAppIdNotFoundException | ✅ 已完成 | Exception/EncryptExceptionTest |
| Exception/EncryptAppIdMissingException | ✅ 已完成 | Exception/EncryptExceptionTest |

## 集成测试覆盖情况

| 测试场景 | 测试覆盖状态 | 测试文件 |
| --- | --- | --- |
| Encryptor与ApiCallerRepository集成 | ✅ 已完成 | Integration/JsonRpcEncryptIntegrationTest |
| EncryptSubscriber事件响应集成测试 | ✅ 已完成 | Integration/EventSubscriber/EncryptSubscriberIntegrationTest |
| 完整加解密流程端到端测试 | ✅ 已完成 | Integration/EndToEndJsonRpcEncryptionTest |

## 测试结果摘要

- 测试总数: 35
- 断言总数: 72
- 通过率: 100%

## 主要测试场景

1. **加解密服务测试**
   - AES-256-CBC加密算法验证
   - 密钥生成逻辑验证
   - 请求解密流程测试
   - 响应加密流程测试
   - 无效AppID和密钥场景测试

2. **事件订阅器测试**
   - 事件注册验证
   - 请求解密处理流程
   - 响应加密处理流程
   - 解密失败错误处理
   - JSON格式校验测试

3. **异常处理测试**
   - 缺少AppID异常
   - AppID无效异常
   - 异常继承结构

4. **Bundle集成测试**
   - Bundle实例化验证
   - 服务容器依赖注入测试

5. **集成测试场景**
   - 模拟ApiCallerRepository测试
   - 完整的端到端加解密流程
   - 多AppID和密钥隔离性测试
   - 异常和错误路径测试

## 注意事项

- 已解决qiniu SDK废弃警告问题
- 所有单元测试均使用Mock对象，无需实际数据库连接
- 集成测试通过内存SQLite数据库进行测试，保证隔离性
- 测试覆盖了正常流程和异常场景处理

## 性能考量

- 对于大批量请求的场景，建议为ApiCallerRepository添加缓存
- AES-256-CBC性能开销在正常范围内
- 在高并发场景下，加解密可能成为性能瓶颈，可考虑异步处理

## 安全性建议

- 定期轮换AppSecret，保证数据安全
- 考虑增加一次性nonce防止重放攻击
- 敏感信息传输使用TLS加密通道
- 考虑增加响应签名验证机制

## 兼容性测试

- 与JSON-RPC Core兼容
- 与JSON-RPC Endpoint无冲突
- 已验证对非加密流量无影响

## 下一步测试计划

- 考虑添加集成测试，验证与JsonRPC Core和JsonRPC Caller的交互
- 考虑添加性能测试，验证大量请求下的加解密性能

## 特殊情况

- 不需要配置文件测试，因为此Bundle使用默认配置
- 不需要启动Bootstrap，因为测试使用PHPUnit标准配置
