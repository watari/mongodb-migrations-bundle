<?php
declare(strict_types=1);


namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use Doctrine\MongoDB\Connection;

/**
 * Class ConfigurationBuilder
 * @package AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration
 * @author Watari <watari.mailbox@gmail.com>
 */
class ConfigurationBuilder extends \AntiMattr\MongoDB\Migrations\Configuration\ConfigurationBuilder
{

    /**
     * @inheritdoc
     *
     * @param Connection   $connection
     * @param OutputWriter $outputWriter
     *
     * @return ConfigurationInterface
     */
    protected function createConfigurationInstance(
        Connection $connection,
        OutputWriter $outputWriter
    ): ConfigurationInterface {
        return new Configuration($connection, $outputWriter);
    }
}
