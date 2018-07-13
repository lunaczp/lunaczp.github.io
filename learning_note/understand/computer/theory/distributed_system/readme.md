# Distributed System 分布式系统

## 分布式系统——一致性问题

### 1978 Lamport Clock
> Distribution is in the eye of the beholder. To the user sitting at the keyboard, his IBM personal computer is a nondistributed system. To a flea crawling around on the circuit board, or to the engineer who designed it, it’s very much a distributed system.

“很多早期的分布式研究是从更加微观的多路处理器的研究开始的, 无论是狭义还是广义的分布式系统, 并发一致性一直是分布式系统的核心问题.”

- Lamport Clock 是一个分辨分布式系统中的事件因果关系的算法，其作用就是找到causally related关系

### 1979 Sequential Consistency
> A multiprocessor is said to be sequentially consistent if the result of any execution is the same as if the operations of all the processors were executed in some sequential order, and the operations of each individual processor appear in this sequence in the order specified by its program.[How to Make a Multiprocessor Computer That Correctly Executes Multiprocess Programs by Leslie Lamport ,1979]


### 1987 Linearizability
“Linearizability模型的一致性高于 sequential consistency, 有时候也叫做strong consistency或者atomic consistency, 可以说是我们能够实现的最高的一致性模型. 它是Herlihy and Wing在1987年提出的. 通过前面的讨论我们现在知道sequential consistency 只关心所有进程或者节点的历史事件存在唯一的偏序关系, 它不关心时间顺序. 而linearizability相当于在sequential consistency的基础上再去关心时间顺序, 在不依赖物理时钟的前提下, 区分非并发操作的先后. Linearizability是一个很重要的概念, 如果你不了解它你就无法真正理解Raft算法等”

## 分布式系统——共识问题

### 分布式系统中的网络模型
> 1. 同步网络(synchronous network): 这里的同步网络和编程中的同步阻塞io和异步非阻塞io是两回事, 不要弄混了. 同步网络是指i). 所有节点的时钟漂移有上限, ii). 网络的传输时间有上限, iii). 所有节点的计算速度一样. 这意味着整个网络按照round运行, 每个round中任何节点都要执行完本地计算并且可以完成一个任意大小消息的传输. 一个发出的消息如果在一个round内没有到达, 那么一定是网络中断造成的, 这个消息会丢失, 不会延迟到第二个round到达. 在现实生活中这种网络比较少, 尽管很少, 同步网络仍然是在计算机科学中是不可缺少的一个模型, 在这种模型下可以解决一些问题, 比如拜占庭式故障. 但我们每天打交道的网络大多都是异步网络.
> 2. 异步网络(asynchornous network): 和同步网络相反, 节点的时钟漂移无上限, 消息的传输延迟无上限, 节点计算的速度不可预料. 这就是和我们每天打交道的网络类型. 在异步网络中, 有些故障非常难解决, 比如当你发给一个节点一个消息之后几秒钟都没有收到他的应答, 有可能这个节点计算非常慢, 但是也可能是节点crash或者网络延迟造成的, 你很难判断到底是发生了什么样的故障.

### 分布式系统中的故障模型
在分布式系统中, 故障可能发生在节点或者通信链路上, 下面我们按照从最广泛最难的到最特定最简单的顺序列出故障类型:

> 1. byzantine failures: 这是最难处理的情况, 一个节点压根就不按照程序逻辑执行, 对它的调用会返回给你随意或者混乱的结果. 要解决拜占庭式故障需要有同步网络, 并且故障节点必须小于1/3, 通常只有某些特定领域才会考虑这种情况通过高冗余来消除故障. 关于拜占庭式故障你现在只要知道这是最难的情况, 稍后我们会更详细的介绍它.
> 2. crash-recovery failures: 它比byzantine类故障加了一个限制, 那就是节点总是按照程序逻辑执行, 结果是正确的. 但是不保证消息返回的时间. 原因可能是crash后重启了, 网络中断了, 异步网络中的高延迟. 对于crash的情况还要分健忘(amnesia)和非健忘的两种情况. 对于健忘的情况, 是指这个crash的节点重启后没有完整的保存crash之前的状态信息, 非健忘是指这个节点crash之前能把状态完整的保存在持久存储上, 启动之后可以再次按照以前的状态继续执行和通信, 比如最基本版本的Paxos要求节点必须把ballot number记录到持久存储中, 一旦crash, 修复之后必须继续记住之前的ballot number.
> 3. omission failures: 这种故障比crash-recovery多一个限制，就是发生故障后，消息不会恢复。比如网络故障造成某条消息在传输中丢失(而不是延迟).
> 4. crash-stop failures: 也叫做crash failure或者fail-stop failures, 它比omission failure容易处理, 因为这种模型下故障就是crash, 并且这些故障不会恢复. 比如一个节点出现故障后立即停止接受和发送所有消息. 简单讲, 一旦发生故障, 这个节点就不会再和其它节点有任何交互. 就像他的名字描述的那样, crash and stop.

### 共识问题（Consensus）
> Consensus问题的定义包含了三个方面, 一般的Consensus问题定义为:
> 1. termination: 所有进程最终会在有限步数中结束并选取一个值, 算法不会无尽执行下去.
> 2. agreement: 所有非故障进程必须同意同一个值.
> 3. validity: 最终达成一致的值必须是V1到Vn其中一个, 如果所有初始值都是vx, 那么最终结果也必须是vx.

> Consensus要满足以下三个方面: termination, agreement 和 validity. 这三个要素定义了所有Consensus问题的本质. 其中termination是liveness的保证, agreement和validity是safety的保证, 分布式系统的算法中liveness和safety就像一对死对头, 关于liveness和safety的关系, 我们将会在本系列后面的文章中介绍. 所有需要满足这三要素的问题都可以看做是Consensus问题的变体.

#### 两阶段提交
- Jim Gray，1978年，提出了two phase commit（2PC）。
- 2PC不能保证事务的一致性，如果不考虑超时回滚则可以，但也只能保证safety不保证liveness。

#### 三阶段提交
- Dale Skeen，1981，提出了three phase commit（3PC）
- 3PC无法在网络分区情况下，保证一致性。

#### 拜占庭将军问题
同步网络下，拜占庭将军问题可以解决。但是异步网络下，无法解决（Paxos和Raft都解决不了。PBFT在放松了liveness的条件下，可以解决拜占庭将军问题）

#### FLP Impossibility 1985
> No completely asynchronous consensus protocol can tolerate even a single unannounced process death. [ Impossibility of Distributed Consensus with One Faulty Process, Journal of the Association for Computing Machinery, Vol. 32, No. 2, April 1985]

__简单来讲，异步网络下，即使只有一个故障节点，那么不存在共识算法可以保证一致性。__

> 在异步网络环境中只要有一个故障节点, 任何Consensus算法都无法保证正确结束. (这里unannounced process death是指一个进程停止工作了但是其它节点不知道, 其它节点认为是消息延迟或者这个进程特别慢. FLP假设没有拜占庭的故障节点, 那种情况过于困难, 故障在这里的定义是指进程停止尝试读取消息, 相当于crash-stop).
> 
> FLP中设计的模型是一个比现实情况要更可靠的模型, 当然了, 如果连更可靠的模型下一致性问题失效那么现实中更宽松的环境当然也是失效的. FLP还假设异步网络是可靠的, 尽管有延迟但是所有的消息都会投递一次且仅一次, 每个进程只会写入一次状态, 然后就进入了decision state, 这是一个很强的保证, 几乎没有任何网络能达到这样的可靠性. FLP并不要求所有非故障节点都达成一致, 只要有一个进程进入decision state就算达成一致了, 而且一致结果只能是属于{0, 1}, 这也是非常”容易”的agreement约束. 加上前面还提到最多只有一个进程发生故障, 相对于现实情况这已经是一个极端可靠的环境了, 但是在这样可靠的环境中仍然无法有一个一致性算法存在! 更不用说真实世界中的网络分区问题和拜占庭式问题了. FLP的证明告诉我们一致性算法的liveness是无法保证的, 如果你要safety那么就会可能进入无限循环, 每次状态变化都会可能保持当前的状态是可分支的(bivalent).

总结：__不一致是常态__,现实生活中只能补偿，而非阻止。

#### Paxos 1990-2001
> Consensus主要目的是屏蔽掉故障节点的噪音让整个系统正常运行下去, 比如选举过程和状态机复制. 所以Consensus问题对于agreement条件做了放松, 它接受不一致是常态的事实, 既然我无法知道某些节点是挂了还是暂时联系不到, 那我只要关心正确响应的节点, 只要表决能过半即可, 过半表决意味着虽然没有完全一致, 但是”投票结果”被过半成员继承下来了, 这是因为任何两个quorum一定会存在交集(想象一下有A/B/C三个节点, 两个quorum比如AB和AC一定会有A是交集), 所以不管有多少个quorum存在, 我们能确保他们一定会有交集, 所以他们一定能信息互通而最终达成一致, 其他没有达成一致的成员将来在网络恢复后也可以和这部分交集内的节点传播出去的”真理”达成一致.

Paxos的问题：
- 网络分区时，系统可能不可用。
- 决策回合多，延迟高。

> Paxos设计的时候把异步网络的不确定性考虑在内, 放松了liveness的要求, 算法按照crash-recovery的思想设计, 所以Paxos才可以成为这样实际广泛应用而且成功的算法. 但是Paxos也不能容忍拜占庭式故障节点, 要容忍拜占庭式故障实在是太困难了 (比如, Paxos中两个quorum中的交集如果都是叛徒怎么办?).

> 尽管Paxos有很多缺点, 但是Paxos仍然是分布式系统中最重要的一个算法, 比如它的一个重要用途就是 State Machine Replication. 一个Deterministic State Machine对于固定的输入序列一定会产生固定的结果, 不论重复执行多少次, 或者再另外一台一样的机器上执行, 结果都是一样的. 分布式系统中最常见的一个需求就是通过某种路由算法让客户端请求去一组状态一致的服务器, 数据在服务器上的分布要有replica来保证高可用性, 但是replica之间要一致. 状态复制就成为了分布式系统的一个核心问题. Paxos的状态复制可以保证整个系统的所有状态机接受同样的输入序列 (Atomic Broadcast), 如果查询也使用过半表决, 那么你一定会得到正确的结果.

> 这种做法相对于Master-Slave的的状态复制一致性好很多. 比如一旦Master节点挂了, 还没来得及复制的结果会导致Slave之间的状态有可能是不一致的, 如果客户端能够访问到延迟的Slave节点, 那么用户展现的数据将会不一致. 如果你可以牺牲性能来换得更高的一致性, 那么你可以通过Paxos表决查询来屏蔽掉延迟或者有故障的节点. 只要系统中存在一个quorum, 那么状态的一致性就可以保留下去. 比如google的chubby, 只要故障节点不超过一半, 网络没有发生分区, chubby就可以通过Paxos状态机为其他服务器提供分布式锁. 因为chubby分别部署在每个数据中心, 他们没有跨数据中心的通信, 所以网络分区的故障频率不像跨数据中心的情况那么多高, chubby牺牲部分性能和网络分区下的可用性, 换来了一致性.

> 因为Paxos家族的算法写性能都不是很好但是一致性又很重要, 所以实现中我们经常做一些取舍, 让Paxos只处理最关键的信息. 比如Kafka的每个Partition都有多个replicas, 日志的复制本身没有经过Paxos这样高延迟的算法, 但是为了保证负责接受producer请求和跟踪ISR的leader只有一个, Kafka依赖于Zookeeper的ZAB算法来选举leader. 在实际应用中, 状态机复制不太适合很高的吞吐量, 一般都是用于不太频繁写入的重要信息. Zookeeper不能被当做一个OLTP级的数据库用, 它不是分布式系统协同的万能钥匙.


# Ref
- [http://danielw.cn/history-of-distributed-systems-1](http://danielw.cn/history-of-distributed-systems-1)
- [http://danielw.cn/history-of-distributed-systems-2](http://danielw.cn/history-of-distributed-systems-2)
