Reach AR Benchmarks
---

$ php ./vendor/bin/athletic -p ./tests/Mongo/Benchmarks/ReachEvent.php -b ./tests/bootstrap.php 
``` 
Mongo\Benchmarks\ReachEvent
    Method Name                        Iterations    Average Time      Ops/second
    --------------------------------  ------------  --------------    -------------
    inserting                       : [500       ] [0.0003539233208] [ 2,825.47078]
    updatingSimple                  : [500       ] [0.0003165793419] [ 3,158.76581]
    updating10times                 : [500       ] [0.0011092510223] [   901.50920]
    findingOneByIndexedField        : [500       ] [0.0003493251801] [ 2,862.66223]
    findingOneByPrimaryKey          : [500       ] [0.0000286836624] [34,863.05150]
    findingOneByPrimaryKey10Times   : [500       ] [0.0003175363541] [ 3,149.24571]
    findingAllByIndexField          : [500       ] [0.0017707347870] [   564.73731]
    findingAllByIndexFieldAndToArray: [500       ] [0.0030659899712] [   326.15893]
    deleteOneByPrimaryKey           : [500       ] [0.0003230485916] [ 3,095.50955]
```

### Mongostat

$ mongostat 1
```
connected to: 127.0.0.1
insert  query update delete getmore command flushes mapped  vsize    res faults  locked db idx miss %     qr|qw   ar|aw  netIn netOut  conn       time
    *0     *0     *0     *0       0     1|0       0    25g  53.1g   244m      0             .:0.0%          0       0|0     0|0    62b     7k    11   00:41:29
   500     *0    715     *0       0     5|0       0    25g  53.1g   248m      0 reach_testing:3.4%          0       0|0     0|0   332k    87k    12   00:41:30
    *0    788     95     *0     180   289|0       0    25g  53.1g   248m      0 reach_testing:0.2%          0       0|0     1|0   152k     6m    12   00:41:31
    *0    426     *0     *0     317   427|0       0    25g  53.1g   248m      0 reach_testing:0.0%          0       0|0     1|0   118k    10m    12   00:41:32
    *0    286     *0    257     286   287|0       0    25g  53.1g   245m      0 reach_testing:0.8%          0       0|0     0|0   130k     7m    12   00:41:33
    *0     *0     *0    243       0     1|0       0    25g  53.1g   245m      0 reach_testing:0.4%          0       0|0     0|0    45k    21k    11   00:41:34
    *0     *0     *0     *0       0     1|0       0    25g  53.1g   245m      0             .:0.0%          0       0|0     0|0    62b     7k    11   00:41:35
```

### Memory

Memory usage: 2.96 mb