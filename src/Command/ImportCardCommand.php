<?php

namespace App\Command;

use App\Entity\Artist;
use App\Entity\Card;
use App\Repository\ArtistRepository;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'import:card',
    description: 'Add a short description for your command',
)]
class ImportCardCommand extends Command
{
    private array $times;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private array $csvHeader = []
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '4G');
        // On récupère le temps actuel
        $io = new SymfonyStyle($input, $output);
        $filepath = __DIR__ . '/../../data/cards.csv';
        $handle = fopen($filepath, 'r');

        // On récupère le temps actuel
        $start = microtime(true);

        $this->logger->info('Importing cards from ' . $filepath);
        if ($handle === false) {
            $io->error('File not found');
            return Command::FAILURE;
        }

        $i = 0;
        $this->csvHeader = fgetcsv($handle);
        $repoCard = $this->entityManager->getRepository(Card::class);
        $uuidInDatabase = $repoCard->getAllUuids();

        $progressIndicator = new ProgressIndicator($output);
        $progressIndicator->start('Importing cards...');

        function printmem(string $msg)
        {
            echo "\r\n" . $msg . ' usage: ' . memory_get_usage() / 1000000 . 'mB real_usage: ' . memory_get_usage(true) / 1000000 . 'mB ';
        }

        printmem('Start');
        $use_rows = true;
        $this->times = [
            "readcsv" => 0,
            "flush" => 0,
            "clear" => 0,
            "persist" => 0,
            "isuuidindb" => 0,
        ];
        if ($use_rows) {
            while (($rows = $this->readCsvBy($handle, 300)) !== false) {
                foreach ($rows as $row) {
                    $time = microtime(true);
                    $isNotInDb = !in_array($row['uuid'], $uuidInDatabase);
                    /*$isNotInDb = $repoCard->find($row['uuid']) == null;*/
                    /*$isNotInDb = true;*/
                    $this->times["isuuidindb"] += microtime(true) - $time;
                    if ($isNotInDb) {
                        $time = microtime(true);
                        $this->addCard($row);
                        $this->times["persist"] += microtime(true) - $time;
                    }
                    $i++;
                }

                /*printmem("Iteration $i avant flush");*/
                $time = microtime(true);
                try {
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    $io->error("$i :".$e->getMessage());
                    return Command::FAILURE;
                }
                $this->times["flush"] += microtime(true) - $time;
                $time = microtime(true);
                $this->entityManager->clear();
                $this->times["clear"] += microtime(true) - $time;
                /*printmem("Iteration $i après flush");*/
                /*$progressIndicator->advance();*/
            }
        } else {
            while (($row = $this->readCSV($handle)) !== false) {
                $i++;

                if (!in_array($row['uuid'], $uuidInDatabase)) {
                    $this->addCard($row);
                }

                if ($i % 300 === 0) {
                    printmem("Iteration $i avant flush");
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    printmem("Iteration $i après flush");
                    /*$progressIndicator->advance();*/
                }
            }
        }
        var_dump($this->times);
        // Toujours flush en sorti de boucle
        $this->entityManager->flush();
        $progressIndicator->finish('Importing cards done.');

        fclose($handle);

        // On récupère le temps actuel, et on calcule la différence avec le temps de départ
        $end = microtime(true);
        $timeElapsed = $end - $start;
        $io->success(sprintf('Imported %d cards in %.2f seconds', $i, $timeElapsed));
        return Command::SUCCESS;
    }

    private function readCSV(mixed $handle): array|false
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return false;
        }
        return array_combine($this->csvHeader, $row);
    }

    private function readCsvBy(mixed $handle, int $limit): array|false
    {
        $rows = [];
        $time = microtime(true);
        for ($i = 0; $i < $limit; $i++) {
            $row = fgetcsv($handle);
            if ($row === false) {
                if ($i === 0) {
                    return false;
                }
                break;
            }
            $rows[] = array_combine($this->csvHeader, $row);
        }
        $this->times["readcsv"] += microtime(true) - $time;
        return $rows;
    }

    private function addCard(array $row)
    {
        $uuid = $row['uuid'];

        $card = new Card();
        $card->setUuid($uuid);
        $card->setManaValue($row['manaValue']);
        $card->setManaCost($row['manaCost']);
        $card->setName($row['name']);
        $card->setRarity($row['rarity']);
        $card->setSetCode($row['setCode']);
        $card->setSubtype($row['subtypes']);
        $card->setText($row['text']);
        $card->setType($row['type']);
        $this->entityManager->persist($card);
    }
}
