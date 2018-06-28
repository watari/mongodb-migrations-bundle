<?php
declare(strict_types=1);

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration;

/**
 * Class Configuration
 * @package AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration
 * @author Watari <watari.mailbox@gmail.com>
 */
class Configuration extends \AntiMattr\MongoDB\Migrations\Configuration\Configuration
{
    /**
     *  Alias of bundle for which command is running.
     *
     * @var string|null
     */
    protected $migrationsBundleAlias;

    /**
     * @return null|string
     */
    public function getMigrationsBundleAlias(): ?string
    {
        return $this->migrationsBundleAlias;
    }

    /**
     * @param null|string $migrationsBundleAlias
     *
     * @return void
     */
    public function setMigrationsBundleAlias(?string $migrationsBundleAlias): void
    {
        $this->migrationsBundleAlias = $migrationsBundleAlias;
    }
}
