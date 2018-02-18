<?php

namespace App\Form;

use App\Entity\LogBookTest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
            //'data_class' => LogBookTest::class,
        ]);
    }
}
