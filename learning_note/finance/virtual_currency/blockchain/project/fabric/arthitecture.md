# Arthitecture

优势：
order和chaincode隔离
扩展性：将耗时的chaincode从order主路径剥离。chaincode之间分别部署也带来扩展性
共识算法的模块化，可配置（共识算法只要集中在orderer内）

# 系统架构

## 交易

## 数据结构

### 状态
分布式账本，维护的其实就是状态。Fabric底层存储模型就是一个KV的键值存储。交易改变数据，进入下一个状态。

不同的Chaincode会有不同的数据，在底层都是KV存储。只有Chainocde自己能够修改属于自己的数据。但是所有chaincode都可以读取全局底层的KV。也就是说底层的KV数据读权限是对所有Chaincode开放的。


### Ledger
Ledger记录所有交易的hash
- 包含成功的交易和不成功交易
- 所有交易都是有序的，Orderer保证block 之间有序，也保证block内的交易有序。
- 所有Peer都有一份完整账本，且标记了成功的交易，和不成功的交易
- 某些Orderer也可能会有账本，但不会标记交易是否成功

Peer可以通过Ledger重放所有交易过程，所以其实上面提到的State，是可选的组件，即使没有。也完全可以通过Ledger计算出任意时刻的全局状态。


## Node
Node，节点，有三种类型
- Client 作为客户端，向endorser提交transaction-invocation，并将返回的transaction-proposal广播给Orderer
- Peer 交易的真正执行者和账本的维护者。部分节点可以承担endorser的角色
- Orderer 排队服务节点

### Client
Client 代表普通用户和区块链交互。

Client同时连接到Orderer和Peer。

Client可以选择任意Peer节点。


### Peer
Peer节点可以同时承担endorser的角色，此时，它负责对一个transaction-invocation进行验证（endorsering）。

注意，endorser角色是对应到chaincode的，一个chaincode会有一个endorsement policy。一般地，这个policy就是指定需要某几个节点的签名（对应到比特币网络内，交易就是资金转移，资金转移需要资金持有方的签名，这个endorsement policy就是：要有资金持有方的签名）。  

而一个endorser角色，就是说这个Peer作为某个chaincode的endorser。当一个针对这个chaincode的transaction-invocation提交过来的时候，该Peer需要负责验证这个invocation是否符合chaincode设定的endorsement policy。并返回是否符合。

这里，Peer的endorser角色，其实就是为某个chainocode把关，对所有想要提交的请求，都endorsing the chaincode's endorsement policy.

### Orderer
排队服务，所有transaction
- 首先，由client提交transaction-invocation给endorser，
- endorsement policy满足后，由cient提交transaction-proposal给orderer。orderer负责排序，生成block，并最终发给peer，写入区块链。

这里，Orderer提供的就是就是写入区块链前的排队服务，及通信服务。

_抽象来看_

Orderer提供一个共享信道给Peer和Client。Client提交的所有请求都会在Orderer汇集成block，并最终一致地到达每个Peer，并写入区块链，从而实现每个Peer的账本全局一致。

Orderer保证
- 请求/交易的全局一致性
- 交易的完整性
- 不重复（可选，不完全保证）

_从实现来看_

Orderer可以使用Kafka组件，一个channel对应一个topic，所有的transaction-proposal，都由client提交到Orderer，Orderer发送到Kafka topic。然后对应到这个chaincode的所有peer其实就是consumer。会一致地收到Kafka的消息。注意这里的topic只能有一个分区（Kafka保证分区内绝对有序）。也即，这里的交易有序性和消息传递，依靠Kafka来保证。


# 交易验证的流程 Basic workflow of transaction endorsement

##  The client creates a transaction and sends it to endorsing peers of its choice
根据endorsement policy，client可能需要多个peer的endorsement，所以client会按照自己的选择，发送给多个endorser，要求endorsement。

注意，client可以指定一个anchor，典型地，是一组当前全局状态下的特定KV值，用来后面提供给Peer做MVCC校验。

## The endorsing peer simulates a transaction and produces an endorsement signature
endorser执行endorsement（主要就是签名），并返回成功数据，或者失败。

## The submitting client collects an endorsement for a transaction and broadcasts it through ordering service
client会根据endorsement policy来收集多个peer的endorsement。注意，这里的policy校验是由client端完成的，并不是由上一步骤的endorser。endorser只负责给收到的transaction-invocation作endorsement（其实主要就是签名)。如果收集到足够的endorsement，则client整理这些信息，向Orderer提交transaction-proposal。否则，client可以选择过段时间后重试。

这个有点类似于：
- chaincode的 endorsement policy是，我需要A组织和B组织都同意，才能执行。
- Client去需要分别去找A组织和B组织，拿到他们的签名，代表说他们都同意了。
- 然后Client再把这些签名和自己的请求收集起来，提交给Orderer，来进行最终的排序于写入

其实就是一个权限问题。比如发射重要武器，必须由多个军官分别确认。

另外这里边的认证核心其实就是Client和A、B的交互。只有在A、B信任Client的情况下，Client的请求才可能/也应该最后被执行。
也即，Chaincode的执行权限取决于设定的endorsement policy。policy决定了需要哪些参与方的认证。然后只有这些参与方信任的个体/Client，才有权限执行chaincode。

再举例，如某些业务需要多个银行间合作，那么只有被这些银行共同认证/背书的交易，才被允许。这样就提供了一个灵活的权限控制，映射到实际世界中。

## The ordering service delivers a transactions to the peers
Orderer将一组交易（block）发送给peer，peer把block写入区块链，并对每个交易
- 验证其是否满足对应到endorsement policy。
- 根据policy配置，可选地执行MVCC校验，防止发生错误。
- 通过验证的，标记为有效；否则标记无效。

注意，这里peer会把新的block写入区块链，而不管block内的交易是否有效。之所以这么设计感觉是权衡之后的选择：
- 如果要针对每个交易校验，并撤回无效交易，那么就要通知Orderer，区块内的部分交易无效，Orderer需要剔除无效交易，然后重新组块。这样数据逆向流动，复杂度极大增高，是很难实现并维护的。
- 或者一个block只包含一个交易，这样当peer认为无效的时候，直接丢掉即可。但这样，区块链的利用率将降低很多，而且系统性能会极大下降。

最终的方案，就是由Orderer发送到peer的新块，peer无条件写入区块链（永久持久化），然后单独维护一个位图，来区分有效交易和无效交易。这样的代价就是区块链内会存在无效的交易。那么为了减少这种可能性，在Client提交交易之前就会验证好policy。那么当最后peer校验的时候，一般地，policy校验不会出问题。唯一可能让交易无效的就是MVCC校验出错，这样，能够在一定程度上减少无效区块在区块链中的占比。 当然随着交易并发的增加，不满足MVCC校验的交易会增多，从而还是会导致区块链的无效交易增多。

![transaction flow](img/trancation_flow.png)

# Endorsement policies
背书策略/证明策略

其实就是一个谓词，结果是TRUR或者FALSE

比如：
```
Suppose the chaincode specifies the endorser set E = {Alice, Bob, Charlie, Dave, Eve, Frank, George}. Some example policies:

A valid signature from on the same tran-proposal from all members of E.
A valid signature from any single member of E.
Valid signatures on the same tran-proposal from endorsing peers according to the condition (Alice OR Bob) AND (any two of: Charlie, Dave, Eve, Frank, George).
```

抽象来说，这个就是来自于实际生活中的需要：一个决策，需要多方协作。规则/策略是什么呢：所有人通过，1/3通过，任意一个人通过。这些，就是策略。

#  (post-v1). Validated ledger and PeerLedger checkpointing (pruning)
由上面可知，在Fabric v1中区块链内其实是包含无效交易的。这个是一种权衡，但也确实造成了区块链空间的浪费。因而在后续Fabric 的开发中，将尝试使用一下技术来应对这个问题。
- VLedger  
VLedger是过滤掉了无效交易的区块链，由各个Peer自己生成。
- PeerLedger Prune
配合VLedger，我们可以把无效的交易从而PeerLedger中修剪（Prune）出去，从而节约空间。但是我们不能简单地就修改PeerLedger，不然，当有新的Peer加入的时候，原有的PeerLedger是修改过的，无法验证。而新的Peer也无法验证VLedger的完整性。这时候，我们考虑采用 _Checkpointing protocol_：
 - 规定，在经过一段时间后，全局所有Peer执行修剪投票，当投票满足，则每个节点各自执行修剪。
