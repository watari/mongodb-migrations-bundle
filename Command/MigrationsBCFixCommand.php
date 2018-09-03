<?php
declare(strict_types = 1);

/*
 * This file is part of the AntiMattr MongoDB Migrations Bundle, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\Bundle\MongoDBMigrationsBundle\Command;

use AntiMattr\MongoDB\Migrations\Tools\Console\Command\BCFixCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Watari <watari.mailbox@gmail.com>
 */
class MigrationsBCFixCommand extends BCFixCommand
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

        $this->setName('mongodb:migrations:bc-fix');
        $this->addOption(
            'dm',
            null,
            InputOption::VALUE_OPTIONAL,
            'The document manager to use for this command.',
            'default_document_manager'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): void
    {
        CommandHelper::setApplicationDocumentManager($this->getApplication(), $input->getOption('dm'));
        $configuration = $this->getMigrationConfiguration($input, $output);
        CommandHelper::configureConfiguration(
            $this->container,
            CommandHelper::getConfigParams($this->container),
            $configuration
        );

        parent::execute($input, $output);
    }
}
