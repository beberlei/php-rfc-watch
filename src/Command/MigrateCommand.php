<?php

namespace App\Command;

use App\Entity\RequestForComment;
use App\Entity\Rfc;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('php-rfc-watch:migrate')
            ->setDescription('Migrate old to new entities')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $oldRfc = $entityManager->getRepository(RequestForComment::class)->findAll();

        $newRfcs = [];

        foreach ($oldRfc as $oldRfc) {
            assert($oldRfc instanceof RequestForComment);

            $hasNew = $entityManager->getRepository(Rfc::class)->findOneBy(['url' => $oldRfc->getUrl()]);

            if ($hasNew) {
                continue;
            }

            if (!isset($newRfcs[$oldRfc->getUrl()])) {
                $newRfcs[$oldRfc->getUrl()] = new Rfc();
            }

            if (!$oldRfc->getVoteId()) {
                continue;
            }

            $newRfc = $newRfcs[$oldRfc->getUrl()];
            assert($newRfc instanceof Rfc);
            $newRfc->title = $oldRfc->getTitle();
            $newRfc->url = $oldRfc->getUrl();
            $newRfc->status = $oldRfc->getStatus();
            $newRfc->targetPhpVersion = $oldRfc->getTargetPhpVersion();
            $newRfc->discussions = $oldRfc->getDiscussions();
            $newRfc->created = $oldRfc->getCreated();
            $newRfc->rejected = $oldRfc->isRejected();

            $votes = [];
            foreach ($oldRfc->getCurrentResults() as $result) {
                $votes[$result['option']] = $result['votes'];
            }

            $vote = $newRfc->getVoteById($oldRfc->getVoteId());
            $vote->question = $oldRfc->getQuestion();
            $vote->passThreshold = $oldRfc->getPassThreshold();
            $vote->currentVotes = $votes;

            $entityManager->persist($newRfc);

            $output->writeln("Migrated " . $newRfc->title);
        }

        $entityManager->flush();
    }
}