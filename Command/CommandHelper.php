<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Bundle, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration\Configuration;
use Doctrine\ODM\MongoDB\Tools\Console\Helper\DocumentManagerHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
final class CommandHelper
{
    /**
     * configureMigrations.
     *
     * @param ContainerInterface $container
     * @param Configuration      $configuration
     */
    public static function configureMigrations(ContainerInterface $container, Configuration $configuration)
    {
        $params = self::getConfigParams($container, $configuration);
        if (!file_exists($params['dir_name'])) {
            mkdir($params['dir_name'], 0777, true);
        }

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

    protected static function getConfigParams(ContainerInterface $container, Configuration $configuration): array
    {
        $params = [
            'collection_name' => $container->getParameter('mongo_db_migrations.collection_name'),
            'database_name' => $container->getParameter('mongo_db_migrations.database_name'),
            'script_dir_name' => $container->getParameter('mongo_db_migrations.script_dir_name'),
        ];

        if (!empty($configuration->getMigrationsBundleAlias())) {
            $bundleAlias = $configuration->getMigrationsBundleAlias();
            $bundles = $container->getParameter('mongo_db_migrations.bundles');
            if (empty($bundles[$bundleAlias])) {
                throw new \RuntimeException("Bundle with alias {$bundleAlias} has no registered migration configs");
            }
            $bundleConfig = $bundles[$bundleAlias];
            /** @var Bundle[] $bundles */
            $bundles = $container->get('kernel')->getBundles();
            $targetBundle = null;
            foreach ($bundles as $bundle) {
                $containerExtension = $bundle->getContainerExtension();
                if(null !== $containerExtension && $containerExtension->getAlias() === $bundleAlias) {
                    $targetBundle = $bundle;
                    break;
                }
            }
            if (null === $targetBundle) {
                throw new \RuntimeException("Bundle with alias {$bundleAlias} is not found");
            }

            $params['namespace'] = $targetBundle->getNamespace() . '\\' . $bundleConfig['namespace'];
            $params['dir_name'] = $targetBundle->getPath() . '/' . $bundleConfig['dir_name'];
            $params['name'] = $bundleConfig['name'];
        } else {
            $params['name'] = $container->getParameter('mongo_db_migrations.name');
            $params['namespace'] = $container->getParameter('mongo_db_migrations.namespace');
            $params['dir_name'] = $container->getParameter('mongo_db_migrations.dir_name');
        }

        return $params;
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
