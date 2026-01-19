<?php

namespace App\Form;

use App\Entity\LogBookSuiteInfo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookSuiteInfoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('uuid')
            ->add('testsCount')
            ->add('assignee')
            ->add('testingLevel')
            ->add('setupConfig')
            ->add('stop_on_fail')
            ->add('stop_on_error')
            ->add('suite_timeout')
            ->add('hours_to_run')
            ->add('test_timeout')
            ->add('labels')
            ->add('supported_farms')
            ->add('lastSeen')
            ->add('creationCount')
            ->add('suiteMode')
            ->add('subscribers')
            ->add('failureSubscribers')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookSuiteInfo::class,
        ]);
    }
}
