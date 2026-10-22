<?php

namespace MathisVincent\MaintenanceBundle\Command;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'maintenance-bundle:maintenance:activate',
    description: 'Activates the maintenance mode of the application.'
)]
class ActivateMaintenanceModeCommand extends Command
{
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('level', InputArgument::OPTIONAL, 'Maintenance level (e.g., default, admin, help_desk)', 'default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $level = $input->getArgument('level');

        $config = $this->params->get('maintenance.config');

        if (!isset($config['levels'][$level])) {
            $io->error(sprintf('The maintenance level "%s" does not exist in your configuration.', $level));
            return Command::FAILURE;
        }

        $this->maintenanceManager->activate($level);

        $io->success(sprintf('Maintenance mode successfully activated with level: "%s".', $level));

        return Command::SUCCESS;
    }
}