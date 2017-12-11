# Binlog File Analyze

## 0. Environment
- mysql

```
mysqld  Ver 5.7.19-log for osx10.12 on x86_64 (Homebrew)
```

- binlog files: 

```
$mysql ll *mysql-bin*
-rw-r-----  1 nuc  admin   974B 12  7 18:56 mysql-bin.000001
-rw-r-----  1 nuc  admin    38K 12  8 15:44 mysql-bin.000002
-rw-r-----  1 nuc  admin    38B 12  7 18:56 mysql-bin.index
```

## 1. Look at the file
```
$mysql xxd mysql-bin.000001|more
00000000: fe62 696e 7bfd 285a 0f01 0000 0077 0000  .bin{.(Z.....w..
00000010: 007b 0000 0000 0004 0035 2e37 2e31 392d  .{.......5.7.19-
00000020: 6c6f 6700 0000 0000 0000 0000 0000 0000  log.............
00000030: 0000 0000 0000 0000 0000 0000 0000 0000  ................
00000040: 0000 0000 0000 0000 0000 007b fd28 5a13  ...........{.(Z.
```
```
$mysql mysqlbinlog mysql-bin.000001|more
/*!50530 SET @@SESSION.PSEUDO_SLAVE_MODE=1*/;
/*!50003 SET @OLD_COMPLETION_TYPE=@@COMPLETION_TYPE,COMPLETION_TYPE=0*/;
DELIMITER /*!*/;
# at 4
#171207 16:36:11 server id 1  end_log_pos 123 CRC32 0xfbd38efa  Start: binlog v 4, server v 5.7.19-log created 171207 16:36:11 at startup
ROLLBACK/*!*/;
BINLOG '
e/0oWg8BAAAAdwAAAHsAAAAAAAQANS43LjE5LWxvZwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
AAAAAAAAAAAAAAAAAAB7/ShaEzgNAAgAEgAEBAQEEgAAXwAEGggAAAAICAgCAAAACgoKKioAEjQA
AfqO0/s=
'/*!*/;
# at 123
```

## 2. Step by Step

- `0xFE 0x62 0x69 0x6e` is the magic number bytes, which means `0xFE bin`, signature of mysql binlog file. See also [mysql internals](https://dev.mysql.com/doc/internals/en/binary-log-structure-and-contents.html)

```
//sql/log_event.h:459
/* 4 bytes which all binlogs should begin with */
#define BINLOG_MAGIC        "\xfe\x62\x69\x6e"
```

- `7bfd285a` represents `171207 16:36:11`, little endian

```
php > echo bin2hex(pack("L",strtotime('2017-12-07 16:36:11')));
7bfd285a
```

- `0f` represents `15`,`FORMAT_DESCRIPTION_EVENT`

```
//sql/log_event.h:634
/**
  @enum Log_event_type

  Enumeration type for the different types of log events.
*/
enum Log_event_type
{
  /*
    Every time you update this enum (when you add a type), you have to
    fix Format_description_log_event::Format_description_log_event().
  */
  UNKNOWN_EVENT= 0,
  START_EVENT_V3= 1,
  QUERY_EVENT= 2,
  STOP_EVENT= 3,
  ROTATE_EVENT= 4,
  INTVAR_EVENT= 5,
  LOAD_EVENT= 6,
  SLAVE_EVENT= 7,  /* Unused. Slave_log_event code has been removed. (15th Oct. 2010) */
  CREATE_FILE_EVENT= 8,
  APPEND_BLOCK_EVENT= 9,
  EXEC_LOAD_EVENT= 10,
  DELETE_FILE_EVENT= 11,
  /*
    NEW_LOAD_EVENT is like LOAD_EVENT except that it has a longer
    sql_ex, allowing multibyte TERMINATED BY etc; both types share the
    same class (Load_log_event)
  */
  NEW_LOAD_EVENT= 12,
  RAND_EVENT= 13,
  USER_VAR_EVENT= 14,
  FORMAT_DESCRIPTION_EVENT= 15,
  XID_EVENT= 16,
  BEGIN_LOAD_QUERY_EVENT= 17,
  EXECUTE_LOAD_QUERY_EVENT= 18,

  TABLE_MAP_EVENT = 19,

  /*
    These event numbers were used for 5.1.0 to 5.1.15 and are
    therefore obsolete.
   */
  PRE_GA_WRITE_ROWS_EVENT = 20,
  PRE_GA_UPDATE_ROWS_EVENT = 21,
  PRE_GA_DELETE_ROWS_EVENT = 22,

  /*
    These event numbers are used from 5.1.16 until mysql-trunk-xx
   */
  WRITE_ROWS_EVENT_V1 = 23,
  UPDATE_ROWS_EVENT_V1 = 24,
  DELETE_ROWS_EVENT_V1 = 25,

  /*
    Something out of the ordinary happened on the master
   */
  INCIDENT_EVENT= 26,

  /*
    Heartbeat event to be send by master at its idle time 
    to ensure master's online status to slave 
  */
  HEARTBEAT_LOG_EVENT= 27,

  /*
    In some situations, it is necessary to send over ignorable
    data to the slave: data that a slave can handle in case there
    is code for handling it, but which can be ignored if it is not
    recognized.
  */
  IGNORABLE_LOG_EVENT= 28,
  ROWS_QUERY_LOG_EVENT= 29,

  /* Version 2 of the Row events */
  WRITE_ROWS_EVENT = 30,
  UPDATE_ROWS_EVENT = 31,
  DELETE_ROWS_EVENT = 32,

  GTID_LOG_EVENT= 33,
  ANONYMOUS_GTID_LOG_EVENT= 34,

  PREVIOUS_GTIDS_LOG_EVENT= 35,
  /*
    Add new events here - right above this comment!
    Existing events (except ENUM_END_EVENT) should never change their numbers
  */

  ENUM_END_EVENT /* end marker */
};
```

- `01 00 00 00`, server_id, below is my conf

```
$mysql cat /etc/my.cnf
[mysqld]
log-bin    = mysql-bin
server-id  = 1
binlog_format=row
``` 

- `77 00 00 00`, represents `119`, event size
- `7b 00 00 00`, represents `123`, next event position, you can verify that from above decoded info by mysqlbinlog, or you  can also see that, `4(magic num) + 119(current event size) = 123(next event start position)`
	- `0-3`, 4 byte magic num
	- `4-122`, 119 byte, first event
	- `123-...`, starts the second event 	
- `00 00` flag, configuration, see [mysql internal](https://dev.mysql.com/doc/internals/en/) for detail. 

Above is common header which cost 19 bytes. then comes the specific event data, size shall be

- `119 - 19 = 100` or
- `119 - 19 - 4 = 96` when checksum enabled, in which case, the last 4 bytes denote checksum.


In my conf,shows

```
mysql> show variables like "%binlog_checksum%";
+-----------------+-------+
| Variable_name   | Value |
+-----------------+-------+
| binlog_checksum | CRC32 |
+-----------------+-------+
1 row in set (0.01 sec)
```
So checksum is enabled. The data size would be 96, follows by 4 byte checksum. First event ends here, then comes the second event, which gives us

```
$mysql xxd -s 23 -l 104 mysql-bin.000001
00000017: 0400 352e 372e 3139 2d6c 6f67 0000 0000  ..5.7.19-log....
00000027: 0000 0000 0000 0000 0000 0000 0000 0000  ................
00000037: 0000 0000 0000 0000 0000 0000 0000 0000  ................
00000047: 0000 0000 7bfd 285a 1338 0d00 0800 1200  ....{.(Z.8......
00000057: 0404 0404 1200 005f 0004 1a08 0000 0008  ......._........
00000067: 0808 0200 0000 0a0a 0a2a 2a00 1234 0001  .........**..4..
00000077: fa8e d3fb 7bfd 285a                      ....{.(Z
```

- `0x04...0001` data area of `FORMAT_DESCRIPTION_EVENT`, see [mysql internals](https://dev.mysql.com/doc/internals/en/event-data-for-specific-event-types.html) for detail.
- `0xfa 0x8e 0xd3 0xfb`, little endian, which should be `0xfbd38efa` in readable order, is the checksum, which again you can verify that in the decoded info:`CRC32 0xfbd38efa`
- `0x7bfd285a` is the start of the second event, also a timestamp
```
php > echo bin2hex(pack("N",strtotime('2017-12-07 16:36:11')));
5a28fd7b
```

## -1. Wrap up
mysql binlog file:

```
magicNum:4	|timestamp:4	|type:1	|id:4	|size:4	|nextPos:4	|flag:2	|event data:NaN	|checksum:4	|...
```

Above is a real world binlog foramt example.
Done.


