# Merkle Patricia Tree(MPT)

Ref:
- [MPT](https://github.com/ethereum/wiki/wiki/Patricia-Tree)
- [MPT](http://ethfans.org/toya/articles/588)

## Basic

## Tries in Ethereum
Ethereum中所有的tri都是MPT。

一个Block Header内有3个MPT的root：
- stateRoot
- transcationsRoot
- receiptsRoot

### State Trie
Ethereum维护一个World State，保存链上所有账户的最新状态。这个World State就是一个MPT，就是State Trie，每个Block内都包含这个MPT最新的root node，即stateRoot。

### Storage Trie
每个账户其实四个四元组`[nonce,balance,storageRoot,codeHash]`，由于每个账号（智能合约）都有自己的存储，这个存储也是一个MPT，即Storage Trie，storageRoot就是其root node。

### Transcations Trie
每个Block都包含若干交易，这些交易也被维护成一个MPT，即Transcations Trie，在矿工挖出该节点时候生成，且永不更改。

### Receipts Trie
每个Block也都有一个Receipts Trie。

以上，Ethereum维护一个Blockchain（类同比特币，传统区块链），和一个外部存储。
- 链上的block包含一组交易，所有交易被组织成一个MPT，叫Transaction Trie，其root node的hash就是block的hash。
- 外部存储用来存储所有链上账户的历史和最新状态，这些数据被维护成MPT，叫State Trie，每个block都包含一个stateRoot，就是该MPT的root node。
	- State Trie的每一个数据节点对应一个账号，保存的是一个账号的信息四元駔`[nonce,balance,storageRoot,codeHash]`
- 每一个账号，对应一个存储空间，这些数据也被组织成一个MPT，叫Storage Trie。其root node的key，保存在账号的四元駔
	- 这个账号的存储空间，存储的是智能合约的数据。

MPT底层是利用KV数据库来存储节点数据的。MPT内只保留节点的Key，然后在需要的时候，通过Key，在数据库中查出Value。

注意，在Ethereum的MPT中，数据是`immutable`的，也是就是所有的数据是不可更改的。所谓的变更，只会产生新的节点存储到数据库，然后把生成的Key替换到MPT中需要替换的位置。也就是说，历史数据是不会变更的。所有变更都是靠加入新节点实现的。

如此，提供了任意回滚的功能。只需要知道某一时刻的State Tri的root node，就等于确定了这一时刻的世界状态。

## MPT的key
- State Trie的`path`是地址（address）的hash，`sha3(ethereumAddress)`，`value`是`rlp(ethereumAccount)`，存储的是账号的信息。
- Transactions Trie的`path`是`rpl(transcationIndex)`，`value`是交易信息。
- Receipts Trie的`path`是`rpl(transcationIndex)`，`value`//todo
- Storage Trie的`path`是通过合约内的变量构造的，存储的是变量的值。//todo detail

## 更新
综上，假如一个account的balance发生了变化，那么需要更新State Trie。`sha3(account) => newAccountValue`，此时，State Trie内，对应`sha3(account)`搜索路径上的所有节点都需要变更。
其实是自下向上的，每一层都需要重新计算hash，导致每一层都会生成新的节点，一直到一个新的root node。  
由于底层是KV存储，假设有N层，那么新生成了至少N个KV对。如此，底层的存储读写是很频繁的，数据增量也是很可观的。这个在未来也许会是个问题（写入速度，与存储空间）
