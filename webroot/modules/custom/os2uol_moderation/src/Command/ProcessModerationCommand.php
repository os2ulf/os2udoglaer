<?php

declare(strict_types=1);

namespace Drupal\os2uol_moderation\Command;

use Drupal\os2uol_moderation\ModerationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

// phpcs:disable Drupal.Commenting.ClassComment.Missing
#[AsCommand(
  name: 'os2uol_moderation:process_moderation',
  description: 'Process content moderation logic',
  aliases: ['os2uol_process_moderation'],
)]
final class ProcessModerationCommand extends Command {

  /**
   * Constructs a ProcessModerationCommand object.
   */
  public function __construct(
    private readonly ModerationService $moderationService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->moderationService->processModeration();

    return self::SUCCESS;
  }

}
