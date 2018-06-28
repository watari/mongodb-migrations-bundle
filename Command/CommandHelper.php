<?php
declare(strict_types=1);

/*
 * This file is part of the AntiMattr MongoDB Migrations Bundle, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
final class CommandHelper
{
    /**
     * configureMigrations.
     *
     * @param ContainerInterface     $container
     * @param ConfigurationInterface $configuration
     */
    public static function configureMigrations(ContainerInterface $container, ConfigurationInterface $configuration)
    {
        $params = self::getConfigParams($container);
        self::configureConfiguration($container, $params, $configuration);
    }

    public static function configureConfiguration(
        ContainerInterface $container,
        array $params,
        ConfigurationInterface $configuration
    ): void {
        if (!\file_exists($params['dir_name'])) {
            \mkdir($params['dir_name'], 0777, true);
        }

        $configuration->setPrefix($params['prefix']);
        $configuration->setMigrationsCollectionName($params['collection_name']);
        $configuration->setMigrationsDatabaseName($params['database_name']);
        $configuration->setMigrationsDirectory($params['dir_name']);
        $configuration->setMigrationsNamespace($params['namespace']);
        $configuration->setName($params['name']);
        $configuration->registerMigrationsFromDirectory($params['dir_name']);
        $configuration->setMigrationsScriptDirectory($params['script_dir_name']);

        self::injectContainerToMigrations($container, $configuration->getMigrations());
    }

    /**
     * @param Application $application
     * @param string      $dmName
     */
    public static function setApplicationDocumentManager(Application $application, $dmName)
    {
        /* @var $dm \Doctrine\ODM\DocumentManager */
        $alias = sprintf(
            'doctrine_mongodb.odm.%s',
            $dmName
        );
        $dm = $application->getKernel()->getContainer()->get($alias);
        $helperSet = $application->getHelperSet();
        $helperSet->set(new DocumentManagerHelper($dm), 'dm');
    }

    public static function getConfigParams(ContainerInterface $container): array
    {
        return [
            'collection_name' => $container->getParameter('mongo_db_migrations.collection_name'),
            'database_name' => $container->getParameter('mongo_db_migrations.database_name'),
            'script_dir_name' => $container->getParameter('mongo_db_migrations.script_dir_name'),
            'name' => $container->getParameter('mongo_db_migrations.name'),
            'namespace' => $container->getParameter('mongo_db_migrations.namespace'),
            'dir_name' => $container->getParameter('mongo_db_migrations.dir_name'),
            'prefix' => \AntiMattr\MongoDB\Migrations\Configuration\Configuration::DEFAULT_PREFIX,
        ];
    }

    public static function getConfigParamsForBundle(
        ContainerInterface $container,
        BundleInterface $bundle
    ): array {
        if ($bundle->getContainerExtension() == null) {
            throw new \InvalidArgumentException(
                "Bundle with name {$bundle->getName()} do not have bundle extension. Bundle alias cannot be defined."
            );
        }
        $bundleAlias = $bundle->getContainerExtension()->getAlias();
        $bundleConfigs = $container->getParameter('mongo_db_migrations.bundles');
        if (empty($bundleConfigs[$bundleAlias])) {
            throw new \RuntimeException("Bundle with alias {$bundleAlias} has no registered migration configs");
        }

        $bundleConfig = $bundleConfigs[$bundleAlias];

        return [
            'collection_name' => $container->getParameter('mongo_db_migrations.collection_name'),
            'database_name' => $container->getParameter('mongo_db_migrations.database_name'),
            'script_dir_name' => $container->getParameter('mongo_db_migrations.script_dir_name'),
            'namespace' => $bundle->getNamespace() . '\\' . $bundleConfig['namespace'],
            'dir_name' => $bundle->getPath() . '/' . $bundleConfig['dir_name'],
            'name' => $bundleConfig['name'],
            'prefix' => $bundleAlias,
        ];
    }

    public static function getBundleByAlias(string $bundleAlias, ContainerInterface $container): ?BundleInterface
    {
        /** @var BundleInterface[] $bundles */
        $bundles = $container->get('kernel')->getBundles();
        $targetBundle = null;
        foreach ($bundles as $bundle) {
            $containerExtension = $bundle->getContainerExtension();
            if (null !== $containerExtension && $containerExtension->getAlias() === $bundleAlias) {
                $targetBundle = $bundle;
                break;
            }
        }

        return $targetBundle;
    }

    /**
     * injectContainerToMigrations.
     *
     * Injects the container to migrations aware of it.
     *
     * @param ContainerInterface $container
     * @param array              $versions
     */
    private static function injectContainerToMigrations(ContainerInterface $container, array $versions)
    {
        foreach ($versions as $version) {
            $migration = $version->getMigration();
            if ($migration instanceof ContainerAwareInterface) {
                $migration->setContainer($container);
            }
        }
    }
}
