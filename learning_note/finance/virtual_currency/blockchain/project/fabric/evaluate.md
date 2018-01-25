# Fabric 测评

- [介绍](#介绍)
- [功能](#功能)
- [测试说明](#测试说明)
- [总结](#总结)
- [附录](#附录)
	- [模块说明](#模块说明)

## 介绍
> Hyperledger超级账本（简称超级账本）为Linux基金会主办的开源合作项目 , 旨在推进跨行业区块链技术。它是一个全球的合作项目, 领导者来自包括金融、银行、物联网、供应链、制造和技术领域。

其应用领域定位：
- 金融
流水化处理，改善的流动性，增加的透明和新产品/新市场
- 医疗健康
联接不同的流程，提高数据流和流动性，降低成本，改善病人的体验和成果
- 供应链
追踪零部件和服务源头，确保商品的真实性，阻止假货赝品，减少冲突


Fabric是Hyperledger下的一个子项目，原始代码主要由IBM贡献，目前发展迅速。旨在提供一个企业级的、开源的分布式账本框架和代码库，支持商业事务。

## 功能
Fabric定位在企业级区块链，因而不同于普通的公开链，Fabric提供了更多的权限控制，是一种联盟链。其功能及特性有：
- 模块化设计，组件可插拔。提供的共识算法、会员制都可以支持插拔，实现了“Plug and Play”
- 利用Docker容器技术来支持智能合约，方便部署
- 抽象化的MSP可以支持不同组织采用不同的认证授权方法
- 权限管理，限定可参与的节点和组织，所有操作都要经过权限校验

## 测试说明

### 基本概念
- Channel  
一个典型的Fabric网络，以Channel为单位，一个Channel是一个区块链，所有相关的业务方操作同一个区块链。同时一个Fabric网络可以同时支持多个Channel，互不影响。
- Organization  
Organization映射现实世界中的各个参与方，如公司，团体，组织等。一个典型的Fabric网络内，可以包含多个Organization。如果只有一个，则可以看作是一个企业内部的区块链。
- Peer  
区块链由节点（Peer）组成，等于比特网络内的单个参与方，Peer包含一个全网完整的账本。一个Organization包含一到多个Peer。同是有一个或多个Anchor Peer负责和其他组织交互。
- Orderer  
一个典型的Fabric网络内，所有的交易都要经过一个Orderer服务进行排序，然后再交有Peer节点写入分布式账本。如此简化了比特币网络中的“一币多付”问题。
- Chaincode  
链码，可以看作是智能合约的载体，所有的业务逻辑在这里。目前可以用Go，和Nodejs编码。
- Ledger  
账本，每个节点维护一个全网一致的账本
- MSP  
Membership Service Provider,所有参与到网络的节点，都要提供自己的身份证明，MSP是这一功能的抽象实现。不同的组织可以提供不同的实现方法。Fabric默认采用公钥认证来实现。
- CA  
Certificate Authority，认证机构。Fabric默认提供CA来做账号签名和验证。可以使用CA来为MSP提供账号管理、签名验证。

### 测试示例
以官方文档提供的“BYFN：Build your first network”为例，说明实际使用的流程

在这个场景中，有2个组织，每个组织2个节点，同时有一个Orderer服务。所有节点通过docker管理。

- 安装基础依赖
	- 安装Docker，建议最新版本，下载集成的Docker Fabric镜像
	- 安装Go，1.9x
	- 安装Node 1.6x
	- 安装Pyton 2.7
- 生成证书及密钥
	- 为Orderer准备创世区块
	- 所有节点都需要私钥和证书。本例中，两个组织各有一个根证书，然后由根证书为组织内每个节点签发证书
	- 启动了tls的，需要为每个节点提供tls证书
- 启动网络
	- 此环节通过docker启动各个容器，并映射各自需要的证书目录
- 创建Channel
	- 利用之前生成的证书，创建需要的channel
- 加入Channel
	- 将4个节点分别加入到Channel
- 设定Anchor Peer
	- 将指定的节点设置为Anchor Peer
- 部署代码
	- 将业务代码部署到所有节点
- 实例化代码
	- 初始化数据
	- 此时会启动一个新的docker容器，后续对chaincode的调用，都有该容器执行。
- 代码调用
	- 执行查询
	- 执行写操作，调用Chaincode
- 组织变更
	- 模拟新增组织。

### 测试说明
上面示例展示了一个简单的场景下的业务部署流程。关键点集中在
- 基础环境：只需要执一次就可以
- 网络拓扑：不同的业务需求可能有不同的场景，因而可能每次都需要重新搭建
- 代码部署：在真实环境中，应要考虑如何和上线系统结合

结合实际情况，一个典型的业务实现流程是：
- 需求分析
- 建模与设计
	- 根据需求确定参与的组织，节点数，网络拓扑
	- 确定Chaincode的功能模块
- 服务搭建
	- CA服务部署。CA服务器提供用户认证与授权校验
	- 业务服务器搭建。根据需求，编码docker配置文件和服务管理脚本
- 编码
编码涉及到两部分，一个是Chaincode，负责核心逻辑。一个是API。Fabric提供Go和Node SDK（Java也在开发中，目前支持不是很完善），但是如果要和具体业务对接，
还是需要提供一层API，供a) 业务查询；b) 业务调用，和区块链交互。
- 代码部署
	- 代码首次部署需要初始化，应提供相应脚本实现
	- 代码更新复杂，需要多个流程，可能需要服务重启，应考虑如何发布系统结合。

## 总结
如上是基于Fabric的区块链搭建简单流程。

使用Fabric，其优点是：
- 模块化设计，灵活可配置
- 授权管理，Fabric提供了丰富的授权管理。抽象的MSP允许不同的认证方法共存。自带的CA支持公钥认证，且可以后端连接LDAP，实现用户注册。

目前使用中遇到的问题是：
- 不能统一管理底层区块链，所有操作都要手动部署。新项目首次部署难度较大。目前有[Cello](https://github.com/hyperledger/cello)可以用来管理。但项目尚处于孵化期，还没到1.0正式发布，功能不完善，[测试环境示例](http://10.70.1.234:8080/)，账号`admin:adminpw`
- 所有的chaincode都需要单独的docker容器来执行。一方面会消耗更多资源；一方面首次启动会比较慢，因为需要编译生成docker容器。
- 区块链提供的核心功能是分布式账本，底层Key—Value存储，并不支持复杂的查询操作。这个要看业务需求需要，考虑结合其他手段实现。
- 网络拓扑变更复杂。网络拓扑变更（新增组织）需要复杂的流程来实现，风险较高。
- 代码部署和更新需要和现有上线系统结合。 

涉及到编码，分两部分：

_部署脚本_

几乎部署的每个流程都需要执行特定的脚本。具体到生产环境，我们需要实现自动化，否则管理成本太高。
- docker节点管理脚本，管理节点的启动，部署，数据文件（账本）的维护。
- 与CA的交互脚本，为每个项目建立需要的账号和证书
- 代码部署脚本，和更新脚本。

_业务代码_

业务代码分两部分
- chaincode，核心业务逻辑，运行在区块链上，由Go或Nodejs提供。
- API，封装Fabric的SDK，提供给业务查询和调用。

## 附录
###  模块说明

#### CA
Fabric CA组件在Fabric网络中，充当Certificate Authority的作用。其功能有：
- 注册实体，或者连接到LDAP。作为用户数据库，起到注册簿的作用
- 发放Enrollment Certificate（ECerts），只有拥有ECerts的实体，才允许在Fabric网络内交互。起到权限管理的作用
- 证书的更新和吊销

##### 关于权限管理
权限管理是多 个层面的
- 只有register并enroll（enroll后拥有证书)的实体，才能加入Fabric网络
- 每个enroll的实体，都有一些相关的属性(存储在CA Server)，比如角色等。
- 证书本身也可以附加属性，可以用来做更进一步的权限控制

##### 关于属性
每个enroll的实体，都有一些属性（存储在CA Server）
- hf.Registrar.Roles	List	List of roles that the registrar is allowed to manage
- hf.Registrar.DelegateRoles	List	List of roles that the registrar is allowed to give to a registree for its ‘hf.Registrar.Roles’ attribute
- hf.Registrar.Attributes	List	List of attributes that registrar is allowed to register
- hf.GenCRL	Boolean	Identity is able to generate CRL if attribute value is true
- hf.Revoker	Boolean	Identity is able to revoke a user and/or certificates if attribute value is true
- hf.AffiliationMgr	Boolean	Identity is able to manage affiliations if attribute value is true
- hf.IntermediateCA Boolean	Identity is able to enroll as an intermediate CA if attribute value is true

客户端可以通过API获取这些属性，然后根据需要做权限控制。注意，这些属性是存储在CA Server的。

除此之外，实体在enroll的时候，可以指定部分属性使其被记录到ECerts内，比如
```
fabric-ca-client register --id.name admin2 --id.affiliation org1.department1 --id.attrs 'hf.Revoker=true,admin=true:ecert'

```
> The ':ecert' suffix means that by default the “admin” attribute and its value will be inserted into the user’s enrollment certificate, which can then be used to make access control decisions.
这样，可以直接通过证书本身去做权限控制，而并不通过CA Server。


##### 关于用户
一般地CA Server启动时，会设立一个管理员。管理员可以用来给order，peer，user，app等参与方建立账号，并设定相应的角色和参数。
- 在测试场景中，每个参与方都会有管理员的证书，方便参与方自己和CA Server沟通并建立自己需要的账号。
- 在生产环境中，应该由管理员统一建立账号，并分发给各个参与方（拷贝证书给参与方）。各个参与方只留有自己的凭证即可。


#### MSP
Membership Service Provider(MSP) 是一个组件，抽象出会员注册制的基本需求，允许不同的实现。其基本功能是
- 身份验证
- 签名

MSP是一个抽象化的组件，不同的组织可以采用不同的具体实现（现实世界中每个公司采用的认证系统可能不一样）。

##### 设计
- 一般地，每个组织对应一个MSP，有一个唯一的MSPID。组织内部可以采用自己的具体实现。
- 组织内的各个节点在参与网络交互时，都会提供自己的凭证。一般地，会有一个MSP目录，保存自己的凭证。


##### 默认实现
默认的MSP实现是通过公钥认证来提供，一个组织内的各个参与节点都有自己的证书，在生产环境中，一般地，由Fabric CA Server来负责建立和分发这些证书。
不同组织可以有自己的CA Server，负责自己组织内的认证。

##### 初始化

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

##### 凭证

每个pee和order都有一个msp目录，里面有
- 自己的私钥，用来签名
- 自己的证书（由所属组织签发）用来自证
- tls根证书
- ca根证书
在参与网络交互时，用证书来自证，用私钥来给操作/交易签名。

