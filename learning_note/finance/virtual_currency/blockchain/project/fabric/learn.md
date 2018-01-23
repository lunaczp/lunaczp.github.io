# Basic Learning and Testing
入门学习Fabric

## Pre
- 已经掌握了Fabric的基本概念，如Channel，Peer，Org，Order等

## BYFN Build Your First Network
这是第一个模拟训练，该场景内，有两个组织/Org（代表实际场景当中的各个公司主体），每个Org有两个Peer（类似比特币的节点），每个Org都会设定一个Peer作为Anchor Peer，作为本组织的代理和其他组织沟通。

### 模拟流程
- 生成各个组织和节点需要的证书
	- 每个组织都有一个根证书，每个节点都有自己的公钥私钥用作身份认证
- 生成其他素材
	- 创世区块
	- channel的描述文件
	- Anchor Peer的描述文件
- 启动网络拓扑（基于docker，docker内定义了各个节点）
	- 1个Orderer节点
	- 4个Peer节点，每个组织2个
	- 1个Cli节点，用来后续执行命令，建立和调整该Blockchain网络
- 创建Channel
- 将各个节点加入Channel
- 在需要的节点上部署/install chaincode，（有准备好的测试chaincode代码在Cli节点上） 
	- `peer chaincode install -n mycc -v 1.0 -p github.com/chaincode/chaincode_example02/go/`
	- 可以4个节都部署，也可以部署1个或多个；后续还可以单独部署
- 实例化/instantiate chaincode,（This is the born of chaincode，Online point of business logic）.
	- `peer chaincode instantiate -o orderer.example.com:7050 --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem -C $CHANNEL_NAME -n mycc -v 1.0 -c '{"Args":["init","a", "100", "b","200"]}' -P "OR ('Org1MSP.member','Org2MSP.member')"`
	- 注意，实例化只需要一次即可，就像出生只有一次
- 执行查询功能
	- 注意，要指定Channel名字，因为一个Fabric集群支持多个Channel，每个Channel相互独立
	- 只能对部署了chaincode的节点执行查询操作，没有部署肯定查询不了。因为部署/install，就是就如它的字面意思一样，把chaincode的代码安装到对应节点的文件系统上，也只有安装了，节点才能查找到，你也才能调用。
	- 注意，这里的查询可以在Cli节点上，通过指定要查询的节点来执行，也可以直接登陆到安装了chaincode的节点，直接执行查询命令。当然在真实生产场景中，是通过sdk和rpc调用来查询的。
	- 对于之前没有安装的节点，自然是查询不了，但是现在可以单独执行安装，安装后，再次执行查询操作，稍等片刻（安装的时候并不会触发启动新的chaincode docker host，但是查询的时候，会触发，编译启动新的chaincode docker host），就会返回结果。
		- 如上面所提到的，所有安装了chaincode的节点，都会启动一个docker host来接收对该节点该chaincode的查询，当然，这个docker host启动的时间点是在查询真正发生的时候，而不是安装的时候
		- 之所以单独启动，一个原因是每个节点可以部署不同业务有的chaincode，甚至不同语言的chaincode，fabric这种实现方式相当于提前准备好各个语言的docker镜像，需要的时候直接启动就行，而且相互隔离。缺点就是一个chaincode一个docker host比较消耗资源。
- 测试写操作
	- `peer chaincode invoke -o orderer.example.com:7050  --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem  -C $CHANNEL_NAME -n mycc -c '{"Args":["invoke","a","b","10"]}'`
	- 这里其实就是调用之前安装的chaincode内体统的方法。方法是"invode"，参数是"a","b","10"，具体的语义是安装的chaincode代码定义的，比如这里提供的测试代码，意思就是“从
	a移动10到b”，这是代码层面的直接意义，至于业务层面的含义，就看写代码的时候是如何抽象的了。
		- 比如业务需求是两个人个有100，200RMB，两人可以相互购买东西，需要记录他们之间的所有资金往来，那么这个chaincode就可以来解决这个需求
		- 比如业务需求是一个银行/a，一个个人/b，银行可以贷款给个人（a->b)，个人也可以还款给银行（b->a），需要记录银行和个人的贷款还款记录，那个这个chaincode也可以用来解决这个需求。
		- 总结来讲，chaincode就是一段代码，是抽象化的业务逻辑。 它有一些数据元素，代表业务模型内的各个对象；它会有一些方法/函数，代表业务模型内的操作和状态转换。
		- 如果没有数据元素，chaincode就没有任何意义，就好像一个加法器，给定a,b，返回a+b，自身无状态。就好像没有记忆的实体，只能承载运算，不能承载意义。
		- 如果没有函数，chaincode也几乎没有任何意义，就好像一个永不会改变的实体，只有一个状态，没有变化，那么我们更无须关注。
- 再次查询确认写结果
- done

#### 备注
- 同一个Channel内的所有Peer都各自拥有一份完全一样的账本
	- 如果某一个Peer安装了某个chaincode，则可以对该Peer执行读写操作。但所有的变更都会同步到所有Peer，不管它们是否安装了某个chaincode。
	- 也就是说，账本是Channel内全局一致的。而chaincode可以只部署在某些Peer，用来提供读写服务/业务逻辑。
	- 如上，当生产使用时候，可以把账本本身作为一个底层服务，而chaincode作为业务接入，这样分层后，更方便服务治理。
