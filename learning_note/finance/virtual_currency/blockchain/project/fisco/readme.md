# Fisco
[FISCO](https://github.com/FISCO-BCOS) 是金融区块链合作联盟开源的区块链项目，基于ethereum修改而来。

## 概念
Fisco沿用Ethereum的概念，相对于传统比特币区块链，Fisco可以运行图灵完备的智能合约。利用智能合约，来实现业务逻辑。

### Fisco相对Ethereum的修改
todo

## 使用-安装
[文档](https://github.com/FISCO-BCOS/FISCO-BCOS/tree/master/doc/manual)

- 操作系统依赖
	- CentOS （7.2 64位）或Ubuntu（16.04 64位）
- 编译安装

安装后的Fisco提供了一些工具集，用于
- 生成配置文件
- 生成证书
- 启动网络

相对于Fabric的完整强大，Fisco更简单，只用几个二进制文件就可以了（其实核心就是一个二进制程序，用来启动节点）。

## 使用-单节点区块链
Fisco需要一个创世节点。在单节点区块链中，只有一个节点，也就是创世节点。

- 生成god账号(全链唯一）
- 生成node身份ID（公钥、私钥），节点唯一
- 生成证书（根证书和节点证书），根证书全链唯一，节点证书节点唯一
- 配置
	- 创世块配置文件(genesis.json)
		- 配置内置管理账号（god），创世块节点ID
		- 同一链，有唯一的创世块文件
	- 节点配置文件
		- 配置共识算法，p2p端口，rpc端口，系统合约地址、钱包地址、日志、数据文件路径
		- 每个节点
	- 日志配置文件
		- 配置日志格式等
- 启动
	- 启动该节点`./start.sh`
- 部署合约
- 部署系统合约。系统合约用来做系统管理（类似系统服务），多节点组网等功能需要。
	- 生成系统代理地址
	- 配置config.json，加入生成的代理地址(未来多节点组网，每个节点都需要配置系统合约)

## 使用-多节点区块链

### 准备
- 多个节点已经配置完成
- 系统合约已经在每个节点配置

### 组网
- 注册创世节点
	- 编写注册文件（节点名称、hash地址、ip、port）
	- 命令行注册`babel-node tool.js NodeAction registerNode node1.json`
- 注册其他节点
- 验证
	- 查看入网情况`babel-node tool.js NodeAction all`
	- 检测节点`babel-node monitor.js`
- 节点退出网络
	- `babel-node tool.js NodeAction cancelNode node2.json`

注意：
- 创世节点必须先注册
- 注册第N个节点前，前N-1个已经注册的节点必须已经启动（要接收新的注册信息）。


## 使用-机构认证
todo

## 使用-工具

### 控制台
```
# 登陆
ethconsole /mydata/nodedata-1/data/geth.ipc
# 查看块高
web3.eth.getBlock(2,console.log)
# 查看交易
web3.eth.getTransaction('0x63749a62851b52f9263e3c9a791369c7380acc5a9b6ee55dabd9c1013634e355',console.log)
# 查看交易回执
web3.eth.getTransactionReceipt('0x63749a62851b52f9263e3c9a791369c7380acc5a9b6ee55dabd9c1013634e355',console.log)
# 查看合约代码
web3.eth.getCode('0x1d2047204130de907799adaea85c511c7ce85b6d',console.log)
# 查看节点连接
web3.admin.getPeers(console.log)
```
