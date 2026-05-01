<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Request\RegistrationUserRequest;
use App\Exception\HttpConflictException;
use App\Service\RegistrationUserService;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Enum\Role;
use ValueError;

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
            ->addArgument('role', InputArgument::OPTIONAL, 'User role', Role::USER->value);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $dto = $this->buildDto($input);
            $this->registrationService->register($dto);
        } catch (HttpConflictException | InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('User created successfully.');

        return Command::SUCCESS;
    }

    private function buildDto(InputInterface $input): RegistrationUserRequest
    {
        /** @var string $role */
        $role = $input->getArgument('role');

        try {
            $roleEnum = Role::from($role);
        } catch (ValueError) {
            throw new InvalidArgumentException(sprintf(
                'Invalid role "%s". Available: %s',
                $role,
                implode(', ', array_column(Role::cases(), 'value'))
            ));
        }

        /** @var string $email */
        $email = $input->getArgument('email') ?? '';
        /** @var string $password */
        $password = $input->getArgument('password') ?? '';

        $dto = new RegistrationUserRequest();
        $dto
            ->setEmail($email)
            ->setPlainPassword($password)
            ->setRole($roleEnum);

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $messages = array_map(
                fn($v) => $v->getPropertyPath() . ': ' . $v->getMessage(),
                iterator_to_array($violations)
            );

            throw new InvalidArgumentException(implode("\n", $messages));
        }

        return $dto;
    }
}
