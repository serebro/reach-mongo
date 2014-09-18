Phalcon ODM Benchmarks
---

$ php ./vendor/bin/athletic -p ./tests/Mongo/Benchmarks/PhalconEvent.php -b ./tests/bootstrap.php
```
Mongo\Benchmarks\ReachEvent
    Method Name                        Iterations    Average Time      Ops/second
    --------------------------------  ------------  --------------    -------------
    inserting                       : [500       ] [0.0004354777336] [2,296.32866]
    updatingSimple                  : [500       ] [0.0005273427963] [1,896.29973]
    updating10times                 : [500       ] [0.0034551749229] [  289.42095]
    findingOneByIndexedField        : [500       ] [0.0001910419464] [5,234.45253]
    findingOneByPrimaryKey          : [500       ] [0.0001557798386] [6,419.31593]
    findingOneByPrimaryKey10Times   : [500       ] [0.0013391375542] [  746.74928]
    findingAllByIndexField          : [500       ] [0.0118254032135] [   84.56371]
    findingAllByIndexFieldAndToArray: [500       ] [0.0115861530304] [   86.30993]
    deleteOneByPrimaryKey           : [500       ] [0.0004432206154] [2,256.21274]
```

### Mongostat

$ mongostat 1
```
connected to: 127.0.0.1
insert  query update delete getmore command flushes mapped  vsize    res faults  locked db idx miss %     qr|qw   ar|aw  netIn netOut  conn       time
    *0     *0     *0     *0       0     1|0       0    25g  53.1g   244m      0             .:0.0%          0       0|0     0|0    62b     7k    11   00:15:27
    *0    578   1775     *0       0  1784|0       0    25g  53.1g   244m      0 reach_testing:8.5%          0       0|0     0|0   857k   385k    12   00:15:28
    *0    257   2570     *0       0  2571|0       0    25g  53.1g   244m      0 reach_testing:9.8%          0       0|0     0|0     1m   399k    12   00:15:29
    *0   2097   1655     *0       0  1655|0       0    25g  53.1g   244m      0 reach_testing:6.1%          0       0|0     0|0   922k     7m    12   00:15:30
    *0   4096     *0     *0      28     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0   370k     2m    12   00:15:31
    *0     72     *0     *0      72     2|0       0    25g  53.1g   244m      0 reach_testing:0.1%          0       0|0     0|0    10k     4m    12   00:15:32
    *0     68     *0     *0      68     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0     9k     4m    12   00:15:33
    *0     70     *0     *0      69     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    10k     4m    12   00:15:34
    *0     70     *0     *0      71     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    10k     4m    12   00:15:35
    *0     79     *0     *0      79     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    11k     5m    12   00:15:36
    *0     71     *0     *0      71     2|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    10k     4m    12   00:15:37
    *0     65     *0     *0      65     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0     9k     4m    12   00:15:38
    *0     67     *0     *0      67     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0     9k     4m    12   00:15:39
    *0     68     *0     *0      68     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0     9k     4m    12   00:15:40
    *0     61     *0     *0      61     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0     8k     4m    12   00:15:41
    *0     79     *0     *0      79     3|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    11k     5m    12   00:15:42
    *0     81     *0     *0      81     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    11k     5m    12   00:15:43
    *0     83     *0     *0      82     1|0       0    25g  53.1g   244m      0 reach_testing:0.0%          0       0|0     0|0    12k     5m    12   00:15:44
    *0    538     *0    500      39     1|0       0    25g  53.1g   244m      0 reach_testing:1.5%          0       0|0     0|0   143k     2m    11   00:15:45
    *0     *0     *0     *0       0     1|0       0    25g  53.1g   244m      0             .:0.0%          0       0|0     0|0    62b     7k    11   00:15:46
```

### Memory

Memory usage: 2.25 mb