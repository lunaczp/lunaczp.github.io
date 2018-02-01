# Private Network
建立一个自己的Ethereum区块链

[Ref](https://omarmetwally.blog/2017/07/25/how-to-create-a-private-ethereum-network/)

## Pre
- 安装了geth工具集（geth、bootnode...）
- 安装了solc（用来编译contract）

## 启动网络
- 配置一个创世块文件`genesis.json`，定义了链的唯一ID，挖矿难度，等
```
{
"config": {
        "chainId": 15,
        "homesteadBlock": 0,
        "eip155Block": 0,
        "eip158Block": 0
    },

  "alloc"      : {
  "0x0000000000000000000000000000000000000001": {"balance": "111111111"},
  "0x0000000000000000000000000000000000000002": {"balance": "222222222"}
    },

  "coinbase"   : "0x0000000000000000000000000000000000000000",
  "difficulty" : "0x00001",
  "extraData"  : "",
  "gasLimit"   : "0x2fefd8",
  "nonce"      : "0x0000000000000107",
  "mixhash"    : "0x0000000000000000000000000000000000000000000000000000000000000000",
  "parentHash" : "0x0000000000000000000000000000000000000000000000000000000000000000",
  "timestamp"  : "0x00"
}
```
- 生成创世块
```
geth --datadir=/root/ucsfnet/data init /root/ucsfnet/genesis.json
```
- 初始化节点
```
bootnode --genkey=boot.key
bootnode --nodekey=boot.key
```
- 启动节点
```
geth --datadir=/root/ucsfnet/data --bootnodes=enode://148f3....@101.102.103.104:3031
```
- done

如上，一个Ethereum区块链就部署成功了（单节点区块链）。其实主要就是配置生成创世块，初始化节点，就可以了。


## 生成账号
生成账号并挖矿，用来后面部署智能合约用
```
geth attach /root/ucsfnet/data/geth.ipc
> eth.accounts
> personal.newAccount("mypassword")
> web3.fromWei(eth.getBalance(eth.accounts[0]), "ether")
```
注意记录密码，和账号地址


## 挖矿
```
geth --datadir=/root/ucsfnet/data --mine --minerthreads=1 --etherbase=0x...
# etherbase填写自己的账号，用来收取挖矿奖励
```
由于设置的挖矿难度低，一段时间后，再次检查账号，就会发现有了很多ether

```
> eth.accounts
["0xd3660ab9180d156d71a4ba9278959901760ebdb2"]
> web3.fromWei(eth.getBalance(eth.accounts[0]), "ether")
5680
```
5680枚以太币


## 编写并部署智能合约
分几部分
- 编写，编译
- 安装
- 调用

### 编写，编译
```
contract mortal {

/* Define variable owner of the type address*/
 address owner;

/* this function is executed at initialization and sets the owner of the contract */
 function mortal() { owner = msg.sender; }

/* Function to recover the funds on the contract */
 function kill() { if (msg.sender == owner) selfdestruct(owner); }
}

contract greeter is mortal {
 /* define variable greeting of the type string */
 string greeting;

/* this runs when the contract is executed */
 function greeter(string _greeting) public {
 greeting = "UCSFnet lives!";
 }

/* main function */
 function greet() constant returns (string) {
 return greeting;
 }
}
```

```
solc --bin --abi  -o /root/ucsfnet/source /root/ucsfnet/source/greeter.sol
```

生成
```
[root@10.70.1.234 source]#ls
greeter.abi  greeter.bin  greeter.sol  mortal.abi  mortal.bin
[root@10.70.1.234 source]#cat greeter.abi
[{"constant":false,"inputs":[],"name":"kill","outputs":[],"payable":false,"stateMutability":"nonpayable","type":"function"},{"constant":true,"inputs":[],"name":"greet","outputs":[{"name":"","type":"string"}],"payable":false,"stateMutability":"view","type":"function"},{"inputs":[{"name":"_greeting","type":"string"}],"payable":false,"stateMutability":"nonpayable","type":"constructor"}][root@10.70.1.234 source]#
[root@10.70.1.234 source]#cat greeter.bin
6060604052341561000f57600080fd5b6040516103de3803806103de83398101604052808051820191905050336000806101000a81548173ffffffffffffffffffffffffffffffffffffffff021916908373ffffffffffffffffffffffffffffffffffffffff1602179055506040805190810160405280600e81526020017f554353466e6574206c6976657321000000000000000000000000000000000000815250600190805190602001906100b69291906100bd565b5050610162565b828054600181600116156101000203166002900490600052602060002090601f016020900481019282601f106100fe57805160ff191683800117855561012c565b8280016001018555821561012c579182015b8281111561012b578251825591602001919060010190610110565b5b509050610139919061013d565b5090565b61015f91905b8082111561015b576000816000905550600101610143565b5090565b90565b61026d806101716000396000f30060606040526004361061004c576000357c0100000000000000000000000000000000000000000000000000000000900463ffffffff16806341c0e1b514610051578063cfae321714610066575b600080fd5b341561005c57600080fd5b6100646100f4565b005b341561007157600080fd5b610079610185565b6040518080602001828103825283818151815260200191508051906020019080838360005b838110156100b957808201518184015260208101905061009e565b50505050905090810190601f1680156100e65780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b6000809054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff161415610183576000809054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16ff5b565b61018d61022d565b60018054600181600116156101000203166002900480601f0160208091040260200160405190810160405280929190818152602001828054600181600116156101000203166002900480156102235780601f106101f857610100808354040283529160200191610223565b820191906000526020600020905b81548152906001019060200180831161020657829003601f168201915b5050505050905090565b6020604051908101604052806000815250905600a165627a7a72305820e52cc886adcc49dda6b9a43b784c35012962db2350ccf541bb87febc80839e060029[root@10.70.1.234 source]#
```

- abi文件Application Binary Inferface，接口描述
- bin文件是EVM的字节码
这两个是安装调用合约的时候需要用。

### 安装
Ethereum提供了SDK来和区块链交互。    
一般地，可以通过js 调用SDK实现来实现安装。  
更简单的，安装代码可以直接通过[工具](https://ethereum.github.io/browser-solidity/#version=soljson-v0.4.13+commit.fb4cb1a.js&optimize=false)来生成

- 将编写/生成的安装代码保存到本地myContract.js
```
var _greeting = 'UCSFnet lives!';

var browser_ballot_sol_greeterContract = web3.eth.contract([{"constant":false,"inputs":[],"name":"kill","outputs":[],"payable":false,"type":"function"},{"constant":true,"inputs":[],"name":"greet","outputs":[{"name":"","type":"string"}],"payable":false,"type":"function"},{"inputs":[{"name":"_greeting","type":"string"}],"payable":false,"type":"constructor"}]);

var browser_ballot_sol_greeter = browser_ballot_sol_greeterContract.new(

  _greeting,

  {

    from: web3.eth.accounts[0],

    data: '0x6060604052341561000f57600080fd5b6040516103dd3803806103dd833981016040528080518201919050505b5b336000806101000a81548173ffffffffffffffffffffffffffffffffffffffff021916908373ffffffffffffffffffffffffffffffffffffffff1602179055505b6040805190810160405280600d81526020017f48656c6c6f2c20576f726c642100000000000000000000000000000000000000815250600190805190602001906100b99291906100c1565b505b50610166565b828054600181600116156101000203166002900490600052602060002090601f016020900481019282601f1061010257805160ff1916838001178555610130565b82800160010185558215610130579182015b8281111561012f578251825591602001919060010190610114565b5b50905061013d9190610141565b5090565b61016391905b8082111561015f576000816000905550600101610147565b5090565b90565b610268806101756000396000f30060606040526000357c0100000000000000000000000000000000000000000000000000000000900463ffffffff16806341c0e1b514610049578063cfae32171461005e575b600080fd5b341561005457600080fd5b61005c6100ed565b005b341561006957600080fd5b61007161017f565b6040518080602001828103825283818151815260200191508051906020019080838360005b838110156100b25780820151818401525b602081019050610096565b50505050905090810190601f1680156100df5780820380516001836020036101000a031916815260200191505b509250505060405180910390f35b6000809054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff163373ffffffffffffffffffffffffffffffffffffffff16141561017c576000809054906101000a900473ffffffffffffffffffffffffffffffffffffffff1673ffffffffffffffffffffffffffffffffffffffff16ff5b5b565b610187610228565b60018054600181600116156101000203166002900480601f01602080910402602001604051908101604052809291908181526020018280546001816001161561010002031660029004801561021d5780601f106101f25761010080835404028352916020019161021d565b820191906000526020600020905b81548152906001019060200180831161020057829003601f168201915b505050505090505b90565b6020604051908101604052806000815250905600a165627a7a7230582069d50e4318daa30d3f74bb817c3b0cb732c4ec6a493eb108266c548906c8b6d70029',

    gas: '1000000'

  }, function (e, contract){

   console.log(e, contract);

   if (typeof contract.address !== 'undefined') {

        console.log('Contract mined! address: ' + contract.address + ' transactionHash: ' + contract.transactionHash);

   }

})
```
如上，这个js其实就是把生成的abi和bin代码提交到区块链。bin是用来执行的，abi是代码接口/标记。

- 在终端执行该js
```
loadScript(myContract.js)
```
输出
```
Contract mined! address: 0xc9c869a658a41641a1ae92a77bee3291e150f29d transactionHash: 0x55d65c69e0ecb8a2cd45070e61b8f779aac0eb5f8af720b835b2ce04100474e0
```
部署成功，记住这个地址，它是合约的地址/账号。当调用的时候需要。

### 调用
调用前，要确认账号内有钱，因为调用合约是需要消费的。
```
> var abi = '[{"constant":false,"inputs":[],"name":"kill","outputs":[],"payable":false,"type":"function"},{"constant":true,"inputs":[],"name":"greet","outputs":[{"name":"","type":"string"}],"payable":false,"type":"function"},{"inputs":[{"name":"_greeting","type":"string"}],"payable":false,"type":"constructor"}]'
> var abi = JSON.parse(abi)
> var contract = web3.eth.contract(abi)
> var c = contract.at("0x4000737c8bd7bbe3dee190b6342ba1245f5452d1")
> c.greet()
"UCSFnet lives!"
```
如上，其实就是通过abi构造了contract对象，并通过contract地址定位到contract。然后就可以调用contract的函数。如显示，调用成功。


## 总结
Ethereum区块链的搭建很简单，主要地
- 准备创世块配置文件，生成创世块
- 初始化节点
- 启动节点
- done

合约的编写部署调用也很简单。注意部署合约没有消费，调用合约才会有消费。其实也就是说是使用运算的人付钱。
