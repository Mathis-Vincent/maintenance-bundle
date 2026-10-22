<?php

namespace MathisVincent\MaintenanceBundle\Command;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'maintenance-bundle:maintenance:deactivate',
    description: 'Deactivates the maintenance mode of the application.'
)]
class DeactivateMaintenanceModeCommand extends Command
{
    public function __construct(private MaintenanceManager $maintenanceManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->maintenanceManager->deactivate();

        $io->success('Maintenance mode has been successfully deactivated.');

        return Command::SUCCESS;
    }
}