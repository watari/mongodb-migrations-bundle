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
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\VersionCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class MigrationsVersionCommand extends VersionCommand
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

        $this->setName('mongodb:migrations:version');
        $this->addOption(
            'bundle',
            null,
            InputOption::VALUE_OPTIONAL,
            'Alias of bundle for which action is performed',
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

        parent::execute($input, $output);
    }
}
