<?php
declare(strict_types=1);

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration\ConfigurationBuilder;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationBuilderInterface;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait BundleAwareTrait
 * @package AntiMattr\Bundle\MongoDBMigrationsBundle\Command
 * @author Watari <watari.mailbox@gmail.com>
 */
trait BundleAwareTrait
{
    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \AntiMattr\Bundle\MongoDBMigrationsBundle\Configuration\Configuration
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output): ConfigurationInterface
    {
        $configuration = parent::getMigrationConfiguration($input, $output);

        if ($input->hasOption('bundle')) {
           $configuration->setMigrationsBundleAlias($input->getOption('bundle'));
        }

        return $configuration;
    }


    /**
     * Create instance of configuration builder for command.
     *
     * @return ConfigurationBuilderInterface
     */
    protected function getConfigurationBuilder(): ConfigurationBuilderInterface
    {
        return ConfigurationBuilder::create();
    }
}
