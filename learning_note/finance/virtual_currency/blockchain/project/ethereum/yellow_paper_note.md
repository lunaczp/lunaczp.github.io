# Yellow Paper note
yellow paper: _ETHEREUM: A SECURE DECENTRALISED GENERALISED TRANSACTION LEDGER BYZANTIUM VERSION 3931466 - 2018-02-25_

# Note
## The Blockchain Paradigm

## Blocks, States and Transactions

### World State
Ethereum维护一个链外的存储，叫做World State，反应的是所有账户/合约的当前最新状态
- 这个存储是独立于区块链的，链上只存储交易本身（创建合约的交易，调用交易）。
- 存储的数据被组织成Merkle Patricia tree，其root节点被存储在区块链上，从而保证链下数据库数据的不可篡改性。
- 存储的后端实际是是一个key-value数据库
	- solidity支持的数据类型，最终都会转化成key-value存储在数据库。大小理论上
	- 这个存储本身也是`immutable`的，数据变更是产生新的状态，而不是覆盖旧值

[Ref](https://medium.com/aigang-network/how-to-read-ethereum-contract-storage-44252c8af925)

### Transcation
- Ethereum的交易有两种类型
	- 消息调用 `those which result in message calls`
	- 新建合约 `those which result in the creation of new accounts with associated code (known informally as ‘contract creation’)`

## Gas And Pagment

## Transaction Execution

## Contract Creation

## Message Call

## Execution Model

## Blocktree to Blockchain

## Block Finalisation

## Implementing Contracts

## Future Directions

## Conclusion
