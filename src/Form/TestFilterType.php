<?php

namespace App\Form;

use App\Entity\TestFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TestFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('suiteUuid')
            ->add('cluster')
            ->add('testList')
            ->add('testingLevel')
            ->add('projectName')
//            ->add('user')
        
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TestFilter::class,
        ]);
    }
}
