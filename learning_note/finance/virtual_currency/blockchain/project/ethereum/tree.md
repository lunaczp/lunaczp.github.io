# Merkle Patricia Tree(MPT)

[Ref](https://github.com/ethereum/wiki/wiki/Patricia-Tree)

## Basic

## Tree in Ethereum
Ethereum中所有的tri都是MPT。一个Block Header内有3个MPT的root：
- stateRoot
- transcationsRoot
- receiptsRoot

### state Root
Ethereum维护一个World State，保存链上所有账户的最新状态。这个World State就是一个MPT，每个Block内都包含这个MPT最新的root node，即state root。

另外，每个账户其实四个四元组`[nonce,balance,storageRoot,codeHash]`，由于每个账号（智能合约）都有自己的存储，这个存储也是一个MPT，storageRoot就是其root node。

### transcationsRoot
每个Block都包含若干交易，这些交易也被维护成一个MPT，在矿工挖出该节点时候生成，且永不更改。
每个Block也都有一个receipts trie。
