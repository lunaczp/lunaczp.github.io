# Certificate Authority
数字认证机构，是利用公钥技术来做签名、认证的体系。是PKI的一种实现。

## Pre
公钥系统可以用来做加解密，同时用来做签名校验。利用公钥可以建立一套PKI系统，Certificate Authority其中一种应用

## 概念
- Certificate Authority，负责签名和作签名校验的机构，一般作为第三方存在，我们默认信任这些结构，从而相信由这些机构为之背书的其他机构。
- 证书，由CA颁发给个人或企业，用来证明“我就是我”。比如颁发给X，意思就是说“X就是X，我以为为他证明，如果你相信我，你就可以相信他就是他”。
- 根证书，证书需要私钥签名，由于需要颁发的证书很多，同时为了保证根证书私钥的安全，一般不使用根证书私钥为普通机构签名。相反地，有根证书签名多个中间证书，中间证书还可以再次签名多个中间证书，最后由中间证书来为个人和企业签发证书。如此，形成一个由上到下的信任链。

## 流程
- A作为一个根证书机构
	- 生成一个私钥ca.key.pem
	- 用私钥签名生成一个证书，ca.cert.pem
	这个证书是自签名的（自己为自己背书）
- B做为一个中间证书机构
	- 生成一个私钥ca.key.pem
	- 生成一个CSR（Certificate Signing Requeset） ca.csr.pem发给A，申请A为自己背书
	- A在验证了B确实是B后（打电话，让B提供资质证明，向政府机构确认），用自己的私钥为B的CSR签名，生成ca.cert.pem，返回给B
- C作为一个普通机构
	- 生成一个私钥ca.key.pem
	- 生成一个CSR（Certificate Signing Requeset） ca.csr.pem发给B，申请B为自己背书
	- B在验证了C确实是C后（打电话，让C提供资质证明，向政府机构确认），用自己的私钥为C的CSR签名，生成ca.cert.pem，返回给C
	- 之后，C再和别人沟通(D)的时候，就可以出示ca.cert.pem，意即，“看，我确实是我，由B为我背书”，D默认信任A，然后验证C的证书确实是由B签发的，B确实是有A认证的，因而决定相信C，认为C确实是C。

## 其他
### 吊销 Revoke
证书是可以被吊销的，那么我们需要一种方式告诉大家，这个证书以前有效，但是现在被吊销了。

#### CRL Certificate Revocation List
- CA会维护一个CRL列表，被自己吊销的每个证书，都会在这里列出
- 在CA签发证书的时候，会在证书内提供一个可远程公开访问的url，指向CRL列表`http://example.com/intermediate.crl.pem`
如此，当某一方接收到一个证书的时候，可以根据连接来查看该证书是否被吊销了。

### OCSP Online Certificate Status Protocal
OCSP是CRL的替代版，不过目前是都在使用。

其实现类似CRL，也是在证书内提供一个url，可以查询证书是否有效。

不同的是， OCSP不是提供所有吊销的证书列表，而是由调用方提供要查询的证书，然后OSCP返回该证书的状态。