<?php

namespace Hakam\MultiTenancyBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * @author Ramy Hakam <pencilsoft1@gmail.com>
 */
class TenantConnection extends Connection
{
    /** @var mixed */
    protected array $params = [];
    /** @var bool */
    protected bool $isConnected = false;
    /** @var bool */
    protected bool $autoCommit = true;

    public function __construct(array $params, $driver, $config = null, $eventManager = null)
    {
        if ($params['driver'] === 'pdo_sqlite') {
            $driver = new \Doctrine\DBAL\Driver\PDO\SQLite\Driver();
            $params = $this->prepareSqliteParams($params);
        }
        $this->params = $params;
        parent::__construct($params, $driver, $config, $eventManager);
    }

    private function prepareSqliteParams(array $params): array
    {
        if (!isset($params['path'])) {
            if (isset($params['url'])) {
                $url = parse_url($params['url']);
                $params['path'] = substr($url['path'], 1);
            } elseif (isset($params['dbname'])) {
                $params['path'] = $params['dbname'];
            }
        }
        
        if (!isset($params['path'])) {
            throw new Exception(sprintf(
                'SQLite connection requires either a "path", "url", or "dbname" parameter. Got: %s',
                implode(', ', array_keys($params))
            ));
        }
        
        return [
            'driver' => 'pdo_sqlite',
            'path' => $params['path']
        ];
    }

    /**
     * @param array $params
     * @return bool
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function switchConnection(array $params): bool
    {
        $this->close();
        
        // Ensure we have the correct driver
        if (!isset($params['driver'])) {
            $params['driver'] = 'pdo_sqlite';
        }
        
        if ($params['driver'] === 'pdo_sqlite') {
            $this->_driver = new \Doctrine\DBAL\Driver\PDO\SQLite\Driver();
            $params = $this->prepareSqliteParams($params);
        }
        
        $this->params = $params;
        $this->_conn = $this->_driver->connect($params);
        
        if ($this->autoCommit === false) {
            $this->beginTransaction();
        }
        $this->isConnected = true;
        return true;
    }

    public function close(): void
    {
        parent::close();
        $this->isConnected = false;
    }

    public function isConnected(): bool
    {
        try {
            if ($this->_conn === null) {
                return false;
            }
            $this->_conn->query('SELECT 1')->fetchOne();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function connect(): void
    {
        if ($this->isConnected()) {
            return;
        }
        
        $params = $this->params;
        if (!isset($params['driver'])) {
            $params['driver'] = 'pdo_sqlite';
        }
        
        if ($params['driver'] === 'pdo_sqlite') {
            $this->_driver = new \Doctrine\DBAL\Driver\PDO\SQLite\Driver();
            $params = $this->prepareSqliteParams($params);
        }
        
        $this->_conn = $this->_driver->connect($params);
        $this->isConnected = true;
    }
}
