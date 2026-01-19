<?php

namespace App\Form;

use App\Entity\LogBookDefect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LogBookDefectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('isExternal')
//            ->add('anotherReporter')
            ->add('ext_url')
            ->add('ext_id')
//            ->add('reporter')
//            ->add('logBookCycleReports')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LogBookDefect::class,
        ]);
    }
}
