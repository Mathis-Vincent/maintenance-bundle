<?php

namespace MathisVincent\MaintenanceBundle\Command;

use MathisVincent\MaintenanceBundle\Service\MaintenanceManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'maintenance:activate',
    description: 'Activates the maintenance mode of the application.'
)]
final class ActivateMaintenanceModeCommand extends Command
{
    public function __construct(
        private MaintenanceManager $maintenanceManager,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('level', InputArgument::OPTIONAL, 'Maintenance level (e.g., default, admin, help_desk)', 'default');
        $this->addOption('duration', null, InputOption::VALUE_REQUIRED, 'Seconds after which the maintenance ends by itself');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $level = (string) $input->getArgument('level');

        /** @var array{levels: array<string, mixed>} $config */
        $config = $this->params->get('maintenance.config');

        if (!isset($config['levels'][$level])) {
            $io->error(sprintf('The maintenance level "%s" does not exist in your configuration.', $level));

            return Command::FAILURE;
        }

        /** @var string|null $durationOption */
        $durationOption = $input->getOption('duration');
        $duration = null;
        if (null !== $durationOption) {
            $duration = filter_var($durationOption, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (false === $duration) {
                $io->error('The duration must be a positive number of seconds.');

                return Command::FAILURE;
            }
        }

        $this->maintenanceManager->activate($level, $duration);

        $io->success(sprintf('Maintenance mode successfully activated with level: "%s".', $level));

        if (null !== $duration) {
            $io->note(sprintf('It will end by itself on %s.', date('Y-m-d H:i', time() + $duration)));
        }

        return Command::SUCCESS;
    }
}
