# Private Network-多节点网络


# 使用bootnode节点建立private network 
使用bootnode，让各个节点发现彼此。其实bootnode就类似是中心注册服务，各个节点连接到bootnode，来获取其他节点。

- 启动bootnode节点
- 各个节点
	- 初始化
	- 启动，连接到bootnode

## 启动bootnode
```
bootnode --genkey=boot.key
bootnode --nodekey=boot.key
```
bootnode的配置很简单，只需要一个key就可以。启动后，会监听一个端口，供其他其他服务连接。


## 启动各个节点
每个节点都要执行下面的操作
- 准备genesis.json创世文件，注意所有节点的genesis.json完全一样
- 利用genesis.json初始化数据库
```
geth --datadir path/to/custom/data/folder init genesis.json
```
- 启动节点，
	- 如果在一个机器上，每个节点指定不同的数据目录
	- 如果在一个机器上，每个节点指定不同的port和rpcport，不然会提示被占用。
	- 指定bootnode的地址
	- 指定网络id，在genesis.json中配置
```
geth --datadir path/to/custom/data/folder --networkid 15 --bootnodes <bootnode-enode-url-from-above>
```
- done
- 验证
可以连接到console验证
```
geth --datadir DIR console
> net.peerCount
2
> admin.peers
...
```
注意，在3节点的情况下（1个bootnode节点，3个peer节点），连接到每个peer，看到的`net.peerCount=2`，`admin.peers`也会显示其他2个节点的信息，而不包括自己。

## Ref
[Connecting to the network](https://github.com/ethereum/go-ethereum/wiki/Connecting-to-the-network)
[Setting up private network](https://github.com/ethereum/go-ethereum/wiki/Setting-up-private-network-or-local-cluster)
[Private network](https://github.com/ethereum/go-ethereum/wiki/Private-network)
[Blog create a private network](https://omarmetwally.blog/2017/07/25/how-to-create-a-private-ethereum-network/)
[Blog create multi node network](https://omarmetwally.blog/2017/09/27/how-to-connect-3-ethereum-nodes-in-a-private-ethereum-network/)

