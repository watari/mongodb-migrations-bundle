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

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\Interfaces\ConfigurationInterface;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\MongoDB\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class MigrationsMigrateCommand extends MigrateCommand
{
    protected $container;


    public function __construct(?string $name = null, ContainerInterface $container)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('mongodb:migrations:migrate');
        $this->addOption(
            'include-bundle',
            null,
            InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
            'Alias of bundle which migrations should be applied.'
        );
        $this->addOption(
            'include-bundles',
            null,
            InputOption::VALUE_NONE,
            'Indicate that all migrations should be applied.'
        );
        $this->addOption(
            'dm',
            null,
            InputOption::VALUE_OPTIONAL,
            'The document manager to use for this command.',
            'default_document_manager'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        CommandHelper::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));

        $version = $input->getArgument('version');
        $configurations = $this->getMigrationConfigurations($input, $output);

        $isInteractive = $input->isInteractive();


        // warn the user if no dry run and interaction is on
        if ($isInteractive) {
            $question = new ConfirmationQuestion(
                '<question>WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/[n])</question> ',
                false
            );

            $confirmation = $this->getHelper('question')->ask($input, $output, $question);

            if (!$confirmation) {
                $output->writeln('<error>Migration cancelled!</error>');

                return 1;
            }
        }

        foreach ($configurations as $configuration) {

            $migration = $this->createMigration($configuration);
            $this->outputHeader($configuration, $output);

            $executedVersions = $configuration->getMigratedVersions();
            $availableVersions = $configuration->getAvailableVersions();
            $executedUnavailableVersions = array_diff($executedVersions, $availableVersions);

            if (!empty($executedUnavailableVersions)) {
                $output->writeln(sprintf('<error>WARNING! You have %s previously executed migrations in the database that are not registered migrations.</error>',
                    count($executedUnavailableVersions)));
                foreach ($executedUnavailableVersions as $executedUnavailableVersion) {
                    $output->writeln(
                        sprintf(
                            '    <comment>>></comment> %s (<comment>%s</comment>)',
                            Configuration::formatVersion($executedUnavailableVersion),
                            $executedUnavailableVersion
                        )
                    );
                }

                if ($isInteractive) {
                    $question = new ConfirmationQuestion(
                        '<question>Are you sure you wish to continue? (y/[n])</question> ',
                        false
                    );

                    $confirmation = $this
                        ->getHelper('question')
                        ->ask($input, $output, $question);

                    if (!$confirmation) {
                        $output->writeln('<error>Migration cancelled!</error>');

                        return 1;
                    }
                }
            }

            $migration->migrate($version);
        }

        return 0;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return ConfigurationInterface[]
     */
    protected function getMigrationConfigurations(InputInterface $input, OutputInterface $output): array
    {
        $configs = [
            $this->getMigrationConfiguration($input, $output),
        ];
        CommandHelper::configureMigrations($this->container, $configs[0]);

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
            /** @var Bundle[] $appBundles */
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
            /** @var Bundle[] $appBundles */
            $appBundles = $this->container->get('kernel')->getBundles();
            $bundleAliasesMap = \array_flip($bundleAliasesList);
            foreach ($appBundles as $bundle) {
                $bundleExtension = $bundle->getContainerExtension();
                if (
                    null !== $bundleExtension && !empty($registeredBundles[$bundleExtension->getAlias()])
                    && !empty($bundleAliasesMap[$bundleExtension->getAlias()])
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
