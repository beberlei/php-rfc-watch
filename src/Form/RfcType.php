<?php
/**
 * Created by PhpStorm.
 * User: benny
 * Date: 02.02.19
 * Time: 16:54
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class RfcType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $versions = ['8.0', '7.4', '7.3', '7.2', '7.1', '7.0'];

        $builder
            ->add('targetPhpVersion', ChoiceType::class, ['choices' => array_combine($versions, $versions)])
            ->add('discussions', TextType::class, ['required' => false])
            ->add('rejected', CheckboxType::class, ['required' => false])
            ->add('submit', SubmitType::class)
        ;

        $builder->get('discussions')
            ->addModelTransformer(new CallbackTransformer(
                function ($discussionsAsArray) {
                    // transform the array to a string
                    return implode(', ', $discussionsAsArray);
                },
                function ($discussions) {
                    if (strlen($discussions) === 0) {
                        return [];
                    }

                    // transform the string back to an array
                    return preg_split('(,[\s]?)', $discussions);
                }
            ))
        ;
    }

}