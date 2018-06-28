<?php
declare(strict_types=1);

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use Doctrine\MongoDB\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Trait BundleAwareTrait
 * @package AntiMattr\Bundle\MongoDBMigrationsBundle\Command
 * @author Watari <watari.mailbox@gmail.com>
 *
 * @property ContainerInterface $container
 */
trait BundleAwareTrait
{

    protected $configuration;

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface
     */

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output): ConfigurationInterface
    {
        if (null === $this->configuration) {
            $configuration = parent::getMigrationConfiguration($input, $output);

            $bundleAlias = $input->getOption('bundle');

            if (Configuration::DEFAULT_PREFIX !== $bundleAlias) {
                $bundle = CommandHelper::getBundleByAlias($bundleAlias, $this->container);
                if (null == $bundle) {
                    throw new \InvalidArgumentException("Bundle is not found for specified alias {$bundleAlias}");
                } else {
                    $configuration = $this->getConfigurationBuilder()
                                          ->setConnection($configuration->getConnection())
                                          ->setOutputWriter($configuration->getOutputWriter())
                                          ->build();
                    CommandHelper::configureConfiguration(
                        $this->container,
                        CommandHelper::getConfigParamsForBundle($this->container, $bundle),
                        $configuration
                    );
                }
            } else {
                CommandHelper::configureConfiguration(
                    $this->container,
                    CommandHelper::getConfigParams($this->container),
                    $configuration
                );
            }

            $this->configuration = $configuration;
        }

        return  $this->configuration;
    }


    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return ConfigurationInterface[]
     */
    protected function getMigrationConfigurations(InputInterface $input, OutputInterface $output): array
    {
        $configs = [$this->getMigrationConfiguration($input, $output)];

        $includeAllBundles = $input->getOption('include-bundles');
        $bundleAliasesList = $input->getOption('include-bundle');

        if (!empty($includeAllBundles) && !empty($bundleAliasesList)) {
            throw new \InvalidArgumentException(
                'Options "include-bundles" and "include-bundle" cannot be specified simultaneously.'
            );
        }

        $outputWriter = new OutputWriter(
            function ($message) use ($output) {
                return $output->writeln($message);
            }
        );
        $databaseConnection = $this->getDatabaseConnection($input);
        $matchedBundles = [];

        if ($includeAllBundles) {
            /** @var array $registeredBundles */
            $registeredBundles = $this->container->getParameter('mongo_db_migrations.bundles');
            /** @var BundleInterface[] $appBundles */
            $appBundles = $this->container->get('kernel')->getBundles();

            foreach ($appBundles as $bundle) {
                $bundleExtension = $bundle->getContainerExtension();
                if (null !== $bundleExtension && !empty($registeredBundles[$bundleExtension->getAlias()])) {
                    $matchedBundles[] = $bundle;
                }
            }
        } elseif (!empty($bundleAliasesList)) {
            /** @var array $registeredBundles */
            $registeredBundles = $this->container->getParameter('mongo_db_migrations.bundles');
            /** @var BundleInterface[] $appBundles */
            $appBundles = $this->container->get('kernel')->getBundles();
            $bundleAliasesMap = \array_flip($bundleAliasesList);
            foreach ($appBundles as $bundle) {
                $bundleExtension = $bundle->getContainerExtension();
                if (
                    null !== $bundleExtension && !empty($registeredBundles[$bundleExtension->getAlias()])
                    && isset($bundleAliasesMap[$bundleExtension->getAlias()])
                ) {
                    $matchedBundles[] = $bundle;
                }
            }
        }

        foreach ($matchedBundles as $bundle) {
            $configs[] = $this->createConfiguration(
                $databaseConnection,
                $outputWriter,
                CommandHelper::getConfigParamsForBundle($this->container, $bundle)
            );
        }

        return $configs;
    }

    protected function createConfiguration(
        Connection $connection,
        OutputWriter $outputWriter,
        array $params
    ): ConfigurationInterface {
        $configuration = $this->getConfigurationBuilder()
                              ->setConnection($connection)
                              ->setOutputWriter($outputWriter)
                              ->build();
        CommandHelper::configureConfiguration($this->container, $params, $configuration);

        return $configuration;
    }
}
