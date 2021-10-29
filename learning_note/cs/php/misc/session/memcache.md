# 关于PHP的session存储

## 默认
默认情况下，存储到files，路径是`/tmp`

## 使用memcache
```
extension=memcache.so
session.save_handler = memcache
session.save_path = "tcp://192.168.1.11:11211,tcp://192.168.1.12:11211"
```


## 使用memcached
```
extension=memcached.so
session.save_handler = memcached
session.save_path = "192.168.1.11:11211"
```
__注意mc和mcd的save_path格式差异__
另外，mcd默认是有前缀的`memc.sess.key.`

## 其他
查看mc的某个key
```
printf "get somekey\r\n" |nc ip:port;
```
遍历mc server list查某个key
```
cat servers.txt | while read line; do    printf "get somekey\r\n" |nc $line; done
```


## demo
```
//获取session 数据
$phpsessid=$argv[1];
if (empty($phpsessid)) {
    exit("need phpsessid");
}


$saveFile = "t";
$x = "printf \"get get $phpsessid\r\n\" |nc 10.132.1.2 11211 >$saveFile && sed -i '1d;\$d' $saveFile";
shell_exec($x);
var_export(igbinary_unserialize(file_get_contents($saveFile)));
```

## 特别的case
正常情况，是服务端下发PHPSESSID到浏览器，浏览器在随后的访问中携带回服务端，以便服务端跟踪这个session。
- session_start启动会话，生成PHPSESSID，返回浏览器，同时记录session数据到mc(如果业务设定了数据，不设定为空，不会记录到mc)，key是PHPSESSID。

不过测试发现，如果手动修改cookie，手动指定任意的PHPSESSID，一样可以！只不过这时候对服务端而言是一个新的session而已。
这时候如果服务端设定了session的一些值，在mc中是可以查到的。
问题是：__PHP接受一个客户端指定的PHPSESSID，而不要求一定是服务端生成的。__
当服务端拿到一个PHPSESSID的时候，不会检查这个PHPSESSID是不是之前生成的。（？），只会直接使用。//todo 源码
