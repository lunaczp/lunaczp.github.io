# BYFN Build Your First Network
“First—Network”场景，1个Orderer，2个组织，每个组织2个Peer。

生成素材
=====
生成各个公钥，私钥和CA //todo

启动网络
=====
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

注意，此时会启动一个新的container，所有对该channel的操作，并发送到peer0.org1的，都会有该container执行
```
$  bin docker ps|grep mycc
c96c6cdcde9a        dev-peer0.org1.example.com-mycc-1.0-384f11f484b9302df90b453200cfb25174305fce8f53f4e94d45ee3b6cab0ce9   "chaincode -peer.add…"   15 minutes ago      Up 15 minutes                                                          dev-peer0.org1.example.com-mycc-1.0
```

调用/查询
=====
```
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# peer chaincode query -C $CHANNEL_NAME -n mycc -c '{"Args":["query","a"]}'
2018-01-25 10:43:44.683 UTC [msp] GetLocalMSP -> DEBU 001 Returning existing local MSP
2018-01-25 10:43:44.683 UTC [msp] GetDefaultSigningIdentity -> DEBU 002 Obtaining default signing identity
2018-01-25 10:43:44.683 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 003 Using default escc
2018-01-25 10:43:44.684 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 004 Using default vscc
2018-01-25 10:43:44.684 UTC [chaincodeCmd] getChaincodeSpec -> DEBU 005 java chaincode disabled
2018-01-25 10:43:44.685 UTC [msp/identity] Sign -> DEBU 006 Sign: plaintext: 0AAB070A6708031A0C08E0E9A6D30510...6D7963631A0A0A0571756572790A0161
2018-01-25 10:43:44.685 UTC [msp/identity] Sign -> DEBU 007 Sign: digest: EEED3BC189170A806331E008CE0787A795861A4D7AC17AB79320B0587FFCE896
Query Result: 100
2018-01-25 10:43:44.703 UTC [main] main -> INFO 008 Exiting.....
```

注意，查询是指向peer0.org1的，如果修改环境变量，执行peer0.org2，则会失败，因为peer0.org2还没有安装该chaincode
```
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# CORE_PEER_MSPCONFIGPATH=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# CORE_PEER_ADDRESS=peer0.org2.example.com:7051
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# CORE_PEER_LOCALMSPID="Org2MSP"
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# CORE_PEER_TLS_ROOTCERT_FILE=/opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/peerOrganizations/org2.example.com/peers/peer0.org2.example.com/tls/ca.crt
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# peer chaincode query -C $CHANNEL_NAME -n mycc -c '{"Args":["query","a"]}'
2018-01-25 10:48:23.895 UTC [msp] GetLocalMSP -> DEBU 001 Returning existing local MSP
2018-01-25 10:48:23.895 UTC [msp] GetDefaultSigningIdentity -> DEBU 002 Obtaining default signing identity
2018-01-25 10:48:23.895 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 003 Using default escc
2018-01-25 10:48:23.896 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 004 Using default vscc
2018-01-25 10:48:23.896 UTC [chaincodeCmd] getChaincodeSpec -> DEBU 005 java chaincode disabled
2018-01-25 10:48:23.896 UTC [msp/identity] Sign -> DEBU 006 Sign: plaintext: 0AAB070A6708031A0C08F7EBA6D30510...6D7963631A0A0A0571756572790A0161
2018-01-25 10:48:23.897 UTC [msp/identity] Sign -> DEBU 007 Sign: digest: 446995FDF0C2C7CDFB6A15463F8AB532799E8CFBE5CC666CE42E96A4991C276F
Error: Error endorsing query: rpc error: code = Unknown desc = cannot retrieve package for chaincode mycc/1.0, error open /var/hyperledger/production/chaincodes/mycc.1.0: no such file or directory - <nil>
```
可以看到最后的提示，是找不到对应的代码。


调用/写操作
=====
调用chaincode的“invoke”方法，参数为`a`,`b`,`10`。
invoke方法作用是：从a移动10到b。

```
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# peer chaincode invoke -o orderer.example.com:7050  --tls --cafile /opt/gopath/src/github.com/hyperledger/fabric/peer/crypto/ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem  -C $CHANNEL_NAME -n mycc -c '{"Args":["invoke","a","b","10"]}'
2018-01-25 10:50:09.318 UTC [msp] GetLocalMSP -> DEBU 001 Returning existing local MSP
2018-01-25 10:50:09.319 UTC [msp] GetDefaultSigningIdentity -> DEBU 002 Obtaining default signing identity
2018-01-25 10:50:09.331 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 003 Using default escc
2018-01-25 10:50:09.331 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 004 Using default vscc
2018-01-25 10:50:09.331 UTC [chaincodeCmd] getChaincodeSpec -> DEBU 005 java chaincode disabled
2018-01-25 10:50:09.332 UTC [msp/identity] Sign -> DEBU 006 Sign: plaintext: 0AAB070A6708031A0C08E1ECA6D30510...696E766F6B650A01610A01620A023130
2018-01-25 10:50:09.332 UTC [msp/identity] Sign -> DEBU 007 Sign: digest: FC74A903A4BC949FF6068F0C0EFF5ED8BB013351F404DCBFD2A7B4A71FE91071
2018-01-25 10:50:09.392 UTC [msp/identity] Sign -> DEBU 008 Sign: plaintext: 0AAB070A6708031A0C08E1ECA6D30510...DE0FFCFB42146D53C9E0F4AA88F08AFE
2018-01-25 10:50:09.392 UTC [msp/identity] Sign -> DEBU 009 Sign: digest: 37A6C223D96B509EA77C2D442D424F765DECC75AFC9291DB04207F313464C21E
2018-01-25 10:50:09.396 UTC [chaincodeCmd] chaincodeInvokeOrQuery -> DEBU 00a ESCC invoke result: version:1 response:<status:200 message:"OK" > payload:"\n g\304\220FD\263\213\256\204\230\313\022\"\221\227\3352\354\320\001\202\241\370m\226#\300\344,d\233|\022Y\nE\022\024\n\004lscc\022\014\n\n\n\004mycc\022\002\010\003\022-\n\004mycc\022%\n\007\n\001a\022\002\010\003\n\007\n\001b\022\002\010\003\032\007\n\001a\032\00290\032\010\n\001b\032\003210\032\003\010\310\001\"\013\022\004mycc\032\0031.0" endorsement:<endorser:"\n\007Org1MSP\022\226\006-----BEGIN CERTIFICATE-----\nMIICGjCCAcCgAwIBAgIRAO2dGWlpcPDbqP1Y6EDMnTAwCgYIKoZIzj0EAwIwczEL\nMAkGA1UEBhMCVVMxEzARBgNVBAgTCkNhbGlmb3JuaWExFjAUBgNVBAcTDVNhbiBG\ncmFuY2lzY28xGTAXBgNVBAoTEG9yZzEuZXhhbXBsZS5jb20xHDAaBgNVBAMTE2Nh\nLm9yZzEuZXhhbXBsZS5jb20wHhcNMTgwMTI1MDkwMjM1WhcNMjgwMTIzMDkwMjM1\nWjBbMQswCQYDVQQGEwJVUzETMBEGA1UECBMKQ2FsaWZvcm5pYTEWMBQGA1UEBxMN\nU2FuIEZyYW5jaXNjbzEfMB0GA1UEAxMWcGVlcjAub3JnMS5leGFtcGxlLmNvbTBZ\nMBMGByqGSM49AgEGCCqGSM49AwEHA0IABN0tqYAH6hD5FAjEZ7DkTr8S9TXM4zLo\n1TSlJoaiGoDPxwqE7pc5hCaIHYKQRCxAhRhyQprxyFScuQOwhB8lRiajTTBLMA4G\nA1UdDwEB/wQEAwIHgDAMBgNVHRMBAf8EAjAAMCsGA1UdIwQkMCKAIFJVeZuGMO5f\neW04vXdk/hZhLBsyquzuc+aeHD6TiPjPMAoGCCqGSM49BAMCA0gAMEUCIQDL/AgH\nv7dvXiZgIsCuuc4X9RweqalSKdxrHIbb4RUdEAIgYd13Lepg0znOCj7c4Zmlr7nv\nTzstEShI3PZJ+gspeQs=\n-----END CERTIFICATE-----\n" signature:"0E\002!\000\347\236\251g9m\031\334\270C\353s\0223q\372\363^X\234\274\205|]\305\257\375*l\032z\033\002 \025n\225>\365\242\004\327\361RrP\254(z\321\336\017\374\373B\024mS\311\340\364\252\210\360\212\376" >
2018-01-25 10:50:09.396 UTC [chaincodeCmd] chaincodeInvokeOrQuery -> INFO 00b Chaincode invoke successful. result: status:200
2018-01-25 10:50:09.397 UTC [main] main -> INFO 00c Exiting.....
```

区块链更新，查看
```
root@d9df86737daf:/var/hyperledger/production/orderer/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 49771 Jan 25 10:50 blockfile_000000

root@bff4a03185e5:/var/hyperledger/production/chaincodes# ll /var/hyperledger/production/ledgersData/chains/chains/mychannel/blockfile_000000
-rw-r----- 1 root root 49776 Jan 25 10:50 /var/hyperledger/production/ledgersData/chains/chains/mychannel/blockfile_000000

root@ba162fa79aca:/var/hyperledger/production/ledgersData/chains/chains/mychannel# ll blockfile_000000
-rw-r----- 1 root root 49776 Jan 25 10:50 blockfile_000000
```

验证
=====
```
root@f053fe5f10c4:/opt/gopath/src/github.com/hyperledger/fabric/peer# peer chaincode query -C $CHANNEL_NAME -n mycc -c '{"Args":["query","a"]}'
2018-01-25 10:53:01.657 UTC [msp] GetLocalMSP -> DEBU 001 Returning existing local MSP
2018-01-25 10:53:01.657 UTC [msp] GetDefaultSigningIdentity -> DEBU 002 Obtaining default signing identity
2018-01-25 10:53:01.657 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 003 Using default escc
2018-01-25 10:53:01.657 UTC [chaincodeCmd] checkChaincodeCmdParams -> INFO 004 Using default vscc
2018-01-25 10:53:01.658 UTC [chaincodeCmd] getChaincodeSpec -> DEBU 005 java chaincode disabled
2018-01-25 10:53:01.658 UTC [msp/identity] Sign -> DEBU 006 Sign: plaintext: 0AAB070A6708031A0C088DEEA6D30510...6D7963631A0A0A0571756572790A0161
2018-01-25 10:53:01.659 UTC [msp/identity] Sign -> DEBU 007 Sign: digest: 80B31F565F5288C9476B056B3044FE62F7F268E2F1743B5CA932331EEBFD58E9
Query Result: 90
2018-01-25 10:53:01.678 UTC [main] main -> INFO 008 Exiting.....
```
A变为90，正确。

