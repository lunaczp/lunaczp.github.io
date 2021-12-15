# Egg Proxy 文件上传的问题
## 背景
有一个项目，Node（Egg）+ Vue，服务端接口通过Node转发。
服务端新增了一个文件上传表单接口，但是前后端联调的时候怎么都调试不通。

## Step 通过Postman测试 
服务端form表单两个字段
- `type` 字符串
- `source` 文件

通过Postman测试服务端接口，没问题，可以分别通过`$_POST`,`$_FILES`拿到`type`,`source`。
那就是前段调用的问题。

## Web层问题？
前端的写法也是标准写法，所以感觉问题不在web层，而在Node层。

## Node层问题
Node层，通过Proxy对请求的header和body做了个性化处理，但是发现没有对multipart做支持。

## 支持文件上传
### 尝试单独处理该接口，构建form表单提交。
```
   } else if (ctx.path.indexOf('uploadExcel') !== -1) {
      const stream = await ctx.getFileStream();
      const fieldsType = stream.fields && stream.fields.type;

      let form = new FormData()
      form.append('type', fieldsType)
      form.append('source', stream)

      let opt = parseRequest(options, url, ctx);
      // @ts-ignore
      opt.data = form

      let res = await urllib.request(opt.uri, opt);
      ctx.body = res.data;
```

服务端还是拿不到，通过tcpflow抓包，发现http body是把stream对象作为字符串传递过去了
```
{"_overheadLength":248,"_valueLength":1,"_valuesToMeasure":[],"writable":false,"readable":true,"dataSize":0,"maxDataSize":2097152,"pauseStreams":true,"_released":false,"_streams":["----------------------------511334654748913548825165\r\nContent-Disposition: form-data; name=\"type\"\r\n\r\n","1",null,"----------------------------511334654748913548825165\r\nContent-Disposition: form-data; name=\"source\"\r\nContent-Type: application/octet-stream\r\n\r\n",{"source":{"_readableState":{"objectMode":false,"highWaterMark":16384,"buffer":{"head":{"data":{"type":"Buffer","data":[49,10,50,10,51,10]},"next":null},"tail":{"data":{"type":"Buffer","data":[49,10,50,10,51,10]},"next":null},"length":1},"length":6,"pipes":null,"pipesCount":0,"flowing":false,"ended":true,"endEmitted":false,"reading":false,"sync":false,"needReadable":false,"emittedReadable":true,"readableListening":false,"resumeScheduled":false,"destroyed":false,"defaultEncoding":"utf8","awaitDrain":0,"readingMore":false,"decoder":null,"encoding":null},"readable":true,"domain":null,"_events":{},"_eventsCount":4,"truncated":false,"fieldname":"source","filename":"test.xlsx","encoding":"7bit","transferEncoding":"7bit","mime":"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet","mimeType":"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet","fields":{"type":"1"}},"dataSize":0,"maxDataSize":null,"pauseStream":true,"_maxDataSizeExceeded":false,"_released":false,"_bufferedEvents":[{"0":"pause"}],"_events":{},"_eventsCount":1},null],"_currentStream":null,"_insideLoop":false,"_pendingNext":false,"_boundary":"--------------------------511334654748913548825165"}
```

### 看urlib API，是参数用错了，应该是
```
opt.stream = form
```

```
https://github.com/node-modules/urllib
data Object - Data to be sent. Will be stringify automatically.
stream stream.Readable - Stream to be pipe to the remote. If set, data and content will be ignored.
```

修改后，测试,tcpflow 显示body正常！
```
----------------------------806627200062792264216493
Content-Disposition: form-data; name="type"

1
----------------------------806627200062792264216493
Content-Disposition: form-data; name="source"
Content-Type: application/octet-stream

1
2
3

----------------------------806627200062792264216493--
```

但服务端依然拿不到，看tcpflow，是因为没有content-type header（根据http协议，multipart的boundary要放到header内，接收方才能拿到boundary，再用boundary解析body）。

### 添加content-type header
```
      opt.headers['content-type'] = form.getHeaders()['content-type']
```

```
content-type: multipart/form-data; boundary=--------------------------461647851557340912012195
(omit)

----------------------------461647851557340912012195
Content-Disposition: form-data; name="type"

1
----------------------------461647851557340912012195
Content-Disposition: form-data; name="source"
Content-Type: application/octet-stream

1
2
3

----------------------------461647851557340912012195--

```
成功啦！
此时服务端可以拿到数据，但是
- 通过$\_POST可以拿到type
- 但是通过$\_FILE拿不到source
尝试用$\_POST['source']，成功！

但是，这个不是标准做法，不确定数据是否正常。
测试拿到的文件。__文件大小不一样__，少了一个字节(二进制查看，少了00），
更换多个文件测试，都存在最后少几个字节的情况（00)。
那么说明$\_POST是不支持binary的。（TODO HTTP POST的约定，PHP的实现）.

### 为什么php $\_FILE拿不到
和正常的一个文件上传请求对比，发现从Node过来的请求，Content-Disposition不一样，缺少了filename，content-type也不同。
如何在Node指定呢，看form-data源码，知道是通过第三个参数option控制

```
     form.append('source', stream, {
        filename: stream.filename,
        contentType: stream.contentType
      })
```

再次测试，成功！
```
content-type: multipart/form-data; boundary=--------------------------513343381408792453564666
(omit)

----------------------------513343381408792453564666
Content-Disposition: form-data; name="type"

1
----------------------------513343381408792453564666
Content-Disposition: form-data; name="source"; filename="test.xlsx"
Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet

1
2
3

----------------------------513343381408792453564666--
```

- content-type 正常
- content-disposition正常
- 服务端
 - 通过$\_POST拿到了type
 - 通过$\_FILE拿到了source！

侧面反应了PHP对multipart的处理逻辑
- 有filename的按file处理
- 没有的，按照普通post处理，不支持binary

# 学习
- form-data、urllib组件
- egg proxy
- http multipart

# TODO
- PHP的源码，验证处理逻辑
- HTTP协议，关于mulitpart这部分的约定


