<?php
namespace App\Utils\Traits;

/**
 * Trait Capsule
 * @package App\Utils\Traits
 */
trait Capsule
{

    /**
     * @param string $connection
     * @return mixed
     */
    public function connection($connection = 'default')
    {
        return $this->_container->get('sqlite')->connection($connection);
    }


    /**
     * @param $tableName
     * @return mixed
     */
    public function table($tableName)
    {
        return $this->connection()->table($tableName);
    }

}