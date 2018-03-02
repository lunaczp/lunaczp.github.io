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

## 存储
- Ethereum上，存储是消耗gas的，而Fisco作为联盟链，去掉了gas的限制，所以可以不用考虑存储的消耗，而把业务数据都存储在Fisco上
- 另一方面，Fisco基于Ethereum，其存储底层是KV数据库。对于常规的业务需求，比如列表、分页等，使用起来有局限性。所以，一般地，除了数据上链外，还可以在关系型数据库内，冗余存储一份，用于业务查询使用。
