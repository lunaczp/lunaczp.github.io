# Orderer
正如其名字所表示的，Orderer是Fabric内的一个排队服务，所有要写入区块链的交易，都要经过验证，然后发给Orderer排队，再由Orderer发送给Peer去真正写入区块链。

## 设计
由于支持Fabric支持一套系统，多个区块链（通过channel来区分），所有对Orderer而言，其操作也是以channel为单位的。

以“First—Network”场景为例，1个Orderer，2个组织，每个组织2个Peer。当完成初始化（生成各种证书及组件），并启动网络。

```
$  ~ docker ps
CONTAINER ID        IMAGE                        COMMAND             CREATED             STATUS              PORTS                                              NAMES
f053fe5f10c4        hyperledger/fabric-tools     "/bin/bash"         40 minutes ago      Up 40 minutes                                                          cli
a0f6fba6a519        hyperledger/fabric-peer      "peer node start"   40 minutes ago      Up 40 minutes       0.0.0.0:8051->7051/tcp, 0.0.0.0:8053->7053/tcp     peer1.org1.example.com
ba162fa79aca        hyperledger/fabric-peer      "peer node start"   40 minutes ago      Up 40 minutes       0.0.0.0:9051->7051/tcp, 0.0.0.0:9053->7053/tcp     peer0.org2.example.com
ea3bd8c47081        hyperledger/fabric-peer      "peer node start"   40 minutes ago      Up 40 minutes       0.0.0.0:10051->7051/tcp, 0.0.0.0:10053->7053/tcp   peer1.org2.example.com
d9df86737daf        hyperledger/fabric-orderer   "orderer"           40 minutes ago      Up 40 minutes       0.0.0.0:7050->7050/tcp                             orderer.example.com
bff4a03185e5        hyperledger/fabric-peer      "peer node start"   40 minutes ago      Up 40 minutes       0.0.0.0:7051->7051/tcp, 0.0.0.0:7053->7053/tcp     peer0.org1.example.com
```

首先创建channel
=====

```
export CHANNEL_NAME=mychannel
peer channel create -o orderer.example.com:7050 -c $CHANNEL_NAME -f ./channel-artifacts/channel.tx --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem
```

此时，Orderer收到请求，创建channel，并为该channel生成一个区块链（注意此链不是该channel对数据链，而是Orderer用来记录channel配置的链）
```
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# pwd
/var/hyperledger/production/orderer/chains/mychannel
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# ll
total 20
drwxr-xr-x 2 root root  4096 Jan 25 09:11 ./
drwxr-xr-x 4 root root  4096 Jan 25 09:11 ../
-rw-r----- 1 root root 11980 Jan 25 09:11 blockfile_000000
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel#
```

这个初始块内包含了类似Orderer自身创世块的9个证书(参见[MSP](msp)，以及Orderer自己的证书。
```
$  mychannel grep -a  "END CER" blockfile_000000 -B1|grep -v "END\|\-\-"|sort|uniq |wc -l
      10
$  mychannel grep -a  "END CER" blockfile_000000 -B1|grep -v "END\|\-\-"|sort|uniq|xargs -I{} sh -c "grep -ar {} /Users/nuc/fabric/fabric-samples/first-network/crypto-config;"|grep signcerts|grep orderer.example
/Users/nuc/fabric/fabric-samples/first-network/crypto-config/ordererOrganizations/example.com/orderers/orderer.example.com/msp/signcerts/orderer.example.com-cert.pem:Bezm0h6Hg1Evpizx2MECIGtRP0ahweubMgnUjmSgq7Vf8X+tJF10oA0hxuv+E+SL
```

加入channel
=====

上一步创建channel后，会返回一个创世块，mychannel.block，节点可以用这个块来加入channel。注意这个块和Orderer生成的块基本一致，除了文件头尾有些不同。
```
# 从docker提取到本地后查看
$ ls -l
total 48
-rw-r-----  1 nuc  staff  11980  1 25 17:45 blockfile_000000
-rw-r--r--  1 nuc  staff  11987  1 25 17:37 mychannel.block
```


执行命令，加入channel
```
CORE_PEER_MSPCONFIGPATH=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp
CORE_PEER_ADDRESS=peer0.org1.example.com:7051
CORE_PEER_LOCALMSPID="Org1MSP"
CORE_PEER_TLS_ROOTCERT_FILE=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org1.example.com/peers/peer0.org1.example.com/tls/ca.crt
peer channel join -b mychannel.block
```

此时，peer0上就有了这个区块链
```
root@bff4a03185e5:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll
total 20
drwxr-xr-x 2 root root  4096 Jan 25 09:41 ./
drwxr-xr-x 3 root root  4096 Jan 25 09:41 ../
-rw-r----- 1 root root 11981 Jan 25 09:41 blockfile_000000
root@bff4a03185e5:/var/hyperledger/production/ledgersData/chains/chains/mychannel#
```
该区块与Orderer的基本一致。


然后把peer0.org2也加入
```
CORE_PEER_MSPCONFIGPATH=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp CORE_PEER_ADDRESS=peer0.org2.example.com:7051 CORE_PEER_LOCALMSPID="Org2MSP" CORE_PEER_TLS_ROOTCERT_FILE=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/peers/peer0.org2.example.com/tls/ca.crt peer channel join -b mychannel.block
```
此时peer0.org2上也有了该区块
```
root@ba162fa79aca:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll
total 20
drwxr-xr-x 2 root root  4096 Jan 25 09:44 ./
drwxr-xr-x 3 root root  4096 Jan 25 09:44 ../
-rw-r----- 1 root root 11981 Jan 25 09:44 blockfile_000000
```
与peer0.org1完全一样

```
# 从docker提取到本地后对比
$  mychannel ls -l
total 72
-rw-r-----  1 nuc  staff  11980  1 25 17:11 blockfile_000000_orderer
-rw-r-----  1 nuc  staff  11981  1 25 17:41 blockfile_000000_peer0.org1
-rw-r-----  1 nuc  staff  11981  1 25 17:41 blockfile_000000_peer0.org2

$  mychannel diff -a  blockfile_000000_orderer blockfile_000000_peer0.org1
1c1
< �] �Q󶏤�K� o3#�-NGl�-�l��R����%�]
---
> �] �Q󶏤�K� o3#�-NGl�-�l��R����%�]
283c283
�d|acf~�)/h
\ No newline at end of file
---
�d|acf~�)/h
\ No newline at end of file
$  mychannel diff -a blockfile_000000_peer0.org1 blockfile_000000_peer0.org2
$  mychannel
```
如上，加入channel块，并不为改变区块链

设定Anchor Peer
=====
```
CORE_PEER_MSPCONFIGPATH=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp
CORE_PEER_ADDRESS=peer0.org1.example.com:7051
CORE_PEER_LOCALMSPID="Org1MSP"
CORE_PEER_TLS_ROOTCERT_FILE=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org1.example.com/peers/peer0.org1.example.com/tls/ca.crt
peer channel update -o orderer.example.com:7050 -c $CHANNEL_NAME -f ./channel-artifacts/Org1MSPanchors.tx --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem
```

设定Anchor Peer后，Orderer，Peer0.Org1，Peer0.Org2的区块都更新了(配置更新，会更新区块链)
```
#orderer
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# ll
total 36
drwxr-xr-x 2 root root  4096 Jan 25 09:11 ./
drwxr-xr-x 4 root root  4096 Jan 25 09:11 ../
-rw-r----- 1 root root 25802 Jan 25 09:56 blockfile_000000

#peer0.org1
root@bff4a03185e5:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll
total 36
drwxr-xr-x 2 root root  4096 Jan 25 09:41 ./
drwxr-xr-x 3 root root  4096 Jan 25 09:41 ../
-rw-r----- 1 root root 25804 Jan 25 09:56 blockfile_000000

#peer0.org2
root@ba162fa79aca:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll
total 36
drwxr-xr-x 2 root root  4096 Jan 25 09:44 ./
drwxr-xr-x 3 root root  4096 Jan 25 09:44 ../
-rw-r----- 1 root root 25804 Jan 25 09:56 blockfile_000000
```

```
#提取到本机对比
$  mychannel diff -a blockfile_000000_orderer_s2 blockfile_000000_peer0.org1_s2
1c1
< �] �Q󶏤�K� o3#�-NGl�-�l��R����%�]
---
> �] �Q󶏤�K� o3#�-NGl�-�l��R����%�]
283c283
�d|acf~�)/h�k �d�m�Mu>�j+��P,I7^t�+�L��n�.� 0� ŠBM��H4�d���)9�遗@��:��C�]
---
�d|acf~�)/h�k �d�m�Mu>�j+��P,I7^t�+�L��n�.� 0� ŠBM��H4�d���)9�遗@��:��C�]
602c602
< ���$
\ No newline at end of file
---
> ���$
\ No newline at end of file
$  mychannel diff -a blockfile_000000_peer0.org1_s2 blockfile_000000_peer0.org2_s2
$  mychannel
```
peer0.org1和peer0.org2完全一样，然后与orderer少许不同。


将peer0.org2也设置为Anchor Peer
```
CORE_PEER_MSPCONFIGPATH=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp CORE_PEER_ADDRESS=peer0.org2.example.com:7051 CORE_PEER_LOCALMSPID="Org2MSP" CORE_PEER_TLS_ROOTCERT_FILE=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/peers/peer0.org2.example.com/tls/ca.crt peer channel update -o orderer.example.com:7050 -c $CHANNEL_NAME -f ./channel-artifacts/Org2MSPanchors.tx --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem
```
区块链再次更新
```
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 39682 Jan 25 10:08 blockfile_000000

root@bff4a03185e5:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 39685 Jan 25 10:08 blockfile_000000

root@ba162fa79aca:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 39685 Jan 25 10:08 blockfile_000000
```

部署代码
=====

```
peer chaincode install -n mycc -v 1.0 -p github.com/chaincode/chaincode_example02/go/
```

查看
```
root@bff4a03185e5:/var/hyperledger/production/chaincodes# ll
total 12
drwxr-xr-x 2 root root 4096 Jan 25 10:21 ./
drwxr-xr-x 1 root root 4096 Jan 25 09:41 ../
-rw-r--r-- 1 root root 1873 Jan 25 10:21 mycc.1.0
```
注意，安装的go代码不是原始的代码，应是编译过的。

实例化/初始化代码
=====
注，初始化代码，只应该执行一次。
```
peer chaincode instantiate -o orderer.example.com:7050 --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem -C $CHANNEL_NAME -n mycc -v 1.0 -c '{"Args":["init","a", "100", "b","200"]}' -P "OR ('Org1MSP.member','Org2MSP.member')"
```

区块链更新
```
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 44997 Jan 25 10:30 blockfile_000000

root@bff4a03185e5:/var/hyperledger/production/chaincodes# ll /var/hyperledger/production/ledgersData/chains/chains/mychannel/blockfile_000000
-rw-r----- 1 root root 45001 Jan 25 10:30 /var/hyperledger/production/ledgersData/chains/chains/mychannel/blockfile_000000

root@ba162fa79aca:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 45001 Jan 25 10:30 blockfile_000000
```
