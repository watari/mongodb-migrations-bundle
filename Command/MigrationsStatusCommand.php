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

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\StatusCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class MigrationsStatusCommand extends StatusCommand
{
    use BundleAwareTrait;

    protected $container;

    public function __construct(?string $name = null, ContainerInterface $container)
    {
        parent::__construct($name);
        $this->container = $container;
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setName('mongodb:migrations:status');
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
            'bundle',
            null,
            InputOption::VALUE_OPTIONAL,
            'Alias of bundle for which migration will be generated.',
            Configuration::DEFAULT_PREFIX
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

        $configurations = $this->getMigrationConfigurations($input, $output);

        foreach ($configurations as $configuration) {
            $this->configuration = $configuration;
            parent::execute($input, $output);
        }

    }
}
