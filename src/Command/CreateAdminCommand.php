<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Créer un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si l'admin existe déjà
        $existingAdmin = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);

        if ($existingAdmin) {
            $io->warning('Un administrateur avec cet email existe déjà');
            return Command::SUCCESS;
        }

        try {
            // Créer l'admin
            $admin = new User();
            $admin->setEmail('admin@example.com');
            $admin->setNom('Admin');
            $admin->setPrenom('Super');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setVerified(true);

            // Hacher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
            $admin->setPassword($hashedPassword);

            // Sauvegarder
            $this->entityManager->persist($admin);
            $this->entityManager->flush();

            $io->success('Administrateur créé avec succès !');
            $io->info('Email: admin@example.com');
            $io->info('Mot de passe: admin123');
            
            // Vérifier les rôles
            $io->info('Rôles: ' . implode(', ', $admin->getRoles()));

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}