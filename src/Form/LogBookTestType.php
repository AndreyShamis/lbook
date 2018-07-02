<?php

namespace App\Form;

use App\Entity\TestSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookTestType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('timeStart')
            ->add('timeEnd')
            ->add('timeRun')
            ->add('dutUpTimeStart')
            ->add('dutUpTimeEnd')
            ->add('verdict')
            ->add('executionOrder')
            ->add('cycle')
            ->add('disabled')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            'data_class' => TestSearch::class,
        ]);
    }
}
