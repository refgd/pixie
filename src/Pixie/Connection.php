<?php namespace Pixie;

use Pixie\QueryBuilder\Raw;
use Viocon\Container;

class Connection
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $adapterConfig;

    /**
     * @var \PDO
     */
    protected $pdoInstances = [];

    /**
     * @var Connection
     */
    protected static $storedConnection;

    /**
     * @var EventHandler
     */
    protected $eventHandler;

    /**
     * @param               $adapter
     * @param array         $adapterConfig
     * @param null|string   $alias
     * @param Container     $container
     */
    public function __construct($adapter, array $adapterConfig, $alias = null, Container $container = null)
    {
        $container = $container ? : new Container();

        $this->container = $container;

        $this->setAdapter($adapter)->setAdapterConfig($adapterConfig);

        // Create event dependency
        $this->eventHandler = $this->container->build('\\Pixie\\EventHandler');

        if ($alias) {
            $this->createAlias($alias);
        }
    }

    /**
     * Create an easily accessible query builder alias
     *
     * @param $alias
     */
    public function createAlias($alias)
    {
        class_alias('Pixie\\AliasFacade', $alias);
        $builder = $this->container->build('\\Pixie\\QueryBuilder\\QueryBuilderHandler', array($this));
        AliasFacade::setQueryBuilderInstance($builder);
    }

    /**
     * Returns an instance of Query Builder
     */
    public function getQueryBuilder()
    {
        return $this->container->build('\\Pixie\\QueryBuilder\\QueryBuilderHandler', array($this));
    }


    /**
     * Create the connection adapter
     */
    protected function connect($config)
    {
        // Build a database connection if we don't have one connected

        $adapter = '\\Pixie\\ConnectionAdapters\\' . $this->adapter;

        $adapterInstance = $this->container->build($adapter, array($this->container));

        $pdo = $adapterInstance->connect($config);

        // Preserve the first database connection with a static property
        if (!static::$storedConnection) {
            static::$storedConnection = $this;
        }

        return $pdo;
    }

    /**
     * @param \PDO $pdo
     *
     * @return $this
     */
    public function setPdoInstance(\PDO $pdo, $sql_type = 'master')
    {
        $this->pdoInstances[$sql_type] = $pdo;
        return $this;
    }

    /**
     * @return \PDO
     */
    public function getPdoInstance($sql_type = 'master')
    {
        if(!isset($this->pdoInstances[$sql_type])){
            if($sql_type == 'master'){
                $this->pdoInstances[$sql_type] = $this->connect($this->adapterConfig);
            }else if($this->adapter == 'Sqlite' || empty($this->adapterConfig[$sql_type])){
                $this->pdoInstances[$sql_type] = $this->getPdoInstance();
            }else{
                $hosts = $this->adapterConfig[$sql_type];
                if(!is_array($hosts)) $hosts = [$hosts];

                $this->pdoInstances[$sql_type] = $this->connect(array_merge($this->adapterConfig, ['host'=>$hosts[array_rand($hosts)]]));
            }
        }

        return $this->pdoInstances[$sql_type];
    }

    /**
     * @param $adapter
     *
     * @return $this
     */
    public function setAdapter($adapter)
    {
        $this->adapter = ucfirst(strtolower($adapter));
        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param array $adapterConfig
     *
     * @return $this
     */
    public function setAdapterConfig(array $adapterConfig)
    {
        $this->adapterConfig = $adapterConfig;
        return $this;
    }

    /**
     * @return array
     */
    public function getAdapterConfig()
    {
        return $this->adapterConfig;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return EventHandler
     */
    public function getEventHandler()
    {
        return $this->eventHandler;
    }

    /**
     * @return Connection
     */
    public static function getStoredConnection()
    {
        return static::$storedConnection;
    }
}
