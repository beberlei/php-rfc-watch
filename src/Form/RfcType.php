<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Rfc;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RfcType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $versions = ['8.2', '8.1', '8.0', '7.4', '7.3', '7.2', '7.1', '7.0', '5.6', '5.5', '5.4'];

        $builder
            ->add('targetPhpVersion', ChoiceType::class, ['choices' => array_combine($versions, $versions)])
            ->add('discussions', TextType::class, ['required' => false])
            ->add('rejected', CheckboxType::class, ['required' => false])
            ->add('status', ChoiceType::class, ['choices' => [Rfc::OPEN => Rfc::OPEN, Rfc::CLOSE => Rfc::CLOSE]])
            ->add('voteList', CollectionType::class, [
                'entry_type' => VoteType::class,
                'by_reference' => true,
            ])
            ->add('submit', SubmitType::class);

        $builder->get('discussions')
            ->addModelTransformer(new CallbackTransformer(
                static fn ($discussionsAsArray) => implode(', ', $discussionsAsArray),
                static function ($discussions) {
                    if ($discussions === null || strlen($discussions) === 0) {
                        return [];
                    }

                    // transform the string back to an array
                    return preg_split('(,[\s]?)', $discussions);
                }
            ));
    }
}
