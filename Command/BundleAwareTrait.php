<?php
declare(strict_types=1);

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
}
