# MSP
Membership Service Provider(MSP) 是一个组件，抽象出会员注册制的基本需求，允许不同的实现。其基本功能是
- 身份验证
- 签名

MSP是一个抽象化的组件，不同的组织可以采用不同的具体实现（现实世界中每个公司采用的认证系统可能不一样）。

## 设计
- 一般地，每个组织对应一个MSP，有一个唯一的MSPID。组织内部可以采用自己的具体实现。
- 组织内的各个节点在参与网络交互时，都会提供自己的凭证。一般地，会有一个MSP目录，保存自己的凭证。


## 默认实现
默认的MSP实现是通过公钥认证来提供，一个组织内的各个参与节点都有自己的证书，在生产环境中，一般地，由Fabric CA Server来负责建立和分发这些证书。
不同组织可以有自己的CA Server，负责自己组织内的认证。

初始化
=====

当建立起基础网络拓扑的时候，组织和节点就已经是确定的，当相应的证书都生成完毕，才可以初始化Order服务，Order服务依赖于一个创世块（因为Order服务自身也维护一个区块链），创世块内包含了各个组织和节点的证书。也就是说，当网络拓扑确定的时候，Order服务就确定了，其创世块就不可更改。

举例，比如在典型的“First-Network”示例中，有一个Order，2个组织，每个组织有两个节点。那么生成的创世块内，就有9个证书。
- OrderOrg的ca证书，tlsca证书，admin证书
- Org1的ca证书，tlsca证书，admin证书
- Org2的ca证书，tlsca证书，admin证书
共9个。
```
$  first-network git:(master) ✗ grep -a "END CERTIFICATE" channel-artifacts/genesis.block -B1|grep -v "END" |grep -v "\-\-" | xargs -I{} sh -c "echo {}; grep -ar {} crypto-config/; echo '-------------'"
VwIgYjQJJp3ti7DHQArTiI1uHuFjqzfxVpRM8xAdmmpQq3Y=
crypto-config//ordererOrganizations/example.com/ca/ca.example.com-cert.pem:VwIgYjQJJp3ti7DHQArTiI1uHuFjqzfxVpRM8xAdmmpQq3Y=
crypto-config//ordererOrganizations/example.com/orderers/orderer.example.com/msp/cacerts/ca.example.com-cert.pem:VwIgYjQJJp3ti7DHQArTiI1uHuFjqzfxVpRM8xAdmmpQq3Y=
crypto-config//ordererOrganizations/example.com/msp/cacerts/ca.example.com-cert.pem:VwIgYjQJJp3ti7DHQArTiI1uHuFjqzfxVpRM8xAdmmpQq3Y=
crypto-config//ordererOrganizations/example.com/users/Admin@example.com/msp/cacerts/ca.example.com-cert.pem:VwIgYjQJJp3ti7DHQArTiI1uHuFjqzfxVpRM8xAdmmpQq3Y=
-------------
ZO/OdgSq/Q+dVR3vAh8tCrGu1CclkOlhuJLVV1Z713dzaGFBFwCnyqm5KBvb
crypto-config//ordererOrganizations/example.com/orderers/orderer.example.com/msp/admincerts/Admin@example.com-cert.pem:ZO/OdgSq/Q+dVR3vAh8tCrGu1CclkOlhuJLVV1Z713dzaGFBFwCnyqm5KBvb
crypto-config//ordererOrganizations/example.com/msp/admincerts/Admin@example.com-cert.pem:ZO/OdgSq/Q+dVR3vAh8tCrGu1CclkOlhuJLVV1Z713dzaGFBFwCnyqm5KBvb
crypto-config//ordererOrganizations/example.com/users/Admin@example.com/msp/admincerts/Admin@example.com-cert.pem:ZO/OdgSq/Q+dVR3vAh8tCrGu1CclkOlhuJLVV1Z713dzaGFBFwCnyqm5KBvb
crypto-config//ordererOrganizations/example.com/users/Admin@example.com/msp/signcerts/Admin@example.com-cert.pem:ZO/OdgSq/Q+dVR3vAh8tCrGu1CclkOlhuJLVV1Z713dzaGFBFwCnyqm5KBvb
-------------
WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/tlsca/tlsca.example.com-cert.pem:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/orderers/orderer.example.com/tls/ca.crt:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/orderers/orderer.example.com/msp/tlscacerts/tlsca.example.com-cert.pem:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/msp/tlscacerts/tlsca.example.com-cert.pem:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/users/Admin@example.com/tls/ca.crt:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
crypto-config//ordererOrganizations/example.com/users/Admin@example.com/msp/tlscacerts/tlsca.example.com-cert.pem:WtdAuamaMQIgWrS2sNYHgaG4wDmuSjV6/hjnWAy8Rm63imEmaQ8wk6I=
-------------
zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/ca/ca.org2.example.com-cert.pem:zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/msp/cacerts/ca.org2.example.com-cert.pem:zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp/cacerts/ca.org2.example.com-cert.pem:zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/users/User1@org2.example.com/msp/cacerts/ca.org2.example.com-cert.pem:zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/peers/peer0.org2.example.com/msp/cacerts/ca.org2.example.com-cert.pem:zEkplC9wjg==
crypto-config//peerOrganizations/org2.example.com/peers/peer1.org2.example.com/msp/cacerts/ca.org2.example.com-cert.pem:zEkplC9wjg==
-------------
QQZm8BuEpz3HsAmEWA==
crypto-config//peerOrganizations/org2.example.com/msp/admincerts/Admin@org2.example.com-cert.pem:QQZm8BuEpz3HsAmEWA==
crypto-config//peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp/admincerts/Admin@org2.example.com-cert.pem:QQZm8BuEpz3HsAmEWA==
crypto-config//peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp/signcerts/Admin@org2.example.com-cert.pem:QQZm8BuEpz3HsAmEWA==
crypto-config//peerOrganizations/org2.example.com/peers/peer0.org2.example.com/msp/admincerts/Admin@org2.example.com-cert.pem:QQZm8BuEpz3HsAmEWA==
crypto-config//peerOrganizations/org2.example.com/peers/peer1.org2.example.com/msp/admincerts/Admin@org2.example.com-cert.pem:QQZm8BuEpz3HsAmEWA==
-------------
a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/tlsca/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/msp/tlscacerts/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/users/Admin@org2.example.com/tls/ca.crt:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/users/Admin@org2.example.com/msp/tlscacerts/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/users/User1@org2.example.com/tls/ca.crt:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/users/User1@org2.example.com/msp/tlscacerts/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/peers/peer0.org2.example.com/tls/ca.crt:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/peers/peer0.org2.example.com/msp/tlscacerts/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/peers/peer1.org2.example.com/tls/ca.crt:a4R8adkbxELBRYDNsA==
crypto-config//peerOrganizations/org2.example.com/peers/peer1.org2.example.com/msp/tlscacerts/tlsca.org2.example.com-cert.pem:a4R8adkbxELBRYDNsA==
-------------
osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/ca/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/msp/cacerts/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp/cacerts/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/users/User1@org1.example.com/msp/cacerts/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/peers/peer1.org1.example.com/msp/cacerts/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
crypto-config//peerOrganizations/org1.example.com/peers/peer0.org1.example.com/msp/cacerts/ca.org1.example.com-cert.pem:osNphM3KJ0Q=
-------------
mUckl3xpev/0jQod
crypto-config//peerOrganizations/org1.example.com/msp/admincerts/Admin@org1.example.com-cert.pem:mUckl3xpev/0jQod
crypto-config//peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp/admincerts/Admin@org1.example.com-cert.pem:mUckl3xpev/0jQod
crypto-config//peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp/signcerts/Admin@org1.example.com-cert.pem:mUckl3xpev/0jQod
crypto-config//peerOrganizations/org1.example.com/peers/peer1.org1.example.com/msp/admincerts/Admin@org1.example.com-cert.pem:mUckl3xpev/0jQod
crypto-config//peerOrganizations/org1.example.com/peers/peer0.org1.example.com/msp/admincerts/Admin@org1.example.com-cert.pem:mUckl3xpev/0jQod
-------------
991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/tlsca/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/users/Admin@org1.example.com/tls/ca.crt:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/users/Admin@org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/users/User1@org1.example.com/tls/ca.crt:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/users/User1@org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/peers/peer1.org1.example.com/tls/ca.crt:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/peers/peer1.org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/peers/peer0.org1.example.com/tls/ca.crt:991KDChLhJrFKnyh
crypto-config//peerOrganizations/org1.example.com/peers/peer0.org1.example.com/msp/tlscacerts/tlsca.org1.example.com-cert.pem:991KDChLhJrFKnyh
-------------
```

凭证
=====

每个pee和order都有一个msp目录，里面有
- 自己的私钥，用来签名
- 自己的证书（由所属组织签发）用来自证
- tls根证书
- ca根证书
在参与网络交互时，用证书来自证，用私钥来给操作/交易签名。
