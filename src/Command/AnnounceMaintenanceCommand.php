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

#[AsCommand(
    name: 'maintenance:announce',
    description: 'Announces an upcoming maintenance, without starting it.'
)]
final class AnnounceMaintenanceCommand extends Command
{
    public function __construct(private MaintenanceManager $maintenanceManager)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->addArgument('date', InputArgument::OPTIONAL, 'Planned start (e.g., "2026-12-01 22:00", "tomorrow 22:00")');
        $this->addOption('cancel', null, InputOption::VALUE_NONE, 'Removes the current announcement');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('cancel')) {
            $this->maintenanceManager->cancelAnnouncement();
            $io->success('The maintenance announcement has been removed.');

            return Command::SUCCESS;
        }

        $startsAt = strtotime((string) $input->getArgument('date'));
        if (false === $startsAt || $startsAt <= time()) {
            $io->error('Give a valid date in the future, or use --cancel.');

            return Command::FAILURE;
        }

        $this->maintenanceManager->announce($startsAt);

        $io->success(sprintf('Maintenance announced for %s.', date('Y-m-d H:i', $startsAt)));

        return Command::SUCCESS;
    }
}
