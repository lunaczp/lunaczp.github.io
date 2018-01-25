# Orderer
正如其名字所表示的，Orderer是Fabric内的一个排队服务，所有要写入区块链的交易，都要经过验证，然后发给Orderer排队，再由Orderer发送给Peer去真正写入区块链。

- 一个典型的Orderer区块链变化，参考[byfn](byfn)
