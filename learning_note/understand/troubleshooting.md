# Troubleshooting


## CA

No certificates found for provided serial and aki
==========

在测试Fabric CA的时候，按照[教程](http://hyperledger-fabric-ca.readthedocs.io/en/latest/users-guide.html#getting-started)，新注册一个用户，
```
fabric-ca-client register --id.name peer1 --id.type peer --id.affiliation org1.department1 --id.secret peer1pw
```

但是报错，ca-client提示
```
Error: Error response from server was: Authorization failure
```

server提示
```
2018/01/24 22:32:41 [DEBUG] DB: Get certificate by serial (33d8db0d8f201b70c22deb81cc8b9e9b4207ab72) and aki (8aa85cb0620e257860cb7d086f029ff3616d0293)
2018/01/24 22:32:41 [ERROR] No certificates found for provided serial and aki
```

一般地，这种情况是因为client使用的user，在server的db中找不到，有可能是server数据库文件删除或者docker重启。但是我查了，db中确认存在。那么就有可能是程序bug。Google之，在jira上看到了相应的错误，但是提示已经fix。不知道如何是好了。

经过一段时间考虑，猜测有可能是版本问题，更新go版本为最新，依然不行。再猜测有可能是代码版本问题，教程中`go get -u github.com/hyperledger/fabric-ca/cmd/...` 下载的应该是master版本，但master应该经常变动，我应该下载一个稳定版。

到github上看，确定有`v1.1.0-preview`版，这也是当前部署的Fabric版本。那么，本地切换下载的分支，并重新编译
```
git checkout tags/v1.1.0-preview
make fabric-ca-client
make fabric-ca-server
cp -f bin/* $GOPATH/bin
```

重启fabric-ca-server，重新register，成功。
