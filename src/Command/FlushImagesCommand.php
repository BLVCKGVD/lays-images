<?php

namespace App\Command;

use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:flush-images',
    description: 'Удаление фото с указанием минут',
)]
class FlushImagesCommand extends Command
{
    private ImageRepository $imageRepository;
    private EntityManagerInterface $entityManager;

    private ParameterBagInterface $params;

    public function __construct(ImageRepository        $imageRepository,
                                EntityManagerInterface $entityManager,
                                ParameterBagInterface  $params)
    {
        $this->imageRepository = $imageRepository;
        $this->entityManager = $entityManager;
        $this->params = $params;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('time',
                InputArgument::REQUIRED,
                'На сколько минут выставить'
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface  $input,
                               OutputInterface $output,
    ): int
    {
        $io = new SymfonyStyle($input, $output);
        $time = $input->getArgument('time');
        $images = $this->imageRepository->findAll();
        $currentTime = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow'));
        foreach ($images as $image) {
            $timeToDelete = $image->getCreatedAt()
                ->setTimezone(new \DateTimeZone('Europe/Moscow'))
                ->add(new \DateInterval("PT0H{$time}M0S"));
            dump($timeToDelete->format('H:i:s') . "|" . $currentTime->format('H:i:s'));
            dump($currentTime > $timeToDelete);
            if ($currentTime >= $timeToDelete) {
                $this->entityManager->remove($image);
                unlink($this->params->get('uploads_directory') . "/{$image->getName()}");
            }
        }
        $this->entityManager->flush();

        $io->success('Команда выполнена!');

        return Command::SUCCESS;
    }
}
