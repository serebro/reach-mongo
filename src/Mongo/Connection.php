<?php

namespace Reach\Mongo;

use MongoClient;
use MongoCollection;
use MongoDB;
use MongoGridFS;
use Reach\EventableTrait;

class Connection
{

    use EventableTrait;

    public static $default_connection_name = 'mongo';

    /** @var array */
    private $_config;

    /** @var MongoClient */
    private $_mongoClient;

    /** @var array */
    private $_collections = [];

    /** @var int */
    private $_default_connection_attempts = 3;

    /** @var int seconds */
    private $_default_connection_timeout = 1;

    private $_logger;

    /** @var array */
    private $_log = [];

    /**
     * @param array  $config array(
     *                       'host' => 'localhost',
     *                       'port' => '27017',
     *                       'username' => '',
     *                       'password' => '',
     *                       'database' => '',
     *                       'options' => array()
     *                       )
     */
    public function __construct(array $config)
    {
        if (empty($config['connection_attempts'])) {
            $config['connection_attempts'] = $this->_default_connection_attempts;
        }
        if (empty($config['connection_timeout'])) {
            $config['connection_timeout'] = $this->_default_connection_timeout;
        }

        $this->_config = $config;
        $dsn = $this->getDsn();
        $options = isset($config['options']) ? $config['options'] : [];

        if (!empty($config['syslog']['class']) && class_exists($config['syslog']['class'])) {
            $class = $config['syslog']['class'];
            $this->_syslogLog = new $class();
            $this->_syslogLog->init();
        }

        if (!empty($config['logger']['class']) && class_exists($config['logger']['class'])) {
            $class = $config['logger']['class'];
            $this->_logger = new $class();
        }

        $this->_mongoClient = $this->createConnection($dsn, $options);
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        $config = $this->_config;
        if (empty($config['host'])) {
            $config['host'] = ini_get('mongo.default_host');
        }
        if (empty($config['port'])) {
            $config['port'] = ini_get('mongo.default_port');
        }

        $hosts = is_array($config['host']) ? $config['host'] : [$config['host']];
        foreach ($hosts as &$host) {
            if (isset($config['port'])) {
                $host = "{$host}:{$config['port']}";
            }
        }

        $dsn = 'mongodb://';
        if (isset($config['username']) && isset($config['password'])) {
            $dsn .= "{$config['username']}:{$config['password']}@";
        }

        return $dsn . implode(',', $hosts) . "/{$config['database']}";
    }

    /**
     * @param string $dsn
     * @param array  $options
     * @throws \Exception
     * @return MongoClient
     */
    public function createConnection($dsn, array $options)
    {
        $client = null;
        $attempts = $this->_config['connection_attempts'];
        $timeout = $this->_config['connection_timeout'];
        do {
            try {
                $client = new MongoClient($dsn, $options);
            } catch(\MongoConnectionException $exception) {
                sleep($timeout);
            } catch(\Exception $exception) {
            }
        } while (!$client && --$attempts > 0);

        if ($client === null) {
            if (isset($exception)) {
                throw new \Exception('Unable to connect to the database', $exception->getCode(), $exception);
            } else {
                $exception = new \Exception(
                    'Unable to connect to the database, after ' . $this->_config['connection_attempts'] . ' attempts'
                );
            }

            throw $exception;
        }

        return $client;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->_config['database'];
    }

    /**
     * @param string $collection_name
     * @return MongoCollection
     */
    public function getMongoCollection($collection_name)
    {
//        if (!isset($this->_collections[$collection_name])) {
        $mongoCollection = $this->_mongoClient->selectCollection($this->_config['database'], $collection_name);
//            $this->_collections[$collection_name] = new Collection($mongoCollection, $class_name);
//        }

//        return $this->_collections[$collection_name];
        return $mongoCollection;
    }

    /**
     * @param string $collection_name
     * @param string $class_name
     * @return Collection
     */
    public function getCollection($collection_name, $class_name = '\stdClass')
    {
        if (!isset($this->_collections[$collection_name])) {
            $mongoCollection = $this->_mongoClient->selectCollection($this->_config['database'], $collection_name);
            $this->_collections[$collection_name] = new Collection($mongoCollection, $this, $class_name);
        }

        return $this->_collections[$collection_name];
    }

    /**
     * @param $collection_name
     * @return MongoGridFS
     */
    public function getGridFSCollection($collection_name)
    {
        if (!isset($this->_collections[$collection_name])) {
            $this->_collections[$collection_name] = $this->getDb()->getGridFS($collection_name);
        }

        return $this->_collections[$collection_name];
    }

    /**
     * @param string $db_name
     * @return MongoDB
     */
    public function selectDB($db_name)
    {
        return $this->_mongoClient->selectDB($db_name);
    }

    /**
     * @return MongoDB
     */
    public function getDb()
    {
        return $this->_mongoClient->selectDB($this->_config['database']);
    }

    /**
     * @param string $name
     */
    public function register($name = null)
    {
        ConnectionManager::registerConnection($this->_config, $name);
    }

    /**
     * @return array
     */
    public function listDatabases()
    {
        return $this->_mongoClient->listDBs();
    }

    /**
     * @return bool
     */
    public function connect()
    {
        return $this->_mongoClient->connect();
    }

    public function close()
    {
        if ($this->_mongoClient !== null) {
            $this->_mongoClient->close(true);
            $this->_mongoClient = null;
        }
        $this->_collections = [];
        $this->_config = [];
        $this->_logger = null;
    }

    public function logging()
    {
        if (!$this->_logger) {
            return;
        }

        $backtrace = debug_backtrace();
        $caller = $backtrace[2];

        $this->logger->log($caller, $backtrace);
    }

    public function getLog()
    {
        return $this->_logger ? $this->_logger->getLog() : [];
    }

    public function stats()
    {
        return $this->getDb()->command(['dbstats' => 1]);
    }

    public function disableProfiler()
    {
        return $this->getDb()->command(['profile' => 0]);
    }

    public function profileSlowQueries($slowms = 100)
    {
        return $this->getDb()->command(['profile' => 1, 'slowms' => (int)$slowms]);
    }

    public function profileAllQueries($slowms = 100)
    {
        return $this->getDb()->command(['profile' => 2, 'slowms' => (int)$slowms]);
    }

    /**
     * @param int $mode
     * @return array
     */
    public function getProfileLog($mode = 0)
    {
        $collection = $this->getDb()->selectCollection('system.profile');
        $cursor = $collection->find()->sort(['ts' => -1]);
        $log = [];
        foreach (iterator_to_array($cursor) as $frame) {
            if (!preg_match('/\.system\..+$/', $frame['ns'])) {
                $query = (empty($frame['query']) ? '' : json_encode(
                        $frame['query']
                    )) . (empty($frame['command']) ? '' : json_encode($frame['command']));
                switch ($mode) {
                    case 1:
                        $log[] = [
                            'ts'    => $frame['ts']->sec,
                            'mills' => $frame['millis'],
                            'ns'    => $frame['ns'],
                            'op'    => $frame['op'],
                            'q'     => $query
                        ];
                        break;
                    case 2:
                        $log[] = date('r', $frame['ts']->sec) . ' (' . sprintf(
                                '%.1f',
                                $frame['millis']
                            ) . 'ms) ' . $frame['ns'] . ' [' . $frame['op'] . '] ' . $query . PHP_EOL;
                        break;
                    default:
                        $log[] = $frame;
                }
            }
        }

        return $log;
    }
}
