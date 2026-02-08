<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Request\RegistrationUserRequest;
use App\Exception\HttpConflictException;
use App\Service\RegistrationUserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly RegistrationUserService $registrationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED)
            ->addArgument('password', InputArgument::REQUIRED)
            ->addArgument('role', InputArgument::OPTIONAL, 'User role', 'ROLE_USER');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /**
         * @var string $email
         */
        $email = $input->getArgument('email') ?? '';
        /**
         * @var string $password
         */
        $password = $input->getArgument('password') ?? '';
        /**
         * @var string $role
         */
        $role = $input->getArgument('role') ?? '';
        $io = new SymfonyStyle($input, $output);
        $dto = new RegistrationUserRequest();
        $dto
            ->setEmail($email)
            ->setPlainPassword($password)
            ->setRole($role);

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $io->error($violation->getPropertyPath() . ': ' . $violation->getMessage());
            }

            return Command::FAILURE;
        }

        try {
            $this->registrationService->register($dto);
        } catch (HttpConflictException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
        $io->success('User created successfully.');

        return Command::SUCCESS;
    }
}
