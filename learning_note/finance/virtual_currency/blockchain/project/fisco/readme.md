# Fisco

## List
- [Fisco Basic](./basic.md)
- [Evidence Example](https://github.com/lunaczp/evidenceSample/tree/master/evidence)

## Other

### Fisco相对于Ethereum的调整
Ethereum是一个开放链，采用PoW，支持自定义的智能合约。Fisco的目标是建立一个联盟链，基于Ethereum改造而来。针对联盟链的场景，其改造有：
- 采用PBFT，而不是Pow
- 去掉Gas、和Ether币的逻辑。
- 添加AMOP协议(消息系统)，方便节点间通信
- 添加CNS模块，方便合约调用和代码升级，版本管理，
