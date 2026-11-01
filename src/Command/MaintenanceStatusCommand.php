<?php

namespace MathisVincent\MaintenanceBundle\Command;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'maintenance:status',
    description: 'Displays the current status of the maintenance mode.'
)]
class MaintenanceStatusCommand extends Command
{
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $state = $this->maintenanceManager->getState();

        if (!$state || empty($state['active'])) {
            $io->success('Maintenance mode is inactive.');

            return Command::SUCCESS;
        }

        $level = $state['level'] ?? 'default';
        /** @var array{levels: array<string, mixed>} $config */
        $config = $this->params->get('maintenance.config');

        // The listener lets every request through when the active level is not configured.
        if (!isset($config['levels'][$level])) {
            $io->warning(sprintf('Maintenance mode is active with level "%s", but this level does not exist in your configuration: requests are not blocked.', $level));

            return Command::SUCCESS;
        }

        $io->warning(sprintf('Maintenance mode is active with level: "%s".', $level));

        return Command::SUCCESS;
    }
}
