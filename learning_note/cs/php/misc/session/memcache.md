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

