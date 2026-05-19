<?php

declare(strict_types=1);

namespace Drupal\os2uol_application_forms\Command;

use Drupal\os2uol_application_forms\Service\FreeCourseEvaluationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'os2uol_application_forms:send_free_course_evaluation',
  description: 'Send evaluation emails for free course requests',
  aliases: ['os2uol_send_free_course_evaluation'],
)]
final class SendFreeCourseEvaluationCommand extends Command {

  /**
   * Constructs a SendFreeCourseEvaluationCommand object.
   */
  public function __construct(
    private readonly FreeCourseEvaluationService $evaluationService,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->evaluationService->processEvaluationEmails();
    return self::SUCCESS;
  }

}

