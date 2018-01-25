# CA
Fabric CA组件在Fabric网络中，充当Certificate Authority的作用。其功能有：
- 注册实体，或者连接到LDAP。作为用户数据库，起到注册簿的作用
- 发放Enrollment Certificate（ECerts），只有拥有ECerts的实体，才允许在Fabric网络内交互。起到权限管理的作用
- 证书的更新和吊销

## 关于权限管理
权限管理是多 个层面的
- 只有register并enroll（enroll后拥有证书)的实体，才能加入Fabric网络
- 每个enroll的实体，都有一些相关的属性(存储在CA Server)，比如角色等。
- 证书本身也可以附加属性，可以用来做更进一步的权限控制

## 关于属性
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


## 关于用户
一般地CA Server启动时，会设立一个管理员。管理员可以用来给order，peer，user，app等参与方建立账号，并设定相应的角色和参数。
- 在测试场景中，每个参与方都会有管理员的证书，方便参与方自己和CA Server沟通并建立自己需要的账号。
- 在生产环境中，应该由管理员统一建立账号，并分发给各个参与方（拷贝证书给参与方）。各个参与方只留有自己的凭证即可。
