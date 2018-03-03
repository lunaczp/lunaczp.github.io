# Recursive length prefix encoding (RLP)
[Ref](https://github.com/ethereum/wiki/wiki/%5BEnglish%5D-RLP)

Ethereum使用RLP来序列化结构数据
> encoding method used to serialize objects in Ethereum. The only purpose of RLP is to encode structure; encoding specific atomic data types (eg. strings, ints, floats) is left up to higher-order protocols; in Ethereum integers must be represented in big endian binary form with no leading zeroes (thus making the integer value zero be equivalent to the empty byte array).

> If one wishes to use RLP to encode a dictionary, the two suggested canonical forms are to either use [[k1,v1],[k2,v2]...] with keys in lexicographic order or to use the higher-level Patricial Tree encoding as Ethereum does.

## 定义
RLP的输入是一个item，item的定义是
- 一个字符串`string`是一个item
- 多个item组成的list，是一个item

## 编码
字符串:

- 单个字符`[0x00,0x7f]`(ASCII表128个基本字符)，返回本身
- 字符长度为0-55字节，前置一个字节，数值为`0x80 + 字符的长度`
	- 由于最长55(`0x37`)字节，如此，第一个字节的范围为`[0x80,0xb7]`
- 字符长度大于55字节，前置多个字节，最终字符串为
	- `0xb7 + 字符长度的二进制表示的长度（字节数）`，比如字符串长度为100，二进制为`0110 0100`,占用一个字节，则第一个字节为'0xb8'
	- 字符的长度
	- 字符本身  
第一个字节的范围设置为`[0xb8,0xbf]`，由于`0xbf-0xb8+1=8`，也就是可表示的字符串的长度的长度是8字节，那么可表示的字符串的长度就是2^64，(2^16PB)，远远够用。

数组/列表：
- 如果长度为0-55字节，那么前置一个字节，数值为`0xc0 + 长度`
	- 如此，第一字节的范围是`[0xc0, 0xcf]`
- 如果长度大于55字节，那么前置多个字节，最终字符串为：
	- `0xf7 + 长度的二进制表示的长度（字节数）`
	- 长度本身
	- 列表内容本身
如此，第一个字节的范围是`[0xf8, 0xff]`，也就是可表示的长度是8字节，也就是列表内容总长度为2^64，远远够用。

### 识别
经过上面的编码后，很容易解码任意数据结构。判断第一字节即可：
- `[0x00,0x7f]`，单字符
- `[0x80,0xb7]`，长度小于55字节的字符串，先解析出长度，再读取即可。
- `[0xb8,0xbf]`，长度大于55自己的字符串，先解析出长度的长度，再读取长度，再读取字符串即可。
- `[0xc0,0xf7]`，长度小于55字节的列表，先解析出长度，在循环读取列表即可。
- `[0xf8,0xff]`，长度大于55字节的列表，写解析出长度的长度，再读取长度，再循环读取列表即可。
done
